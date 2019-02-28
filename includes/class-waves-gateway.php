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
        $this->currencyIsWaves = in_array(get_woocommerce_currency(), array("WAVES","WNET","ARTcoin","POL","Wykop Coin","TN","Ecop"));
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
		else if (get_woocommerce_currency() == "TN") {
		$total_waves = $total_converted * 100;
		}
		else {
		$total_waves = $total_converted * 100000000;
		//$total_waves_qr = $total_converted * 1;
		}


        $destination_tag = 'P' . hexdec( substr(sha1(current_time(timestamp,1) . key ($woocommerce->cart->cart_contents )  ), 0, 7) );
        $base58 = new StephenHill\Base58();
        $destination_tag_encoded = $base58->encode(strval($destination_tag));
        // set session data 
        WC()->session->set('waves_payment_total', $total_waves);
        WC()->session->set('waves_destination_tag', $destination_tag_encoded);
        WC()->session->set('waves_data_hash', sha1( $this->secret . $total_converted ));
        //QR uri
        if($this->assetId) {
            $url = "https://client.wavesplatform.com/#send/".$this->assetId."?amount=". $total_converted."&recipient=".$this->address."&attachment=".$destination_tag;
        }
		else {
			$url = "https://client.wavesplatform.com/#send/WAVES"."?amount=". $total_converted."&recipient=".$this->address."&attachment=".$destination_tag;
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
				<br>
				<strong>DO NOT FORGET TO ADD THE ATTACHMENT NUMBER IN THE DESCRIPTION FIELD OF YOUR PAYMENT! </strong>
            </div>
            <div class="separator"></div>
            </div>
            <div id="waves-qr-code" data-contents="<?=$url?>"></div>
            <div class="separator"></div>
            <div class="waves-container">
            
            <div class="separator"></div>
        
        <a href="<?=$url?>" target="_blank">
        <button style="color:#FFF;background-color:#0056FF;padding:7px 8px;min-width:64px; font-size: 0.8125rem;min-height:32px;" tabindex="0"" type="button"><span class="jss283">Pay with<span><svg xmlns="http://www.w3.org/2000/svg" width="90" height="16" viewBox="0 0 130 28" style="display: block;margin-top: -5px;"><path fill="#fff" d="M30.25 6.76l.26.36-6.1 18.93-.43.37h-3.89l-.43-.37-4.39-13.55h-.08l-4.37 13.55-.43.37H6.54l-.43-.37L0 7.12l.26-.36h3.88l.4.36 3.93 13.3h.08l4.38-13.3.4-.35h4l.4.35 4.4 13.46 3.94-13.44.4-.37h3.79zm39.37 0l-.43.35-5.38 14-5.33-14-.48-.33h-3.89l-.23.35 7.65 19 .45.36h3.53l.45-.36 7.73-19-.23-.35h-3.84zm21.44 2.42a10.89 10.89 0 0 1 2.59 7.56v.57l-.36.36h-14.5a5.6 5.6 0 0 0 1.78 3.81 5.54 5.54 0 0 0 3.89 1.56A4.5 4.5 0 0 0 89 20.39l.5-.36h3.58l.28.37a8.42 8.42 0 0 1-3.48 4.89 9.15 9.15 0 0 1-5.34 1.51 9.39 9.39 0 0 1-7.12-2.9 10.19 10.19 0 0 1-2.74-7.29 10.53 10.53 0 0 1 2.65-7.18 8.75 8.75 0 0 1 6.83-3 8.9 8.9 0 0 1 6.9 2.75zm-1.72 5a5.08 5.08 0 0 0-5.17-4 5.31 5.31 0 0 0-5.13 4zM50.92 6.76l.36.36v18.95l-.36.36h-3.28l-.36-.36V24h-.12a6.82 6.82 0 0 1-1.91 1.63c-.19.12-.4.22-.61.32a8.24 8.24 0 0 1-3.61.78 8.48 8.48 0 0 1-6.44-2.93 10.34 10.34 0 0 1-2.7-7.27 10.34 10.34 0 0 1 2.7-7.27A8.48 8.48 0 0 1 41 6.37a8.13 8.13 0 0 1 3.62.79 4.85 4.85 0 0 1 .57.31 6.91 6.91 0 0 1 2 1.69l.06-.08v-2l.36-.36h3.28zm-3.84 7.93a5.89 5.89 0 0 0-1.46-2.83 5.32 5.32 0 0 0-4-1.75 5 5 0 0 0-3.81 1.76 6.59 6.59 0 0 0-1.68 4.71 6.54 6.54 0 0 0 1.68 4.71 4.93 4.93 0 0 0 3.8 1.71 5.26 5.26 0 0 0 4-1.75 5.83 5.83 0 0 0 1.46-2.83zm60 .37s-2.15-.45-3.92-.85-2.43-.84-2.43-2 1.18-2.24 3.7-2.24 3.83 1.11 3.83 2.07l.43.37h3.58l.28-.36c0-2.56-2.22-5.65-8-5.65-6.06 0-8 3.56-8 5.86 0 1.93.7 4.2 5.34 5.28l4 .89c2 .47 2.87 1.14 2.87 2.33s-1.07 2.37-3.87 2.37c-2.6 0-4.18-1.25-4.24-2.73l-.45-.36h-3.63l-.28.37c.34 3.3 2.78 6.39 8.62 6.39 6.61 0 8-4 8-6.15-.06-2.83-1.68-4.65-5.79-5.59z"></path><path fill="#fff" d="M116.45 6.77l6.774-6.774 6.774 6.774-6.774 6.774z"></path></svg></span></span><span class="jss71"></span></button>
				</a>
        <div class="separator"></div>
        
            
                <strong>DO NOT FORGET TO ADD THE ATTACHMENT NUMBER IN THE DESCRIPTION FIELD OF YOUR PAYMENT! </strong> <br>
				<p>
                    <?=sprintf(__('Send a payment of exactly %s to the address above (click the links to copy or scan the QR code). <br>
					We will check in the background and notify you when the payment has been validated.', 'waves-gateway-for-woocommerce'), '<strong>'. esc_attr($total_converted).' '.$this->assetCode.'</strong>' )?>
                </p>
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