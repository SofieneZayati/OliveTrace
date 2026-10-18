<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Production\Farm;
use App\Models\Production\FarmAdviceReport;
use App\Models\Production\ProducerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FarmActionPlanTest extends TestCase
{
    use RefreshDatabase;

    private Farm $farm;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 10, 18)->startOfDay());
        Http::preventStrayRequests();
        config(['farm-assistant.provider' => 'gemini', 'farm-assistant.gemini_key' => 'fake-gemini-key', 'farm-assistant.model' => 'gemini-3.1-flash-lite']);
        $owner = User::factory()->create(['role' => Role::Producer]);
        $profile = ProducerProfile::factory()->for($owner)->create(['phone' => 'private-phone-marker', 'address' => 'private-address-marker', 'is_public' => true]);
        $this->farm = Farm::factory()->for($profile, 'producerProfile')->create(['name' => 'Private-name-marker', 'description' => 'private-description-marker', 'gps_lat' => '34.1234567', 'gps_lng' => '10.1234567', 'is_public' => true]);
        $this->actingAs($owner);
    }

    private function advice(array $evidence = []): array
    {
        return [
            'summary' => 'Review your recorded farm care.', 'suggestions' => ['Discuss practices with a local agronomist.'],
            'flags' => ['No soil measurements were provided.'], 'limitations' => 'Producer declarations only. No environmental outcome is established.',
            'recommendations' => [[
                'title' => 'Inspect irrigation lines', 'type' => 'inspection', 'priority' => 'medium',
                'reason' => 'Review the recorded irrigation and check for leaks; no leak is assumed.', 'evidence_activity_ids' => $evidence,
            ]],
        ];
    }

    private function fake(array $advice): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [['finishReason' => 'STOP', 'content' => ['parts' => [['text' => json_encode($advice)]]]]],
        ])]);
    }

    private function generate(?array $advice = null): FarmAdviceReport
    {
        $this->fake($advice ?? $this->advice());
        $this->post(route('producer.farms.reports.store', $this->farm))->assertSessionHasNoErrors()->assertRedirect();

        return $this->farm->adviceReports()->latest('id')->firstOrFail();
    }

    private function adoption(array $overrides = []): array
    {
        return array_replace(['title' => 'Inspect irrigation lines', 'planned_on' => '2026-10-19', 'notes' => 'My private follow-up', 'reviewed' => '1'], $overrides);
    }

    public function test_saved_gemini_report_uses_bounded_structured_records_without_sending_private_fields(): void
    {
        $entry = $this->farm->activities()->create(['title' => 'private-activity-title', 'type' => 'irrigation', 'planned_on' => '2026-10-17', 'notes' => 'private-activity-notes']);
        $entry->forceFill(['status' => 'completed', 'completed_on' => '2026-10-18', 'water_m3' => '15.25'])->save();
        $report = $this->generate($this->advice([$entry->id]));
        Http::assertSent(function ($request) use ($entry) {
            $input = $request['contents'][0]['parts'][0]['text'];
            $facts = json_decode($input, true);
            $this->assertSame(1, $facts['journal']['included_records']);
            $this->assertSame('15.25', $facts['journal']['completed_irrigation_water_m3']);
            $this->assertSame($entry->id, $facts['journal']['activities'][0]['id']);
            foreach (['private-phone-marker', 'private-address-marker', 'private-description-marker', 'Private-name-marker', 'private-activity-title', 'private-activity-notes', '34.1234567', '10.1234567'] as $private) {
                $this->assertStringNotContainsString($private, $input);
            }
            $this->assertContains('recommendations', $request['generationConfig']['responseJsonSchema']['required']);

            return true;
        });
        $this->get(route('producer.farms.reports.index', $this->farm))->assertOk()->assertSee('Review your recorded farm care.');
        $this->get(route('producer.farms.reports.show', [$this->farm, $report]))->assertOk()->assertSee('15.25')->assertSee('Inspect irrigation lines')->assertSee('Print / save PDF')->assertSee('References recorded activity IDs:')->assertDontSee('@else');
        $this->assertDatabaseCount('farm_activities', 1); // Generating advice never creates an activity.
        $snapshot = $report->facts;
        $this->farm->update(['area_ha' => '20.00']);
        $entry->forceFill(['water_m3' => '30.00'])->save();
        $this->assertSame($snapshot, $report->fresh()->facts);
    }

    public function test_producer_must_review_before_adoption_and_repeat_submissions_do_not_duplicate_work(): void
    {
        $report = $this->generate();
        $route = route('producer.farms.reports.adopt', [$this->farm, $report, 0]);
        $this->post($route, $this->adoption(['reviewed' => 0]))->assertSessionHasErrors('reviewed');
        $this->post($route, $this->adoption(['planned_on' => '2026-10-17']))->assertSessionHasErrors('planned_on');
        $this->assertDatabaseCount('farm_activities', 0);
        $this->post($route, $this->adoption())->assertSessionHasNoErrors()->assertRedirect(route('producer.farms.activities.index', $this->farm));
        $this->post($route, $this->adoption())->assertSessionHasNoErrors();
        $this->assertDatabaseCount('farm_activities', 1);
        $entry = $this->farm->activities()->sole();
        $this->assertSame($report->id, $entry->advice_report_id);
        $this->assertSame('inspection', $entry->type->value);
        $this->assertSame('planned', $entry->status->value);
        $this->patch(route('producer.farms.activities.update', [$this->farm, $entry]), ['title' => 'Reviewed inspection', 'type' => 'inspection', 'planned_on' => '2026-10-18'])->assertSessionHasNoErrors();
        $this->patch(route('producer.farms.activities.status', [$this->farm, $entry]), ['status' => 'completed', 'completed_on' => '2026-10-18'])->assertSessionHasNoErrors();
        $this->post($route, $this->adoption())->assertSessionHasNoErrors();
        $this->assertDatabaseCount('farm_activities', 1);
        $this->get(route('producer.farms.reports.show', [$this->farm, $report]))->assertSee('Added to the journal')->assertSee('Completed');
    }

    public function test_reports_and_recommendation_ids_cannot_be_used_on_another_farm_or_by_another_producer(): void
    {
        $report = $this->generate();
        $otherFarm = Farm::factory()->for($this->farm->producerProfile, 'producerProfile')->create();
        $this->get(route('producer.farms.reports.show', [$otherFarm, $report]))->assertNotFound();
        $this->post(route('producer.farms.reports.adopt', [$otherFarm, $report, 0]), $this->adoption())->assertNotFound();
        $this->post(route('producer.farms.reports.adopt', [$this->farm, $report, 9]), $this->adoption())->assertNotFound();
        $other = User::factory()->create(['role' => Role::Producer]);
        $this->actingAs($other)->get(route('producer.farms.reports.index', $this->farm))->assertForbidden();
        $this->get(route('producer.farms.reports.show', [$this->farm, $report]))->assertForbidden();
        $this->post(route('producer.farms.reports.store', $this->farm))->assertForbidden();
        $this->post(route('producer.farms.reports.adopt', [$this->farm, $report, 0]), $this->adoption())->assertForbidden();
        $this->assertDatabaseCount('farm_activities', 0);
    }

    public function test_admin_can_inspect_reports_but_cannot_adopt_work_for_the_producer(): void
    {
        $report = $this->generate();
        $this->actingAs(User::factory()->create(['role' => Role::Admin]));
        $this->get(route('admin.farms.reports.index', $this->farm))->assertOk();
        $this->get(route('admin.farms.reports.show', [$this->farm, $report]))->assertOk()->assertDontSee('Add reviewed activity');
        $this->post(route('producer.farms.reports.adopt', [$this->farm, $report, 0]), $this->adoption())->assertForbidden();
    }

    public function test_missing_key_and_invalid_provider_output_do_not_save_reports_or_change_records(): void
    {
        config(['farm-assistant.gemini_key' => null]);
        $this->post(route('producer.farms.reports.store', $this->farm))->assertRedirect()->assertSessionHas('error');
        Http::assertNothingSent();
        config(['farm-assistant.gemini_key' => 'fake-gemini-key']);
        $invalid = $this->advice([99999]);
        $this->fake($invalid);
        $this->post(route('producer.farms.reports.store', $this->farm))->assertRedirect()->assertSessionHas('error');
        $invalid['recommendations'][0]['evidence_activity_ids'] = [];
        $invalid['recommendations'][0]['type'] = 'harvest';
        $this->fake($invalid);
        $this->post(route('producer.farms.reports.store', $this->farm))->assertRedirect()->assertSessionHas('error');
        $this->assertDatabaseCount('farm_advice_reports', 0);
        $this->assertDatabaseCount('farm_activities', 0);
        $this->assertSame('active', $this->farm->fresh()->status->value);
    }

    public function test_journal_context_caps_records_and_excludes_outside_dates(): void
    {
        for ($i = 0; $i < 35; $i++) {
            $this->farm->activities()->create(['title' => 'Inspection '.$i, 'type' => 'inspection', 'planned_on' => '2026-10-17']);
        }
        $this->farm->activities()->create(['title' => 'Old record', 'type' => 'inspection', 'planned_on' => '2025-01-01']);
        $report = $this->generate();
        $this->assertSame(35, $report->facts['journal']['matching_records']);
        $this->assertSame(30, $report->facts['journal']['included_records']);
        $this->assertCount(30, $report->facts['journal']['activities']);
    }

    public function test_report_history_is_retained_when_farm_is_archived_and_no_new_work_is_allowed(): void
    {
        $report = $this->generate();
        $this->delete(route('producer.farms.destroy', $this->farm))->assertRedirect();
        $this->assertDatabaseHas('farms', ['id' => $this->farm->id, 'status' => 'archived']);
        $this->get(route('producer.farms.reports.show', [$this->farm, $report]))->assertOk()->assertDontSee('Add reviewed activity');
        $this->post(route('producer.farms.reports.store', $this->farm))->assertSessionHasErrors('farm');
        $this->post(route('producer.farms.reports.adopt', [$this->farm, $report, 0]), $this->adoption())->assertSessionHasErrors('farm');
        $this->assertDatabaseCount('farm_advice_reports', 1);
    }

    public function test_ai_content_is_escaped_and_cannot_execute_html_in_saved_report(): void
    {
        $advice = $this->advice();
        $advice['summary'] = '<script>alert("unsafe")</script>';
        $advice['recommendations'][0]['reason'] = '<img src=x onerror=alert(1)>';
        $report = $this->generate($advice);
        $this->get(route('producer.farms.reports.show', [$this->farm, $report]))->assertOk()
            ->assertSee($advice['summary'])->assertDontSee($advice['summary'], false)
            ->assertSee($advice['recommendations'][0]['reason'])->assertDontSee($advice['recommendations'][0]['reason'], false)
            ->assertSee('no activity is cited.')->assertDontSee('@else');
        $this->get(route('origin.farms.show', $this->farm))->assertDontSee($advice['summary']);
    }

    public function test_saved_report_generation_is_rate_limited(): void
    {
        config(['farm-assistant.gemini_key' => null]);
        for ($attempt = 0; $attempt < 3; $attempt++) {
            $this->post(route('producer.farms.reports.store', $this->farm))->assertRedirect();
        }
        $this->post(route('producer.farms.reports.store', $this->farm))->assertTooManyRequests();
        Http::assertNothingSent();
    }
}
