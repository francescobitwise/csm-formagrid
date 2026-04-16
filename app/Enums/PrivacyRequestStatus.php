<?php

declare(strict_types=1);

namespace App\Enums;

enum PrivacyRequestStatus: string
{
    case New = 'new';
    case InProgress = 'in_progress';
    case Closed = 'closed';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Nuova',
            self::InProgress => 'In lavorazione',
            self::Closed => 'Chiusa',
            self::Rejected => 'Respinta / archiviata',
        };
    }
}
