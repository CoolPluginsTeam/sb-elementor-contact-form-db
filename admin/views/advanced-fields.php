<?php
// Ensure the file is being accessed through the WordPress admin area
if (!defined('ABSPATH')) {
    die;
}

if (! function_exists('get_plugins')) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
}


$enabled_elements = get_option('cfkef_enabled_elements', array());


$popular_elements = array('range_slider');
$updated_elements = array('country_code');

$first_plugin = 'formsdb';



$form_elements = array(


    'whatsapp_redirect' => array(
        'label' => __('Whatsapp Redirect', 'sb-elementor-contact-form-db'),
        'how_to' => str_replace('utm_source=', 'utm_source=' . $first_plugin, 'https://coolformkit.com/features/whatsapp-redirect-elementor-form/?utm_source=&utm_medium=inside&utm_campaign=demo&utm_content=dashboard'),
        'demo' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://docs.coolplugins.net/doc/whatsapp-redirection-elementor-form/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=docs&utm_content=dashboard'),
        'icon' => FDBGP_PLUGIN_URL . 'assets/icons/whatsapp-icon-min.svg',
        'pro' => true
    ),

    'range_slider' => array(
        'label' => __('Range Slider', 'sb-elementor-contact-form-db'),
        'how_to' => str_replace('utm_source=', 'utm_source=' . $first_plugin, 'https://coolformkit.com/features/range-slider-for-elementor-form/?utm_source=&utm_medium=inside&utm_campaign=demo&utm_content=dashboard'),
        'demo' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://docs.coolplugins.net/doc/range-slider-elementor-form/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=docs&utm_content=dashboard'),
        'icon' => FDBGP_PLUGIN_URL . 'assets/icons/range-slider-min.svg',
        'pro' => true
    ),
    'calculator_field' => array(
        'label' => __('Calculator Field', 'sb-elementor-contact-form-db'),
        'how_to' => str_replace('utm_source=', 'utm_source=' . $first_plugin, 'https://coolformkit.com/features/calculator-for-elementor/?utm_source=&utm_medium=inside&utm_campaign=demo&utm_content=dashboard'),
        'demo' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://docs.coolplugins.net/doc/calculator-field-elementor-form/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=docs&utm_content=dashboard'),
        'icon' => FDBGP_PLUGIN_URL . 'assets/icons/calculator-field-min.svg',
        'pro' => true
    ),
    'rating_field' => array(
        'label' => __('Rating Field', 'sb-elementor-contact-form-db'),
        'how_to' => str_replace('utm_source=', 'utm_source=' . $first_plugin, 'https://coolformkit.com/features/rating-field-for-elementor-form/?utm_source=&utm_medium=inside&utm_campaign=demo&utm_content=dashboard'),
        'demo' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://docs.coolplugins.net/doc/rating-field-elementor-form/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=docs&utm_content=dashboard'),
        'icon' => FDBGP_PLUGIN_URL . 'assets/icons/rating-field-min.svg',
        'pro' => true
    ),
    'signature_field' => array(
        'label' => __('Signature Field', 'cool-formkit'),
        'how_to' => str_replace('utm_source=', 'utm_source=' . $first_plugin, 'https://coolformkit.com/features/signature-field-for-elementor-form/?utm_source=&utm_medium=inside&utm_campaign=demo&utm_content=dashboard'),
        'demo' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://docs.coolplugins.net/doc/signature-field-elementor-form/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=docs&utm_content=dashboard'),
        'icon' => FDBGP_PLUGIN_URL . 'assets/icons/signature.svg',
        'pro' => true
    ),
    'image_radio' => array(
        'label' => __('Image Radio', 'cool-formkit'),
        'how_to' => str_replace('utm_source=', 'utm_source=' . $first_plugin, 'https://coolformkit.com/features/image-radio-for-elementor-form/?utm_source=&utm_medium=inside&utm_campaign=demo&utm_content=dashboard'),
        'demo' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://docs.coolplugins.net/doc/add-image-radio-field/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=docs&utm_content=dashboard'),
        'icon' => FDBGP_PLUGIN_URL . 'assets/icons/image-radio-min.svg',
        'pro' => true
    ),
    'radio_checkbox_styler' => array(
        'label' => __('Radio & Checkbox Styler', 'cool-formkit'),
        'how_to' => str_replace('utm_source=', 'utm_source=' . $first_plugin, 'https://coolformkit.com/features/checkbox-radio-styles-for-elementor-form/?utm_source=&utm_medium=inside&utm_campaign=demo&utm_content=dashboard'),
        'demo' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://docs.coolplugins.net/doc/style-radio-checkbox-elementor-form/?utm_source=cfkef_plugin&&utm_medium=inside&utm_campaign=docs&utm_content=dashboard'),
        'icon' => FDBGP_PLUGIN_URL . 'assets/icons/radio-styler-min.svg',
        'pro' => true
    ),
    'label_styler' => array(
        'label' => __('Label Styler', 'cool-formkit'),
        'how_to' => str_replace('utm_source=', 'utm_source=' . $first_plugin, 'https://coolformkit.com/features/label-styler-for-elementor-form/?utm_source=&utm_medium=inside&utm_campaign=demo&utm_content=dashboard'),
        'demo' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://docs.coolplugins.net/doc/label-styler-elementor-form/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=docs&utm_content=dashboard'),
        'icon' => FDBGP_PLUGIN_URL . 'assets/icons/label-style-min.svg',
        'pro' => true
    ),
    'select2' => array(
        'label' => __('Select2', 'cool-formkit'),
        'how_to' => str_replace('utm_source=', 'utm_source=' . $first_plugin, 'https://coolformkit.com/features/select2-field-for-elementor-form/?utm_source=&utm_medium=inside&utm_campaign=demo&utm_content=dashboard'),
        'demo' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://docs.coolplugins.net/doc/select-field-elementor-form/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=docs&utm_content=dashboard'),
        'icon' => FDBGP_PLUGIN_URL . 'assets/icons/select2-field-min.svg',
        'pro' => true
    ),
    'WYSIWYG' => array(
        'label' => __('WYSIWYG', 'cool-formkit'),
        'how_to' => str_replace('utm_source=', 'utm_source=' . $first_plugin, 'https://coolformkit.com/features/wysiwyg-field-for-elementor-form/?utm_source=&utm_medium=inside&utm_campaign=demo&utm_content=dashboard'),
        'demo' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://docs.coolplugins.net/doc/add-wysiwyg-elementor-form/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=docs&utm_content=dashboard'),
        'icon' => FDBGP_PLUGIN_URL . 'assets/icons/WYSIWYG-min.svg',
        'pro' => true
    ),
    'confirm_dialog' => array(
        'label' => __('Confirm Dialog Box', 'cool-formkit'),
        'how_to' => str_replace('utm_source=', 'utm_source=' . $first_plugin, 'https://coolformkit.com/features/confirm-dialog-box-for-elementor-form/?utm_source=&utm_medium=inside&utm_campaign=demo&utm_content=dashboard'),
        'demo' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://docs.coolplugins.net/doc/elementor-form-confirm-dialog-popup/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=docs&utm_content=dashboard'),
        'icon' => FDBGP_PLUGIN_URL . 'assets/icons/dialog-box-min.svg',
        'pro' => true
    ),
    'restrict_date' => array(
        'label' => __('Restrict Date', 'cool-formkit'),
        'how_to' => str_replace('utm_source=', 'utm_source=' . $first_plugin, 'https://coolformkit.com/features/restrict-date-field-for-elementor-form/?utm_source=&utm_medium=inside&utm_campaign=demo&utm_content=dashboard'),
        'demo' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://docs.coolplugins.net/doc/restrict-date-elementor-form/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=docs&utm_content=dashboard'),
        'icon' => FDBGP_PLUGIN_URL . 'assets/icons/restrict-date-min.svg',
        'pro' => true
    ),
    'currency_field' => array(
        'label' => __('Currency Field', 'cool-formkit'),
        'how_to' => str_replace('utm_source=', 'utm_source=' . $first_plugin, 'https://coolformkit.com/features/currency-field-for-elementor-form/?utm_source=&utm_medium=inside&utm_campaign=demo&utm_content=dashboard'),
        'demo' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://docs.coolplugins.net/doc/add-currency-elementor-form/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=docs&utm_content=dashboard'),
        'icon' => FDBGP_PLUGIN_URL . 'assets/icons/currency-field-min.svg',
        'pro' => true
    ),
    'month_week_field' => array(
        'label' => __('Month/Week Field', 'cool-formkit'),
        'how_to' => str_replace('utm_source=', 'utm_source=' . $first_plugin, 'https://coolformkit.com/features/month-week-field-for-elementor-form/?utm_source=&utm_medium=inside&utm_campaign=demo&utm_content=dashboard'),
        'demo' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://docs.coolplugins.net/doc/add-month-week/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=docs&utm_content=dashboard'),
        'icon' => FDBGP_PLUGIN_URL . 'assets/icons/month-week-field-min.svg',
        'pro' => true
    ),
    'cloudflare_recaptcha' => array(
        'label' => __('Cloudflare Turnstile', 'cool-formkit'),
        'how_to' => str_replace('utm_source=', 'utm_source=' . $first_plugin, 'https://coolformkit.com/features/cloudflare-turnstile-for-elementor-form/?utm_source=&utm_medium=inside&utm_campaign=demo&utm_content=dashboard'),
        'demo' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://docs.coolplugins.net/doc/add-cloudflare-turnstile-elementor-form/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=docs&utm_content=dashboard'),
        'icon' => FDBGP_PLUGIN_URL . 'assets/icons/cloudflare-icon-min.svg',
        'pro' => true
    ),

    'h_recaptcha' => array(
        'label' => __('hCAPTCHA', 'cool-formkit'),
        'how_to' => str_replace('utm_source=', 'utm_source=' . $first_plugin, 'https://coolformkit.com/features/hcaptcha-for-elementor-form/?utm_source=&utm_medium=inside&utm_campaign=demo&utm_content=dashboard'),
        'demo' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://docs.coolplugins.net/doc/add-hcaptcha-elementor-form/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=docs&utm_content=dashboard'),
        'icon' => FDBGP_PLUGIN_URL . 'assets/icons/hcaptcha-icon-min.svg',
        'pro' => true
    ),
    'toggle_field' => array(

        'label' => __('Toggle Field', 'cool-formkit'),
        'how_to' => str_replace('utm_source=', 'utm_source=' . $first_plugin, 'https://coolformkit.com/features/toggle-field-for-elementor-form/?utm_source=&utm_medium=inside&utm_campaign=demo&utm_content=dashboard'),
        'demo' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://docs.coolplugins.net/doc/toggle-field-elementor-form/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=docs&utm_content=dashboard'),
        'icon' => FDBGP_PLUGIN_URL . 'assets/icons/toggle-field.svg',
        'pro' => true,
    ),


    'conditional_mailchimp' => array(
        'label' => __('Conditional MailChimp', 'cool-formkit'),
        'demo' => str_replace('utm_source=', 'utm_source=' . esc_attr($first_plugin),'https://docs.coolplugins.net/doc/conditional-mailchimp-elementor-form/?utm_source=&utm_medium=inside&utm_campaign=docs&utm_content=dashboard'),
        'how_to' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . esc_attr($first_plugin),'https://coolformkit.com/features/conditional-mailchimp-for-elementor-form/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=demo&utm_content=dashboard'),
        'icon' => FDBGP_PLUGIN_URL . 'assets/icons/mailchimp-logo.svg',
        'pro' => true,
        'pro_link' => 'https://coolplugins.net/cool-formkit-for-elementor-forms/?utm_source=formsdb&utm_medium=inside&utm_campaign=get-pro&utm_content=plugins-dashboard/'
    ),
);


