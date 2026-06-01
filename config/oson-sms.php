<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | API endpoint
    |--------------------------------------------------------------------------
    */
    'server' => env('OSON_SMS_SERVER', 'https://api.osonsms.com/sendsms_v1.php'),

    /*
    |--------------------------------------------------------------------------
    | Credentials
    |--------------------------------------------------------------------------
    |
    | login  -> the `login` request parameter
    | token  -> Bearer token sent in the Authorization header
    | sender -> default sender name/number (the `from` parameter)
    |
    */
    'login' => env('OSON_SMS_LOGIN', ''),
    'token' => env('OSON_SMS_TOKEN', ''),
    'sender' => env('OSON_SMS_SENDER', ''),

    /*
    |--------------------------------------------------------------------------
    | Optional SHA-256 signature (str_hash)
    |--------------------------------------------------------------------------
    |
    | Leave empty to authenticate with the Bearer token only. Set the API hash
    | secret here if your account requires a signed `str_hash` parameter:
    | SHA256(txn_id ; login ; sender ; phone_number ; hash_secret).
    |
    */
    'hash_secret' => env('OSON_SMS_HASH_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | HTTP timeout (seconds)
    |--------------------------------------------------------------------------
    */
    'timeout' => (int) env('OSON_SMS_TIMEOUT', 30),
];
