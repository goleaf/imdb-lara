@extends('layouts.public')

@section('title', 'Changes')
@section('meta_description', 'Read the latest Screenbase portal updates, release notes, and catalog improvements rendered from the repository changelog.')

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('public.home')">Home</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>Changes</x-ui.breadcrumbs.item>
@endsection

@section('content')
    <section class="space-y-6" data-slot="changes-page-shell">
        <div class="sb-changelog-stream">
            @foreach ($entries as $entry)
                <article id="{{ $entry['id'] }}" class="sb-changelog-entry" data-slot="changes-entry">
                    <div class="sb-changelog-entry-grid">
                        <div class="sb-changelog-entry-meta">
                            <div class="sb-changelog-entry-date">{{ $entry['date']->format('M j, Y') }}</div>
                            <div class="sb-changelog-entry-year">{{ $entry['date']->format('Y') }}</div>
                        </div>

                        <div class="min-w-0 space-y-5">
                            <div class="space-y-3">
                                <x-ui.heading level="h2" size="lg" class="sb-changelog-entry-title">
                                    {{ $entry['title'] }}
                                </x-ui.heading>

                                @if ($entry['excerpt'])
                                    <x-ui.text class="sb-changelog-entry-excerpt max-w-3xl text-base">
                                        {{ $entry['excerpt'] }}
                                    </x-ui.text>
                                @endif
                            </div>

                            <div class="sb-changelog-prose">
                                {!! $entry['html'] !!}
                            </div>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    </section>
@endsection
