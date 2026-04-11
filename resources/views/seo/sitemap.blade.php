@php echo '<?xml version="1.0" encoding="UTF-8"?>'; @endphp
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    @foreach ($staticRoutes as $staticRoute)
        <url>
            <loc>{{ $staticRoute }}</loc>
        </url>
    @endforeach

    @foreach ($genres as $genre)
        <url>
            <loc>{{ route('public.genres.show', $genre) }}</loc>
        </url>
    @endforeach

    @foreach ($years as $year)
        <url>
            <loc>{{ route('public.years.show', ['year' => $year]) }}</loc>
        </url>
    @endforeach

    @foreach ($titles as $title)
        <url>
            <loc>{{ route('public.titles.show', $title) }}</loc>
        </url>
    @endforeach

    @foreach ($titleArchiveUrls as $titleArchiveUrl)
        <url>
            <loc>{{ $titleArchiveUrl }}</loc>
        </url>
    @endforeach

    @foreach ($seasons as $season)
        @if ($season->series)
            <url>
                <loc>{{ route('public.seasons.show', ['series' => $season->series, 'season' => $season]) }}</loc>
            </url>
        @endif
    @endforeach

    @foreach ($episodes as $episode)
        @if ($episode->episodeMeta?->series)
            <url>
                <loc>{{ route('public.episodes.show', ['series' => $episode->episodeMeta->series, 'season' => 'season-'.$episode->episodeMeta->season_number, 'episode' => $episode]) }}</loc>
            </url>
        @endif
    @endforeach

    @foreach ($people as $person)
        <url>
            <loc>{{ route('public.people.show', $person) }}</loc>
        </url>
    @endforeach
</urlset>
