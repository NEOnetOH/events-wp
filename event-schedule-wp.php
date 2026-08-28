<?php
/**
 * Plugin Name: Event Scheduler
 * Plugin URI: https://github.com/NEOnetOH/events-wp
 * Description: Securely display upcoming events from the Event Scheduler API using a WordPress widget.
 * Version: 1.1.0
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * Author: NEOnet
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: event-schedule-wp
 * Domain Path: /languages
 *
 * @package EventScheduleWp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ESWP_VERSION', '1.1.0' );
define( 'ESWP_FILE', __FILE__ );
define( 'ESWP_PATH', plugin_dir_path( __FILE__ ) );
define( 'ESWP_URL', plugin_dir_url( __FILE__ ) );
define( 'ESWP_OPTION_KEY', 'eswp_settings' );
define( 'ESWP_SECRET_OPTION', 'eswp_client_secret' );
define( 'ESWP_TOKEN_TRANSIENT', 'eswp_access_token' );
define( 'ESWP_EVENTS_TRANSIENT', 'eswp_events_cache' );

require_once ESWP_PATH . 'includes/class-security.php';
require_once ESWP_PATH . 'includes/class-event.php';
require_once ESWP_PATH . 'includes/class-cache.php';
require_once ESWP_PATH . 'includes/class-api-client.php';
require_once ESWP_PATH . 'includes/class-settings.php';
require_once ESWP_PATH . 'includes/class-connect.php';
require_once ESWP_PATH . 'includes/Display/class-query.php';
require_once ESWP_PATH . 'includes/Display/class-shortcode.php';
require_once ESWP_PATH . 'includes/Display/class-widget.php';
require_once ESWP_PATH . 'includes/Display/class-block.php';
require_once ESWP_PATH . 'includes/class-plugin.php';

register_activation_hook( __FILE__, array( 'Eswp_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Eswp_Plugin', 'deactivate' ) );

Eswp_Plugin::instance();
