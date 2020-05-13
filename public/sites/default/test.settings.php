<?php

// Show all error messages on the site
$config['system.logging']['error_level'] = 'all';

// Expiration of cached pages on Varnish to 15 min
$config['system.performance']['cache']['page']['max_age'] = 900;

// Aggregate CSS files on
$config['system.performance']['css']['preprocess'] = 1;

// Aggregate JavaScript files on
$config['system.performance']['js']['preprocess'] = 1;

// Disabling stage file proxy on production, with that the module can be enabled even on production
$config['stage_file_proxy.settings']['origin'] = false;

// Environment indication.
$config['environment']['env'] = 'testing';

// For some reason this helped to get to login.helsinki.fi (but not yet back)
$settings['reverse_proxy'] = TRUE;
$settings['reverse_proxy_addresses'] = ['127.0.0.1'];

// Make $_SERVER variables correct for Shibboleth authentication.
if (getenv('HTTPS') !== 'on' && getenv('HTTP_X_FORWARDED_PROTO') === 'https') {
  $_SERVER['HTTPS'] = 'on';
  $_SERVER["SERVER_PORT"] = 443;
}
