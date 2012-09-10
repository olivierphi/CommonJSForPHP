<?php

class Logger
{
    public function __toString ()
    {
        return '['.__CLASS__.']';
    }
}

$module['exports'] = new Logger();