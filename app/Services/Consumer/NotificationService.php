<?php

namespace App\Services\Consumer;

use App\Mail\UrgentComplaintNotification;
use App\Models\Consumer\Complaint;
use App\Models\Consumer\ConsumerNotification;
use App\Models\Consumer\Feedback;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    public function notifyAdminUrgentComplaint(Complaint $complaint): void
    {
        $admins = User::where('role', 'admin')
            ->where('is_active', true)
            ->get();

        foreach ($admins as $admin) {
            $this->createNotification($admin->id, [
                'type' => 'complaint_urgent',
                'title' => '⚠️ Réclamation urgente : '.$complaint->subject,
                'message' => "Une réclamation urgente a été soumise pour le produit #{$complaint->oil_product_id}.\n\n".
                    "Consommateur: {$complaint->consumer->name}\n".
                    "Description: {$complaint->description}\n".
                    "Priorité IA: {$complaint->ai_priority?->label()}\n".
                    "Catégorie: {$complaint->ai_category?->label()}",
                'related_complaint_id' => $complaint->id,
                'related_oil_product_id' => $complaint->oil_product_id,
            ]);

            // Send email if admin has email
            if ($admin->email) {
                try {
                    Mail::to($admin->email)->send(new UrgentComplaintNotification($complaint, $admin));
                } catch (\Throwable $exception) {
                    Log::warning('Urgent complaint mail failed', ['complaint' => $complaint->id, 'exception' => $exception::class]);
                }
            }
        }
    }

    public function notifyComplaintStatusChange(Complaint $complaint, string $oldStatus): void
    {
        $consumer = $complaint->consumer;
        if (! $consumer) {
            return;
        }

        $statusLabels = [
            'open' => 'Ouverte',
            'in_review' => 'En cours de traitement',
            'resolved' => 'Résolue',
            'rejected' => 'Rejetée',
        ];

        $this->createNotification($consumer->id, [
            'type' => 'complaint_status',
            'title' => 'Mise à jour de votre réclamation : '.$complaint->subject,
            'message' => 'Le statut de votre réclamation a changé : '.$statusLabels[$oldStatus].' → '.$statusLabels[$complaint->status->value]."\n\n".
                ($complaint->admin_response ? "Réponse de l'équipe : {$complaint->admin_response}" : ''),
            'related_complaint_id' => $complaint->id,
            'related_oil_product_id' => $complaint->oil_product_id,
        ]);

        // The moderation controller sends the reviewed status email once.
    }

    public function notifyNewCertificate(int $productId): void
    {
        $consumers = Feedback::where('oil_product_id', $productId)
            ->whereHas('consumer', fn ($query) => $query->where('is_active', true))
            ->visible()
            ->distinct('consumer_user_id')
            ->pluck('consumer_user_id');

        foreach ($consumers as $userId) {
            $this->createNotification($userId, [
                'type' => 'new_certificate',
                'title' => 'Nouveau certificat disponible',
                'message' => 'Un nouveau certificat est disponible pour un produit que vous avez noté. Consultez la page de traçabilité pour le voir.',
                'related_oil_product_id' => $productId,
            ]);
        }
    }

    public function createNotification(int $userId, array $data): ConsumerNotification
    {
        return ConsumerNotification::create(array_merge([
            'consumer_user_id' => $userId,
            'read' => false,
        ], $data));
    }
}
