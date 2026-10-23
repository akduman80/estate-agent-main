<?php

/** Run only over the existing SSH console. No web route; never accepts a key as an argument. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
chdir(dirname(__DIR__));
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$mode = $argv[1] ?? 'status';
if (! in_array($mode, ['status','activate','set-key','test'], true)) { fwrite(STDERR, "Use status, activate, set-key or test.\n"); exit(1); }
try {
    $vars = is_file('.env') ? Dotenv\Dotenv::parse(file_get_contents('.env')) : [];
    if ($mode === 'status') {
        echo 'ENV KEY: '.(filled($vars['GEMINI_API_KEY'] ?? getenv('GEMINI_API_KEY')) ? 'PRESENT' : 'ABSENT')."\n";
        echo 'ACTIVE: '.(app(App\Services\Assistant\GeminiClient::class)->configured() ? 'CONFIGURED' : 'NOT CONFIGURED')."\n";
        exit;
    }
    if ($mode === 'test') {
        $response = app(App\Services\Assistant\GeminiClient::class)->generate([['type' => 'user_input','content' => 'Türkçe yalnız bağlantı tamam yaz.']], [], 'Bir bağlantı testisin. Kısa yanıt ver.');
        echo ($response['status'] === 'completed' ? 'GEMINI CONNECTION OK' : 'GEMINI RESPONSE INCOMPLETE')."\n"; exit;
    }
    $lock = fopen('storage/framework/deploy.lock','c');
    if (! $lock || ! flock($lock, LOCK_EX | LOCK_NB)) throw new RuntimeException('Another deployment is running.');
    $key = $vars['GEMINI_API_KEY'] ?? getenv('GEMINI_API_KEY');
    if ($mode === 'set-key') {
        if (! function_exists('stream_isatty') || ! stream_isatty(STDIN) || PHP_OS_FAMILY === 'Windows') throw new RuntimeException('Use an interactive Linux SSH terminal.');
        fwrite(STDOUT, 'Gemini API anahtarı (gizli giriş): ');
        system('stty -echo', $code); if ($code !== 0) throw new RuntimeException('Cannot hide terminal input.');
        try { $key = trim((string) fgets(STDIN)); } finally { system('stty echo'); fwrite(STDOUT, "\n"); }
        if (! preg_match('/^[A-Za-z0-9_\-]{20,200}$/', $key)) throw new RuntimeException('Invalid API key format; no changes made.');
    }
    if (! filled($key)) throw new RuntimeException('GEMINI_API_KEY absent. Use set-key in the SSH terminal. Do not send it in chat.');
    $path = 'bootstrap/cache/config.php';
    if (! is_file($path) || is_link($path) || ! is_file('.env') || is_link('.env')) throw new RuntimeException('Expected regular .env and cached config files.');
    $old = require $path; $new = $old;
    $backup = 'storage/app/private/gemini-config-before-'.gmdate('Ymd\THis\Z').'-'.bin2hex(random_bytes(3));
    if (! mkdir($backup,0700)) throw new RuntimeException('Cannot create backup.');
    foreach (['.env' => 'env','bootstrap/cache/config.php' => 'config.php'] as $source => $target) { if (! copy($source,$backup.'/'.$target)) throw new RuntimeException('Backup failed.'); chmod($backup.'/'.$target,0600); }
    $model = $vars['GEMINI_MODEL'] ?? 'gemini-3.5-flash-lite';
    if (! preg_match('/^gemini-[a-z0-9.\-]+$/', $model)) throw new RuntimeException('Invalid GEMINI_MODEL.');
    $new['assistant']['gemini'] = [
        'key' => $key, 'model' => $model, 'timeout' => (int) ($vars['GEMINI_TIMEOUT'] ?? 12),
        'max_output_tokens' => (int) ($vars['GEMINI_MAX_OUTPUT_TOKENS'] ?? 1200),
        'user_daily_limit' => (int) ($vars['GEMINI_USER_DAILY_LIMIT'] ?? 50), 'server_daily_limit' => (int) ($vars['GEMINI_SERVER_DAILY_LIMIT'] ?? 1000),
        'daily_token_budget' => (int) ($vars['GEMINI_DAILY_TOKEN_BUDGET'] ?? 2000000),
        'input_usd_per_million' => $vars['GEMINI_INPUT_USD_PER_MILLION'] ?? null, 'output_usd_per_million' => $vars['GEMINI_OUTPUT_USD_PER_MILLION'] ?? null,
    ];
    $oldRest = $old; $newRest = $new; unset($oldRest['assistant']['gemini'], $newRest['assistant']['gemini']);
    if (serialize($oldRest) !== serialize($newRest)) throw new RuntimeException('Unrelated configuration changed.');
    $atomic = function (string $file, string $contents): void {
        $tmp = $file.'.gemini-'.bin2hex(random_bytes(6));
        if (file_put_contents($tmp,$contents,LOCK_EX) === false) throw new RuntimeException('Write failed.');
        chmod($tmp, fileperms($file)&0777);
        if (filegroup($tmp) !== filegroup($file) && ! chgrp($tmp,filegroup($file))) { unlink($tmp); throw new RuntimeException('Cannot preserve file group.'); }
        if (! rename($tmp,$file)) { unlink($tmp); throw new RuntimeException('Atomic replacement failed.'); }
    };
    if ($mode === 'set-key') {
        $env = file_get_contents('.env');
        $env = preg_match('/^GEMINI_API_KEY=/m',$env) ? preg_replace_callback('/^GEMINI_API_KEY=.*$/m', fn () => 'GEMINI_API_KEY='.$key, $env) : rtrim($env)."\nGEMINI_API_KEY=".$key."\n";
        $atomic('.env', $env);
    }
    try { $atomic($path, '<?php return '.var_export($new,true).";\n"); }
    catch (Throwable $e) { if ($mode === 'set-key') $atomic('.env',file_get_contents($backup.'/env')); throw $e; }
    if (function_exists('opcache_invalidate')) opcache_invalidate(base_path($path),true);
    echo "GEMINI CONFIGURED. Other configuration preserved.\nBACKUP ".$backup."\nRun: php scripts/assistant-gemini.php test\n";
} catch (Throwable $e) {
    // Known local errors only. Never print request objects, provider error bodies, keys or stack traces.
    fwrite(STDERR, ($e instanceof RuntimeException ? $e->getMessage() : 'Gemini operation failed; no secret details logged.')."\n"); exit(1);
}
