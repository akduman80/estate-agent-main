<?php

namespace App\Services;

use App\Enums\AccountStatus;
use App\Enums\BusinessApprovalStatus;
use App\Enums\UserRole;
use App\Models\Listing;
use App\Models\Post;
use App\Models\PropertyComment;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PropertyInteractions
{
    public function target(string $type, int $id): Listing|Post
    {
        abort_unless(in_array($type, ['listing', 'post'], true), 404);
        if ($type === 'listing') {
            return Listing::query()->published()->findOrFail($id);
        }

        return Post::query()->where('status', 'published')->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()))->findOrFail($id);
    }

    public function guardUser(string $action): User
    {
        $user = auth()->user();
        abort_unless($user, 401);
        abort_unless($user->account_status === AccountStatus::ACTIVE, 403, 'Etkileşim için aktif bir üyelik gerekir.');
        abort_if(DB::table('property_interaction_preferences')->where('user_id', $user->id)->where('blocked', true)->exists(), 403, 'Etkileşim yetkiniz moderasyon nedeniyle kapalı.');
        $key = 'property:'.$action.':'.$user->id;
        if (RateLimiter::tooManyAttempts($key, $action === 'like' ? 30 : 8)) {
            throw ValidationException::withMessages(['body' => 'Çok sık işlem yaptınız. Bir dakika sonra tekrar deneyin.']);
        }
        RateLimiter::hit($key, 60);

        return $user;
    }

    public function body(string $value): string
    {
        $text = trim($value);
        $fold = Str::ascii(mb_strtolower($text), 'tr');
        if (mb_strlen($text) < 3 || mb_strlen($text) > 2000 || preg_match('/<|>|https?:|www\.|\b(?:\+?90\s*)?0?5(?:[\s().-]*\d){9}\b|(?<!\d)(?:\d[\s.-]*){11}(?!\d)|[\w.+-]+@[\w.-]+\.[a-z]{2,}/iu', $text) || preg_match('/\b(?:siktir|orospu|sikerim|oldururum|amk|ibne)\b/', $fold)) {
            throw ValidationException::withMessages(['body' => '3–2000 karakter kullanın. Hakaret, tehdit, bağlantı, HTML veya kişisel bilgi paylaşmayın.']);
        }

        return $text;
    }

    public function answerLabel(Listing $listing, User $user): ?string
    {
        if (! $user->isActive()) {
            return null;
        }
        if ($listing->user_id === $user->id) {
            if ($user->role === UserRole::INDIVIDUAL) {
                return 'İlan Sahibi';
            }
            if ($user->businessProfile?->approval_status === BusinessApprovalStatus::APPROVED) {
                return 'Yetkili Emlak İşletmesi';
            }

            // Owning accounts may answer, without an unverified business badge.
            return '';
        }
        if ($listing->businessProfile?->user_id === $user->id && $listing->businessProfile?->approval_status === BusinessApprovalStatus::APPROVED) {
            return 'Yetkili Emlak İşletmesi';
        }
        if ($user->role === UserRole::CONSULTANT && $listing->businessProfile?->approval_status === BusinessApprovalStatus::APPROVED && $user->consultantAssignments()->where('agent_id', $listing->businessProfile->user_id)->where('status', 'active')->exists()) {
            return 'Yetkili Emlak İşletmesi';
        }

        return null;
    }

    public function setLike(string $type, int $id, bool $liked): void
    {
        $user = $this->guardUser('like');
        $this->target($type, $id);
        $key = ['user_id' => $user->id, $type.'_id' => $id];
        if ($liked) {
            DB::table('property_likes')->insertOrIgnore($key + ['created_at' => now(), 'updated_at' => now()]);
        } else {
            DB::table('property_likes')->where($key)->delete();
        }
    }

    public function submit(string $type, int $id, string $body, string $requestId, ?int $parentId = null): PropertyComment
    {
        $user = $this->guardUser('comment');
        $target = $this->target($type, $id);
        $body = $this->body($body);
        abort_unless(Str::isUuid($requestId), 422);

        return DB::transaction(function () use ($type, $id, $body, $requestId, $parentId, $user, $target) {
            $existing = PropertyComment::where('request_id', $requestId)->first();
            if ($existing) {
                abort_unless($existing->user_id === $user->id && $existing->{$type.'_id'} === $id, 403);

                return $existing;
            }
            $parent = $parentId ? PropertyComment::where($type.'_id', $id)->whereNull('parent_id')->where('status', 'approved')->findOrFail($parentId) : null;
            $label = null;
            if ($parent && $type === 'listing') {
                $label = $this->answerLabel($target, $user);
                abort_if($label === null, 403);
            }
            $comment = PropertyComment::create(['user_id' => $user->id, $type.'_id' => $id, 'parent_id' => $parent?->id, 'kind' => $type === 'post' ? ($parent ? 'reply' : 'comment') : ($parent ? 'answer' : 'question'), 'body' => $body, 'status' => Setting::get('property_premoderation', true) ? 'pending' : 'approved', 'author_label' => $label ?: null, 'request_id' => $requestId]);
            if ($comment->status === 'approved') {
                $this->notify($comment);
            }

            return $comment;
        });
    }

    public function edit(PropertyComment $comment, string $body): void
    {
        $user = $this->guardUser('comment');
        abort_unless($comment->user_id === $user->id && $comment->status !== 'deleted', 403);
        $this->target($comment->listing_id ? 'listing' : 'post', $comment->listing_id ?: $comment->post_id);
        // Every edit is reviewed; automatic approval of edited text could bypass moderation.
        $comment->update(['body' => $this->body($body), 'status' => 'pending']);
    }

    public function removeOwn(PropertyComment $comment): void
    {
        $user = $this->guardUser('comment');
        abort_unless($comment->user_id === $user->id, 403);
        $comment->update(['body' => '', 'status' => 'deleted', 'author_label' => null]);
    }

    public function moderate(PropertyComment $comment, string $status, string $reason): void
    {
        abort_unless(auth()->user()?->role === UserRole::SUPERVISOR, 403);
        abort_unless(in_array($status, ['approved', 'rejected', 'deleted'], true) && mb_strlen(trim($reason)) >= 3 && mb_strlen($reason) <= 500, 422);
        DB::transaction(function () use ($comment, $status, $reason) {
            $comment = PropertyComment::lockForUpdate()->findOrFail($comment->id);
            abort_if($comment->status === 'deleted', 422);
            $comment->update(['status' => $status, ...($status === 'deleted' ? ['body' => '', 'author_label' => null] : [])]);
            DB::table('property_moderation_logs')->insert(['user_id' => auth()->id(), 'comment_id' => $comment->id, 'action' => $status, 'reason' => $reason, 'created_at' => now(), 'updated_at' => now()]);
            if ($status === 'approved') {
                $this->notify($comment);
            }
        });
    }

    public function notify(PropertyComment $comment): void
    {
        $recipient = $comment->parent?->user_id ?: $comment->listing?->user_id;
        if (! $recipient || $recipient === $comment->user_id || DB::table('property_interaction_preferences')->where('user_id', $recipient)->where('notifications', false)->exists()) {
            return;
        }
        DB::table('property_notifications')->insertOrIgnore(['user_id' => $recipient, 'comment_id' => $comment->id, 'message' => $comment->parent_id ? 'Sorunuza veya yorumunuza bir yanıt geldi.' : 'İlanınıza yeni bir soru geldi.', 'created_at' => now(), 'updated_at' => now()]);
    }
}
