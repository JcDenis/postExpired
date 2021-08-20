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

/**
 * Encode Expired Date settings
 *
 * This is saved into post_meta as meta_id value,
 * so this must be less than 255 caracters.
 * 
 * @param  array  $in Array of options
 * @return string     "Serialized" options
 */
function encodePostExpired($in)
{
    $out = array();
    foreach($in as $k => $v) {
        $out[] = $k . '|' . $v;
    }

    return implode(';', $out);
}

/**
 * Decode Expired Date settings
 * 
 * @param  string $in "Serialized" options
 * @return array      Array of options
 */
function decodePostExpired($in)
{
    $out = array();
    foreach(explode(';', $in) as $v) {
        $v = explode('|', $v);
        $out[$v[0]] = $v[1];
    }

    return $out;
}