<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Exchange class
 */
class WavesExchange
{
    public static $ASSET_ID = 'AxAmJaro7BJ4KasYiZhw7HkjwgYtt2nekPuF2CN9LMym';//WNET

    public static function convert($currency, $amount)
    {

        // making use of cryptonator api

        $url = "https://api.cryptonator.com/api/ticker/". strtolower($currency) ."-waves";

        $result = WavesExchange::getBodyAsJson($url);
        if(!$result) {
            return 'undefined';
        }
        $rate = $result->ticker->price;
        return round($amount * $rate,6);
    }

    private static function getBodyAsJson($url,$retries=1) {
        $response = wp_remote_get( $url );
        $result = json_decode(wp_remote_retrieve_body($response));
        if(!$result && $retries>0) {
            return WavesExchange::getBodyAsJson($url,--$retries);
        }
        return $result?$result:null;
    }

    public static function getExchangePrice($currency1,$currency2) {
        $pair = $currency1."/".$currency2;
        $result = wp_cache_get($pair,'exchangePrices');
        if (false === $result ) {
            $result = WavesExchange::getBodyAsJson("http://marketdata.wavesplatform.com/api/ticker/".$currency1."/".$currency2);
            $result = $result?$result->{'24h_vwap'}:false;
            wp_cache_set( $pair, $result, 'exchangePrices', 3600);
        }
        return $result;
    }

    public static function convertToWnet($currency, $price) {
        $price_in_waves = WavesExchange::convert($currency, $price);
        $wnet_asset_price = WavesExchange::getExchangePrice('wnet','waves');
        return round($price_in_waves / $wnet_asset_price, 0, PHP_ROUND_HALF_UP);
    }
}
