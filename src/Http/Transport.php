<?php

declare(strict_types=1);

namespace TexHub\OsonSms\Http;

use TexHub\OsonSms\Exceptions\TransportException;

/**
 * Minimal HTTP transport abstraction so the SDK has no hard dependency on a
 * specific HTTP client and can be fully unit-tested with a fake.
 */
interface Transport
{
    /**
     * Perform a GET request with a query string.
     *
     * @param string                $url     Base endpoint URL.
     * @param array<string, mixed>  $query   Query parameters.
     * @param array<string, string> $headers Request headers.
     *
     * @throws TransportException On connection/network failures.
     */
    public function get(string $url, array $query, array $headers = []): RawResponse;
}
