<?php
/**
 * NGP Donation
 *
 * @author      Josh Lockhart <josh@newmediacampaigns.com>
 * @copyright   2012 New Media Campaigns
 * @link        http://www.newmediacampaigns.com
 * @version     1.0.0
 *
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
 * The first argument is your NGP credentials string. The second
 * argument is a boolean value that determines if the contributor
 * should be notified by email after his contribution is accepted.
 * The final argument is a key value array of Contributor, Contribution,
 * and Payment details. See the array below for valid keys.
 *
 * $d = new NgpDonation('credentials-string', false, array(
 *     'LastName' => 'Doe',
 *     'FirstName' => 'John',
 *     'Address1' => '100 Elm Street',
 *     'Zip' => '27514',
 *     'Cycle' => 2012,
 *     'Amount' => 10,
 *     'CreditCardNumber' => '4111111111111111',
 *     'ExpYear' => '13',
 *     'ExpMonth' => '02'
 * ));
 * if ( $d->save() ) {
 *     //Success
 * } else {
 *     if ( $d->hasErrors() ) {
 *         $errors = $d->getErrors(); //array, indicates errors with local data (e.g. missing required fields)
 *     } else if ( $d->hasFault() ) {
 *         $fault = $d->getFault(); //SoapFault Exception, indicates error communicating with SOAP API
 *     } else {
 *         $transactionDetails = $d->getResult(); //SimpleXMLElement, may indicate payment transaction failure
 *         $transactionStatus = $transactionDetails->VendorResult->Result; //int, status code
 *         $transactionMessage = $transactionDetails->VendorResult->Message; //string, status description
 *     }
 * }
 */
class NgpDonation {
    /**
     * @var string Provided by NGP
     */
    protected $credentials;

    /**
     * @var string Send email to contributor after donation accepted?
     */
    protected $sendEmail;

    /**
     * @var array[String] Case sensitive!
     */
    protected $allFields;

    /**
     * @var array[String] Case sensitive!
     */
    protected $contributorFields;

    /**
     * @var array[String] Case sensitive!
     */
    protected $contributionFields;

