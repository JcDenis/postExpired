<?php
/**
 * @file
 * @brief       The plugin postExpired definition
 * @ingroup     postExpired
 *
 * @defgroup    postExpired Plugin postExpired.
 *
 * Change entries options at a given date.
 *
 * @author      Tomtom (author)
 * @author      Jean-Christian Denis (latest)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
declare(strict_types=1);

$this->registerModule(
    'Expired entries',
    'Change entries options at a given date',
    'Jean-Christian Denis and Contributors',
    '2023.11.04',
    [
        'requires'    => [['core', '2.28']],
        'permissions' => 'My',
        'type'        => 'plugin',
        'support'     => 'https://github.com/JcDenis/' . $this->id . '/issues',
        'details'     => 'https://github.com/JcDenis/' . $this->id . '/',
        'repository'  => 'https://raw.githubusercontent.com/JcDenis/' . $this->id . '/master/dcstore.xml',
        'date'        => '2025-02-24T23:31:12+00:00',
    ]
);
