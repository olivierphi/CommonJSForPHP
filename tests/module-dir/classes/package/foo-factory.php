<?php

class Foo {

    function __toString()
    {
        return __CLASS__;
    }

}

$exports['getInstance'] = function ()
{
    return new Foo();
};