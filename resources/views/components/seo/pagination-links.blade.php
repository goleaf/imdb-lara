@props([
    'paginator',
])

@push('head')
    @if ($paginator->previousPageUrl())
        <link rel="prev" href="{{ $paginator->previousPageUrl() }}">
    @endif

    @if ($paginator->nextPageUrl())
        <link rel="next" href="{{ $paginator->nextPageUrl() }}">
    @endif
@endpush
