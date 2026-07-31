<?php

namespace App\Enums;

enum NodeStatus: string
{
    case Normal = 'normal';
    case AtRisk = 'at_risk';
    case Blocked = 'blocked';
    case Completed = 'completed';
}
