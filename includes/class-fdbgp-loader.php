<?php

use Formsdb_Google_Sheets_Posttype\Admin\Register_Menu_Dashboard\FDBGP_Dashboard;

if (!defined('ABSPATH')) {
    die;
}

if(!class_exists('FDBGP_Loader')) {

    class FDBGP_Loader {

        protected $plugin_name;
        protected $version;
        private static $instance = null;
    
        private function __construct() {

            $this->plugin_name = 'formsdb-elementor-google-sheets-posttype';
            $this->version = FDBGP_PLUGIN_VERSION;
    
            $this->admin_menu_dashboard();
    
            $this->load_dependencies();
        }

        public static function get_instance() {
            if (null == self::$instance) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        private function load_dependencies() {
            require_once FDBGP_PLUGIN_DIR . 'admin/class-fdbgp-admin.php';
            $plugin_admin = FDBGP_Admin::get_instance($this->get_plugin_name(), $this->get_version());
        }

        private function admin_menu_dashboard() {
            if(class_exists(FDBGP_Dashboard::class)){
                $menu_pages = FDBGP_Dashboard::get_instance($this->get_plugin_name(), $this->get_version());
            }
        }

        public function get_plugin_name() {
            return $this->plugin_name;
        }

        public function get_version() {
            return $this->version;
        }

    }
     
    
}