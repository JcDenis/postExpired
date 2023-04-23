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
use Dotclear\Database\MetaRecord;
use rsExtPost;

/**
 * @ingroup DC_PLUGIN_POSTEXPIRED
 * @brief Scheduled post change - extends recordset.
 * @since 2.6
 */
class rsExtPostExpired extends rsExtPost
{
    /** @var    array   $memory     Memory to prevent redondant call */
    protected static array $memory = [];

    /**
     * Retrieve expired date of a post.
     *
     * @param   MetaRecord  $rs     Post recordset
     *
     * @return  string  Expired date or null
     */
    public static function postExpiredDate(MetaRecord $rs): string
    {
        if (!static::$memory[$rs->f('post_id')]) {
            $rs_date = dcCore::app()->meta->getMetadata([
                'meta_type' => My::META_TYPE,
                'post_id'   => $rs->f('post_id'),
                'limit'     => 1,
            ]);

            if ($rs_date->isEmpty()) {
                return '';
            }

            $v                                 = My::decode($rs_date->f('meta_id'));
            static::$memory[$rs->f('post_id')] = $v['date'];
        }

        return static::$memory[$rs->f('post_id')];
    }
}
