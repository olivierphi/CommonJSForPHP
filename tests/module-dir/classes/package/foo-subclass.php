<?php

$require('./foo');

class Foo extends \CommonJS\Module\module_dir\classes\package\foo\Foo {

    function __toString()
    {
        return __CLASS__;
    }

    public function sayHi()
    {
        return "I'm an instance of yet another Foo class, inheriting from another Foo class, automatically namespaced (but I have to use a hardocoded parent class namespace).";
    }

}

$module['exports'] = __NAMESPACE__.'\\Foo';