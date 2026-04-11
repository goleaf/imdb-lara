<?php

namespace App\Models;

class LocalTitle extends Title
{
    protected $table = 'titles';

    public static function usesCatalogOnlySchema(): bool
    {
        return false;
    }
}
