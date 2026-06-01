<?php

declare(strict_types=1);

namespace TexHub\OsonSms\Exceptions;

/**
 * Thrown when OsonSMS returns an error object: `{ "error": { code, msg, error_type } }`.
 */
class ApiException extends OsonSmsException
{
    /**
     * @param int|string           $errorCode  The API `error.code` value.
     * @param string               $apiMessage The API `error.msg` text.
     * @param string|null          $errorType  The API `error.error_type` value.
     * @param int                  $httpStatus HTTP status code of the response.
     * @param array<string, mixed> $payload    The full decoded response body.
     */
    public function __construct(
        public readonly int|string $errorCode,
        public readonly string $apiMessage,
        public readonly ?string $errorType = null,
        public readonly int $httpStatus = 0,
        public readonly array $payload = [],
    ) {
        parent::__construct(
            sprintf('OsonSMS API error [%s]: %s', (string) $errorCode, $apiMessage),
            is_int($errorCode) ? $errorCode : 0,
        );
    }
}
