<?php
if (!defined('WPINC')) {
    die;
}

/**
 * Class to create product categories on the basis of selected cats on settings page
 */

 class Trends_Create_Cat{
    private $cats;
    private $selectedCats;
    public function __construct()
    {
        require_once ABS_ROOT_PATH  . 'includes/trends-integration.php';
    }
    
 }

 