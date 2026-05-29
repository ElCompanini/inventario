<?php

return [
    'enabled' => env('ERP_MAIL_NOTIFICATIONS_ENABLED', true),

    'stock_alert_debounce_hours' => (int) env('ERP_STOCK_ALERT_DEBOUNCE_HOURS', 6),

    'queue' => env('ERP_MAIL_QUEUE', 'default'),
];
