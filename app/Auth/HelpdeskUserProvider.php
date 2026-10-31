<?php

namespace App\Auth;

use App\Models\User;
use App\Models\Login;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;

class HelpdeskUserProvider implements UserProvider
{
    public function __construct(
        protected Hasher $hasher,
        protected string $model = \App\Models\User::class,
    ) {}

    public function retrieveByCredentials(array $credentials)
    {
        $login = Login::where('usuario', $credentials['usuario'] ?? '')
            ->where('activo', true)
            ->first();

        if (!$login) {
            return null;
        }

        return User::where('id_Usuario', $login->id_Usuario)
            ->where('activo', true)
            ->first();
    }

    public function retrieveById($identifier)
    {
        return User::where('id_Usuario', $identifier)
            ->where('activo', true)
            ->first();
    }

    public function retrieveByToken($identifier, $token)
    {
        return null;
    }

    public function updateRememberToken(Authenticatable $user, $token)
    {
    }

    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        $login = Login::where('id_Usuario', $user->getKey())
            ->where('activo', true)
            ->first();

        if (!$login) {
            return false;
        }

        $dbHash = strtolower($login->pass);
        $inputHash = strtolower(hash('sha512', $credentials['password'] ?? ''));

        return $dbHash === $inputHash;
    }

    public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false)
    {
    }
}
