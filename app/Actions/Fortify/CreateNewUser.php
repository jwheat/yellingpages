<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ])->validate();

        return User::create([
            'name' => $input['name'],
            'username' => $this->uniqueUsername($input['name'], $input['email']),
            'email' => $input['email'],
            'password' => $input['password'],
        ]);
    }

    private function uniqueUsername(string $name, string $email): string
    {
        $base = Str::slug(Str::before($name, ' ') ?: Str::before($email, '@'), '');
        $base = $base !== '' ? Str::lower($base) : 'ranter';
        $username = $base;
        $i = 0;

        while (User::where('username', $username)->exists()) {
            $username = $base.(++$i);
        }

        return $username;
    }
}
