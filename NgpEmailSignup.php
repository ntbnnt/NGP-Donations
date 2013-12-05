<?php
/* 
 * MIT LICENSE
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 * LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 * USAGE:
 *
 * The first argument is the configuration array, including your NGP credentials string.
 * The final argument is a key value array of constituent information.
 * See the array below for valid keys.
 *
 * $d = new NgpEmailSignup(array(
 *         'credentials'=>'[NGP-generated Credentials String] (CWP or COO string)',
 *         'userID'=>'[User ID] (only required when using COO api Credential String)',
 *         'campaignID'=>'[Campaign ID] (only required when using COO api Credential String)'
 *     ),
 *     array(
 *         'lastName' => 'Doe',
 *         'firstName' => 'John',
 *         'email' => 'johndoe@yahoo.com',
 *         'zip' => '27514',
 *     )
 * );
 * if ( $d->save() ) {
 *     //Success
 * } else {
 *     if ( $d->hasErrors() ) {
 *         $errors = $d->getErrors(); //array, indicates errors with local data (e.g. missing required fields)
 *     } else if ( $d->hasFault() ) {
 *         $fault = $d->getFault(); //SoapFault Exception, indicates error communicating with SOAP API
 *     } else {
 *         $signupDetails = $d->getResult(); //SimpleXMLElement, may indicate payment transaction failure
 *         $signupStatus = $transactionDetails->VendorResult->Result; //int, status code
 *         $signupMessage = $transactionDetails->VendorResult->Message; //string, status description
 *     }
 * }
 */
class NgpEmailSignup {
    /**
     * @var string Provided by NGP
     */
    protected $credentialString;

    /**
     * @var string Campaign ID
     */
    protected $campaignID;

    /**
     * @var array[Int] User ID
     */
    protected $userID;

    /**
     * @var array[String] Case sensitive!
     */
    protected $constituentFields;

    /**
     * @var array[String] Case sensitive!
     */
    protected $requiredFields;

    /**
     * @var array[String]
     */
    protected $errors;

    /**
     * @var SoapClient
     */
    protected $client;

    /**
     * @var SoapFault
     */
    protected $fault;

    /**
     * @var SimpleXMLElement
     */
    protected $result;

    /**
     * Constructor
     *
     * @param   string  $configuration    Key-value array of signup configuration names and values:
     *                                    NGP-encrypted string (credentials), Main Code (mainCode) and Campaign ID (campaignID)
     * @param   array   $data           Key-value array of field names and values
     * @return  void
     */
    public function __construct( $configuration, $data = array() ) {
        if(is_array($configuration) && count($configuration)==3) {
            if( !class_exists( 'WP_Http' ) )
                include_once( ABSPATH . WPINC. '/class-http.php' );
            $this->client = new WP_Http();
            $this->credentialString = $configuration['credentials'];
            $this->userID = $configuration['userID'];
            $this->campaignID = $configuration['campaignID'];
        } else if(is_array($configuration) && count($configuration)==1) {
            $this->client = new SoapClient('https://services.myngp.com/ngponlineservices/EmailSignUpService.asmx?wsdl');
            $this->credentialString = $configuration['credentials'];
        }
        // http://www.myngp.com/ngpapi/transactions/Contact/Contact.xsd
        $this->constituentFields = array(
            'lastName' => '', //REQUIRED
            'firstName' => '', //REQUIRED
            'email' => '', //REQUIRED
            'zip' => '', //REQUIRED
            'phone'
        );
        $this->allFields = array_merge(
            $this->constituentFields,
            $data
        );
        $this->requiredFields = array(
            'lastName',
            'firstName',
            'email',
            'zip'
        );
    }

    /**
     * Set required fields
     * @param array[String] Case sensitive numeric array of field names
     * @return void
     */
    public function setRequiredFields( $fields ) {
        $this->requiredFields = $fields;
    }

    /**
     * Add required fields
     * @param array[String] Case sensitive numeric array of field names
     * @return void
     */
    public function addRequiredFields( $fields ) {
        $this->requiredFields = array_merge($this->requiredFields, $fields);
    }

