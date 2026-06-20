# TexHub · OsonSMS

**English** · [Русский](README.ru.md)

[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/php-%5E8.2-777bb4.svg)](composer.json)
[![Laravel](https://img.shields.io/badge/laravel-11%20%7C%2012%20%7C%2013-ff2d20.svg)](#laravel)

A clean, framework-agnostic PHP SDK for the **OsonSMS** gateway — send single and bulk SMS — with first-class **Laravel** support.

> Works in plain PHP and any framework. Laravel gets auto-discovery, a config file and a facade for free.

---

## Features

- **Send SMS** with a fluent message builder or a one-line shortcut
- **Bulk send** that never throws — each result carries its own success/error
- **Bearer-token auth** + optional **SHA-256 `str_hash`** signing
- **Pluggable HTTP transport** — cURL by default; inject your own for testing
- **Typed responses & exceptions** (`ApiException` with code / message / error_type)
- **Fully unit-tested**, no network needed

---

## Installation

```bash
composer require texhub/oson-sms
```

Requirements: **PHP ≥ 8.2** with the `curl`, `json` and `hash` extensions.

---

## Quick start (plain PHP)

```php
use TexHub\OsonSms\OsonSms;

$oson = OsonSms::make(
    login: 'YOUR_LOGIN',
    token: 'YOUR_TOKEN',
    sender: 'YOUR_SENDER',
);

$response = $oson->send('992900123456', 'Привет! Это тест OsonSMS.');

echo $response->messageId;  // msg_id
echo $response->txnId;      // txn_id
```

### Fluent builder

```php
use TexHub\OsonSms\Requests\SmsMessage;

$oson->send(
    SmsMessage::make('992900123456', 'Ваш код: 1234')
        ->from('MyShop')        // override the default sender
        ->txnId('order-42')     // idempotency key (auto-generated otherwise)
);
```

### Bulk (tolerant — never throws)

```php
$results = $oson->sms()->sendMany([
    SmsMessage::make('992900000001', 'Сообщение 1'),
    SmsMessage::make('992900000002', 'Сообщение 2'),
]);

foreach ($results as $r) {
    if ($r['ok']) {
        echo "sent: {$r['response']->messageId}\n";
    } else {
        echo "failed: {$r['error']->getMessage()}\n";
    }
}
```

---

## Authentication

By default the SDK authenticates with the **Bearer token** in the `Authorization` header (as in the OsonSMS sample), sending these parameters:

| Param | Source |
|----------------|------------------------------|
| `from` | sender (config or per-message) |
| `phone_number` | recipient |
| `msg` | text |
| `txn_id` | idempotency key (auto) |
| `login` | config login |

### Optional `str_hash` signature

If your account requires a signed request, set a hash secret and the SDK appends a `str_hash` parameter:

```
str_hash = SHA256( txn_id ; login ; sender ; phone_number ; hash_secret )
```

```php
$oson = OsonSms::fromArray([
    'login' => '...', 'token' => '...', 'sender' => '...',
    'hash_secret' => 'YOUR_API_HASH',
]);
```

> If your terminal uses a different field order, compute it yourself with `new Signature($secret)` `->hash($yourString)`.

---

## Error handling

```php
use TexHub\OsonSms\Exceptions\ApiException;
use TexHub\OsonSms\Exceptions\TransportException;
use TexHub\OsonSms\Exceptions\OsonSmsException;

try {
    $oson->send('992900123456', 'Hi');
} catch (ApiException $e) {
    $e->errorCode;   // error.code
    $e->apiMessage;  // error.msg
    $e->errorType;   // error.error_type
} catch (TransportException $e) {
    // network failure
} catch (OsonSmsException $e) {
    // base type — also covers invalid responses
}
```

The API signals errors with a JSON body: `{ "error": { "code": ..., "msg": "...", "error_type": "..." } }`.

---

## <a name="laravel"></a> Laravel

The service provider and `OsonSms` facade are **auto-discovered**. Publish the config:

```bash
php artisan vendor:publish --tag=oson-sms-config
```

Add credentials to `.env`:

```dotenv
OSON_SMS_LOGIN=your_login
OSON_SMS_TOKEN=your_token
OSON_SMS_SENDER=YourSender
# optional:
OSON_SMS_HASH_SECRET=
OSON_SMS_SERVER=https://api.osonsms.com/sendsms_v1.php
OSON_SMS_TIMEOUT=30
```

Use the facade:

```php
use TexHub\OsonSms\Laravel\OsonSms;
use TexHub\OsonSms\Requests\SmsMessage;

OsonSms::send('992900123456', 'Привет из Laravel!');

OsonSms::send(
    SmsMessage::make('992900123456', 'Ваш код: 1234')->txnId('order-'.$order->id)
);
```

…or inject it:

```php
public function notify(\TexHub\OsonSms\OsonSms $oson) { /* ... */ }
```

---

## Testing

Inject the fake transport to test without hitting the network:

```php
use TexHub\OsonSms\OsonSms;
use TexHub\OsonSms\Config;
use TexHub\OsonSms\Tests\Support\FakeTransport;

$transport = (new FakeTransport())->willReturnJson(['msg_id' => '1', 'txn_id' => 'a']);
$oson = new OsonSms(new Config('login', 'token', 'Sender'), $transport);

$oson->send('992900123456', 'Hi');
// assert on $transport->lastQuery / lastHeaders / lastUrl
```

Run the package test suite:

```bash
composer install
composer test          # or: vendor/bin/phpunit
```

---

## Architecture

```
src/
├── OsonSms.php              # entry point — sms() / send()
├── Config.php               # immutable configuration
├── Signature.php            # optional SHA-256 str_hash signer
├── Http/                    # Transport interface, CurlTransport, Response
├── Requests/SmsMessage.php  # fluent message builder
├── Clients/SmsClient.php    # send() / sendMany()
├── Exceptions/              # ApiException, TransportException, …
└── Laravel/                 # ServiceProvider + Facade
```

---

## License

MIT © TexHub Pro — built by Mahmudi Shodmehr.
