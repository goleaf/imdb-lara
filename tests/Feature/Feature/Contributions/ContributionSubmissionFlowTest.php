<?php

namespace Tests\Feature\Feature\Contributions;

use App\Enums\ContributionAction;
use App\Enums\ContributionStatus;
use App\Livewire\Contributions\SuggestionForm;
use App\Models\Contribution;
use App\Models\Title;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ContributionSubmissionFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_contributor_can_submit_a_title_edit_suggestion(): void
    {
        $contributor = User::factory()->contributor()->create();
        $moderator = User::factory()->moderator()->create();
        $title = Title::factory()->create([
            'name' => 'Orbit Station',
        ]);

        Livewire::actingAs($contributor)
            ->test(SuggestionForm::class, [
                'contributableType' => 'title',
                'contributableId' => $title->id,
                'contributableLabel' => $title->name,
            ])
            ->set('field', 'plot_outline')
            ->set('value', 'A tighter plot summary for the catalog card.')
            ->set('notes', 'Verified against the latest studio synopsis.')
            ->call('save')
            ->assertHasNoErrors();

        /** @var Contribution $contribution */
        $contribution = Contribution::query()->latest('id')->firstOrFail();

        $this->assertSame($contributor->id, $contribution->user_id);
        $this->assertSame(Title::class, $contribution->contributable_type);
        $this->assertSame($title->id, $contribution->contributable_id);
        $this->assertSame(ContributionAction::Update, $contribution->action);
        $this->assertSame(ContributionStatus::Submitted, $contribution->status);
        $this->assertSame('Plot outline', $contribution->proposed_field_label);
        $this->assertSame('A tighter plot summary for the catalog card.', $contribution->proposed_value);
        $this->assertSame('Verified against the latest studio synopsis.', $contribution->submission_notes);

        $this->actingAs($moderator)
            ->get(route('admin.contributions.index'))
            ->assertOk()
            ->assertSee('Plot outline')
            ->assertSee('A tighter plot summary for the catalog card.');
    }

    public function test_regular_user_cannot_submit_catalog_contributions(): void
    {
        $regularUser = User::factory()->create();
        $title = Title::factory()->create();

        Livewire::actingAs($regularUser)
            ->test(SuggestionForm::class, [
                'contributableType' => 'title',
                'contributableId' => $title->id,
                'contributableLabel' => $title->name,
            ])
            ->set('field', 'plot_outline')
            ->set('value', 'Blocked contribution')
            ->call('save')
            ->assertForbidden();
    }

    public function test_suggestion_form_validates_value_during_field_updates(): void
    {
        $contributor = User::factory()->contributor()->create();
        $title = Title::factory()->create();

        Livewire::actingAs($contributor)
            ->test(SuggestionForm::class, [
                'contributableType' => 'title',
                'contributableId' => $title->id,
                'contributableLabel' => $title->name,
            ])
            ->set('value', str_repeat('A', 5001))
            ->assertHasErrors(['value' => ['max']]);
    }
}
