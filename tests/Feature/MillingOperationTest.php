<?php

namespace Tests\Feature;

use App\Enums\HarvestStatus;
use App\Enums\MillRequestStatus;
use App\Enums\Production\MillingOperationStatus;
use App\Enums\Role;
use App\Models\Mill;
use App\Models\Production\Harvest;
use App\Models\Production\MillingOperation;
use App\Models\Production\MillRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MillingOperationTest extends TestCase
{
    // Notifications must run after real commits, rather than inside a test transaction.
    use DatabaseMigrations;

    private function operation(string $producerName): MillingOperation
    {
        $harvest = Harvest::factory()->create([
            'quantity_kg' => '100.00',
            'status' => HarvestStatus::Completed,
        ]);
        $harvest->farm->producerProfile->user->update(['name' => $producerName]);
        $mill = Mill::factory()->create();
        $request = MillRequest::factory()->create([
            'harvest_id' => $harvest->id,
            'mill_id' => $mill->id,
            'quantity_kg' => '100.00',
            'status' => MillRequestStatus::Accepted,
        ]);

        return MillingOperation::create([
            'mill_request_id' => $request->id,
            'status' => MillingOperationStatus::Scheduled,
            'producer_confirmed_at' => now(),
            'delivered_at' => now(),
            'created_by' => $mill->user_id,
        ]);
    }

    public static function receptionQuantities(): array
    {
        return [
            'no discrepancy' => [100, '0.00', false],
            'ten percent does not alert admins' => [90, '10.00', false],
            'over ten percent alerts admins' => [80, '20.00', true],
        ];
    }

    #[DataProvider('receptionQuantities')]
    public function test_reception_commits_and_delivers_the_expected_notifications(
        int $received,
        string $discrepancy,
        bool $adminAlert
    ): void {
        $admin = User::factory()->create(['role' => Role::Admin]);
        $operation = $this->operation('Reception test producer');
        $returnUrl = route('miller.milling-operations.show', $operation);

        $this->actingAs($operation->millRequest->mill->user)
            ->from($returnUrl)
            ->post(route('miller.milling-operations.confirm-reception', $operation), [
                'received_quantity_kg' => (string) $received,
                'olive_condition' => 'good',
                'discrepancy_note' => $received < 100 ? 'Difference recorded at weighing.' : null,
            ])->assertRedirect($returnUrl)->assertSessionHasNoErrors();

        $operation->refresh();
        $this->assertNotNull($operation->miller_confirmed_at);
        $this->assertSame(number_format($received, 2, '.', ''), $operation->received_quantity_kg);
        $this->assertSame($discrepancy, $operation->discrepancy_percent);

        $producer = $operation->millRequest->harvest->farm->producerProfile->user;
        $notification = $producer->notifications()->sole();
        $this->assertSame('milling.reception_confirmed', $notification->data['type']);
        $this->assertSame($operation->id, $notification->data['operation_id']);

        if ($adminAlert) {
            $alert = $admin->notifications()->sole();
            $this->assertSame('milling.discrepancy_high', $alert->data['type']);
            $this->assertSame($operation->id, $alert->data['operation_id']);
        } else {
            $this->assertSame(0, $admin->notifications()->count());
        }
    }

    public function test_miller_without_a_mill_cannot_list_other_producers_operations(): void
    {
        $this->operation('Private producer record');
        $miller = User::factory()->create(['role' => Role::Miller]);

        $this->actingAs($miller)->get(route('miller.milling-operations.index'))
            ->assertForbidden()->assertDontSee('Private producer record');
    }

    public function test_assigned_miller_sees_only_operations_for_their_mill(): void
    {
        $own = $this->operation('Owned producer record');
        $this->operation('Other producer record');

        $this->actingAs($own->millRequest->mill->user)
            ->get(route('miller.milling-operations.index'))->assertOk()
            ->assertSee('Owned producer record')->assertDontSee('Other producer record');
    }

    public function test_miller_cannot_view_or_confirm_another_mills_reception(): void
    {
        $operation = $this->operation('Private producer record');
        $otherMill = Mill::factory()->create();

        $this->actingAs($otherMill->user)
            ->get(route('miller.milling-operations.show', $operation))->assertForbidden();
        $this->post(route('miller.milling-operations.confirm-reception', $operation), [
            'received_quantity_kg' => '100.00',
            'olive_condition' => 'good',
        ])->assertForbidden();
        $this->assertNull($operation->fresh()->miller_confirmed_at);
        $this->assertDatabaseCount('notifications', 0);
    }
}
