<?php
/*
    Plugin Name: NGP Forms
    Plugin URI: http://revolutionmessaging.com/code/ngp-forms/
    Description: Integrate NGP donation, volunteer, & signup forms with your site
    Version: 1.2.4
    Author: Revolution Messaging
    Author URI: http://revolutionmessaging.com
    Tags: NGP, NGPVAN, Voter Action Network, donations, FEC, politics, fundraising
    License: MIT
    
    Copyright 2011 Revolution Messaging LLC (email: support@revolutionmessaging.com)
    
    Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
    
    The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
    
    THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
    */

$GLOBALS['ngp'] = (object) array(
    'version' => '1.2.4'
);

include_once(dirname(__FILE__).'/ngp-donation-frontend.php');
include_once(dirname(__FILE__).'/ngp-signup-frontend.php');
include_once(dirname(__FILE__).'/ngp-volunteer-frontend.php');

if(strpos($_SERVER['REQUEST_URI'], 'ngp-donations/admin')!==false) {
    include_once(dirname(__FILE__).'/ngp-manage.php');
}

if (!function_exists('add_action')){
    require_once("../../../wp-config.php");
}

add_action('admin_init', 'ngp_admin_init');
add_shortcode('ngp_show_form', 'ngp_show_form');
add_shortcode('ngp_show_donation', 'ngp_show_form');
add_shortcode('ngp_show_volunteer', 'ngp_show_volunteer');
add_shortcode('ngp_show_signup', 'ngp_show_signup');
add_shortcode('ngp_donation_invite_form', 'ngp_donation_invite_form');

if(isset($_POST['ngp_add'])) {
    add_action('wp', 'ngp_process_form');
}
if(isset($_POST['ngp_volunteer'])) {
    add_action('wp', 'ngp_process_volunteer');
}
if(isset($_POST['ngp_signup'])) {
    add_action('wp', 'ngp_process_signup');
}

function ngp_admin_init() {
    // add_action('admin_menu', 'ngp_plugin_menu');
    register_setting('general', 'ngp_api_key', 'esc_attr');
    add_settings_field(
        'ngp_api_key',
        '<label for="ngp_api_key">'.__('NGP API Key' , 'ngp_api_key' ).'</label>',
        'ngp_api_key_field',
        'general'
    );
    // register_setting('general', 'ngp_volunteer_thanks_url', 'esc_attr');
    // add_settings_field(
    //     'ngp_volunteer_thanks_url',
    //     '<label for="ngp_volunteer_thanks_url">'.__('"Thanks for Volunteering" URL' , 'ngp_volunteer_thanks_url' ).'<br /><small>(e.g. /thank-you-for-volunteering")</small></label>',
    //     'ngp_volunteer_thanks_url_field',
    //     'general'
    // );

    // register_setting('general', 'ngp_thanks_url', 'esc_attr');
    // add_settings_field(
    //     'ngp_thanks_url',
    //     '<label for="ngp_api_key">'.__('"Thanks for Contributing" URL' , 'ngp_thanks_url' ).'<br /><small>(e.g. "/thank-you")</small></label>',
    //     'ngp_thanks_url_field',
    //     'general'
    // );

    register_setting('general', 'ngp_secure_url', 'esc_attr');
    add_settings_field(
        'ngp_secure_url',
        '<label for="ngp_secure_url">'.__('Secure URL (No https://)' , 'ngp_secure_url' ).'</label>',
        'ngp_secure_url',
        'general'
    );
    register_setting('general', 'ngp_accept_amex', 'esc_attr');
    add_settings_field(
        'ngp_accept_amex',
        '<label for="ngp_accept_amex">'.__('Check to accept Amex:' , 'ngp_accept_amex' ).'</label>',
        'ngp_accept_amex',
        'general'
    );
    register_setting('general', 'ngp_support_phone', 'esc_attr');
    add_settings_field(
        'ngp_support_phone',
        '<label for="ngp_support_phone">'.__('Donation Support Phone Line' , 'ngp_support_phone' ).'</label>',
        'ngp_support_phone',
        'general'
    );
    register_setting('general', 'ngp_footer_info', 'esc_attr');
    add_settings_field(
        'ngp_footer_info',
        '<label for="ngp_footer_info">'.__('Addt\'l Information for Donation Footer' , 'ngp_footer_info' ).'</label>',
        'ngp_footer_info',
        'general'
    );

    register_setting('general', 'ngp_coo_api_key', 'esc_attr');
    add_settings_field(
        'ngp_coo_api_key',
        '<label for="ngp_coo_api_key">'.__('NGP COO API Key <small>(optional)</small>' , 'ngp_coo_api_key' ).'</label>',
        'ngp_coo_api_key_field',
        'general'
    );
    register_setting('general', 'ngp_campaignid', 'esc_attr');
    add_settings_field(
        'ngp_campaignid',
        '<label for="ngp_campaignid">'.__('NGP Campaign ID <small>(optional)</small>' , 'ngp_campaignid' ).'</label>',
        'ngp_campaignid_field',
        'general'
    );
    register_setting('general', 'ngp_userid', 'esc_attr');
    add_settings_field(
        'ngp_userid',
        '<label for="ngp_userid">'.__('NGP User ID <small>(optional)</small>' , 'ngp_userid' ).'</label>',
        'ngp_userid_field',
        'general'
    );
}

