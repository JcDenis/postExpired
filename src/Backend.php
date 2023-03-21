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

use dcAuth;
use dcCore;
use dcNsProcess;

class Backend extends dcNsProcess
{
    public static function init(): bool
    {
        static::$init = defined('DC_CONTEXT_ADMIN') && dcCore::app()->auth->check(
            dcCore::app()->auth->makePermissions([dcAuth::PERMISSION_CONTENT_ADMIN]),
            dcCore::app()->blog->id
        );

        return static::$init;
    }

    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        dcCore::app()->addBehaviors([
            'adminPostsActions'     => [BackendBehaviors::class, 'adminPostsActions'],
            'adminPagesActions'     => [BackendBehaviors::class, 'adminPostsActions'],
            'adminPostHeaders'      => [BackendBehaviors::class, 'adminPostHeaders'],
            'adminPageHeaders'      => [BackendBehaviors::class, 'adminPostHeaders'],
            'adminPostFormItems'    => [BackendBehaviors::class, 'adminPostFormItems'],
            'adminPageFormItems'    => [BackendBehaviors::class, 'adminPostFormItems'],
            'adminBeforePostDelete' => [BackendBehaviors::class, 'adminBeforePostDelete'],
            'adminBeforePageDelete' => [BackendBehaviors::class, 'adminBeforePostDelete'],
            'adminAfterPostUpdate'  => [BackendBehaviors::class, 'adminAfterPostSave'],
            'adminAfterPageUpdate'  => [BackendBehaviors::class, 'adminAfterPostSave'],
            'adminAfterPostCreate'  => [BackendBehaviors::class, 'adminAfterPostSave'],
            'adminAfterPageCreate'  => [BackendBehaviors::class, 'adminAfterPostSave'],
        ]);

        return true;
    }
}
