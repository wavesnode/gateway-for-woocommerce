<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Ajax class
 */
class WavesAjax
{

    private static $instance;

    public static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct()
    {
        $this->init();
    }

    public function init()
    {
        add_action('wp_ajax_check_waves_payment', array(__CLASS__, 'checkWavesPayment'));
    }

    public function checkWavesPayment()
    {
        global $woocommerce;
        $woocommerce->cart->get_cart();

        $options = get_option('woocommerce_waves_settings');

        $payment_total   = WC()->session->get('waves_payment_total');
        $destination_tag = WC()->session->get('waves_destination_tag');

        $ra     = new WavesApi($options['address']);
        $result = $ra->findByDestinationTag($destination_tag);

        $result['match'] = ($result['amount'] == $payment_total ) ? true : false;

        echo json_encode($result);
        exit();
    }

} 

WavesAjax::getInstance();
