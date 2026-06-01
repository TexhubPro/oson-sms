<?php

declare(strict_types=1);

namespace TexHub\OsonSms;

use TexHub\OsonSms\Clients\SmsClient;
use TexHub\OsonSms\Http\CurlTransport;
use TexHub\OsonSms\Http\Response;
use TexHub\OsonSms\Http\Transport;
use TexHub\OsonSms\Requests\SmsMessage;

/**
 * Entry point of the OsonSMS SDK.
 *
 * Framework-agnostic: construct it directly, or resolve it from the container
 * in Laravel via the {@see \TexHub\OsonSms\Laravel\OsonSms} facade.
 *
 * ```php
 * $oson = OsonSms::make('login', 'token', 'MyCompany');
 * $oson->send('992900123456', 'Hello!');
 * ```
 */
final class OsonSms
{
    private readonly Transport $transport;
    private readonly ?Signature $signature;
    private ?SmsClient $sms = null;

    public function __construct(
        private readonly Config $config,
        ?Transport $transport = null,
    ) {
        $this->transport = $transport ?? new CurlTransport($config->timeout);
        $this->signature = $config->usesSignature() ? new Signature((string) $config->hashSecret) : null;
    }

    /**
     * Convenience constructor.
     */
    public static function make(
        string $login,
        string $token,
        string $sender,
        string $server = Config::DEFAULT_SERVER,
        ?Transport $transport = null,
    ): self {
        return new self(new Config($login, $token, $sender, $server), $transport);
    }

    /**
     * Build from a config array (login, token, sender, server, hash_secret, …).
     *
     * @param array<string, mixed> $config
     */
    public static function fromArray(array $config, ?Transport $transport = null): self
    {
        return new self(Config::fromArray($config), $transport);
    }

    public function config(): Config
    {
        return $this->config;
    }

    public function sms(): SmsClient
    {
        return $this->sms ??= new SmsClient($this->config, $this->transport, $this->signature);
    }

    /**
     * Shortcut for {@see SmsClient::send()}.
     */
    public function send(SmsMessage|string $message, ?string $text = null): Response
    {
        return $this->sms()->send($message, $text);
    }
}
