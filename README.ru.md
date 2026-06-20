# TexHub · OsonSMS

[English](README.md) · **Русский**

[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/php-%5E8.2-777bb4.svg)](composer.json)
[![Laravel](https://img.shields.io/badge/laravel-11%20%7C%2012%20%7C%2013-ff2d20.svg)](#laravel)

Чистый, не привязанный к фреймворку PHP SDK для шлюза **OsonSMS** — отправка одиночных и массовых SMS — с полной поддержкой **Laravel**.

> Работает в чистом PHP и любом фреймворке. Для Laravel — авто-дискавери, конфиг и фасад из коробки.

---

## Возможности

- **Отправка SMS** через текучий билдер сообщений или вызов в одну строку
- **Массовая отправка**, которая не бросает исключений — у каждого результата свой успех/ошибка
- **Авторизация по Bearer-токену** + опциональная подпись **SHA-256 `str_hash`**
- **Подменяемый HTTP-транспорт** — cURL по умолчанию; можно внедрить свой для тестов
- **Типизированные ответы и исключения** (`ApiException` с code / message / error_type)
- **Полностью покрыт тестами**, без обращения к сети

---

## Установка

```bash
composer require texhub/oson-sms
```

Требования: **PHP ≥ 8.2** с расширениями `curl`, `json` и `hash`.

---

## Быстрый старт (чистый PHP)

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

### Текучий билдер

```php
use TexHub\OsonSms\Requests\SmsMessage;

$oson->send(
    SmsMessage::make('992900123456', 'Ваш код: 1234')
        ->from('MyShop')        // переопределить отправителя по умолчанию
        ->txnId('order-42')     // ключ идемпотентности (иначе генерируется сам)
);
```

### Массовая отправка (толерантная — не бросает исключений)

```php
$results = $oson->sms()->sendMany([
    SmsMessage::make('992900000001', 'Сообщение 1'),
    SmsMessage::make('992900000002', 'Сообщение 2'),
]);

foreach ($results as $r) {
    if ($r['ok']) {
        echo "отправлено: {$r['response']->messageId}\n";
    } else {
        echo "ошибка: {$r['error']->getMessage()}\n";
    }
}
```

---

## Аутентификация

По умолчанию SDK авторизуется через **Bearer-токен** в заголовке `Authorization` (как в примере OsonSMS), отправляя параметры:

| Параметр | Источник |
|----------------|-----------------------------------|
| `from` | отправитель (конфиг или сообщение) |
| `phone_number` | получатель |
| `msg` | текст |
| `txn_id` | ключ идемпотентности (авто) |
| `login` | login из конфига |

### Опциональная подпись `str_hash`

Если ваш аккаунт требует подписанный запрос, задайте секрет — SDK добавит параметр `str_hash`:

```
str_hash = SHA256( txn_id ; login ; sender ; phone_number ; hash_secret )
```

```php
$oson = OsonSms::fromArray([
    'login' => '...', 'token' => '...', 'sender' => '...',
    'hash_secret' => 'YOUR_API_HASH',
]);
```

> Если у вашего терминала другой порядок полей — вычислите сами через `new Signature($secret)` `->hash($yourString)`.

---

## Обработка ошибок

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
    // сетевая ошибка
} catch (OsonSmsException $e) {
    // базовый тип — также покрывает некорректные ответы
}
```

API сигнализирует об ошибках JSON-телом: `{ "error": { "code": ..., "msg": "...", "error_type": "..." } }`.

---

## <a name="laravel"></a> Laravel

Сервис-провайдер и фасад `OsonSms` **регистрируются автоматически**. Опубликуйте конфиг:

```bash
php artisan vendor:publish --tag=oson-sms-config
```

Добавьте учётные данные в `.env`:

```dotenv
OSON_SMS_LOGIN=your_login
OSON_SMS_TOKEN=your_token
OSON_SMS_SENDER=YourSender
# опционально:
OSON_SMS_HASH_SECRET=
OSON_SMS_SERVER=https://api.osonsms.com/sendsms_v1.php
OSON_SMS_TIMEOUT=30
```

Используйте фасад:

```php
use TexHub\OsonSms\Laravel\OsonSms;
use TexHub\OsonSms\Requests\SmsMessage;

OsonSms::send('992900123456', 'Привет из Laravel!');

OsonSms::send(
    SmsMessage::make('992900123456', 'Ваш код: 1234')->txnId('order-'.$order->id)
);
```

…либо внедрите через DI:

```php
public function notify(\TexHub\OsonSms\OsonSms $oson) { /* ... */ }
```

---

## Тестирование

Подставьте фейковый транспорт, чтобы тестировать без обращения к сети:

```php
use TexHub\OsonSms\OsonSms;
use TexHub\OsonSms\Config;
use TexHub\OsonSms\Tests\Support\FakeTransport;

$transport = (new FakeTransport())->willReturnJson(['msg_id' => '1', 'txn_id' => 'a']);
$oson = new OsonSms(new Config('login', 'token', 'Sender'), $transport);

$oson->send('992900123456', 'Hi');
// проверяйте $transport->lastQuery / lastHeaders / lastUrl
```

Запуск тестов пакета:

```bash
composer install
composer test          # или: vendor/bin/phpunit
```

---

## Архитектура

```
src/
├── OsonSms.php              # точка входа — sms() / send()
├── Config.php               # неизменяемая конфигурация
├── Signature.php            # опциональная подпись SHA-256 str_hash
├── Http/                    # интерфейс Transport, CurlTransport, Response
├── Requests/SmsMessage.php  # текучий билдер сообщения
├── Clients/SmsClient.php    # send() / sendMany()
├── Exceptions/              # ApiException, TransportException, …
└── Laravel/                 # ServiceProvider + Facade
```

---

## Лицензия

MIT © TexHub Pro — разработано Mahmudi Shodmehr.
