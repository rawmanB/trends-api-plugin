<?php
if (!defined('WPINC')) {
    die;
}
/**
 * 
 * Api Integration class
 * includes all functions
 * Returns categories with subcategories
 * @param $username for basic authentication
 * @param $password for basic authentication
 * @param $baseUrl base url for the curl call
 * @param $apiVersion api version
 * @param $format either json or xml
 */

class Trends_Api_Integration
{

    private $user;
    private $pw;
    private $markup;
    // private $return_val = [];

    public function __construct()
    {
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABS_ROOT_PATH  . 'admin/admin.php';
        $this->user = get_option('trends_api_username') ? get_option('trends_api_username') : "";
        $this->pw  = get_option('trends_api_password') ? decrypt(get_option('trends_api_password')) : "";
        $this->markup  = get_option('trends_api_price_markup') ? get_option('trends_api_price_markup') : "";
    }

    /**
     * function to run on cron job to get available cats on trends api and update database 
     * 
     **/
    public function get_product_categories_trends()
    {
        $ch = curl_init();
        $username = $this->user;
        $password = $this->pw;
        $full_url = "https://nz.api.trends.nz/api/v1/categories.json"; //get categories
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        // set url
        curl_setopt($ch, CURLOPT_URL, $full_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");

        $headers = array();
        $headers[] = "Accept: application/json";
        if ($username !== '' && $password !== '') {
            $headers[] = "Authorization: Basic " . base64_encode($username . ":" . $password);
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        $info = curl_getinfo($ch);

        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);

        if ($result) {
            $results = json_decode($result);
            $data = $results->data;
            update_option('trends_api_avil_cats', $data);
        } else {
            return;
        }
    }
    /**
     * 
     * get product details from trends api
     * 
     */
    public function trends_products($cat_no, $cat_id, $page_no = 1)
    {
        $username = $this->user;
        $password = $this->pw;
        $markup = $this->markup;

        // create curl resource
        $ch = curl_init();

        $full_url = 'https://au.api.trends.nz/api/v1/products.json?&page_size=4&inc_discontinued=false&inc_inactive=0&category_no=' . $cat_no . '&page_no=' . $page_no;

        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        // set url
        curl_setopt($ch, CURLOPT_URL, $full_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");

        $headers = array();
        $headers[] = "Accept: application/json";
        if ($username !== '' && $password !== '') {
            $headers[] = "Authorization: Basic " . base64_encode($username . ":" . $password);
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        $info = curl_getinfo($ch);

        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);

        //do something with $result
        $results = json_decode($result);
        $result = $results->data;
        $page_count = $results->page_count;

        $return_val = [];

        foreach ($result as $data) {

            $product = $this->get_product_by_sku($data->code);
            $trendsArgs = array(
                'name' => $data->name,
                'price' => $data->pricing->prices[0]->price,
                'description' => $data->description
            );

            if ($product) {

                // $product->set_sold_individually(false);
                // array_push($return_val, $data->code . ' already exists');

                // $productArgs = array(
                //     'id' => $product->get_id(),
                //     'name' => $product->get_name(),
                //     'price' => $product->get_regular_price(),
                //     'description' => $product->get_description()
                // );

                //perform update functions here
                // $this->update_product($productArgs, $trendsArgs);
            } else {

                $colours = ($data->colours != '') ? explode(' ', trim($data->colours)) : explode(',', str_replace('.', '', $data->standard_colours));
                $post_id = $this->create_product(array(
                    'type'               => '', // Simple product by default
                    'name'               => __($data->name, "woocommerce"),
                    'description'        => __($data->description, "woocommerce"),
                    'short_description'  => __(" ", "woocommerce"),
                    'sku'                => $data->code,
                    'regular_price'      => $data->pricing->prices[0]->price, // product price
                    // 'sale_price'         => '',
                    'reviews_allowed'    => false,
                    'sold_individually'  => false,
                    'downloadable'       => false,
                    'virtual'            => false,
                    'catalog_visibility' => 'visible',
                    'status'             => 'publish',
                    'attributes'         => array(
                        // Taxonomy and term name values
                        'pa_color' => array(
                            'term_names' => $colours,
                            'is_visible' => true,
                            'for_variation' => true,
                        )
                    ),
                ));

                wp_set_object_terms($post_id, (int) $cat_id, 'product_cat');

                //custom fields update

                $stock_url = 'https://au.api.trends.nz/api/v1/stock/' . $data->code . '.json';

                $ch1 = curl_init();
                curl_setopt($ch1, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch1, CURLOPT_SSL_VERIFYPEER, false);

                // set url
                curl_setopt($ch1, CURLOPT_URL, $stock_url);
                curl_setopt($ch1, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch1, CURLOPT_CUSTOMREQUEST, "GET");

                $headers = array();
                $headers[] = "Accept: application/json";
                if ($username !== '' && $password !== '') {
                    $headers[] = "Authorization: Basic " . base64_encode($username . ":" . $password);
                }
                curl_setopt($ch1, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch1, CURLOPT_HTTPHEADER, $headers);

                $stock_level = curl_exec($ch1);
                $stocks = json_decode($stock_level)->data;
                update_post_meta($post_id, '_custom_product_stock', (json_encode($stocks))); //update stock from separate stock endpoint


                //update custom meta fields from products api end point
                update_post_meta($post_id, '_custom_product_wire', esc_attr($data->product_wire));
                update_post_meta($post_id, '_custom_product_last_update', esc_attr($data->last_updated));
                update_post_meta($post_id, '_custom_product_packaging', (json_encode($data->packaging)));
                update_post_meta($post_id, '_custom_product_carton', (json_encode($data->carton)));
                update_post_meta($post_id, '_custom_product_prices', (json_encode($data->pricing->prices)));
                update_post_meta($post_id, '_custom_product_secondary_colours', (json_encode($data->secondary_colours)));
                update_post_meta($post_id, '_custom_product_colours_3', (json_encode($data->colours_3)));
                update_post_meta($post_id, '_custom_product_standard_colours', (json_encode($data->standard_colours)));
                $additional_costs = $data->pricing->additional_costs;
                update_post_meta($post_id, '_custom_product_additional_cost', (json_encode($additional_costs)));

                $branding_type = $data->branding_options;
                update_post_meta($post_id, '_custom_product_branding_options', (json_encode($branding_type)));

                $media_id = [];
                foreach ($data->images as $images) {
                    $img_url = "https:" . trim($images->link);
                    $ret_id = media_sideload_image($img_url, $post_id, '', $return = 'id');
                    array_push($media_id, $ret_id);
                }

                set_post_thumbnail($post_id, $media_id[0]);

                //if there is more than 1 image - add the rest to product gallery
                if (sizeof($media_id) > 1) {
                    array_shift($media_id); //removes first item of the array (because it's been set as the featured image already)
                    update_post_meta($post_id, '_product_image_gallery', implode(',', $media_id)); //set the images id's left over after the array shift as the gallery images
                }
                array_push($return_val, $post_id);
            }
        }

        if ($page_count > $page_no) {
            $p_no = (int)$page_no + 1;
            $recursive_array = array('repeat' => 'true', 'cat_no' => $cat_no, 'cat_id' => $cat_id, 'page_no' => $p_no, 'page_count' => $page_count);
            return ($recursive_array);
            // return true;
        } else {
            $recursive_array = array('repeat' => 'false', 'cat_no' => $cat_no, 'cat_id' => $cat_id, 'page_no' => $page_no, 'page_count' => $page_count);
            return ($recursive_array);
        }
        // return $return_val;
    }

    /**
     * Check if product exists in woo commerce db
     * 
     * @return null if true
     */
    public function get_product_by_sku($sku)
    {
        global $wpdb;

        $product_id = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_sku' AND meta_value='%s' LIMIT 1", $sku));

        if ($product_id) {

            $product_type = get_post_type($product_id);
            if ($product_type == 'product') {

                return new WC_Product($product_id);
            } else {

                return new WC_Product_Variation($product_id);
            }
        }
        return null;
    }

    /**
     * Update product if any changes
     */
    public function  update_product($woo_product, $trends_product)
    {
        $product_id = $woo_product['id'];
        $nametrends = $trends_product['name'];
        $namewoo = $woo_product['name'];
        $regularPricetrends = $trends_product['price'];
        $regularPricewoo = $woo_product['price'];
        $descriptionTrends = $trends_product['description'];
        $descriptionWoo = $woo_product['description'];

        //check if logged in user can capability
        if (!current_user_can('edit_posts')) {
            return;
        }
        //if name has changed udpate
        if ($nametrends != $namewoo) {
            $post_update = array(
                'ID'         => $product_id,
                'post_title' => $nametrends
            );
            wp_update_post($post_update);
        }

        //if price has changed update
        if ($regularPricetrends != $regularPricewoo) {
            update_post_meta($product_id, '_regular_price', $regularPricetrends, $regularPricewoo);
        }

        //if description has changed udpate
        if ($descriptionTrends != $descriptionWoo) {
            $post_update = array(
                'ID'         => $product_id,
                'post_content' => $descriptionTrends
            );
            wp_update_post($post_update);
        }
    }

    /**
     * Custom function for product creation (For Woocommerce 3+ only) 
     */
    public function create_product($args)
    {

        if (!method_exists($this, 'wc_get_product_object_type') && !method_exists($this, 'wc_prepare_product_attributes'))
            return false;

        // Get an empty instance of the product object (defining it's type)
        $product = $this->wc_get_product_object_type($args['type']);
        if (!$product)
            return false;

        // Product name (Title) and slug
        $product->set_name($args['name']); // Name (title).
        if (isset($args['slug']))
            $product->set_name($args['slug']);

        // Description and short description:
        $product->set_description($args['description']);
        $product->set_short_description($args['short_description']);

        // Status ('publish', 'pending', 'draft' or 'trash')
        $product->set_status(isset($args['status']) ? $args['status'] : 'publish');

        // Visibility ('hidden', 'visible', 'search' or 'catalog')
        $product->set_catalog_visibility(isset($args['visibility']) ? $args['visibility'] : 'visible');

        // Featured (boolean)
        $product->set_featured(isset($args['featured']) ? $args['featured'] : false);

        // Virtual (boolean)
        $product->set_virtual(isset($args['virtual']) ? $args['virtual'] : false);

        // Prices
        $product->set_regular_price($args['regular_price']);
        $product->set_sale_price(isset($args['sale_price']) ? $args['sale_price'] : '');
        $product->set_price(isset($args['sale_price']) ? $args['sale_price'] :  $args['regular_price']);
        if (isset($args['sale_price'])) {
            $product->set_date_on_sale_from(isset($args['sale_from']) ? $args['sale_from'] : '');
            $product->set_date_on_sale_to(isset($args['sale_to']) ? $args['sale_to'] : '');
        }

        // Downloadable (boolean)
        $product->set_downloadable(isset($args['downloadable']) ? $args['downloadable'] : false);
        if (isset($args['downloadable']) && $args['downloadable']) {
            $product->set_downloads(isset($args['downloads']) ? $args['downloads'] : array());
            $product->set_download_limit(isset($args['download_limit']) ? $args['download_limit'] : '-1');
            $product->set_download_expiry(isset($args['download_expiry']) ? $args['download_expiry'] : '-1');
        }

        // Taxes
        if (get_option('woocommerce_calc_taxes') === 'yes') {
            $product->set_tax_status(isset($args['tax_status']) ? $args['tax_status'] : 'taxable');
            $product->set_tax_class(isset($args['tax_class']) ? $args['tax_class'] : '');
        }

        // SKU and Stock (Not a virtual product)
        if (isset($args['virtual']) && !$args['virtual']) {
            $product->set_sku(isset($args['sku']) ? $args['sku'] : '');
            $product->set_manage_stock(isset($args['manage_stock']) ? $args['manage_stock'] : false);
            $product->set_stock_status(isset($args['stock_status']) ? $args['stock_status'] : 'instock');
            if (isset($args['manage_stock']) && $args['manage_stock']) {
                $product->set_stock_status($args['stock_qty']);
                $product->set_backorders(isset($args['backorders']) ? $args['backorders'] : 'no'); // 'yes', 'no' or 'notify'
            }
        }

        // Sold Individually
        $product->set_sold_individually(isset($args['sold_individually']) ? $args['sold_individually'] : false);

        // Weight, dimensions and shipping class
        $product->set_weight(isset($args['weight']) ? $args['weight'] : '');
        $product->set_length(isset($args['length']) ? $args['length'] : '');
        $product->set_width(isset($args['width']) ?  $args['width']  : '');
        $product->set_height(isset($args['height']) ? $args['height'] : '');
        if (isset($args['shipping_class_id']))
            $product->set_shipping_class_id($args['shipping_class_id']);

        // Upsell and Cross sell (IDs)
        $product->set_upsell_ids(isset($args['upsells']) ? $args['upsells'] : '');
        $product->set_cross_sell_ids(isset($args['cross_sells']) ? $args['upsells'] : '');

        // Attributes et default attributes
        if (isset($args['attributes']))
            $product->set_attributes($this->wc_prepare_product_attributes($args['attributes']));
        if (isset($args['default_attributes']))
            $product->set_default_attributes($args['default_attributes']); // Needs a special formatting

        // Reviews, purchase note and menu order
        $product->set_reviews_allowed(isset($args['reviews']) ? $args['reviews'] : false);
        $product->set_purchase_note(isset($args['note']) ? $args['note'] : '');
        if (isset($args['menu_order']))
            $product->set_menu_order($args['menu_order']);

        // Product categories and Tags
        if (isset($args['category_ids']))
            $product->set_category_ids($args['category_ids']);
        if (isset($args['tag_ids']))
            $product->set_tag_ids($args['tag_ids']);


        // Images and Gallery
        $product->set_image_id(isset($args['image_id']) ? $args['image_id'] : "");
        $product->set_gallery_image_ids(isset($args['gallery_ids']) ? $args['gallery_ids'] : array());

        ## --- SAVE PRODUCT --- ##
        $product_id = $product->save();

        return $product_id;
    }

    // Utility function that returns the correct product object instance
    public function wc_get_product_object_type($type)
    {
        // Get an instance of the WC_Product object (depending on his type)
        if (isset($args['type']) && $args['type'] === 'variable') {
            $product = new WC_Product_Variable();
        } elseif (isset($args['type']) && $args['type'] === 'grouped') {
            $product = new WC_Product_Grouped();
        } elseif (isset($args['type']) && $args['type'] === 'external') {
            $product = new WC_Product_External();
        } else {
            $product = new WC_Product_Simple(); // "simple" By default
        }

        if (!is_a($product, 'WC_Product'))
            return false;
        else
            return $product;
    }

    public function every_one_hour_event_cron_trends()
    {
        // die();
        $products = wc_get_products(array('status' => 'publish', 'limit' => -1));
        foreach ($products as $product) {
        // die();
        $username = $this->user;
        $password = $this->pw;

        $id =  $product->get_id();    // Product ID
        // $id =  '9969';    // Product ID
        $sku =  $product->get_sku();    // Product SKU?
        // $sku =  '101457';    // Product SKU

        $path = ABS_ROOT_PATH . 'admin/log/apidata.txt';
        $stock_url = 'https://au.api.trends.nz/api/v1/products/' . $sku . '.json';

        $ch1 = curl_init();
        curl_setopt($ch1, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch1, CURLOPT_SSL_VERIFYPEER, false);

        // set url
        curl_setopt($ch1, CURLOPT_URL, $stock_url);
        curl_setopt($ch1, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch1, CURLOPT_CUSTOMREQUEST, "GET");

        $headers = array();
        $headers[] = "Accept: application/json";
        if ($username !== '' && $password !== '') {
            $headers[] = "Authorization: Basic " . base64_encode($username . ":" . $password);
        }
        curl_setopt($ch1, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch1, CURLOPT_HTTPHEADER, $headers);

        $stock_level = curl_exec($ch1);
        $stocks = json_decode($stock_level)->data[0]->stock;
        $prices = json_decode($stock_level)->data[0]->pricing[0]->prices;
        $colours = (json_decode($stock_level)->data[0]->colours != '') ? explode(',', trim(json_decode($stock_level)->data[0]->colours)) : explode(' ', str_replace('.', '', json_decode($stock_level)->data[0]->standard_colours));

        
        $data = json_decode($stock_level)->data[0];
        $additional_costs = $data->pricing[0]->additional_costs;
        $branding_type = $data->branding_options;

        $cats = $data -> categories;

        $catNum = [];
        $termId = [];
        foreach($cats as $cat){
            $catNum[] = "'".$cat->num."'";
        }

        $tags = implode(", ", $catNum);
        global $wpdb;
        $table_prefix = $wpdb->prefix;
        $sql = "SELECT tm.term_id from ".$table_prefix."termmeta tm JOIN ".$table_prefix."terms wt WHERE tm.meta_key='trendz' and tm.meta_value IN ( ". $tags ." ) and tm.term_id = wt.term_id";

        $results = $wpdb->get_results($sql);

        foreach ($results as $result){
            $termId[] = $result->term_id;
        }

        sleep(1);
        if ($colours) {
            wp_set_object_terms($id, $colours, 'pa_color', true);
            $att_color = array(
                'pa_color' => array(
                    'name'        => 'pa_color',
                    'value'       => $colours,
                    'is_visible'  => '1',
                    'is_taxonomy' => '1'
                )
            );

            update_post_meta($id, '_product_attributes', $att_color);
        }


        wp_set_post_terms($id, $termId, 'product_cat', true);

        update_post_meta($id, '_custom_product_stock', (json_encode($stocks)));
        update_post_meta($id, '_regular_price', $prices[0]->price);
        update_post_meta($id, '_custom_product_prices', (json_encode($prices)));

        //update additional product details
        update_post_meta($id, '_custom_product_wire', esc_attr($data->product_wire));
        update_post_meta($id, '_custom_product_last_update', esc_attr($data->last_updated));

        update_post_meta($id, '_custom_product_packaging', (json_encode($data->packaging)));
        update_post_meta($id, '_custom_product_carton', (json_encode($data->carton)));
        update_post_meta($id, '_custom_product_additional_cost', (json_encode($additional_costs)));

        update_post_meta($id, '_custom_product_branding_options', (json_encode($branding_type)));

        // file_put_contents($path, json_encode($termId), FILE_APPEND);

        }
    }

    public function update_products_from_trends($schedules)
    {
        $schedules['every_one_hour'] = array(
            'interval'  => 5400, //since cron job function has become heavy, it might cause server time out so time increased to 1.5 hours
            'display'   => __('Every 1 hour', 'textdomain')
        );
        return $schedules;
    }


    // Utility function that prepare product attributes before saving
    function wc_prepare_product_attributes($attributes)
    {
        global $woocommerce;

        $data = array();
        $position = 0;

        foreach ($attributes as $taxonomy => $values) {
            // var_dump($taxonomy);die();
            if (!taxonomy_exists($taxonomy))
                continue;

            // {
            //     global $wpdb; 

            //     $insert = $wpdb->insert(
            //         $wpdb->prefix . 'woocommerce_attribute_taxonomies',
            //         array(
            //             'attribute_label'   => $taxonomy,
            //             'attribute_name'    => $taxonomy,
            //             'attribute_type'    => 'type',
            //             'attribute_orderby' => 'order_by',
            //             'attribute_public'  => 1
            //         )
            //     );
            //     var_dump($insert);
            // }
            // wp_insert_term($taxonomy,'product_attributes',array('description' => $taxonomy,'slug' => $taxonomy));


            // Get an instance of the WC_Product_Attribute Object
            $attribute = new WC_Product_Attribute();


            $term_ids = array();
            // var_dump($values['term_names']);
            // print_r($values['term_names']);
            // Loop through the term names
            foreach ($values['term_names'] as $term_name) {

                if ($term_name != '') {
                    if (term_exists($term_name, $taxonomy))
                        // Get and set the term ID in the array from the term name
                        $term_ids[] = get_term_by('name', $term_name, $taxonomy)->term_id;
                    else
                        $term_ids[] = wp_create_term($term_name, $taxonomy);
                }
            }

            $taxonomy_id = wc_attribute_taxonomy_id_by_name($taxonomy); // Get taxonomy ID

            $attribute->set_id($taxonomy_id);
            $attribute->set_name($taxonomy);
            $attribute->set_options($term_ids);
            $attribute->set_position($position);
            $attribute->set_visible($values['is_visible']);
            $attribute->set_variation($values['for_variation']);

            $data[$taxonomy] = $attribute; // Set in an array
            $position++; // Increase position

            // print_r($term_ids);
        }
        // print_r($data);
        // die();
        return $data;
    }
}
