<?php

declare(strict_types=1);

namespace Akeneo\Platform\Job\Test\Integration\Infrastructure\Controller;

use Akeneo\Platform\Job\Test\Integration\ControllerIntegrationTestCase;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Response;

class GetJobUsersActionTest extends ControllerIntegrationTestCase
{
    private const ROUTE = 'akeneo_job_get_job_users_action';

    public function setUp(): void
    {
        parent::setUp();

        $this->fixturesLoader->loadFixtures();
    }

    public function test_with_permission_it_returns_all_job_users(): void
    {
        $this->logAs('peter');
        $this->webClientHelper->callApiRoute($this->client, self::ROUTE);

        $expectedJobUsers = [
            'peter',
            'mary',
        ];

        $response = $this->client->getResponse();
        Assert::assertSame($response->getStatusCode(), Response::HTTP_OK);
        Assert::assertEqualsCanonicalizing(json_decode($response->getContent(), true), $expectedJobUsers);
    }

    public function test_without_permission_it_returns_only_current_user(): void
    {
        $this->logAs('mary');
        $this->webClientHelper->callApiRoute($this->client, self::ROUTE);

        $expectedJobUsers = [
            'mary',
        ];

        $response = $this->client->getResponse();
        Assert::assertSame($response->getStatusCode(), Response::HTTP_OK);
        Assert::assertEqualsCanonicalizing(json_decode($response->getContent(), true), $expectedJobUsers);
    }
}
