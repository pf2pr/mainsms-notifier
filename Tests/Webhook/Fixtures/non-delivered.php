<?php

declare(strict_types=1);

use Symfony\Component\RemoteEvent\Event\Sms\SmsEvent;

parse_str(trim(file_get_contents(str_replace('.php', '.txt', __FILE__))), $payload);
$wh = new SmsEvent(SmsEvent::FAILED, '123456', $payload);
$wh->setRecipientPhone('79161234567');

return $wh;