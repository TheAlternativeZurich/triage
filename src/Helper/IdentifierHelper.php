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

class IdentifierHelper
{
    /**
     * transforms text to human readable URL
     * only outputs lowercase alphanummeric string, invalid characters are replaced by -.
     *
     * min length 10, max length 100
     */
    public static function getHumanReadableIdentifier(string $text): string
    {
        $lowercase = strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $text));

        $result = '';
        for ($i = 0; $i < strlen($lowercase); ++$i) {
            $character = $lowercase[$i];
            $characterValue = ord($character);

            //0-9, a-z
            if (($characterValue >= 48 && $characterValue <= 57) ||
                ($characterValue >= 97 && $characterValue <= 122)) {
                $result .= $character;
            } else {
                $result .= '-';
            }
        }

        if (strlen($result) > 100) {
            $result = substr($result, 0, 100); // make max length
            $result = substr($result, 0, strrpos($result, '-')); // cut off last word
        }

        if (strlen($result) < 10) {
            $result .= RandomHelper::generateHumanReadableRandom(10, '-');
        }

        return trim($result);
    }
}
