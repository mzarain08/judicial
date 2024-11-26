<?php

use PrestoPlayer\Pro\Plugin;

/**
 * Plugin Name: Presto Player Pro
 * Plugin URI: http://prestoplayer.com
 * Description: Pro extension for Presto Player to enable chapters, private video and more.
 * Version: 2.0.7
 * Author: Presto Player
 * Text Domain: presto-player-pro
 * Tags: private, video, lms, hls
 * Domain Path: languages
 */

// Don't do anything if called directly.
if (!\defined('ABSPATH') || !\defined('WPINC')) {
	exit;
}

// let everyone know we're cooking with fire!
define('PRESTO_PLAYER_PRO_PLUGIN_FILE', __FILE__);
define('PRESTO_PLAYER_PRO_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PRESTO_PLAYER_PRO_PLUGIN_DIR', plugin_dir_path(__FILE__));

// autoload components
if (file_exists(dirname(__FILE__) . '/vendor/autoload.php')) {
	include_once dirname(__FILE__) . '/vendor/autoload.php';
}

register_activation_hook(
	__FILE__,
	function () {
		set_transient('presto_player_pro_activated', true);
	}
);

// bootstrap the plugin.
(new Plugin())->bootstrap();

/**
 * The code that runs during plugin activation
 */
register_activation_hook(
	__FILE__,
	function () {
		if (!class_exists('\PrestoPlayer\Plugin')) {
			(new \PrestoPlayer\Pro\Services\CoreInstaller())->maybeInstallCore();
		}
	}
);
