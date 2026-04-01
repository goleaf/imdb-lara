<?php

namespace App\Enums;

enum ReviewStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Published = 'published';
    case Rejected = 'rejected';
}
