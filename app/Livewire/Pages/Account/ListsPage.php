<?php

namespace App\Livewire\Pages\Account;

use App\Actions\Lists\BuildAccountListsIndexQueryAction;
use App\Livewire\Pages\Concerns\RendersPageView;
use App\Models\User;
use App\Models\UserList;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ListsPage extends Component
{
    use RendersPageView;

    public ?UserList $list = null;

    public function mount(?UserList $list = null): void
    {
        if (! $list instanceof UserList) {
            return;
        }

        $user = auth()->user();
        $slug = (string) $list->getRouteKey();

        abort_unless($user instanceof User && $slug !== '', 404);

        $this->list = UserList::query()
            ->select([
                'id',
                'user_id',
                'name',
                'slug',
                'description',
                'visibility',
                'is_watchlist',
                'meta_title',
                'meta_description',
                'created_at',
                'updated_at',
            ])
            ->whereBelongsTo($user)
            ->custom()
            ->where('slug', $slug)
            ->first();

        abort_unless($this->list instanceof UserList && $user->can('update', $this->list), 404);
    }

    public function render(BuildAccountListsIndexQueryAction $buildAccountListsIndexQuery): View
    {
        if ($this->list instanceof UserList) {
            return $this->renderPageView('account.lists.show', [
                'list' => $this->list,
            ]);
        }

        $lists = $buildAccountListsIndexQuery
            ->handle(auth()->user())
            ->simplePaginate(12)
            ->withQueryString();

        return $this->renderPageView('account.lists.index', [
            'lists' => $lists,
        ]);
    }
}
