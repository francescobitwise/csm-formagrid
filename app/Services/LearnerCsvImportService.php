<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Tenant\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

final class LearnerCsvImportService
{
    public function __construct() {}

    /**
     * @return array{created: int, skipped: int, errors: list<string>, users_to_notify: list<User>, plain_passwords_by_user_id: array<string, string>}
     */
    public function import(UploadedFile $file, bool $sendEmailsImmediately, ?string $companyId = null): array
    {
        $created = 0;
        $skipped = 0;
        $errors = [];
        $usersToNotify = [];
        $plainPasswords = [];

        $handle = fopen($file->getRealPath(), 'rb');
        if ($handle === false) {
            return [
                'created' => 0,
                'skipped' => 0,
                'errors' => ['Impossibile leggere il file.'],
                'users_to_notify' => [],
                'plain_passwords_by_user_id' => [],
            ];
        }

        $header = fgetcsv($handle);
        if (is_array($header) && isset($header[0])) {
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]);
        }
        if ($header === false) {
            fclose($handle);

            return [
                'created' => 0,
                'skipped' => 0,
                'errors' => ['CSV vuoto.'],
                'users_to_notify' => [],
                'plain_passwords_by_user_id' => [],
            ];
        }

        $map = $this->normalizeHeader($header);
        if (! isset($map['email'])) {
            fclose($handle);

            return [
                'created' => 0,
                'skipped' => 0,
                'errors' => ['Colonna obbligatoria mancante: serve almeno `email` (accettati anche mail, e-mail).'],
                'users_to_notify' => [],
                'plain_passwords_by_user_id' => [],
            ];
        }

        $line = 1;
        $maxRows = 1000;
        $processed = 0;
        while (($row = fgetcsv($handle)) !== false) {
            $line++;
            if ($this->rowIsEmpty($row)) {
                continue;
            }
            $processed++;
            if ($processed > $maxRows) {
                $errors[] = "Import interrotto: superato il limite di {$maxRows} righe con dati.";
                break;
            }

            $email = $this->cell($row, $map['email'] ?? null);
            $name = $this->cell($row, $map['name'] ?? null);
            $plainPassword = isset($map['password'])
                ? trim((string) $this->cell($row, $map['password']))
                : '';

            if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Riga {$line}: email non valida.";
                $skipped++;

                continue;
            }

            $email = strtolower($email);

            if (User::query()->where('email', $email)->exists()) {
                $errors[] = "Riga {$line}: email «{$email}» già registrata.";
                $skipped++;

                continue;
            }

            if ($name === '') {
                $name = strstr($email, '@', true) ?: $email;
            }

            $mustChangePassword = false;
            if ($plainPassword === '') {
                $plainPassword = Str::password(18, true, true, false, false);
                $mustChangePassword = true;
            }

            if (strlen($plainPassword) < 8) {
                $errors[] = "Riga {$line}: password troppo corta (minimo 8 caratteri).";
                $skipped++;

                continue;
            }

            $user = User::query()->create([
                'name' => $name,
                'email' => $email,
                'password' => $plainPassword,
                'role' => UserRole::Learner,
                'company_id' => $companyId,
                'email_verified_at' => now(),
                'must_change_password' => $mustChangePassword,
            ]);

            $created++;
            $usersToNotify[] = $user;
            $plainPasswords[$user->id] = $plainPassword;
        }

        fclose($handle);

        return [
            'created' => $created,
            'skipped' => $skipped,
            'errors' => $errors,
            'users_to_notify' => $sendEmailsImmediately ? $usersToNotify : [],
            'plain_passwords_by_user_id' => $sendEmailsImmediately ? $plainPasswords : [],
        ];
    }

    /**
     * @return array<string, int>
     */
    private function normalizeHeader(array $header): array
    {
        $map = [];
        foreach ($header as $i => $col) {
            $key = strtolower(trim((string) $col));
            $key = match ($key) {
                'mail', 'e-mail' => 'email',
                'nome', 'name', 'full name', 'fullname', 'display name' => 'name',
                'password', 'passwd', 'pass' => 'password',
                default => $key,
            };
            if ($key !== '') {
                $map[$key] = $i;
            }
        }

        return $map;
    }

    private function rowIsEmpty(array $row): bool
    {
        foreach ($row as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }

    private function cell(array $row, ?int $index): string
    {
        if ($index === null || ! array_key_exists($index, $row)) {
            return '';
        }

        return trim((string) $row[$index]);
    }
}
