@extends('layouts.account')

@section('title', $list->name)

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('public.home')">Home</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item :href="route('account.lists.index')">Your Lists</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>{{ $list->name }}</x-ui.breadcrumbs.item>
@endsection

@section('content')
    <livewire:lists.manage-list :list="$list" defer />
@endsection
