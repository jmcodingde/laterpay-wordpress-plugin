<?php

class LaterPay_Controller_Admin_Dashboard extends LaterPay_Controller_Abstract
{

    /**
     * Sections are used by the ajax laterpay_get_dashboard-callback.
     * Every section is mapped to a private method within this controller.
     *
     * @var array
     */
    private $ajax_sections = array(
        'converting_items',
        'selling_items',
        'revenue_items',
        'most_least_converting_items',
        'most_least_selling_items',
        'most_least_revenue_items',
        'metrics',
    );

    private $cache_file_exists;
    private $cache_file_is_broken;

    private $ajax_nonce = 'laterpay_dashboard';

    /**
     * @see LaterPay_Controller_Abstract::load_assets
     */
    public function load_assets() {
        parent::load_assets();

        // load page-specific JS
        wp_register_script(
            'laterpay-flot',
            $this->config->get( 'js_url' ) . 'vendor/lp_jquery.flot.js',
            array( 'jquery' ),
            $this->config->get( 'version' ),
            true
        );
        wp_register_script(
            'laterpay-peity',
            $this->config->get( 'js_url' ) . 'vendor/jquery.peity.min.js',
            array( 'jquery' ),
            $this->config->get( 'version' ),
            true
        );
        wp_register_script(
            'laterpay-backend-dashboard',
            $this->config->get( 'js_url' ) . 'laterpay-backend-dashboard.js',
            array( 'jquery', 'laterpay-flot', 'laterpay-peity' ),
            $this->config->get( 'version' ),
            true
        );
        wp_enqueue_script( 'laterpay-flot' );
        wp_enqueue_script( 'laterpay-peity' );
        wp_enqueue_script( 'laterpay-backend-dashboard' );

        $this->logger->info( __METHOD__ );

        // pass localized strings and variables to script
        wp_localize_script(
            'laterpay-backend-dashboard',
            'lpVars',
            array(
                'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
                'nonces'    => array(
                                    'dashboard' => wp_create_nonce( $this->ajax_nonce ),
                                ),
                'i18n'      => array(
                                     'noData'   => __( 'No data available', 'laterpay' ),
                                ),
            )
        );
    }

    /**
     * @see LaterPay_Controller_Abstract::render_page
     */
    public function render_page() {
        $this->load_assets();

        $view_args = array(
            'plugin_is_in_live_mode'    => $this->config->get( 'is_in_live_mode' ),
            'top_nav'                   => $this->get_menu(),
            'admin_menu'                => LaterPay_Helper_View::get_admin_menu(),
            'currency'                  => get_option( 'laterpay_currency' ),

            // in wp-config.php the user can disable the WP-Cron completely OR replace it with real server crons.
            // this view variable can be used to show additional information that *maybe* the dashboard
            // data will not refresh automatically
            'is_cron_enabled'           => ! defined( 'DISABLE_WP_CRON' ) || ( defined( 'DISABLE_WP_CRON' ) && ! DISABLE_WP_CRON ),
            'cache_file_exists'         => $this->cache_file_exists,
            'cache_file_is_broken'      => $this->cache_file_is_broken,
        );

        $this->assign( 'laterpay', $view_args );

        $this->render( 'backend/dashboard' );
    }

    /**
     * Ajax callback to refresh the dashboard data.
     *
     * @wp-hook wp_ajax_laterpay_get_dashboard_data
     *
     * @return void
     */
    public function ajax_get_dashboard_data() {
        $this->validate_ajax_nonce();
        $this->validate_ajax_section_callback();

        $options = $this->get_ajax_request_options( $_POST );

        if ( $options[ 'refresh' ] ) {
            $section = $options[ 'section' ];
            $data = $this->$section( $options );
            LaterPay_Helper_Dashboard::refresh_cache_data( $options, $data );
        }

        $data = LaterPay_Helper_Dashboard::get_cache_data( $options );

        if ( empty( $data ) ) {
            $response = array(
                'message'   => sprintf( __( 'Cache data is empty on <code>%s</code>', 'laterpay' ), $options[ 'section' ] ),
                'success'   => FALSE,
            );
        } else {
            $response = array(
                'data'      => $data,
                'success'   => TRUE,
            );
        }

        if ( $this->config->get( 'debug_mode' ) ) {
            $response[ 'options' ] = $options;
        }

        wp_send_json( $response );
    }

