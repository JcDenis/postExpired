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

if (!defined('DC_RC_PATH')) {
    return null;
}

if (dcCore::app()->getVersion('postExpired') != dcCore::app()->plugins->moduleInfo('postExpired', 'version')) {
    return null;
}

__('Expired on');
__('This entry has no expiration date');

# launch update only on public home page and feed
if (in_array(dcCore::app()->url->type, array('default', 'feed'))) { 
    dcCore::app()->addBehavior(
        'publicBeforeDocumentV2',
        ['publicBehaviorPostExpired', 'publicBeforeDocument']
    );
}
dcCore::app()->addBehavior(
    'coreBlogGetPosts',
    ['publicBehaviorPostExpired', 'coreBlogGetPosts']
);
dcCore::app()->tpl->addBlock(
    'EntryExpiredIf',
    ['tplPostExpired', 'EntryExpiredIf']
);
dcCore::app()->tpl->addValue(
    'EntryExpiredDate',
    ['tplPostExpired', 'EntryExpiredDate']
);
dcCore::app()->tpl->addValue(
    'EntryExpiredTime',
    ['tplPostExpired', 'EntryExpiredTime']
);

/**
 * @ingroup DC_PLUGIN_POSTEXPIRED
 * @brief Scheduled post change - public methods.
 * @since 2.6
 */
