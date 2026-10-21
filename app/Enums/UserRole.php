<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Instructor = 'instructor';
    case Inspector = 'inspector';
    case CompanyManager = 'company_manager';
    case Learner = 'learner';
}

