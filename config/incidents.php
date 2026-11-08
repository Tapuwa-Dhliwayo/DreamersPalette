<?php

return [
    'enabled' => env('INCIDENTS_ENABLED', true),
    'path' => storage_path('app/private/operations/incidents.json'),
    'email' => env('INCIDENT_ALERT_TO') ?: env('ADMIN_EMAIL'),
    'mailer' => env('INCIDENT_ALERT_MAILER', env('MAIL_MAILER', 'log')),
];
