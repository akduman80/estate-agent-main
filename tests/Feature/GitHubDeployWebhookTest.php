<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class GitHubDeployWebhookTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.github_deploy', [
            'secret' => 'test-secret',
            'branch' => 'main',
            'service' => 'estate-agent-deploy.service',
        ]);
    }

    public function test_webhook_rejects_requests_without_a_valid_signature(): void
    {
        Process::fake();

        $this->postJson('/github-deploy-webhook', ['ref' => 'refs/heads/main'])
            ->assertUnauthorized();

        Process::assertNothingRan();
    }

    public function test_webhook_ignores_pushes_to_other_branches(): void
    {
        Process::fake();

        $this->signedWebhook(['ref' => 'refs/heads/feature/test'])
            ->assertAccepted()
            ->assertJsonPath('status', 'ignored');

        Process::assertNothingRan();
    }

    public function test_webhook_runs_the_deploy_script_for_main_pushes(): void
    {
        Process::fake();

        $this->signedWebhook(['ref' => 'refs/heads/main'])
            ->assertAccepted()
            ->assertJsonPath('status', 'accepted');

        Process::assertRan(fn ($process): bool => $process->command === [
            'sudo',
            '-n',
            '/usr/bin/systemctl',
            'start',
            '--no-block',
            'estate-agent-deploy.service',
        ] && $process->timeout === 10);
    }

    private function signedWebhook(array $payload)
    {
        $content = json_encode($payload, JSON_THROW_ON_ERROR);
        $signature = 'sha256='.hash_hmac('sha256', $content, 'test-secret');

        return $this->call(
            'POST',
            '/github-deploy-webhook',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_X_GITHUB_EVENT' => 'push',
                'HTTP_X_HUB_SIGNATURE_256' => $signature,
            ],
            $content,
        );
    }
}
