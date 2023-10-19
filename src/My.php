<?php

declare(strict_types=1);

namespace Dotclear\Plugin\postExpired;

use Dotclear\Module\MyPlugin;

/**
 * @brief       postExpired My helper.
 * @ingroup     postExpired
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class My extends MyPlugin
{
    /** @var    string  This module meta type */
    public const META_TYPE = 'post_expired';

    /**
     * Encode Expired Date settings.
     *
     * This is saved into post_meta as meta_id value,
     * so this must be less than 255 caracters.
     *
     * @param   array   $in     Array of options
     *
     * @return  string  "Serialized" options
     */
    public static function encode(array $in): string
    {
        $out = [];
        foreach ($in as $k => $v) {
            $out[] = $k . '|' . $v;
        }

        return implode(';', $out);
    }

    /**
     * Decode Expired Date settings.
     *
     * @param   string  $in     "Serialized" options
     *
     * @return  array   Array of options
     */
    public static function decode(string $in): array
    {
        $out = [];
        foreach (explode(';', $in) as $v) {
            $v          = explode('|', $v);
            $out[$v[0]] = $v[1];
        }

        return $out;
    }

    // Use default permissions
}
