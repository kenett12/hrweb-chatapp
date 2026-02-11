<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Filters\CSRF;
use CodeIgniter\Filters\DebugToolbar;
use CodeIgniter\Filters\Honeypot;
use CodeIgniter\Shield\Filters\AuthRates;
use CodeIgniter\Shield\Filters\AuthThrottle;
use App\Filters\AuthFilter;
use App\Filters\GuestFilter;
use App\Filters\TSRFilter;

class Filters extends BaseConfig
{
    /**
     * Configures aliases for Filter classes to
     * make reading things nicer and simpler.
     *
     * @var array<string, string>
     * @phpstan-var array<string, class-string>
     */
    public $aliases = [
        'csrf'     => CSRF::class,
        'toolbar'  => DebugToolbar::class,
        'honeypot' => Honeypot::class,
        'auth'     => AuthFilter::class,
        'guest'    => GuestFilter::class,
        'tsr'      => TSRFilter::class,
        'auth-rates'  => AuthRates::class,
        'auth-throttle' => AuthThrottle::class,
    ];

    /**
     * List of filter aliases that are always
     * applied before and after every request.
     *
     * @var array<string, array<string, array<string, string>>>|array<string, array<string>>
     * @phpstan-var array<string, list<string>>|array<string, array<string, array<string, string>>>
     */
    public $globals = [
        'before' => [
            'honeypot',
            // 'csrf',
            // 'auth-throttle', //Remove auth-throttle to allow API requests
        ],
        'after' => [
            'toolbar',
            'honeypot',
            // 'auth-rates',  //Remove auth-rates to allow API requests
        ],
    ];

    /**
     * List of filter aliases that works on a
     * particular HTTP method (GET, POST, etc.).
     *
     * Example:
     * 'post' => ['csrf', 'throttle']
     *
     * @var array<string, array<string>>
     */
    public $methods = [];

    /**
     * List of filter aliases that should run on any
     * before or after URI patterns.
     *
     * Example:
     * 'isLoggedIn' => ['before' => ['account/*', 'profiles/*']]
     *
     * @var array<string, array<string, array<string, string>>>
     */
    public $filters = [
        'auth' => ['before' => ['admin/*', 'user/*']],
        'tsr' => ['before' => ['admin/*']]
    ];
}
