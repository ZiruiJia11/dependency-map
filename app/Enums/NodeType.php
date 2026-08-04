<?php

namespace App\Enums;

/** Supported categories used to classify nodes throughout the map. */
enum NodeType: string
{
    case Task = 'task';
    case Person = 'person';
    case Service = 'service';
    case Database = 'database';
    case Supplier = 'supplier';
    case Approval = 'approval';
    case Other = 'other';
}
