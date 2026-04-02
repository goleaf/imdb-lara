<?php

namespace App\Livewire\Pages\Public;

use App\Actions\Lists\BuildUserListItemsQueryAction;
use App\Actions\Lists\LoadPublicUserListAction;
use App\Actions\Seo\PageSeoData;
use App\Actions\Users\LoadPublicUserProfileAction;
use App\Enums\MediaKind;
use App\Livewire\Pages\Concerns\RendersPageView;
use App\Models\MediaAsset;
use App\Models\User;
use App\Models\UserList;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class UserPage extends Component
{
    use RendersPageView;

    public ?UserList $list = null;

    public User $user;

    public function mount(User $user, ?UserList $list = null): void
    {
        if (request()->routeIs('public.users.show')) {
            abort_unless($user->isProfileVisibleToPublic(), 404);
        }

        if (request()->routeIs('public.lists.show')) {
            abort_unless(
                $list instanceof UserList
                && $list->user_id === $user->id
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
        if (request()->routeIs('public.lists.show')) {
            $list = $loadPublicUserList->handle($this->list);
            $itemsQuery = $buildUserListItemsQuery->handle($list);
            $listPreviewTitle = (clone $itemsQuery)->first()?->title;
            $items = $itemsQuery
                ->simplePaginate(18, pageName: 'titles')
                ->withQueryString();
            $listPreviewImage = $listPreviewTitle
                ? MediaAsset::preferredFrom($listPreviewTitle->mediaAssets, MediaKind::Poster, MediaKind::Backdrop)
                : null;
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