    /**
     * Callback for wp-cron to refresh today's dashboard data.
     * The Cron job provides two params for {x} days back and {n} count of items to
     * register your own cron with custom params to cache data.
     *
     * @wp-hook laterpay_refresh_dashboard_data
     *
     * @param int       $start_timestamp
     * @param int       $count
     * @param string    $interval
     *
     * @return void
     */
    public function refresh_dasboard_data( $start_timestamp = null, $count = 10, $interval = 'week' ) {
        set_time_limit( 0 );

        if ( $start_timestamp === null ) {
            $start_timestamp = strtotime( 'today GMT' );
        }

        $args = array(
            'start_timestamp'   => $start_timestamp,
            'count'             => (int) $count,
            'interval'          => $interval,
        );


        foreach ( $this->ajax_sections as $section ) {
            $args[ 'section' ]  = $section;
            $options            = $this->get_ajax_request_options( $args );
            $this->logger->info(
                __METHOD__ . ' - ' . $section,
                $options
            );
            $data = $this->$section( $options );
            LaterPay_Helper_Dashboard::refresh_cache_data( $options, $data );
        }
    }

    /**
     * Internal function to load the conversion data as diagram.
     *
     * @param array $options
     *
     * @return array $data
     */
    private function converting_items( $options ) {
        $post_views_model   = new LaterPay_Model_Post_Views();
        $converting_items   = $post_views_model->get_history( $options[ 'query_args' ] );
        $data               = LaterPay_Helper_Dashboard::convert_history_result_to_diagram_data(
                                $converting_items,
                                $options[ 'start_timestamp' ],
                                $options[ 'interval' ]
                            );

        $this->logger->info(
            __METHOD__,
            array(
                'options'   => $options,
                'data'      => $data,
            )
        );

        return $data;
    }

    /**
     * Internal function to load the sales data as diagram.
     *
     * @param array $options
     *
     * @return array $data
     */
    private function selling_items( $options ) {
        $history_model  = new LaterPay_Model_Payments_History();
        $selling_items  = $history_model->get_history( $options[ 'query_args' ] );
        $data           = LaterPay_Helper_Dashboard::convert_history_result_to_diagram_data(
                            $selling_items,
                            $options[ 'start_timestamp' ],
                            $options[ 'interval' ]
                        );

        $this->logger->info(
            __METHOD__,
            array(
                'options'   => $options,
                'data'      => $data,
            )
        );

        return $data;
    }

    /**
     * Internal function to load the revenue data items as diagram.
     *
     * @param array $options
     *
     * @return array $data
     */
    private function revenue_items( $options ) {
        $history_model  = new LaterPay_Model_Payments_History();
        $revenue_item   = $history_model->get_revenue_history( $options[ 'query_args' ] );
        $data           = LaterPay_Helper_Dashboard::convert_history_result_to_diagram_data(
                            $revenue_item,
                            $options[ 'start_timestamp' ],
                            $options[ 'interval' ]
                        );

        $this->logger->info(
            __METHOD__,
            array(
                'options'   => $options,
                'data'      => $data,
            )
        );

        return $data;
    }

    /**
     * Internal function to load the most / least converting items by given options.
     *
     * @param array $options
     *
     * @return array $data
     */
    private function most_least_converting_items( $options ) {
        $post_views_model = new LaterPay_Model_Post_Views();
        $data = array(
            'most'  => $post_views_model->get_most_viewed_posts( $options[ 'most_least_query' ], $options[ 'start_timestamp' ], $options[ 'interval' ] ),
            'least' => $post_views_model->get_least_viewed_posts( $options[ 'most_least_query' ], $options[ 'start_timestamp' ], $options[ 'interval' ] ),
        );

        $this->logger->info(
            __METHOD__,
            array(
                'options'   => $options,
                'data'      => $data,
            )
        );

        return $data;
    }

    /**
     * Internal function to load the most / least selling items by given options.
     *
     * @param array $options
     *
     * @return array $data
     */
    private function most_least_selling_items( $options ) {
        $history_model = new LaterPay_Model_Payments_History();
        $data = array(
            'most'  => $history_model->get_best_selling_posts(
                            $options[ 'most_least_query' ],
                            $options[ 'start_timestamp' ],
                            $options[ 'interval' ]
                        ),
            'least' => $history_model->get_least_selling_posts(
                            $options[ 'most_least_query' ],
                            $options[ 'start_timestamp' ],
                            $options[ 'interval' ]
                        ),
        );

        $this->logger->info(
            __METHOD__,
            array(
                'options'   => $options,
                'data'      => $data,
            )
        );

        return $data;
    }

