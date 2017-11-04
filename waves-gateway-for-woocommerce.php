<?php

/**
 * WNET Gateway for Woocommerce
 *
 * Plugin Name: WNET Gateway for Woocommerce (also for other Waves assets)
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
	        
            add_filter('woocommerce_currencies', array($this, 'AddWavesAssetCurrency'));
            add_filter('woocommerce_currency_symbol', array($this, 'AddWavesAssetCurrencySymbol'), 10, 2);
	        add_filter('woocommerce_payment_gateways', array($this, 'addToGateways'));

	        add_filter('woocommerce_get_price_html', array($this, 'WavesFilterPriceHtml'), 10, 2);
	        add_filter('woocommerce_cart_item_price', array($this, 'WavesFilterCartItemPrice'), 10, 3);
	        add_filter('woocommerce_cart_item_subtotal', array($this, 'WavesFilterCartItemSubtotal'), 10, 3);
	        add_filter('woocommerce_cart_subtotal', array($this, 'WavesFilterCartSubtotal'), 10, 3);
	        add_filter('woocommerce_cart_totals_order_total_html', array($this, 'WavesFilterCartTotal'), 10, 1);

	    }

	    public static function addToGateways($gateways)
	    {
	        $gateways['waves'] = 'WcWavesGateway';
	        return $gateways;
	    }

	    public function WavesFilterCartTotal($value)
	    {
	        $total = WC()->cart->total;
	        $value = $this->convertToWave($value, $total);
	        return $value;
	    }
	    public function WavesFilterCartItemSubtotal($cart_subtotal, $compound, $that)
	    {
	        $cart_subtotal = $this->convertToWave($cart_subtotal, $that->subtotal);
	        return $cart_subtotal;
	    }

	    public function WavesFilterPriceHtml($price, $that)
	    {
	        $price = $this->convertToWave($price, $that->price);
	        return $price;
	    }

	    public function WavesFilterCartItemPrice($price, $cart_item, $cart_item_key)
	    {
	        $price = $this->convertToWave($price, ($cart_item['line_subtotal'] + $cart_item['line_subtotal_tax']) / $cart_item['quantity']);
	        return $price;
	    }

	    public function WavesFilterCartSubtotal($price, $cart_item, $cart_item_key)
	    {
	        $price = $this->convertToWave($price, $cart_item['line_subtotal'] + $cart_item['line_subtotal_tax']);
	        return $price;
	    }

        public function AddWavesAssetCurrency( $currencies )
        {
            $options = get_option('woocommerce_waves_settings');
            $asset_code = $options['asset_code'];
            $currencies[$asset_code] = __( $options['asset_desciption'], $asset_code);
            return $currencies;
        }

        public function AddWavesAssetCurrencySymbol( $currency_symbol, $currency ) {
            $options = get_option('woocommerce_waves_settings');
            $asset_code = $options['asset_code'];
            switch( $currency ) {
                case $asset_code: $currency_symbol = $asset_code; break;
            }
            return $currency_symbol;
        }

	    public function convertToWave($price_string, $price)
	    {
	        $currency = get_woocommerce_currency();
            if($currency!='WNET') {
                $options = get_option('woocommerce_waves_settings');

                if ($options['show_prices'] == 'yes') {
                    $wnet_price = WavesExchange::convertToWnet($currency, $price);
                    if ($wnet_price) {
                        return $price_string . '&nbsp;(<span class="woocommerce-price-amount amount">' . $wnet_price . '&nbsp;</span><span class="woocommerce-price-currencySymbol">WNET)</span>';
                    }
                }
            }
	        return $price_string;
	    }
    }

}

WcWaves::getInstance();

function wavesGateway_textdomain() {
    load_plugin_textdomain( 'waves-gateway-for-woocommerce', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
}
        
add_action( 'plugins_loaded', 'wavesGateway_textdomain' );