$condition_plugin_features = array(
    'conditional_logic' => array(
        'label' => __('Conditional Logic', 'cool-formkit'),
        'how_to' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://docs.coolplugins.net/doc/elementor-form-conditional-fields/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=docs&utm_content=dashboard'),
        'demo' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://coolplugins.net/product/conditional-fields-for-elementor-form/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=docs&utm_content=dashboard'),
        'icon' => FDBGP_PLUGIN_URL . 'assets/icons/conditional-logic-1-min.svg'
    ),

    'submit_condition' => array(
        'label' => __('Submit Conditions', 'cool-formkit'),
        'how_to' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://coolplugins.net/product/conditional-fields-for-elementor-form/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=demo&utm_content=dashboard'),
        'demo' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://docs.coolplugins.net/doc/elementor-form-submit-button-conditions/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=docs&utm_content=dashboard'),
        'icon' => FDBGP_PLUGIN_URL . 'assets/icons/conditional-button-min.svg',
        'pro' => true
    ),


    'redirect_conditionaly' => array(
        'label' => __('Redirect Conditionaly', 'cool-formkit'),
        'how_to' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://coolplugins.net/product/conditional-fields-for-elementor-form/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=demo&utm_content=dashboard'),
        'demo' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://docs.coolplugins.net/doc/conditional-redirect-elementor-form-on-submit/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=docs&utm_content=dashboard'),
        'icon' => FDBGP_PLUGIN_URL . 'assets/icons/redirect-conditionally-min.svg',
        'pro' => true
    ),


    'email_conditionaly' => array(
        'label' => __('Email Conditionaly', 'cool-formkit'),
        'how_to' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://coolplugins.net/product/conditional-fields-for-elementor-form/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=demo&utm_content=dashboard'),
        'demo' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://docs.coolplugins.net/doc/conditional-email-elementor-form/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=docs&utm_content=dashboard'),
        'icon' => FDBGP_PLUGIN_URL . 'assets/icons/conditional-email-1-min.svg',
        'pro' => true

    ),


    'multicondtion_or_logic' => array(
        'label' => __('Multiple OR Conditions', 'cool-formkit'),
        'how_to' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://coolplugins.net/product/conditional-fields-for-elementor-form/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=demo&utm_content=dashboard'),
        'demo' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://docs.coolplugins.net/doc/and-or-conditional-logic-elementor-form/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=docs&utm_content=dashboard'),
        'icon' => FDBGP_PLUGIN_URL . 'assets/icons/or-condition.svg',
        'pro' => true

    ),


    'more_operators' => array(
        'label' => __('More Operators', 'cool-formkit'),
        'how_to' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://coolplugins.net/product/conditional-fields-for-elementor-form/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=demo&utm_content=dashboard'),
        'demo' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://docs.coolplugins.net/doc/elementor-form-conditional-logic-operators/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=docs&utm_content=dashboard'),
        'icon' => FDBGP_PLUGIN_URL . 'assets/icons/more-opreators.svg',
        'pro' => true

    ),

    




);


