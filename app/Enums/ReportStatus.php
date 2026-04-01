<?php

namespace App\Enums;

enum ReportStatus: string
{
    case Open = 'open';
    case Investigating = 'investigating';
    case Resolved = 'resolved';
    case Dismissed = 'dismissed';
}
