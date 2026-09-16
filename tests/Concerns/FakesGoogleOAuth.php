<?php

namespace Tests\Concerns;

use App\Services\GoogleOAuthService;
use Mockery\MockInterface;

trait FakesGoogleOAuth
{
    /**
     * Replace Google authentication so tests never read real credentials or hit Google.
     */
    protected function fakeGoogleOAuth(?string $projectId = 'demo-project'): void
    {
        $this->mock(GoogleOAuthService::class, function (MockInterface $mock) use ($projectId) {
            $mock->shouldReceive('getAccessToken')->andReturn('test-token');
            $mock->shouldReceive('projectId')->andReturn($projectId);
        });
    }
}
