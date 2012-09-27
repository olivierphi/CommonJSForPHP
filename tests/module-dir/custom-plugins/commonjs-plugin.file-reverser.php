<?php

$fileContent = file_get_contents($resourcePath);
return strrev($fileContent);