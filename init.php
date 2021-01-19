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
     * table id which we need
     */
    private static $_table_id;
    
    /**
     * Table id which we need
     */
    private static $_is_on;
    
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
    
    /**
     * This function starts the main job
     */
    public function gpt_wpt_start() {
            // Get all the settings from table
            $config_value = get_option( 'wpt_configure_options' );
            self::$_table_id = isset( $config_value['group_product_table_id'] ) ? $config_value['group_product_table_id'] : false;
            self::$_is_on = isset( $config_value['table_on_grouped_product'] ) ? $config_value['table_on_grouped_product'] : false;
            
            // Change the grouped products template location
            add_filter( 'woocommerce_locate_template', [$this, 'gpt_wpt_woocommerce_group_locate'], 10, 3 );
            
            // Add admin settings
            add_action( 'wpto_admin_configuration_form_top', [$this, 'gpt_wpt_add_admin_settings'], 10, 2 ); 
    }
    

    /**
     * This function has used for relocate grouped products template 
     * with our product table plugin
     * 
     * @param string $template
     * @return string $template
     */
    public function gpt_wpt_woocommerce_group_locate( $template ) {

            $_product = wc_get_product( get_the_ID() );
            
            if( is_singular( 'product' ) && $_product->is_type( 'grouped' ) ){
                if(self::$_is_on){
                    remove_action( 'woocommerce_grouped_add_to_cart', 'woocommerce_grouped_add_to_cart', 30 );
                    add_action( 'woocommerce_grouped_add_to_cart', [$this, 'gpt_woocommerce_grouped_add_to_cart'], 30 );
                }

            }
            
            return $template;
    }
    
    /**
     * Output the grouped product add to cart area.
     */
    public function gpt_woocommerce_grouped_add_to_cart() {
            
            add_filter( 'wpto_table_query_args_in_row', [$this, 'gpt_wpt_arg_manipulate'], 10, 2 );
            echo do_shortcode("[Product_Table id='". self::$_table_id ."']");
    }
    
    /**
     * This function adds grouped child products ids to table's main query args
     * 
     * @param array $args
     * @param int $table_ID
     * @return array $args
     */
    public function gpt_wpt_arg_manipulate( $args, $table_ID ){
        
            $product = wc_get_product(get_the_ID());
            $children   = $product->get_children();
            
            if( $table_ID == self::$_table_id ){
                $args['post__in'] = $children;
            }
            return $args;
    }
    
    /**
     * Add setting for admin
     */
    public function gpt_wpt_add_admin_settings( $settings, $current_config_value ){
        ?>
        <div class="section ultraaddons-panel basic configuration_page">
            <h3 class="with-background dark-background">Settings for Grouped Product</h3>
            <table class="ultraaddons-table">
                <tr class="each-style">
                    <th><label>Select Table for Grouped Product</label></th>
                    <td>
                            <?php
                            $product_tables = get_posts( array(
                                'post_type' => 'wpt_product_table',
                                'numberposts' => -1,
                                ) );
                            if( !empty( $product_tables )){ ?>
                            <select name="data[group_product_table_id]" class="wpt_fullwidth ua_input wpt_table_on_archive">
                                <option value="">Select a Table</option>
                            <?php 

                            foreach ($product_tables as $table){
                                $is_archive = isset( $current_config_value['archive_table_id'] ) && $current_config_value['archive_table_id'] == $table->ID ? __('--> currently using for archive', 'group-product-table-for-wpt') : '';
                                $selected = isset( $current_config_value['group_product_table_id'] ) && $current_config_value['group_product_table_id'] == $table->ID ? 'selected' : '';
                                echo '<option value="'. $table->ID .'" ' . $selected . '>' . $table->post_title . ' ' . $is_archive . '</option>'; 
                            }
                            ?>
                            </select>
                            <?php
                            } else { 
                                echo esc_html( 'Seems you have not created any table yet. Create a table first!', 'wpt_pro' );
                            } ?>
                            <br>
                            <label class="switch">
                                <input  name="data[table_on_grouped_product]" type="checkbox" id="table_on_grouped_product" <?php echo isset( $current_config_value['table_on_grouped_product'] ) ? 'checked="checked"' : ''; ?>>
                                <div class="slider round">
                                    <span class="on">On</span><span class="off">Off</span><!--END-->
                                </div>
                            </label>
                            <p><?php echo esc_html__( 'Enable Table on Grouped Product Page. First Select a table and check [On] to show.', 'group-product-table-for-wpt' ); ?></p>
<!--                            <p class="wpt-tips">
                                <b><?php // echo esc_html__( 'Tips:', 'group-product-table-for-wpt' ); ?></b>
                                <span><?php // echo esc_html__( 'Advance Search box is not availeable on WooCommerce Archive page. Such: shop page, product category page.', 'group-product-table-for-wpt' ); ?></span>
                            </p>-->
                    </td>
                </tr>
            </table>
            <div class="ultraaddons-button-wrapper">
                <button name="configure_submit" class="button-primary primary button">Save All</button>
            </div>
        </div>
        <?php
    }
    
    /**
     * This function shows the missing plugins notifications
     */
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
    
    /**
     * This function will show the minimum php version message
     */
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