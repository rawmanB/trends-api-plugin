<?php

if (!defined('WPINC')) {
    die;
}
class api_setting_tab {

    private $cats;
    private $selectedCats;
    private $creds;

    public function __construct()
    {
        require_once ABS_ROOT_PATH  . 'includes/trends-integration.php';
        require_once ABS_ROOT_PATH  . 'admin/includes/api-integration.php';
        require_once ABS_ROOT_PATH  . 'admin/admin.php';
      
    }
    /**
     * Register new tab on woocommerce setting page
     * this is call back function and is called by woocommerce hook woocommerce_settings_tabs_array in during init
     */
    public function add_settings_tab( $settings_tabs ) {
        $settings_tabs['trends_api_setting'] = __( 'Trends API Integration', PLUGIN_TEXTDOMAIN );
        return $settings_tabs;
    }

    /**
     * Returns settings fields
     */
    public function settings_tab() {
       $this->get_settings();
    }
    
    /* Uses the WooCommerce options API to save settings via the @see woocommerce_update_options() function.
     *
     * @uses woocommerce_update_options()
     * @uses get_settings()
     */
    public function update_settings() {
        

        $u = isset ($_POST['trends_api_username']) ? sanitize_user($_POST['trends_api_username']) : "";
        $p = isset ($_POST['trends_api_password']) ? $_POST['trends_api_password']: "";
        $c = isset ($_POST['trends_api_cats']) ? $_POST['trends_api_cats']: "";
        $markup = isset ($_POST['trends_api_price_markup']) ? sanitize_text_field($_POST['trends_api_price_markup']): "";

        update_option('trends_api_username', $u,true);
        update_option('trends_api_password', encrypt($p), true);
        update_option('trends_api_cats',$c , true);
        update_option('trends_api_price_markup',$markup , true);
        $api_integration = new Trends_Api_Integration();
        $api_integration->get_product_categories_trends();
        // $this->create_parent_cat();
    }


    /*  
     Get all the settings for this plugin for @see woocommerce_admin_fields() function.
     *
     * @return array Array of settings for @see woocommerce_admin_fields() function.
     */
    public function get_settings() {
        $mainclass = new Trends_Inegration();
        $this->cats = $mainclass->get_categories_avail();
        $this->selectedCats = $mainclass->get_categories_selected();
        $this->creds = $mainclass->get_credentials__cog();

        ob_start();?>
            <div id="loader_gif">
                <img src="<?php echo ABS_ROOT_URL.'admin/image/loader.gif'?>" alt="">
                <div class=""><h1> <?php _e('Sync in progress...', 'woocommerce')?></h1></div>
            </div>
            <div class="row mb-10">
            <div class="alert alert-dark" role="alert">NOTE : Please Remember to save changes (at the bottom of the page) before strating the Sync.</div>
               <div class="col col-md-6"> <h1>     
                    <?php _e('Trends API Settings', 'woocommerce')?>
                </h1>
                </div>   
                <div class="col col-md-6" style="text-align:right; "><button type="submit" name="sync" id="sync_products_now" value="sync" class="btn btn-primary" style="max-width:100px;margin-right:50px">Sync Now</button></div>
            </div>
        <table class="form-table cog_trends_settings">
            <tbody>
                <tr>
                    <th>
                        <label for="username">
                            <?php _e('Username', 'woocommerce')?>
                        </label>
                    </th>
                    <td class="forminp">
                        
                        <input class="regular-text" id="username" name="trends_api_username" type="text" value="<?php echo $this->creds['user']?>">
                    </td>
                    
                </tr>

                <tr>
                    <th>
                        <label for="password">
                            <?php _e('Password', 'woocommerce')?>
                        </label>
                    </th>
                    <td class="forminp">
                        <input class="regular-text" id="password" name="trends_api_password" type="password" value="<?php echo decrypt($this->creds['pass'])?>">
                    </td>
                </tr>

                <tr>
                    <th>
                        <label for="price_markup">
                           
                            <?php _e('Price Markup', 'woocommerce')?>

                        </label>
                    </th>
                    <td class="forminp">
                        
                        <input class="regular-text" id="price_markup" name="trends_api_price_markup" type="text" value="<?php echo $this->creds['markup']?>">
                    </td>
                    
                </tr>

                <tr>
                    <th>
                        <label for="sync_categories">
                        <?php _e('Categories to sync', 'woocommerce')?>
                        </label>
                    </th>
                   
                    <td class="forminp">
                        <?php
                         $this->display_products_cats_trends()?>
                    </td>

                </tr>

            </tbody>
        </table>
        <?php echo ob_get_clean();
    }

