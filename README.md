# Mainsms Notifier

Provides [Mainsms](https://mainsms.ru) integration for Symfony Notifier.

## DSN Example

```env
MAINSMS_DSN=mainsms://APIKEY@default?project=PROJECT&sender=SENDER&strategy=STRATEGY&timeout=TIMEOUT&test=TEST
```

### Parameters

- **APIKEY** — your Mainsms API key (**required**)
- **PROJECT** — project name (**required**)
- **SENDER** — sender name (optional)
- **STRATEGY** — message delivery strategy: (optional)
    - omitted or `-1`: SMS only
    - `1`: Viber only
    - `2`: Viber or SMS
- **TIMEOUT** — HTTP timeout in seconds (optional)
- **TEST** — enable test mode (`true` or `false`, default: `false`) (optional)

## Resources

- [Mainsms API Documentation](https://mainsms.ru/home/api)

