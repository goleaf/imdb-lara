<?php

namespace App\Http\Requests\Catalog;

use App\Http\Requests\NotFoundFormRequest;

class ShowYearRequest extends NotFoundFormRequest
{
    public function authorize(): bool
    {
        $year = (int) $this->route('year');

        return $year >= 1888 && $year <= now()->addYear()->year;
    }

    public function year(): int
    {
        return (int) $this->route('year');
    }
}