    /**
     * Callback function to display list on dropdown in woocommerce settings
     */

     public function display_products_cats_trends(){
        
        $selectedCats = (is_array($this->selectedCats) ? $this->selectedCats : []);
        $cats = (is_array($this->cats) ? $this->cats : []);?>
            <div class="form-group col-sm-8">
                <div id="myMultiselect" class="multiselect">
                    <div id="" class="row">
                        <?php 
                        foreach($cats as  $cat){
                            $checkedp = in_array($cat->number, $selectedCats) ? "checked" : "";
                            echo '<div class="lists-cat col col-sm col-md-4"> <ul>';
                            echo '<li><label style="margin-bottom:4px;font-weight:bold" for="'.$cat->number.'"><input '.$checkedp.' name="trends_api_cats[]" data-name="'.$cat->name.'" type="checkbox" id="'.$cat->number.'" class="parent-'.$cat->number.'" value="'.$cat->number.'" /> '.$cat->name.'</label></li>';
                            foreach ($cat->sub_categories as $sub_cat){
                                $checkeds = in_array($sub_cat->number, $selectedCats) ? "checked" : "";
                            echo '<li><label style="margin-left:12px; margin-bottom:4px" for="'.$sub_cat->number.'"><input '.$checkeds.' onClick="childCheckBox(\''.$cat->number.'\')" name="trends_api_cats[]" data-name="'.$sub_cat->name.'" type="checkbox" id="'.$sub_cat->number.'" class="'.$cat->number.'-child child_categories"  value="'.$sub_cat->number.'" /> '.$sub_cat->name.'</label></li>';}
                            echo ' </ul></div>';
                        }?> 
                                             
                    </div>
                </div>
            </div>
      <?php 
     }

    /**
     * create categories from selected cats from setting page of woocommerce
     */
    public function create_parent_cat(){
        $mainclass = new Trends_Inegration();
        $this->cats = $mainclass->get_categories_avail();
        $this->selectedCats = $mainclass->get_categories_selected();

        $countarray = count($this->cats);
        for($counter = 0; $counter < $countarray; $counter++){

            $parent_id = $this->cats[$counter]->number;
            $parent_name = $this->cats[$counter]->name;                
            if(in_array($parent_id,$this->selectedCats)){
                $parent_name = $this->cats[$counter]->name;
                $sub_categories = $this->cats[$counter]->sub_categories;

                $category = get_term_by('name', $parent_name, 'product_cat');
                if (!$category){
                    $woocat = wp_insert_term($parent_name, 'product_cat');
                    $woo_parent_id = $woocat['term_id'];
                    add_term_meta($woo_parent_id, 'trendz', $parent_id);
                    $this->create_sub_categories($woo_parent_id, $sub_categories, $this->selectedCats);                    
                }else{
                    $woo_parent_id = $category->term_id;
                    $this->create_sub_categories($woo_parent_id, $sub_categories, $this->selectedCats);
                }
               
            }
        }
    }

    /**
     * creates sub categories of products
     */

    public function create_sub_categories($parent_id, $sub_cat_arr, $selected_cat_arr){
        $api_fucntion = new Trends_Api_Integration(); 
        foreach( $sub_cat_arr  as $ele){
            $name = $ele->name;
            $number = $ele->number;
            if(in_array($number,$selected_cat_arr)){
                $sub_category = get_term_by('name', $name, 'product_cat');
                if (!$sub_category){
                    $subcat = wp_insert_term($name, 'product_cat', array(
                        'parent' => $parent_id,
                        ) );
                    add_term_meta($subcat['term_id'], 'trendz', $number);
                    // $api_fucntion->trends_products($number, $subcat['term_id']);
                }else{
                    // $api_fucntion->trends_products($number, $sub_category->term_id);

                }
            }
        }
    }

    /**
     * loops through the product creation to prevent resource overuse and error 500
     */

