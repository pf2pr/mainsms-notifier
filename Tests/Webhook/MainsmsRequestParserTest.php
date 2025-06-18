<?php

declare(strict_types = 1);

namespace Pf2Pr\Notifier\Bridge\Mainsms\Tests\Webhook;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Webhook\Client\RequestParserInterface;
use Symfony\Component\Webhook\Test\AbstractRequestParserTestCase;
use Pf2Pr\Notifier\Bridge\Mainsms\Webhook\MainsmsRequestParser;

class MainsmsRequestParserTest extends AbstractRequestParserTestCase
{
    protected function createRequestParser(): RequestParserInterface
    {
        return new MainsmsRequestParser();
    }

    protected function createRequest(string $payload): Request
    {
        parse_str(trim($payload), $parameters);

        return Request::create('/', 'GET', $parameters, [], [], ['REMOTE_ADDR' => '37.59.198.135']);
    }

    protected static function getFixtureExtension(): string
    {
        return 'txt';
    }
}