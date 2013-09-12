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
// This way, we do not create any single global variable or function
return call_user_func(function()
{

    $_definitionsRegistry = array();
    $_modulesRegistry = array();

    $config = array(
        // Default config
        'basePath' => __DIR__,
        'modulesExt' => '.php',
        'folderAsModuleFileName' => 'index.php',
        'autoNamespacing' => false,
    );
    $plugins = array(
        // Default plugins
        'json' => __DIR__ . '/plugins/commonsjs-plugin.json.php'
    );

    $_currentResolvedModuleDir = null;

    $_getResourceFullPath = function ($modulePath, $fileExtToAdd = '') use (&$config, &$_searchResource, &$_currentResolvedModuleDir)
    {
        static $absolutePathsResolutionsCache = array();

        $isRelativePath = ('./' === substr($modulePath, 0, 2) || '../' === substr($modulePath, 0, 3));

        $pathCacheId = $modulePath . $fileExtToAdd;
        if (!$isRelativePath && isset($absolutePathsResolutionsCache[$pathCacheId])) {

            return $absolutePathsResolutionsCache[$pathCacheId];
        }

        $basePaths = is_array($config['basePath']) ? $config['basePath'] : array($config['basePath']);

        if (null === $_currentResolvedModuleDir) {
            //defaults to $config['basePath'][0] if we are not already in a Module context
            $_currentResolvedModuleDir = $basePaths[0];
        }

        // Relative or absolute path?
        if ($isRelativePath) {
            // Relative path
            $fullModulePath = $_currentResolvedModuleDir . DIRECTORY_SEPARATOR . $modulePath;
            $resolvedModulePath = $_searchResource($fullModulePath, $fileExtToAdd);
        } else {
            // Absolute path search from $basePaths
            foreach ($basePaths as $currentBasePath) {
                $fullModulePath = $currentBasePath . DIRECTORY_SEPARATOR . $modulePath;
                $resolvedModulePath = $_searchResource($fullModulePath, $fileExtToAdd);
                if (null !== $resolvedModulePath) {
                    break;
                }
            }
        }

        $absolutePathsResolutionsCache[$pathCacheId] = $resolvedModulePath;

        return $resolvedModulePath;//can be null if no matching module path has been found
    };

    $_moduleExists = function ($modulePath) use (&$_getResourceFullPath, &$config)
    {
        return (boolean) $_getResourceFullPath($modulePath, $config['modulesExt']);
    };

    $_searchResource = function ($searchedResourceFullPath, $fileExtToAdd = '') use (&$config)
    {
        static $resolutionsCache = array();

        $moduleCacheId = $searchedResourceFullPath . '|' . $fileExtToAdd;
        if (isset($resolutionsCache[$moduleCacheId])) {

            return $resolutionsCache[$moduleCacheId];
        }

        $resolvedModulePath = null;
        if (is_file($searchedResourceFullPath . $fileExtToAdd)) {
            // This is a regular "file Module" ; we just add the file extension
            $resolvedModulePath = $searchedResourceFullPath . $fileExtToAdd;
        } else if (is_dir($searchedResourceFullPath)) {
            $directoryModulePath = $searchedResourceFullPath . DIRECTORY_SEPARATOR . $config['folderAsModuleFileName'];
            if (file_exists($directoryModulePath)) {
                // Yeah! This is a "folder as Module"
                $resolvedModulePath = $directoryModulePath;
            }
        }

        if (null !== $resolvedModulePath) {
            $resolvedModulePath = str_replace('/', DIRECTORY_SEPARATOR, $resolvedModulePath);
            $resolvedModulePath = realpath($resolvedModulePath);
        }

        $resolutionsCache[$moduleCacheId] = $resolvedModulePath;

        return $resolvedModulePath;
    };

    $_triggerModule = function ($moduleFilePath) use (&$config, &$require, &$define, &$_currentResolvedModuleDir, &$_getResourceFullPath, &$_moduleExists)
    {
        // Env setup...
        $module = array();
        $module['id'] = str_replace($config['basePath'], '', $moduleFilePath);//can handle string or array "$config['basePath']" :-)
        $module['id'] = str_replace(
            array(DIRECTORY_SEPARATOR, $config['modulesExt']),
            array('/', ''),
            $module['id']
        );
        $module['uri'] = $moduleFilePath;
        $module['resolve'] = function ($modulePath) use ($_getResourceFullPath, $config)
        {
            return $_getResourceFullPath($modulePath, $config['modulesExt']);
        };
        $module['moduleExists'] = function ($modulePath) use ($_moduleExists)
        {
            return $_moduleExists($modulePath);
        };
        $exports = array();

        if ($config['autoNamespacing']) {

            $moduleTrigger = function () use ($moduleFilePath, &$require, &$define, &$module, &$exports)
            {
                // Yes, you're right : I probably deserve death for this "eval()" usage...
                // But I have not been able to find another way of using properly isolated classes in this CommonJS Modules PHP implementation :-)
                // 1) Let's create a unique namespace, based on the Module ID and prefixed with "CommonJS\Module"
                $moduleDynamicNamespace = 'CommonJS\Module' . str_replace('/', '\\', $module['id']);
                $moduleDynamicNamespace = preg_replace('|[\s-]|i', '_', $moduleDynamicNamespace);
                // 2) The PHP Module file content is read...
                $moduleFileContent = file_get_contents($moduleFilePath);
                // 3) ...and we add a dynamic namespace before it
                $moduleFileContent = 'namespace '.$moduleDynamicNamespace.'; ?>'.$moduleFileContent;
                // 4) Now we can actually trigger this PHP Module code, properly isolated in a unique namespace!
                eval($moduleFileContent);
            };

        } else {

            $moduleTrigger = function () use ($moduleFilePath, &$require, &$define, &$module, &$exports)
            {
                include $moduleFilePath;
            };

        }


        // Go!
        $previousResolvedModuleDir = $_currentResolvedModuleDir;//current dir backup...
        $_currentResolvedModuleDir = dirname($moduleFilePath);
        call_user_func($moduleTrigger);
        $_currentResolvedModuleDir = $previousResolvedModuleDir;//..and restore!

        // Result analysis
        if (isset($module['exports'])) {

            return $module['exports'];
        } else {

            return $exports;
        }
    };

    $_triggerDefine = function ($definitionPath) use (&$_definitionsRegistry, &$require)
    {
        // Env setup...
        $module = array();
        $exports = array();
        $defineArgs = array(&$require, &$exports, &$module);

        // Go!
        $definitionResult = call_user_func_array($_definitionsRegistry[$definitionPath], $defineArgs);

        // Result analysis
        if (null === $definitionResult) {
            if (isset($module['exports'])) {
                $definitionResult = $module['exports'];
            } else if (sizeof($exports) > 0) {
                $definitionResult = $exports;
            }
        }

        return $definitionResult;
    };

    $_triggerPlugin = function ($extensionName, $resourcePath) use (&$plugins, &$require, &$_getResourceFullPath)
    {
        static $pluginsResolutionsCache = array();

        $extensionFilePath = $plugins[$extensionName];
        $resourcePath = $_getResourceFullPath($resourcePath);

        $pluginCacheId = $extensionFilePath . '|' . $resourcePath;
        if (isset($pluginsResolutionsCache[$pluginCacheId])) {

            return $pluginsResolutionsCache[$pluginCacheId];
        }

        $extensionTrigger = function () use ($extensionFilePath, &$require, $resourcePath)
        {
            return require $extensionFilePath;
        };
        $extensionResult = call_user_func($extensionTrigger);

        $pluginsResolutionsCache[$pluginCacheId] = $extensionResult;

        return $extensionResult;
    };

    /**
     * @param string $definitionPath
     * @param callable $moduleDefinition
     * @public
     */
    $define = function ($definitionPath, \Closure $moduleDefinition) use (&$_definitionsRegistry, &$_modulesRegistry)
    {
        if (isset($_modulesRegistry[$definitionPath])) {
            unset($_modulesRegistry[$definitionPath]);//clear previous defined module result cache
        }

        $_definitionsRegistry[$definitionPath] = $moduleDefinition;
    };

    /**
     * @param string $modulePath
     * @public
     * @return mixed
     * @throw \Exception
     */
    $require = function ($modulePath) use (&$config, &$plugins, &$_definitionsRegistry, &$_modulesRegistry,
        &$_triggerModule, &$_getResourceFullPath, &$_triggerDefine, &$_triggerPlugin, &$_currentResolvedModuleDir)
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

        // Do we use a plugin on this Module path (i.e. do we have a [prefix]![resource path] pattern) ?
        if (preg_match('|^(\w+)!([a-z0-9/_.-]+)$|i', $modulePath, $matches)) {
            $pluginName = $matches[1];
            $resourcePath = $matches[2];
            if (!isset($plugins[$pluginName])) {
                throw new Exception('Unregistered plugin  "'.$pluginName.'" (resource path: "'.$resourcePath.'")!');
            }
            $moduleDefinitionResult = $_triggerPlugin($pluginName, $resourcePath);

            return $moduleDefinitionResult;
        }

        // Regular Module resolution
        $fullModulePath = $_getResourceFullPath($modulePath, $config['modulesExt']);
        if (null === $fullModulePath) {
            throw new Exception('Unresolvable module "'.$modulePath.'" (from path: '.$_currentResolvedModuleDir.')!');
        }

        if (isset($_modulesRegistry[$fullModulePath])) {
            return $_modulesRegistry[$fullModulePath];//previously resolved Module
        }

        // Okay, let's trigger this Module!
        $moduleResolution = $_triggerModule($fullModulePath);
        $_modulesRegistry[$fullModulePath] = $moduleResolution;//this Module won't have to be resolved again

        return $moduleResolution;
    };

    return array(
        'define' => $define,
        'require' => $require,
        'config' => &$config,
        'plugins' => &$plugins,
    );
});
