<?php
/**
 * Plugin Name: Group Product Table Add-Ons for Woo Product Table
 * Plugin URI: 
 * Description: Show grouped products as table using Woo Product Table plugin
 * Author: autocircle
 * Author URI: 
 * Text Domain: group-product-table-for-wpt
 * Domain Path: /languages/
 * 
 * Version: 1.0.0
 * Requires at least:    4.0.0
 * Tested up to:         5.4.2
 * WC requires at least: 3.7
 * WC tested up to:      4.2.2
 */

if ( !defined( 'ABSPATH' ) ) {
    die();
}

if ( !defined( 'GPT_WPT_VERSION' ) ) {
    define( 'GPT_WPT_VERSION', '1.0.0' );
}

if ( !defined( 'GPT_WPT_NAME' ) ) {
    define( 'GPT_WPT_NAME', 'Group Product Table Add-Ons for Woo Product Table' );
}

include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

GPT_WPT::getInstance();

class GPT_WPT {
    
    /**
     * Core singleton class
     * @var self - pattern realization
     */
    private static $_instance;
    
    /**
     * Trying to commit and push something
     * Minimum PHP Version
     *
     * @since 1.0.0
     *
     * @var string Minimum PHP version required to run the plugin.
     */
    const MINIMUM_PHP_VERSION = '5.6';
    
    /**
     * Create instance
     */
    public static function getInstance() {
        if (!( self::$_instance instanceof self )) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }
    
    public function __construct() {
        
        if ( !is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
            add_action( 'admin_notices', [$this, 'admin_notice_missing_main_plugin'] );
            return;
        }
        if ( !is_plugin_active( 'woo-product-table/woo-product-table.php' )) {
            add_action( 'admin_notices', [$this, 'admin_notice_missing_main_plugin'] );
            return;
        }
        
        // Check for required PHP version
        if ( version_compare( PHP_VERSION, self::MINIMUM_PHP_VERSION, '<' ) ) {
            add_action( 'admin_notices', [ $this, 'admin_notice_minimum_php_version' ] );
            return;
        }
        
        add_action(
	'plugins_loaded',
	function () {
		load_plugin_textdomain( 'group-product-table-for-wpt', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	});
        
       $this->gpt_wpt_start();
        
       
    }
    
    public function gpt_wpt_start() {
        
            add_filter( 'woocommerce_locate_template', [$this, 'gpt_wpt_woocommerce_group_locate'], 10, 3 );
            add_filter( 'wpto_localize_data', [$this, 'gpt_wpto_localize_data'] );
        
        
    }
    


    public function gpt_wpt_woocommerce_group_locate( $template, $template_name, $template_path ) {

            $_product = wc_get_product( get_the_ID() );

            if( is_singular( 'product' ) && $_product->is_type( 'grouped' ) ){
                    remove_action( 'woocommerce_grouped_add_to_cart', 'woocommerce_grouped_add_to_cart', 30 );
                    add_action( 'woocommerce_grouped_add_to_cart', [$this, 'gpt_woocommerce_grouped_add_to_cart'], 30 );

            }
            // Return what we found
            return $template;
    }
    
    /**
     * Output the grouped product add to cart area.
     */
    public function gpt_woocommerce_grouped_add_to_cart() {
            
            add_filter( 'wpto_table_query_args_in_row', [$this, 'gpt_wpt_arg_manipulate'], 10, 2 );
            echo do_shortcode("[Product_Table id='71' name='বাংলাদেশের স্বপ্ন']");
    }
    
    public function gpt_wpt_arg_manipulate( $args, $table_ID ){
        
            $product = wc_get_product(get_the_ID());
            $children   = $product->get_children();

            if( $table_ID == 71 ){
                $args['post__in'] = $children;
            }
            return $args;
}
    
    public function admin_notice_missing_main_plugin() {
        
        if (isset($_GET['activate']))
            unset($_GET['activate']);
        
        $link = $name = '';
        if( !is_plugin_active('woocommerce/woocommerce.php') ){
            $link = 'https://wordpress.org/plugins/woocommerce/';
            $name = __('WooCommerce', 'group-product-table-for-wpt');
        }
        
        if( !is_plugin_active('woo-product-table/woo-product-table.php') ){
            $link = 'https://wordpress.org/plugins/woo-product-table/';
            $name = __('Woo Product Table', 'group-product-table-for-wpt');
        }
        
        $message = sprintf(
                esc_html__('"%1$s" requires "%2$s" to be installed and activated.', 'group-product-table-for-wpt'),
                '<strong>' . GPT_WPT_NAME . '</strong>',
                '<strong><a href="' . esc_url( $link ) . '" target="_blank">' . esc_html( $name ) . '</a></strong>'
        );

        printf('<div class="notice notice-error is-dismissible"><p>%1$s</p></div>', $message);
    }
    
    public function admin_notice_minimum_php_version() {

           if ( isset( $_GET['activate'] ) ) unset( $_GET['activate'] );

           $message = sprintf(
                   /* translators: 1: Plugin name 2: PHP 3: Required PHP version */
                   esc_html__( '"%1$s" requires "%2$s" version %3$s or greater.', 'fscart' ),
                   '<strong>' . GPT_WPT_NAME . '</strong>',
                   '<strong>' . esc_html__( 'PHP', 'fscart' ) . '</strong>',
                    self::MINIMUM_PHP_VERSION
           );

           printf( '<div class="notice notice-error is-dismissible"><p>%1$s</p></div>', $message );

    }
}