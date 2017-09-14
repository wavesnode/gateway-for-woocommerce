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
        $waves_price = WavesExchange::getExchangePrice('waves',$currency);
        return round($amount / $waves_price, 0, PHP_ROUND_HALF_UP);
    }

    private static function getBodyAsJson($url,$retries=1) {
        $response = wp_remote_get( $url );
        $result = json_decode(wp_remote_retrieve_body($response));
        if(!$result && $retries>0) {
            return WavesExchange::getBodyAsJson($url,--$retries);
        }
        return $result?$result:null;
    }

    private static function getExchangePrice($currency1,$currency2) {
        $pair = strtolower($currency1."/".$currency2);
        $result = wp_cache_get($pair,'exchangePrices');
        if (false === $result ) {
            $result = WavesExchange::getBodyAsJson("http://marketdata.wavesplatform.com/api/ticker/".$pair);
            $result = isset($result->{'24h_vwap'})?$result->{'24h_vwap'}:false;
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
