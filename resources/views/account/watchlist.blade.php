@extends('layouts.account')

@section('title', 'Your Watchlist')

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('public.home')">Home</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>Your Watchlist</x-ui.breadcrumbs.item>
@endsection

@section('content')
    <livewire:account.watchlist-browser />
@endsection
