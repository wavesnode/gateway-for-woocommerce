<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Gateway class
 */
class WcWavesGateway extends WC_Payment_Gateway
{
    public $id;
    public $title;
    public $form_fields;
    public $addresses;
    private $assetId;
    private $assetCode;
    private $currencyIsWaves = false;

    public function __construct()
    {

        $this->id          			= 'waves';
        $this->title       			= $this->get_option('title');
        $this->description 			= $this->get_option('description');
        $this->address   			= $this->get_option('address');
        $this->secret   			= $this->get_option('secret');
        $this->order_button_text 	= __('Awaiting transfer..','waves-gateway-for-woocommerce');
        $this->has_fields 			= true;

        // assetCode+id if woocommerce_currency is set to Waves-like currency
        $this->currencyIsWaves = in_array(get_woocommerce_currency(), array("WAVES","WNET","ARTcoin","POL","Wykop Coin","Surfcash","TN","Ecop"));
        if($this->currencyIsWaves) {
            if (get_woocommerce_currency() == "Waves") {
                $this->assetCode = 'Waves';
                $this->assetId = null;
            } else if (get_woocommerce_currency() == "WNET") {
                $this->assetCode = 'WNET';
                $this->assetId = 'AxAmJaro7BJ4KasYiZhw7HkjwgYtt2nekPuF2CN9LMym';
            } else if (get_woocommerce_currency() == "ARTcoin") {
                $this->assetCode = 'ARTcoin';
                $this->assetId = 'GQe2a2uReaEiHLdjzC8q4Popr9tnKonEpcaihEoZrNiR';
            } else if (get_woocommerce_currency() == "POL") {
                $this->assetCode = 'POL';
                $this->assetId = 'Fx2rhWK36H1nfXsiD4orNpBm2QG1JrMhx3eUcPVcoZm2';
            } else if (get_woocommerce_currency() == "Wykop Coin") {
                $this->assetCode = 'Wykop Coin';
                $this->assetId = 'AHcY2BMoxDZ57mLCWWQYBcWvKzf7rdFMgozJn6n4xgLt';
			} else if (get_woocommerce_currency() == "Surfcash") {
                $this->assetCode = 'Surfcash';
                $this->assetId = 'GcQ7JVnwDizXW8KkKLKd8VDnygGgN7ZnpwnP3bA3VLsE';
			} else if (get_woocommerce_currency() == "TN") {
                $this->assetCode = 'TN';
                $this->assetId = 'HxQSdHu1X4ZVXmJs232M6KfZi78FseeWaEXJczY6UxJ3';
			} else if (get_woocommerce_currency() == "Ecop") {
                $this->assetCode = 'Ecop';
                $this->assetId = 'DcLDr4g2Ys4D2RWpkhnUMjMR1gVNPxHEwNkmZzmakQ9R';
				}
        } else {
            $this->assetId              = $this->get_option('asset_id');
            $this->assetCode            = $this->get_option('asset_code');
        }
        if(empty($this->assetId)) {
            $this->assetId = null;
        }
        if(empty($this->assetCode)) {
            $this->assetCode = 'Waves';
        }

        $this->initFormFields();

        $this->initSettings();

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(
            $this,
            'process_admin_options',
        ));
        add_action('wp_enqueue_scripts', array($this, 'paymentScripts'));

