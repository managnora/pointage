<?php

namespace App\Enum;

enum WorkLogType: string
{
    case WORK = 'work';
    case LEAVE = 'leave';
    case RECOVERY = 'recovery';
    case HOLIDAY = 'holiday';
    case ABSENCE = 'absence';
}
