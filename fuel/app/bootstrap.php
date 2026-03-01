<?php
// Bootstrap the framework DO NOT edit this
require COREPATH.'bootstrap.php';

\Autoloader::add_classes(array(
	// Add classes you want to override here
	// Example: 'View' => APPPATH.'classes/view.php',
));

// Register the autoloader
\Autoloader::register();

/**
 * Your environment.  Can be set to any of the following:
 *
 * Fuel::DEVELOPMENT
 * Fuel::TEST
 * Fuel::STAGING
 * Fuel::PRODUCTION
 */
\Fuel::$env = \Arr::get($_SERVER, 'FUEL_ENV', \Arr::get($_ENV, 'FUEL_ENV', \Fuel::DEVELOPMENT));

// Initialize the framework with the config file.
\Fuel::init('config.php');

// ログイン到達調査: リクエスト時の生URI・base_urlをログ出力（送信先URLの検証用）
if (php_sapi_name() !== 'cli' && isset($_SERVER['REQUEST_URI'])) {
	\Log::debug('[ROUTE DEBUG] REQUEST_URI = ' . $_SERVER['REQUEST_URI']);
	\Log::debug('[ROUTE DEBUG] SCRIPT_NAME = ' . (isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : ''));
	\Log::debug('[ROUTE DEBUG] base_url = ' . (\Config::get('base_url') ?: '(null/auto)'));
}
