@props([
    'type' => 'country',
    'code' => null,
    'variant' => 'flat',
])

@if ($flag = app(\App\Actions\Layout\ResolveFlagViewDataAction::class)->handle(
    type: $type,
    code: $code,
    variant: $variant,
    className: (string) $attributes->get('class'),
))
    <span
        {{ $attributes->except('class')->class('inline-flex items-center') }}
        data-slot="flag"
        data-flag-type="{{ $flag['type'] }}"
        data-flag-code="{{ $flag['code'] }}"
        aria-hidden="true"
    >
        {!! $flag['iconMarkup'] !!}
    </span>
@endif
