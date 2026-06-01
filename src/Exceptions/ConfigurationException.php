<?php

declare(strict_types=1);

namespace TexHub\OsonSms\Exceptions;

/**
 * Thrown when the SDK is misconfigured (missing login/token/sender, etc.).
 */
class ConfigurationException extends OsonSmsException
{
}
