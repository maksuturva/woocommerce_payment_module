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


// Helpful functions to work better with MB
function mb_str_replace($needle, $replacement, $haystack)
{
    $needle_len = mb_strlen($needle);
    $replacement_len = mb_strlen($replacement);
    $pos = mb_strpos($haystack, $needle);
    while ($pos !== false)
    {
        $haystack = mb_substr($haystack, 0, $pos) . $replacement
                . mb_substr($haystack, $pos + $needle_len);
        $pos = mb_strpos($haystack, $needle, $pos + $replacement_len);
    }
    return $haystack;
}

function mb_concat($str1, $str2)
{
	return $str1 . $str2;
}

/**
 * Main class for gateway payments
 * @author RunWeb
 */
abstract class MaksuturvaGatewayAbstract
{
	/**
	 * Status query codes
	 */
	const STATUS_QUERY_NOT_FOUND 		= "00";
	const STATUS_QUERY_FAILED 			= "01";
	const STATUS_QUERY_WAITING 			= "10";
	const STATUS_QUERY_UNPAID 			= "11";
	const STATUS_QUERY_UNPAID_DELIVERY	= "15";
	const STATUS_QUERY_PAID 			= "20";
	const STATUS_QUERY_PAID_DELIVERY	= "30";
	const STATUS_QUERY_COMPENSATED 		= "40";
	const STATUS_QUERY_PAYER_CANCELLED 	= "91";
	const STATUS_QUERY_PAYER_CANCELLED_PARTIAL			= "92";
	const STATUS_QUERY_PAYER_CANCELLED_PARTIAL_RETURN	= "93";
	const STATUS_QUERY_PAYER_RECLAMATION 				= "95";
	const STATUS_QUERY_CANCELLED 						= "99";
	
	const EXCEPTION_CODE_ALGORITHMS_NOT_SUPORTED          = '00';
	const EXCEPTION_CODE_URL_GENERATION_ERRORS            = '01';
	const EXCEPTION_CODE_FIELD_ARRAY_GENERATION_ERRORS    = '02';
	const EXCEPTION_CODE_REFERENCE_NUMBER_UNDER_100       = '03';
	const EXCEPTION_CODE_FIELD_MISSING                    = '04';
	const EXCEPTION_CODE_INVALID_ITEM                     = '05';
	const EXCEPTION_CODE_PHP_CURL_NOT_INSTALLED           = '06';
	const EXCEPTION_CODE_HASHES_DONT_MATCH                = '07';


    protected $_formData = array();

    protected $_secretKey = null;

    protected $_hashAlgoDefined = null;

    /**
     * Url used to redirect the user to
     * @var string
     */
    protected $_baseUrl = null;

    /**
     * Status query channel
     * @var string
     */
    protected $_statusQueryBaseUrl = "https://www.maksuturva.fi/PaymentStatusQuery.pmt";
    /**
     * Data POSTed to maksuturva
     * @var array
     */
    protected $_statusQueryData = array();

    protected $_charset = 'UTF-8';

    protected $_charsethttp = 'UTF-8';

    /**
     * @var array
     */
    protected $_optionalData = array(
        'pmt_selleriban',
        'pmt_userlocale',
        'pmt_invoicefromseller',
        'pmt_paymentmethod',
    	'pmt_buyeridentificationcode',
        'pmt_buyerphone',
        'pmt_buyeremail',
    );

