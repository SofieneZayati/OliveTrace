<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\Mill;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserAdministration
{
    public function update(User $actor, User $target, array $attributes): void
    {
        DB::transaction(function () use ($actor, $target, $attributes) {
            // Serialize admin removals so two requests cannot remove the final admins.
            $admins = User::where('role', Role::Admin->value)->where('is_active', true)
                ->orderBy('id')->lockForUpdate()->get();
            $target = User::whereKey($target->id)->lockForUpdate()->firstOrFail();
            $removesAdmin = $attributes['role'] !== Role::Admin->value || ! $attributes['is_active'];

            if ($actor->is($target) && $removesAdmin) {
                throw ValidationException::withMessages(['role' => 'You cannot remove your own admin access or deactivate your account.']);
            }

            if ($target->isAdmin() && $target->is_active && $removesAdmin && $admins->count() <= 1) {
                throw ValidationException::withMessages(['role' => 'Keep at least one active administrator.']);
            }

            $target->fill(collect($attributes)->only(['name', 'email'])->all());
            if ($target->isDirty('email')) {
                $target->email_verified_at = null;
            }
            // Privileged fields are assigned explicitly; they are not mass assignable.
            $target->role = Role::from($attributes['role']);
            $target->is_active = (bool) $attributes['is_active'];
            $target->save();

            $this->syncMill($target, $attributes);
        });
    }

    /**
     * A miller account always owns exactly one mill row: promoting creates or
     * completes it, demoting soft deletes it so the history is preserved.
     */
    private function syncMill(User $target, array $attributes): void
    {
        $mill = Mill::withTrashed()->where('user_id', $target->id)->first();

        if ($target->role !== Role::Miller) {
            $mill?->delete();

            return;
        }

        $mill ??= new Mill(['user_id' => $target->id]);
        $mill->fill(collect($attributes['mill'] ?? [])->only(['name', 'region', 'extraction_type', 'capacity', 'contact'])->all());
        $mill->deleted_at = null;
        $mill->save();
    }

    public function delete(User $actor, User $target): void
    {
        if ($actor->is($target)) {
            throw ValidationException::withMessages(['user' => 'You cannot delete your own admin account.']);
        }

        try {
            DB::transaction(function () use ($target) {
                $admins = User::where('role', Role::Admin->value)->where('is_active', true)
                    ->orderBy('id')->lockForUpdate()->get();
                $target = User::whereKey($target->id)->lockForUpdate()->firstOrFail();
                if ($target->isAdmin() && $target->is_active && $admins->count() <= 1) {
                    throw ValidationException::withMessages(['user' => 'Keep at least one active administrator.']);
                }
                $target->delete();
            });
        } catch (QueryException $exception) {
            if ($exception->getCode() !== '23000') {
                throw $exception;
            }
            throw ValidationException::withMessages(['user' => 'This account has linked records. Deactivate it instead.']);
        }
    }
}
