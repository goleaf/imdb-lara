<?php

namespace App\Models;

class LocalPerson extends Person
{
    protected $table = 'people';

    public static function usesCatalogOnlySchema(): bool
    {
        return false;
    }
}
