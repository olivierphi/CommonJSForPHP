<?php
/*
 * This file is part of the CommonJS for PHP library.
 *
 * (c) Olivier Philippon <https://github.com/DrBenton>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CommonJS;

/**
 * Allows a Composer-based CommonJS API access
 */
class CommonJSProvider
{

    static protected $instances = array();

    static public function getInstance ($instanceName = 'default')
    {
        if (!isset(self::$instances[$instanceName])) {
            self::$instances[$instanceName] = include __DIR__ . '/../../commonjs.php';
        }

        return self::$instances[$instanceName];
    }

}
