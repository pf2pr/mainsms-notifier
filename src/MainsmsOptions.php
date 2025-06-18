<?php

declare(strict_types=1);

namespace Pf2Pr\Notifier\Bridge\Mainsms;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Pf2Pr\Notifier\Bridge\Mainsms\Enum\Strategy;
use Symfony\Component\Clock\Clock;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Notifier\Exception\InvalidArgumentException;
use Symfony\Component\Notifier\Message\MessageOptionsInterface;

final class MainsmsOptions implements MessageOptionsInterface
{
    private ClockInterface $clock;

    public function __construct(
        /** @var mixed[] */
        private array $options = [],
        ?ClockInterface $clock = null,
    ) {
        $this->clock = $clock ?? Clock::get();
    }

    public function getRecipientId(): ?string
    {
        return null;
    }

    public function project(string $project): static
    {
        $this->options['project'] = $project;

        return $this;
    }

    public function sender(string $sender): static
    {
        $this->options['sender'] = $sender;

        return $this;
    }

    /**
     * Sets the delivery strategy for the message.
     *
     * Modes:
     * - null (or Strategy::Sms): SMS only
     * - Strategy::Viber: Viber only
     * - Strategy::ViberOrSms: Try Viber first, then fallback to SMS
     *
     * If not set, SMS-only mode is used by default.
     * This setting requires Viber mode to be enabled in the API project configuration.
     */
    public function strategy(?Strategy $strategy): static
    {
        if (Strategy::Sms === $strategy) {
            $strategy = null;
        }

        $this->options['strategy'] = $strategy;

        return $this;
    }

    /**
     * Sets the date and time when the message should be sent.
     *
     * The provided DateTime must be in the future relative to the current system clock.
     * The value will be converted to the Europe/Moscow timezone before storing.
     */
    public function dateTime(DateTimeImmutable $dateTime): static
    {
        if ($dateTime < $this->clock->now()) {
            throw new InvalidArgumentException('The given DateTime must be greater to the current date.');
        }

        $this->options['run_at'] = $dateTime->setTimezone(new DateTimeZone('Europe/Moscow'));

        return $this;
    }

    /**
     * Sets the image URL to be used in the message.
     *
     * URL must start with "http://" or "https://", point to a JPG or PNG image,
     * and the image must have dimensions exactly 400x400 pixels.
     *
     * If set, both button() and buttonUrl() must also be set.
     */
    public function image(string $image): static
    {
        if (!str_starts_with($image, 'http://') && !str_starts_with($image, 'https://')) {
            throw new InvalidArgumentException(sprintf('The image url "%s" is not valid. It must start with http:// or https://.', $image));
        }

        $this->options['image'] = $image;

        return $this;
    }

    /**
     * Sets the button text.
     *
     * If set, both image() and buttonUrl() must also be set.
     * The text must not exceed 20 characters.
     */
    public function button(string $button): static
    {
        if (mb_strlen($button) > 20) {
            throw new InvalidArgumentException(sprintf(
                'Button text must be at most 20 characters long, got %d.',
                mb_strlen($button)
            ));
        }

        $this->options['button'] = $button;

        return $this;
    }

    /**
     * Sets the URL for the button.
     *
     * The URL must start with "http://", "https://", or "tel:".
     * If set, both image() and button() must also be set.
     */
    public function buttonUrl(string $buttonUrl): static
    {
        if (
            !str_starts_with($buttonUrl, 'http://')
            && !str_starts_with($buttonUrl, 'https://')
            && !str_starts_with($buttonUrl, 'tel:')
        ) {
            throw new InvalidArgumentException(sprintf('The button url "%s" is not valid. It must start with http:// or https:// or tel:.', $buttonUrl));
        }

        $this->options['button_url'] = $buttonUrl;

        return $this;
    }

    /**
     * Sets the Viber-specific message text.
     *
     * If not set, the text from the general message will be used instead.
     */
    public function viberText(string $viberText): static
    {
        $this->options['viber_text'] = $viberText;

        return $this;
    }

    /**
     * Enables or disables test mode.
     *
     * When enabled, messages will not be sent.
     */
    public function test(bool $test): static
    {
        $this->options['test'] = (int) $test;

        return $this;
    }

    /**
     * @return mixed[]
     */
    public function toArray(): array
    {
        $options = $this->options;

        if (isset($options['run_at']) && $options['run_at'] instanceof DateTimeInterface) {
            $options['run_at'] = $options['run_at']->format('Y-m-d H:i');
        }

        return $options;
    }
}
