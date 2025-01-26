<?php
// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}

if( !class_exists( 'Apps_RP_RadioProgram_MetaBox' ) ) {

	/**
	 * Define the Radio Program CPT's Metabox.
	 */
    class Apps_RP_RadioProgram_MetaBox {
		/**
		 * A reference to an instance of this class.
		 */
		private static $instance = null;

		private $cpt = 'apps-rp';

		private $days;

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
			$this->days = [
				'monday'    => esc_html__('Monday', 'apps-rp'),
				'tuesday'   => esc_html__('Tuesday', 'apps-rp'),
				'wednesday' => esc_html__('Wednesday', 'apps-rp'),
				'thursday'  => esc_html__('Thursday', 'apps-rp'),
				'friday'    => esc_html__('Friday', 'apps-rp'),
				'saturday'  => esc_html__('Saturday', 'apps-rp'),
				'sunday'    => esc_html__('Sunday', 'apps-rp'),
			];

			if( is_admin() ) {
				add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );

				add_filter( 'manage_apps-rp_posts_columns', [ $this, 'add_columns' ] );
				add_action( 'manage_apps-rp_posts_custom_column', [ $this, 'custom_columns' ], 10, 2 );

				add_action( 'load-post.php',     [ $this, 'init' ] );
				add_action( 'load-post-new.php', [ $this, 'init' ] );
			}
		}

		public function enqueue_assets( $hook ) {
			global $typenow;

			if( $typenow == $this->cpt && in_array( $hook, [ 'post.php', 'post-new.php' ]) ) {
				wp_register_style( 'flatpickr', APPS_CONST_URL . 'assets/css/flatpickr.min.css' );
				wp_enqueue_style( $this->cpt, APPS_CONST_URL . 'assets/css/admin.css', ['flatpickr'] );

				wp_register_script( 'flatpickr', APPS_CONST_URL . 'assets/js/flatpickr.js' );
				wp_enqueue_script( $this->cpt, APPS_CONST_URL . 'assets/js/admin.js', [ 'jquery', 'flatpickr' ] );
			}
		}

		public function add_columns( $columns ) {
			$columns = array_merge(
				array_slice($columns, 0, 1, true), # Keep "cb"
				[
					'post-id' => esc_html__( 'Post ID', 'apps-rp' )
				],
				array_slice($columns, 1, 1, true),
				[
					'start-date' => esc_html__( 'Start Date', 'apps-rp' ),
					'end-date'   => esc_html__( 'End Date', 'apps-rp' ),
					'time-slot'  => esc_html__( 'Time Slot', 'apps-rp' ),
				],
				array_slice($columns, 2, null, true)
			);

			return $columns;
		}

		public function custom_columns( $column_name, $post_id ) {
			switch ( $column_name ) {
				case 'post-id':
					echo $post_id;
				break;

				case 'start-date':
					$date = get_post_meta( $post_id, '_rp_start_date', true );
					echo date('l, F j Y', strtotime( $date )) ;
				break;

				case 'end-date':
					$date = get_post_meta( $post_id, '_rp_end_date', true );
					echo date('l, F j Y', strtotime( $date )) ;
				break;

				case 'time-slot':
					$days = $this->days;
					echo '<ul>';
					foreach( $days as $day => $label ) {
						$start_time_key        = sprintf('_rp_%s_start_time', $day );
						$start_time            = get_post_meta( $post_id, $start_time_key, true );
						if( !empty( $start_time ) ) {
							printf('<li>%1$s @ %2$s</li>', esc_html( $label ), esc_html( $start_time ) );
						}
					}
					echo '</ul>';
				break;
			}
		}

		public function init() {
			global $typenow;

			if( $typenow === $this->cpt ) {
				add_action( 'add_meta_boxes', [ $this, 'add_metabox' ] );
				add_action( 'save_post', [ $this, 'save_metabox' ], 10, 3 );
			}
		}

		/**
		 * Add Program Info Meta Box
		 */
		public function add_metabox() {
			add_meta_box(
				'apps-rp-div',
				esc_html__( 'Program Info', 'apps-rp' ),
				[ $this, 'render_rp_meta_box' ],
				$this->cpt,
				'normal',
				'default'
			);
		}

		/**
		 * Render Program Info Meta Box
		 */
		public function render_rp_meta_box( $post ) {
			// Nonce field for security
			wp_nonce_field( 'rp_mb_nonce', '_rp_mb_nonce' );

			/**
			 * Start & End Date
			 */
				$start_date = get_post_meta( $post->ID, '_rp_start_date', true );
				$end_date   = get_post_meta( $post->ID, '_rp_end_date', true );

				echo '<div class="rp-date-fields rp-date-fields-js">';
					printf('
						<p><label>%1$s</label></p>
						<p><input type="text" name="rp-start-date" value="%2$s" data-type="start-date"/></p>',
						esc_html__( 'Start Date', 'apps-rp' ),
						$start_date
					);

					printf('
						<p><label>%1$s</label></p>
						<p><input type="text" name="rp-end-date" value="%2$s" data-type="end-date"/></p>',
						esc_html__( 'End Date', 'apps-rp' ),
						$end_date
					);
				echo '</div>';

			/**
			 * Time Slot
			 */
				echo '<div class="rp-time-slots-wrap">';
					$days = $this->days;
					foreach( $days as $day => $label ) {
						echo '<div class="rp-time-fields rp-time-fields-js">';
							printf( '<h4>%s</h4>', $label );
							echo '<div class="rp-time-slots">';
							/**
							 * Start Time
							 */
								$start_time_key        = sprintf('_rp_%s_start_time', $day );
								$start_time            = get_post_meta( $post->ID, $start_time_key, true );
								$start_time_hidden_key = sprintf('_rp_%s_start_time_hidden', $day );
								$start_time_hidden     = get_post_meta( $post->ID, $start_time_hidden_key, true );

								printf('
									<p><label>%1$s</label></p>
									<p>
										<input type="text" class="rp-time-field-js" name="%2$s" value="%3$s"/>
										<input type="hidden" name="%4$s" value="%5$s"/></p>',
									esc_html__( 'Start Time', 'apps-rp' ),
									sprintf('rp-%s-start-time', $day ),
									$start_time,
									sprintf('rp-%s-start-time-hidden', $day ),
									$start_time_hidden,
								);
							echo '</div>';
						echo '</div>';
					}
				echo '</div>';
		}

		/**
		 * Saving Program Info
		 */
		public function save_metabox( $post_id, $post, $update ) {
			// Verify nonce for security.
			$nonce_name   = isset( $_POST['_rp_mb_nonce'] ) ? $_POST['_rp_mb_nonce'] : '';
			$nonce_action = 'rp_mb_nonce';

			// Check if nonce is valid.
			if ( ! wp_verify_nonce( $nonce_name, $nonce_action ) ) {
				return;
			}

			// Check if user has permissions to save data.
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}

			// Check if not an autosave.
			if ( wp_is_post_autosave( $post_id ) ) {
				return;
			}

			// Check if not a revision.
			if ( wp_is_post_revision( $post_id ) ) {
				return;
			}

			/**
			 * Start & End Date
			 */
				$start_date = sanitize_text_field( $_POST['rp-start-date']);
				$end_date   = sanitize_text_field( $_POST['rp-end-date']);

				if( !empty( $start_date ) ) {
					update_post_meta( $post_id, '_rp_start_date', $start_date );
				} else {
					delete_post_meta(  $post_id, '_rp_start_date' );
				}

				if( !empty( $end_date ) ) {
					update_post_meta( $post_id, '_rp_end_date', $end_date );
				} else {
					delete_post_meta(  $post_id, '_rp_end_date' );
				}

			$days = array_keys( $this->days );
			foreach( $days as $day ) {
				$start_time_key = sprintf('_rp_%s_start_time', $day );
				$start_field    = sprintf('rp-%s-start-time', $day );
				update_post_meta( $post_id, $start_time_key, sanitize_text_field( $_POST[$start_field] ));

				$start_time_hidden_key = sprintf('_rp_%s_start_time_hidden', $day );
				$start_hidden_field    = sprintf('rp-%s-start-time-hidden', $day );
				update_post_meta( $post_id, $start_time_hidden_key, sanitize_text_field( $_POST[$start_hidden_field] ));
			}
		}
    }
}

if( !function_exists( 'apps_rp_radio_program_mb' ) ) {
    /**
     * Returns the instance of a class.
     */
    function apps_rp_radio_program_mb() {
        return Apps_RP_RadioProgram_MetaBox::get_instance();
    }
}

apps_rp_radio_program_mb();
/* Omit closing PHP tag to avoid "Headers already sent" issues. */