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
if (!defined('DC_CONTEXT_ADMIN')) {
    return null;
}

# -- Module specs --
$dc_min = '2.24';
$mod_id = 'postExpired';

# -- Nothing to change below --
try {
    # Check module version
    if (version_compare(
        dcCore::app()->getVersion($mod_id),
        dcCore::app()->plugins->moduleInfo($mod_id, 'version'),
        '>='
    )) {
        return null;
    }

    # Check Dotclear version
    if (!method_exists('dcUtils', 'versionsCompare')
     || dcUtils::versionsCompare(DC_VERSION, $dc_min, '<', false)) {
        throw new Exception(sprintf(
            '%s requires Dotclear %s',
            $mod_id,
            $dc_min
        ));
    }

    # Set module version
    dcCore::app()->setVersion(
        $mod_id,
        dcCore::app()->plugins->moduleInfo($mod_id, 'version')
    );

    return true;
} catch (Exception $e) {
    dcCore::app()->error->add($e->getMessage());

    return false;
}
