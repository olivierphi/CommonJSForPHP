<?php

$module['exports'] = array(
    $module['resolve']('module-dir/direct-export'),
    $module['resolve']('./a-module'),
    $module['resolve']('unfound-module'),
    $module['resolve']('./relative-unfound-module'),
    $module['moduleExists']('module-dir/direct-export'),
    $module['moduleExists']('./a-module'),
    $module['moduleExists']('unfound-module'),
    $module['moduleExists']('./relative-unfound-module'),
);