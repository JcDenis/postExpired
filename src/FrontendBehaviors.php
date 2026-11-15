<?php

declare(strict_types=1);

namespace Dotclear\Plugin\postExpired;

use Dotclear\Database\MetaRecord;

/**
 * @brief       postExpired frontend behaviors class.
 * @ingroup     postExpired
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class FrontendBehaviors
{
    /**
     * Extends posts record with expired date
     *
     * @param  MetaRecord $rs Post recordset
     */
    public static function coreBlogGetPosts(MetaRecord $rs): void
    {
        $rs->extend(RsExtPostExpired::class);
    }
}
