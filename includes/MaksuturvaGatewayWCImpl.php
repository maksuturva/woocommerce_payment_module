<?php
/**
 * Maksuturva Payment Gateway Plugin for WooCommerce 2.x
 * Plugin developed for Maksuturva
 * Last update: 06/03/2015
 * 
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 * [GNU LGPL v. 2.1 @gnu.org] (https://www.gnu.org/licenses/lgpl-2.1.html)
 * 
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 */


if ( ! defined( 'ABSPATH' ) ) { 
    exit; // Exit if accessed directly
}

require_once dirname(__FILE__) . '/MaksuturvaGatewayAbstract.php';

/**
 * Main class for gateway payments
 * @author RunWeb
 */
class MaksuturvaGatewayWCImpl extends MaksuturvaGatewayAbstract
{
	var $compulsoryReturnData = array(
    	"pmt_action",
    	"pmt_version",
    	"pmt_id",
    	"pmt_reference",
    	"pmt_amount",
    	"pmt_currency",
    	"pmt_sellercosts",
    	"pmt_paymentmethod",
    	"pmt_escrow",
    	"pmt_hash"
    );

	private function calc_tax_rate($product)
	{
		static $tax_rates = array();
		$item_tax_rates = array();
		$compound_tax_rates = 0;
		$regular_tax_rates = 0;
		
		if ( empty( $tax_rates[ $product->get_tax_class() ] )) {
			$tax_rates[ $product->get_tax_class() ] = WC_Tax::get_rates($product->get_tax_class());
		}
		
		$item_tax_rates = $tax_rates[ $product->get_tax_class() ];
		$regular_tax_rates = $compound_tax_rates = 0;
        
        foreach ( $item_tax_rates as $key => $rate )
            if ( $rate['compound'] == 'yes' )
                $compound_tax_rates = $compound_tax_rates + $rate['rate'];
            else
                $regular_tax_rates  = $regular_tax_rates + $rate['rate'];
 
        $regular_tax_rate   = 1 + ( $regular_tax_rates / 100 );
        $compound_tax_rate  = 1 + ( $compound_tax_rates / 100 );
        $the_rate = 0;

 		foreach ( $item_tax_rates as $key => $rate ) {
            if ( ! isset( $taxes[ $key ] ) )
                 $taxes[ $key ] = 0;
 
            $the_rate      = $rate['rate']; // 100;
 
            if ( $rate['compound'] == 'yes' ) {
                 //$the_price = $price;
            //    $the_rate  = $the_rate / $compound_tax_rate;
            } else {
                 //$the_price = $non_compound_price;
            //    $the_rate  = $the_rate / $regular_tax_rate;
            }
        }
        return $the_rate;
	}

