<?php

declare(strict_types=1);

namespace TexHub\OsonSms;

/**
 * Optional SHA-256 request signature (`str_hash`) for OsonSMS accounts that
 * require it.
 *
 * The signing string follows the OsonSMS scheme:
 *
 *     str_hash = SHA256( txn_id ; login ; sender ; phone_number ; hash_secret )
 *
 * Signing is only applied when a `hash_secret` is configured. If your account
 * uses a different field order, pass a custom signing string to {@see hash()}.
 */
final class Signature
{
    public function __construct(
        private readonly string $hashSecret,
    ) {
    }

    /**
     * Build the OsonSMS signing string and hash it.
     */
    public function make(string $txnId, string $login, string $sender, string $phoneNumber): string
    {
        return $this->hash(self::concat($txnId, $login, $sender, $phoneNumber, $this->hashSecret));
    }

    /**
     * Hash an arbitrary signing string with SHA-256.
     */
    public function hash(string $dataToSign): string
    {
        return hash('sha256', $dataToSign);
    }

    public static function concat(string ...$parts): string
    {
        return implode(';', $parts);
    }
}
