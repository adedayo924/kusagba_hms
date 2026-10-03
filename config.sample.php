<?php
/**
 * Configuration file.
 * Copy to config.php after installation, or run the web installer at /install/.
 */

define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'hospital_db');
define('DB_USER', 'root');
define('DB_PASS', '');

/* Base URL of the app. Use '' when installed at the domain root
   (e.g. InfinityFree subdomain), or '/subfolder' under a folder. */
define('BASE_URL', '/kusagba');

define('APP_NAME', 'Kusagba Hospital');
define('APP_CURRENCY', 'NGN');
define('TIMEZONE', 'Africa/Lagos');

/* Never enable APP_DEBUG on a public server: it prints exception messages and
   stack traces (file paths, SQL) to the browser. Errors are always written to
   the PHP error log regardless of this setting. */
define('APP_DEBUG', false);

define('INSTALLED', true);