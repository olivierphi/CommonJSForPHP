<?php

if (!isset($GLOBALS['CommonJSUnitTest']))
    $GLOBALS['CommonJSUnitTest'] = array();
if (!isset($GLOBALS['CommonJSUnitTest'][__FILE__]))
    $GLOBALS['CommonJSUnitTest'][__FILE__] = 0;

return ++$GLOBALS['CommonJSUnitTest'][__FILE__];