        add_action('woocommerce_thankyou_' . $this->id, array($this, 'thankyouPage'));        

    }

    public function initFormFields()
    {
        parent::init_form_fields();
        $this->form_fields = WavesSettings::fields();
    }

    public function initSettings()
    {
    	// sha1( get_bloginfo() )
        parent::init_settings();
    }
   
    public function payment_fields()
    {
    	global $woocommerce;
    	$woocommerce->cart->get_cart();
        $total_converted = $this->get_order_total();
        $rate = null;
        if(!$this->currencyIsWaves) {
            $total_converted = WavesExchange::convertToAsset(get_woocommerce_currency(), $total_converted,$this->assetId);
            $rate = $total_converted / $this->get_order_total();
        }
		
		// Set decimals for tokens other than default value 8
		if (get_woocommerce_currency() == "Ecop") {
		$total_waves = $total_converted * 100000;
		} 
		else if (get_woocommerce_currency() == "Surfcash") {
		$total_waves = $total_converted * 100;
		} 
		else if (get_woocommerce_currency() == "TN") {
		$total_waves = $total_converted * 100;
		}
		else {
			$total_waves = $total_converted * 100000000;
		}


        $destination_tag = hexdec( substr(sha1(current_time(timestamp,1) . key ($woocommerce->cart->cart_contents )  ), 0, 7) );
        $base58 = new StephenHill\Base58();
        $destination_tag_encoded = $base58->encode(strval($destination_tag));
        // set session data 
        WC()->session->set('waves_payment_total', $total_waves);
        WC()->session->set('waves_destination_tag', $destination_tag_encoded);
        WC()->session->set('waves_data_hash', sha1( $this->secret . $total_converted ));
        //QR uri
        $url = "waves://". $this->address ."?amount=". $total_waves."&attachment=".$destination_tag;
        if($this->assetId) {
            $url .= "&asset=".$this->assetId;
        }?>
        <div id="waves-form">
            <div class="waves-container">
            <div>
                <?if ($this->description) { ?>
                <div class="separator"></div>
                <div id="waves-description">
                    <?=apply_filters( 'wc_waves_description', wpautop(  $this->description ) )?>
                </div>
                <?}?>
                <div class="separator"></div>
                <div class="waves-container">
                <?if($rate!=null){?>
                <label class="waves-label">
                    (1<?=get_woocommerce_currency()?> = <?=round($rate,6)?> <?=$this->assetCode?>)
                </label>
                <?}?>
                <p class="waves-amount">
                    <span class="copy" data-success-label="<?=__('copied','waves-gateway-for-woocommerce')?>"
                          data-clipboard-text="<?=esc_attr($total_converted)?>"><?=esc_attr($total_converted)?>
                    </span> <strong><?=$this->assetCode?></strong>
                </p>
                </div>
            </div>
            <div class="separator"></div>
            <div class="waves-container">
                <label class="waves-label"><?=__('destination address', 'waves-gateway-for-woocommerce')?></label>
                <p class="waves-address">
                    <span class="copy" data-success-label="<?=__('copied','waves-gateway-for-woocommerce')?>"
                          data-clipboard-text="<?=esc_attr($this->address)?>"><?=esc_attr($this->address)?>
                    </span>
                </p>
            </div>
            <div class="separator"></div>
            <div class="waves-container">
                <label class="waves-label"><?=__('attachment', 'waves-gateway-for-woocommerce')?></label>
                <p class="waves-address">
                    <span class="copy" data-success-label="<?=__('copied','waves-gateway-for-woocommerce')?>"
                          data-clipboard-text="<?=esc_attr($destination_tag)?>"><?=esc_attr($destination_tag)?>
                    </span>
                </p>
            </div>
            <div class="separator"></div>
            </div>
            <div id="waves-qr-code" data-contents="<?=$url?>"></div>
            <div class="separator"></div>
            <div class="waves-container">
                <p>
                    <?=sprintf(__('Send a payment of exactly %s to the address above (click the links to copy or scan the QR code). We will check in the background and notify you when the payment has been validated.', 'waves-gateway-for-woocommerce'), '<strong>'. esc_attr($total_converted).' '.$this->assetCode.'</strong>' )?>
                </p>
                <strong>DO NOT FORGET THE ATTACHMENT IF YOU USE MANUAL PAYMENT! </strong>
                <p>
                    <?=sprintf(__('Please send your payment within %s.', 'waves-gateway-for-woocommerce'), '<strong><span class="waves-countdown" data-minutes="10">10:00</span></strong>' )?>
                </p>
                <p class="small">
                    <?=__('When the timer reaches 0 this form will refresh and update the attachment as well as the total amount using the latest conversion rate.', 'waves-gateway-for-woocommerce')?>
                </p>
            </div>
            <input type="hidden" name="tx_hash" id="tx_hash" value="0"/>
        </div>
        <?
    }

    public function process_payment( $order_id ) 
    {
    	global $woocommerce;
        $this->order = new WC_Order( $order_id );
        
	    $payment_total   = WC()->session->get('waves_payment_total');
        $destination_tag = WC()->session->get('waves_destination_tag');

	    $ra = new WavesApi($this->address);
	    $transaction = $ra->getTransaction( $_POST['tx_hash']);
	    
        if($transaction->attachment != $destination_tag) {
	    	exit('destination');
	    	return array(
		        'result'    => 'failure',
		        'messages' 	=> 'attachment mismatch'
		    );
	    }
		
		if($transaction->assetId != $this->assetId ) {
			return array(
		        'result'    => 'failure',
		        'messages' 	=> 'Wrong Asset'
		    );
		}
		
	    if($transaction->amount != $payment_total) {
	    	return array(
		        'result'    => 'failure',
		        'messages' 	=> 'amount mismatch'
		    );
	    }
	    
        $this->order->payment_complete();

        $woocommerce->cart->empty_cart();
	   
        return array(
            'result' => 'success',
            'redirect' => $this->get_return_url($this->order)
        );
	}

    public function paymentScripts()
    {
        wp_enqueue_script('qrcode', plugins_url('assets/js/jquery.qrcode.min.js', WcWaves::$plugin_basename), array('jquery'), WcWaves::$version, true);
        wp_enqueue_script('initialize', plugins_url('assets/js/jquery.initialize.js', WcWaves::$plugin_basename), array('jquery'), WcWaves::$version, true);
        
        wp_enqueue_script('clipboard', plugins_url('assets/js/clipboard.js', WcWaves::$plugin_basename), array('jquery'), WcWaves::$version, true);
        wp_enqueue_script('woocommerce_waves_js', plugins_url('assets/js/waves.js', WcWaves::$plugin_basename), array(
            'jquery',
        ), WcWaves::$version, true);
        wp_enqueue_style('woocommerce_waves_css', plugins_url('assets/css/waves.css', WcWaves::$plugin_basename), array(), WcWaves::$version);

        // //Add js variables
        $waves_vars = array(
            'wc_ajax_url' => WC()->ajax_url(),
            'nonce'      => wp_create_nonce("waves-gateway-for-woocommerce"),
        );

        wp_localize_script('woocommerce_waves_js', 'waves_vars', apply_filters('waves_vars', $waves_vars));

    }

}
