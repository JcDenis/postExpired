<?php
/**
 * @brief postExpired, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugin
 *
 * @author Jean-Christian Denis and Contributors
 *
 * @copyright Jean-Christian Denis
 * @copyright GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
declare(strict_types=1);

namespace Dotclear\Plugin\postExpired;

use dcCore;

/**
 * Module definiton shortcut.
 */
class My
{
    /** @var string This plugin meta type */
    public const META_TYPE = 'post_expired';

    /**
     * This module id.
     *
     * @return  string  The module id
     */
    public static function id(): string
    {
        return basename(dirname(__DIR__));
    }

    /**
     * This module name.
     *
     * @return  string  The module translated name
     */
    public static function name(): string
    {
        return __((string) dcCore::app()->plugins->moduleInfo(self::id(), 'name'));
    }

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
}
