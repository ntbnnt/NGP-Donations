<?php

class NGPDonationFrontend {
    
    var $state_list = array(
        'Alabama'=>'AL',
        'Alaska'=>'AK',
        'Arizona'=>'AZ',
        'Arkansas'=>'AR',
        'California'=>'CA',
        'Colorado'=>'CO',
        'Connecticut'=>'CT',
        'Delaware'=>'DE',
        'District Of Columbia'=>'DC',
        'Florida'=>'FL',
        'Georgia'=>'GA',
        'Hawaii'=>'HI',
        'Idaho'=>'ID',
        'Illinois'=>'IL',
        'Indiana'=>'IN',
        'Iowa'=>'IA',
        'Kansas'=>'KS',
        'Kentucky'=>'KY',
        'Louisiana'=>'LA',
        'Maine'=>'ME',
        'Maryland'=>'MD',
        'Massachusetts'=>'MA',
        'Michigan'=>'MI',
        'Minnesota'=>'MN',
        'Mississippi'=>'MS',
        'Missouri'=>'MO',
        'Montana'=>'MT',
        'Nebraska'=>'NE',
        'Nevada'=>'NV',
        'New Hampshire'=>'NH',
        'New Jersey'=>'NJ',
        'New Mexico'=>'NM',
        'New York'=>'NY',
        'North Carolina'=>'NC',
        'North Dakota'=>'ND',
        'Ohio'=>'OH',
        'Oklahoma'=>'OK',
        'Oregon'=>'OR',
        'Pennsylvania'=>'PA',
        'Rhode Island'=>'RI',
        'South Carolina'=>'SC',
        'South Dakota'=>'SD',
        'Tennessee'=>'TN',
        'Texas'=>'TX',
        'Utah'=>'UT',
        'Vermont'=>'VT',
        'Virginia'=>'VA',
        'Washington'=>'WA',
        'West Virginia'=>'WV',
        'Wisconsin'=>'WI',
        'Wyoming'=>'WY'
    );
    
    // The API Key for NGP (should be a superlong string.)
    var $api_key = '';
    
    // Is set to true when there's a problem communicating
    // with the NGP API or NGP API returns an error for an
    // attempted contribution
    var $ngp_error = false;
    
    // Is set to the url specified in the WP General Settings
    // This is the Domain that the SSL cert for your server is keyed to.
    // Example: donate.yourdomain.com, yourdomain.com, or www.yourdomain.com
    // OPTIONAL
    var $url_specified = '';
    
    // Set to true when errors are found in the form itself
    // (Set before we even try to send it to NGP)
    var $any_errors = false;
    
    // Set to try when we have processed the form during the current run.
    var $been_processed = false;
    
    // The default redirect URL for the thank-you page.
    var $redirect_url = '/thank-you-for-your-contribution';
    
    // Populated with the NGP fieldsets
    var $fieldsets = array();
    
    // Support phone for error messages.
    var $support_phone = '';
    
