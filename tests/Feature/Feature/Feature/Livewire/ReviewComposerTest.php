<?php

namespace Tests\Feature\Feature\Feature\Livewire;

use Tests\TestCase;

class ReviewComposerTest extends TestCase
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
