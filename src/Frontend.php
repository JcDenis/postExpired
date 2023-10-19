<?php

declare(strict_types=1);

namespace Dotclear\Plugin\postExpired;

use Dotclear\App;
use Dotclear\Core\Process;

/**
 * @brief       postExpired frontend class.
 * @ingroup     postExpired
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class Frontend extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::FRONTEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        // l10n
        __('Expired on');
        __('This entry has no expiration date');

        # launch update only on public home page and feed
        if (in_array(App::url()->type, ['default', 'feed'])) {
            App::behavior()->addBehavior(
                'publicBeforeDocumentV2',
                FrontendBehaviors::publicBeforeDocumentV2(...)
            );
        }
        App::behavior()->addBehavior(
            'coreBlogGetPosts',
            FrontendBehaviors::coreBlogGetPosts(...)
        );
        App::frontend()->template()->addBlock(
            'EntryExpiredIf',
            FrontendTemplate::EntryExpiredIf(...)
        );
        App::frontend()->template()->addValue(
            'EntryExpiredDate',
            FrontendTemplate::EntryExpiredDate(...)
        );
        App::frontend()->template()->addValue(
            'EntryExpiredTime',
            FrontendTemplate::EntryExpiredTime(...)
        );

        return true;
    }
}
