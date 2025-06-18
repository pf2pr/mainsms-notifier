# Mainsms Notifier

Provides [Mainsms](https://mainsms.ru) integration for Symfony Notifier.

## Installation

```bash
composer require pf2pr/mainsms-notifier
```

## Configuration

### Register the transport factory

```yaml
# config/services.yaml
Pf2Pr\Notifier\Bridge\Mainsms\MainsmsTransportFactory:
    tags: ['texter.transport_factory']
```

### Configure the transport

```yaml
# config/packages/notifier.yaml
framework:
    notifier:
        texter_transports:
            mainsms: '%env(MAINSMS_DSN)%'
```

Then define the DSN in your `.env` file.

## DSN example

```env
MAINSMS_DSN=mainsms://APIKEY@default?project=PROJECT&sender=SENDER&strategy=STRATEGY&timeout=TIMEOUT&test=TEST
```

### DSN parameters

- **APIKEY** — Your Mainsms API key (**required**)
- **project** — Project name (**required**)
- **sender** — Sender name (optional)
- **strategy** — Message delivery strategy (optional):
  - omitted or `-1`: SMS only
  - `1`: Viber only
  - `2`: Viber or SMS
- **timeout** — HTTP request timeout (numeric, optional)
- **test** — Enable test mode (`true` or `false`, default: `false`) (optional)

## Resources

- [Mainsms API Documentation](https://mainsms.ru/home/api)
