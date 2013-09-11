<?php

class Foo {

    public function __toString()
    {
        return __CLASS__;
    }

    public function sayHi()
    {
        return "I'm an instance of a Foo class, automatically namespaced.";
    }

}

$module['exports'] = __NAMESPACE__.'\\Foo';