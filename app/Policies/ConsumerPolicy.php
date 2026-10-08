<?php

namespace App\Policies;

use App\Models\Consumer\Complaint;
use App\Models\Consumer\Feedback;
use App\Models\User;

class ConsumerPolicy
{
    private function ownerOrAdmin(User $user, Feedback|Complaint $record): bool
    {
        return $user->is_active && ($user->isAdmin() || $record->consumer_user_id === $user->id);
    }

    public function view(User $user, Feedback|Complaint $record): bool
    {
        return $this->ownerOrAdmin($user, $record);
    }

    // Only the owning consumer edits or deletes their own feedback.
    // Admins moderate through the visible/hidden status instead.
    public function update(User $user, Feedback $feedback): bool
    {
        return $user->is_active && ! $user->isAdmin() && $feedback->consumer_user_id === $user->id;
    }

    public function delete(User $user, Feedback $feedback): bool
    {
        return $this->update($user, $feedback);
    }

    // Consumers submit complaints and read their own status; only the admin
    // moves a complaint to in_review / resolved / rejected.
    public function moderate(User $user): bool
    {
        return $user->is_active && $user->isAdmin();
    }
}
