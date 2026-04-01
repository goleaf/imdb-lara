@extends('layouts.admin')

@section('title', 'Edit Credit')

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('admin.dashboard')">Admin</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>Edit Credit</x-ui.breadcrumbs.item>
@endsection

@section('content')
    <section class="space-y-4">
        @if (session('status'))
            <x-ui.alerts variant="success" icon="check-circle">
                <x-ui.alerts.description>{{ session('status') }}</x-ui.alerts.description>
            </x-ui.alerts>
        @endif

        <x-ui.card class="!max-w-none">
            <form method="POST" action="{{ route('admin.credits.update', $credit) }}" class="space-y-6">
                @csrf
                @method('PATCH')

                @include('admin.credits._form')

                <div class="flex justify-end gap-3">
                    <x-ui.button as="a" :href="route('admin.titles.edit', $credit->title)" variant="ghost" icon="arrow-left">
                        Back to title
                    </x-ui.button>
                    <x-ui.button type="submit" icon="check-circle">
                        Save changes
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </section>
@endsection
