<?php

namespace App\Enums;

/** Lifecycle and risk states available for every node. */
enum NodeStatus: string
{
    case Normal = 'normal';
    case AtRisk = 'at_risk';
    case Blocked = 'blocked';
    case Completed = 'completed';
}
