<?php
class Plugin_Install{
    /**
     * checks if woocommerce is active/Installed or not 
     */

   
    public function pluginInstall(){
        $plugin_path = WP_PLUGIN_DIR . '/woocommerce/woocommerce.php';

        if (!in_array( $plugin_path, wp_get_active_and_valid_plugins())) {
            // add_action('admin_notices', array($this, 'adminErrorMessage'));
            return false;
        } else{
            return true;
        }       
        }

        public function adminErrorMessage()
        {
            $out = '<div class="error" id="messages"><p>';
            if (file_exists(WP_PLUGIN_DIR . '/woocommerce/woocommerce.php')) {
                $out .= 'The Woocommerce plugin is installed, but <strong>you must activate Woocommerce</strong> below for the Api from this plugin to work.';
            } else {
                $out .= 'The Woocommerce plugin must be installed for the Api from this plugin to work. <a href="' . admin_url('plugin-install.php?tab=plugin-information&plugin=woocommerce&from=plugins&TB_iframe=true&width=600&height=550') . '" class="thickbox" title="Woocommerce">Install Now.</a>';
            }
            $out .= '</p></div>';
            echo $out;
        }
}