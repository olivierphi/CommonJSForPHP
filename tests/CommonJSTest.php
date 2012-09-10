<?php

class CommonJSTest extends \PHPUnit_Framework_TestCase
{

    public function testSimpleDefinition ()
    {
        $commonJs = include __DIR__ . '/../commonjs.php';
        $define = $commonJs['define'];
        $require = $commonJs['require'];
        $define('definition1', function () {
           return 10;
        });
        $this->assertEquals(10, $require('definition1'));
    }

    public function testModuleDirectExport ()
    {
        $commonJs = include __DIR__ . '/../commonjs.php';
        $require = $commonJs['require'];
        $commonJs['config']['basePath'] = __DIR__;
        $this->assertEquals('[Logger]', ''.$require('module-dir/direct-export'));
    }

    public function testModuleMultipleExports ()
    {
        $commonJs = include __DIR__ . '/../commonjs.php';
        $require = $commonJs['require'];
        $commonJs['config']['basePath'] = __DIR__;
        $services = $require('module-dir/multiple-exports');
        $this->assertEquals(100, $services['service1']);
        $this->assertEquals(200, $services['service2']);
    }

    public function testRelativeModuleResolution ()
    {
        $commonJs = include __DIR__ . '/../commonjs.php';
        $require = $commonJs['require'];
        $commonJs['config']['basePath'] = __DIR__;
        $this->assertEquals(300, $require('./module-dir/relative-module'));
    }

    public function testRecursiveModulesResolution ()
    {
        $commonJs = include __DIR__ . '/../commonjs.php';
        $require = $commonJs['require'];
        $commonJs['config']['basePath'] = __DIR__;
        $this->assertEquals(400, $require('module-dir/relative-module-consumer'));
    }

}