    /**
     * Internal function to load the most / least revenue generating items by given options.
     *
     * @param array $options
     *
     * @return array $data
     */
    private function most_least_revenue_items( $options ) {
        $history_model = new LaterPay_Model_Payments_History();
        $data = array(
            'most'        => $history_model->get_most_revenue_generating_posts(
                                $options[ 'most_least_query' ],
                                $options[ 'start_timestamp' ],
                                $options[ 'interval' ]
                            ),
            'least'       => $history_model->get_least_revenue_generating_posts(
                                $options[ 'most_least_query' ],
                                $options[ 'start_timestamp' ],
                                $options[ 'interval' ]
                            ),
        );

        $this->logger->info(
            __METHOD__,
            array(
                'options'   => $options,
                'data'      => $data,
            )
        );

        return $data;
    }

    /**
     * Internal function to load metrics by given options.
     *
     * @param array $options
     *
     * @return array $data
     */
    private function metrics( $options ) {
        $args = array(
            'where' => $options[ 'query_where' ],
        );

        $history_model      = new LaterPay_Model_Payments_History();
        $post_views_model   = new LaterPay_Model_Post_Views();

        // get the user stats for the given params
        $user_stats             = $history_model->get_user_stats( $args );
        $total_customers        = count( $user_stats );
        $new_customers          = 0;
        $returning_customers    = 0;
        foreach ( $user_stats as $stat ) {
            if ( (int) $stat->quantity === 1 ) {
                $new_customers += 1;
            } else {
                $returning_customers += 1;
            }
        }

        if ( $total_customers > 0 ) {
            $new_customers          = round( $new_customers * 100 / $total_customers );
            $returning_customers    = round( $returning_customers * 100 / $total_customers );
        }

        $total_items_sold           = $history_model->get_total_items_sold( $args );
        $total_items_sold           = number_format_i18n( $total_items_sold->quantity );    // TODO: replace number_format_i18n with new improved number_format helper function

        $impressions                = $post_views_model->get_total_post_impression( $args );
        $impressions                = number_format_i18n( $impressions->quantity );

        $total_revenue_items        = $history_model->get_total_revenue_items( $args );
        $total_revenue_items        = $total_revenue_items->amount;
        $avg_purchase               = number_format_i18n( 0, 2 );
        if ( $total_revenue_items > 0 ) {
            $avg_purchase           = number_format_i18n( $total_revenue_items / $total_items_sold, 2 );
        }
        // format the total revenue items after calculating the avg purchase,
        // to make sure that the number format does not break the calculation
        $total_revenue_items        = number_format_i18n( $total_revenue_items, 2 );

        $conversion = 0;
        if ( $impressions > 0 ) {
            $conversion = number_format_i18n( $total_items_sold / $impressions, 1 );
        }

        $avg_items_sold = 0;
        if ( $total_items_sold > 0 ) {
            if ( $options[ 'interval' ] === 'week' ) {
                $diff = 7;
            } else if ( $options[ 'interval' ] === '2-weeks' ) {
                $diff = 14;
            } else if ( $options[ 'interval' ] === 'month' ) {
                $diff = 30;
            } else {
                // hour
                $diff = 24;
            }
            $avg_items_sold = number_format_i18n( $total_items_sold / $diff, 1 );
        }

        $data = array(
            // column 1 - conversion metrics
            'impressions'           => $impressions,
            'conversion'            => $conversion,
            'new_customers'         => $new_customers,
            'returning_customers'   => $returning_customers,

            // column 2 - sales metrics
            'avg_items_sold'        => $avg_items_sold,
            'total_items_sold'      => $total_items_sold,

            // column 3 - revenue metrics
            'avg_purchase'          => $avg_purchase,
            'total_revenue'         => $total_revenue_items,
        );

        $this->logger->info(
            __METHOD__,
            array(
                'options'   => $options,
                'data'      => $data,
            )
        );

        return $data;
    }