function ngp_api_key_field() {
    $value = get_option('ngp_api_key', '');
    echo '<input type="text" style="width:300px;" id="ngp_api_key" name="ngp_api_key" value="' . $value . '" />';
}

// function ngp_volunteer_thanks_url_field() {
//     $value = get_option('ngp_volunteer_thanks_url', '');
//     echo '<input type="text" style="width:300px;" id="ngp_volunteer_thanks_url" name="ngp_volunteer_thanks_url" value="' . $value . '" />';
// }

// function ngp_thanks_url_field() {
//     $value = get_option('ngp_thanks_url', '');
//     echo '<input type="text" style="width:300px;" id="ngp_thanks_url" name="ngp_thanks_url" value="' . $value . '" />';
// }

function ngp_support_phone() {
    $value = get_option('ngp_support_phone', '');
    echo '<input type="text" style="width:150px;" id="ngp_support_phone" name="ngp_support_phone" value="' . $value . '" />';
}

function ngp_secure_url() {
    $value = get_option('ngp_secure_url', '');
    echo '<input type="text" style="width:150px;" id="ngp_secure_url" name="ngp_secure_url" value="' . $value . '" />';
}

function ngp_accept_amex() {
    $value = get_option('ngp_accept_amex', '');
    if($value=='on')
        echo '<input type="checkbox" id="ngp_accept_amex" name="ngp_accept_amex" checked="checked" />';
    else
        echo '<input type="checkbox" id="ngp_accept_amex" name="ngp_accept_amex" />';
}

function ngp_footer_info() {
    $value = get_option('ngp_footer_info', '');
    echo '<textarea style="width:300px;height:150px;" id="ngp_footer_info" name="ngp_footer_info">'.$value.'</textarea>';
}

function ngp_coo_api_key_field() {
    $value = get_option('ngp_coo_api_key', '');
    echo '<input type="text" style="width:300px;" id="ngp_coo_api_key" name="ngp_coo_api_key" value="' . $value . '" />';
}

function ngp_userid_field() {
    $value = get_option('ngp_userid', '');
    echo '<input type="text" style="width:300px;" id="ngp_userid" name="ngp_userid" value="' . $value . '" />';
}

function ngp_campaignid_field() {
    $value = get_option('ngp_campaignid', '');
    echo '<input type="text" style="width:300px;" id="ngp_campaignid" name="ngp_campaignid" value="' . $value . '" />';
}

// register_activation_hook(__FILE__, 'psc_activate');
// register_deactivation_hook(__FILE__, 'psc_deactivate');
// register_uninstall_hook(__FILE__, 'psc_uninstall');

// function ngp_plugin_menu() {
//     add_submenu_page('settings.php', 'NGP Donations', 'NGP Donations', 'ngp_manage_forms');
// }
