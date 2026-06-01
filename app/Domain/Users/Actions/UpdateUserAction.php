<?php

namespace App\Domain\Users\Actions;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class UpdateUserAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __invoke(User $user, array $data): User
    {
        $isChangingBranch = array_key_exists('branch_id', $data)
            && (int) ($user->branch_id ?? 0) !== (int) ($data['branch_id'] ?? 0);

        if ($user->role === UserRole::STAFF && $isChangingBranch && $user->hasActiveAppointments()) {
            throw ValidationException::withMessages([
                'branch_id' => ['Cannot change branch while staff has active appointments (Confirmed or In Progress).'],
            ]);
        }

        $user->update($data);

        return $user->refresh();
    }
}
