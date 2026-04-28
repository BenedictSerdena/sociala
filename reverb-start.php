<?php

// Define POSIX signal constants that require the pcntl extension,
// which is unavailable in some container environments (e.g. Railway).
if (!defined('SIGINT'))  define('SIGINT',  2);
if (!defined('SIGTERM')) define('SIGTERM', 15);
if (!defined('SIGTSTP')) define('SIGTSTP', 20);
if (!defined('SIGHUP'))  define('SIGHUP',  1);
if (!defined('SIGQUIT')) define('SIGQUIT', 3);

// Inject reverb:start as the artisan command, forwarding any extra args.
$_SERVER['argv'] = array_merge(
    ['artisan', 'reverb:start'],
    array_slice($_SERVER['argv'], 1)
);

require __DIR__ . '/artisan';
