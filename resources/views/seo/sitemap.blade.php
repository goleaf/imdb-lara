@php echo '<?xml version="1.0" encoding="UTF-8"?>'; @endphp
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    @foreach ($staticRoutes as $staticRoute)
        <url>
            <loc>{{ $staticRoute }}</loc>
        </url>
    @endforeach

    @foreach ($titles as $title)
        <url>
            <loc>{{ route('public.titles.show', $title) }}</loc>
            @if ($title->updated_at)
                <lastmod>{{ $title->updated_at->toAtomString() }}</lastmod>
            @endif
        </url>
    @endforeach

    @foreach ($people as $person)
        <url>
            <loc>{{ route('public.people.show', $person) }}</loc>
            @if ($person->updated_at)
                <lastmod>{{ $person->updated_at->toAtomString() }}</lastmod>
            @endif
        </url>
    @endforeach

    @foreach ($lists as $list)
        <url>
            <loc>{{ route('public.lists.show', [$list->user, $list]) }}</loc>
            @if ($list->updated_at)
                <lastmod>{{ $list->updated_at->toAtomString() }}</lastmod>
            @endif
        </url>
    @endforeach
</urlset>
