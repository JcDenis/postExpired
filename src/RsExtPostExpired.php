<?php

declare(strict_types=1);

namespace Dotclear\Plugin\postExpired;

use Dotclear\App;
use Dotclear\Database\MetaRecord;
use Dotclear\Schema\Extension\Post;

/**
 * @brief       postExpired record extension class.
 * @ingroup     postExpired
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class rsExtPostExpired extends Post
{
    /**
     * Memory to prevent redondant call.
     *
     * @var     array<string, string>   $memory
     */
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
            $rs_date = App::meta()->getMetadata([
                'meta_type' => My::META_TYPE,
                'post_id'   => $rs->f('post_id'),
                'limit'     => 1,
            ]);

            if ($rs_date->isEmpty()) {
                return '';
            }

            $v                                          = My::decode($rs_date->f('meta_id'));
            static::$memory[(string) $rs->f('post_id')] = (string) $v['date'];
        }

        return static::$memory[$rs->f('post_id')];
    }
}
