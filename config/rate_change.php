<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Rate Change Notification Threshold
    |--------------------------------------------------------------------------
    |
    | A notification is sent whenever a currency's rate moves by more than
    | this percentage compared to its previously stored rate.
    |
    */

    'threshold_percent' => (float) env('RATE_CHANGE_THRESHOLD_PERCENT', 5),

    /*
    |--------------------------------------------------------------------------
    | Notification Email
    |--------------------------------------------------------------------------
    |
    | The email address rate change notifications are sent to.
    |
    */

    'notification_email' => env('RATE_CHANGE_NOTIFICATION_EMAIL'),

];
