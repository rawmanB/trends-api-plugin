<?php
if (!defined('WPINC')) {
    die;
}
class TRENDS_SINGLE_PRODUCTS_METADATA{

    public function trends_additional_costs(){ 

        $id = get_the_ID();

        echo $id;
    ob_start();?>

    <?php
    return ob_get_clean();
    }
}