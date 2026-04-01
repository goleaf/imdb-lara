<?php

namespace App\Enums;

enum TitleRelationshipType: string
{
    case Similar = 'similar';
    case Franchise = 'franchise';
    case Sequel = 'sequel';
    case Prequel = 'prequel';
    case SpinOff = 'spin-off';
    case Remake = 'remake';
    case Adaptation = 'adaptation';
    case SharedUniverse = 'shared-universe';
}
