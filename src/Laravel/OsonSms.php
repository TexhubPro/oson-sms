<?php

declare(strict_types=1);

namespace TexHub\OsonSms\Laravel;

use Illuminate\Support\Facades\Facade;

/**
 * Laravel facade for the OsonSMS client.
 *
 * @method static \TexHub\OsonSms\Clients\SmsClient sms()
 * @method static \TexHub\OsonSms\Http\Response      send(\TexHub\OsonSms\Requests\SmsMessage|string $message, ?string $text = null)
 * @method static \TexHub\OsonSms\Config             config()
 *
 * @see \TexHub\OsonSms\OsonSms
 */
class OsonSms extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'oson-sms';
    }
}
