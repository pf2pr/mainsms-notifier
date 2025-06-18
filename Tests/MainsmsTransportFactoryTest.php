<?php

declare(strict_types=1);

namespace Pf2Pr\Notifier\Bridge\Mainsms\Tests;

use Pf2Pr\Notifier\Bridge\Mainsms\MainsmsTransportFactory;
use Symfony\Component\Notifier\Test\TransportFactoryTestCase;

final class MainsmsTransportFactoryTest extends TransportFactoryTestCase
{
    public function createFactory(): MainsmsTransportFactory
    {
        return new MainsmsTransportFactory();
    }

    public static function createProvider(): iterable
    {
        yield [
            'mainsms://host.test?project=test-project&test=false',
            'mainsms://apiKey@host.test?project=test-project',
        ];
        yield [
            'mainsms://mainsms.ru?project=test-project&test=false',
            'mainsms://apiKey@default?project=test-project',
        ];
        yield [
            'mainsms://mainsms.ru?project=test-project&strategy=2&test=true',
            'mainsms://apiKey@default?project=test-project&test=true&strategy=2',
        ];
        yield [
            'mainsms://mainsms.ru?project=test-project&strategy=2&timeout=1.11&test=true',
            'mainsms://apiKey@default?project=test-project&test=true&strategy=2&timeout=1.11',
        ];        yield [
        'mainsms://mainsms.ru?project=test-project&sender=test&strategy=2&timeout=1.11&test=true',
        'mainsms://apiKey@default?project=test-project&test=true&strategy=2&timeout=1.11&sender=test',
    ];
    }

    public static function supportsProvider(): iterable
    {
        yield [true, 'mainsms://apiKey@default?project=test'];
        yield [false, 'somethingElse://apiKey:apiSecret@default?from=0611223344'];
    }

    public static function missingRequiredOptionProvider(): iterable
    {
        yield 'missing option: project' => ['mainsms://apiKey@default'];
    }

    public static function unsupportedSchemeProvider(): iterable
    {
        yield ['somethingElse://apiKey:apiSecret@default?from=0611223344'];
        yield ['somethingElse://apiKey:apiSecret@default'];
    }

    public static function incompleteDsnProvider(): iterable
    {
        yield ['mainsms://default?project=test-project'];
        yield ['mainsms://apikey@default'];
    }
}