@include('layouts.partials.app-shell', [
    'shell' => app(\App\Actions\Seo\ResolvePageShellViewDataAction::class)->forStandardLayout($__env, get_defined_vars()),
])
