<?php

namespace App\Livewire\Seasons;

use App\Actions\Titles\GetSeasonWatchProgressAction;
use App\Actions\Titles\MarkSeasonEpisodesWatchedAction;
use App\Models\Season;
use App\Models\Title;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class WatchProgressPanel extends Component
{
    public Title $series;

    public Season $season;

    public int $totalEpisodes = 0;

    public int $watchedEpisodes = 0;

    public int $remainingEpisodes = 0;

    public int $percentage = 0;

    public ?string $statusMessage = null;

    public function mount(Title $series, Season $season, GetSeasonWatchProgressAction $getSeasonWatchProgress): void
    {
        $this->series = $series;
        $this->season = $season;

        if (! auth()->check()) {
            return;
        }

        $this->fillProgress(auth()->user(), $getSeasonWatchProgress);
    }

    public function markSeasonWatched(
        MarkSeasonEpisodesWatchedAction $markSeasonEpisodesWatched,
        GetSeasonWatchProgressAction $getSeasonWatchProgress,
    ): void {
        if (! auth()->check()) {
            $this->redirectRoute('login');

            return;
        }

        $updatedEntries = $markSeasonEpisodesWatched->handle(auth()->user(), $this->season);

        $this->fillProgress(auth()->user(), $getSeasonWatchProgress);
        $this->statusMessage = $updatedEntries > 0
            ? 'Every published episode in this season is now marked as watched.'
            : 'No published episodes were available to update yet.';
    }

    public function render(): View
    {
        return view('livewire.seasons.watch-progress-panel', [
            'markButton' => $this->markButton(),
            'progressSummary' => $this->progressSummary(),
            'statusAlert' => $this->statusAlert(),
        ]);
    }

    private function fillProgress(User $user, GetSeasonWatchProgressAction $getSeasonWatchProgress): void
    {
        $progress = $getSeasonWatchProgress->handle($user, $this->season);

        $this->totalEpisodes = $progress['total'];
        $this->watchedEpisodes = $progress['watched'];
        $this->remainingEpisodes = $progress['remaining'];
        $this->percentage = $progress['percentage'];
    }

    /**
     * @return array{label: string, variant: string}
     */
    private function markButton(): array
    {
        $seasonIsComplete = $this->remainingEpisodes === 0 && $this->totalEpisodes > 0;

        return [
            'label' => $seasonIsComplete ? 'Season watched' : 'Mark season watched',
            'variant' => $seasonIsComplete ? 'outline' : 'primary',
        ];
    }

    private function progressSummary(): string
    {
        return number_format($this->watchedEpisodes).' of '.number_format($this->totalEpisodes).' episodes tracked';
    }

    /**
     * @return array{icon: string, variant: string}|null
     */
    private function statusAlert(): ?array
    {
        if (! filled($this->statusMessage)) {
            return null;
        }

        $hasNoPublishedEpisodes = str_contains(strtolower($this->statusMessage), 'no published episodes');

        return [
            'icon' => $hasNoPublishedEpisodes ? 'information-circle' : 'check-circle',
            'variant' => $hasNoPublishedEpisodes ? 'info' : 'success',
        ];
    }
}