$country_field_features = array(
    'country_code' => array(
        'label' => __('Country code', 'cool-formkit'),
        'how_to' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://docs.coolplugins.net/doc/country-code-elementor-form/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=docs&utm_content=dashboard'),
        'demo' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://docs.coolplugins.net/doc/country-code-elementor-form/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=docs&utm_content=dashboard'),
        'icon' => FDBGP_PLUGIN_URL . 'assets/icons/country-code-min.svg'
    ),


    'country_state' => array(
        'label' => __('State Field', 'cool-formkit'),
        'how_to' => str_replace('utm_source=', 'utm_source=' . $first_plugin, 'https://coolformkit.com/features/country-and-state-field-for-elementor-form/?utm_source=&utm_medium=inside&utm_campaign=demo&utm_content=dashboard'),
        'demo' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://docs.coolplugins.net/plugin/cool-formkit-for-elementor-form/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=docs&utm_content=dashboard'),
        'icon' => FDBGP_PLUGIN_URL . 'assets/icons/state-field.svg',
        'pro' => true
    ),

    'auto_select_country' => array(
        'label' => __('Auto Detect Country', 'cool-formkit'),
        'how_to' => str_replace('utm_source=', 'utm_source=' . $first_plugin, 'https://coolformkit.com/features/?utm_source=&utm_medium=inside&utm_campaign=demo&utm_content=dashboard'),
        'demo' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://docs.coolplugins.net/plugin/cool-formkit-for-elementor-form/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=docs&utm_content=dashboard'),
        'icon' => FDBGP_PLUGIN_URL . 'assets/icons/auto-detect.svg',
        'pro' => true

    ),

);



