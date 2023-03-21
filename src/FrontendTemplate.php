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

use ArrayObject;
use dcCore;
use dcTemplate;

/**
 * @ingroup DC_PLUGIN_POSTEXPIRED
 * @brief Scheduled post change - template methods.
 * @since 2.6
 */
class FrontendTemplate
{
    /**
     * Template condition to check if there is an expired date
     *
     * @param ArrayObject  $attr    Block attributes
     * @param string $content Block content
     *
     * @return string
     */
    public static function EntryExpiredIf(ArrayObject $attr, string $content): string
    {
        $if       = [];
        $operator = isset($attr['operator']) ?
            dcTemplate::getOperator($attr['operator']) : '&&';

        if (isset($attr['has_date'])) {
            $sign = (bool) $attr['has_date'] ? '!' : '=';
            $if[] = '(null ' . $sign . '== dcCore::app()->ctx->posts->postExpiredDate())';
        } else {
            $if[] = '(null !== dcCore::app()->ctx->posts->postExpiredDate())';
        }

        return
        '<?php if(' . implode(' ' . $operator . ' ', $if) . ") : ?>\n" .
        $content .
        "<?php endif; ?>\n";
    }

    /**
     * Template for expired date
     *
     * @param ArrayObject $attr Value attributes
     *
     * @return string
     */
    public static function EntryExpiredDate(ArrayObject $attr): string
    {
        $format = !empty($attr['format']) ?
            addslashes($attr['format']) : '';
        $f = dcCore::app()->tpl->getFilters($attr);

        if (!empty($attr['rfc822'])) {
            $res = sprintf($f, 'dt::rfc822(strtotime(dcCore::app()->ctx->posts->postExpiredDate()),dcCore::app()->ctx->posts->post_tz)');
        } elseif (!empty($attr['iso8601'])) {
            $res = sprintf($f, 'dt::iso8601(strtotime(dcCore::app()->ctx->posts->postExpiredDate(),dcCore::app()->ctx->posts->post_tz)');
        } elseif ($format) {
            $res = sprintf($f, "dt::dt2str('" . $format . "',dcCore::app()->ctx->posts->postExpiredDate())");
        } else {
            $res = sprintf($f, 'dt::dt2str(dcCore::app()->blog->settings->system->date_format,dcCore::app()->ctx->posts->postExpiredDate())');
        }

        return '<?php if (null !== dcCore::app()->ctx->posts->postExpiredDate()) { echo ' . $res . '; } ?>';
    }

    /**
     * Template for expired time
     *
     * @param ArrayObject $attr Value attributes
     *
     * @return string
     */
    public static function EntryExpiredTime(ArrayObject $attr): string
    {
        return
        '<?php if (null !== dcCore::app()->ctx->posts->postExpiredDate()) { echo ' . sprintf(
            dcCore::app()->tpl->getFilters($attr),
            'dt::dt2str(' .
            (
                !empty($attr['format']) ?
                "'" . addslashes($attr['format']) . "'" : 'dcCore::app()->blog->settings->system->time_format'
            ) . ',dcCore::app()->ctx->posts->postExpiredDate())'
        ) . '; } ?>';
    }
}
