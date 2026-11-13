<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Network driver
    |--------------------------------------------------------------------------
    | fake  : no RADIUS or RouterOS side effect, safe for local/dev/CI (default)
    | radius: apply policy to the RADIUS SQL projection and optional RouterOS API
    |
    | Never switch this to "radius" on a developer machine or in CI.
    */
    'driver' => env('NETWORK_DRIVER', 'fake'),

    /*
    |--------------------------------------------------------------------------
    | Roles allowed to execute central network actions
    |--------------------------------------------------------------------------
    | Temporary legacy-role mapping until Agent A's permission catalog is
    | merged (network.device.manage, network.session.disconnect, ...).
    */
    'central_roles' => ['super_admin', 'admin', 'network_operator'],

    'partner_roles' => ['mitra', 'mitra_admin', 'mitra_billing', 'mitra_operator'],

    /*
    |--------------------------------------------------------------------------
    | RADIUS SQL projection
    |--------------------------------------------------------------------------
    | Connection name of the RADIUS database (separate role/schema). Null means
    | the projection adapter refuses to write (local/CI safety).
    */
    'radius' => [
        'connection' => env('RADIUS_DB_CONNECTION'),
        'tables' => [
            'check' => 'radcheck',
            'reply' => 'radreply',
            'group_check' => 'radgroupcheck',
            'group_reply' => 'radgroupreply',
            'user_group' => 'radusergroup',
            'accounting' => 'radacct',
        ],
        // USP: the SQL module version pinned by the ops team. Documented, not enforced.
        'schema_version' => env('RADIUS_SCHEMA_VERSION', '3.2.x'),
        // Enroll credentials in a separate secured path; this adapter only manages policy.
        'manage_credentials' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | RouterOS management API
    |--------------------------------------------------------------------------
    | Disabled until the target RouterOS version and CoA/Disconnect behavior are
    | proven in the lab. Disabled => disconnect commands stay pending, never
    | reported as applied.
    */
    'routeros' => [
        'enabled' => (bool) env('ROUTEROS_API_ENABLED', false),
        'timeout_seconds' => (int) env('ROUTEROS_API_TIMEOUT', 5),
        'port' => (int) env('ROUTEROS_API_PORT', 8728),
    ],

    /*
    |--------------------------------------------------------------------------
    | MikroTik rate limit
    |--------------------------------------------------------------------------
    | RouterOS "rate-limit=rx/tx" is expressed from the router's perspective:
    | rx = traffic received from the client (customer upload) and tx = traffic
    | sent to the client (customer download). Direction MUST be re-verified on
    | the target RouterOS version before production use.
    */
    'rate_limit' => [
        'template' => env('NETWORK_RATE_LIMIT_TEMPLATE', ':uploadM/:downloadM'),
        'include_burst' => (bool) env('NETWORK_RATE_LIMIT_BURST', false),
        'burst_template' => ':downloadM/:uploadM/:downloadM/:uploadM',
    ],

    'outbox' => [
        'max_attempts' => (int) env('NETWORK_OUTBOX_MAX_ATTEMPTS', 8),
        'retry_base_seconds' => (int) env('NETWORK_OUTBOX_RETRY_BASE', 30),
        'retry_max_seconds' => (int) env('NETWORK_OUTBOX_RETRY_MAX', 3600),
        'stale_lock_minutes' => (int) env('NETWORK_OUTBOX_STALE_LOCK', 10),
    ],

    'accounting' => [
        'calculation_version' => (int) env('NETWORK_ACCOUNTING_VERSION', 1),
        // Sessions without a Stop record are reconciled after this idle window.
        'stale_after_minutes' => (int) env('NETWORK_SESSION_STALE_MINUTES', 90),
        // Samples closer together than this are still accepted but flagged.
        'min_interim_seconds' => (int) env('NETWORK_ACCOUNTING_MIN_INTERIM', 30),
    ],

];
