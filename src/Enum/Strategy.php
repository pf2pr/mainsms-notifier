<?php

declare(strict_types=1);

namespace Pf2Pr\Notifier\Bridge\Mainsms\Enum;

enum Strategy: int
{
    case Sms         = -1;
    case Viber       = 1;
    case SmsAndViber = 2;
}
