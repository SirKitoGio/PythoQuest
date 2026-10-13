<?php

namespace App\Enums;

enum UserRole: string
{
    case Researcher = 'researcher';
    case Reviewer = 'reviewer';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Researcher => 'Registered researcher',
            self::Reviewer => 'Expert reviewer',
            self::Admin => 'Administrator',
        };
    }

    public function canReview(): bool
    {
        return $this !== self::Researcher;
    }
}
