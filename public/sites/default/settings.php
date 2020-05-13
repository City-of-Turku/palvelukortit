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
require_once 'drupal.configurator.php';

// Private files path
$settings['file_private_path'] = realpath(__DIR__ . '/../../../files_private');
