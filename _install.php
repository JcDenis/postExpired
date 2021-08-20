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

if (!defined('DC_CONTEXT_ADMIN')) {
    return null;
}

# -- Module specs --
$dc_min = '2.18';
$mod_id = 'postExpired';

# -- Nothing to change below --
try {
    # Check module version
    if (version_compare(
        $core->getVersion($mod_id),
        $core->plugins->moduleInfo($mod_id, 'version'),
        '>='
    )) {
        return null;
    }

    # Check Dotclear version
    if (!method_exists('dcUtils', 'versionsCompare') 
     || dcUtils::versionsCompare(DC_VERSION, $dc_min, '<', false)) {
        throw new Exception(sprintf(
            '%s requires Dotclear %s', $mod_id, $dc_min
        ));
    }

    # Set module version
    $core->setVersion(
        $mod_id,
        $core->plugins->moduleInfo($mod_id, 'version')
    );

    return true;
} catch (Exception $e) {
    $core->error->add($e->getMessage());

    return false;
}