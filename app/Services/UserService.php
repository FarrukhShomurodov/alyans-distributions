<?php

namespace App\Services;

use App\Models\User;

class UserService
{
    /**
     * @param  array<string, mixed>  $validated
     */
    public function updateStatus(User $user, array $validated): void
    {
        $user->update($validated);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function update(User $user, array $validated): User
    {
        $user->update($validated);

        return $user->refresh();
    }

    public function destroy(User $user): void
    {
        $user->delete();
    }
}
