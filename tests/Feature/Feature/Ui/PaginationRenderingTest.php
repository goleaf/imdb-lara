<?php

namespace Tests\Feature\Feature\Ui;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Tests\TestCase;

class PaginationRenderingTest extends TestCase
{
    public function test_length_aware_pagination_renders_sheaf_button_primitives(): void
    {
        $paginator = new LengthAwarePaginator(
            items: collect(range(11, 20)),
            total: 50,
            perPage: 10,
            currentPage: 2,
            options: [
                'path' => '/catalog/titles',
                'pageName' => 'page',
            ],
        );

        $rendered = (string) $this->blade('{{ $paginator->links() }}', [
            'paginator' => $paginator,
        ]);

        $this->assertStringContainsString('data-slot="button"', $rendered);
        $this->assertStringContainsString('aria-label="Go to page 1"', $rendered);
        $this->assertStringContainsString('aria-current="page"', $rendered);
    }

    public function test_livewire_island_simple_pagination_renders_sheaf_buttons_and_keeps_wire_actions(): void
    {
        $paginator = new Paginator(
            items: collect(range(1, 2)),
            perPage: 1,
            currentPage: 1,
            options: [
                'path' => '/discover',
                'pageName' => 'discover',
            ],
        );

        $rendered = $this->view('livewire.pagination.island-simple', [
            'paginator' => $paginator,
            'scrollTo' => false,
        ])->render();

        $this->assertStringContainsString('data-slot="button"', $rendered);
        $this->assertStringContainsString("wire:click=\"nextPage('discover')\"", $rendered);
        $this->assertStringContainsString('Pagination Navigation', $rendered);
    }
}
