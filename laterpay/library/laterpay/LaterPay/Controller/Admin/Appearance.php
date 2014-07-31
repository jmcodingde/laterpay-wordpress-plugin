<?php

class LaterPay_Controller_Admin_Appearance extends LaterPay_Controller_Abstract
{

	/**
	 * @see LaterPay_Controller_Abstract::load_assets()
	 */
	public function load_assets() {
        parent::load_assets();
        global $laterpay_version;

        // load page-specific JS
        wp_register_script(
            'laterpay-ezmark',
            LATERPAY_ASSETS_PATH . '/js/vendor/jquery.ezmark.min.js',
            array( 'jquery' ),
            $laterpay_version,
            true
        );
        wp_register_script(
            'laterpay-backend-appearance',
            LATERPAY_ASSETS_PATH . '/js/laterpay-backend-appearance.js',
            array( 'jquery', 'laterpay-ezmark' ),
            $laterpay_version,
            true
        );
        wp_enqueue_script( 'laterpay-ezmark' );
        wp_enqueue_script( 'laterpay-backend-appearance' );
    }

	/**
	 * @see LaterPay_Controller_Abstract::render_page()
	 */
    public function render_page() {
        $this->load_assets();

        $this->assign( 'plugin_is_in_live_mode',     get_option( 'laterpay_plugin_is_in_live_mode' ) == 1 );
        $this->assign( 'show_teaser_content_only',   get_option( 'laterpay_teaser_content_only' ) == 1 );
        $this->assign( 'top_nav',                    $this->get_menu() );
        $this->assign( 'admin_menu',                 LaterPay_Helper_View::get_admin_menu() );

        $this->render( 'backend/tabs/appearance' );
    }

    /**
     * Process Ajax requests from appearance tab.
     *
     * @return void
     */
    public static function process_ajax_requests() {
        if ( isset( $_POST['form'] ) ) {
            // check for required privileges to perform action
            if ( ! LaterPay_Helper_User::can( 'laterpay_edit_plugin_settings' ) ) {
                echo Zend_Json::encode(
                    array(
                        'success' => false,
                        'message' => __( 'You don´t have sufficient user privileges to do this.', 'laterpay' )
                    )
                );
                die;
            }

            if ( function_exists('check_admin_referer') ) {
                check_admin_referer( 'laterpay_form' );
            }

            switch ( $_POST['form'] ) {
                // update presentation mode for paid content
                case 'teaser_content_only':
                    $result = update_option( 'laterpay_teaser_content_only', $_POST['teaser_content_only'] );
                    if ( $result ) {
                        if ( get_option( 'laterpay_teaser_content_only' ) ) {
                            echo Zend_Json::encode(
                                array(
                                    'success' => true,
                                    'message' => __( 'Visitors will now see only the teaser content of paid posts.', 'laterpay' )
                                )
                            );
                        } else {
                            echo Zend_Json::encode(
                                array(
                                    'success' => true,
                                    'message' => __( 'Visitors will now see the teaser content of paid posts plus an excerpt of the real content under an overlay.', 'laterpay' )
                                )
                            );
                        }
                    } else {
                        echo Zend_Json::encode(
                            array(
                                'success' => false,
                                'message' => __( 'An error occurred when trying to save your settings. Please try again.', 'laterpay' )
                            )
                        );
                    }
                    die;
                    break;

                default:
                    echo Zend_Json::encode(
                        array(
                            'success' => false,
                            'message' => __( 'An error occurred when trying to save your settings. Please try again.', 'laterpay' )
                        )
                    );
                    die;
                    break;
            }
        }
    }

}