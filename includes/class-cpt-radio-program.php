<?php
// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}

if( !class_exists( 'Apps_RP_CPT_RadioProgram' ) ) {

	/**
	 * Define the Radio Program custom post type.
	 */
    class Apps_RP_CPT_RadioProgram {
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
            add_action( 'init', [ $this, 'init' ] );
            add_filter( 'enter_title_here', [ $this, 'title_placeholder' ], 10, 2 );
        }

        public function init() {
            $labels = [
                'name'                  => esc_html__( 'Programs', 'apps-rp' ),
                'singular_name'         => esc_html__( 'Program', 'apps-rp' ),
                'add_new'               => esc_html__( 'Add New', 'apps-rp' ),
                'add_new_item'          => esc_html__( 'Add New Program', 'apps-rp' ),
                'edit_item'             => esc_html__( 'Edit Program', 'apps-rp' ),
                'new_item'              => esc_html__( 'New Program', 'apps-rp' ),
                'view_item'             => esc_html__( 'View Program', 'apps-rp' ),
                'view_items'            => esc_html__( 'View Programs', 'apps-rp' ),
                'search_items'          => esc_html__( 'Search Programs', 'apps-rp' ),
                'not_found'             => esc_html__( 'No Programs found.', 'apps-rp' ),
                'not_found_in_trash'    => esc_html__( 'No Programs found in Trash.', 'apps-rp' ),
                'all_items'             => esc_html__( 'All Programs', 'apps-rp' ),
                'featured_image'        => esc_html__( 'Program Thumbnail', 'apps-rp' ),
                'set_featured_image'    => esc_html__( 'Set Program Thumbnail', 'apps-rp' ),
                'remove_featured_image' => esc_html__( 'Remove Program Thumbnail', 'apps-rp' ),
                'use_featured_image'    => esc_html__( 'Use as Program Thumbnail', 'apps-rp' ),
            ];

            $args   = [
                'label'         => esc_html__( 'Program', 'apps-rp' ),
                'labels'        => $labels,
                'public'        => true,
                'menu_position' => 5,
                'menu_icon'     => 'dashicons-megaphone',
                'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
            ];

            register_post_type( 'apps-rp', $args );
        }

        public function title_placeholder( $placeholder, $post ) {
            if ($post->post_type == 'apps-rp' ) {
                $placeholder = esc_html__( 'Add Program', 'apps-rp' );
            }

            return $placeholder;
        }
    }
}

if( !function_exists( 'apps_rp_cpt_radio_program' ) ) {
    /**
     * Returns the instance of a class.
     */
    function apps_rp_cpt_radio_program() {
        return Apps_RP_CPT_RadioProgram::get_instance();
    }
}

apps_rp_cpt_radio_program();
/* Omit closing PHP tag to avoid "Headers already sent" issues. */