    protected $_compulsoryData = array(
        'pmt_action',                    //alphanumeric        max lenght 50        min lenght 4        NEW_PAYMENT_EXTENDED
        'pmt_version',                   //alphanumeric        max lenght 4         min lenght 4        0004

        'pmt_sellerid',                  //alphanumeric        max lenght 15             -
        'pmt_id',                        //alphanumeric        max lenght 20             -
        'pmt_orderid',                   //alphanumeric        max lenght 50             -
        'pmt_reference',                 //numeric             max lenght 20        min lenght 4        Reference number + check digit
        'pmt_duedate',                   //alphanumeric        max lenght 10        min lenght 10       dd.MM.yyyy
        'pmt_amount',                    //alphanumeric        max lenght 17        min lenght 4
        'pmt_currency',                  //alphanumeric        max lenght 3         min lenght 3        EUR

        'pmt_okreturn',                  //alphanumeric        max lenght 200            -
        'pmt_errorreturn',               //alphanumeric        max lenght 200            -
        'pmt_cancelreturn',              //alphanumeric        max lenght 200            -
        'pmt_delayedpayreturn',          //alphanumeric        max lenght 200            -

        'pmt_escrow',                    //alpha               max lenght 1         min lenght 1         Maksuturva=Y, eMaksut=N
        'pmt_escrowchangeallowed',       //alpha               max lenght 1         min lenght 1         N

        'pmt_buyername',                 //alphanumeric        max lenght 40             -
        'pmt_buyeraddress',              //alphanumeric        max lenght 40             -
        'pmt_buyerpostalcode',           //numeric             max lenght 5              -
        'pmt_buyercity',                 //alphanumeric        max lenght 40             -
        'pmt_buyercountry',              //alpha               max lenght 2              -               Respecting the ISO 3166

        'pmt_deliveryname',              //alphanumeric        max lenght 40             -
        'pmt_deliveryaddress',           //alphanumeric        max lenght 40             -
        'pmt_deliverypostalcode',        //numeric             max lenght 5              -
        'pmt_deliverycountry',           //alpha               max lenght 2              -               Respecting the ISO 3166

        'pmt_sellercosts',               //alphanumeric        max lenght 17        min lenght 4         n,nn

    	'pmt_rows',                      //numeric             max lenght 4         min lenght 1

    	'pmt_charset',                   //alphanumeric        max lenght 15             -               {ISO-8859-1, ISO-8859-15, UTF-8}
    	'pmt_charsethttp',               //alphanumeric        max lenght 15             -               {ISO-8859-1, ISO-8859-15, UTF-8}
    	'pmt_hashversion',               //alphanumeric        max lenght 10             -               {SHA-512, SHA-256, SHA-1, MD5}
      /*'pmt_hash',                      //alphanumeric        max lenght 128       min lenght 32*/
    	'pmt_keygeneration',             //numeric             max lenght 3              -
    );

    protected $_rowOptionalData = array(
     	'pmt_row_articlenr',
        'pmt_row_unit',
    );

    protected $_rowCompulsoryData = array(
        'pmt_row_name',                  //alphanumeric        max lenght 40             -
    	'pmt_row_desc',                  //alphanumeric        max lenght 1000      min lenght 1
    	'pmt_row_quantity',              //numeric             max lenght 8         min lenght 1
    	'pmt_row_deliverydate',          //alphanumeric        max lenght 10        min lenght 10        dd.MM.yyyy
    	'pmt_row_price_gross',           //alphanumeric        max lenght 17        min lenght 4         n,nn
    	'pmt_row_price_net',             //alphanumeric        max lenght 17        min lenght 4         n,nn
    	'pmt_row_vat',                   //alphanumeric        max lenght 5         min lenght 4         n,nn
    	'pmt_row_discountpercentage',    //alphanumeric        max lenght 5         min lenght 4         n,nn
    	'pmt_row_type',                  //numeric             max lenght 5         min lenght 1
    );

    protected $_hashData = array(
        'pmt_action',
        'pmt_version',
		'pmt_selleriban',
		'pmt_id',
		'pmt_orderid',
		'pmt_reference',
		'pmt_duedate',
		'pmt_amount',
		'pmt_currency',
		'pmt_okreturn',
		'pmt_errorreturn',
		'pmt_cancelreturn',
		'pmt_delayedpayreturn',
		'pmt_escrow',
		'pmt_escrowchangeallowed',
		'pmt_invoicefromseller',
		'pmt_paymentmethod',
		'pmt_buyeridentificationcode',
		'pmt_buyername',
		'pmt_buyeraddress',
		'pmt_buyerpostalcode',
		'pmt_buyercity',
		'pmt_buyercountry',
		'pmt_deliveryname',
		'pmt_deliveryaddress',
		'pmt_deliverypostalcode',
		'pmt_deliverycity',
		'pmt_deliverycountry',
		'pmt_sellercosts',
		/*'pmt_row_* fields in specified order, one row at a time',
		'<merchant’s secret key >'*/
    );

    private $_errors = array();

    /**
     * To be duplicated before trimming the content
     * @var array
     */
    private $_originalFormData = array();
    
