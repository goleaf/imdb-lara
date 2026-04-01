<?php

namespace App;

enum ContributionStatus: string
{
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
