<?php

class Foo {

    function __toString()
    {
        return __CLASS__;
    }

}

$module['exports'] = __NAMESPACE__.'\\Foo';