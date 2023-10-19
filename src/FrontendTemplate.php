<?php

declare(strict_types=1);

namespace Dotclear\Plugin\postExpired;

use ArrayObject;
use Dotclear\App;
use Dotclear\Helper\Date;

/**
 * @brief       postExpired frontend template class.
 * @ingroup     postExpired
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class FrontendTemplate
{
    /**
     * Template condition to check if there is an expired date.
     *
     * @param   ArrayObject     $attr       Block attributes
     * @param   string          $content    Block content
     *
     * @return  string
     */
    public static function EntryExpiredIf(ArrayObject $attr, string $content): string
    {
        $if       = [];
        $operator = isset($attr['operator']) ?
            App::frontend()->template()->getOperator($attr['operator']) : '&&';

        if (isset($attr['has_date'])) {
            $sign = (bool) $attr['has_date'] ? '!' : '=';
            $if[] = '(null ' . $sign . '== App::frontend()->context()->posts->postExpiredDate())';
        } else {
            $if[] = '(null !== App::frontend()->context()->posts->postExpiredDate())';
        }

        return
        '<?php if(' . implode(' ' . $operator . ' ', $if) . ") : ?>\n" .
        $content .
        "<?php endif; ?>\n";
    }

    /**
     * Template for expired date.
     *
     * @param   ArrayObject     $attr   Value attributes
     *
     * @return  string
     */
    public static function EntryExpiredDate(ArrayObject $attr): string
    {
        $format = !empty($attr['format']) ?
            addslashes($attr['format']) : '';
        $f = App::frontend()->template()->getFilters($attr);

        if (!empty($attr['rfc822'])) {
            $res = sprintf($f, Date::class . '::rfc822(strtotime(App::frontend()->context()->posts->postExpiredDate()),App::frontend()->context()->posts->post_tz)');
        } elseif (!empty($attr['iso8601'])) {
            $res = sprintf($f, Date::class . '::iso8601(strtotime(App::frontend()->context()->posts->postExpiredDate(),App::frontend()->context()->posts->post_tz)');
        } elseif ($format) {
            $res = sprintf($f, Date::class . "::dt2str('" . $format . "',App::frontend()->context()->posts->postExpiredDate())");
        } else {
            $res = sprintf($f, Date::class . '::dt2str(App::blog()->settings()->system->date_format,App::frontend()->context()->posts->postExpiredDate())');
        }

        return '<?php if (null !== App::frontend()->context()->posts->postExpiredDate()) { echo ' . $res . '; } ?>';
    }

    /**
     * Template for expired time.
     *
     * @param   ArrayObject     $attr   Value attributes
     *
     * @return  string
     */
    public static function EntryExpiredTime(ArrayObject $attr): string
    {
        return
        '<?php if (null !== App::frontend()->context()->posts->postExpiredDate()) { echo ' . sprintf(
            App::frontend()->template()->getFilters($attr),
            Date::class . '::dt2str(' .
            (
                !empty($attr['format']) ?
                "'" . addslashes($attr['format']) . "'" : 'App::blog()->settings()->system->time_format'
            ) . ',App::frontend()->context()->posts->postExpiredDate())'
        ) . '; } ?>';
    }
}
