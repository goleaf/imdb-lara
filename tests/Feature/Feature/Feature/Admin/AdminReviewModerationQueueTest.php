<?php

namespace Tests\Feature\Feature\Feature\Admin;

use Tests\TestCase;

class AdminReviewModerationQueueTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
