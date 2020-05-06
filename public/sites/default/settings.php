<?php

// Use druidfi/omen to auto-configure Drupal
//
// You can setup project specific configuration in this directory:
//
// ENV.settings.php and ENV.services.yml
// and
// local.settings.php and local.service.yml
//
// These files are loaded automatically if found.
//
extract((new Druidfi\Omen\DrupalEnvDetector(__DIR__))->getConfiguration());

// Hash salt.
$settings['hash_salt'] = 'oNbAEGiCIhNhXU-hNBmZMLSSR11HUqnXVUdG9IwMDFBft67IXRV4xjao1W20AQ_O5pRQ07PNMg';

// Private files path
$settings['file_private_path'] = realpath(__DIR__ . '/../../../files_private');
