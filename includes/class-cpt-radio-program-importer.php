<?php
// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}

if( !class_exists( 'Apps_RP_RadioProgram_Importer' ) ) {

	/**
	 * Define the Radio Program importer.
	 */
    class Apps_RP_RadioProgram_Importer {
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
            add_action( 'admin_menu', [ $this, 'admin_menu' ] );
        }

        public function admin_menu() {
            if( !current_user_can('edit_pages') ) {
                return;
            }

            add_submenu_page(
                'edit.php?post_type=apps-rp',
                esc_html__( 'Import Programs', 'apps-rp' ),
                esc_html__( 'Import', 'apps-rp' ),
                'manage_options',
                'apps-rp-impoter',
                [ $this, 'importer_page' ]
            );
        }

        public function importer_page() {
            if( isset($_POST['preview-csv']) ) {
                $this->csv_preview();
            } elseif (isset($_POST['create-programs'])) {
                $this->create_programs();
            } else {
                $this->csv_upload_form();
            }
        }

        public function form() {
            echo '<form method="post" enctype="multipart/form-data">';
                echo '<input type="file" name="csv-file" accept=".csv" required>';
                printf('<button type="submit" name="preview-csv" class="button button-primary">%1$s</button>', esc_html__( 'Preview CSV', 'apps-rp' ) );
            echo '</form>';
        }

        public function csv_upload_form() {
            echo '<div class="wrap">';
                printf('<h1>%1$s</h1>', esc_html__( 'Import Radio Programs', 'apps-rp' ) );
                printf('<p>%1$s</p>', esc_html__( 'Accepts CSV file only', 'apps-rp' ) );
                $this->form();
            echo '</div>';
        }

        public function csv_preview() {
            if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['csv-file'])) {
                $file = $_FILES['csv-file']['tmp_name'];

                // Open the file and count the rows
                if (($handle = fopen($file, 'r')) !== FALSE) {
                    $rows = 0;
                    $data = [];

                    // Skip the header row
                    $headers = fgetcsv($handle);

                    // Count rows
                    while (($row = fgetcsv($handle)) !== FALSE) {
                        $data[] = $row;
                        $rows++;
                    }

                    fclose($handle);

                    // Check if there are more than 10 rows
                    if ($rows > 10) {
                        echo '<div class="wrap">';
                            printf('<h1>%1$s</h1>', esc_html__( 'Import Radio Programs', 'apps-rp' ) );
                            printf(
                                '<p style="color:red;">%1$s</p>',
                                esc_html__('The uploaded CSV file contains more than 10 rows. Please upload a file with 10 rows or fewer.', 'apps-rp')
                            );
                            $this->form();
                        echo '</div>';
                    }

                    $this->preview_data( $data );

                }
            } else {
                $this->csv_upload_form();
            }
        }

        public function preview_data( $data ) {
            echo '<div class="wrap">';
                printf('<h1>%1$s</h1>', esc_html__( 'Preview Radio Programs', 'apps-rp' ) );

                echo '<form method="post">';
                    echo '<table class="widefat fixed">';
                        echo '<thead>';
                            echo '<tr>';
                                printf('<th>%1$s</th>', esc_html__( 'Name', 'apps-rp' ) );
                                printf('<th>%1$s</th>', esc_html__( 'Description', 'apps-rp' ) );
                                printf('<th>%1$s</th>', esc_html__( 'Start Date', 'apps-rp' ) );
                                printf('<th>%1$s</th>', esc_html__( 'End Date', 'apps-rp' ) );
                                printf('<th>%1$s</th>', esc_html__( 'Thumbnail URL', 'apps-rp' ) );
                                printf('<th>%1$s</th>', esc_html__( 'Schedule', 'apps-rp' ) );
                            echo '</tr>';
                        echo '</thead>';
                        echo '<tbody>';
                            foreach ($data as $row):
                                echo '<tr>';
                                    printf('<td>%1$s</td>', esc_html( $row[0] ) );
                                    printf('<td>%1$s</td>', esc_html( $row[1] ) );
                                    printf('<td>%1$s</td>', esc_html( $row[2] ) );
                                    printf('<td>%1$s</td>', esc_html( $row[3] ) );
                                    printf('<td>%1$s</td>', esc_html( $row[4] ) );
                                    printf('<td>%1$s</td>', esc_html( $row[5] ) );
                                echo '</tr>';
                            endforeach;
                        echo '</tbody>';
                    echo '</table>';
                    printf('<input type="hidden" name="csv-data" value="%1$s"/>', base64_encode(serialize($data)) );
                    printf('<br/><button type="submit" name="create-programs" class="button button-primary">%1$s</button>', esc_html__( 'create Programs', 'apps-rp' ) );
                echo '</form>';
            echo '</div>';
        }

        public function create_programs() {
            if (!empty($_POST['csv-data'])) {
                $count = 0;
                $data  = unserialize(base64_decode($_POST['csv-data']));

                $dayMap = [
                    "mon" => "monday",
                    "tue" => "tuesday",
                    "wed" => "wednesday",
                    "thu" => "thursday",
                    "fri" => "friday",
                    "sat" => "saturday",
                    "sun" => "sunday"
                ];

                foreach ($data as $row) {
                    $title         = sanitize_text_field($row[0]);
                    $excerpt       = sanitize_textarea_field($row[1]);

                    $post_id = wp_insert_post([
                        'post_type'    => 'apps-rp',
                        'post_title'   => $title,
                        'post_excerpt' => $excerpt,
                        'post_status'  => 'publish',
                    ]);

                    if ($post_id) {
                        $count++;

                        /**
                         * Start & End Date
                         */
                            $start_date           = sanitize_text_field($row[2]);
                            $start_date_formatted = date('m/d/Y', strtotime($start_date));

                            $end_date           = sanitize_text_field($row[3]);
                            $end_date_formatted = date('m/d/Y', strtotime($end_date));

                            update_post_meta($post_id, '_rp_start_date', $start_date_formatted);
                            update_post_meta($post_id, '_rp_end_date', $end_date_formatted);

                        /**
                         * Time Slot
                         */
                            $schedule = json_decode($row[5], true);
                            if (is_array($schedule)) {
                                foreach ($schedule as $day => $time) {
                                    if( !empty($time) ) {
                                        $fullDay = $dayMap[strtolower($day)] ?? strtolower($day);

                                        $start_time_key = '_rp_' . strtolower($fullDay) . '_start_time';
                                        $start_time     = date('h:i A', strtotime($time));
                                        update_post_meta($post_id, $start_time_key, $start_time);

                                        $start_time_hidden_key = '_rp_' . strtolower($fullDay) . '_start_time_hidden';
                                        $start_time_hidden     = date('H:i', strtotime($time));
                                        update_post_meta($post_id, $start_time_hidden_key, $start_time_hidden);
                                    }
                                }
                            }

                        /**
                         * Featured Image
                         */
                        $thumbnail_url = esc_url_raw($row[4]);
                        if( !empty( $thumbnail_url ) ) {
                            $image_id = $this->upload_image_from_url( $thumbnail_url, $post_id );
                            if ($image_id) {
                                set_post_thumbnail($post_id, $image_id);
                            }
                        }
                    }
                }

                echo '<div class="wrap">';
                    printf('<p>%1$s %2$s</p>', esc_html( $count ), esc_html__('programs created successfully.', 'apps-rp'));
                echo '</div>';
            } else {
                echo '<div class="wrap">';
                    printf('<p>%1$s</p>', esc_html__('No programs to process.', 'apps-rp'));
                echo '</div>';
            }
        }

        public function upload_image_from_url($image_url, $post_id) {
            // Get the file name and download the image
            $file_name = basename($image_url);
            $upload_dir = wp_upload_dir();
            $image_data = file_get_contents($image_url);

            if ($image_data) {
                $file_path = $upload_dir['path'] . '/' . $file_name;
                file_put_contents($file_path, $image_data);

                // Check file type and create attachment
                $file_type = wp_check_filetype($file_name, null);
                $attachment = [
                    'post_mime_type' => $file_type['type'],
                    'post_title'     => sanitize_file_name($file_name),
                    'post_content'   => '',
                    'post_status'    => 'inherit',
                ];
                $attachment_id = wp_insert_attachment($attachment, $file_path, $post_id);

                // Generate attachment metadata and assign it
                require_once ABSPATH . 'wp-admin/includes/image.php';
                $attachment_data = wp_generate_attachment_metadata($attachment_id, $file_path);
                wp_update_attachment_metadata($attachment_id, $attachment_data);

                return $attachment_id;
            }

            return false;
        }
    }
}

if( !function_exists( 'apps_rp_radio_program_importer' ) ) {
    /**
     * Returns the instance of a class.
     */
    function apps_rp_radio_program_importer() {

        return Apps_RP_RadioProgram_Importer::get_instance();
    }
}

apps_rp_radio_program_importer();
/* Omit closing PHP tag to avoid "Headers already sent" issues. */