    private $_fieldLength = array(
    	// min, max, required 
    	'pmt_action'	=> array(4, 50),
        'pmt_version' 	=> array(4, 4),
    	'pmt_sellerid' 	=> array(1, 15),
		'pmt_selleriban'	=> array(18, 30), // optional
		'pmt_id' 	=> array(1, 20),
		'pmt_orderid'	=> array(1, 50),
		'pmt_reference' => array(3, 20), // > 100
		'pmt_duedate'	=> array(10, 10),
    	'pmt_userlocale'	=> array(5, 5), // optional
		'pmt_amount' 	=> array(4, 17),
		'pmt_currency'	=> array(3, 3),
		'pmt_okreturn'	=> array(1, 200),
		'pmt_errorreturn'	=> array(1, 200),
		'pmt_cancelreturn'	=> array(1, 200),
		'pmt_delayedpayreturn'	=> array(1, 200),
		'pmt_escrow'	=> array(1, 1),
		'pmt_escrowchangeallowed'	=> array(1, 1),
		'pmt_invoicefromseller'	=> array(1, 1),	// opt
		'pmt_paymentmethod'		=> array(4, 4), // opt
		'pmt_buyeridentificationcode'	=> array(9, 11),	// opt
		'pmt_buyername'	=> array(1, 40),
		'pmt_buyeraddress'	=> array(1, 40),
		'pmt_buyerpostalcode'	=> array(1, 5),
		'pmt_buyercity'	=> array(1, 40),
		'pmt_buyercountry'	=> array(1, 2),
    	'pmt_buyerphone'	=> array(0, 40),	// opt
    	'pmt_buyeremail'	=> array(0, 40),	// opt
		'pmt_deliveryname'	=> array(1, 40),
		'pmt_deliveryaddress' 	=> array(1, 40),
		'pmt_deliverypostalcode'	=> array(1, 5),
		'pmt_deliverycity'	=> array(1, 40),
		'pmt_deliverycountry'	=> array(1, 2),
		'pmt_sellercosts'	=> array(4, 17),
    	'pmt_rows'	=> array(1, 4),
    	'pmt_row_name'	=> array(1, 40),
    	'pmt_row_desc'	=> array(1, 1000), 
    	'pmt_row_quantity'	=> array(1, 8),
    	'pmt_row_deliverydate'	=> array(10, 10),
    	'pmt_row_price_gross'	=> array(4, 17),
    	'pmt_row_price_net'	=> array(4, 17),
    	'pmt_row_vat'	=> array(4, 5),
    	'pmt_row_discountpercentage'	=> array(4, 5),
    	'pmt_row_type'	=> array(1, 5),
    	'pmt_charset'	=> array(1, 15),
    	'pmt_charsethttp'	=> array(1, 15),
    	'pmt_hashversion'	=> array(1, 10),
    	'pmt_keygeneration'	=> array(1, 3),
    );

    protected function dataIsValid()
    {
        $isvalid = true;
		
		$DELIVERY_FIELDS = array(
			'pmt_deliveryname' => 'pmt_buyername' ,
			'pmt_deliveryaddress' => 'pmt_buyeraddress' ,
			'pmt_deliverypostalcode' => 'pmt_buyerpostalcode' , 
			'pmt_deliverycity' => 'pmt_buyercity' , 
			'pmt_deliverycountry' => 'pmt_buyercountry'
		);
		
		foreach ($DELIVERY_FIELDS as $dfield => $bfield){
			if  ( (! isset($this->_formData[$dfield])) || mb_strlen(trim($this->_formData[$dfield])) == 0  || $this->_formData[$dfield] == NULL)
				$this->_formData[$dfield] = $this->_formData[$bfield];
		}
		
        foreach ($this->_compulsoryData as $compulsoryData) {
            if (array_key_exists($compulsoryData, $this->_formData)) {
                switch ($compulsoryData) {
                    case 'pmt_reference':
                        if (mb_strlen((string)$this->_formData['pmt_reference']) < 3) {
                            $isvalid = false;
                            $this->_errors[] = "$compulsoryData need to have at least 3 digits";
                        }
                        break;
                }
            } else {
                $isvalid = false;
                $this->_errors[] = "$compulsoryData is mandatory";
                //break;
            }
        }

        if (array_key_exists("pmt_rows_data", $this->_formData)) {
            $countRows = 1;
            foreach ($this->_formData['pmt_rows_data'] as $rowData) {
                $isvalid = $this->itemIsValid($rowData, $countRows, $isvalid);
                $countRows++;
            }

        } else {
            $isvalid = false;
        }

        if (($countRows - 1) != $this->_formData['pmt_rows']) {
            $isvalid = false;
            $this->_errors[] = "The amount(" .  $this->_formData['pmt_rows'] . ") of items passed in the field 'pmt_rows' don't match with real amount(" . ($countRows - 1) . ") of items";
        }

        // now, filter the content
        $tmp = $this->filterFieldsLength();

        return $isvalid;
    }

