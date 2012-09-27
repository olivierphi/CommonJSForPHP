# CommonJS for PHP

A simple CommonJS spec implementation for PHP 5.3+.

[![build status](https://secure.travis-ci.org/DrBenton/CommonJSForPHP.png)](http://travis-ci.org/DrBenton/CommonJSForPHP)

It fits in a single PHP file (≈ 150 lines of effective code) and allows a simple and easy application structure, based on
the CommonJS "Module" design pattern. You might already know this pattern if you have ever worked with
[Node.js](http://nodejs.org/) (server-side Javascript) or [RequireJS](http://requirejs.org/) (client-side Javascript).

From [JavaScript Growing Up](http://fr.slideshare.net/davidpadbury/javascript-growing-up):
> CommonJS introduced a simple API for dealing with modules:

> * "require" for importing a module.
> * "exports" for exposing stuff from a module
>


This PHP implementation also supports two features inspired by RequireJS:
* _defined-by-Closures_ Modules, with the [$define](#define) function
* resources [Plugins](#plugins) (a simple "JSON decoder" is bundled as a sample).

It comes with a "Folder as Modules" feature too, inspired by Node.js.

## Why CommonJS for PHP ?

* CommonJS "Module" pattern is simple and efficient ; it lets you quickly create easily understandable and flexible code.
* Between 2 beautiful projects based on Symfony, Zend Framework, Slim, Silex or whatever modern
[PSR-0](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md) heavy Object Oriented framework, have
some rest with simple "good ol' procedural" PHP codestyle!
* Feel comfortable in PHP when you're back from a Node.js or front-end [AMD](https://github.com/amdjs/amdjs-api/wiki/AMD) project.
* CommonJS Module pattern acts as a very simple Service Locator and lazy-loaded dependencies resolver.
* Have fun with isolated PHP code parts! Every Module runs in an automatically generated Closure, and you can freely create
variables and Closures within your Modules without without fearing a pollution of the PHP global space, nor collisions with
other Modules code.
* All your Modules code run in a "Closure sandbox", and Modules communicate between each other only through their
```$require()``` function and ```$exports``` variable.
* Every Module content is run only once - the first time it is required.
* CommonJS for PHP is perfectly interoperable with PSR-0 classes. You can use Symfony 2 or Zend Framework or yet other
components in your Modules. It can be used with libraries managed by Composer as well.
* This CommonJS implementation for PHP can be used as a micro-framework for quick little projects...
* ...But it can used for large serious projects too ; thousands of Node.js and AMD developers use this CommonJS
Module pattern everyday.

The code is willfully 100% procedural and Closures-based - this way, this CommonJS spec implementation code looks
like Javascript code :-)

## Synopsis

```php
// ******************************* file "index.php"
// CommonJs setup
$commonJS = include './commonjs.php';
$define = $commonJS['define'];
$require = $commonJS['require'];
$commonJS['config']['basePath'] = __DIR__ '/modules';

// Custom plugin?
$commonJS['plugins']['yaml'] = __DIR__ . '/commonsjs-plugin.yaml.php';

// Modules are files ; but you can define "modules-as-Closures" too
$define('logger', function($require) {
    return function($msg) {
        syslog(LOG_DEBUG, $msg);
    };
});

// Boostrap module trigger!
$require('app/bootstrap');


// ******************************* file "modules/app/bootstrap.php"
/**
 * Note that "$define", "$require", "$exports" and "$module"
 * are automatically globally defined in the Module!
 */
$config = $require('yaml!../config/config.yml');//we use the YAML plugin with a relative path
$logger = $require('logger');
$requestBridge = $require('../vendor/symfony-bridge/request');
$router = $require('app/router');

$request = $requestBridge->createFromGlobals();
list($targetControllerModule, $targetAction) = $router->resolveRequest($request);
$config['debug'] && $logger('** $targetControllerModule='.$targetControllerModule);
$require($targetControllerModule)->$targetAction();
```

## API

### CommonJS environment initialization

To initialize the CommonJS environment you simply have to do this:

```php
$commonJS = include './commonjs.php';
```

The ```$commonJS``` returned associative Array contains the following keys :
* **define**: the CommonJS ```define()``` Closure ; outside Modules, you can alias it with ```$define = $commonJS['define'];```
* **require**: the CommonJS ```require()``` Closure ; outside Modules, you can alias it with ```$require = $commonJS['require'];```
* **config**: a simple config associative Array with 2 keys:
    * **basePath**: the base path of your Modules. Every Module you ```require()``` without a relative path will be located
    in this directory path. Default is the _commonjs.php_'s ```__DIR__```
    * **modulesExt**: the extension to add to the requested Modules path. Default: _'.php'_
    * **folderAsModuleFileName**: the file name for "folders as Modules". Default: _'index.php'_
* **plugins**: this associative Array is the CommonJS for PHP "a la RequireJS" plugins registry. Keys are plugin prefixes,
values are paths to plugins files. See [Plugins](#plugins) section for more details.

Note that you can also use an array for **config['basePath']** : this allow you to define multiple modules root paths :

```php
$commonJS = include './commonjs.php';
$commonJS['config']['basePath'] = array(
    __DIR__.'/app/modules',
    __DIR__.'/vendor/symfony-bridge/modules',
);
```

### require()

Triggers the resolution of a Module. All Modules resolutions are triggered only once, the first time they are requested.
All subsequent calls to this Module will return the same value, retrieved from an internal data cache.

They are 4 types of Module resolutions:
* Modules mapped to a Closure through the ```define()``` function. When the required module path matches a previously defined
Module path, the Closure is triggered and we fetch its returned value.
* Modules mapped to files. This is the most common Module type. The module path is resolved, and the matching PHP
file is triggered in a CommonJS environment.
    * The module path resolution follows this rule: if the module path begins with **./** or **../**, the PHP file path will
    be resolved relatively to the current Module path. Otherwise, the Module path is just appended to the CommonJS
    config "basePath" path.
    * Don't use the ".php" file extension in your required modules paths. It will be automatically appended to the resolved
    file path.
* Modules mapped to folders. It is very close to the previous "Modules mapped to files" behaviour, excepted that you only
have to use a folder path as the ```$require```-ed module file path. If the folder contains a "index.php" file, this file
will be used as the file Module.
* Modules mapped to plugins. When a module path contains a prefix followed by an exclamation mark, it is considered as a
plugin call. The part before the "!" is the plugin name, and the part after the "!" is the resource name.
See [Plugins](#plugins) section for more detail.

```php
// All Module types:

// Closure-mapped Module:
$define('config', function() { return array('debug' => true, 'appPath' => __DIR__); });
$config = $require('config');

// Absolute Module file resolution: (absolute, but relative to the CommonJS "config['basePath']" path)
// --> will trigger the "app/logger.php" Module file code
$logger = $require('app/logger');

// Relative Module file resolution: (relative to the Module which calls "$require()")
// --> will trigger the "../mailer.php" Module file code
$logger = $require('../mailer');

// Folder as Module resolution: (works with absolute or relative paths)
// --> will trigger the "symfony-bridge/request/index.php" Module file code
$logger = $require('symfony-bridge/request');

// Plugin call:
$myModuleConfig = $require('json!./module-config.json');
```

### Modules scope

This is the heart of the CommonJS coolness! Every Module is isolated from others Modules, and interact with them only
through its ```$require()``` method (for input) and its ```$exports``` array (for output).

In a Module you can create vars and Closures without fearing a pollution of the PHP global space, nor collisions with
other Modules code. Every time a Module is triggered, its code is automatically embedded is a generated PHP Closure.

In this "Closure sandbox", your Module have automatically access to the following vars:  **(and only to them)**
* **$require**: the ```$require``` function. Let's you access to other Modules exports.
* **$define**: the ```$define``` function. You can dynamically create new "mapped to Closures" Modules definitions in your Modules.
* **$exports**: this is an empty Array. Add key/values couples to this Array, and they're will be automatically available
in other Modules.
* **$module**: is mainly used for direct Modules export value. If you want to export a single value from a Module,
use ```$module['exports'] = $mySingleExportedValue;```.
Additionally, you have access to ```$module['id']``` and
```$module['uri']``` properties, according to [CommonJS spec](http://wiki.commonjs.org/wiki/Modules/1.1.1).
```uri``` is the [realpath](php.net/manual/fr/function.realpath.php) of the Module file,
and ```id``` is the absolute Module path of the Module :

> The "id" property must be such that require(module.id) will return the exports object from which the module.id originated
> (That is to say module.id can be passed to another module, and requiring that must return the original module).

### define()

The ```define()``` function lets you create Modules resolved by Closures. The first param is the path of the Module
you define, and the second one is a Closure. The first time this Module path is ```$require```-ed, this Closure is triggered
and its return value is used a the Module value resolution. Subsequent calls will return the same value, managed by
an internal data cache.

```php
$define('config', function() {
    return array('debug' => true, 'appPath' => __DIR__);
});

// PHP 5.4 (needs function array dereferencing)
echo $require('config')['debug'];//--> 'true'
// PHP 5.3
$config = $require('config')
echo $config['debug'];//--> 'true'
```

The triggered Closure can accept up to 3 injected params: ```$require```, ```$exports``` and ```$module```.
```$require``` let you require other modules from your Closure, while ```$exports``` and ```$module``` allows you
to define the Module value resolution in a "CommonJS way":

```php
$define('app/logger', function($require, &$exports, &$module) {
    $config = $require('config');
    $logger = new Monolog\Logger('app');
    if ($config['debug']) {
        $logger->pushHandler(new Monolog\Handler\StreamHandler($config['appPath'] . 'logs/app.log'));
    }
    $module['exports'] = $logger;
});

$logger = $require('app/logger');
```

If you want to use that form instead of a simple ```return```, be aware that because call-time pass by reference
has been removed in PHP 5.4, you have to use ```&$exports``` and ```&$module``` and not just just
```$exports / $module``` in your definition Closure params.
Although it is possible to omit the "&" in PHP 5.3, it's better to think about the future :-)

### Plugins

CommonJS for PHP is bundled with a minimalist ["a la RequireJS" plugin system](http://requirejs.org/docs/plugins.html).
A plugin is defined by a unique name and a resource path. They are triggered when the ```$require()``` function parameter is
a path containing an exclamation mark. The part before the "!" is the plugin name, and the part after the "!" is the
resource path: ```[plugin name]![resource path]```.

Like Modules, plugins are triggered in a generated Closure, and run in their own scope. This scope only contains the
```$require``` and ```$resourcePath``` variables. With ```$require()``` you can have access to other Modules and plugins,
while ```$resourcePath``` is the part of the required Module path after the "!". The resource path is resolved in the
same way than Modules: they can be relative to the Module which triggers the plugin or absolute.

Like ```$require```-ed Modules (which are triggered only once), plugins already triggered with the same resolved resource
path will return a cached result value for subsequent calls.

```php
// A YAML sample plugin

// ******************************* file "app/plugins/commonsjs-plugin.yaml.php"
$yamlParser = new \Symfony\Component\Yaml\Parser();
return $yamlParser->parse(file_get_contents($resourcePath));


// ******************************* file "app/bootstrap.php"
$commonJS['plugins']['yaml'] = __DIR__ . '/app/plugins/commonsjs-plugin.yaml.php';


// ******************************* file "app/config.php"
$config = $require('yaml!./resources/config.yml');
```

## More info

As the source code of this "CommonJS for PHP" library is a lot shorter than this README, you can
[have a look at it](https://github.com/DrBenton/CommonJSForPHP/blob/master/commonjs.php)
for further information :-)

You can also look at [Unit Tests](https://github.com/DrBenton/CommonJSForPHP/blob/master/tests/CommonJSTest.php),
since they cover a rather large scope of use cases.

## License
(The MIT License)

Copyright (c) 2012 Olivier Philippon <https://github.com/DrBenton>

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation
files (the 'Software'), to deal in the Software without restriction, including without limitation the rights to use, copy,
modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the
Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED 'AS IS', WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO
THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS
OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