    /**
     * @var array[String] Case sensitive!
     */
    protected $paymentFields;

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
     * @param   string  $credentials    Your NGP encrypted credentials string
     * @param   bool    $sendEmail      Notify contributor after donation accepted?
     * @param   array   $data           Key-value array of field names and values
     * @return  void
     */
    public function __construct( $credentials, $sendEmail = false, $data = array() ) {
        $this->client = new SoapClient('https://services.myngp.com/ngponlineservices/onlinecontribservice.asmx?wsdl');
        $this->credentials = $credentials;
        $this->sendEmail = $sendEmail;
        $this->contributorFields = array(
            'LastName' => '', //REQUIRED
            'FirstName' => '', //REQUIRED
            'MiddleName' => '',
            'Prefix' => '',
            'Suffix' => '',
            'Address1' => '', //REQUIRED
            'Address2' => '',
            'Address3' => '',
            'City' => '',
            'State' => '',
            'Zip' => '', //REQUIRED
            'Salutation' => '',
            'Email' => '',
            'HomePhone' => '',
            'WorkPhone' => '',
            'WorkExtension' => '',
            'FaxPhone' => '',
            'Employer' => '',
            'Occupation' => '',
            'OptIn' => true, //bool
            'MainType' => 'I',
            'Organization' => '',
        );
        $this->contributionFields = array(
            'Cycle' => null, //int; REQUIRED; election year the donation is for
            'Member' => '',
            'Attribution' => '',
            'Source' => '',
            'Period' => 'G',
            'RecurringContrib' => false, //bool
            'RecurringContribNote' => '',
            'Amount' => 0.0, //decimal; REQUIRED
            'Account' => '',
            'Attend' => '',
            'RecurringPeriod' => 'MONT', //frequency on which total recurring contributions will be processed (MONT, WEEK, BIWK, FRWK, QTER, SMYR, YEAR)
            'RecurringTerm' => 1, //int; number of total recurring contributions (1-24)
        );
        $this->paymentFields = array(
            'CreditCardNumber' => '', //REQUIRED
            'ExpYear' => null, //int; REQUIRED
            'ExpMonth' => null, //int; REQUIRED
            'CVV' => ''
        );
        $this->allFields = array_merge(
            $this->contributorFields,
            $this->contributionFields,
            $this->paymentFields,
            $data
        );
        $this->requiredFields = array(
            'FirstName',
            'LastName',
            'Address1',
            'Zip',
            'Cycle',
            'Amount',
            'CreditCardNumber',
            'ExpYear',
            'ExpMonth'
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
        $args = array(
            'credentials' => $this->credentials,
            'data' => $this->generateXml(),
            'sendEmail' => $this->sendEmail ? 'true' : 'false'
        );
        try {
            $res = $this->client->PostVerisignTransaction($args);
            $this->result = new SimpleXMLElement($res->PostVerisignTransactionResult);
            if($this->result->Message=='An unexpected error has occurred.') { return false; } else {
                return (int)$this->result->VendorResult->Result === 0;
            }
        } catch ( SoapFault $e ) {
            $this->fault = $e;
            return false;
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
        foreach( $this->requiredFields as $field ) {
            if ( !isset($this->allFields[$field]) || empty($this->allFields[$field]) ) {
                $this->errors[] = "$field is required";
            }
        }
		
		//Check recurring period
		if ( !empty($this->allFields['RecurringPeriod']) && !in_array($this->allFields['RecurringPeriod'], array('MONT', 'WEEK', 'BIWK', 'FRWK', 'QTER', 'SMYR', 'YEAR')) ) {
			$this->errors[] = 'Invalid recurring period. Must be one of: MONT, WEEK, BIWK, FRWK, QTER, SMYR, YEAR.';
		}
		
		//Check recurring term
		if ( !empty($this->allFields['RecurringTerm']) ) {
			$rt = (int)$this->allFields['RecurringTerm'];
			if ( $rt < 1 || $rt > 24 ) {
				$this->errors[] = 'Invalid recurring term. Must be a number 1-24.';
			}
		}
		
		//Check ExpMonth format
		if ( !empty($this->allFields['ExpMonth']) ) {
			$m = $this->allFields['ExpMonth'];
			if ( !preg_match('#^(0[1-9]|1[012])$#', $m) ) {
				$this->errors[] = 'Invalid Expiration Month. Must be a two-digit number 01-12.';
			}
		}
		
		//Check ExpYear format
		if ( !empty($this->allFields['ExpYear']) ) {
			$y = $this->allFields['ExpYear'];
			if ( !preg_match('#^\d{2}$#', $y) ) {
				$this->errors[] = 'Invalid Expiration Year. Must be a two-digit number 00-99.';
			}
		}
		
		//Check donation cycle
		if ( !empty($this->allFields['Cycle']) ) {
			$c = $this->allFields['Cycle'];
			if ( !preg_match('#^(19|20)\d\d$#', $c) ) {
				$this->errors[] = 'Invalid cycle. Must be four-digit year.';
			}
		}

        //Check donation amount
        if ( isset($this->allFields['Amount']) ) {
            if ( (float)$this->allFields['Amount'] < 1.0 ) {
                $this->errors[] = 'Invalid contribution amount. Must be greater than or equal to 1.';
            }
        }

        return empty($this->errors);
    }

    /**
     * Generate XML payload
     * @return string
     */
    public function generateXml() {
        $xml = '<PostVerisignTransaction>';
        $xml .= '<ContactInfo>';
        foreach ( $this->contributorFields as $name => $defaultValue ) {
            if ( is_bool($this->allFields[$name]) ) {
                $this->allFields[$name] = $this->allFields[$name] ? 'true' : 'false';
            }
            if ( !empty($this->allFields[$name]) ) {
                $xml .= sprintf('<%s>%s</%s>', $name, $this->allFields[$name], $name);
            } else {
                $xml .= sprintf('<%s/>', $name);
            }
        }
        $xml .= '</ContactInfo>';
        $xml .= '<ContributionInfo>';
        foreach ( $this->contributionFields as $name => $defaultValue ) {
            if ( $name === 'RecurringTerm' || $name === 'RecurringPeriod' ) {
                if ( empty($this->allFields['RecurringContrib']) || $this->allFields['RecurringContrib'] === 'false' ) {
                    continue;
                }
            }
            if ( is_bool($this->allFields[$name]) ) {
                $this->allFields[$name] = $this->allFields[$name] ? 'true' : 'false';
            }
            if ( !empty($this->allFields[$name]) ) {
                $xml .= sprintf('<%s>%s</%s>', $name, $this->allFields[$name], $name);
            } else {
                $xml .= sprintf('<%s/>', $name);
            }
        }
        $xml .= '</ContributionInfo>';
        $xml .= '<VerisignInfo>';
        foreach ( $this->paymentFields as $name => $defaultValue ) {
            if ( is_bool($this->allFields[$name]) ) {
                $this->allFields[$name] = $this->allFields[$name] ? 'true' : 'false';
            }
            if ( !empty($this->allFields[$name]) ) {
                $xml .= sprintf('<%s>%s</%s>', $name, $this->allFields[$name], $name);
            } else {
                $xml .= sprintf('<%s/>', $name);
            }
        }
        $xml .= '</VerisignInfo>';
        $xml .= '</PostVerisignTransaction>';
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