class publicBehaviorPostExpired
{
    /**
     * Check if there are expired dates
     * 
     */
    public static function publicBeforeDocument()
    {
        # Get expired dates and post_id
        $posts = dcCore::app()->con->select(
            'SELECT P.post_id, P.post_tz, META.meta_id ' .
            'FROM ' . dcCore::app()->prefix . 'post P ' .
            'INNER JOIN ' . dcCore::app()->prefix . 'meta META ' .
            'ON META.post_id = P.post_id ' .
            "WHERE blog_id = '" . dcCore::app()->con->escape(dcCore::app()->blog->id) . "' " .
            // Removed for quick compatibility with some plugins
            //"AND P.post_type = 'post' " . 
            "AND META.meta_type = 'post_expired' "
        );

        # No expired date
        if ($posts->isEmpty()) {
            return null;
        }

        # Get curent timestamp
        $now = dt::toUTC(time());

        # Prepared post cursor
        $post_cur = dcCore::app()->con->openCursor(dcCore::app()->prefix . 'post');

        # Loop through marked posts
        $updated = false;
        while($posts->fetch()) {

            # Decode meta record
            $post_expired = decodePostExpired($posts->meta_id);

            # Check if post is outdated
            $now_tz = $now + dt::getTimeOffset($posts->post_tz, $now);
            $meta_tz = strtotime($post_expired['date']);
            if ($now_tz > $meta_tz) {
                # Delete meta for expired date
                dcCore::app()->auth->sudo(
                    array(dcCore::app()->meta, 'delPostMeta'),
                    $posts->post_id,
                    'post_expired'
                );

                # Prepare post cursor
                $post_cur->clean();
                $post_cur->post_upddt = date('Y-m-d H:i:s', $now_tz);

                # Loop through actions
                foreach($post_expired as $k => $v) {
                    if (empty($v)) {
                        continue;
                    }

                    # values are prefixed by "!"
                    $v =  (integer) substr($v, 1);

                    # Put value in post cursor
                    switch($k)
                    {
                        case 'status':
                            $post_cur->post_status = $v;
                        break;

                        case 'category':
                            $post_cur->cat_id = $v ? $v : null;
                        break;

                        case 'selected':
                            $post_cur->post_selected = $v;
                        break;

                        case 'comment':
                            $post_cur->post_open_comment = $v;
                        break;

                        case 'trackback':
                            $post_cur->post_open_tb = $v;
                        break;
                    }
                }

                # Update post
                $post_cur->update(
                    'WHERE post_id = ' . $posts->post_id . ' ' .
                    "AND blog_id = '" . dcCore::app()->con->escape(dcCore::app()->blog->id) . "' "
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
     * @param  dcRecord $rs Post recordset
     */
    public static function coreBlogGetPosts(dcRecord $rs)
    {
        $rs->extend('rsExtPostExpiredPublic');
    }
}

/**
 * @ingroup DC_PLUGIN_POSTEXPIRED
 * @brief Scheduled post change - extends recordset.
 * @since 2.6
 */
class rsExtPostExpiredPublic extends rsExtPost
{
    /**
     * Retrieve expired date of a post
     * 
     * @param  record  $rs            Post recordset
     * @return string                 Expired date or null
     */
    public static function postExpiredDate(dcRecord $rs)
    {
        if (!$rs->postexpired[$rs->post_id]) { //memory
            $rs_date = $rs->core->meta->getMetadata([
                'meta_type' => 'post_expired',
                'post_id'=> $rs->post_id,
                'limit'=> 1
            ]);

            if ($rs_date->isEmpty()) {
                return null;
            }

            $v = decodePostExpired($rs_date->meta_id);
            $rs->postexpired[$rs->post_id] = $v['date'];
        }

        return $rs->postexpired[$rs->post_id];
    }
}

/**
 * @ingroup DC_PLUGIN_POSTEXPIRED
 * @brief Scheduled post change - template methods.
 * @since 2.6
 */
class tplPostExpired
{
    /**
     * Template condition to check if there is an expired date
     * 
     * @param array  $attr    Block attributes
     * @param string $content Block content
     */
    public static function EntryExpiredIf($attr, $content)
    {
        $if = array();
        $operator = isset($attr['operator']) ? 
            self::getOperator($attr['operator']) : '&&';

        if (isset($attr['has_date'])) {
            $sign = (boolean) $attr['has_date'] ? '!' : '=';
            $if[] = '(null ' . $sign . '== dcCore::app()->ctx->posts->postExpiredDate())';
        } else {
            $if[] = '(null !== dcCore::app()->ctx->posts->postExpiredDate())';
        }

        return 
        "<?php if(" . implode(' ' . $operator . ' ', $if) . ") : ?>\n" .
        $content .
        "<?php endif; ?>\n";
    }

    /**
     * Template for expired date
     * 
     * @param array $attr Value attributes
     */
    public static function EntryExpiredDate($attr)
    {
        $format = !empty($attr['format']) ? 
            addslashes($attr['format']) : '';
        $f = dcCore::app()->tpl->getFilters($attr);

        if (!empty($attr['rfc822'])) {
            $res = sprintf($f, "dt::rfc822(strtotime(dcCore::app()->ctx->posts->postExpiredDate()),dcCore::app()->ctx->posts->post_tz)");
        } elseif (!empty($attr['iso8601'])) {
            $res = sprintf($f, "dt::iso8601(strtotime(dcCore::app()->ctx->posts->postExpiredDate(),dcCore::app()->ctx->posts->post_tz)");
        } elseif ($format) {
            $res = sprintf($f, "dt::dt2str('" . $format . "',dcCore::app()->ctx->posts->postExpiredDate())");
        } else {
            $res = sprintf($f, "dt::dt2str(dcCore::app()->blog->settings->system->date_format,dcCore::app()->ctx->posts->postExpiredDate())");
        }

        return '<?php if (null !== dcCore::app()->ctx->posts->postExpiredDate()) { echo ' . $res . '; } ?>';
    }

    /**
     * Template for expired time
     * 
     * @param array $attr Value attributes
     */
    public static function EntryExpiredTime($attr)
    {
        return 
        '<?php if (null !== dcCore::app()->ctx->posts->postExpiredDate()) { echo ' . sprintf(
            dcCore::app()->tpl->getFilters($attr), "dt::dt2str(" .
            (!empty($attr['format']) ? 
                "'" . addslashes($attr['format']) . "'" : "dcCore::app()->blog->settings->system->time_format"
            ) . ",dcCore::app()->ctx->posts->postExpiredDate())"
        ) . '; } ?>';
    }

    /**
     * Parse tempalte attributes oprerator
     * 
     * @param string $op Operator
     */
    protected static function getOperator($op)
    {
        switch (strtolower($op))
        {
            case 'or':
            case '||':
                return '||';
            case 'and':
            case '&&':
            default:
                return '&&';
        }
    }
}