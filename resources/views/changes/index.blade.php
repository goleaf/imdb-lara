@extends('layouts.public')

@section('title', 'Changes')
@section('meta_description', 'Read the latest Screenbase portal updates, release notes, and catalog improvements published from repository changelog files.')

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('public.home')">Home</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>Changes</x-ui.breadcrumbs.item>
@endsection

@section('content')
    <section class="sb-changelog-page" data-slot="changes-page-shell">
        <div class="sb-changelog-stream">
            @forelse ($entries as $entry)
                <article
                    id="{{ $entry['id'] }}"
                    class="sb-changelog-entry"
                    data-slot="changes-entry"
                    wire:key="changes-entry-{{ $entry['id'] }}"
                >
                    <div class="sb-changelog-entry-grid">
                        <div class="sb-changelog-entry-meta">
                            <div class="sb-changelog-entry-date">{{ $entry['date']->format('M j') }}</div>
                            <div class="sb-changelog-entry-year">{{ $entry['date']->format('Y') }}</div>
                        </div>

                        <div class="sb-changelog-entry-body min-w-0 space-y-5">
                            <header class="sb-changelog-entry-header space-y-3">
                                <x-ui.heading level="h2" size="lg" class="sb-changelog-entry-title">
                                    {{ $entry['title'] }}
                                </x-ui.heading>

                                @if ($entry['excerpt'])
                                    <x-ui.text class="sb-changelog-entry-excerpt">
                                        {{ $entry['excerpt'] }}
                                    </x-ui.text>
                                @endif
                            </header>

                            <div class="sb-changelog-prose">
                                {!! $entry['html'] !!}
                            </div>
                        </div>
                    </div>
                </article>
            @empty
                <x-ui.empty class="rounded-box border border-dashed border-white/10 bg-white/5">
                    <x-ui.heading level="h3">No release notes are available yet.</x-ui.heading>
                    <x-ui.text class="mt-1 text-neutral-300">
                        Add a markdown file under <code>/changelog</code> to publish the first update.
                    </x-ui.text>
                </x-ui.empty>
            @endforelse
        </div>
    </section>
@endsection
