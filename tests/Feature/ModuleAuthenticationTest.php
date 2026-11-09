<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_module_endpoints_require_authentication(): void
    {
        foreach ([
            ['GET', '/courses'],
            ['GET', '/courses/1'],
            ['POST', '/courses'],
            ['PUT', '/courses/1'],
            ['PATCH', '/courses/1'],
            ['DELETE', '/courses/1'],
            ['GET', '/classrooms'],
            ['POST', '/classrooms/1/users'],
        ] as [$method, $uri]) {
            $this->json($method, $uri)->assertUnauthorized();
        }
    }
}
