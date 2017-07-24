<?php

if (!defined('ABSPATH')) {
    exit;
}


/**
 * API class
 */
class WavesApi
{

    private $address;
    private $url;
    private $port;

    public function __construct($address)
    {
        $this->address = $address;
        $this->url = 'https://nodes.wavesnodes.com/';
    }

    private function get( $endpoint )
    {
        $url = $this->url . $endpoint;
        $response = wp_remote_get( $url );
        $result = wp_remote_retrieve_body( $response );
        return json_decode($result);
    }

    public function validAccount($address)
    {
        $result = $this->get('transactions/address/' . $address. '/limit/50');
        return $result->result === 'success' ? true : false;
    }

    function findByDestinationTag($dt)
    {
        $attachment = $dt;
       
        $result = $this->get('transactions/address/' . $this->address. '/limit/50');
        if ($result) {
            $result_encoded = json_encode($result);
            $resultarray = json_decode($result_encoded, true);
            foreach($resultarray as $payments) {
                foreach($payments as $payment) {
                    if ($payment['attachment'] == $attachment) {
                         return array(
                            'result'  => true,
                            'tx_hash' => $payment['id'],
                            'amount' => $payment['amount'],
                            );
                        }
                    }
                }
                return array(
                    'result' => false,
                );
        } else {
            return array(
                'result' => false,
            );
        }
    }

    public function getTransaction($tx_hash)
    {
        return $this->get('transactions/info/' . $tx_hash);
    }

}