    protected function itemIsValid($data, $countRows= null, $isvalid = true)
    {
        foreach ($this->_rowCompulsoryData as $rowCompulsoryKeyData) {
            if (array_key_exists($rowCompulsoryKeyData, $data)) {
                switch ($rowCompulsoryKeyData) {
                    case 'pmt_row_price_gross':
                        if (array_key_exists('pmt_row_price_net', $data)) {
                            $isvalid = false;
                            $this->_errors[] = "pmt_row_price_net$countRows and pmt_row_price_gross$countRows are both supplied, when only one of them should be";
                        }
                        break;
                }
            } else {
                switch ($rowCompulsoryKeyData) {
                    case 'pmt_row_price_gross':
                        if (array_key_exists('pmt_row_price_net', $data)) {
        			        break;
        			    }
        			case 'pmt_row_price_net':
        			    if (array_key_exists('pmt_row_price_gross', $data)) {
        			        break;
        			    }
        			default:
        			    $isvalid = false;
                        $this->_errors[] = "$rowCompulsoryKeyData$countRows is mandatory";
                }

            }
        }

        return $isvalid;
    }

    public function __construct($secretKey, $data = null, $encoding = null, $url = 'https://www.maksuturva.fi')
    {
        if ($encoding) {
            $this->_charset = $encoding;
            $this->_charsethttp = $encoding;
        }
        
        $this->_secretKey                           = $secretKey;
        $this->_baseUrl                             = self::getPaymentUrl($url);
        $this->_statusQueryBaseUrl                  = self::getStatusQueryUrl($url);

        $this->_formData['pmt_action']              = 'NEW_PAYMENT_EXTENDED';
        $this->_formData['pmt_version']             = '0004';
        $this->_formData['pmt_escrow']              = 'Y';
        $this->_formData['pmt_keygeneration']       = '001';
        $this->_formData['pmt_currency']            = 'EUR';
        $this->_formData['pmt_escrowchangeallowed'] = 'N';
        
    	$this->_formData['pmt_charset']             = $this->_charset;
    	$this->_formData['pmt_charsethttp']         = $this->_charsethttp;

//        if ($data) {
//             $this->_formData = array_merge($this->_formData, $data);
//        }
        // Force to cut off all amps and merge in current array_data
        foreach ($data as $key => $value ){
            if ($key == 'pmt_rows_data') {
                $rows = array(); 
                foreach ($value as $k => $v){
                    $rows[$k] = str_replace('&amp;', '', $v);
                }
                $this->_formData[$key] = $rows;
            } else {
                $this->_formData[$key] = str_replace('&amp;', '', $value);
            }
        }
        $hashAlgos = hash_algos();

        if (in_array("sha512", $hashAlgos)) {
            $this->_formData['pmt_hashversion'] = 'SHA-512';
            $this->_hashAlgoDefined = "sha512";
        } else if (in_array("sha256", $hashAlgos)) {
            $this->_formData['pmt_hashversion'] = 'SHA-256';
            $this->_hashAlgoDefined = "sha256";
        } else if (in_array("sha1", $hashAlgos)) {
            $this->_formData['pmt_hashversion'] = 'SHA-1';
            $this->_hashAlgoDefined = "sha1";
        } else if (in_array("md5", $hashAlgos)) {
            $this->_formData['pmt_hashversion'] = 'MD5';
            $this->_hashAlgoDefined = "md5";
        } else {
           throw new MaksuturvaGatewayException(array('the hash algorithms SHA-512, SHA-256, SHA-1 and MD5 are not supported!'), self::EXCEPTION_CODE_ALGORITHMS_NOT_SUPORTED);
        }

    }

    public function getUrl()
    {

        $url = $this->convert_encoding($this->_baseUrl . "?", $this->_charsethttp);

        if ($this->dataIsValid()) {

            //Generate the check number for pmt_reference
            $this->_formData['pmt_reference']  = $this->getPmtReferenceNumber($this->_formData['pmt_reference']);

            foreach ($this->_formData as $key => $data) {
                if ($key == 'pmt_rows_data') {
                    $rowCount = 1;
                    foreach ($data as $rowData) {
                        foreach ($rowData as $rowKey => $rowInnerData) {
                            $url .= $this->convert_encoding($rowKey . $rowCount . '=' . $rowInnerData . '&', $this->_charsethttp);
                        }
                        $rowCount++;
                    }
                } else {
                    $url .= $this->convert_encoding($key . '=' . $data . '&', $this->charsethttp);
                }
            }

            $url .= $this->convert_encoding('pmt_hash=', $this->_charsethttp) . $this->convert_encoding($this->generateHash(), $this->_charset);

            return $url;
        } else {
            throw new MaksuturvaGatewayException($this->getErrors(), self::EXCEPTION_CODE_URL_GENERATION_ERRORS);
        }
    }

