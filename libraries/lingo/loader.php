<?php

// Define Lingo path constant
if (!defined('JPATH_LINGO')) {
    define('JPATH_LINGO', dirname(__FILE__));
}

class LingoLoader
{

    public static function init()
    {
        //Registering Lingo libraries prefix
        JLoader::registerPrefix('Lingo', JPATH_LINGO);

        //Registering SQL parser Namespace
        JLoader::registerNamespace('PHPSQL', JPATH_LINGO . '/database/sqlparser');
    }

}
