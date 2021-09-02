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

$this->registerModule(
    'Expired entries',
    'Change entries options at a given date', 
    'Jean-Christian Denis and Contributors',
    '2021.08.20.1',
    [
        'requires' => [['core', '2.19']],
        'permissions'   => 'usage,contentadmin',
        'type'          => 'plugin',
        'support'       => 'https://github.com/JcDenis/postExpired',
        'details'       => 'https://plugins.dotaddict.org/dc2/details/postExpired',
        'repository' => 'https://raw.githubusercontent.com/JcDenis/postExpired/master/dcstore.xml'
    ]
);