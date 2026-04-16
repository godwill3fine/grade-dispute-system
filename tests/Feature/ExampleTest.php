<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_the_landing_page_redirects_into_the_portal_flow(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('portal.auth'));
    }
}
