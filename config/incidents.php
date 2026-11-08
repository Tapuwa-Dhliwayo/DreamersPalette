<?php

return [
    'enabled' => env('INCIDENTS_ENABLED', true),
    'path' => storage_path('app/private/operations/incidents.json'),
    'email' => env('INCIDENT_ALERT_TO') ?: env('ADMIN_EMAIL'),
    'mailer' => env('INCIDENT_ALERT_MAILER', env('MAIL_MAILER', 'log')),
    // A one-off error (deadlock, network blip) only alerts. A feature pauses when its
    // open incident sees this many occurrences, or this many distinct signed-in users
    // hitting the same error, inside the window.
    'pause_after_occurrences' => max(1, (int) env('INCIDENT_PAUSE_AFTER_OCCURRENCES', 3)),
    'pause_after_users' => max(1, (int) env('INCIDENT_PAUSE_AFTER_USERS', 2)),
    'pause_window_minutes' => max(1, (int) env('INCIDENT_PAUSE_WINDOW_MINUTES', 10)),
];