	function __construct($plugin, $order, $cart = null) 
	{
		global $woocommerce;
		$secretKey = $plugin->maksuturva_secretkey;
	    $sellerId = $plugin->maksuturva_sellerid;
	    $dueDate = date("d.m.Y");
		$id = $plugin->maksuturva_orderid_prefix . self::getMaksuturvaId($order->id);
		$pid = self::getMaksuturvaId($order->id);

		$products_rows = array();
		
		$discount_total = 0;
  		$product_total = 0;
		$shipping_total = 0;
		
		foreach ( $order->get_items() as $order_item_id => $item ) {
			$product = $order->get_product_from_item($item);

			$desc = ''; //$product->post->post_excerpt; // $product->get_title();
			
			$woi = new WC_Order_Item_Meta($order->get_item_meta($order_item_id, ''));
          	$gf = $woi->get_formatted();
          	  	
			if ( $gf ) {
				foreach($gf as $attr) {
          	  		$desc .= implode('=', $attr).'|';
				}
			}
          
			$item_price_with_tax = $order->get_item_subtotal($item, true);
			$item_totalprice_with_tax = $order->get_item_total($item, true);
			

			$discount_pct =  ($item_price_with_tax - $item_totalprice_with_tax) / $item_price_with_tax *100.0;  //( $line_price_with_tax - $line_totalprice_with_tax); //)) / $line_price_with_tax*100;
			
			if ($product->product_type == 'variable') {
				$desc .= implode(',',$product->get_variation_attributes( )). ' ';
			}
			$desc .= ($product->post->post_excerpt); //apply_filters( 'woocommerce_short_description', $product->post->post_excerpt)); 
			//$product_total = $product_total + $item_totalprice_with_tax;
			//$discount_total = $discount_total - $line_price_with_tax - $item_totalprice_with_tax;
			
			$encoding = get_bloginfo('charset');
			//if($plugin->debug) {
			//	$plugin->log->add($plugin->id, $encoding.' Item total price='.$item_totalprice_with_tax." subtotal=".$item_price_with_tax." discount-%=".$discount_pct.":".$desc);
			//}

			$sku = '-';
			if($product->get_sku()) {
				$sku = $product->get_sku();
			}

			$row = array(
				'pmt_row_name' => $item['name'],                                                      //alphanumeric        max lenght 40             -
            	'pmt_row_desc' => strip_tags(html_entity_decode($desc)),//'Product #'.$product->product_id,                                                       //alphanumeric        max lenght 1000      min lenght 1
            	'pmt_row_quantity' => $item['qty'], //$order->get_item_meta($order_item_id)['qty'],                                                    //numeric             max lenght 8         min lenght 1
            	'pmt_row_articlenr' => $sku,
		        'pmt_row_deliverydate' => date("d.m.Y"),                                                   //alphanumeric        max lenght 10        min lenght 10        dd.MM.yyyy
            	'pmt_row_price_gross' => str_replace('.', ',', sprintf("%.2f", $order->get_item_subtotal($item, true)  )),          //alphanumeric        max lenght 17        min lenght 4         n,nn
            	'pmt_row_vat' =>  str_replace('.', ',',sprintf("%.2f", $this->calc_tax_rate($product))),               //alphanumeric        max lenght 5         min lenght 4         n,nn
            	'pmt_row_discountpercentage' => str_replace('.', ',', sprintf("%.2f", $discount_pct)),                                                    //alphanumeric        max lenght 5         min lenght 4         n,nn
            	'pmt_row_type' => 1,
			);
			
			array_push($products_rows, $row);
		}

		//Coupons
		if ($cart) {
			foreach ($cart->get_coupons() as $code => $coupon) {

				$coupon_desc = $code;
				$coupon = new WC_Coupon($code);

				if ($coupon->apply_before_tax()) {
					continue;
				}

 				$coupon_post  = get_post( $coupon->id );
				$excerpt = $coupon_post->post_excerpt;

				if ($excerpt) {
					$coupon_desc .= ' ' . $excerpt;
				}
			

				$row = array(
				    'pmt_row_name' => __( 'Discount', $plugin->td ),
		        	'pmt_row_desc' => strip_tags(html_entity_decode($coupon_desc)),
		        	'pmt_row_quantity' => 1,
		        	'pmt_row_deliverydate' => date("d.m.Y"),
		        	'pmt_row_price_gross' => str_replace('.', ',', sprintf("-%.2f", $cart->get_coupon_discount_amount($code))),
		        	'pmt_row_vat' => "0,00",
		        	'pmt_row_discountpercentage' => "00,00",
		        	'pmt_row_type' => 6
				);

				array_push($products_rows, $row);
			}
		} elseif ($order->get_total_discount()) {
			$discount = $order->get_total_discount();
			$coupon_desc = implode(',',$order->get_used_coupons( ));
			$row = array(
			    'pmt_row_name' => __( 'Discount', $plugin->td ),
	        	'pmt_row_desc' => strip_tags(html_entity_decode($coupon_desc)),
	        	'pmt_row_quantity' => 1,
	        	'pmt_row_deliverydate' => date("d.m.Y"),
	        	'pmt_row_price_gross' => str_replace('.', ',', sprintf("-%.2f", $discount)),
	        	'pmt_row_vat' => "0,00",
	        	'pmt_row_discountpercentage' => "00,00",
	        	'pmt_row_type' => 6
			);

			array_push($products_rows, $row);
		}

		//Shipping costs
		$shipping_cost = $order->get_total_shipping() + $order->get_shipping_tax( ); //- $order->get_shipping_tax( );
		if ($order->get_total_shipping()>0)
			$shipping_tax = 100 * $order->get_shipping_tax( ) / $order->get_total_shipping();
		else
			$shipping_tax = 0;

		$row = array(
		    'pmt_row_name' => __('Shipping cost', $plugin->td ),
        	'pmt_row_desc' => strip_tags(html_entity_decode($order->get_shipping_method())),
        	'pmt_row_quantity' => 1,
        	'pmt_row_deliverydate' => date("d.m.Y"),
        	'pmt_row_price_gross' => str_replace('.', ',', sprintf("%.2f", $shipping_cost )),
        	'pmt_row_vat' => str_replace('.', ',',sprintf("%.2f",$shipping_tax)),
        	'pmt_row_discountpercentage' => "0,00",
        	'pmt_row_type' => 2,
		);
		array_push($products_rows, $row);

		$returnURL = $plugin->notify_url;
		if ($woocommerce->session && $woocommerce->session->id) {
			$sessionid = $woocommerce->session->id;

			$returnURL = add_query_arg('sessionid', $sessionid, $plugin->notify_url); //get_return_url( $order ));
			$returnURL = add_query_arg('orderid', $id, $returnURL);	
		}
		//$returnURL = add_query_arg('gateway', 'wc_maksurva_emaksut', $returnURL);
		$billing_email = $order->billing_email;
		
		$billing_phone = $order->billing_phone;

		if ( ! empty( $order->customer_user ) ) {
          	$user = get_user_by( 'id', $order->customer_user );
          	if (empty( $order->billing_email ) )
	        	$billing_email = $user->user_email;
	        if (empty( $order->billing_email ) )
	        	$billing_email = $user->user_phone;
	    }

	    $locale = get_bloginfo('language');
	    global $sitepress;
	    if ($sitepress) {
	    	$locale = $sitepress->get_locale(ICL_LANGUAGE_CODE);
	    }
	    if ($locale != "fi_FI" || $locale != "sv_FI" || $locale != "en_FI") {
	    	if(substr($locale, 0, 2) == "fi") {
	    		$locale = "fi_FI";
	    	} elseif(strpos($locale, "sv") != FALSE) {
	    		$locale = "sv_FI";
	    	} else {
	    		$locale = "en_FI";
	    	}
	    }
	    
	    $options = array(
			"pmt_keygeneration" => $plugin->maksuturva_keyversion,
			"pmt_id" 		=> $id,
			"pmt_orderid"	=> $order->id,
			"pmt_reference" => $pid,
			"pmt_sellerid" 	=> $sellerId,
			"pmt_duedate" 	=> $dueDate,
			"pmt_userlocale" => $locale,
			"pmt_okreturn"	=> add_query_arg('pmt_act', 'ok', $returnURL), 
			"pmt_errorreturn"	=> add_query_arg('pmt_act', 'error', $returnURL), 
			"pmt_cancelreturn"	=> add_query_arg('pmt_act', 'cancel', $returnURL), 

			//Tiilisiirtoa ei tueta enää nykyisissä valmispaketeissa. Siksi usein ehdotammekin
			//käytettävän viivästetty maksupaluuosoitteeseena samaa osoitetta kuin peruutusten tapauksessa (pmt_delayedpayreturn ~= pmt_cancelreturn).
			"pmt_delayedpayreturn"	=> add_query_arg('pmt_act', 'delay', $returnURL), 
			"pmt_amount" 		=> str_replace('.', ',', sprintf("%.2f",  $order->get_total() - $shipping_cost )),

			// Delivery information
			"pmt_deliveryname" 	=> trim($order -> shipping_first_name .' '. $order -> shipping_last_name),
		    "pmt_deliveryaddress" => trim($order -> shipping_address_1. "  ". $order -> shipping_address_2), //$this->clean($data['billing_address']['address']),
			"pmt_deliverypostalcode" => trim($this->clean($order -> shipping_postcode, '000')),
			"pmt_deliverycity" => trim($this->clean($order -> shipping_city)),
			"pmt_deliverycountry" => trim($this->clean($order -> shipping_country)),
		    		    
			// Customer Information
		    "pmt_buyername" 	=> trim($order -> billing_first_name .' '. $order -> billing_last_name),
		   	"pmt_buyeraddress" => trim($this->clean($order -> billing_address_1. "  ". $order -> billing_address_2)),
			"pmt_buyerpostalcode" => trim($this->clean($order -> billing_postcode, '000')),
			"pmt_buyercity" => trim($this->clean($order -> billing_city)),
			"pmt_buyercountry" => trim($order -> billing_country),
		    "pmt_buyeremail" => trim($billing_email),
		    "pmt_buyerphone" => trim($billing_phone),

			// emaksut
			"pmt_escrow" => ($plugin->maksuturva_emaksut == "no" ? "Y" : "N"),

			
			"pmt_sellercosts" => str_replace('.', ',', sprintf("%.2f", $shipping_cost)),

		    "pmt_rows" => count($products_rows),
		    "pmt_rows_data" => $products_rows,

		);
		parent::__construct($secretKey, $options, $plugin->maksuturva_encoding, $plugin->maksuturva_url);		

	}

	static public function getMaksuturvaId($order_id){ 
		return intval($order_id) + 100;
	}
	static public function getOrderId($maksuturva_id){
		return intval($maksuturva_id) - 100;
	}
	
    public function calcPmtReferenceCheckNumber()
    {
        return $this->getPmtReferenceNumber($this->_formData['pmt_reference']);
    }

    public function calcHash()
    {
        return $this->generateHash();
    }

    public function getHashAlgo()
    {
        return $this->_hashAlgoDefined;
    }
    
	private function clean($var, $def='EMPTY'){
		return ($var ? $var : $def);
	}
}