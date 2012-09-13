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
        $this->assertEquals(500, $require('./module-dir/package1/relative-upper-module-consumer'));
    }

    public function testCommonJsDefine ()
    {
        $commonJs = include __DIR__ . '/../commonjs.php';
        $define = $commonJs['define'];
        $require = $commonJs['require'];
        $commonJs['config']['basePath'] = __DIR__;

        $that = &$this;
        $define('definition1', function () {
           return 10;
        });
        $define('definition2', function ($require, &$exports, &$module) use ($that) {
            $that->assertEquals(10, $require('definition1'));
            $module['exports'] = 20;
        });
        $define('definition3', function ($require, &$exports, &$module) use ($that) {
            $that->assertEquals(20, $require('definition2'));
            $exports['value1'] = 30;
        });
        $this->assertEquals(20, $require('definition2'));
        $definition3 = $require('definition3');
        $this->assertEquals(30, $definition3['value1']);
    }

    public function testModuleCodeIsTriggeredOnlyOnce ()
    {
        $commonJs = include __DIR__ . '/../commonjs.php';
        $require = $commonJs['require'];
        $commonJs['config']['basePath'] = __DIR__;

        $this->assertEquals(1, $require('module-dir/incrementer-module'));
        $this->assertEquals(1, $require('module-dir/incrementer-module'));
    }

    public function testOtherModulesFileExtension ()
    {
        $commonJs = include __DIR__ . '/../commonjs.php';
        $require = $commonJs['require'];
        $commonJs['config']['basePath'] = __DIR__;
        $commonJs['config']['modulesExt'] = '.inc';

        $this->assertEquals(100, $require('module-dir/module-with-another-ext'));
    }

    public function testBundledJsonPlugin ()
    {
        $commonJs = include __DIR__ . '/../commonjs.php';
        $require = $commonJs['require'];
        $commonJs['config']['basePath'] = __DIR__;

        $this->assertEquals(array('key1' => 100, 'key2' => 200), $require('json!module-dir/resources/data.json'));
    }

    public function testCustomPlugin ()
    {
        $commonJs = include __DIR__ . '/../commonjs.php';
        $require = $commonJs['require'];
        $commonJs['config']['basePath'] = __DIR__;
        $commonJs['plugins']['fileRev'] = __DIR__ .'/module-dir/custom-extensions/commonjs-ext.file-reverser.php';

        $this->assertEquals('notneBrD', $require('fileRev!./module-dir/resources/simple-text.txt'));
    }

    public function testModuleIdAndUri ()
    {
        $commonJs = include __DIR__ . '/../commonjs.php';
        $require = $commonJs['require'];
        $commonJs['config']['basePath'] = __DIR__;

        $expectedId = '/module-dir/module-id-and-uri-exporter';
        $expectedUri = realpath(__DIR__.'/module-dir/module-id-and-uri-exporter.php');

        $result = $require('./module-dir/module-id-and-uri-exporter');
        $this->assertEquals($expectedId, $result['id']);
        $this->assertEquals($expectedUri, $result['uri']);
    }

    public function testFolderAsModule ()
    {
        $commonJs = include __DIR__ . '/../commonjs.php';
        $require = $commonJs['require'];
        $commonJs['config']['basePath'] = __DIR__;

        $this->assertEquals(500, $require('/module-dir/folder-as-module'));
    }
}
