<?php

/**
 * Load services definition file.
 */
$settings['container_yamls'][] = __DIR__ . '/services.yml';

/**
 * Include the Pantheon-specific settings file.
 *
 * n.b. The settings.pantheon.php file makes some changes
 *      that affect all environments that this site
 *      exists in.  Always include this file, even in
 *      a local development environment, to ensure that
 *      the site settings remain consistent.
 */
$settings['hash_salt'] = '08DnwRV7OYjCvhT8XYrD53VpieOKEFLMJj6C1_HauJLI0keDw_hU2jAwCnggbLSwoHBHSE_0Jw';

/**
 * Place the config directory outside of the Drupal root.
 */
$settings['config_sync_directory'] = '../config/default';


/**
 * Exclude a few configs from exporting/importing.
 */
$settings['config_exclude_modules'] = ['devel', 'tb_megamenu', 'webform'];

/**
 * HTTP Client config.actua
 */
$settings['http_client_config']['timeout'] = 60;
$settings['file_public_base_url'] = 'http://' . $_SERVER['HTTP_HOST'] . '/sites/default/files';

//D7 DB config
if (!isset($databases))
    $databases = array();

$databases['default']['default'] = array(
    'driver' => 'mysql',
    'database' => 'vvc',
    'username' => 'root',
    'password' => '',
    'host' => '127.0.0.1',
    'port' => 3307 );

/**
 * If there is a local settings file, then include it
 */
$local_settings = __DIR__ . "/settings.local.php";
if (file_exists($local_settings)) {
    include $local_settings;
}

