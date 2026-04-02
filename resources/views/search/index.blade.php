@extends('layouts.public')

@section('title', 'Search')
@section('meta_description', 'Search titles, people, and public lists with top matches, poster-first results, and focused title discovery filters.')

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('public.home')">Home</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>Search</x-ui.breadcrumbs.item>
@endsection

@section('content')
    <livewire:search.search-results />
@endsection
