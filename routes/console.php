<?php

use App\Enums\CourseStatus;
use App\Enums\UserRole;
use App\Models\Tenant\Course;
use App\Models\Tenant\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('app:make-admin {email} {password} {--name=Admin : Nome utente}', function () {
    $email = (string) $this->argument('email');
    $password = (string) $this->argument('password');
    $name = (string) ($this->option('name') ?? 'Admin');

    User::updateOrCreate(
        ['email' => $email],
        [
            'name' => $name,
            'password' => Hash::make($password),
            'role' => UserRole::Admin,
            'must_change_password' => false,
        ],
    );

    $this->info("Admin created/updated: {$email}");

    return self::SUCCESS;
})->purpose('Create an admin user (single DB)');

Artisan::command('app:seed-demo-courses', function () {
    Course::firstOrCreate(
        ['slug' => 'cybersecurity-awareness-2024'],
        [
            'title' => 'Cybersecurity Awareness 2024',
            'status' => CourseStatus::Published,
            'description' => 'Fondamenti di sicurezza per tutti i dipendenti.',
            'total_hours' => 4,
        ],
    );

    Course::firstOrCreate(
        ['slug' => 'onboarding-aziendale'],
        [
            'title' => 'Onboarding Aziendale',
            'status' => CourseStatus::Published,
            'description' => 'Benvenuto: valori, cultura e processi essenziali.',
            'total_hours' => 2,
        ],
    );

    Course::firstOrCreate(
        ['slug' => 'leadership-avanzata'],
        [
            'title' => 'Leadership Avanzata',
            'status' => CourseStatus::Draft,
            'description' => 'Percorso per manager e team leader.',
        ],
    );

    $this->info('Demo courses created (if missing).');

    return self::SUCCESS;
})->purpose('Seed demo courses (single DB)');
