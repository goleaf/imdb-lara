<?php

namespace App\Actions\Admin;

use App\Models\Credit;

class DeleteCreditAction
{
    public function handle(Credit $credit): void
    {
        $credit->delete();
    }
}
