<?php

namespace App\Enums;

enum ProcessingStatus: string
{
    case Processing = 'processing';
    case Ready = 'ready';
    case Error = 'error';
}

