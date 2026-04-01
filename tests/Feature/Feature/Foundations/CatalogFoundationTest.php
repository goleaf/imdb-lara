<?php

namespace Tests\Feature\Feature\Foundations;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CatalogFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_schema_is_available_after_migration(): void
    {
        $this->assertTrue(Schema::hasColumns('users', [
            'username',
            'role',
            'status',
            'bio',
            'avatar_path',
        ]));

        foreach ([
            'titles',
            'people',
            'companies',
            'credits',
            'genres',
            'media_assets',
            'ratings',
            'reviews',
            'review_votes',
            'title_statistics',
            'user_lists',
            'list_items',
            'reports',
            'moderation_actions',
            'company_title',
            'genre_title',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), sprintf('Missing table [%s].', $table));
        }
    }
}