    /*
     * Construct
     * Here we populate many of the above vars from the WP options.
     */
    function __construct() {
        $this->api_key = get_option('ngp_api_key', '');
        $this->url_specified = get_option('ngp_secure_url', '');
        $this->support_phone = get_option('ngp_support_phone', '');
        
        $this->redirect_url = get_option('ngp_thanks_url', '/thank-you-for-your-contribution');
        
        $this->fieldsets = array(
            'Personal Information' => array(
                array(
                    'type' => 'text',
                    'slug' => 'FullName',
                    'required' => 'true',
                    'label' => 'Name',
                ),
                array(
                    'type' => 'text',
                    'slug' => 'Email',
                    'required' => 'false',
                    'label' => 'Email Address'
                ),
                array(
                    'type' => 'text',
                    'slug' => 'Address1',
                    'required' => 'true',
                    'label' => 'Street Address'
                ),
                // array(
                //     'type' => 'text',
                //     'slug' => 'Address2',
                //     'required' => 'false',
                //     'label' => 'Address (Cont.)'
                //     'show_label' => 'false'
                // ),
                array(
                    'type' => 'hidden',
                    'slug' => 'City',
                    // 'required' => 'true',
                    // 'label' => 'City'
                ),
                array(
                    'type' => 'hidden',
                    'slug' => 'State',
                    // 'required' => 'true',
                    // 'label' => 'State',
                    // 'options' => array('AK'=>'AK','AL'=>'AL','AR'=>'AR','AZ'=>'AZ','CA'=>'CA','CO'=>'CO','CT'=>'CT','DC'=>'DC','DE'=>'DE','FL'=>'FL','GA'=>'GA','HI'=>'HI','IA'=>'IA','ID'=>'ID','IL'=>'IL','IN'=>'IN','KS'=>'KS','KY'=>'KY','LA'=>'LA','MA'=>'MA','MD'=>'MD','ME'=>'ME','MI'=>'MI','MN'=>'MN','MO'=>'MO','MS'=>'MS','MT'=>'MT','NC'=>'NC','ND'=>'ND','NE'=>'NE','NH'=>'NH','NJ'=>'NJ','NM'=>'NM','NV'=>'NV','NY'=>'NY','OH'=>'OH','OK'=>'OK','OR'=>'OR','PA'=>'PA','RI'=>'RI','SC'=>'SC','SD'=>'SD','TN'=>'TN','TX'=>'TX','UT'=>'UT','VA'=>'VA','VT'=>'VT','WA'=>'WA','WI'=>'WI','WV'=>'WV','WY'=>'WY')
                ),
                array(
                    'type' => 'text',
                    'slug' => 'Zip',
                    'required' => 'true',
                    'label' => 'Zip Code'
                )
            ),
            'Employment' => array(
                'html_intro' => '<p>Federal law requires us to use our best efforts to collect and report the name, mailing address, occupation, and employer of individuals whose contributions exceed $200 in an election cycle.</p>',
                array(
                    'type' => 'text',
                    'slug' => 'Employer',
                    'required' => 'true',
                    'label' => 'Employer'
                ),
                array(
                    'type' => 'text',
                    'slug' => 'Occupation',
                    'required' => 'true',
                    'label' => 'Occupation'
                )
            ),
            'Credit card' => array(
                'html_intro' => '<p id="accepted-cards" style="margin: 0pt 0pt -5px; background: url(/wp-content/plugins/'.plugin_basename(dirname(__FILE__)).'/credit-card-logos.png) no-repeat scroll 0% 0% transparent; text-indent: -900em; width: 211px; height: 34px;">We accept Visa, Mastercard, American Express and Discover cards.</p>',
                array(
                    'type' => 'radio',
                    'slug' => 'Amount',
                    'required' => 'true',
                    'label' => 'Amount',
                    'options' => array(
                        '10.00' => '$10',
                        '25.00' => '$25',
                        '50.00' => '$50',
                        '100.00' => '$100',
                        '500.00' => '$500',
                        '1000.00' => '$1,000',
                        '2600.00' => '$2,600',
                        'custom' => '<label for="ngp_custom_dollar_amt">Other:</label> $<input type="text" name="custom_dollar_amt" %s class="ngp_custom_dollar_amt" /> <small>(USD)</small>'
                    )
                ),
                array(
                    'type' => 'text',
                    'slug' => 'CreditCardNumber',
                    'required' => 'true',
                    'label' => 'Credit Card Number'
                ),
                array(
                    'type' => 'select',
                    'slug' => 'ExpMonth',
                    'required' => 'true',
                    'label' => 'Expiration Date',
                    'show_label' => 'true',
                    'show_pre_div' => 'true',
                    'show_post_div' => 'false',
                    'options' => array(
                        '01' => '1 - January',
                        '02' => '2 - February',
                        '03' => '3 - March',
                        '04' => '4 - April',
                        '05' => '5 - May',
                        '06' => '6 - June',
                        '07' => '7 - July',
                        '08' => '8 - August',
                        '09' => '9 - September',
                        '10' => '10 - October',
                        '11' => '11 - November',
                        '12' => '12 - December'
                    )
                ),
                array(
                    'type' => 'select',
                    'slug' => 'ExpYear',
                    'required' => 'true',
                    'label' => 'Expiration Year',
                    'show_label' => 'false',
                    'show_placeholder' => 'false',
                    'show_pre_div' => 'false',
                    'options' => array()
                ),
            )
            // array(
            //     'type' => 'checkbox',
            //     'slug' => 'RecurringContrib',
            //     'required' => 'true',
            //     'label' => 'Recurring Contribution?'
            //     'show_label' => 'false'
            //     'show_placeholder' => 'false'
            // )
        );
        
        /*
         * Set the Year options for CC expiration to include this year
         * and 19 more years.
         */
        $y = (int)date('Y');
        $y_short = (int)date('y');
        while($y < (int)date('Y', strtotime('+19 years'))) {
            $this->fieldsets['Credit card'][3]['options'][$y_short] = $y;
            $y+=1;
            $y_short+=1;
        }
        
    }
    
