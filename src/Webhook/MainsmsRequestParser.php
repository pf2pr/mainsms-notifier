<?php

declare(strict_types=1);

namespace Pf2Pr\Notifier\Bridge\Mainsms\Webhook;

use SensitiveParameter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcher\MethodRequestMatcher;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\RemoteEvent\Event\Sms\SmsEvent;
use Symfony\Component\Webhook\Client\AbstractRequestParser;
use Symfony\Component\Webhook\Exception\RejectWebhookException;

final class MainsmsRequestParser extends AbstractRequestParser
{
    protected function getRequestMatcher(): RequestMatcherInterface
    {
        return new MethodRequestMatcher('GET');
    }

    protected function doParse(Request $request, #[SensitiveParameter] string $secret): ?SmsEvent
    {
        $payload = $request->query->all();

        if (
            !isset($payload['id'])
            || !isset($payload['status'])
            || !isset($payload['phone'])
        ) {
            throw new RejectWebhookException(406, 'Payload is malformed.');
        }

        $messageId      = (string) $payload['id'];
        $status         = $payload['status'];
        $recipientPhone = $payload['phone'];

        $eventName = match ($status) {
            'delivered' => SmsEvent::DELIVERED,
            'non-delivered', 'canceled' => SmsEvent::FAILED,
            'enqueued', 'accepted', 'scheduled', 'wait', 'read' => null,
            default => throw new RejectWebhookException(406, sprintf('Unsupported event "%s".', $status)),
        };

        if (!$eventName) {
            return null;
        }

        $event = new SmsEvent($eventName, $messageId, $payload);
        $event->setRecipientPhone($recipientPhone);

        return $event;
    }
}
