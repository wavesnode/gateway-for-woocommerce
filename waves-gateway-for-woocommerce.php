<?php

/**
 * Waves Gateway for Woocommerce
 *
 * Plugin Name: WNET Gateway for Woocommerce
 * Plugin URI: https://uwtoken.com
 * Description: Show prices in WNET and accept WNET payments in your woocommerce webshop
 * Version: 0.1.3
 * Author: John Doe / Useless Waves Token
 * License: GPLv2 or later
 * License URI: http://www.opensource.org/licenses/gpl-license.php
 * Text Domain: waves-gateway-for-woocommerce
 * Domain Path: /languages/
  *
 * Copyright 2017 Useless Waves Token Foundation
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WcWaves')) {

    class WcWaves
    {

        private static $instance;
        public static $version = '0.2.2';
        public static $plugin_basename;
        public static $plugin_path;
        public static $plugin_url;

        protected function __construct()
        {
        	self::$plugin_basename = plugin_basename(__FILE__);
        	self::$plugin_path = trailingslashit(dirname(__FILE__));
        	self::$plugin_url = plugin_dir_url(self::$plugin_basename);
            add_action('plugins_loaded', array($this, 'init'));
        }
        
        public static function getInstance()
        {
            if (null === self::$instance) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        public function init()
        {
            $this->initGateway();
        }

        public function initGateway()
        {

            if (!class_exists('WC_Payment_Gateway')) {
                return;
            }

            if (class_exists('WC_Waves_Gateway')) {
	            return;
	        }

	        /*
	         * Include gateway classes
	         * */
	        include_once plugin_basename('includes/base58/src/Base58.php');
	        include_once plugin_basename('includes/base58/src/ServiceInterface.php');
	        include_once plugin_basename('includes/base58/src/GMPService.php');
	        include_once plugin_basename('includes/base58/src/BCMathService.php');
	        include_once plugin_basename('includes/class-waves-gateway.php');
	        include_once plugin_basename('includes/class-waves-api.php');
	        include_once plugin_basename('includes/class-waves-exchange.php');
	        include_once plugin_basename('includes/class-waves-settings.php');
	        include_once plugin_basename('includes/class-waves-ajax.php');
	        
            add_filter('woocommerce_currencies', array($this, 'WnetCurrency'));
            add_filter('woocommerce_currency_symbol', array($this, 'WnetCurrencySymbol'), 10, 2);
	        add_filter('woocommerce_payment_gateways', array($this, 'addToGateways'));
	    }

	    public static function addToGateways($gateways)
	    {
	        $gateways['waves'] = 'WcWavesGateway';
	        return $gateways;
	    }


        public function WnetCurrency( $currencies )
        {
            $currencies['WNET'] = __( 'Wavesnode.NET', 'wnet' );
            return $currencies;
        }

        public function WnetCurrencySymbol( $currency_symbol, $currency ) {
            switch( $currency ) {
                case 'WNET': $currency_symbol = 'WNET'; break;
            }
            return $currency_symbol;
        }
    }

}

WcWaves::getInstance();

function wavesGateway_textdomain() {
    load_plugin_textdomain( 'waves-gateway-for-woocommerce', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
}
        
add_action( 'plugins_loaded', 'wavesGateway_textdomain' );