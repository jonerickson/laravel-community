<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Trending Algorithm Weights
    |--------------------------------------------------------------------------
    |
    | These weights determine how much each engagement metric contributes
    | to the overall trending score. Higher weights mean more influence.
    |
    */

    'weights' => [
        'views' => 1.0,
        'unique_views' => 1.5,
        'posts' => 3.0,
        'reads' => 2.0,
        'likes' => 2.5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Time Decay Parameters
    |--------------------------------------------------------------------------
    |
    | Controls how content popularity decays over time to promote fresh content.
    |
    */

    'decay' => [
        // Half-life in hours (168 = 7 days)
        'half_life' => 168,

        // Recency boost multiplier for new content
        'recency_boost' => [
            'multiplier' => 2.0,
            'threshold_hours' => 24,
        ],

        // Sharp drop-off for old content
        'old_content' => [
            'multiplier' => 0.1,
            'threshold_hours' => 720, // 30 days
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Trending score calculations can be expensive, so we cache them.
    |
    */

    'cache' => [
        // Cache duration in minutes
        'duration' => 60,

        // Cache key prefix
        'prefix' => 'trending',

        // Whether to cache individual scores
        'cache_scores' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Query Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for trending queries and limits.
    |
    */

    'query' => [
        // Default limit for trending queries
        'default_limit' => 50,

        // Minimum engagement threshold for trending
        'min_engagement_threshold' => 1,

        // Available timeframes for trending queries (in hours)
        'timeframes' => [
            'hour' => 1,
            'day' => 24,
            'week' => 168,
            'month' => 720,
            'all' => null, // null means no time limit
        ],
    ],
];
