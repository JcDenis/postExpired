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

use DateTimeZone;
use dcBlog;
use dcCore;
use dcMeta;
use Dotclear\Database\MetaRecord;

/**
 * @ingroup DC_PLUGIN_POSTEXPIRED
 * @brief Scheduled post change - public methods.
 * @since 2.6
 */
class FrontendBehaviors
{
    /**
     * Check if there are expired dates
     */
    public static function publicBeforeDocument(): void
    {
        # Get expired dates and post_id
        $posts = dcCore::app()->con->select(
            'SELECT P.post_id, P.post_tz, META.meta_id ' .
            'FROM ' . dcCore::app()->prefix . dcBlog::POST_TABLE_NAME . ' P ' .
            'INNER JOIN ' . dcCore::app()->prefix . dcMeta::META_TABLE_NAME . ' META ' .
            'ON META.post_id = P.post_id ' .
            "WHERE blog_id = '" . dcCore::app()->con->escapeStr((string) dcCore::app()->blog->id) . "' " .
            // Removed for quick compatibility with some plugins
            //"AND P.post_type = 'post' " .
            "AND META.meta_type = '" . My::META_TYPE . "' "
        );

        # No expired date
        if ($posts->isEmpty()) {
            return;
        }

        # Prepared date
        $utc    = new DateTimeZone('UTC');
        $now_tz = (int) date_format(date_create('now', $utc), 'U');

        # Prepared post Cursor
        $post_cur = dcCore::app()->con->openCursor(dcCore::app()->prefix . dcBlog::POST_TABLE_NAME);

        # Loop through marked posts
        $updated = false;
        while ($posts->fetch()) {
            # Decode meta record
            $post_expired = My::decode($posts->f('meta_id'));

            # Check if post is outdated
            $meta_dt = date_create((string) $post_expired['date'], $utc);
            $meta_tz = $meta_dt ? date_format($meta_dt, 'U') : 0;

            if ($now_tz > $meta_tz) {
                # Delete meta for expired date
                dcCore::app()->auth->sudo(
                    [dcCore::app()->meta, 'delPostMeta'],
                    $posts->f('post_id'),
                    My::META_TYPE
                );

                # Prepare post Cursor
                $post_cur->clean();
                $post_cur->setField('post_upddt', date('Y-m-d H:i:s', $now_tz));

                # Loop through actions
                foreach ($post_expired as $k => $v) {
                    if (empty($v)) {
                        continue;
                    }

                    # values are prefixed by "!"
                    $v = (int) substr($v, 1);

                    # Put value in post Cursor
                    switch($k) {
                        case 'status':
                            $post_cur->setField('post_status', $v);

                            break;

                        case 'category':
                            $post_cur->setField('cat_id', $v ? $v : null);

                            break;

                        case 'selected':
                            $post_cur->setField('post_selected', $v);

                            break;

                        case 'comment':
                            $post_cur->setField('post_open_comment', $v);

                            break;

                        case 'trackback':
                            $post_cur->setField('post_open_tb', $v);

                            break;
                    }
                }

                # Update post
                $post_cur->update(
                    'WHERE post_id = ' . $posts->f('post_id') . ' ' .
                    "AND blog_id = '" . dcCore::app()->con->escapeStr((string) dcCore::app()->blog->id) . "' "
                );

                $updated = true;
            }
        }

        # Say blog is updated
        if ($updated) {
            dcCore::app()->blog->triggerBlog();
        }
    }

    /**
     * Extends posts record with expired date
     *
     * @param  MetaRecord $rs Post recordset
     */
    public static function coreBlogGetPosts(MetaRecord $rs): void
    {
        $rs->extend('rsExtPostExpired');
    }
}
