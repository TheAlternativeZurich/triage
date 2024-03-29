<?php

/*
 * This file is part of the thealternativezurich/triage project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Helper;

use DateTimeZone;

class DateTimeFormatter
{
    public const DATE_TIME_FORMAT = 'd.m.Y H:i';
    public const DATE_FORMAT = 'd.m.Y';
    public const TIME_FORMAT = 'H:i';

    public static function toStringUTCTimezone(\DateTime $dateTime)
    {
        $current = clone $dateTime;
        $current->setTimezone(new DateTimeZone('UTC'));

        return $current->format('Y-m-d\TH:i:s.u\Z');
    }
}
