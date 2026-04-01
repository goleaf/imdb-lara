<?php

namespace App;

enum UserRole: string
{
    case Member = 'member';
    case Moderator = 'moderator';
    case Admin = 'admin';
}
