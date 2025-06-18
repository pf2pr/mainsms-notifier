<?php

declare(strict_types=1);

namespace Pf2Pr\Notifier\Bridge\Mainsms;

use Pf2Pr\Notifier\Bridge\Mainsms\Enum\Strategy;
use Symfony\Component\Notifier\Exception\InvalidArgumentException;
use Symfony\Component\Notifier\Exception\UnsupportedSchemeException;
use Symfony\Component\Notifier\Transport\AbstractTransportFactory;
use Symfony\Component\Notifier\Transport\Dsn;
use ValueError;

final class MainsmsTransportFactory extends AbstractTransportFactory
{
    public function create(Dsn $dsn): MainsmsTransport
    {
        $scheme = $dsn->getScheme();

        if ('mainsms' !== $scheme) {
            throw new UnsupportedSchemeException($dsn, 'mainsms', $this->getSupportedSchemes());
        }

        $apiKey  = $this->getUser($dsn);
        $project = $dsn->getRequiredOption('project');
        $sender  = $dsn->getOption('sender');

        $strategy        = null;
        $strategyOptions = $dsn->getOption('strategy');
        if (null !== $strategyOptions) {
            try {
                $strategy = Strategy::from((int) $strategyOptions);
            } catch (ValueError $e) {
                throw new InvalidArgumentException(sprintf('Invalid strategy: "%s"', $strategyOptions), 0, $e);
            }
        }

        $test = filter_var($dsn->getOption('test', false), FILTER_VALIDATE_BOOL);

        $timeout = $dsn->getOption('timeout');
        $timeout = is_numeric($timeout) ? (float) $timeout : null;

        $host = 'default' === $dsn->getHost() ? null : $dsn->getHost();
        $port = $dsn->getPort();

        return (new MainsmsTransport($apiKey, $project, $sender, $strategy, $timeout, $test, $this->client, $this->dispatcher))
            ->setHost($host)
            ->setPort($port)
        ;
    }

    /**
     * @return string[]
     */
    protected function getSupportedSchemes(): array
    {
        return ['mainsms'];
    }
}
