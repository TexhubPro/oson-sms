<?php

declare(strict_types=1);

namespace TexHub\OsonSms;

use TexHub\OsonSms\Exceptions\ConfigurationException;

/**
 * Immutable SDK configuration for OsonSMS.
 */
final class Config
{
    public const DEFAULT_SERVER = 'https://api.osonsms.com/sendsms_v1.php';

    /**
     * @param string      $login      Account login (the `login` parameter).
     * @param string      $token      Bearer token used in the Authorization header.
     * @param string      $sender     Default sender name / number (the `from` parameter).
     * @param string      $server     API endpoint URL.
     * @param string|null $hashSecret Optional API hash secret. When set, the client
     *                                adds a SHA-256 `str_hash` signature to requests.
     * @param int         $timeout    HTTP timeout in seconds.
     */
    public function __construct(
        public readonly string $login,
        public readonly string $token,
        public readonly string $sender,
        public readonly string $server = self::DEFAULT_SERVER,
        public readonly ?string $hashSecret = null,
        public readonly int $timeout = 30,
    ) {
        if (trim($this->login) === '') {
            throw new ConfigurationException('OsonSMS login must not be empty.');
        }

        if (trim($this->token) === '') {
            throw new ConfigurationException('OsonSMS token must not be empty.');
        }

        if (trim($this->sender) === '') {
            throw new ConfigurationException('OsonSMS sender (from) must not be empty.');
        }

        if (trim($this->server) === '') {
            throw new ConfigurationException('OsonSMS server URL must not be empty.');
        }

        if ($this->timeout < 1) {
            throw new ConfigurationException('OsonSMS timeout must be a positive number of seconds.');
        }
    }

    /**
     * Build configuration from a plain array (e.g. a Laravel config entry).
     *
     * @param array<string, mixed> $config
     */
    public static function fromArray(array $config): self
    {
        return new self(
            login: (string) ($config['login'] ?? ''),
            token: (string) ($config['token'] ?? ''),
            sender: (string) ($config['sender'] ?? ''),
            server: (string) ($config['server'] ?? self::DEFAULT_SERVER),
            hashSecret: isset($config['hash_secret']) && $config['hash_secret'] !== ''
                ? (string) $config['hash_secret']
                : null,
            timeout: (int) ($config['timeout'] ?? 30),
        );
    }

    public function usesSignature(): bool
    {
        return $this->hashSecret !== null;
    }
}