    /*
     * Check Security
     *
     * This function not only checks that the methods are running under SSL,
     * but it also makes sure that the API Key has been configured.
     * If not under SSL and not on a .dev TLD, it redirects first to the URL
     * specified in the WP General Options panel, if not that, then to the
     * same URL as the page attempted to load.
     */
    function check_security() {
        global $wpdb, $ngp;
        $server_url_parts = explode('.', $_SERVER["SERVER_NAME"]);
        if(!empty($this->url_specified) && $server_url_parts[count($server_url_parts)-1]!=='dev') {
            $url_parts = $this->url_specified;
        } else {
            $url_parts = $server_url_parts;
        }
        if($_SERVER["HTTPS"] != "on" && $url_parts[count($url_parts)-1]!=='dev' && !isset($_GET['devtest'])) {
            if(!empty($this->url_specified)) {
                $newurl = "https://" . $this->url_specified . $_SERVER["REQUEST_URI"];
            } else {
                $newurl = "https://" . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
            }
            header("Location: $newurl");
            exit();
        }
        
        if(empty($this->api_key)) {
            return 'Not currently configured.';
            exit();
        }
        return true;
    }
    
    function trim_value(&$value, $chars=null) {
        if($chars)
            $value = trim($value, $chars);
        else
            $value = trim($value);
    }
    