    public function getFieldArray()
    {
        if ($this->dataIsValid()) {
            $returnArray = array();

            //Generate the check number for pmt_reference
            $this->_formData['pmt_reference']  = $this->getPmtReferenceNumber($this->_formData['pmt_reference']);

            foreach ($this->_formData as $key => $data) {
                if ($key == 'pmt_rows_data') {
                    $rowCount = 1;
                    foreach ($data as $rowData) {
                        foreach ($rowData as $rowKey => $rowInnerData) {
                            $returnArray[$this->convert_encoding($rowKey . $rowCount, $this->charsethttp)] = $this->convert_encoding($rowInnerData, $this->charsethttp);
                        }
                        $rowCount++;
                    }
                } else {
                    $returnArray[$this->convert_encoding($key, $this->charsethttp)] = $this->convert_encoding($data, $this->charsethttp);
                }
            }

            $returnArray[$this->convert_encoding('pmt_hash', $this->charsethttp)] = $this->convert_encoding($this->generateHash(), $this->_charset);

            return $returnArray;
        } else {
            throw new MaksuturvaGatewayException($this->getErrors(), self::EXCEPTION_CODE_FIELD_ARRAY_GENERATION_ERRORS);
        }
    }

    protected function generateHash()
    {
        $hashString = '';
        foreach ($this->_hashData as $hashData) {
            switch ($hashData) {
                case 'pmt_selleriban':
                case 'pmt_invoicefromseller':
                case 'pmt_paymentmethod':
                case 'pmt_buyeridentificationcode':
                    if (in_array($hashData, $this->_formData)) {
                        $hashString .= $this->_formData[$hashData] . '&';
                    }
                    break;
                default:
                    $hashString .= $this->_formData[$hashData] . '&';
            }
        }

        foreach ($this->_formData['pmt_rows_data'] as $order) {
            foreach ($order as $data) {
                $hashString .= $data . '&';
            }
        }

        $hashString .= $this->_secretKey . '&';

        return hash($this->_hashAlgoDefined, $hashString);
    }

    /**
     * Calculate the hash based on the paraeters returned from maksuturva
     * @param array $hashData
     */
    public function generateReturnHash($hashData)
    {
        $hashString = '';
        foreach ($hashData as $key => $data) {
            //Ignore the hash itself if passed
            if ($key != 'pmt_hash') {
                $hashString .= $data . '&';
            }
        }

        $hashString .= $this->_secretKey . '&';

        return strtoupper(hash($this->_hashAlgoDefined, $hashString));

    }

    protected function getPmtReferenceNumber($number)
    {
        if ($number < 100) {
        	throw new MaksuturvaGatewayException(array("Cannot generate reference numbers for an ID smaller than 100"), self::EXCEPTION_CODE_REFERENCE_NUMBER_UNDER_100);
        }

        // Painoarvot
        $tmpMultip = array(7, 3, 1);
        // Muutetaan parametri merkkijonoksi
        $tmpStr = (string)$number;
        $tmpSum = 0;
        $tmpIndex = 0;
        for ($i=strlen($tmpStr)-1; $i>=0; $i--) {
            $tmpSum += intval(substr($tmpStr, $i, 1)) * intval($tmpMultip[$tmpIndex % 3]);
            $tmpIndex++;
        }

        // Laskettua summaa vastaava seuraava täysi kymmenluku:
        $nextTen = ceil(intval($tmpSum)/10)*10;

        return $tmpStr . (string)(abs($nextTen-$tmpSum));
    }

    public function getErrors()
    {
        return $this->_errors;
    }

    public function __get($name)
    {
        if (in_array($name, $this->_compulsoryData) || in_array($name, $this->_optionalData) || $name == 'pmt_rows_data') {
            return $this->_formData[$name];
        }
        return null;
    }

    public function __set($name, $value)
    {
        if (in_array($name, $this->_compulsoryData) || in_array($name, $this->_optionalData)) {
            $this->_formData[$name] = $value;
        } else {
            throw new MaksuturvaGatewayException(array("Field $name is not part of the form"), self::EXCEPTION_CODE_FIELD_MISSING);
        }
    }

