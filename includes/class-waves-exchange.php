<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Exchange class
 */
class WavesExchange
{
    public static function convert($currency, $amount)
    {

        // making use of cryptonator api

        $url = "https://api.cryptonator.com/api/ticker/". strtolower($currency) ."-waves";

        $response = wp_remote_get( $url );
        $result = json_decode(wp_remote_retrieve_body($response));
        
        if ($result) {
            $rate = $result->ticker->price;
        } else {
            // sometimes we need to try it once again if it doesn't work the first time
            $response = wp_remote_get( $url );
            $result = json_decode(wp_remote_retrieve_body($response));
            if ($result) {
                $rate = $result->ticker->price;
                } else {
            return 'undefined';
            }
        }
		return round($amount * $rate,6);
    
    }
}
