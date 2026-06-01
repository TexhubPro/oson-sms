<?php

declare(strict_types=1);

namespace TexHub\OsonSms\Tests\Support;

use TexHub\OsonSms\Http\RawResponse;
use TexHub\OsonSms\Http\Transport;

/**
 * In-memory transport for tests: records the last request and returns a
 * queued/canned response without touching the network.
 */
final class FakeTransport implements Transport
{
    public ?string $lastUrl = null;

    /** @var array<string, mixed>|null */
    public ?array $lastQuery = null;

    /** @var array<string, string> */
    public array $lastHeaders = [];

    public int $calls = 0;

    public function __construct(
        private int $statusCode = 200,
        private string $body = '{"status":"ok","txn_id":"sms-1","msg_id":"123456"}',
    ) {
    }

    public function willReturn(int $statusCode, string $body): self
    {
        $this->statusCode = $statusCode;
        $this->body = $body;

        return $this;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function willReturnJson(array $payload, int $statusCode = 200): self
    {
        return $this->willReturn($statusCode, (string) json_encode($payload));
    }

    public function get(string $url, array $query, array $headers = []): RawResponse
    {
        $this->calls++;
        $this->lastUrl = $url;
        $this->lastQuery = $query;
        $this->lastHeaders = $headers;

        return new RawResponse($this->statusCode, $this->body);
    }
}
