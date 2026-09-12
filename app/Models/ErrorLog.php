<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;
use Throwable;

class ErrorLog extends Model
{
    protected $fillable = [
        'level', 'exception_class', 'message', 'file', 'line', 'trace', 'user_id',
        'method', 'url', 'route_name', 'ip_address', 'user_agent', 'context',
        'occurred_at', 'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'occurred_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function recordException(Throwable $exception): void
    {
        try {
            $request = app()->bound('request') ? app('request') : null;
            $user = auth()->user();

            static::create([
                'level' => 'error',
                'exception_class' => $exception::class,
                'message' => $exception->getMessage() ?: $exception::class,
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
                'user_id' => $user?->getAuthIdentifier(),
                'method' => $request?->method(),
                'url' => $request?->fullUrl(),
                'route_name' => $request?->route()?->getName(),
                'ip_address' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
                'context' => static::requestContext($request),
                'occurred_at' => now(),
            ]);
        } catch (Throwable $loggingException) {
            // The database may be the source of the original failure. Preserve it in laravel.log.
            logger()->error('Error log could not be persisted.', [
                'original_exception' => $exception::class,
                'original_message' => $exception->getMessage(),
                'logging_exception' => $loggingException->getMessage(),
            ]);
        }
    }

    private static function requestContext(?Request $request): ?array
    {
        if (! $request) {
            return null;
        }

        $sensitive = ['password', 'password_confirmation', 'token', 'code', 'identity_number', 'tax_number'];
        $input = $request->except($sensitive);

        return [
            'input' => static::truncate($input),
            'query' => static::truncate($request->query()),
        ];
    }

    private static function truncate(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map(fn (mixed $item): mixed => static::truncate($item), $value);
        }

        return is_string($value) && strlen($value) > 2000 ? substr($value, 0, 2000).'…' : $value;
    }
}
