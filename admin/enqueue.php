<?php
// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
class Enqueue_Class_Admin
{
    public function enqueue_scripts()
    {
       $tab = isset($_GET['tab'] ) ? $_GET['tab'] : '';
       if ($tab == 'trends_api_setting'){
        wp_enqueue_style('bootstrap-style-css', 'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css', '', '', '');
        wp_enqueue_script('bootstrap-js-custom', 'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.min.js', '', '5.1', true);
        wp_enqueue_script('jquery-js','https://code.jquery.com/jquery-3.6.1.min.js');
        wp_enqueue_script('custom_script', plugin_dir_url(__FILE__) . 'js/custom.js', 'jQuery', '', true);
        wp_enqueue_style('custom_style-css', plugin_dir_url(__FILE__) . 'css/style.css', '', '', '');
        wp_localize_script( 'custom_script', 'ajax_params',
            array( 'ajax_url' => admin_url( 'admin-ajax.php' ), 'place' => '' ) );
       }
       
    }
}