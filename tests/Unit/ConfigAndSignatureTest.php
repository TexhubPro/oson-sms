<?php

declare(strict_types=1);

namespace TexHub\OsonSms\Tests\Unit;

use PHPUnit\Framework\TestCase;
use TexHub\OsonSms\Config;
use TexHub\OsonSms\Exceptions\ConfigurationException;
use TexHub\OsonSms\Requests\SmsMessage;
use TexHub\OsonSms\Signature;

final class ConfigAndSignatureTest extends TestCase
{
    public function test_config_requires_credentials(): void
    {
        $this->expectException(ConfigurationException::class);
        new Config(login: '', token: 't', sender: 's');
    }

    public function test_config_from_array_and_defaults(): void
    {
        $config = Config::fromArray([
            'login' => 'l',
            'token' => 't',
            'sender' => 's',
        ]);

        $this->assertSame(Config::DEFAULT_SERVER, $config->server);
        $this->assertSame(30, $config->timeout);
        $this->assertFalse($config->usesSignature());
    }

    public function test_config_detects_signature(): void
    {
        $config = Config::fromArray([
            'login' => 'l', 'token' => 't', 'sender' => 's', 'hash_secret' => 'x',
        ]);

        $this->assertTrue($config->usesSignature());
    }

    public function test_signature_is_sha256_of_concatenation(): void
    {
        $sig = new Signature('secret');

        $expected = hash('sha256', 'txn1;login;Sender;992900123456;secret');
        $this->assertSame($expected, $sig->make('txn1', 'login', 'Sender', '992900123456'));
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $sig->make('a', 'b', 'c', 'd'));
    }

    public function test_sms_message_validates_input(): void
    {
        $this->expectException(ConfigurationException::class);
        SmsMessage::make('', 'text');
    }
}
