<?php
/**
 * Configuration file for the application.
 * use with require_once('config.php');
 */

define('EMBEDDING_SERVER_URL', 'http://localhost:8041/embed');
define('EMBEDDING_MODEL', 'all-MiniLM-L6-v2');
define('KI_SERVER_URL', 'http://localhost:8041/generate');
define('KI_MODEL', 'SmolLM2-135M-Instruct');
define('KI_MAX_TOKENS', 1000);
define('KI_TEMPERATURE', 0.7);
define('DOKUWIKI_PAGES_DIR', '/var/www/php/dokuwiki/data/pages');