    /* Submits and reroutes donation form */
    function process_form() {
        global $wpdb, $ngp;
        if($this->been_processed) { return false; exit(); }
        
        $check_security = $this->check_security();
        if($check_security!==true) {
            return false;
            exit();
        }
    
        if(!empty($_POST)) {
            if(wp_verify_nonce($_POST['ngp_add'], 'ngp_nonce_field')) // && $_POST['ngp_form_id']==$id
            {
                foreach($this->fieldsets as $fkey => $fieldset) {
                    foreach($fieldset as $key => $field) {
                        if($key!=='html_intro') {
                            if($field['required']=='true' && (!isset($_POST[$field['slug']]) || empty($_POST[$field['slug']]))) {
                                if($field['slug']=='Amount' && !empty($_POST['custom_dollar_amt'])) {
                                    // Do nothing
                                } else {
                                    $this->fieldsets[$fkey][$key]['error'] = true;
                                    $this->any_errors = true;
                                }
                            }
                        }
                    }
                }
                
                if(!$this->any_errors) {
                    // Split Name
                    $namePrefixes = array('Dr', 'Hon', 'Mr', 'Mrs', 'Ms', 'Prof', 'Rep', 'Rev');
                    $nameSuffixes = array(
                        'Jr'        =>    'Jr',
                        'Junior'    =>    'Jr',
                        'Senior'    =>    'Sr',
                        'Sr'        =>    'Sr',
                        'I'            =>    'I',
                        'i'            =>    'I',
                        'ii'        =>    'II',
                        'II'        =>    'II',
                        'iii'        =>    'III',
                        'III'        =>    'III',
                        'iv'        =>    'IV',
                        'IV'        =>    'IV',
                        'v'            =>    'V',
                        'V'            =>    'V',
                        'VI'        =>    'VI',
                        'vii'        =>    'VII',
                        'VII'        =>    'VII',
                        'viii'        =>    'VIII',
                        'VIII'        =>    'VIII'
                    );
                    $payment_data = $_POST;
                    if(isset($_POST['redirect_url']))
                        $this->redirect_url = $_POST['redirect_url'];
                        unset($payment_data['redirect_url']);
                    if(isset($_POST['FullName']) && !empty($_POST['FullName'])) {
                        // Split name
                        $names = explode(' ', $_POST['FullName']);
                        unset($payment_data['ngp_form_id']);
                        unset($payment_data['ngp_add']);
                        unset($payment_data['FullName']);
                        unset($payment_data['_wp_http_referer']);
                        
                        array_walk($names, function(&$value) {
                            $chars = "\t\n\r\0\x0B,.[]{};:\"'\x00..\x1F";
                            $value = trim($value, $chars);
                        });
                        if(count($names)==1) {
                            $payment_data['LastName'] = $names[0];
                        } else if(count($names)==2) {
                            $payment_data['FirstName'] = $names[0];
                            $payment_data['LastName'] = $names[1];
                        } else if(count($names)>2) {
                            // Check for Prefix
                            array_walk($namePrefixes, function($value, $key, &$the_names) {
                                if(strlen($the_names[0])==strlen($value) && stripos($the_names[0], $value)!==false && isset($the_names[0])) {
                                    $the_names['prefix'] = $value;
                                    unset($the_names[0]);
                                }
                            }, &$names);
                            
                            // Check for Suffix
                            array_walk($nameSuffixes, function($value, $key, &$the_names) {
                                $possible_suffix = null;
                                foreach($the_names as $k => $v) {
                                    if(is_int($k)) {
                                        $possible_skey = $k;
                                        $possible_suffix = $v;
                                    }
                                }
                                if(strlen($possible_suffix)==strlen($key) && stripos($possible_suffix, $key)!==false) {
                                    $the_names['suffix'] = $value;
                                    unset($the_names[$possible_skey]);
                                }
                            }, &$names);
                            
                            // Whatever is left over, set as FirstName, MiddleName, LastName
                            if(isset($names['prefix'])) {
                                if($names['prefix']=='Rep')
                                    $payment_data['Prefix'] = $names['prefix'];
                                else
                                    $payment_data['Prefix'] = $names['prefix'].'.';
                                unset($names['prefix']);
                            }
                            if(isset($names['suffix'])) {
                                $payment_data['Suffix'] = $names['suffix'];
                                unset($names['suffix']);
                            }
                            $names = array_merge($names);
                            if(count($names)==1) {
                                $payment_data['LastName'] = $names[0];
                            } else if(count($names)==2) {
                                $payment_data['FirstName'] = $names[0];
                                $payment_data['LastName'] = $names[1];
                            } else if(count($names)==3) {
                                $payment_data['FirstName'] = $names[0];
                                $payment_data['MiddleName'] = $names[1];
                                $payment_data['LastName'] = $names[2];
                            } else if(count($names)==4) {
                                $payment_data['FirstName'] = $names[0];
                                $payment_data['MiddleName'] = $names[1];
                                $payment_data['MiddleName'] .= ' '.$names[2];
                                $payment_data['LastName'] = $names[3];
                            } else {
                                // Otherwise, let's bail out but save everything
                                $payment_data['FirstName'] = $names[0];
                                foreach($names as $namekey => $name) {
                                    if($namekey==0) {
                                        $payment_data['FirstName'] = $name;
                                    } else {
                                        if(!isset($payment_data['LastName'])) {
                                            $payment_data['LastName'] = $name;
                                        } else {
                                            $payment_data['LastName'] .= ' '.$name;
                                        }
                                    }
                                }
                            }
                        }
                        
                        if((isset($_POST['City']) && empty($_POST['City'])) || (isset($_POST['State']) && empty($_POST['State']))) {
                            $result = wp_remote_get('http://zip.elevenbasetwo.com/'.$_POST['Zip']);
                            $result = json_decode($result['body']);
                            
                            if(isset($_POST['City']) && empty($_POST['City']) && isset($result->city)) {
                                $payment_data['City'] = ucwords(strtolower($result->city));
                            }
                            
                            if(isset($_POST['State']) && empty($_POST['State']) && isset($result->state)) {
                                $payment_data['State'] = $result->state;
                            }
                        }
                        
                        // setlocale(LC_MONETARY, 'en_US');
                        if(!empty($payment_data['custom_dollar_amt'])) {
                            $payment_data['Amount'] = number_format(str_replace('$', '', $payment_data['custom_dollar_amt']), 2, '.', '');
                        } else {
                            $payment_data['Amount'] = number_format($payment_data['Amount'], 2, '.', '');
                        }
                        unset($payment_data['custom_dollar_amt']);
                        $payment_data['Cycle'] = date('Y');
                        
                        require_once('NgpDonation.php');
                        $send_email  = (isset($payment_data['Email']) && !empty($payment_data['Email'])) ? true : false;
                        $donation = new NgpDonation($this->api_key, $send_email, $payment_data);
                        if($donation->save()) {
                            // Success!
                            // Redirect.
                            $_POST = array();
                            $this->been_processed = true;
                            // require_once(dirname(dirname(dirname(__FILE__))).'/wp-includes/pluggable.php');
                            header('Location: '.$this->redirect_url);
                            exit;
                        } else {
                            // Failure.
                            $this->ngp_error = true;
                        }
                    } else {
                        $field['Personal Information'][0]['error'] = true;
                    }
            
                }
            } else if(!empty($_POST) && isset($_POST['ngp_add']) && !wp_verify_nonce($_POST['ngp_add'], 'ngp_nonce_field')) {
                $this->ngp_error = true;
            }
            /* else if(!empty($_POST) && $_POST['ngp_form_id']!=$id) {
                $this->ngp_error = true;
            } */
            $this->been_processed = true;
        }
    }
    
