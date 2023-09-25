<?php
if (!defined('WPINC')){
    die;
}

/**
 * Main class 
 */

 class Trends_Inegration{
    protected $loader;
    public $version;
    private $user;
    private $pw;
    public $text_domain;
    public $avail_cats;
    public $selected_cats;
    private $markup;

    public function __construct()
    {
        if (defined('PLUGIN_VERSION')) {
            $this->version = PLUGIN_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        if (defined('PLUGIN_TEXTDOMAIN')) {
            $this->text_domain = PLUGIN_TEXTDOMAIN;
        } else {
            $this->text_domain = 'trends-cog';
        }
        $this->load_all_dependencies();
        $this->add_setting_tab_woo();
        $this->run_stocks_cron();
    }

    public function run_stocks_cron(){
        // Schedule an action if it's not already scheduled
        if (!wp_next_scheduled('update_products_from_trends')) {
            wp_schedule_event(time(), 'every_one_hour', 'update_products_from_trends');
        }
    }

    /**
     * return avail cats
     */

    public function returnAvailCats(){
        return $this->avail_cats;
    }
    /**
     * return selected cats
     */
    public function returnSelectedCats(){
        return $this->selected_cats;
    }

    /**
    * load all dependencies
    */

     public function load_all_dependencies(){
        require_once ABS_ROOT_PATH . 'includes/loader.php';
        require_once ABS_ROOT_PATH  . 'admin/settings.php';
        require_once ABS_ROOT_PATH  . 'admin/enqueue.php';
        require_once ABS_ROOT_PATH  . 'admin/includes/create-cat.php';
        require_once ABS_ROOT_PATH  . 'admin/includes/api-integration.php';

        $this->loader = new Plugin_Loader();
     }

     /**
      * register new function
      */

      public function add_setting_tab_woo(){
        $settings = new api_setting_tab();
        $admin = new Enqueue_Class_Admin();
        // $catg = new Trends_Create_Cat();
        $apiInt = new Trends_Api_Integration();

        $this->loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_scripts');
        $this->loader->add_filter( 'woocommerce_settings_tabs_array', $settings, 'add_settings_tab', 50);
        $this->loader->add_action( 'woocommerce_settings_trends_api_setting', $settings ,'settings_tab' );
        $this->loader->add_action( 'woocommerce_update_options_trends_api_setting', $settings , 'update_settings' );
        $this->loader->add_action( 'wp_ajax_create_parent_cat',$settings,'create_parent_cat');
        $this->loader->add_action( 'wp_ajax_nopriv_create_parent_cat',$settings,'create_parent_cat');
        $this->loader->add_action( 'wp_ajax_create_products',$settings,'create_products');
        $this->loader->add_action( 'wp_ajax_nopriv_create_products',$settings,'create_products');
        $this->loader->add_action( 'woocommerce_product_options_general_product_data',$settings, 'woocommerce_product_custom_fields' );
        $this->loader->add_action( 'woocommerce_process_product_meta',$settings, 'woocommerce_product_custom_fields_save' );

        $this->loader->add_action('update_products_from_trends', $apiInt, 'every_one_hour_event_cron_trends' );
        $this->loader->add_filter('cron_schedules', $apiInt, 'update_products_from_trends');

        $this->loader->add_filter( 'rest_prepare_post',$settings, 'trends_fix_decode_rest_api', 10, 3);
        $this->loader->add_filter('woocommerce_get_price_html',$settings, 'trends_change_product_price_display', 10, 2);

      }
      /**
       * Get Creds
       */

       public function get_credentials__cog(){
        $this->user = get_option('trends_api_username') ? get_option('trends_api_username') : "";
        $this->pw   = get_option('trends_api_password') ? get_option('trends_api_password') : "";
        $this->markup   = get_option('trends_api_price_markup') ? get_option('trends_api_price_markup') : "";

        return array(
            'user'=> $this->user,
            'pass'=> $this->pw,
            'markup'=>$this->markup );
       }

    /**
     * Returns available categories in trends api
     */
    public function get_categories_avail(){
        return get_option('trends_api_avil_cats') ? get_option('trends_api_avil_cats') : [];
    }

    /**
     * Returns selected cats from the database
     */
    public function get_categories_selected(){
        return get_option('trends_api_cats') ? get_option('trends_api_cats') : [];
    }
    /**
     * Returns selected woocommerce product cats from the database
     */
    public function get_woo_product_categories(){
        $registeredCats = [];
        $categories = get_terms( ['taxonomy' => 'product_cat', 'hide_empty' => false] );

        foreach ($categories as $c){
            $registeredCats[]=$c->name;
        }

        return $registeredCats;
    }

    /**
    * Initialize all filters and hooks
    */
     public function initialize(){
        $this->avail_cats = $this->get_categories_avail();
        $this->selected_cats = $this->get_categories_selected();
        $this->loader->run();
     }

 }