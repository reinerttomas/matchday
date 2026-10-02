<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Fortify\CreateNewUser;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

use function Laravel\Prompts\error;
use function Laravel\Prompts\form;
use function Laravel\Prompts\info;

#[Signature('user:create')]
#[Description('Create a new user')]
final class CreateUserCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(CreateNewUser $createNewUser): int
    {
        /** @var array{name: string, email: string, password: string, password_confirmation: string} $data */
        $data = form()
            ->text(label: 'Name', required: true, name: 'name')
            ->text(label: 'Email', placeholder: 'player@example.com', required: true, name: 'email')
            ->password(label: 'Password', required: true, name: 'password')
            ->password(label: 'Confirm password', required: true, name: 'password_confirmation')
            ->submit();

        try {
            $user = $createNewUser->create([
                ...$data,
                // Google sign-in links accounts by lowercased email, so store it the same way.
                'email' => Str::lower($data['email']),
            ]);
        } catch (ValidationException $exception) {
            foreach ($exception->validator->errors()->all() as $message) {
                error($message);
            }

            return self::FAILURE;
        }

        info("User {$user->email} created.");

        return self::SUCCESS;
    }
}
