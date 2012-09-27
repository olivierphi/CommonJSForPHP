<?php

$relativeModuleResult = $require('./alt-module-consumer');

$module['exports'] = $relativeModuleResult + 100;