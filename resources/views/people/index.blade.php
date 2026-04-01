@extends('layouts.public')

@section('title', 'Browse People')
@section('meta_description', 'Browse the people directory for actors, directors, and other creators in the Screenbase catalog.')

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('public.home')">Home</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>Browse People</x-ui.breadcrumbs.item>
@endsection

@section('content')
    <section class="space-y-4">
        <div class="flex items-center justify-between gap-4">
            <div>
                <x-ui.heading level="h1" size="xl">Browse People</x-ui.heading>
                <x-ui.text class="mt-1 text-neutral-600 dark:text-neutral-300">
                    Cast and crew profiles with filmography context and department metadata.
                </x-ui.text>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @forelse ($people as $person)
                <x-catalog.person-card :person="$person" />
            @empty
                <div class="sm:col-span-2 xl:col-span-3">
                    <x-ui.empty class="rounded-box border border-dashed border-black/10 bg-white dark:border-white/10 dark:bg-neutral-900">
                        <x-ui.heading level="h3">No people have been published yet.</x-ui.heading>
                    </x-ui.empty>
                </div>
            @endforelse
        </div>

        <div>
            {{ $people->links() }}
        </div>
    </section>
@endsection
