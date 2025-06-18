<?php

declare(strict_types=1);

namespace Pf2Pr\Notifier\Bridge\Mainsms;

use Pf2Pr\Notifier\Bridge\Mainsms\Enum\Strategy;
use Symfony\Component\Notifier\Exception\InvalidArgumentException;
use Symfony\Component\Notifier\Exception\TransportException;
use Symfony\Component\Notifier\Exception\UnsupportedMessageTypeException;
use Symfony\Component\Notifier\Message\MessageInterface;
use Symfony\Component\Notifier\Message\SentMessage;
use Symfony\Component\Notifier\Message\SmsMessage;
use Symfony\Component\Notifier\Transport\AbstractTransport;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

use function count;
use function is_array;
use function sprintf;

final class MainsmsTransport extends AbstractTransport
{
    protected const HOST = 'mainsms.ru';

    public function __construct(
        #[\SensitiveParameter]
        private string $apiKey,
        private string $project,
        private ?string $sender,
        private ?Strategy $strategy,
        private ?float $timeout,
        private bool $test = false,
        ?HttpClientInterface $client = null,
        ?EventDispatcherInterface $dispatcher = null,
    ) {
        parent::__construct($client, $dispatcher);
    }

    public function __toString(): string
    {
        $dsn = sprintf(
            'mainsms://%s?project=%s',
            $this->getEndpoint(),
            $this->project
        );

        if (null !== $this->sender) {
            $dsn .= '&sender=' . $this->sender;
        }

        if (null !== $this->strategy && Strategy::Sms !== $this->strategy) {
            $dsn .= '&strategy=' . $this->strategy->value;
        }

        if (null !== $this->timeout) {
            $dsn .= '&timeout=' . $this->timeout;
        }

        $dsn .= '&test=' . ($this->test ? 'true' : 'false');

        return $dsn;
    }

    public function supports(MessageInterface $message): bool
    {
        return $message instanceof SmsMessage && (null === $message->getOptions() || $message->getOptions() instanceof MainsmsOptions);
    }

    protected function doSend(MessageInterface $message): SentMessage
    {
        if (!$message instanceof SmsMessage) {
            throw new UnsupportedMessageTypeException(__CLASS__, SmsMessage::class, $message);
        }

        // clean phones
        $phones = array_map(
            static fn (string $raw) => preg_replace('/\D+/', '', $raw),
            array_filter(array_map('trim', explode(',', $message->getPhone())))
        );

        if (count($phones) > 100) {
            throw new InvalidArgumentException('Too many phone numbers. Limit is 100.');
        }

        $recipients = implode(',', $phones);

        // get all options
        $options = $message->getOptions()?->toArray() ?? [];

        // validate options
        $this->validateInteractiveOptions($options);

        // build query
        $query = [
            'project'    => $options['project'] ?? $this->project,
            'sender'     => $options['sender'] ?? $this->sender,
            'recipients' => $recipients,
            'message'    => $message->getSubject(),
            'test'       => isset($options['test']) ? (int) $options['test'] : (int) $this->test,
        ];

        $strategy = $options['strategy'] ?? $this->strategy;
        if (null !== $strategy && Strategy::Sms !== $this->strategy) {
            $query['viber'] = $strategy->value;
        }

        // extra params
        foreach (['image', 'button', 'button_url', 'viber_text', 'run_at'] as $key) {
            if (isset($options[$key])) {
                $query[$key] = $options[$key];
            }
        }

        // sign request
        $query['sign'] = $this->generateSign($query);

        try {
            $response = $this->client->request('GET', sprintf('https://%s/api/mainsms/message/send', $this->getEndpoint()), [
                'timeout' => $this->timeout,
                'headers' => ['Accept' => 'application/json'],
                'query'   => $query,
            ]);

            $statusCode = $response->getStatusCode();
            if (200 !== $statusCode) {
                throw new TransportException(sprintf('Unexpected HTTP status: %d', $statusCode), $response);
            }

            $data = $response->toArray(false);

            if (($data['status'] ?? null) !== 'success') {
                $code = $data['error'] ?? 0;
                $msg  = $data['message'] ?? 'Unknown error';

                throw new TransportException(sprintf('MainSMS API error "%s": %s.', $code, $msg), $response);
            }

            if (empty($data['messages_id']) || !is_array($data['messages_id'])) {
                throw new TransportException('MainSMS responded with success, but no message ID was returned.', $response);
            }

            $sentMessage = new SentMessage($message, (string) $this);
            $sentMessage->setMessageId(implode(',', array_map('strval', $data['messages_id'])));

            return $sentMessage;
        } catch (TransportExceptionInterface $e) {
            throw new TransportException('Could not reach the MainSMS server.', $response ?? null, previous: $e);
        }
    }

    /**
     * @param mixed[] $request
     */
    private function generateSign(array $request): string
    {
        ksort($request);

        return md5(sha1(implode(';', array_merge($request, ['apikey' => $this->apiKey]))));
    }

    /**
     * @param mixed[] $options
     */
    private function validateInteractiveOptions(array $options): void
    {
        $required = [
            'image'      => ['button', 'button_url'],
            'button'     => ['image', 'button_url'],
            'button_url' => ['image', 'button'],
        ];

        foreach ($required as $key => $dependsOn) {
            if (isset($options[$key])) {
                foreach ($dependsOn as $requiredKey) {
                    if (!isset($options[$requiredKey])) {
                        throw new InvalidArgumentException(sprintf(
                            'If "%s" is set, then "%s" must also be set.',
                            $key,
                            $requiredKey
                        ));
                    }
                }
            }
        }
    }
}
