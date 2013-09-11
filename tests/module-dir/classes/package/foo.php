<?php

class Foo {

    function __toString()
    {
        return __CLASS__;
    }

    public function sayHi()
    {
        return "I'm an instance of another Foo class, automatically namespaced.";
    }

}

$module['exports'] = __NAMESPACE__.'\\Foo';