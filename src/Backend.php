<?php

declare(strict_types=1);

namespace Dotclear\Plugin\postExpired;

use Dotclear\App;
use Dotclear\Core\Process;

/**
 * @brief       postExpired backend class.
 * @ingroup     postExpired
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class Backend extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::BACKEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        App::behavior()->addBehaviors([
            'adminPostsActions'     => BackendBehaviors::adminPostsActions(...),
            'adminPagesActions'     => BackendBehaviors::adminPostsActions(...),
            'adminPostHeaders'      => BackendBehaviors::adminPostHeaders(...),
            'adminPageHeaders'      => BackendBehaviors::adminPostHeaders(...),
            'adminPostFormItems'    => BackendBehaviors::adminPostFormItems(...),
            'adminPageFormItems'    => BackendBehaviors::adminPostFormItems(...),
            'adminBeforePostDelete' => BackendBehaviors::adminBeforePostDelete(...),
            'adminBeforePageDelete' => BackendBehaviors::adminBeforePostDelete(...),
            'adminAfterPostUpdate'  => BackendBehaviors::adminAfterPostSave(...),
            'adminAfterPageUpdate'  => BackendBehaviors::adminAfterPostSave(...),
            'adminAfterPostCreate'  => BackendBehaviors::adminAfterPostSave(...),
            'adminAfterPageCreate'  => BackendBehaviors::adminAfterPostSave(...),
        ]);

        return true;
    }
}