    /**
     * Save email signup
     *
     * Returns (int)0 on success, (bool)false on failure. If this returns an integer other
     * than zero, inspect the transaction result with `getResult()`. If this returns false,
     * you should check for data errors with `getErrors()` or an API fault with `getFault()`.
     *
     * @return bool
     */
    public function save() {
        if ( $this->isValid() === false ) {
            return false;
        }
        // Check for All Three
        if($this->userID && $this->campaignID) {
            $args = array(
                'RequestXML' => $this->generateNGPAPIXml(),
                'transType' => 'ContactSetICampaigns',
                'credentialString' => $this->credentialString
            );
            // WP_Http
            $headers = array(
                'User-agent' => 'RevMsg Wordpress Plugin (support@revmsg.com)',
            );
            try {
                $result = $this->client->request('http://www.myngp.com/ngpapi/APIService.asmx/processRequestWithCreds', array(
                    'method' => 'POST',
                    'body' => $args,
                    'headers' => $headers
                ));
                // Need to find out what the hell that's returning
                if($result['response']['code']==200)
                    return true;
                else {
                    if($result['response']['code']==500) {
                        echo 'The NGP COO API is not configured properly.';
                    }
                    return false;
                }
            } catch ( SoapFault $e ) {
                $this->fault = $e;
                return false;
            }
        } else {
            $array = $this->generateArr();
            try {
                $res = $this->client->EmailSignUp($array);
                if($res->EmailSignUpResult===true)
                    return true;
                else
                    return false;
            } catch ( SoapFault $e ) {
                $this->fault = $e;
                return false;
            }
        }
    }

    /**
     * Get transaction result details
     * @return SimpleXMLElement
     */
    public function getResult() {
        return $this->result;
    }

    /**
     * Is transaction data valid?
     * @return bool
     */
    public function isValid() {
        //Check requiredness
        if($this->userID && $this->campaignID) {
            if(!isset($this->allFields['phone']) && !isset($this->allFields['email'])) {
                $this->errors[] = "You must provide either an email address or phone number.";
            }
        } else {
            foreach( $this->requiredFields as $field ) {
                if ( !isset($this->allFields[$field]) || empty($this->allFields[$field]) ) {
                    $this->errors[] = $field." is required";
                }
            }
        }
        return empty($this->errors);
    }

    /**
     * Generate XML payload
     * @return string
     */
    public function generateArr() {
        $ret_array = array('credentials' => $this->credentialString);
        foreach ( $this->constituentFields as $name => $defaultValue ) {
            if( !empty($this->allFields[$name]) ) {
                $ret_array[$name] = $this->allFields[$name];
            }
        }
        $ret_array['optIn'] = true;
        return $ret_array;
    }

    /**
     * Generate XML payload
     * @return string
     */
    public function generateNGPAPIXml() {
        $xml = '<ngp:contactSetICampaigns xmlns:ngp="http://www.ngpsoftware.com/ngpapi" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">';
        $xml .= '<campaignID>'.$this->campaignID.'</campaignID>'; // $this->campaignID
        $xml .= '<userID>'.$this->userID.'</userID>'; // $this->userID
        $xml .= '<contact xsi:type="ngp:Contact">';
        $xml .= ( isset( $this->allFields['lastName'] ) && !empty( $this->allFields['lastName'] ) ) ? "<lastName>{".$this->allFields['lastName']."}</lastName>" : '<lastName />';
        $xml .= ( isset( $this->allFields['firstName'] ) && !empty( $this->allFields['firstName'] ) ) ? "<firstName>{".$this->allFields['firstName']."}</firstName>" : '<firstName />';
        $xml .= ( isset( $this->allFields['Zip'] ) && !empty( $this->allFields['Zip'] ) ) ? "<zip>{".$this->allFields['zip']."}</zip>" : '<zip />';
        $xml .= ( isset( $this->allFields['email'] ) && !empty( $this->allFields['email'] ) ) ? "<email>{".$this->allFields['email']."}</email>" : '<email />';
        if(isset($this->allFields['phone']) && !empty($this->allFields['phone'])) {
            $the_phone = $this->allFields['phone'];
            $the_phone = str_replace('+', '', $the_phone);
            $xml .= '<mobilePhone>'.$the_phone.'</mobilePhone>';
            $xml .= '<smsOptIn>1</smsOptIn>';
        }
        $xml .= "</contact>";
        $xml .= "</ngp:contactSetICampaigns>";
        return $xml;
    }

    /**
     * Get errors
     * return array[String]|null
     */
    public function getErrors() {
        return $this->errors;
    }

    /**
     * Has errors?
     * @return bool
     */
    public function hasErrors() {
        return !empty($this->errors);
    }

    /**
     * Get last fault
     * @return SoapFault|null
     */
    public function getFault() {
        return $this->fault;
    }

    /**
     * Has fault?
     * @return bool
     */
    public function hasFault() {
        return !empty($this->fault);
    }
}
