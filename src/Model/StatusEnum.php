<?php

namespace App\Model;

enum StatusEnum: string
{
    case ALERT = 'alert';
    case WARNING = 'warning';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
}
