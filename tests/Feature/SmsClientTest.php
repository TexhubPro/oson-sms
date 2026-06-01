<?php

declare(strict_types=1);

namespace TexHub\OsonSms\Tests\Feature;

use PHPUnit\Framework\TestCase;
use TexHub\OsonSms\Config;
use TexHub\OsonSms\Exceptions\ApiException;
use TexHub\OsonSms\Exceptions\OsonSmsException;
use TexHub\OsonSms\OsonSms;
use TexHub\OsonSms\Requests\SmsMessage;
use TexHub\OsonSms\Signature;
use TexHub\OsonSms\Tests\Support\FakeTransport;

final class SmsClientTest extends TestCase
{
    private function oson(\TexHub\OsonSms\Http\Transport $transport, ?string $hashSecret = null): OsonSms
    {
        return new OsonSms(
            new Config(login: 'mylogin', token: 'mytoken', sender: 'TexHub', hashSecret: $hashSecret),
            $transport,
        );
    }

    public function test_send_builds_request_with_bearer_token_and_params(): void
    {
        $transport = new FakeTransport();

        $response = $this->oson($transport)->send(
            SmsMessage::make('992900123456', 'Hello!')->txnId('txn-1')
        );

        $this->assertSame('https://api.osonsms.com/sendsms_v1.php', $transport->lastUrl);
        $this->assertSame('Bearer mytoken', $transport->lastHeaders['Authorization']);

        $this->assertSame('TexHub', $transport->lastQuery['from']);
        $this->assertSame('992900123456', $transport->lastQuery['phone_number']);
        $this->assertSame('Hello!', $transport->lastQuery['msg']);
        $this->assertSame('txn-1', $transport->lastQuery['txn_id']);
        $this->assertSame('mylogin', $transport->lastQuery['login']);
        $this->assertArrayNotHasKey('str_hash', $transport->lastQuery);

        $this->assertSame('123456', $response->messageId);
        $this->assertSame('sms-1', $response->txnId);
    }

    public function test_string_shortcut_and_auto_txn_id(): void
    {
        $transport = new FakeTransport();

        $this->oson($transport)->send('992900123456', 'Hi');

        $this->assertSame('Hi', $transport->lastQuery['msg']);
        $this->assertNotEmpty($transport->lastQuery['txn_id']); // auto-generated
    }

    public function test_per_message_sender_override(): void
    {
        $transport = new FakeTransport();

        $this->oson($transport)->send(
            SmsMessage::make('992900123456', 'Hi')->from('OtherName')->txnId('t1')
        );

        $this->assertSame('OtherName', $transport->lastQuery['from']);
    }

    public function test_str_hash_added_when_secret_configured(): void
    {
        $transport = new FakeTransport();

        $this->oson($transport, hashSecret: 'topsecret')->send(
            SmsMessage::make('992900123456', 'Hi')->txnId('t1')
        );

        $expected = (new Signature('topsecret'))->make('t1', 'mylogin', 'TexHub', '992900123456');
        $this->assertSame($expected, $transport->lastQuery['str_hash']);
    }

    public function test_api_error_throws_api_exception(): void
    {
        $transport = (new FakeTransport())->willReturnJson([
            'error' => ['code' => 103, 'msg' => 'Invalid login', 'error_type' => 'auth'],
        ]);

        try {
            $this->oson($transport)->send('992900123456', 'Hi');
            $this->fail('Expected ApiException');
        } catch (ApiException $e) {
            $this->assertSame(103, $e->errorCode);
            $this->assertSame('Invalid login', $e->apiMessage);
            $this->assertSame('auth', $e->errorType);
        }
    }

    public function test_invalid_json_throws(): void
    {
        $transport = (new FakeTransport())->willReturn(200, '<html>oops</html>');

        $this->expectException(OsonSmsException::class);
        $this->oson($transport)->send('992900123456', 'Hi');
    }

    public function test_send_many_is_tolerant(): void
    {
        // First OK, second errors.
        $transport = new class implements \TexHub\OsonSms\Http\Transport {
            private int $n = 0;
            public function get(string $url, array $query, array $headers = []): \TexHub\OsonSms\Http\RawResponse
            {
                $this->n++;
                $body = $this->n === 1
                    ? '{"msg_id":"1","txn_id":"a"}'
                    : '{"error":{"code":105,"msg":"No money","error_type":"balance"}}';

                return new \TexHub\OsonSms\Http\RawResponse(200, $body);
            }
        };

        $results = $this->oson($transport)->sms()->sendMany([
            SmsMessage::make('992900000001', 'A')->txnId('a'),
            SmsMessage::make('992900000002', 'B')->txnId('b'),
        ]);

        $this->assertTrue($results[0]['ok']);
        $this->assertSame('1', $results[0]['response']->messageId);
        $this->assertFalse($results[1]['ok']);
        $this->assertInstanceOf(ApiException::class, $results[1]['error']);
    }
}
