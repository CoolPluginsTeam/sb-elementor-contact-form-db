<?php


if (!defined('ABSPATH')) {
    die;
}

if (!class_exists('CoolForm_Widget_Loader')) {
    class CoolForm_Widget_Loader
    {

        protected $plugin_name;

        /**
         * The current version of the plugin.
         *
         * @since    1.0.0
         * @access   protected
         * @var      string    $version    The current version of the plugin.
         */
        protected $version;

        /**
         * The loader instance.
         *
         * @since    1.0.0
         * @access   private
         * @var      CFL_Addons_Loader    $instance    The loader instance.
         */
        private static $instance = null;

        public function __construct() {
            $this->version = FDBGP_PLUGIN_VERSION;

            add_action( 'cool_form/forms/actions/register', array($this,'register_new_form_actions') );
        }

        public function register_new_form_actions($actions_registrar){


            if(is_plugin_active( 'extensions-for-elementor-form/extensions-for-elementor-form.php' )){
                require_once FDBGP_PLUGIN_DIR . 'includes/widgets/coolform-modules/coolform-fdbgp-form-sheets-action.php';
                $actions_registrar->register(new CoolForm_FDBGP_Form_Sheets_Action);
            }
            
            if(is_plugin_active( 'cool-formkit-for-elementor-forms/cool-formkit-for-elementor-forms.php' )){
                require_once FDBGP_PLUGIN_DIR . 'includes/widgets/coolform-modules/coolformpro-fdbgp-form-sheets-action.php';
                $actions_registrar->register(new CoolFormPro_FDBGP_Form_Sheets_Action);
            }
        }
    }
}