    public function addItem($data)
    {
        if (!$this->itemIsValid($data)) {
            throw new MaksuturvaGatewayException($this->getErrors(), self::EXCEPTION_CODE_INVALID_ITEM);
        }

        $this->_formData['pmt_rows_data'][] = $data;
        $this->_formData['pmt_rows']++;

    }

    public function setItemData($index, $dataKey, $value)
    {
        if (in_array($dataKey, $this->_rowCompulsoryData) || $dataKey($name, $this->_rowOptionalData)) {
            $this->_formData['pmt_rows_data'][$index][$dataKey] = $value;
        } else {
            throw new MaksuturvaGatewayException(array("Item field $name is not part of the form"), self::EXCEPTION_CODE_FIELD_MISSING);
        }
    }

    public function getItemData($index, $dataKey)
    {
        if (in_array($dataKey, $this->_rowCompulsoryData) || $dataKey($name, $this->_rowOptionalData)) {
            return $this->_formData['pmt_rows_data'][$index][$dataKey];
        }

        return null;
    }

    /**
     * Perform a status query to maksuturva's server using the current order
     * <code>
     * array(
     * 		"pmtq_action",
     * 		"pmtq_version",
     * 		"pmtq_sellerid",
     * 		"pmtq_id",
     * 		"pmtq_resptype",
     * 		"pmqt_return",
     * 		"pmtq_hashversion",
     * 		"pmtq_keygeneration"
     * );
     * </code>
     * The return data is an array if the order is successfully organized;
     * Otherwise, possible situations of errors:
     * 1) Exceptions in case of not having curl in PHP - exception
     * 2) Network problems (cannot connect, etc) - exception
     * 3) Invalid returned data (hash or consistency) - return false
     *
     * @param array $data Configuration values to be used
     * @return array|boolean
     */
    public function statusQuery($plugin, $data = array())
    {
    	// curl is mandatory
    	if (!function_exists("curl_init")) {
    		throw new MaksuturvaGatewayException(array("cURL is needed in order to communicate with the maksuturva's server. Check your PHP installation."), self::EXCEPTION_CODE_PHP_CURL_NOT_INSTALLED);
    	}

    	$defaultFields = array(
    		"pmtq_action" => "PAYMENT_STATUS_QUERY",
    		"pmtq_version" => "0005",
    		"pmtq_sellerid" => $this->_formData["pmt_sellerid"],
    		"pmtq_id" => $this->_formData["pmt_id"],
    		"pmtq_resptype" => "XML",
    		//"pmqt_return" => "",	// optional
    	 	"pmtq_hashversion" => $this->_formData["pmt_hashversion"],
    		"pmtq_keygeneration" => $this->_formData['pmt_keygeneration']
    	);

    	// overrides with user-defined fields
    	$this->_statusQueryData = array_merge($defaultFields, $data);
        
        // hash calculation
    	$hashFields = array(
    		"pmtq_action",
    		"pmtq_version",
    		"pmtq_sellerid",
    		"pmtq_id"
    	);
    	$hashString = '';
        foreach ($hashFields as $hashField) {
        	$hashString .= $this->_statusQueryData[$hashField] . '&';
        }
        $hashString .= $this->_secretKey . '&';
        // last step: the hash is placed correctly
        $this->_statusQueryData["pmtq_hash"] = strtoupper(hash($this->_hashAlgoDefined, $hashString));

        // now the request is made to maksuturva
        $request = curl_init($this->_statusQueryBaseUrl);
		curl_setopt($request, CURLOPT_HEADER, 0);
		curl_setopt($request, CURLOPT_FRESH_CONNECT, 1);
		curl_setopt($request, CURLOPT_FORBID_REUSE, 1);
		curl_setopt($request, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($request, CURLOPT_POST, 1);
        curl_setopt($request, CURLOPT_SSL_VERIFYPEER, 0); // Ignoring certificate verification
        curl_setopt($request, CURLOPT_CONNECTTIMEOUT, 120);
        curl_setopt($request, CURLOPT_POSTFIELDS, $this->_statusQueryData);

        $res = curl_exec($request);
        if ($res === false) {
        	throw new MaksuturvaGatewayException(array("Failed to communicate with maksuturva. Please check the network connection."));
        }
        curl_close($request);

        // we will not rely on xml parsing - instead, the fields are going to be collected by means of preg_match
        $parsedResponse = array();
        $responseFields = array(
        	"pmtq_action", "pmtq_version", "pmtq_sellerid", "pmtq_id",
        	"pmtq_amount", "pmtq_returncode", "pmtq_returntext", "pmtq_trackingcodes",
        	"pmtq_sellercosts", "pmtq_paymentmethod", "pmtq_escrow",
            "pmtq_certification", "pmtq_externalcode1", "pmtq_externalcode2", "pmtq_externaltext",
            "pmtq_paymentstarttime", "pmtq_paymentdate", "pmtq_hash"
        	//"pmtq_buyername", "pmtq_buyeraddress1", "pmtq_buyeraddress2",
        	//"pmtq_buyerpostalcode", "pmtq_buyercity"
        );
        foreach ($responseFields as $responseField) {
        	preg_match("/<$responseField>(.*)?<\/$responseField>/i", $res, $match);
        	if (count($match) == 2) {
        		$parsedResponse[$responseField] = $match[1];
        	}
        }

        /*if($plugin->debug == 'yes') {
            $plugin->log->add($plugin->id, "    response:".$res);
        }*/

		// do not provide a response which is not valid
		if (!$this->_verifyStatusQueryResponse($parsedResponse)) {
			throw new MaksuturvaGatewayException(array("The authenticity of the answer could't be verified. Hashes didn't match."), self::EXCEPTION_CODE_HASHES_DONT_MATCH);
		}

        // return the response - verified
        return $parsedResponse;
    }

    /**
     * Internal method to validate the consistency of maksuturva
     * responses for a given status query.
     * @param array $data
     * @return boolean
     */
    private function _verifyStatusQueryResponse($data)
    {
    	$hashFields = array(
			"pmtq_action",
			"pmtq_version",
			"pmtq_sellerid",
			"pmtq_id",
			"pmtq_amount",
			"pmtq_returncode",
			"pmtq_returntext",
    		"pmtq_sellercosts",
			"pmtq_paymentmethod",
			"pmtq_escrow",
            "pmtq_certification",
            "pmtq_paymentdate"
    	);

    	$optionalFields = array(
    		"pmtq_sellercosts",
			"pmtq_paymentmethod",
			"pmtq_escrow",
            "pmtq_certification",
            "pmtq_paymentdate"
    	);

    	$hashString = "";
    	foreach ($hashFields as $hashField) {
    		if (!isset($data[$hashField]) && !in_array($hashField, $optionalFields)) {
    			return false;
    		// optional fields
    		} else if (!isset($data[$hashField])) {
    			continue;
    		}
    		// test the vality of data as well, when the field exists
    		if  (isset($this->_statusQueryData[$hashField]) &&
    			($data[$hashField] != $this->_statusQueryData[$hashField])) {
    			return false;
    		}
    		$hashString .= $data[$hashField] . "&";
    	}
    	$hashString .= $this->_secretKey . '&';

    	$calcHash = strtoupper(hash($this->_hashAlgoDefined, $hashString));
    	if ($calcHash != $data["pmtq_hash"]) {
    		return false;
    	}

    	return true;
    }

    /**
     * Converts the given string to UTF-8
     * @param string $string_input
     * @param string $encoding
     * @return string
     */
    private function convert_encoding($string_input, $encoding)
    {
        return mb_convert_encoding($string_input, $encoding);
    }
    
    /**
     * Calculate the payment url base on the admin module configuration
     * of the base url
     * @param string $baseUrl
     * @return string
     */
    static function getPaymentUrl($baseUrl = 'https://www.maksuturva.fi')
    {
        return $baseUrl . '/NewPaymentExtended.pmt';
    }
    
	/**
     * Calculate the status query url base on the admin module configuration
     * of the base url
     * @param string $baseUrl
     * @return string
     */
    static function getStatusQueryUrl($baseUrl = 'https://www.maksuturva.fi')
    {
        return $baseUrl . '/PaymentStatusQuery.pmt';
    }
    
    /**
     * Given the private var $_fieldLength, parses all the fields,
     * 	trimming them as needed. If a required field is missing or with length below
     * 	required, throws an exception
     */
    private function filterFieldsLength()
    {
    	// clone values
    	$originalData = array();
    	foreach ($this->_formData as $key => $data) {
    		$originalData[$key] = $data;
    	}
    	$this->_originalFormData = $originalData;
    	
    	$changes = FALSE;
    	foreach ($this->_formData as $key => $data) {
    		// mandatory
    		if ((array_key_exists($key, $this->_fieldLength) && in_array($key, $this->_compulsoryData)) ||
    			array_key_exists($key, $this->_fieldLength) && in_array($key, $this->_rowCompulsoryData)) {
    			if (mb_strlen($data) < $this->_fieldLength[$key][0]) {
    				throw new MaksuturvaGatewayException(array("Field " . $key . " should be at least " . $this->_fieldLength[$key][0] . " characters long"));
    			} else if (mb_strlen($data) > $this->_fieldLength[$key][1]) {
    				// auto trim
    				$this->_formData[$key] = mb_substr($data, 0, $this->_fieldLength[$key][1]);
					$this->_formData[$key] = mb_convert_encoding($this->_formData[$key], $this->_charset, $this->_charset);
    				$changes = true;
    			}
    			continue;
    		// optional
    		} else if ((array_key_exists($key, $this->_fieldLength) && in_array($key, $this->_optionalData) && mb_strlen($data)) ||
    			(array_key_exists($key, $this->_fieldLength) && in_array($key, $this->_rowOptionalData) && mb_strlen($data))) {
    			if (mb_strlen($data) < $this->_fieldLength[$key][0]) {
    				throw new MaksuturvaGatewayException(array("Field " . $key . " should be at least " . $this->_fieldLength[$key][0] . " characters long"));
    			} else if (mb_strlen($data) > $this->_fieldLength[$key][1]) {
    				// auto trim
    				$this->_formData[$key] = mb_substr($data, 0, $this->_fieldLength[$key][1]);
					$this->_formData[$key] = mb_convert_encoding($this->_formData[$key], $this->_charset, $this->_charset);
    				$changes = true;
    			}
    			continue;
    		}
    	}
    	
    	// now, the product rows
    	foreach ($this->_formData["pmt_rows_data"] as $i => $product) {
		// Putting desc or title to not be blank
		if (array_key_exists('pmt_row_name', $product) && array_key_exists('pmt_row_desc', $product)){
			if (!trim($product['pmt_row_name'])){
				$this->_formData["pmt_rows_data"][$i]['pmt_row_name'] = $product['pmt_row_name'] = $product['pmt_row_desc'];
			} else if (!trim($product['pmt_row_desc'])){
				$this->_formData["pmt_rows_data"][$i]['pmt_row_desc'] = $product['pmt_row_desc'] = $product['pmt_row_name'];
			}
			
		}

    		foreach ($product as $key => $data) {
	    		// mandatory
	    		if ((array_key_exists($key, $this->_fieldLength) && in_array($key, $this->_compulsoryData)) ||
	    			array_key_exists($key, $this->_fieldLength) && in_array($key, $this->_rowCompulsoryData)) {
	    			if (mb_strlen($data) < $this->_fieldLength[$key][0]) {
	    				throw new MaksuturvaGatewayException(array("Field " . $key . " should be at least " . $this->_fieldLength[$key][0] . " characters long"));
	    			} else if (mb_strlen($data) > $this->_fieldLength[$key][1]) {
	    				// auto trim
	    				$this->_formData["pmt_rows_data"][$i][$key] = mb_substr($data, 0, $this->_fieldLength[$key][1]);
						$this->_formData["pmt_rows_data"][$i][$key] = mb_convert_encoding($this->_formData["pmt_rows_data"][$i][$key], $this->_charset, $this->_charset);
	    				$changes = true;
	    			}
	    			continue;
	    		// optional
	    		} else if ((array_key_exists($key, $this->_fieldLength) && in_array($key, $this->_optionalData) && mb_strlen($data)) ||
	    			(array_key_exists($key, $this->_fieldLength) && in_array($key, $this->_rowOptionalData) && mb_strlen($data))) {
	    			if (mb_strlen($data) < $this->_fieldLength[$key][0]) {
	    				throw new MaksuturvaGatewayException(array("Field " . $key . " should be at least " . $this->_fieldLength[$key][0] . " characters long"));
	    			} else if (mb_strlen($data) > $this->_fieldLength[$key][1]) {
	    				// auto trim
	    				$this->_formData["pmt_rows_data"][$i][$key] = mb_substr($data, 0, $this->_fieldLength[$key][1]);
						$this->_formData["pmt_rows_data"][$i][$key] = mb_convert_encoding($this->_formData["pmt_rows_data"][$i][$key], $this->_charset, $this->_charset);
	    				$changes = true;
	    			}
	    			continue;
	    		}
    		}
    	}
    	
    	return $changes;
    }
}

class MaksuturvaGatewayException extends Exception
{
    public function __construct($errors, $code = null)
    {
        $message = '';
        foreach ($errors as $error) {
            $message .= $error . ', ';
        }

        parent::__construct($message, $code);
    }
}
