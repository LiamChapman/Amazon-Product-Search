<?php

/**
 * Amazon_Product_Search
 * Class to interact with the Amazon Product API
 * @author Liam Chapman
 * @version 1.0 
 *
 * @example
 *
 * (Square brackets show optional parameters)
 *
 * $aps 	= Amazon_Product_Search('MY_PUBLIC_KEY', 'MY_PRIVATE_KEY', 'MY_ASSOC_TAG', [com, co.uk, fr, de]);
 * $search  = $aps->search('Harry Potter', [Books, DVDs], [true, false]);
 * print_r($search); # yeaaaah boooi!
**/
class Amazon_Product_Search {
	
	/* Variables to access your account on Amazon */	
	protected $public_key, $private_key, $assoc_tag, $query_string;
	
	/* Product Search Parameters */
	public $operation 	   = 'ItemSearch',
		   $version   	   = '2009-03-31', //'2011-08-01',
		   $response_group = 'Large',		   
		   $service 	   = 'AWSECommerceService';		   

	/* Request Defaults */
	public $defaults = array(
		'url'  		=> 'ecs.amazonaws.',
		'uri'		=> '/onca/xml',
		'hash' 		=> 'sha256',
		'method'	=> 'GET'
	);

	/**
	 * __construct
	 * Pass through account details and region.
	 * @param $public_key String
	 * @param $private_key String
	 * @param $assoc_tag String
	 * @param $region String
	 * @return void
	**/
	public function __construct ($public_key, $private_key, $assoc_tag, $region = 'com') {
		$this->public_key   = $public_key;
		$this->private_key  = $private_key;
		$this->assoc_tag	= $assoc_tag;
		$this->region 		= $region;
	}

	/**
	 * parameters()
	 * Builds the query to be passed to amazon and encodes and cleans where necessary
	 * @param $array Array
	 * @return void
	**/
	public function parameters ($array = array()) {
		$array['AWSAccessKeyId'] = $this->public_key;
		$array['AssociateTag']	 = $this->assoc_tag;
		$array['Service'] 		 = $this->service;
		$array['Timestamp']		 = gmdate("Y-m-d\TH:i:s\Z");
		$array['Version']		 = $this->version;
		$array['Operation']		 = $this->operation;
		$array['ResponseGroup']	 = $this->response_group;		
		ksort($array);				
		$return 				 = array();
		foreach ($array as $key => $value) {
			$key 	  = str_replace("%7E", "~", rawurlencode($key));
			$value 	  = str_replace("%7E", "~", rawurlencode($value));
			$return[] = $key.'='.$value;
		}		
		$this->query_string =  implode("&", $return);
	}

	/**
	 * search()
	 * Quick method to search a particula index, such as Books, DVDs etc
	 * Just pass through keywords
	 * @param $keywords String
	 * @param $index String - Default Books.
	 * @param $raw	 Boolean
	 * @return SimpleXML
	**/
	public function search ($keywords, $index = 'Books', $raw = false) {
		$this->parameters(array('Keywords' => $keywords, 'SearchIndex' => $index));
		return $this->output($raw);
	}

	/**
	 * signature()
	 * Builds signature for request and encodes/hashes 
	 * @return String
	**/
	private function signature () {
		$url = $this->defaults['method']."\n".$this->defaults['url'].$this->region."\n".$this->defaults['uri']."\n".$this->query_string;
		$signature = base64_encode(hash_hmac($this->defaults['hash'], $url, $this->private_key, true));
		return str_replace("%7E", "~", rawurlencode($signature));
	}

	/**
	 * url()
	 * Returns the final url to be sent to Amazon
	 * @return String
	**/
	private function url () {		
		return 'http://'.$this->defaults['url'].$this->region.$this->defaults['uri']."?".$this->query_string."&Signature=".$this->signature();
	}


	/**
	 * request()
	 * Opens up a stream to amazon by sending the finished url
	 * @return Response
	**/
	private function request () {				
		return file_get_contents($this->url());
	}

	/**
	 * output()
	 * Returns the data from amazon, optionally return raw response and also optionally just the Items node
	 * @param $raw Boolean
	 * @param $items_only Boolean
	 * @return SimpleXML || Response
	**/
	public function output($raw = false, $items_only = true) {
		$response  = $this->request();
		if ($response === false) {
			return false;
		} else {
			if (!$raw) {
				if ($items_only) {
					return simplexml_load_string($response)->Items;
				} else {
					return simplexml_load_string($response);
				}
			} else {
				return $response;
			}
		}
	}

}