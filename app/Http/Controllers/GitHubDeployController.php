<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class GitHubDeployController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $secret = (string) config('services.github_deploy.secret');
        $signature = (string) $request->header('X-Hub-Signature-256');

        if ($secret === '') {
            Log::error('GitHub deploy webhook secret is not configured.');

            return response()->json(['error' => 'Deploy webhook is not configured.'], 503);
        }

        $expectedSignature = 'sha256='.hash_hmac('sha256', $request->getContent(), $secret);

        if ($signature === '' || ! hash_equals($expectedSignature, $signature)) {
            Log::warning('GitHub deploy webhook signature validation failed.');

            return response()->json(['error' => 'Unauthorized signature.'], 401);
        }

        if ($request->header('X-GitHub-Event') !== 'push') {
            return response()->json(['status' => 'ignored', 'reason' => 'Not a push event.'], 202);
        }

        $branch = (string) config('services.github_deploy.branch', 'main');

        if ($request->input('ref') !== 'refs/heads/'.$branch) {
            return response()->json(['status' => 'ignored', 'reason' => 'Branch is not configured for deployment.'], 202);
        }

        $service = (string) config('services.github_deploy.service', 'estate-agent-deploy.service');

        if (! preg_match('/^[a-zA-Z0-9_.@-]+$/', $service)) {
            Log::error('Invalid GitHub deployment service name.');

            return response()->json(['error' => 'Deploy webhook is not configured.'], 503);
        }

        $result = Process::timeout(10)->run([
            'sudo',
            '-n',
            '/usr/bin/systemctl',
            'start',
            '--no-block',
            $service,
        ]);

        Log::log($result->successful() ? 'info' : 'error', 'GitHub deployment service trigger finished.', [
            'exit_code' => $result->exitCode(),
            'error_output' => $result->errorOutput(),
        ]);

        if (! $result->successful()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Deployment could not be started. Check the application log.',
            ], 500);
        }

        return response()->json([
            'status' => 'accepted',
            'message' => 'Deployment started.',
        ], 202);
    }
}
