<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\CentralAdmin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class CreateCentralAdminCommand extends Command
{
    protected $signature = 'central:create-admin
                            {email : Email dell’amministratore}
                            {--name= : Nome visualizzato}
                            {--password= : Password (se omessa, viene generata)}';

    protected $description = 'Crea o aggiorna un amministratore piattaforma (tabella users sul DB landlord, modello CentralAdmin)';

    public function handle(): int
    {
        $email = strtolower(trim((string) $this->argument('email')));
        $name = (string) ($this->option('name') ?: 'Amministratore');
        $plain = $this->option('password');

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Email non valida.');

            return self::FAILURE;
        }

        if ($plain === null || $plain === '') {
            $plain = Str::password(16);
            $this->warn('Password generata (salvala in un posto sicuro):');
            $this->line($plain);
        }

        $admin = CentralAdmin::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($plain),
                'email_verified_at' => now(),
            ],
        );

        $this->info("OK — amministratore «{$admin->name}» ({$admin->email}).");

        return self::SUCCESS;
    }
}
