<?php

return [
    'component_locations' => [
        resource_path('views/components'),
        resource_path('views/livewire'),
    ],

    'component_namespaces' => [
        'layouts' => resource_path('views/layouts'),
        'pages' => resource_path('views/pages'),
    ],

    'component_layout' => 'layouts.app',

    'component_placeholder' => null,

    'make_command' => [
        'type' => 'sfc',
        'emoji' => true,
        'with' => [
            'js' => false,
            'css' => false,
            'test' => false,
        ],
    ],

    'class_namespace' => 'App\\Livewire',

    'class_path' => app_path('Livewire'),

    'view_path' => resource_path('views/livewire'),

    'render_on_redirect' => false,

    'legacy_model_binding' => false,

    'inject_assets' => false,

    'navigate' => [
        'show_progress_bar' => true,
        'progress_bar_color' => '#d6b574',
    ],

    'inject_morph_markers' => true,

    'smart_wire_keys' => true,

    'pagination_theme' => 'tailwind',

    'csp_safe' => false,
];
