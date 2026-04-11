<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'imdb' => [
        'base_url' => env('IMDB_API_BASE_URL', 'https://api.imdbapi.dev'),
        'catalog_import' => [
            'seed_titles' => [],
        ],
        'graphql' => [
            'enabled' => (bool) env('IMDB_GRAPHQL_ENABLED', false),
            'url' => env('IMDB_GRAPHQL_URL', 'https://graph.imdbapi.dev/v1'),
        ],
        'http_cache' => [
            'enabled' => (bool) env('IMDB_HTTP_CACHE_ENABLED', false),
            'ttl_seconds' => max(0, (int) env('IMDB_HTTP_CACHE_TTL_SECONDS', 86400)),
        ],
        'inter_request_delay_microseconds' => (int) env('IMDB_INTER_REQUEST_DELAY_MICROSECONDS', 1000000),
        'retry_attempts' => max(1, (int) env('IMDB_HTTP_RETRY_ATTEMPTS', 5)),
        'retry_delay_milliseconds' => max(0, (int) env('IMDB_HTTP_RETRY_DELAY_MILLISECONDS', 1000)),
        'default_batch_concurrency' => max(1, (int) env('DEFAULT_BATCH_CONCURRENCY', 5)),
        'title_batch_concurrency' => max(
            1,
            (int) env('IMDB_TITLE_BATCH_CONCURRENCY', env('DEFAULT_BATCH_CONCURRENCY', 5)),
        ),
        'name_batch_concurrency' => max(
            1,
            (int) env('IMDB_NAME_BATCH_CONCURRENCY', env('DEFAULT_BATCH_CONCURRENCY', 5)),
        ),
        'endpoints' => [
            'chart.starmeter' => '/chart/starmeter',
            'titles.frontier' => '/titles',
            'title' => '/titles/{titleId}',
            'title.credits' => '/titles/{titleId}/credits',
            'title.release_dates' => '/titles/{titleId}/releaseDates',
            'title.akas' => '/titles/{titleId}/akas',
            'title.seasons' => '/titles/{titleId}/seasons',
            'title.episodes' => '/titles/{titleId}/episodes',
            'title.images' => '/titles/{titleId}/images',
            'title.videos' => '/titles/{titleId}/videos',
            'title.award_nominations' => '/titles/{titleId}/awardNominations',
            'title.parents_guide' => '/titles/{titleId}/parentsGuide',
            'title.certificates' => '/titles/{titleId}/certificates',
            'title.company_credits' => '/titles/{titleId}/companyCredits',
            'title.box_office' => '/titles/{titleId}/boxOffice',
            'search.titles' => '/search/titles',
            'interests.frontier' => '/interests',
            'interest' => '/interests/{interestId}',
            'name' => '/names/{nameId}',
            'name.images' => '/names/{nameId}/images',
            'name.relationships' => '/names/{nameId}/relationships',
            'name.trivia' => '/names/{nameId}/trivia',
            'name.filmography' => '/names/{nameId}/filmography',
        ],
    ],

];
