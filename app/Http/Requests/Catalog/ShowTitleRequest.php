<?php

namespace App\Http\Requests\Catalog;

use App\Http\Requests\NotFoundFormRequest;
use App\Models\Title;

class ShowTitleRequest extends NotFoundFormRequest
{
    public function authorize(): bool
    {
        $title = $this->route('title');

        return $title instanceof Title
            && ($title->is_published || ($this->user()?->can('view', $title) ?? false));
    }

    public function title(): Title
    {
        /** @var Title */
        return $this->route('title');
    }
}
