<?php

declare(strict_types=1);

namespace Pf2Pr\Notifier\Bridge\Mainsms\Tests;

use DateTimeImmutable;
use DateTimeZone;
use Pf2Pr\Notifier\Bridge\Mainsms\Enum\Strategy;
use Pf2Pr\Notifier\Bridge\Mainsms\MainsmsOptions;
use Pf2Pr\Notifier\Bridge\Mainsms\MainsmsTransport;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\Notifier\Exception\TransportException;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Notifier\Message\PushMessage;
use Symfony\Component\Notifier\Message\SmsMessage;
use Symfony\Component\Notifier\Test\TransportTestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class MainsmsTransportTest extends TransportTestCase
{
    public static function createTransport(?HttpClientInterface $client = null): MainSmsTransport
    {
        return new MainSmsTransport(
            'apikey',
            'projectName',
            'senderName',
            null,
            1.2,
            false,
            $client ?? new MockHttpClient()
        );
    }

    public static function toStringProvider(): iterable
    {
        yield ['mainsms://mainsms.ru?project=projectName&sender=senderName&timeout=1.2&test=false', self::createTransport()];
    }

    public static function supportedMessagesProvider(): iterable
    {
        yield [new SmsMessage('+79161234567', 'Hello!')];
    }

    public static function unsupportedMessagesProvider(): iterable
    {
        yield [new ChatMessage('Hello!')];
        yield [new PushMessage('Hello!', 'Hello!')];
    }

    public function testBasicSucceeded()
    {
        $message = new SmsMessage('+79161234567', 'Hello!');
        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->exactly(2))
            ->method('getStatusCode')
            ->willReturn(200);

        $response->expects($this->exactly(1))
            ->method('getContent')
            ->willReturn(json_encode([
                'status' => 'success',
                'messages_id' => ['12345678']
            ]));

        $client = new MockHttpClient(function (string $method, string $url, $request) use ($response): ResponseInterface {
            $this->assertSame('GET', $method);
            $this->assertSame('https://mainsms.ru/api/mainsms/message/send', strstr($url, '?', true));
            $this->assertEquals(
                [
                    'project' => 'projectName',
                    'sender' => 'senderName',
                    'recipients' => '79161234567',
                    'message' => 'Hello!',
                    'test' => 0,
                    'sign' => '84b1be1d0025fb7f400f9d457612052a',
                ],
                $request['query']
            );

            return $response;
        });

        $transport = $this->createTransport($client);
        $sentMessage = $transport->send($message);

        $this->assertSame('12345678', $sentMessage->getMessageId());
    }

    public function testMultiplyPhones()
    {
        $message = new SmsMessage('79161234567,79161234568,79161234569', 'Hello!');
        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->exactly(2))
            ->method('getStatusCode')
            ->willReturn(200);

        $response->expects($this->exactly(1))
            ->method('getContent')
            ->willReturn(json_encode([
                'status' => 'success',
                'messages_id' => ['1', '2', '3']
            ]));

        $client = new MockHttpClient(function (string $method, string $url, $request) use ($response): ResponseInterface {
            $this->assertSame('GET', $method);
            $this->assertSame('https://mainsms.ru/api/mainsms/message/send', strstr($url, '?', true));
            $this->assertEquals(
                [
                    'project' => 'projectName',
                    'sender' => 'senderName',
                    'recipients' => '79161234567,79161234568,79161234569',
                    'message' => 'Hello!',
                    'test' => 0,
                    'sign' => '146aad3a8112b89a32666ccf9cc4727d',
                ],
                $request['query']
            );

            return $response;
        });

        $transport = $this->createTransport($client);
        $sentMessage = $transport->send($message);

        $this->assertSame('1,2,3', $sentMessage->getMessageId());
    }

    public function testBasicFailed()
    {
        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('MainSMS API error "2": invalid signature or message encoding is not utf8');

        $message = new SmsMessage('+79161234567', 'Hello!');
        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->exactly(2))
            ->method('getStatusCode')
            ->willReturn(200);

        $response->expects($this->once())
            ->method('getContent')
            ->willReturn(json_encode([
                'status' => 'error',
                'error' => 2,
                'message' => 'invalid signature or message encoding is not utf8'
            ]));

        $client = new MockHttpClient(function (string $method, string $url, $request) use ($response): ResponseInterface {
            $this->assertSame('GET', $method);
            $this->assertSame('https://mainsms.ru/api/mainsms/message/send', strstr($url, '?', true));
            $this->assertEquals(
                [
                    'project' => 'projectName',
                    'sender' => 'senderName',
                    'recipients' => '79161234567',
                    'message' => 'Hello!',
                    'test' => 0,
                    'sign' => '84b1be1d0025fb7f400f9d457612052a',
                ],
                $request['query']);

            return $response;
        });

        $transport = $this->createTransport($client);
        $transport->send($message);
    }

    public function testSucceededWithOptions()
    {
        $message = new SmsMessage('+79161234567', 'Hello!');
        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->exactly(2))
            ->method('getStatusCode')
            ->willReturn(200);

        $response->expects($this->once())
            ->method('getContent')
            ->willReturn(json_encode([
                'status' => 'success',
                'messages_id' => ['12345678']
            ]));

        $client = new MockHttpClient(function (string $method, string $url, $request) use ($response): ResponseInterface {
            $this->assertSame('GET', $method);
            $this->assertSame('https://mainsms.ru/api/mainsms/message/send', strstr($url, '?', true));
            $this->assertEquals(
                [
                    'project' => 'custom-project',
                    'sender' => 'custom-sender',
                    'recipients' => '79161234567',
                    'message' => 'Hello!',
                    'viber' => 1,
                    'test' => 1,
                    'sign' => '82a987254b75e1103a55dd7469278395',
                ],
                $request['query']
            );

            return $response;
        });

        $transport = $this->createTransport($client);
        $options = (new MainsmsOptions())
            ->project('custom-project')
            ->sender('custom-sender')
            ->strategy(Strategy::Viber)
            ->test(true);

        $message->options($options);
        $sentMessage = $transport->send($message);

        $this->assertSame('12345678', $sentMessage->getMessageId());
    }

    public function testOptionsDateTime()
    {
        $dateTime = (new DateTimeImmutable('tomorrow'))
            ->setTime(12, 00)
            ->setTimezone(new DateTimeZone('UTC'));

        $message = new SmsMessage('+79161234567', 'Hello!');
        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->exactly(2))
            ->method('getStatusCode')
            ->willReturn(200);

        $response->expects($this->once())
            ->method('getContent')
            ->willReturn(json_encode([
                'status' => 'success',
                'messages_id' => ['12345678']
            ]));

        $client = new MockHttpClient(function (string $method, string $url, $request) use ($response, $dateTime): ResponseInterface {
            $this->assertSame('GET', $method);
            $this->assertSame('https://mainsms.ru/api/mainsms/message/send', strstr($url, '?', true));

            $expected = [
                'project' => 'projectName',
                'sender' => 'senderName',
                'recipients' => '79161234567',
                'message' => 'Hello!',
                'test' => 0,
                'run_at' => $dateTime->setTimezone(new DateTimeZone('Europe/Moscow'))->format('Y-m-d H:i'),
                'sign' => '21d5bdb66f77710a2874f3c200c9a08d',
            ];
            unset($expected['sign'], $request['query']['sign']);
            $this->assertEquals(
                $expected,
                $request['query']
            );

            return $response;
        });

        $transport = $this->createTransport($client);

        $options = (new MainsmsOptions())
            ->dateTime($dateTime);

        $message->options($options);
        $sentMessage = $transport->send($message);

        $this->assertSame('12345678', $sentMessage->getMessageId());
    }

    public function testOptionsInvalidDateTime()
    {
        $dateTime = (new DateTimeImmutable('yesterday'))
            ->setTime(12, 00)
            ->setTimezone(new DateTimeZone('UTC'));

        $response = $this->createMock(ResponseInterface::class);
        $client = new MockHttpClient(function () use ($response): ResponseInterface {
            return $response;
        });


        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("The given DateTime must be greater to the current date.");
        $message = new SmsMessage('+79161234567', 'Hello');

        $options = (new MainsmsOptions())
            ->sender('SENDER')
            ->dateTime($dateTime);

        $transport = $this->createTransport($client);

        $message->options($options);
        $transport->send($message);
    }

    public function testOptionsInvalidImage()
    {
        $response = $this->createMock(ResponseInterface::class);
        $client = new MockHttpClient(function () use ($response): ResponseInterface {
            return $response;
        });


        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("The image url \"test\" is not valid. It must start with http:// or https://.");
        $message = new SmsMessage('+79161234567', 'Hello');

        $options = (new MainsmsOptions())
            ->image('test');

        $transport = $this->createTransport($client);

        $message->options($options);
        $transport->send($message);
    }

    public function testOptionsImageWithoutButton()
    {
        $response = $this->createMock(ResponseInterface::class);
        $client = new MockHttpClient(function () use ($response): ResponseInterface {
            return $response;
        });


        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("If \"image\" is set, then \"button\" must also be set.");
        $message = new SmsMessage('+79161234567', 'Hello');

        $options = (new MainsmsOptions())
            ->image('http://example.com/image.png');

        $transport = $this->createTransport($client);

        $message->options($options);
        $transport->send($message);
    }

    public function testOptionsImageWithoutButtonUrl()
    {
        $response = $this->createMock(ResponseInterface::class);
        $client = new MockHttpClient(function () use ($response): ResponseInterface {
            return $response;
        });


        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("If \"image\" is set, then \"button_url\" must also be set.");
        $message = new SmsMessage('+79161234567', 'Hello');

        $options = (new MainsmsOptions())
            ->image('http://example.com/image.png')
            ->button('button text');

        $transport = $this->createTransport($client);

        $message->options($options);
        $transport->send($message);
    }

    public function testOptionsInvalidButton()
    {
        $response = $this->createMock(ResponseInterface::class);
        $client = new MockHttpClient(function () use ($response): ResponseInterface {
            return $response;
        });


        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Button text must be at most 20 characters long, got 36.");
        $message = new SmsMessage('+79161234567', 'Hello');

        $options = (new MainsmsOptions())
            ->button('button text toooooooooo looooooooong');

        $transport = $this->createTransport($client);

        $message->options($options);
        $transport->send($message);
    }
}