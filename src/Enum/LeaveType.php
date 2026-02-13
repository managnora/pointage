<?php

namespace App\Enum;

enum LeaveType: string
{
    case CONGE_PAYE = 'conge_paye';
    case RTT = 'rtt';
    case MALADIE = 'maladie';
    case RECUP = 'recup';
}
