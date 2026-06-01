<?php

declare(strict_types=1);

namespace TexHub\OsonSms\Http;

/**
 * A decoded, successful OsonSMS response.
 *
 * On success the API returns at least a message id and the transaction id;
 * additional fields (status, smsc, …) are kept in {@see $raw}.
 */
final class Response
{
    /**
     * @param array<string, mixed> $raw
     */
    public function __construct(
        public readonly ?string $messageId,
        public readonly ?string $txnId,
        public readonly ?string $status,
        public readonly array $raw = [],
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            messageId: isset($data['msg_id']) ? (string) $data['msg_id'] : null,
            txnId: isset($data['txn_id']) ? (string) $data['txn_id'] : null,
            status: isset($data['status']) ? (string) $data['status'] : null,
            raw: $data,
        );
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->raw[$key] ?? $default;
    }
}
