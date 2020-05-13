<?php

// Configuration directory.
$config_directories[CONFIG_SYNC_DIRECTORY] = '../conf/cmi';

// Hash salt.
$settings['hash_salt'] = 'oNbAEGiCIhNhXU-hNBmZMLSSR11HUqnXVUdG9IwMDFBft67IXRV4xjao1W20AQ_O5pRQ07PNMg';

// Database connection.
$databases['default']['default'] = [
  'driver' => 'mysql',
  'database' => getenv('DRUPAL_DB_NAME'),
  'username' => getenv('DRUPAL_DB_USER'),
  'password' => getenv('DRUPAL_DB_PASS'),
  'host' => getenv('DRUPAL_DB_HOST'),
  'port' => getenv('DRUPAL_DB_PORT'),
  'prefix' => '',
];

// Trusted Host Patterns, see https://www.drupal.org/node/2410395 for more information.
// If your site runs on multiple domains, you need to add these domains here
$settings['trusted_host_patterns'] = [
  '^' . str_replace('.', '\.', getenv('HOSTNAME')) . '$',
];

// Public files path
$settings['file_public_path'] = 'sites/default/files/public';

// Private files path
$settings['file_private_path'] = realpath(__DIR__ . '/../../../files_private');

// Temp directory
$settings['file_temp_path'] = '/tmp';
