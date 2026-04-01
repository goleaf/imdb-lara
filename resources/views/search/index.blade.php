@extends('layouts.public')

@section('title', 'Search')
@section('meta_description', 'Run advanced title discovery across keywords, genre, title type, and minimum ratings.')

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('public.home')">Home</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>Search</x-ui.breadcrumbs.item>
@endsection

@section('content')
    <livewire:search.search-results />
@endsection
