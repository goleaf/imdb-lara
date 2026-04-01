<?php

namespace App;

enum ContributionAction: string
{
    case Create = 'create';
    case Update = 'update';
    case Curate = 'curate';
    case Merge = 'merge';
}
