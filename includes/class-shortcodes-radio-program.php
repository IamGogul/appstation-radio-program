<?php
// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}

if( !class_exists( 'Apps_RP_RadioProgram_Shortcodes' ) ) {

	/**
	 * Define the Radio Program Shortcodes.
	 */
    class Apps_RP_RadioProgram_Shortcodes {
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

		/**
		 * Constructor
		 */
        public function __construct() {
			add_action( 'wp_enqueue_scripts', [ $this, 'load_assets' ] );
			require_once APPS_CONST_DIR . 'includes/shortcodes/class-sc-week-view.php';
        }

		public function load_assets() {
			wp_enqueue_style('apps-sc', APPS_CONST_URL . 'assets/css/style.css' );
			wp_enqueue_script('apps-sc', APPS_CONST_URL . 'assets/js/script.js', ['jquery'] );
			wp_localize_script('jquery', 'appStation',[
				'ajaxUrl' => admin_url('admin-ajax.php'),
			]);
		}
    }
}

if( !function_exists( 'apps_rp_radio_program_sc' ) ) {
    /**
     * Returns the instance of a class.
     */
    function apps_rp_radio_program_sc() {
        return Apps_RP_RadioProgram_Shortcodes::get_instance();
    }
}

apps_rp_radio_program_sc();
/* Omit closing PHP tag to avoid "Headers already sent" issues. */