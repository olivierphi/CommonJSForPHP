<?php
/*
 * This file is part of the CommonJS for PHP library.
 *
 * (c) Olivier Philippon <https://github.com/DrBenton>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// Our API is entirely embedded in this "a la Javascript" self executing function
$commonJsAPI = call_user_func(function()
{

    $_definitionsRegistry = array();
    $_modulesRegistry = array();

    $config = array(
        'basePath' => __DIR__,
        'modulesExt' => '.php',
    );
    $plugins = array(
        'json' => __DIR__ . '/plugins/commonsjs-plugin.json.php'
    );

    $_currentResolvedModuleDir = null;

    $_getResourceFullPath = function ($modulePath, $fileExtToAdd = '') use (&$config, &$_currentResolvedModuleDir)
    {
        if ('./' === substr($modulePath, 0, 2) || '../' === substr($modulePath, 0, 3)) {
            // Relative path
            if (null === $_currentResolvedModuleDir) {
                //defaults to $config['basePath'] if we are not already in a Module context
                $_currentResolvedModuleDir = $config['basePath'];
            }
            $fullModulePath = $_currentResolvedModuleDir . DIRECTORY_SEPARATOR . $modulePath;
        } else {
            // Absolute path
            $fullModulePath = $config['basePath'] . DIRECTORY_SEPARATOR . $modulePath;
        }
        //TODO: handle "a la Node.js" "php_modules/" recursive resolution?

        $fullModulePath = str_replace('/', DIRECTORY_SEPARATOR, $fullModulePath);
        $fullModulePath .= $fileExtToAdd;;

        return realpath($fullModulePath);
    };

    $_triggerModule = function ($moduleFilePath) use (&$require, &$define, &$_currentResolvedModuleDir)
    {
        // Env setup...
        $module = array();
        $exports = array();
        $moduleTrigger = function () use ($moduleFilePath, &$require, &$define, &$module, &$exports)
        {
            require $moduleFilePath;
        };

        // Go!
        $previousResolvedModuleDir = $_currentResolvedModuleDir;//current dir backup...
        $_currentResolvedModuleDir = dirname($moduleFilePath);
        call_user_func($moduleTrigger);
        $_currentResolvedModuleDir = $previousResolvedModuleDir;//..and restore!

        if (isset($module['exports'])) {

            return $module['exports'];
        } else {

            return $exports;
        }
    };

    $_triggerDefine = function ($definitionPath) use (&$_definitionsRegistry, &$require) {
        $module = array();
        $exports = array();
        $defineArgs = array(&$require, &$exports, &$module);
        $definitionResult = call_user_func_array($_definitionsRegistry[$definitionPath], $defineArgs);

        if (!$definitionResult) {
            if (isset($module['exports'])) {
                $definitionResult = $module['exports'];
            } else {
                $definitionResult = $exports;
            }
        }

        return $definitionResult;
    };

    $_triggerPlugin = function ($extensionName, $resourcePath) use (&$plugins, &$require, &$_getResourceFullPath)
    {
        $extensionFilePath = $plugins[$extensionName];
        $resourcePath = $_getResourceFullPath($resourcePath);
        $extensionTrigger = function () use ($extensionFilePath, &$require, $resourcePath)
        {
            return require $extensionFilePath;
        };
        $extensionResult = call_user_func($extensionTrigger);

        return $extensionResult;
    };

    /**
     * @param string $definitionPath
     * @param callable $moduleDefinition
     * @public
     */
    $define = function ($definitionPath, \Closure $moduleDefinition) use (&$_definitionsRegistry)
    {
        $_definitionsRegistry[$definitionPath] = $moduleDefinition;
    };

    /**
     * @param string $modulePath
     * @public
     * @return mixed
     * @throw \Exception
     */
    $require = function ($modulePath) use (&$config, &$plugins, &$_definitionsRegistry, &$_modulesRegistry, &$_triggerModule, &$_getResourceFullPath, &$_triggerDefine, &$_triggerPlugin)
    {
        // "define()"-ed Module
        if (isset($_definitionsRegistry[$modulePath])) {

            if (isset($_modulesRegistry[$modulePath])) {

                return $_modulesRegistry[$modulePath];
            } else {
                // First "define()" module definition resolution: after that, it will be resolved with the modules registry
                $moduleDefinitionResult = $_triggerDefine($modulePath);
                $_modulesRegistry[$modulePath] = $moduleDefinitionResult;

                return $moduleDefinitionResult;
            }

        }

        // Do we use a plugin on this Module path (i.e. do we have "prefix!") ?
        if (preg_match('|^(\w+)!([a-z0-9/_.-]+)$|i', $modulePath, $matches)) {
            $pluginName = $matches[1];
            $resourcePath = $matches[2];
            if (!isset($plugins[$pluginName])) {
                throw new Exception('Unregistered plugin  "'.$pluginName.'" (resource path: "'.$resourcePath.'")!');
            }
            $moduleDefinitionResult = $_triggerPlugin($pluginName, $resourcePath);
            $_modulesRegistry[$modulePath] = $moduleDefinitionResult;

            return $moduleDefinitionResult;
        }

        // Regular Module
        $fullModulePath = $_getResourceFullPath($modulePath, $config['modulesExt']);
        if (isset($_modulesRegistry[$fullModulePath])) {

            return $_modulesRegistry[$fullModulePath];//previously resolved Module
        }

        // Okay, let's resolve & trigger this Module!
        if (!file_exists($fullModulePath)) {
            throw new Exception('Unresolvable module "'.$modulePath.'" (full path: '.$fullModulePath.')!');
        }

        $moduleResolution = $_triggerModule($fullModulePath);
        $_modulesRegistry[$modulePath] = $moduleResolution;//this Module won't have to be resolved again

        return $moduleResolution;
    };

    return array(
        'define' => $define,
        'require' => $require,
        'config' => &$config,
        'plugins' => &$plugins,
    );
});

return $commonJsAPI;
