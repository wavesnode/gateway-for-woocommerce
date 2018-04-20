<?php

/**
 * Waves Gateway for Woocommerce
 *
 * Plugin Name: WNET Gateway for Woocommerce (also for other Waves assets)
 * Plugin URI: https://github.com/wavesnode/gateway-for-woocommerce/
 * Description: Show prices in Waves (or asset) and accept Waves payments in your woocommerce webshop
 * Version: 0.4.2
 * Author: Tubby / Useless Waves Token
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
        public static $version = '0.4.1';
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

	        add_filter('woocommerce_payment_gateways', array($this, 'addToGateways'));
            add_filter('woocommerce_currencies', array($this, 'WavesCurrencies'));
            add_filter('woocommerce_currency_symbol', array($this, 'WavesCurrencySymbols'), 10, 2);

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

        public function WavesCurrencies( $currencies )
        {
            $currencies['WAVES'] = __( 'Waves', 'waves' );
            $currencies['WNET'] = __( 'Wavesnode.NET', 'wnet' );
            $currencies['ARTcoin'] = __( 'ARTcoin', 'ARTcoin' );
            $currencies['POL'] = __( 'POLTOKEN.PL', 'POL' );
            return $currencies;
        }

        public function WavesCurrencySymbols( $currency_symbol, $currency ) {
            switch( $currency ) {
                case 'WAVES': $currency_symbol = 'WAVES'; break;
                case 'WNET': $currency_symbol = 'WNET'; break;
                case 'ARTcoin': $currency_symbol = 'ARTcoin'; break;
                case 'POL': $currency_symbol = 'POL'; break;
            }
            return $currency_symbol;
        }

	    public function WavesFilterCartTotal($value)
	    {
	        return $this->convertToWavesPrice($value, WC()->cart->total);
	    }

	    public function WavesFilterCartItemSubtotal($cart_subtotal, $compound, $that)
	    {
	        return $this->convertToWavesPrice($cart_subtotal, $that->subtotal);
	    }

	    public function WavesFilterPriceHtml($price, $that)
	    {
	        return $this->convertToWavesPrice($price, $that->price);
	    }

	    public function WavesFilterCartItemPrice($price, $cart_item, $cart_item_key)
	    {
	        $item_price = ($cart_item['line_subtotal'] + $cart_item['line_subtotal_tax']) / $cart_item['quantity'];
	        return $this->convertToWavesPrice($price,$item_price);
	    }

	    public function WavesFilterCartSubtotal($price, $cart_item, $cart_item_key)
	    {
	        $subtotal = $cart_item['line_subtotal'] + $cart_item['line_subtotal_tax'];
	        return $this->convertToWavesPrice($price, $subtotal);
	    }

	    private function convertToWavesPrice($price_string, $price)
	    {
            $options = get_option('woocommerce_waves_settings');
            if(!in_array(get_woocommerce_currency(), array("WAVES","WNET","ARTcoin","POL")) && $options['show_prices'] == 'yes') {
                $waves_currency = $options['asset_code'];
                if(empty($waves_currency)) {
                    $waves_currency = 'Waves';
                }
                $waves_assetId = $options['asset_id'];
                if(empty($waves_assetId)) {
                    $waves_assetId = null;
                }
                $waves_price = WavesExchange::convertToAsset(get_woocommerce_currency(), $price,$waves_assetId);
                if ($waves_price) {
                    $price_string .= '&nbsp;(<span class="woocommerce-price-amount amount">' . $waves_price . '&nbsp;</span><span class="woocommerce-price-currencySymbol">'.$waves_currency.')</span>';
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