$form_mask_features = array(
    'form_input_mask' => array(
        'label' => __('Field Masking', 'cool-formkit'),
        'how_to' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://docs.coolplugins.net/doc/input-masks-elementor-form/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=docs&utm_content=dashboard'),
        'demo' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://docs.coolplugins.net/doc/input-masks-elementor-form/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=docs&utm_content=dashboard'),
        'icon' => FDBGP_PLUGIN_URL . 'assets/icons/input-mask-min.svg'
    ),
    'hello_plus_support' => array(
        'label' => __('Hello Plus Support', 'cool-formkit'),
        'how_to' => str_replace('utm_source=', 'utm_source=' . $first_plugin, 'https://coolformkit.com/features/?utm_source=&utm_medium=inside&utm_campaign=demo&utm_content=dashboard'),
        'demo' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://docs.coolplugins.net/plugin/cool-formkit-for-elementor-form/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=docs&utm_content=dashboard'),
        'icon' => FDBGP_PLUGIN_URL . 'assets/icons/hello-plus-support.svg',
        'pro' => true
    ),

    'advanced_fields' => array(
        'label' => __('Advanced Fields', 'cool-formkit'),
        'how_to' => str_replace('utm_source=', 'utm_source=' . $first_plugin, 'https://coolformkit.com/features/?utm_source=&utm_medium=inside&utm_campaign=demo&utm_content=dashboard'),
        'demo' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://docs.coolplugins.net/plugin/cool-formkit-for-elementor-form/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=docs&utm_content=dashboard'),
        'icon' => FDBGP_PLUGIN_URL . 'assets/icons/advanced-field.svg',
        'pro' => true
    ),

);