    /**
     * Shows form used to donate
     */
    function show_form( $atts=null, $form=true ) {
        global $wpdb, $ngp;
    
        $check_security = $this->check_security();
    
        if($check_security!==true) {
            return false;
            exit();
        }
            
        extract( shortcode_atts( array(
            'amounts' => null,
            'source' => null,
            'thanks_url' => null,
            'custom_amt_off' => 'false'
        ), $atts ) );
        
        if($thanks_url)
            $this->redirect_url = $thanks_url;
        
        if(isset($_GET['amounts']) && !empty($_GET['amounts'])) {
            $amounts = $_GET['amounts'];
        }
        
        if(isset($_GET['source'])) {
            $source = $_GET['source'];
        } else if(isset($_GET['refcode'])) {
            $source = $_GET['refcode'];
        }
        
        if($amounts) {
            $amounts = explode(',', $amounts);
            $this->custom_amt_options = array();
            
            foreach($amounts as $amount) {
                $ths_amt = round($amount);
                $ths_amt = (string) $ths_amt;
                $this->custom_amt_options[$ths_amt.".00"] = '$'.$ths_amt;
            }
            $this->custom_amt_options['custom'] = '<label for="ngp_custom_dollar_amt">Other:</label> $<input type="text" name="custom_dollar_amt" %s class="ngp_custom_dollar_amt" /> <small>(USD)</small>';
        }
        
        if(!empty($_POST)) {
            $this->process_form();
        }
        
        $form_fields = '';
        // Loop through and generate the elements
        
        if(isset($source) && !empty($source)) {
            $form_fields .= '<input type="hidden" name="Source" value="'.$source.'" />';
        }
        
        foreach($this->fieldsets as $fieldset_name => $fields) {
            $form_fields .= '<fieldset><legend>'.$fieldset_name.'</legend>';
            if(isset($fields['html_intro'])) {
                $form_fields .= $fields['html_intro'];
                unset($fields['html_intro']);
            }
            foreach($fields as $field_key => $field) {
                switch($field['type']) {
                    case 'text':
                        if(!isset($field['show_pre_div']) || $field['show_pre_div']=='true') {
                            $form_fields .= '
                                <div class="input';
                            if(isset($field['error']) && $field['error']===true) {
                                $form_fields .= ' error';
                            }
                            $form_fields .= '">';
                        }
                        if(isset($field['error']) && $field['error']===true) {
                            $form_fields .= '<div class="errMsg">This field cannot be left blank.</div>';
                        }
                        if(!isset($field['show_label']) || $field['show_label']!='false') {
                            $form_fields .= '
                                    <label for="'.$field['slug'].'">'.$field['label'];
                            if($field['required']=='true') { $form_fields .= ' <span class="required">*</span>'; }
                            $form_fields .= '</label>';
                        }
                        $form_fields .= '<input type="text" name="'.$field['slug'].'" id="'.$field['slug'].'" value="';
                        if(isset($_POST[$field['slug']])) {
                            $form_fields .= $_POST[$field['slug']];
                        }
                        $form_fields .= '"';
                        if(!empty($field['label']) && (!isset($field['show_placeholder']) || $field['show_placeholder']=='true')) {
                            $form_fields .= ' placeholder="'.$field['label'].'"';
                        }
                        $form_fields .= ' />';
                        if(!isset($field['show_post_div']) || $field['show_post_div']=='true') {
                            $form_fields .= '</div>';
                        }
                        break;
                    case 'file':
                        $file = true;
                        $form_fields .= '
                            <div class="file';
                            if(isset($field['error']) && $field['error']===true) {
                                $form_fields .= ' error';
                            }
                            $form_fields .= '">';
                            if(isset($field['error']) && $field['error']===true && $field['required']=='true') {
                                $form_fields .= '<div class="errMsg">You must provide a '.$field['label'].'.</div>';
                            } else if(isset($field['error']) && $field['error']===true) {
                                $form_fields .= '<div class="errMsg">There was a problem uploading your file.</div>';
                            }
                        
                            $form_fields .= '
                                    <label for="'.$field['slug'].'">'.$field['label'];
                            if($field['required']=='true') { $form_fields .= ' <span class="required">*</span>'; }
                            $form_fields .= '</label>
                                <input type="file" name="'.$field['slug'].'" id="'.$field['slug'].'" />
                            </div>
                        ';
                        break;
                    case 'hidden':
                        $form_fields .= '<input type="hidden" name="'.$field['slug'].'" id="'.$field['slug'].'" value="';
                        if(isset($_POST[$field['slug']])) {
                            $form_fields .= $_POST[$field['slug']];
                        } else if(isset($field['value'])) {
                            $form_fields .= $field['value'];
                        }
                        $form_fields .= '" />';
                        break;
                    case 'password':
                        $form_fields .= '
                        <div class="password    ';
                            if(isset($field['error']) && $field['error']===true) {
                                $form_fields .= ' error';
                            }
                            $form_fields .= '">';
                            if(isset($field['error']) && $field['error']===true) {
                                $form_fields .= '<div class="errMsg">This field cannot be left blank.</div>';
                            }
                            $form_fields .= '
                                    <label for="'.$field['slug'].'">'.$field['label'];
                            if($field['required']=='true') { $form_fields .= ' <span class="required">*</span>'; }
                            $form_fields .= '</label>
                        <input type="password" name="'.$field['slug'].'" id="'.$field['slug'].'" value="';
                        if(isset($_POST[$field['slug']])) {
                            $form_fields .= $_POST[$field['slug']];
                        }
                        $form_fields .= '"/>
                        </div>
                        ';
                        break;
                    case 'textarea':
                        $form_fields .= '
                        <div class="textarea';
                        if(isset($field['error']) && $field['error']===true) {
                            $form_fields .= ' error';
                        }
                        $form_fields .= '">';
                        if(isset($field['error']) && $field['error']===true) {
                            $form_fields .= '<div class="errMsg">This field cannot be left blank.</div>';
                        }
                        $form_fields .= '
                                <label for="'.$field['slug'].'">'.$field['label'];
                        if($field['required']=='true') { $form_fields .= ' <span class="required">*</span>'; }
                        $form_fields .= '</label>
                        <textarea name="'.$field['slug'].'" id="'.$field['slug'].'">';
                        if(isset($_POST[$field['slug']])) {
                            $form_fields .= $_POST[$field['slug']];
                        }
                        $form_fields .= '</textarea>
                        </div>
                        ';
                        break;
                    case 'checkbox':
                        if(isset($field['options']) && !empty($field['options'])) {
                            $form_fields .= '<fieldset id="ngp_'.$field['slug'].'" class="checkboxgroup';
                            if(isset($field['error']) && $field['error']===true) {
                                $form_fields .= ' error">
                                <div class="errMsg">You must check at least one.</div>';
                            } else {
                                $form_fields .= '">';
                            }
                            $form_fields .= '<legend>'.$field['label'];
                            if($field['required']=='true') $form_fields .= '<span class="required">*</span>';
                            $form_fields .= '</legend>';
                            $i = 0;
                            foreach($field['options'] as $val) {
                                $i++;
                                $form_fields .= '<div class="checkboxoption"><input type="checkbox" value="'.$val.'" name="'.$field['slug'].'['.$i.']['.$val.']" id="option_'.$i.'_'.$field['slug'].'" class="'.$field['slug'].'" /> <label for="option_'.$i.'_'.$field['slug'].'">'.$val.'</label></div>'."\r\n";
                            }
                            $form_fields .= '</fieldset>';
                        } else {
                            $form_fields .= '<div id="ngp_'.$field['slug'].'" class="checkbox">';
                            $form_fields .= '<div class="checkboxoption"><input type="checkbox" name="'.$field['slug'].'" id="'.$field['slug'].'" class="'.$field['slug'].'" /> <label for="'.$field['slug'].'">'.$field['label'].'</label></div>'."\r\n";
                            $form_fields .= '</div>';
                        }
                        break;
                    case 'radio':
                        $form_fields .= '
                        <fieldset id="ngp_'.$field['slug'].'" class="radiogroup';
                        if(isset($field['error']) && $field['error']===true) {
                            $form_fields .= ' error';
                        }
                        $form_fields .= '"><legend>'.$field['label'];
                        if($field['required']=='true') { $form_fields .= '<span class="required">*</span>'; }
                        $form_fields .= '</legend>';
                        if(isset($field['error']) && $field['error']===true) {
                            $form_fields .= '<div class="errMsg">You must select an option.</div>';
                        }
                        $i = 0;
                        if($field['label']=='Amount' && isset($this->custom_amt_options)) {
                            $the_options = $this->custom_amt_options;
                        } else {
                            $the_options = $field['options'];
                        }
                        
                        if(isset($_GET['amt']) && empty($_POST)) {
                            if(strpos($_GET['amt'], '.')===false) {
                                $get_amt = $_GET['amt'].'.00';
                            } else {
                                $get_amt = $_GET['amt'];
                            }
                            
                            if(array_key_exists($get_amt, $the_options)) {
                                $amt = $get_amt;
                            } else {
                                $custom_amt = $_GET['amt'];
                            }
                        } else if(isset($_POST['custom_dollar_amt'])) {
                            $custom_amt = $_POST['custom_dollar_amt'];
                        } else if(isset($_POST[$field['slug']])) {
                            $amt = $_POST[$field['slug']];
                        }
                        
                        foreach($the_options as $val => $labe) {
                            $i++;
                            if($val=='custom' && $custom_amt_off=='false') {
                                $replace = (isset($custom_amt)) ? 'value="'.$custom_amt.'"' : '';
                                $form_fields .= '<div class="radio custom-donation-amt">'.sprintf($labe, $replace).'</div>'."\r\n";
                            } else {
                                $form_fields .= '<div class="radio"><input type="radio" value="'.$val.'" name="'.$field['slug'].'" id="'.$i.'_'.$field['slug'].'" class="'.$field['slug'].'"';
                                if(isset($amt) && $amt==$val) {
                                    $form_fields .= ' checked';
                                }
                                $form_fields .= '> <label for="'.$i.'_'.$field['slug'].'">'.$labe.'</label></div>'."\r\n";
                            }
                        }
                        $form_fields .= '</fieldset>';
                        break;
                    case 'select':
                        if(!isset($field['show_pre_div']) || $field['show_pre_div']=='true') {
                            $form_fields .= '
                                <div class="input';
                            if(isset($field['error']) && $field['error']===true) {
                                $form_fields .= ' error';
                            }
                            $form_fields .= '">';
                        }
                        if(isset($field['error']) && $field['error']===true) {
                            $form_fields .= '<div class="errMsg">You must select an option.</div>';
                        }
                        if(!isset($field['show_label']) || $field['show_label']!='false') {
                            $form_fields .= '
                                    <label for="'.$field['slug'].'">'.$field['label'];
                            if($field['required']=='true') { $form_fields .= ' <span class="required">*</span>'; }
                            $form_fields .= '</label>';
                        }
                        $form_fields .= '<select name="'.$field['slug'].'" id="'.$field['slug'].'">'."\r\n";
                        if($field['slug']!='State' && $field['slug']!='ExpYear' && $field['slug']!='ExpMonth') {
                            $form_fields .= '
                            <option>Select an option...</option>
                            ';
                        }
                        foreach($field['options'] as $key => $val) {
                            $form_fields .= '<option value="'.$key.'"';
                            if(isset($_POST[$field['slug']]) && $_POST[$field['slug']]==$key) {
                                $form_fields .= ' selected="selected"';
                            } else if(isset($default_state) && $default_state==$key) {
                                $form_fields .= ' selected="selected"';
                            }
                            $form_fields .= '>'.$val.'</option>'."\r\n";
                        }
                        $form_fields .= '</select>';
                        if(!isset($field['show_post_div']) || $field['show_post_div']=='true') {
                            $form_fields .= '</div>';
                        }
                        break;
                    case 'multiselect':
                        $form_fields .= '
                        <div class="multiselect    ';
                            if(isset($field['error']) && $field['error']===true) {
                                $form_fields .= ' error';
                            }
                            $form_fields .= '">';
                            if(isset($field['error']) && $field['error']===true) {
                                $form_fields .= '<div class="errMsg">This field cannot be left blank.</div>';
                            }
                            $form_fields .= '
                                    <label for="'.$field['slug'].'">'.$field['label'];
                            if($field['required']=='true') { $form_fields .= ' <span class="required">*</span>'; }
                            $form_fields .= '</label>
                            <select multiple name="'.$field['slug'].'" id="'.$field['slug'].'">'."\r\n";
                                foreach($field['options'] as $key => $val) {
                                    $form_fields .= '<option value="'.$key.'">'.$val.'</option>'."\r\n";
                                }
                                $form_fields .= '
                            </select>
                        </div>
                        ';
                        break;
                }
            }
            $form_fields .= '</fieldset>';
        }
        
        $return = '';
        if($this->any_errors) {
            $return .= '<div class="errMsg ngp_alert">There were errors in your payment submission. Please fix below and try again';
            if(!empty($this->support_phone)) {
                $return .= ' or call '.$this->support_phone;
            }
            $return .= '.</div>';
        } else if($this->ngp_error) {
            $return .= '<div class="errMsg ngp_alert">Sorry, but your payment could not be processed. Please try again';
            if(!empty($this->support_phone)) {
                $return .= ' or call '.$this->support_phone;
            }
            $return .= '.</div>';
        }
        
        if(!empty($form_fields)) {
            $return .= '<form ';
            if($file) { $return .= 'enctype="multipart/form-data" '; }
            $return .= 'name="ngp_user_news" class="ngp_user_submission" id="ngp_form" action="'.$_SERVER['REQUEST_URI'].'" method="post">';
            // echo '<input type="hidden" name="ngp_form_id" id="ngp_form_id" value="'.$id.'" />';
            
            if(function_exists('wp_nonce_field')) {
                $return .= wp_nonce_field('ngp_nonce_field', 'ngp_add', true, false);
            }
            
            $return .= $form_fields;
            if($thanks_url) {
                $return .= '<input type="hidden" name="redirect_url" value="'.$thanks_url.'" />';
            }
            
            $return .= '
                <div class="submit">
                    <input type="submit" value="Donate Now" />
                </div>
                <p class="ngp-small-print">By clicking on the "Donate now" button above you confirm that the following statements are true and accurate:</small>
                <ol class="ngp-small-print">
                    <li>I am a United States citizen or a lawfully admitted permanent resident of the United States.</li>
                    <li>This contribution is not made from the general treasury funds of a corporation, labor organization or national bank.</li>
                    <li>This contribution is not made from the treasury of an entity or person who is a federal contractor.</li>
                    <li>This contribution is not made from the funds of a political action committee.</li>
                    <li>The funds I am donating are not being provided to me by another person or entity for the purpose of making this contribution.</li>
                </ol>
                ';
            $return .= '<p class="addtl-donation-footer-info">'.str_replace("\r\n", '<br />', str_replace('&lt;i&gt;', '<i>', str_replace('&lt;/i&gt;', '</i>', str_replace('&lt;u&gt;', '<u>', str_replace('&lt;/u&gt;', '</u>', str_replace('&lt;b&gt;', '<b>', str_replace('&lt;/b&gt;', '</b>', get_option('ngp_footer_info')))))))).'</p>';
            $return .= '</form>';
            
            return $return;
        }
    }
    