    public function create_products(){
        $api_fucntion = new Trends_Api_Integration(); 
        
        $number = $_POST['number'];
        $category = (int)$_POST['category'];
        $page = (int)(isset($_POST['page']))?$_POST['page']:1;

        if(!empty($_POST['category'])){
            $category = $_POST['category'];
        }
        else{
            global $wpdb;
            $table_prefix = $wpdb->prefix;
            $sql = "SELECT tm.term_id from ".$table_prefix."termmeta tm JOIN ".$table_prefix."terms wt WHERE tm.meta_key='trendz' and tm.meta_value='". $number."' and tm.term_id = wt.term_id";
      
            $results = $wpdb->get_results($sql);
            $category = $results[0]->term_id;
        }
        $ret = $api_fucntion->trends_products($number, $category, $page); 
        echo json_encode($ret);
        die();
    }



    /**
     *add custom metafields to produts 
     */

    public function woocommerce_product_custom_fields () {
        global $woocommerce, $post;
        echo '<div class=" product_custom_field ">';
        woocommerce_wp_text_input(
            array(
                'id' => '_custom_product_wire',
                'placeholder' => 'Product Wire',
                'label' => __('Product Wire', 'woocommerce'),
                'desc_tip' => 'true'
            )
        );
        woocommerce_wp_text_input(
            array(
                'id' => '_custom_product_branding_options',
                'placeholder' => 'Branding Option Type',
                'label' => __('Branding Option Type', 'woocommerce'),
                'desc_tip' => 'true'
            )
        );
        woocommerce_wp_text_input(
            array(
                'id' => '_custom_product_last_update',
                'placeholder' => 'Last Stock Update at',
                'label' => __('Last Stock Update at', 'woocommerce'),
                'desc_tip' => 'true'
            )
        );
        woocommerce_wp_text_input(
            array(
                'id' => '_custom_product_additional_cost',
                'placeholder' => 'Additional Cost',
                'label' => __('Additional Cost', 'woocommerce'),
                'desc_tip' => 'true'
            )
        );
        woocommerce_wp_text_input(
            array(
                'id' => '_custom_product_stock',
                'placeholder' => 'Available Stocks',
                'label' => __('Available Stocks', 'woocommerce'),
                'desc_tip' => 'true'
            )
        );
        woocommerce_wp_text_input(
            array(
                'id' => '_custom_product_packaging',
                'placeholder' => 'Available packaging',
                'label' => __('Available packaging', 'woocommerce'),
                'desc_tip' => 'true'
            )
        );
        woocommerce_wp_text_input(
            array(
                'id' => '_custom_product_carton',
                'placeholder' => 'Available carton',
                'label' => __('Available cartons', 'woocommerce'),
                'desc_tip' => 'true'
            )
        );
        woocommerce_wp_text_input(
            array(
                'id' => '_custom_product_prices',
                'placeholder' => 'Prices',
                'label' => __('Prices', 'woocommerce'),
                'desc_tip' => 'true'
            )
        );

        woocommerce_wp_text_input(
            array(
                'id' => '_custom_product_secondary_colours',
                'placeholder' => 'Secondary colours',
                'label' => __('Secondary colours', 'woocommerce'),
                'desc_tip' => 'true'
            )
        );

        woocommerce_wp_text_input(
            array(
                'id' => '_custom_product_colours_3',
                'placeholder' => 'colours_3',
                'label' => __('colours_3', 'woocommerce'),
                'desc_tip' => 'true'
            )
        );

        woocommerce_wp_text_input(
            array(
                'id' => '_custom_product_standard_colours',
                'placeholder' => 'Standard colours',
                'label' => __('Standard colours', 'woocommerce'),
                'desc_tip' => 'true'
            )
        );
        echo '</div>';
    }
    public function trends_fix_decode_rest_api($response, $post, $request) {
        if (isset($post)) {
            $decodedTitle = html_entity_decode($post->post_title);
            $response->data['title']['rendered'] = $decodedTitle;
            $decodedPostTitle = html_entity_decode($response->data['title']['rendered']);
            $response->data['title']['rendered'] = $decodedPostTitle;
        }
        return $response;
    }

    /**
     * Customzie displayed price in shop page 
     */
    public function trends_change_product_price_display($price, $product)
    {
        $markup  = get_option('trends_api_price_markup') ? get_option('trends_api_price_markup') : "";

        $prices = json_decode($product->get_meta('_custom_product_prices'));
        $leastPrice = end($prices)->price * $markup;

        $roundPrice = sprintf('%0.2f', $leastPrice);
        $price  =  '<p>From : $' . $roundPrice . '/unit</p> ';
        return $price;
    }
}
