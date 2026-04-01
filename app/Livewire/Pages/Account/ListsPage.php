<?php

namespace App\Livewire\Pages\Account;

use App\Actions\Lists\BuildAccountListsIndexQueryAction;
use App\Livewire\Pages\Concerns\RendersLegacyPage;
use App\Models\User;
use App\Models\UserList;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ListsPage extends Component
{
    use RendersLegacyPage;

    public ?UserList $list = null;

    public function mount(): void
    {
        if (! request()->routeIs('account.lists.show')) {
            return;
        }

        $user = auth()->user();
        $routeList = request()->route('list');
        $slug = $routeList instanceof UserList
            ? (string) $routeList->getRouteKey()
            : (string) $routeList;

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
        if (request()->routeIs('account.lists.show')) {
            return $this->renderLegacyPage('account.lists.show', [
                'list' => $this->list,
            ]);
        }

        $lists = $buildAccountListsIndexQuery
            ->handle(auth()->user())
            ->simplePaginate(12)
            ->withQueryString();

        return $this->renderLegacyPage('account.lists.index', [
            'lists' => $lists,
        ]);
    }
}