$input_form_mask_features = array(
    'form_input_mask' => array(
        'label' => __('Input Mask', 'cool-formkit'),
        'how_to' => str_replace('utm_source=', 'utm_source=' . $first_plugin, 'https://docs.coolplugins.net/doc/input-masks-elementor-form/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=demo&utm_content=plugins-dashboard'),
        'demo' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://docs.coolplugins.net/doc/input-masks-elementor-form/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=docs&utm_content=dashboard'),
        'icon' => FDBGP_PLUGIN_URL . 'assets/icons/input-mask-min.svg'
    ),
    'hello_plus_support' => array(
        'label' => __('Hello Plus Support', 'cool-formkit'),
        'how_to' => str_replace('utm_source=', 'utm_source=' . $first_plugin, 'https://coolformkit.com/features/?utm_source=&utm_medium=inside&utm_campaign=demo&utm_content=dashboard'),
        'demo' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://docs.coolplugins.net/plugin/conditional-fields-for-elementor-form/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=docs&utm_content=dashboard'),
        'icon' => FDBGP_PLUGIN_URL . 'assets/icons/hello-plus-support.svg',
        'pro' => true
    ),

    'advanced_fields' => array(
        'label' => __('Advanced Fields', 'cool-formkit'),
        'how_to' => str_replace('utm_source=', 'utm_source=' . $first_plugin, 'https://coolformkit.com/features/?utm_source=&utm_medium=inside&utm_campaign=demo&utm_content=dashboard'),
        'demo' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://coolplugins.net/product/conditional-fields-for-elementor-form/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=docs&utm_content=dashboard'),
        'icon' => FDBGP_PLUGIN_URL . 'assets/icons/advanced-field.svg',
        'pro' => true
    ),

);




?>


<div id="cfkef-loader" style="display: none;">
    <div class="cfkef-loader-overlay"></div>
    <div class="cfkef-loader-spinner"></div>
</div>

