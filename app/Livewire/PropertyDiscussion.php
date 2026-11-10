<?php

namespace App\Livewire;

use App\Models\PropertyComment;
use App\Models\PropertyReport;
use App\Services\PropertyInteractions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class PropertyDiscussion extends Component
{
    use WithPagination;

    #[Locked]
    public string $targetType;

    #[Locked]
    public int $targetId;

    #[Locked]
    public string $requestId;

    public string $body = '';

    public ?int $replyTo = null;

    public ?int $editing = null;

    public string $sort = 'oldest';

    public string $notice = '';

    public ?int $replyThread = null;

    public function showReplies(int $id): void
    {
        $comment = $this->comment($id);
        abort_unless(! $comment->parent_id && in_array($comment->status, ['approved', 'deleted'], true), 404);
        $this->replyThread = $id;
        $this->resetPage('replyPage');
    }

    public function cancel(): void
    {
        $this->reset('body', 'replyTo', 'editing');
        $this->resetValidation();
    }

    public function mount(string $targetType, int $targetId): void
    {
        $this->targetType = $targetType;
        $this->targetId = $targetId;
        $this->requestId = (string) Str::uuid();
        app(PropertyInteractions::class)->target($targetType, $targetId);
    }

    public function like(bool $desired): void
    {
        if (! auth()->check()) {
            $this->redirectRoute('login');

            return;
        }
        app(PropertyInteractions::class)->setLike($this->targetType, $this->targetId, $desired);
    }

    private function comment(int $id): PropertyComment
    {
        return PropertyComment::where($this->targetType.'_id', $this->targetId)->findOrFail($id);
    }

    public function reply(int $id): void
    {
        $comment = $this->comment($id);
        abort_unless($comment->status === 'approved' && ! $comment->parent_id, 403);
        $this->replyTo = $id;
        $this->editing = null;
    }

    public function edit(int $id): void
    {
        $comment = $this->comment($id);
        abort_unless($comment->user_id === auth()->id() && $comment->status !== 'deleted', 403);
        $this->editing = $id;
        $this->replyTo = null;
        $this->body = $comment->body;
    }

    public function submit(): void
    {
        if (! auth()->check()) {
            $this->redirectRoute('login');

            return;
        }
        $service = app(PropertyInteractions::class);
        if ($this->editing) {
            $service->edit($this->comment($this->editing), $this->body);
        } else {
            $service->submit($this->targetType, $this->targetId, $this->body, $this->requestId, $this->replyTo);
        }
        $this->reset('body', 'replyTo', 'editing');
        $this->requestId = (string) Str::uuid();
        $this->notice = 'İçeriğiniz alındı. Onay gerektiren içerikler moderasyon sonrası görünür.';
    }

    public function remove(int $id): void
    {
        app(PropertyInteractions::class)->removeOwn($this->comment($id));
    }

    public function report(int $id): void
    {
        if (! auth()->check()) {
            $this->redirectRoute('login');

            return;
        }
        app(PropertyInteractions::class)->guardUser('report');
        $comment = $this->comment($id);
        abort_unless($comment->status === 'approved', 404);
        PropertyReport::firstOrCreate(['user_id' => auth()->id(), 'comment_id' => $comment->id], ['reason' => 'Kullanıcı tarafından uygunsuz içerik olarak bildirildi.']);
        $this->notice = 'Şikâyetiniz moderasyon ekibine iletildi.';
    }

    public function render()
    {
        $target = app(PropertyInteractions::class)->target($this->targetType, $this->targetId);
        $comments = PropertyComment::where($this->targetType.'_id', $this->targetId)->whereNull('parent_id')->whereIn('status', ['approved', 'deleted'])
            ->with(['user', 'replies' => fn ($q) => $q->whereIn('status', ['approved', 'deleted'])->with('user.businessProfile')->oldest()->orderBy('id')->limit(3)])
            ->withCount(['replies as visible_replies_count' => fn ($q) => $q->whereIn('status', ['approved', 'deleted'])])
            ->orderBy('created_at', $this->sort === 'newest' ? 'desc' : 'asc')->orderBy('id')->paginate(10, ['*'], 'discussionPage');
        $likes = DB::table('property_likes')->where($this->targetType.'_id', $this->targetId);
        $thread = $this->replyThread ? PropertyComment::where($this->targetType.'_id', $this->targetId)->whereNull('parent_id')->whereIn('status', ['approved', 'deleted'])->find($this->replyThread) : null;
        $threadReplies = $thread?->replies()->whereIn('status', ['approved', 'deleted'])->with('user.businessProfile')->oldest()->orderBy('id')->paginate(10, ['*'], 'replyPage');

        return view('livewire.property-discussion', ['target' => $target, 'threadReplies' => $threadReplies, 'comments' => $comments, 'likeCount' => (clone $likes)->count(), 'liked' => auth()->check() && (clone $likes)->where('user_id', auth()->id())->exists(), 'canAnswer' => $this->targetType === 'post' || (auth()->check() && app(PropertyInteractions::class)->answerLabel($target, auth()->user()) !== null)]);
    }
}
