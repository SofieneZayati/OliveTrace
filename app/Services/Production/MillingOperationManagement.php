<?php

namespace App\Services\Production;

use App\Enums\HarvestStatus;
use App\Enums\MillRequestStatus;
use App\Enums\Production\MillingOperationStatus;
use App\Enums\Production\OliveCondition;
use App\Models\Production\Harvest;
use App\Models\Production\MillingOperation;
use App\Models\Production\MillRequest;
use App\Models\Production\OilLot;
use App\Models\Production\StatusHistory;
use App\Models\User;
use App\Notifications\MillingNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class MillingOperationManagement
{
    public function scheduleFromAcceptedRequest(User $actor, MillRequest $request): MillingOperation
    {
        // Only for internal mill (has mill_id) as per brief; external mills kept as-is
        if (! $request->mill_id) {
            throw ValidationException::withMessages(['mill' => 'External mills do not use the smart milling operation flow.']);
        }

        if ($request->status !== MillRequestStatus::Accepted) {
            throw ValidationException::withMessages(['status' => 'Only accepted requests can be scheduled.']);
        }

        if ($request->millingOperation) {
            throw ValidationException::withMessages(['operation' => 'A milling operation already exists for this request.']);
        }

        return DB::transaction(function () use ($actor, $request) {
            $op = new MillingOperation;
            $op->mill_request_id = $request->id;
            $op->status = MillingOperationStatus::Scheduled;
            $op->created_by = $actor->id;
            $op->save();

            $this->recordHistory($op, null, MillingOperationStatus::Scheduled->value, $actor->id);

            return $op;
        });
    }

    public function confirmDeliveryByProducer(User $actor, MillingOperation $operation, array $data): MillingOperation
    {
        Gate::forUser($actor)->authorize('confirmDelivery', $operation);

        if ($operation->status !== MillingOperationStatus::Scheduled) {
            throw ValidationException::withMessages(['status' => 'Delivery can only be confirmed while scheduled.']);
        }
        if ($operation->producer_confirmed_at) {
            throw ValidationException::withMessages(['delivery' => 'Delivery already confirmed by producer.']);
        }

        return DB::transaction(function () use ($operation, $data) {
            $op = MillingOperation::whereKey($operation->id)->lockForUpdate()->firstOrFail();
            $op->producer_confirmed_at = $data['delivered_at'] ?? now();
            $op->delivered_at = $op->producer_confirmed_at;
            $op->save();

            return $op;
        });
    }

    public function confirmReceptionByMiller(User $actor, MillingOperation $operation, array $data): MillingOperation
    {
        Gate::forUser($actor)->authorize('confirmReception', $operation);

        if ($operation->status !== MillingOperationStatus::Scheduled) {
            throw ValidationException::withMessages(['status' => 'Reception can only be confirmed while scheduled.']);
        }
        if (! $operation->producer_confirmed_at) {
            throw ValidationException::withMessages(['delivery' => 'Producer delivery must be confirmed first.']);
        }
        if ($operation->miller_confirmed_at) {
            throw ValidationException::withMessages(['reception' => 'Reception already confirmed.']);
        }

        $received = (float) $data['received_quantity_kg'];
        $requested = (float) $operation->millRequest->quantity_kg;
        if ($requested <= 0) {
            throw ValidationException::withMessages(['received_quantity_kg' => 'Requested quantity must be greater than zero.']);
        }

        $discrepancy = abs($received - $requested) / $requested * 100;
        $discrepancyRounded = round($discrepancy, 2);

        if ($discrepancyRounded >= 5.0 && empty($data['discrepancy_note'])) {
            throw ValidationException::withMessages(['discrepancy_note' => 'A discrepancy note is required for 5% or more.']);
        }

        if ($discrepancyRounded > 10.0 && ! $operation->discrepancy_acknowledged_at && empty($data['acknowledge'])) {
            // We still allow recording; acknowledgement is separate action by producer
        }

        return DB::transaction(function () use ($operation, $data, $received, $discrepancyRounded) {
            $op = MillingOperation::whereKey($operation->id)->lockForUpdate()->firstOrFail();
            $op->received_quantity_kg = number_format($received, 2, '.', '');
            $op->olive_condition = OliveCondition::from($data['olive_condition'])->value;
            $op->delivery_notes = $data['delivery_notes'] ?? null;
            $op->miller_confirmed_at = now();
            $op->discrepancy_percent = number_format($discrepancyRounded, 2, '.', '');
            $op->discrepancy_note = $data['discrepancy_note'] ?? null;
            $op->save();

            $this->notifyAfterCommit(function () use ($op, $discrepancyRounded) {
                $op->loadMissing('millRequest.harvest.farm.producerProfile.user');
                $producer = $op->millRequest?->harvest?->farm?->producerProfile?->user;
                if ($producer) {
                    $producer->notify(new MillingNotification([
                        'type' => 'milling.reception_confirmed',
                        'title' => 'Reception confirmed',
                        'message' => 'The mill confirmed reception for your delivery.',
                        'url' => route('producer.milling-operations.show', $op->id),
                        'operation_id' => $op->id,
                    ]));
                }
                if ($discrepancyRounded > 10.0) {
                    $admins = User::where('role', 'admin')->get();
                    foreach ($admins as $admin) {
                        $admin->notify(new MillingNotification([
                            'type' => 'milling.discrepancy_high',
                            'title' => 'High discrepancy (>10%)',
                            'message' => 'A milling operation has a discrepancy above 10%.',
                            'url' => route('miller.milling-operations.show', $op->id),
                            'operation_id' => $op->id,
                        ]));
                    }
                }
            });

            return $op;
        });
    }

    public function acknowledgeDiscrepancy(User $actor, MillingOperation $operation): MillingOperation
    {
        Gate::forUser($actor)->authorize('acknowledgeDiscrepancy', $operation);

        if (! $operation->miller_confirmed_at) {
            throw ValidationException::withMessages(['reception' => 'Reception must be confirmed first.']);
        }
        if ((float) $operation->discrepancy_percent <= 10.0) {
            throw ValidationException::withMessages(['discrepancy' => 'Acknowledgement only required for more than 10%.']);
        }
        if ($operation->discrepancy_acknowledged_at) {
            throw ValidationException::withMessages(['discrepancy' => 'Discrepancy already acknowledged.']);
        }

        return DB::transaction(function () use ($operation) {
            $op = MillingOperation::whereKey($operation->id)->lockForUpdate()->firstOrFail();
            $op->discrepancy_acknowledged_at = now();
            $op->save();

            return $op;
        });
    }

    public function start(User $actor, MillingOperation $operation): MillingOperation
    {
        Gate::forUser($actor)->authorize('start', $operation);

        if ($operation->status !== MillingOperationStatus::Scheduled) {
            throw ValidationException::withMessages(['status' => 'Only scheduled operations can be started.']);
        }
        if (! $operation->producer_confirmed_at || ! $operation->miller_confirmed_at) {
            throw ValidationException::withMessages(['confirmations' => 'Both delivery and reception must be confirmed.']);
        }
        if ((float) $operation->discrepancy_percent > 10.0 && ! $operation->discrepancy_acknowledged_at) {
            throw ValidationException::withMessages(['discrepancy' => 'Producer must acknowledge discrepancy above 10%.']);
        }
        if ((float) $operation->discrepancy_percent >= 5.0 && empty($operation->discrepancy_note)) {
            throw ValidationException::withMessages(['discrepancy_note' => 'A discrepancy note is required for 5% or more.']);
        }

        return DB::transaction(function () use ($actor, $operation) {
            $op = MillingOperation::whereKey($operation->id)->lockForUpdate()->firstOrFail();
            $from = $op->status->value;
            $op->status = MillingOperationStatus::InProgress;
            $op->started_at = now();
            $op->save();

            $this->recordHistory($op, $from, $op->status->value, $actor->id);

            $this->notifyAfterCommit(function () use ($op) {
                $op->loadMissing('millRequest.harvest.farm.producerProfile.user', 'millRequest.mill');
                $producer = $op->millRequest?->harvest?->farm?->producerProfile?->user;
                if ($producer) {
                    $producer->notify(new MillingNotification([
                        'type' => 'milling.started',
                        'title' => 'Milling started',
                        'message' => 'Milling operation has started.',
                        'url' => route('producer.milling-operations.show', $op->id),
                        'operation_id' => $op->id,
                    ]));
                }
            });

            return $op;
        });
    }

    public function complete(User $actor, MillingOperation $operation, array $data): MillingOperation
    {
        Gate::forUser($actor)->authorize('complete', $operation);

        if ($operation->status !== MillingOperationStatus::InProgress) {
            throw ValidationException::withMessages(['status' => 'Only in-progress operations can be completed.']);
        }

        $processed = (float) $data['processed_quantity_kg'];
        $received = (float) $operation->received_quantity_kg;
        $output = (float) $data['output_volume_l'];
        $pomace = isset($data['pomace_quantity_kg']) && $data['pomace_quantity_kg'] !== ''
            ? (float) $data['pomace_quantity_kg']
            : null;

        if ($processed <= 0) {
            throw ValidationException::withMessages(['processed_quantity_kg' => 'Processed quantity must be greater than zero.']);
        }
        if ($processed > $received + 0.00001) {
            throw ValidationException::withMessages(['processed_quantity_kg' => 'Processed quantity cannot exceed received quantity.']);
        }
        if ($output <= 0) {
            throw ValidationException::withMessages(['output_volume_l' => 'Output volume must be greater than zero.']);
        }
        if ($pomace !== null && $pomace > $processed + 0.00001) {
            throw ValidationException::withMessages(['pomace_quantity_kg' => 'Pomace quantity cannot exceed processed quantity.']);
        }

        $extraction = ($output / $processed) * 100;
        if ($extraction > 100.0) {
            throw ValidationException::withMessages(['extraction_rate' => 'Extraction rate cannot be above 100%.']);
        }
        $extractionRounded = round($extraction, 2);

        return DB::transaction(function () use ($actor, $operation, $data, $processed, $output, $pomace, $extractionRounded) {
            $op = MillingOperation::whereKey($operation->id)->lockForUpdate()->firstOrFail();
            $request = MillRequest::whereKey($op->mill_request_id)->lockForUpdate()->firstOrFail();
            $harvest = Harvest::whereKey($request->harvest_id)->lockForUpdate()->firstOrFail();

            $op->processed_quantity_kg = number_format($processed, 2, '.', '');
            $op->output_volume_l = number_format($output, 2, '.', '');
            $op->pomace_quantity_kg = $pomace !== null ? number_format($pomace, 2, '.', '') : null;
            $op->extraction_rate = number_format($extractionRounded, 2, '.', '');
            $op->notes = $data['notes'] ?? null;
            $from = $op->status->value;
            $op->status = MillingOperationStatus::Completed;
            $op->completed_at = now();
            $op->save();

            $this->recordHistory($op, $from, $op->status->value, $actor->id);

            // Create OilLot
            $lot = new OilLot;
            $lot->mill_request_id = $request->id;
            $lot->milling_operation_id = $op->id;
            // producer_user_id from harvest owner
            $producerUserId = $harvest->farm?->producerProfile?->user_id;
            $lot->producer_user_id = $producerUserId;
            $lot->lot_number = $this->generateLotNumber();
            $lot->liters = number_format($output, 2, '.', '');
            $lot->quality_grade = $data['quality_grade'] ?? null;
            $lot->production_date = now()->toDateString();
            $lot->notes = $data['lot_notes'] ?? null;
            $lot->save();

            // Update request and harvest
            $request->status = MillRequestStatus::Completed;
            $request->save();
            $this->recordHistory($request, $request->getRawOriginal('status') ?? MillRequestStatus::Accepted->value, MillRequestStatus::Completed->value, $actor->id);

            if ($harvest->status !== HarvestStatus::Milled) {
                $harvest->status = HarvestStatus::Milled;
                $harvest->save();
                $this->recordHistory($harvest, $harvest->getRawOriginal('status') ?? HarvestStatus::Completed->value, HarvestStatus::Milled->value, $actor->id);
            }

            $this->notifyAfterCommit(function () use ($op, $lot) {
                $op->loadMissing('millRequest.harvest.farm.producerProfile.user');
                $producer = $op->millRequest?->harvest?->farm?->producerProfile?->user;
                if ($producer) {
                    $producer->notify(new MillingNotification([
                        'type' => 'milling.completed',
                        'title' => 'Milling completed',
                        'message' => 'Milling completed. Oil lot '.$lot->lot_number.' created.',
                        'url' => route('producer.milling-operations.show', $op->id),
                        'operation_id' => $op->id,
                        'lot_id' => $lot->id,
                    ]));
                }
            });

            return $op->fresh(['millRequest', 'oilLot']);
        });
    }

    public function cancel(User $actor, MillingOperation $operation, string $reason): MillingOperation
    {
        Gate::forUser($actor)->authorize('cancel', $operation);

        if ($operation->status === MillingOperationStatus::Completed) {
            throw ValidationException::withMessages(['status' => 'Completed operations cannot be cancelled.']);
        }

        return DB::transaction(function () use ($actor, $operation, $reason) {
            $op = MillingOperation::whereKey($operation->id)->lockForUpdate()->firstOrFail();
            $from = $op->status->value;
            $op->status = MillingOperationStatus::Cancelled;
            $op->save();

            $this->recordHistory($op, $from, $op->status->value, $actor->id, $reason);

            $this->notifyAfterCommit(function () use ($op) {
                $op->loadMissing('millRequest.harvest.farm.producerProfile.user');
                $producer = $op->millRequest?->harvest?->farm?->producerProfile?->user;
                if ($producer) {
                    $producer->notify(new MillingNotification([
                        'type' => 'milling.cancelled',
                        'title' => 'Milling operation cancelled',
                        'message' => 'Milling operation was cancelled.',
                        'url' => route('producer.milling-operations.show', $op->id),
                        'operation_id' => $op->id,
                    ]));
                }
            });

            return $op;
        });
    }

    private function notifyAfterCommit(callable $callback): void
    {
        DB::afterCommit($callback);
    }

    private function generateLotNumber(): string
    {
        $year = now()->year;
        for ($i = 0; $i < 10; $i++) {
            $count = OilLot::whereYear('created_at', $year)->count() + 1 + $i;
            $number = sprintf('LOT-%d-%03d', $year, $count);
            if (! OilLot::where('lot_number', $number)->exists()) {
                return $number;
            }
        }

        // fallback
        return sprintf('LOT-%d-%06d', $year, random_int(100000, 999999));
    }

    private function recordHistory($model, ?string $from, string $to, ?int $changedBy, ?string $reason = null): void
    {
        StatusHistory::create([
            'trackable_type' => get_class($model),
            'trackable_id' => $model->id,
            'from_status' => $from,
            'to_status' => $to,
            'changed_by' => $changedBy,
            'reason' => $reason,
            'created_at' => now(),
        ]);
    }
}
