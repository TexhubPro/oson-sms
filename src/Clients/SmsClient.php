<?php

declare(strict_types=1);

namespace TexHub\OsonSms\Clients;

use TexHub\OsonSms\Config;
use TexHub\OsonSms\Exceptions\ApiException;
use TexHub\OsonSms\Exceptions\OsonSmsException;
use TexHub\OsonSms\Http\Response;
use TexHub\OsonSms\Http\Transport;
use TexHub\OsonSms\Requests\SmsMessage;
use TexHub\OsonSms\Signature;

/**
 * Sends SMS through the OsonSMS gateway.
 */
final class SmsClient
{
    public function __construct(
        private readonly Config $config,
        private readonly Transport $transport,
        private readonly ?Signature $signature = null,
    ) {
    }

    /**
     * Send a single SMS.
     *
     * @param SmsMessage|string $message A built message, or the phone number
     *                                   (with $text as the second argument).
     *
     * @throws ApiException     On an API-level error.
     * @throws OsonSmsException On invalid responses / transport failures.
     */
    public function send(SmsMessage|string $message, ?string $text = null): Response
    {
        if (is_string($message)) {
            $message = SmsMessage::make($message, (string) $text);
        }

        $sender = $message->getSender() ?? $this->config->sender;
        $txnId = $message->getTxnId() ?? $this->generateTxnId();
        $phone = $message->getPhoneNumber();

        $params = [
            'from' => $sender,
            'phone_number' => $phone,
            'msg' => $message->getText(),
            'txn_id' => $txnId,
            'login' => $this->config->login,
        ];

        if ($this->signature !== null) {
            $params['str_hash'] = $this->signature->make($txnId, $this->config->login, $sender, $phone);
        }

        $headers = ['Authorization' => 'Bearer ' . $this->config->token];

        $raw = $this->transport->get($this->config->server, $params, $headers);

        $decoded = json_decode($raw->body, true);

        if (! is_array($decoded)) {
            throw new OsonSmsException('Invalid (non-JSON) response from OsonSMS: ' . substr($raw->body, 0, 200));
        }

        if (isset($decoded['error']) && is_array($decoded['error'])) {
            throw new ApiException(
                errorCode: $decoded['error']['code'] ?? 'unknown',
                apiMessage: (string) ($decoded['error']['msg'] ?? 'Unknown error'),
                errorType: isset($decoded['error']['error_type']) ? (string) $decoded['error']['error_type'] : null,
                httpStatus: $raw->statusCode,
                payload: $decoded,
            );
        }

        if ($raw->statusCode >= 400) {
            throw new ApiException(
                errorCode: $raw->statusCode,
                apiMessage: 'HTTP error from OsonSMS',
                httpStatus: $raw->statusCode,
                payload: $decoded,
            );
        }

        return Response::fromArray($decoded);
    }

    /**
     * Send many messages. Tolerant: never throws — each result carries either a
     * Response or the ApiException that occurred.
     *
     * @param iterable<SmsMessage> $messages
     *
     * @return array<int, array{message: SmsMessage, ok: bool, response: ?Response, error: ?OsonSmsException}>
     */
    public function sendMany(iterable $messages): array
    {
        $results = [];

        foreach ($messages as $message) {
            try {
                $results[] = [
                    'message' => $message,
                    'ok' => true,
                    'response' => $this->send($message),
                    'error' => null,
                ];
            } catch (OsonSmsException $e) {
                $results[] = [
                    'message' => $message,
                    'ok' => false,
                    'response' => null,
                    'error' => $e,
                ];
            }
        }

        return $results;
    }

    private function generateTxnId(): string
    {
        return uniqid('sms-', true);
    }
}
