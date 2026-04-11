@extends('layouts.public')

@section('title', 'Create account')
@section('shell_variant', 'auth')
@section('show_footer', '0')

@section('content')
    <x-auth.member-entry-card
        active-tab="register"
        kicker="Create Your Identity"
        title="Create your Screenbase account"
        copy="Start a premium member profile for watchlists, ratings, curated lists, and moderated reviews without the usual clutter."
        note="Social entry is being prepared. You can create a full member account with email right away."
        divider-label="Or create with email"
    >
        <livewire:auth.register-form />
    </x-auth.member-entry-card>
@endsection
