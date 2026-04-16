<?php

namespace App\Enums;

enum ScormTrackingStatus: string
{
    case NotAttempted = 'not_attempted';
    case Incomplete = 'incomplete';
    case Completed = 'completed';
    case Passed = 'passed';
    case Failed = 'failed';
}

