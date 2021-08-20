<?php
# -- BEGIN LICENSE BLOCK ----------------------------------
#
# This file is part of postExpired, a plugin for Dotclear 2.
# 
# Copyright (c) 2009-2021 Jean-Christian Denis and contributors
# 
# Licensed under the GPL version 2.0 license.
# A copy of this license is available in LICENSE file or at
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK ------------------------------------

if (!defined('DC_RC_PATH')) {
    return null;
}

if ($core->getVersion('postExpired') != $core->plugins->moduleInfo('postExpired', 'version')) {
    return null;
}

__('Expired on');
__('This entry has no expiration date');

# launch update only on public home page and feed
if (in_array($core->url->type, array('default', 'feed'))) { 
    $core->addBehavior(
        'publicBeforeDocument',
        ['publicBehaviorPostExpired', 'publicBeforeDocument']
    );
}
$core->addBehavior(
    'coreBlogGetPosts',
    ['publicBehaviorPostExpired', 'coreBlogGetPosts']
);
$core->tpl->addBlock(
    'EntryExpiredIf',
    ['tplPostExpired', 'EntryExpiredIf']
);
$core->tpl->addValue(
    'EntryExpiredDate',
    ['tplPostExpired', 'EntryExpiredDate']
);
$core->tpl->addValue(
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
     * @param  dcCore $core dcCore instance
     */
    public static function publicBeforeDocument(dcCore $core)
    {
        # Get expired dates and post_id
        $posts = $core->con->select(
            'SELECT P.post_id, P.post_tz, META.meta_id ' .
            'FROM ' . $core->prefix . 'post P ' .
            'INNER JOIN ' . $core->prefix . 'meta META ' .
            'ON META.post_id = P.post_id ' .
            "WHERE blog_id = '" . $core->con->escape($core->blog->id) . "' " .
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
        $post_cur = $core->con->openCursor($core->prefix . 'post');

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
                $core->auth->sudo(
                    array($core->meta, 'delPostMeta'),
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
                    "AND blog_id = '" . $core->con->escape($core->blog->id) . "' "
                );

                $updated = true;
            }
        }

        # Say blog is updated
        if ($updated) {
            $core->blog->triggerBlog();
        }
    }

    /**
     * Extends posts record with expired date
     * 
     * @param  record $rs Post recordset
     */
    public static function coreBlogGetPosts(record $rs)
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
    public static function postExpiredDate(record $rs)
    {
        if (!$rs->postexpired[$rs->post_id]) { //memory
            $rs_date = $rs->core->meta->getMetadata(array(
                'meta_type' => 'post_expired',
                'post_id'=> $rs->post_id,
                'limit'=> 1
            ));

            if ($rs_date->isEmpty()) {
                return null;
            }

            $v = unserialize(base64_decode($rs_date->meta_id));
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
            $if[] = '(null ' . $sign . '== $_ctx->posts->postExpiredDate())';
        } else {
            $if[] = '(null !== $_ctx->posts->postExpiredDate())';
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
        $f = $GLOBALS['core']->tpl->getFilters($attr);

        if (!empty($attr['rfc822'])) {
            $res = sprintf($f, "dt::rfc822(strtotime(\$_ctx->posts->postExpiredDate()),\$_ctx->posts->post_tz)");
        } elseif (!empty($attr['iso8601'])) {
            $res = sprintf($f, "dt::iso8601(strtotime(\$_ctx->posts->postExpiredDate(),\$_ctx->posts->post_tz)");
        } elseif ($format) {
            $res = sprintf($f, "dt::dt2str('" . $format . "',\$_ctx->posts->postExpiredDate())");
        } else {
            $res = sprintf($f, "dt::dt2str(\$core->blog->settings->system->date_format,\$_ctx->posts->postExpiredDate())");
        }

        return '<?php if (null !== $_ctx->posts->postExpiredDate()) { echo ' . $res . '; } ?>';
    }

    /**
     * Template for expired time
     * 
     * @param array $attr Value attributes
     */
    public static function EntryExpiredTime($attr)
    {
        return 
        '<?php if (null !== $_ctx->posts->postExpiredDate()) { echo ' . sprintf(
            $GLOBALS['core']->tpl->getFilters($attr), "dt::dt2str(" .
            (!empty($attr['format']) ? 
                "'" . addslashes($attr['format']) . "'" : "\$core->blog->settings->system->time_format"
            ) . ",\$_ctx->posts->postExpiredDate())"
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