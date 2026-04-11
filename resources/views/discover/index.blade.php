@extends('layouts.public')

@section('title', 'Discovery')
@section('meta_description', 'Use Screenbase advanced discovery filters to explore titles by type, release date, awards, ratings, votes, language, runtime, and country.')

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('public.home')">Home</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>Discovery</x-ui.breadcrumbs.item>
@endsection

@section('content')
    <section class="space-y-6">
        <livewire:search.discovery-filters />
    </section>
@endsection
