<?php
class System {
    public function __construct() {
        define('FOG_VERSION', '8203');
        define('FOG_SCHEMA', 227);
        define('FOG_BCACHE_VER',75);
        define('FOG_SVN_REVISION',5729);
        define('FOG_SVN_LAST_UPDATE', '$LastChangedDate: 2015-01-01 14:16:56 -0500 (Thu, 01 Jan 2015) $');
        define('FOG_CLIENT_VERSION', '0.11.0');
        define('PHP_VERSION_REQUIRED', '5.3.0');
        define('PHP_COMPATIBLE', version_compare(PHP_VERSION, PHP_VERSION_REQUIRED, '>='));
        define('SPACE_DEFAULT_STORAGE', '/images');
        if (PHP_COMPATIBLE === false) {
            die(sprintf(_('Your systems PHP version is not sufficient. You have version %s, version %s is required.'), PHP_VERSION, PHP_VERSION_REQUIRED));
            exit;
        }
    }
}
