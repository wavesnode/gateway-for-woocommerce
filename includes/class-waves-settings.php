<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Settings class
 */
if (!class_exists('WavesSettings')) {

    class WavesSettings
    {

        public static function fields()
        {

            return apply_filters('wc_waves_settings',

                array(
                    'enabled'     => array(
                        'title'   => __('Enable/Disable', 'waves-gateway-for-woocommerce'),
                        'type'    => 'checkbox',
                        'label'   => __('Enable Waves (or assets) payments', 'waves-gateway-for-woocommerce'),
                        'description' => __('Select the desired Waves (or asset) as Currency (General -> Currency Options).', 'waves-gateway-for-woocommerce'),
                        'default' => 'yes',
                    ),
                    'title'       => array(
                        'title'       => __('Title', 'waves-gateway-for-woocommerce'),
                        'type'        => 'text',
                        'description' => __('This controls the title which the user sees during checkout.', 'waves-gateway-for-woocommerce'),
                        'default'     => __('Pay with WAVES', 'waves-gateway-for-woocommerce'),
                        'desc_tip'    => true,
                    ),
                    'description' => array(
                        'title'   => __('Customer Message', 'waves-gateway-for-woocommerce'),
                        'type'    => 'textarea',
                        'default' => __('Ultra-fast and secure checkout with WAVES'),
                    ),
                    'address'     => array(
                        'title'       => __('Destination address', 'waves-gateway-for-woocommerce'),
                        'type'        => 'text',
                        'default'     => '',
                        'description' => __('This addresses will be used for receiving funds.', 'waves-gateway-for-woocommerce'),
                    ),
                    'secret'      => array(
                        'type'    => 'hidden',
                        'default' => sha1(get_bloginfo() . Date('U')),

                    ),
                )
            );
        }
    }

}
