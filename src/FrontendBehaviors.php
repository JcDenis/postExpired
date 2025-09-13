<?php

declare(strict_types=1);

namespace Dotclear\Plugin\postExpired;

use DateTimeZone;
use Dotclear\App;
use Dotclear\Database\MetaRecord;
use Dotclear\Database\Statement\JoinStatement;
use Dotclear\Database\Statement\SelectStatement;

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
     * Check if there are expired dates.
     */
    public static function publicBeforeDocumentV2(): void
    {
        // Get expired dates and post_id
        $sql   = new SelectStatement();
        $posts = $sql->from($sql->as(App::db()->con()->prefix() . App::blog()::POST_TABLE_NAME, 'P'))
            ->columns([
                'P.post_id',
                'P.post_tz',
                'META.meta_id',
            ])
            ->join(
                (new JoinStatement())
                    ->inner()
                    ->from($sql->as(App::db()->con()->prefix() . App::meta()::META_TABLE_NAME, 'META'))
                    ->on('META.post_id = P.post_id')
                    ->statement()
            )
            ->where('blog_id = ' . $sql->quote(App::blog()->id()))
            ->and('META.meta_type = ' . $sql->quote(My::META_TYPE))
            ->select();

        // No expired date
        if (is_null($posts) || $posts->isEmpty()) {
            return;
        }

        // Prepared date
        $utc    = new DateTimeZone('UTC');
        $now_tz = (int) date_format(date_create('now', $utc), 'U');

        // Prepared post Cursor
        $post_cur = App::blog()->openPostCursor();

        // Loop through marked posts
        $updated = false;
        while ($posts->fetch()) {
            // Decode meta record
            $post_expired = My::decode($posts->f('meta_id'));

            // Check if post is outdated
            $meta_dt = date_create((string) $post_expired['date'], $utc);
            $meta_tz = $meta_dt ? date_format($meta_dt, 'U') : 0;

            if ($now_tz > $meta_tz) {
                // Delete meta for expired date
                App::auth()->sudo(
                    App::meta()->delPostMeta(...),
                    $posts->f('post_id'),
                    My::META_TYPE
                );

                // Prepare post Cursor
                $post_cur->clean();
                $post_cur->setField('post_upddt', date('Y-m-d H:i:s', $now_tz));

                // Loop through actions
                foreach ($post_expired as $k => $v) {
                    if (empty($v)) {
                        continue;
                    }

                    // values are prefixed by "!"
                    $v = (int) substr($v, 1);

                    // Put value in post Cursor
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

                        case 'password':
                            $post_cur->setField('post_password', empty($post_expired['newpassword']) ? '' : $post_expired['newpassword']);

                            break;
                    }
                }

                // Update post
                $post_cur->update(
                    'WHERE post_id = ' . $posts->f('post_id') . ' ' .
                    "AND blog_id = '" . App::db()->con()->escapeStr(App::blog()->id()) . "' "
                );

                $updated = true;
            }
        }

        // Say blog is updated
        if ($updated) {
            App::blog()->triggerBlog();
        }
    }

    /**
     * Extends posts record with expired date
     *
     * @param  MetaRecord $rs Post recordset
     */
    public static function coreBlogGetPosts(MetaRecord $rs): void
    {
        $rs->extend(rsExtPostExpired::class);
    }
}
