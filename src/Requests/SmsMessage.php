<?php

declare(strict_types=1);

namespace TexHub\OsonSms\Requests;

use TexHub\OsonSms\Exceptions\ConfigurationException;

/**
 * Fluent builder for a single SMS.
 *
 * `from` (sender) and `login` default to the configured values and are
 * injected by the client; `txn_id` is generated automatically if not set.
 */
final class SmsMessage
{
    private ?string $txnId = null;
    private ?string $sender = null;

    public function __construct(
        private readonly string $phoneNumber,
        private readonly string $text,
    ) {
        if (trim($phoneNumber) === '') {
            throw new ConfigurationException('SMS phone number is required.');
        }

        if (trim($text) === '') {
            throw new ConfigurationException('SMS message text is required.');
        }
    }

    public static function make(string $phoneNumber, string $text): self
    {
        return new self($phoneNumber, $text);
    }

    /**
     * Set a custom transaction id (idempotency key). Generated automatically
     * when omitted.
     */
    public function txnId(string $txnId): self
    {
        $this->txnId = $txnId;

        return $this;
    }

    /**
     * Override the default sender for this message.
     */
    public function from(string $sender): self
    {
        $this->sender = $sender;

        return $this;
    }

    public function getPhoneNumber(): string
    {
        return $this->phoneNumber;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function getTxnId(): ?string
    {
        return $this->txnId;
    }

    public function getSender(): ?string
    {
        return $this->sender;
    }
}
