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
use dcNsProcess;

class Frontend extends dcNsProcess
{
    public static function init(): bool
    {
        static::$init = true;

        return static::$init;
    }

    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        // l10n
        __('Expired on');
        __('This entry has no expiration date');

        # launch update only on public home page and feed
        if (in_array(dcCore::app()->url->type, ['default', 'feed'])) {
            dcCore::app()->addBehavior(
                'publicBeforeDocumentV2',
                [FrontendBehaviors::class, 'publicBeforeDocument']
            );
        }
        dcCore::app()->addBehavior(
            'coreBlogGetPosts',
            [FrontendBehaviors::class, 'coreBlogGetPosts']
        );
        dcCore::app()->tpl->addBlock(
            'EntryExpiredIf',
            [FrontendTemplate::class, 'EntryExpiredIf']
        );
        dcCore::app()->tpl->addValue(
            'EntryExpiredDate',
            [FrontendTemplate::class, 'EntryExpiredDate']
        );
        dcCore::app()->tpl->addValue(
            'EntryExpiredTime',
            [FrontendTemplate::class, 'EntryExpiredTime']
        );

        return true;
    }
}
