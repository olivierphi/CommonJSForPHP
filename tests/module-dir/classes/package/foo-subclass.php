<?php

$require('./foo');

class Foo extends \CommonJS\Module\module_dir\classes\package\foo\Foo {

    function __toString()
    {
        return __CLASS__;
    }

}

$module['exports'] = __NAMESPACE__.'\\Foo';