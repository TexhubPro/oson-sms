<?php

declare(strict_types=1);

namespace TexHub\OsonSms\Http;

use TexHub\OsonSms\Exceptions\TransportException;

/**
 * Default {@see Transport} implementation built on the cURL extension.
 */
final class CurlTransport implements Transport
{
    public function __construct(
        private readonly int $timeout = 30,
        private readonly string $userAgent = 'texhub-oson-sms/1.0 (+https://texhub.pro)',
    ) {
    }

    public function get(string $url, array $query, array $headers = []): RawResponse
    {
        $fullUrl = $url . (str_contains($url, '?') ? '&' : '?') . http_build_query($query);

        $headerLines = [];
        foreach ($headers as $name => $value) {
            $headerLines[] = $name . ': ' . $value;
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $fullUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => $this->timeout,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => $headerLines,
            CURLOPT_USERAGENT => $this->userAgent,
        ]);

        $response = curl_exec($ch);
        $errorNo = curl_errno($ch);
        $error = curl_error($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($errorNo !== 0 || $response === false) {
            throw new TransportException(sprintf('OsonSMS request to %s failed: %s', $url, $error ?: 'unknown cURL error'));
        }

        return new RawResponse($statusCode, (string) $response);
    }
}
