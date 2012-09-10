<?php

$relativeModuleResult = $require('./relative-module');

$module['exports'] = $relativeModuleResult + 100;