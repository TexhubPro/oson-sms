<?php

declare(strict_types=1);

namespace TexHub\OsonSms\Exceptions;

/**
 * Thrown on a network/transport-level failure (connection refused, timeout,
 * unreadable body) before a valid API response could be parsed.
 */
class TransportException extends OsonSmsException
{
}
