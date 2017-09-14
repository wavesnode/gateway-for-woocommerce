<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Exchange class
 */
class WavesExchange
{
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

    private static function exchange($currency,$price,$currencyTo) {
        $exchange_price = WavesExchange::getExchangePrice($currencyTo,$currency);
        return round($price / $exchange_price, 0, PHP_ROUND_HALF_UP);
    }

    public static function convertToWnet($currency, $price) {
        $price_in_waves = WavesExchange::exchange($currency,$price,'waves');
        return WavesExchange::exchange('waves',$price_in_waves,'wnet');
    }
}
