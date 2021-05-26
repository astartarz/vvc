<?php

// @codingStandardsIgnoreFile


$databases = [];
$config_directories = [];
$settings['hash_salt'] = 'mGsdPX72eY8E-0mjPPsVYPe_lGNJuDZQq6ug096GONr1bV-srnV5LNhLumMd1pXR2DlAo6MknA';
$settings['update_free_access'] = FALSE;

$settings['file_scan_ignore_directories'] = [
  'node_modules',
  'bower_components',
];

$settings['entity_update_batch_size'] = 50;

 if (file_exists($app_root . '/' . $site_path . '/settings.local.php')) {
   include $app_root . '/' . $site_path . '/settings.local.php';
 }
$config_directories['sync'] = 'sites/default/files/config_eKycI6T9tM9IzoGZpJv0Z8qu8vxoGX9ogLCH4Qqvbj4i7If3-nADF9gK__b2oTDZ_JJCDm6Tjg/sync';
$settings['install_profile'] = 'standard';

if (file_exists($app_root . '/' . $site_path . '/settings.local.php')) {
  include $app_root . '/' . $site_path . '/settings.local.php';
}

$settings['container_yamls'][] = $app_root . '/' . $site_path . '/services.yml';

// <DDSETTINGS>
// Please don't edit anything between <DDSETTINGS> tags.
// This section is autogenerated by Acquia Dev Desktop.
if (isset($_SERVER['DEVDESKTOP_DRUPAL_SETTINGS_DIR']) && file_exists($_SERVER['DEVDESKTOP_DRUPAL_SETTINGS_DIR'] . '/loc_kwall_dd.inc')) {
  require $_SERVER['DEVDESKTOP_DRUPAL_SETTINGS_DIR'] . '/loc_kwall_dd.inc';
}
// </DDSETTINGS>
