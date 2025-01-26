<?php
/**
 * AppStation Radio Program : To manage and display radio program’s schedule
 *
 * Plugin Name: AppStation Radio Program
 * Plugin URI:  http://plugins.gogul.pro/appstation-radio-program
 * Description: A WordPress plugin to manage and display radio program’s schedule, including an import feature for program details via CSV. The schedule should allow unique broadcast times for each day of the week.
 * Version: 1.0.0
 * Author: 🎖️ M Gogul Saravanan
 * Author URI: https://profiles.wordpress.org/iamgogul/
 * License:     GPLv2 or later
 * License URI: http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: apps-rp
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Debug
 */
if( !function_exists( 'apps_rp_debug' ) ) {
	function apps_rp_debug( $arg = NULL ) {
		echo '<pre>';
		var_dump( $arg );
		echo '</pre>';
	}
}

if( !class_exists( 'AppStation_RP_WP_Plugin' ) ) {

	final class AppStation_RP_WP_Plugin {
		/**
		 * A reference to an instance of this class.
		 */
		private static $instance = null;

		/**
		 * Returns the instance.
		 */
		public static function get_instance() {
			// If the single instance hasn't been set, set it now.
			if ( null == self::$instance ) {
				self::$instance = new self;
            }

			return self::$instance;
		}

        public function __construct() {
			$this->define_constants();
            $this->load_dependencies();

			add_action( 'plugins_loaded', [ $this, 'load_plugin_textdomain' ] );

			// Register activation and deactivation hook.
			register_activation_hook( APPS_RP_CONST_FILE , [ $this, 'activate_plugin' ] );
			register_deactivation_hook( APPS_RP_CONST_FILE, [ $this, 'deactivate_plugin' ] );
		}

		/**
		 * Define plugin required constants
		 */
		private function define_constants() {
            $this->define( 'APPS_RP_CONST_FILE', __FILE__ );

			if( ! function_exists('get_plugin_data') ){
				require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			}
			$plugin_data = get_plugin_data( APPS_RP_CONST_FILE, true, false );

            $this->define( 'APPS_RP_CONST_PLUGIN_NAME', $plugin_data['Name'] );
            $this->define( 'APPS_RP_CONST_SAN_PLUGIN_NAME', sanitize_title( $plugin_data['Name'] ) );
            $this->define( 'APPS_CONST_VERSION', $plugin_data['Version'] );
            $this->define( 'APPS_CONST_DIR', trailingslashit( plugin_dir_path( APPS_RP_CONST_FILE ) ) );
			$this->define( 'APPS_CONST_URL', trailingslashit( plugin_dir_url( APPS_RP_CONST_FILE ) ) );
			$this->define( 'APPS_CONST_BASENAME', plugin_basename( APPS_RP_CONST_FILE ) );
		}

		/**
		 * Define constant if not already set.
		 */
		private function define( $name, $value ) {
			if( !defined( $name ) ) {
				define( $name, $value );
            }
		}

		/**
		 * Load the required dependencies for this plugin.
		 */
		private function load_dependencies() {
			/**
			 * Radio Program Custom Post Type
			 */
			require_once APPS_CONST_DIR . 'includes/class-cpt-radio-program.php';

			/**
			 * Radio Program CPT's Metabox
			 */
			require_once APPS_CONST_DIR . 'includes/class-metabox-radio-program.php';

			/**
			 * Radio Program's Shortcodes
			 */
			require_once APPS_CONST_DIR . 'includes/class-shortcodes-radio-program.php';

			/**
			 * Radio Program Importer
			 */
			require_once APPS_CONST_DIR . 'includes/class-cpt-radio-program-importer.php';

		}

		/**
		 * Load plugin textdomain for i18n.
		 */
		public function load_plugin_textdomain() {
			load_plugin_textdomain( 'apps-rp', false, dirname( APPS_CONST_BASENAME ) . '/languages');
		}

		/**
		 * The code that runs during plugin activation.
		 */
		public static function activate_plugin() {}

		/**
		 * The code that runs during plugin deactivation.
		 */
		public static function deactivate_plugin() {}
	}
}

if( !function_exists( 'apps_rp_wp_plugin' ) ) {
    /**
     * Returns instance WP Plugin class.
     */
    function apps_rp_wp_plugin() {
        return AppStation_RP_WP_Plugin::get_instance();
    }
}

apps_rp_wp_plugin();
