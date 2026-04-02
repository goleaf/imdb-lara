<?php

namespace App\Livewire\People;

use App\Actions\Catalog\BuildPersonFilmographyQueryAction;
use App\Models\Person;
use Livewire\Attributes\Url;
use Livewire\Component;

class FilmographyPanel extends Component
{
    public Person $person;

    #[Url(as: 'job')]
    public ?string $profession = null;

    #[Url(as: 'credit_sort')]
    public string $sort = 'latest';

    public function mount(Person $person): void
    {
        $this->person = $person;
    }

    public function render()
    {
        $filmography = app(BuildPersonFilmographyQueryAction::class)->handle($this->person, [
            'profession' => $this->profession,
            'sort' => $this->sort,
        ]);

        return view('livewire.people.filmography-panel', [
            'groups' => $filmography['groups'],
            'professionOptions' => $filmography['professionOptions'],
            'sortOptions' => collect([
                ['value' => 'latest', 'label' => 'Newest first', 'icon' => 'calendar-days'],
                ['value' => 'oldest', 'label' => 'Oldest first', 'icon' => 'clock'],
                ['value' => 'rating', 'label' => 'Highest rated', 'icon' => 'star'],
            ]),
        ]);
    }
}
