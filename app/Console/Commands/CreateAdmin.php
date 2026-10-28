<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class CreateAdmin extends Command
{
    protected $signature = 'app:create-admin';

    protected $description = 'Δημιουργία διαχειριστή (ο κωδικός πληκτρολογείται κρυφά)';

    public function handle(): int
    {
        $name = text('Ονοματεπώνυμο', required: true);
        $email = text('Email', required: true, validate: fn ($v) => Validator::make(
            ['email' => $v],
            ['email' => ['email', 'unique:users,email']],
        )->errors()->first('email') ?: null);

        $password = password(
            'Κωδικός (τουλάχιστον 12 χαρακτήρες)',
            required: true,
            validate: fn ($v) => Validator::make(
                ['password' => $v],
                ['password' => [Password::min(12)->mixedCase()->numbers()->symbols()]],
            )->errors()->first('password') ?: null,
        );

        $user = new User(['name' => $name, 'email' => $email, 'password' => $password]);
        $user->role = UserRole::Admin;
        $user->email_verified_at = now();
        $user->save();

        $this->info("Ο διαχειριστής {$email} δημιουργήθηκε. Στην πρώτη σύνδεση θα ζητηθεί ρύθμιση 2FA.");

        return self::SUCCESS;
    }
}
