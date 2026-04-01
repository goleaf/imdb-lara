<?php

namespace App;

enum ReportReason: string
{
    case Spam = 'spam';
    case Abuse = 'abuse';
    case Spoiler = 'spoiler';
    case Harassment = 'harassment';
    case Inaccurate = 'inaccurate';
}
