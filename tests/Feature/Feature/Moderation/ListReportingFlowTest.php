<?php

namespace Tests\Feature\Feature\Moderation;

use App\Enums\ReportReason;
use App\Livewire\Lists\ReportListForm;
use App\Models\User;
use App\Models\UserList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ListReportingFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_report_a_public_list(): void
    {
        $owner = User::factory()->create();
        $reporter = User::factory()->create();
        $list = UserList::factory()->public()->for($owner)->create([
            'name' => 'Best Midnight Watches',
        ]);

        Livewire::actingAs($reporter)
            ->test(ReportListForm::class, ['list' => $list])
            ->set('form.reason', ReportReason::Inaccurate->value)
            ->set('form.details', 'This list contains duplicated titles and misleading annotations.')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('reports', [
            'user_id' => $reporter->id,
            'reportable_type' => UserList::class,
            'reportable_id' => $list->id,
            'reason' => ReportReason::Inaccurate->value,
        ]);
    }

    public function test_list_owner_cannot_report_their_own_public_list(): void
    {
        $owner = User::factory()->create();
        $list = UserList::factory()->public()->for($owner)->create();

        Livewire::actingAs($owner)
            ->test(ReportListForm::class, ['list' => $list])
            ->set('form.reason', ReportReason::Spam->value)
            ->call('save')
            ->assertForbidden();
    }
}