    /**
     * Internal function to add the query options to the options array.
     *
     * @param array $options
     *
     * @return array $options
     */
    private function get_query_options( $options ) {
        $end_timestamp = LaterPay_Helper_Dashboard::get_end_timestamp( $options[ 'start_timestamp' ], $options[ 'interval' ] );
        $where = array(
            'date' => array(
                array(
                    'before'=> LaterPay_Helper_Date::get_date_query_before_end_of_day( $options[ 'start_timestamp' ] ),
                    'after' => LaterPay_Helper_Date::get_date_query_after_start_of_day( $end_timestamp ),
                ),
            ),
        );

        // add the query options to the options array
        $options [ 'query_args' ] = array(
            'order_by'  => LaterPay_Helper_Dashboard::get_order_and_group_by( $options[ 'interval' ]  ),
            'group_by'  => LaterPay_Helper_Dashboard::get_order_and_group_by( $options[ 'interval' ]  ),
            'where'     => $where,
        );

        $options [ 'most_least_query' ] = array(
            'where' => $where,
            'limit' => $options[ 'count' ],
        );

        $options [ 'query_where' ] = $where;

        return $options;
    }

    /**
     * Internal function to convert the $_POST-request-vars to an options array for the Ajax callbacks.
     *
     * @param array $post_args
     *
     * @return array $options
     */
    private function get_ajax_request_options( $post_args = array() ) {
        $interval = 'week';
        if ( isset( $post_args[ 'interval' ] ) ) {
            $interval = LaterPay_Helper_Dashboard::get_interval( $post_args[ 'interval' ] );
        }

        $count = 10;
        if ( isset( $post_args[ 'count' ] ) ) {
            $count = absint( $post_args[ 'count' ] );
        }

        $start_timestamp = strtotime( 'yesterday GMT' );
        if ( isset( $post_args[ 'start_timestamp' ] ) ) {
            $start_timestamp = $post_args[ 'start_timestamp' ];
        }

        $refresh = TRUE;
        if ( isset( $post_args[ 'refresh' ] ) ) {
            $refresh = (bool) $post_args[ 'refresh' ];
        }

        $section = (string) $post_args[ 'section' ];

        $cache_dir      = LaterPay_Helper_Dashboard::get_cache_dir( $start_timestamp );
        $cache_filename = LaterPay_Helper_Dashboard::get_cache_filename( $section, $interval, $count );
        if ( $refresh || ! file_exists( $cache_dir . $cache_filename ) ) {
            // refresh the cache, if refresh == false and the file doesn't exist
            $refresh = TRUE;
        }

        $options = array(
            // request data
            'start_timestamp'   => $start_timestamp,
            'interval'          => $interval,
            'count'             => $count,
            'refresh'           => $refresh,
            'section'           => $section,

            // cache data
            'cache_filename'    => $cache_filename,
            'cache_dir'         => $cache_dir,
            'cache_file_path'   => $cache_dir . $cache_filename,
        );

        $options = $this->get_query_options( $options );

        return $options;
    }

    /**
     * Internal function to check the section parameter on Ajax requests.
     *
     * @return void
     */
    private function validate_ajax_section_callback() {
        if ( ! isset( $_POST[ 'section' ] ) ) {
            $error = array(
                'success'   => FALSE,
                'message'   => __( 'Error, missing section on request', 'laterpay' ),
                'step'      => 3,
            );
            wp_send_json( $error );
        }

        if ( ! in_array( $_POST[ 'section' ], $this->ajax_sections ) ) {
            $error = array(
                'success'   => FALSE,
                'message'   => sprintf( __( 'Section is not allowed <code>%s</code>', 'laterpay' ), $_POST[ 'section' ] ),
                'step'      => 4,
            );
            wp_send_json( $error );
        }

        if ( ! method_exists( $this, $_POST[ 'section' ] ) ) {
            $error = array(
                'success'   => FALSE,
                'message'   => sprintf( __( 'Invalid section <code>%s</code>', 'laterpay' ), $_POST[ 'section' ] ),
                'step'      => 4,
            );
            wp_send_json( $error );
        }
    }

    /**
     * Internal function to check the wpnonce on Ajax requests.
     *
     * @return void
     */
    private function validate_ajax_nonce() {
        if ( ! isset( $_POST[ '_wpnonce' ] ) || empty( $_POST[ '_wpnonce' ] ) ) {
            $error = array(
                'success'   => false,
                'message'   => __( "You don't have sufficient user capabilities to do this.", 'laterpay' ),
                'step'      => 1,
            );
            wp_send_json( $error );
        }

        $nonce = $_POST[ '_wpnonce' ];
        if ( ! wp_verify_nonce( $nonce, $this->ajax_nonce ) ) {
            $error = array(
                'success'   => FALSE,
                'message'   => __( 'You don\'t have sufficient user capabilities to do this.', 'laterpay'),
                'step'      => 2,
            );
            wp_send_json( $error );
        }
    }

}