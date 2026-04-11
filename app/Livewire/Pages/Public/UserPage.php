<?php

namespace App\Livewire\Pages\Public;

use App\Actions\Lists\BuildUserListItemsQueryAction;
use App\Actions\Lists\LoadPublicUserListAction;
use App\Actions\Seo\PageSeoData;
use App\Actions\Users\LoadPublicUserProfileAction;
use App\Livewire\Pages\Concerns\RendersPageView;
use App\Models\ListItem;
use App\Models\User;
use App\Models\UserList;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;

class UserPage extends Component
{
    use RendersPageView;

    #[Locked]
    public ?UserList $list = null;

    #[Locked]
    public User $user;

    public function mount(User $user, ?UserList $list = null): void
    {
        if (! $list instanceof UserList) {
            abort_unless($user->isProfileVisibleToPublic(), 404);
        }

        if ($list instanceof UserList) {
            abort_unless(
                $list->user_id === $user->id
                && Gate::allows('view', $list),
                404,
            );
        }

        $this->user = $user;
        $this->list = $list;
    }

    public function render(
        LoadPublicUserProfileAction $loadPublicUserProfile,
        BuildUserListItemsQueryAction $buildUserListItemsQuery,
        LoadPublicUserListAction $loadPublicUserList,
    ): View {
        if ($this->list instanceof UserList) {
            $list = $loadPublicUserList->handle($this->list);
            $itemsQuery = $buildUserListItemsQuery->handle($list);
            $items = $itemsQuery
                ->simplePaginate(18, pageName: 'titles')
                ->withQueryString();
            $items->setCollection(
                $items->getCollection()
                    ->filter(fn (ListItem $item): bool => $item->title !== null)
                    ->values(),
            );
            $listPreviewTitle = $items->getCollection()->first()?->title;
            $listPreviewImage = $listPreviewTitle?->preferredPoster() ?? $listPreviewTitle?->preferredBackdrop();
            $breadcrumbs = [
                ['label' => 'Home', 'href' => route('public.home')],
                ['label' => 'Public Lists', 'href' => route('public.lists.index')],
                ['label' => $this->user->name, 'href' => route('public.users.show', $this->user)],
                ['label' => $list->name],
            ];

            return $this->renderPageView('lists.show', [
                'items' => $items,
                'list' => $list,
                'owner' => $this->user,
                'seo' => new PageSeoData(
                    title: $list->meta_title ?: $list->name,
                    description: $list->meta_description ?: ($list->description ?: 'Browse the curated Screenbase list '.$list->name.'.'),
                    canonical: route('public.lists.show', [$this->user, $list]),
                    robots: $list->isPublic() ? 'index,follow' : 'noindex,follow',
                    openGraphImage: $listPreviewImage?->url,
                    openGraphImageAlt: $listPreviewImage?->alt_text ?: $listPreviewTitle?->name,
                    breadcrumbs: $breadcrumbs,
                    paginationPageName: 'titles',
                ),
            ]);
        }

        return $this->renderPageView('users.show', $loadPublicUserProfile->handle($this->user, auth()->user()));
    }
}
