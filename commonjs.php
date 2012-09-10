<?php

// Self executing function
$commonJsAPI = call_user_func(function()
{

    $_definitionsRegistry = array();
    $_modulesRegistry = array();

    $config = array(
        'basePath' => __DIR__
    );

    $_currentResolvedModuleDir = null;

    $_findModule = function ($modulePath) use (&$config, &$_currentResolvedModuleDir)
    {
        if (!preg_match('/\.php$/', $modulePath)) {
            $modulePath = $modulePath . '.php';
        }

        if ('./' === substr($modulePath, 0, 2) || '../' === substr($modulePath, 0, 3)) {
            // Relative path
            if (null === $_currentResolvedModuleDir) {
                //defaults to $config['basePath'] if we are not already in a Module context
                $_currentResolvedModuleDir = $config['basePath'];
            }
            $absoluteFilePath = $_currentResolvedModuleDir . DIRECTORY_SEPARATOR . $modulePath;
        } else {
            // Absolute path
            $absoluteFilePath = $config['basePath'] . DIRECTORY_SEPARATOR . $modulePath;
        }
        //TODO: handle "php_modules/" recursive resolution


        if (file_exists($absoluteFilePath)) {

            return $absoluteFilePath;
        }

        return null;
    };

    $_triggerModule = function ($moduleFilePath) use (&$require, &$_currentResolvedModuleDir)
    {
        // Env setup...
        $module = array();
        $exports = array();
        $moduleTrigger = function () use ($moduleFilePath, &$require, &$module, &$exports)
        {
            require $moduleFilePath;
        };

        // Go!
        $_currentResolvedModuleDir = dirname($moduleFilePath);
        call_user_func($moduleTrigger);

        if (isset($module['exports'])) {

            return $module['exports'];
        } else {

            return $exports;
        }
    };

    /**
     * @param string $modulePath
     * @param callable $moduleDefinition
     * @public
     */
    $define = function ($modulePath, \Closure $moduleDefinition) use (&$_definitionsRegistry)
    {
        $_definitionsRegistry[$modulePath] = $moduleDefinition;
    };

    /**
     * @param string $modulePath
     * @public
     * @return mixed
     * @throw \Exception
     */
    $require = function ($modulePath) use (&$_definitionsRegistry, &$_modulesRegistry, &$_findModule, &$_triggerModule)
    {
        if (isset($_modulesRegistry[$modulePath])) {

            return $_modulesRegistry[$modulePath];//previously resolved Module
        }

        if (isset($_definitionsRegistry[$modulePath])) {
            // First "define()" module definition resolution: after that, it will be resolved with the modules registry
            $moduleDefinitionResult = call_user_func($_definitionsRegistry[$modulePath]);
            $_modulesRegistry[$modulePath] = $moduleDefinitionResult;

            return $moduleDefinitionResult;
        }

        // Okay, let's resolve this Module...
        $moduleFilePath = $_findModule($modulePath);
        if (null === $moduleFilePath) {
            throw new Exception('Unresolvable module "'.$modulePath.'"!');
        }

        $moduleResolution = $_triggerModule($moduleFilePath);
        $_modulesRegistry[$modulePath] = $moduleResolution;//this Module won't have to be resolved again

        return $moduleResolution;
    };

    return array(
        'define' => $define,
        'require' => $require,
        'config' => &$config,
    );
});

return $commonJsAPI;