    function donation_invite_form($atts=null) {
        extract( shortcode_atts( array(
            'url' => null,
            'source' => null,
            'submit' => '&raquo;',
        ), $atts ) );
        if($url) {
            $server_url_parts = explode('.', $_SERVER["SERVER_NAME"]);
            if(!empty($this->url_specified) && $server_url_parts[count($server_url_parts)-1]!=='dev') {
                $url_parts = $this->url_specified;
            } else {
                $url_parts = $server_url_parts;
            }
            if($_SERVER["HTTPS"] != "on" && $url_parts[count($url_parts)-1]!=='dev') {
                if(!empty($this->url_specified)) {
                    $newurl = "https://" . $this->url_specified;
                } else {
                    $newurl = "https://" . $_SERVER["SERVER_NAME"];
                }
            } else {
                $newurl = '';
            }
            $url_components = parse_url($url);
            if(isset($url_components['path']) && !isset($url_components['scheme']) && !isset($url_components['host'])) {
                $return = '<form action="'.$newurl.$url_components['path'].'" method="GET" id="ngp-donate-invite"><label for="ngp-donate-amt">Donate $<input type="text" id="ngp-donate-amt" name="amt" value=""><input type="submit" value="'.$submit.'">';
                
                if(isset($url_components['query'])) {
                    $queries = explode('&', $url_components['query']);
                    foreach($queries as $query) {
                        $this_query = explode('=', $query);
                        if(count($this_query)==2) {
                            $return .= '<input type="hidden" name="'.$this_query[0].'" value="'.$this_query[1].'" />';
                        }
                    }
                }
                if(isset($_GET['devtest'])) {
                    $return .= '<input type="hidden" name="devtest" value="true" />';
                }
                if($source) {
                    $return .= '<input type="hidden" name="source" value="'.$source.'" />';
                }
                $return .= '</label></form>';
            } else if(isset($url_components['scheme']) || isset($url_components['host'])) {
                return '<p>URL was not configured properly. Should be for your site and relative. e.g. <code>/donate</code></p>';
            } else {
                return '<p>URL was not configured properly.</p>';
            }
            return $return;
        } else {
            return '<p>No Url was configured.</p>';
        }
    }
}
$ngpDonationFrontend = new NGPDonationFrontend();

function ngp_process_form() {
    global $ngpDonationFrontend;
    $ngpDonationFrontend->process_form();
}

function ngp_show_form($atts=null) {
    global $ngpDonationFrontend;
    return $ngpDonationFrontend->show_form($atts);
}

function ngp_donation_invite_form($atts=null) {
    global $ngpDonationFrontend;
    return $ngpDonationFrontend->donation_invite_form($atts);
}