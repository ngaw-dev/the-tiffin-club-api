<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class CreateAdminUser extends Command
{
    protected $signature = 'admin:create';

    protected $description = 'Create the single owner admin user for the Filament panel';

    public function handle(): int
    {
        $email = text('Admin email', required: true, validate: fn (string $v) => filter_var($v, FILTER_VALIDATE_EMAIL) ? null : 'Invalid email');
        $name = text('Admin name', required: true);
        $passwordValue = password('Admin password', required: true, validate: fn (string $v) => Password::min(8)->validate($v) ? null : 'Must be at least 8 characters');

        User::updateOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => Hash::make($passwordValue)],
        );

        $this->info("Admin user {$email} created. Log in at /admin/login.");

        return self::SUCCESS;
    }
}
