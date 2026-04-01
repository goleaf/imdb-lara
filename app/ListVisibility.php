<?php

namespace App;

enum ListVisibility: string
{
    case Public = 'public';
    case Unlisted = 'unlisted';
    case Private = 'private';
}
