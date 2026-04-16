<?php

declare(strict_types=1);

namespace App\Enums;

enum PrivacyRequestType: string
{
    case Access = 'access';
    case Rectification = 'rectification';
    case Erasure = 'erasure';
    case Limitation = 'limitation';
    case Portability = 'portability';
    case Objection = 'objection';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Access => 'Accesso ai dati',
            self::Rectification => 'Rettifica',
            self::Erasure => 'Cancellazione (diritto all’oblio)',
            self::Limitation => 'Limitazione del trattamento',
            self::Portability => 'Portabilità',
            self::Objection => 'Opposizione',
            self::Other => 'Altro',
        };
    }
}
