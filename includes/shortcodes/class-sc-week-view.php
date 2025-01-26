<?php
// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}

if( !class_exists( 'Apps_RP_SC_Week_View' ) ) {

	/**
	 * Define the  Week View Shortcode
	 */
    class Apps_RP_SC_Week_View {
		/**
		 * A reference to an instance of this class.
		 */
		private static $instance = null;

        private $programs;

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
            /**
             * Retrive only Radio Programs which has Start and End Date Meta
             */
            $this->programs = get_posts([
                'post_type'      => 'apps-rp',
                'posts_per_page' => -1,
                'meta_query'     => [
                    'relation' => 'OR',
                    [
                        'key'     => '_rp_start_date',
                        'compare' => 'EXISTS',
                    ],
                    [
                        'key'     => '_rp_end_date',
                        'compare' => 'EXISTS',
                    ],
                ],
            ]);

            add_shortcode( 'apps-radio-program-week-view', [ $this, 'output' ] );

            add_action('wp_ajax_fetch_week_view', [ $this, 'output_ajax' ] );
            add_action('wp_ajax_nopriv_fetch_week_view', [ $this, 'output_ajax' ] );
        }

        public function output( $atts ) {
            $output = '';
            $atts   = shortcode_atts( [
                'week' => current_time('Y-m-d')
            ], $atts, 'apps-radio-program-week-view' );

            $current_date  = strtotime($atts['week']);

            $start_of_week   = strtotime('monday this week', $current_date);
            $end_of_week     = strtotime('sunday this week', $current_date);
            $weekly_programs = $this->get_programs_for_week( $start_of_week, $end_of_week );

            $prev_week     = date('Y-m-d', strtotime('-1 week', $start_of_week));
            $next_week     = date('Y-m-d', strtotime('+1 week', $start_of_week));

            $output .= '<div class="apps-week-view-calendar-wrap">';
                $output .= sprintf(
                    '<h3 class="apps-week-view-calendar-title">%1$s %2$s - %3$s</h3>',
                    esc_html__('Week View:', 'apps-rp'),
                    date('F j, Y', $start_of_week),
                    date('F j, Y', $end_of_week),
                );

                $output .= '<div class="apps-week-view-calendar-nav-wrap">';
                    $output .= sprintf(
                        '<button class="apps-week-view-calendar-nav apps-week-view-calendar-prev-nav" data-week="%1$s">%2$s</button>',
                        esc_attr( $prev_week ),
                        esc_html__('Previous Week', 'apps-rp'),
                    );

                    $output .= sprintf(
                        '<button class="apps-week-view-calendar-nav apps-week-view-calendar-next-nav" data-week="%1$s">%2$s</button>',
                        esc_attr( $next_week ),
                        esc_html__('Next Week', 'apps-rp'),
                    );
                $output .= '</div>';

                $output .= '<div class="apps-week-view-calendar">';
                    for ($i = 0; $i < 7; $i++) {
                        $day = strtotime("+$i day", $start_of_week);

                        $output .= '<div class="apps-calendar-day">';
                            $output .= sprintf('<div class="apps-calendar-date">%1$s</div>', esc_html( date('l, F j', $day) ) );
                            $output .= "<div class='apps-rs-list'>";
                                $day_programs = $weekly_programs[date('m/d/Y', $day )];
                                if( count( $day_programs ) > 0 ) {
                                    foreach( $day_programs as $day_program ) {
                                        $output .= "<div class='apps-rs-list-item'>";
                                            $output .= $this->program_layout( $day_program, sprintf('_rp_%s_start_time', strtolower( date('l', $day ) ) ) );
                                        $output .= "</div>";
                                    }
                                } else {
                                    $output .= sprintf(
                                        '<div class="apps-rs-list-item apps-rs-list-fullwidth-item">%1$s</div>',
                                        esc_html__('No Programs', 'apps-rp'),
                                    );
                                }
                            $output .= "</div>";
                        $output .= '</div>';
                    }
                $output .= '</div>';

                $output .= '<div class="apps-week-view-calendar-nav-wrap">';
                    $output .= sprintf(
                        '<button class="apps-week-view-calendar-nav apps-week-view-calendar-prev-nav" data-week="%1$s">%2$s</button>',
                        esc_attr( $prev_week ),
                        esc_html__('Previous Week', 'apps-rp'),
                    );

                    $output .= sprintf(
                        '<button class="apps-week-view-calendar-nav apps-week-view-calendar-next-nav" data-week="%1$s">%2$s</button>',
                        esc_attr( $next_week ),
                        esc_html__('Next Week', 'apps-rp'),
                    );
                $output .= '</div>';

            $output .= '</div>';

            return $output;
        }

        public function output_ajax() {
            $week_date       = isset($_POST['week']) ? sanitize_text_field($_POST['week']) : current_time('Y-m-d');
            $current_date    = strtotime($week_date);
            $start_of_week   = strtotime('monday this week', $current_date);
            $end_of_week     = strtotime('sunday this week', $current_date);
            $weekly_programs = $this->get_programs_for_week( $start_of_week, $end_of_week );
            $prev_week       = date('Y-m-d', strtotime('-1 week', $start_of_week));
            $next_week       = date('Y-m-d', strtotime('+1 week', $start_of_week));

            $response = [
                'title'     => 'Week View: '. date('F j, Y', $start_of_week) . ' - ' . date('F j, Y', $end_of_week),
                'prev-week' => $prev_week,
                'next-week' => $next_week,
                'html'      => '',
            ];

            $output = '';
            for ($i = 0; $i < 7; $i++) {
                $day          = strtotime("+$i day", $start_of_week);
                $day_programs = $weekly_programs[date('m/d/Y', $day )];

                $output .= '<div class="apps-calendar-day">';
                    $output .= sprintf('<div class="apps-calendar-date">%1$s</div>', esc_html( date('l, F j', $day) ) );
                    $output .= "<div class='apps-rs-list'>";
                        if( count( $day_programs ) > 0 ) {
                            foreach( $day_programs as $day_program ) {
                                $output .= "<div class='apps-rs-list-item'>";
                                    $output .= $this->program_layout( $day_program, sprintf('_rp_%s_start_time', strtolower( date('l', $day ) ) ) );
                                $output .= "</div>";
                            }
                        } else {
                            $output .= sprintf(
                                '<div class="apps-rs-list-item apps-rs-list-fullwidth-item">%1$s</div>',
                                esc_html__('No Programs', 'apps-rp'),
                            );
                        }
                    $output .= "</div>";
                $output .= "</div>";
            }

            $response['html'] = $output;

            wp_send_json_success($response);
            wp_die();
        }

        public function get_programs_for_week( $start_timestamp, $end_timestamp ) {
            $weekly_programs = [];

            // Iterate through each day of the week
            $start_date = (new DateTime())->setTimestamp($start_timestamp);
            $end_date   = (new DateTime())->setTimestamp($end_timestamp);
            $interval   = new DateInterval('P1D');
            $period     = new DatePeriod($start_date, $interval, $end_date->modify('+1 day'));

            foreach ($period as $day) {
                $day_format    = $day->format('m/d/Y');
                $weekly_programs[$day_format] = [];

                foreach ($this->programs as $program ) {
                    $start_date = get_post_meta( $program->ID, '_rp_start_date', true );
                    $end_date   = get_post_meta( $program->ID, '_rp_end_date', true );

                    // Convert meta dates to DateTime
                    $rp_start = $start_date ? new DateTime($start_date) : null;
                    $rp_end   = $end_date ? new DateTime($end_date) : null;

                    // Check if the day falls within the range of _rp_start_date and _rp_end_date
                    if (
                        ($rp_start && $rp_start <= $day && (!$rp_end || $rp_end >= $day)) ||
                        ($rp_end && $rp_end == $day)
                    ) {
                        $weekly_programs[$day_format][] = $program->ID;
                    }
                }

                /**
                 * Filter Programs with empty timeslot
                 */
                $day_name      = strtolower( $day->format('l') );
                $time_slot_key = sprintf('_rp_%s_start_time_hidden', $day_name );

                $weekly_programs[$day_format] = array_filter($weekly_programs[$day_format], function($program) use ($time_slot_key) {
                    $start_time = get_post_meta($program, $time_slot_key, true);
                    return !empty($start_time); // Keep only posts with a non-empty meta value
                });

                /**
                 * Sort Programs by the timeslot
                 */
                usort($weekly_programs[$day_format], function($a, $b) use ($time_slot_key) {
                    $start_time_a = get_post_meta($a, $time_slot_key, true);
                    $start_time_b = get_post_meta($b, $time_slot_key, true);
                    return $start_time_a <=> $start_time_b;
                });

            }

            return $weekly_programs;
        }

        public function program_layout( $post_id, $time_slot_key ) {
            $post_title     = get_the_title( $post_id );
            $post_thumbnail = '';

            if( has_post_thumbnail( $post_id ) ) {
                $post_thumbnail = sprintf(
                    '<div class="apps-rp-img">%1$s</div>',
                    get_the_post_thumbnail(
                        $post_id,
                        'medium',
                        [
                            'alt' => esc_attr( $post_title ),
                            'class' => 'aligncenter'
                        ]
                    )
                );
            }

            return sprintf(
                '<div class="apps-rp-info-wrap apps-rp-%1$s">
                    %2$s
                    <div class="apps-rp-info">
                        <h5>%3$s</h5>
                        <p>@ %4$s</p>
                    </div>
                </div>',
                esc_attr( $post_id ),
                $post_thumbnail,
                esc_html( $post_title ),
                esc_html( get_post_meta( $post_id, $time_slot_key, true ) )
            );
        }
    }
}

if( !function_exists( 'apps_rp_sc_week_view' ) ) {
    /**
     * Returns the instance of a class.
     */
    function apps_rp_sc_week_view() {

        return Apps_RP_SC_Week_View::get_instance();
    }
}

apps_rp_sc_week_view();
/* Omit closing PHP tag to avoid "Headers already sent" issues. */