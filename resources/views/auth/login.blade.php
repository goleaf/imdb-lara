@extends('layouts.public')

@section('title', 'Sign in')
@section('shell_variant', 'auth')
@section('show_footer', '0')

@section('content')
    <x-auth.member-entry-card
        active-tab="login"
        kicker="Member Access"
        title="Sign in to Screenbase"
        copy="Return to your private watchlist, ratings, reviews, and editorial profile in one calm member entry."
        note="Social entry is being prepared. Email access is available now for every Screenbase member."
        divider-label="Or continue with email"
    >
        <livewire:auth.login-form />
    </x-auth.member-entry-card>
@endsection
