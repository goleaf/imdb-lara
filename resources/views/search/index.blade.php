@extends('layouts.public')

@section('title', 'Search')
@section('meta_description', 'Search imported titles and people, then refine title matches by type, year, rating, runtime, language, and country.')

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('public.home')">Home</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>Search</x-ui.breadcrumbs.item>
@endsection

@section('content')
    <livewire:search.search-results />
@endsection