<form method="post" action="options.php">

    <?php settings_fields('cfkef_form_elements_group'); ?>
    <?php do_settings_sections('cfkef_form_elements_group'); ?>

    <div class="cfk-wrapper">

        <div class="cfk-content">

            <div class="cfk-promo">
                <div class="cfk-box cfk-left">

                    <div class="wrapper-header">
                        <div class="cfkef-save-all">
                            <div class="cfkef-title-desc">
                                <h2><?php esc_html_e('What is Cool FormKit?', 'cool-formkit'); ?></h2>
                            </div>

                            <div class="cfkef-save-controls">

                                <a target="_blank" href="https://coolformkit.com/pricing/?utm_source=<?php echo esc_attr($first_plugin) ?>&utm_medium=inside&utm_campaign=get_pro&utm_content=dashboard" class="button">Get Pro</a>
                            </div>

                        </div>
                    </div>

                    <div class="wrapper-body">


                        <p>All-in-one plugin, An addon for Elementor Pro forms that provides many extra features and advanced fields to extend your form-building experience using Elementor form widget.</p>


                        <div class="cfkef-form-element-box">
                            <?php foreach ($form_elements as $key => $element): ?>
                                <div class="cfkef-form-element-card">
                                    <div class="cfkef-form-element-info">
                                        <img src="<?php echo esc_url($element['icon']) ?>" alt="Color Field">
                                        <h4>
                                            <?php echo esc_html($element['label']); ?>
                                            <?php if (!empty($element['pro'])): ?>
                                                <span class="cfkef-label-popular"><a href="<?php echo esc_url($element['how_to']) ?>" target="_blank"><?php esc_html_e('Pro', 'cool-formkit'); ?></a></span>
                                            <?php endif; ?>

                                            
                                        </h4>
                                        <div>
                                            <a href="<?php echo esc_url($element['demo']) ?>" title="Documentation" target="_blank" rel="noreferrer">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                                                    <path fill="#000" d="M21 11V3h-8v2h4v2h-2v2h-2v2h-2v2H9v2h2v-2h2v-2h2V9h2V7h2v4zM11 5H3v16h16v-8h-2v6H5V7h6z" />
                                                </svg>
                                            </a>
                                        </div>
                                    </div>
                                    <label class="cfkef-toggle-switch" style="opacity: 0.5; ">
                                        <input type="checkbox" name="cfkef_enabled_elements[]" value="<?php echo esc_attr($key); ?>" <?php checked(in_array($key, $enabled_elements)); ?> class="cfkef-element-toggle"
                                            <?php disabled(!empty($element['pro'])); ?>>
                                        <?php if (!empty($element['pro'])): ?>
                                            <a href="<?php echo esc_url($element['how_to']) ?>" target="_blank">
                                                <span class="cfkef-slider round"></span>
                                            </a>
                                        <?php else: ?>
                                            <span class="cfkef-slider round"></span>
                                        <?php endif; ?>

                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>


                    </div>
                </div>

                <div class="cfk-right">
                    <a href="https://wordpress.org/support/plugin/sb-elementor-contact-form-db/reviews/#new-post" target="_blank" class="cfk-box review">
                        Are you enjoying using our addon to upgrade features inside your Elementor form? Please submit your review as it boosts our energy to work on future updates.
                        <span>Submit Review ★★★★★</span>
                    </a>
                    <div class="cfk-box">
                        <h3>Important Links</h3>
                        <div class="cfk-buttons">
                            <a href="https://coolplugins.net/support/?utm_source=<?php echo esc_attr($first_plugin); ?>&utm_medium=inside&utm_campaign=support&utm_content=setting_page_sidebar" class="button button-secondary" target="_blank">Contact Support</a>
                            <a href="https://coolplugins.net/about-us/?utm_source=<?php echo esc_attr($first_plugin); ?>&utm_medium=inside&utm_campaign=about_us&utm_content=setting_page_sidebar" class="button"  target="_blank">Meet Cool Plugins Developers</a>
                            <a href="https://x.com/cool_plugins" class="button" target="_blank">Follow On X</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div>

</form>