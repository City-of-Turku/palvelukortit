<?php

// Drupal Configurator. Detects the Cloud and converts ENV vars to generalized ones.

/**
 * Some servers don't allow setting HOSTNAME globally, use then DRUPAL_HOSTNAME.
 */

if (getenv('DRUPAL_HOSTNAME')) {
  putenv('HOSTNAME='. getenv('DRUPAL_HOSTNAME'));
}

/**
 * ENV. By default it's least open, so prod.
 */

$APP_ENV = getenv('APP_ENV') ?: 'prod';

/**
 * Load/add files (if exist) in following order:
 */

foreach (['all', $APP_ENV, 'local'] as $set) {
  // all.settings.php, dev.settings.php and local.settings.php
  if (file_exists(__DIR__ . '/' . $set . '.settings.php')) {
    include __DIR__ . '/' . $set . '.settings.php';
  }

  // all.services.yml, dev.services.yml and local.services.yml
  if (file_exists(__DIR__ . '/' . $set . '.services.yml')) {
    $settings['container_yamls'][] = __DIR__ . '/' . $set . '.services.yml';
  }
}
