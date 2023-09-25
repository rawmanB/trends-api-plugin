<?php
/**
 * @link            https://www.cogbranding.com.au
 * @since           1.0.0
 * @package         trends-api-product-import
 * 
 * @wordpress-plugin
 * 
 * Plugin Name:     Trends Api Product Import Woo
 * Plugin URI:      https://www.cogbranding.com.au
 * Description:     This plugin imports the products from Trends.nz site's API which is provided by trenz website themselves
 * Version:         1.0.0
 * Authot:          COG
 * Authot URI:      https://www.cogbranding.com.au
 * License:         GPL-2.0+
 * License URI:     http://www.gnu.org/license/gpl-2.0.txt
 * Text Domain:     trends-cog
 * Domain Path:     /languages
 * 
 */

//Return if this file is call directly
 if (!defined('WPINC')){
    die();
 }


 /**
  * Define contanst
  */

  define('PLUGIN_VERSION', '1.0.0');
  define('PLUGIN_TEXTDOMAIN', 'trends-cog');
  define('PLUGIN_NAME', 'Trends API Product Import Woo');
  define('ABS_ROOT_PATH', plugin_dir_path(__FILE__));
  define('ABS_ROOT_URL', plugin_dir_url(__FILE__));
  define('ABS_FILE_PATH', __FILE__);
  define('PLUGIN_MIN_PHP_VERSION', '7.0' );
  define('PLUGIN_WP_VERSION', '5.3' );




/**
 * Only activates this plugin if woo commerce is installed
 */
  function trends_api_plugin_init() {


    if( !function_exists( 'is_plugin_inactive' ) ) :
      require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
    endif;


    if( is_plugin_inactive( 'woocommerce/woocommerce.php' ) ) :
        add_action( 'admin_init', 'trends_api_product_import_deactivate' );
        add_action( 'admin_notices', 'trends_api_product_import_admin_notice' );

        function trends_api_product_import_deactivate() {
            deactivate_plugins( plugin_basename( __FILE__ ) );
        }

        
        function trends_api_product_import_admin_notice() {
            echo '<div class="error"><p><strong>WooCommerce</strong> must be installed and activated for the API to work.</p></div>';
            if( isset( $_GET['activate'] ) ) unset( $_GET['activate'] );
        }
    endif;
}

  require_once ABS_ROOT_PATH. 'includes/trends-integration.php';
  require_once ABS_ROOT_PATH. 'includes/install.php';

  $install = new Plugin_Install();

  if ($install->pluginInstall()){
    initPlugin();
  }else{
    add_action( 'plugins_loaded', 'trends_api_plugin_init' );
    return;
  }

/**
 * Run the hooks and filters
 */

 function initPlugin(){
   $plugin = new Trends_Inegration();
   $plugin->initialize();
 }

