=== NGP Forms ===
Contributors: signalfade, davidholtz
Tags: NGP, NGPVAN, Voter Action Network, donations, FEC, politics, fundraising, signup, volunteer
Requires at least: 3.0.0
Tested up to: 3.5.2
Stable tag: 1.2.4

Integrate NGP "Classic" (NGP VAN) donation, signup, and volunteer forms with your site. You'll need an SSL certificate running on your site if you want to use the donation portion of this plugin.

This plugin _does not_ work with the new NGP Action Platform.

== Description ==

This plugin helps you integrate NGP (NGP VAN) donation, signup, and volunteer forms with your site. You'll need an SSL certificate running on your site if you want to use the donation portion of this plugin.

This plugin _does not_ work with the new NGP Action Platform.

This plugin is built and maintained by [Revolution Messaging, LLC](http://revolutionmessaging.com) and makes use of the New Media Campaigns' NGP Donations API class.

### Alert No. 1!

This plugin does *not* work with NGP's recently released "NEW" platform. I think they call it the "Action" platform, but it's unclear.

### Alert No. 2!

You should be running your site under an SSL certificate if you utilize this plugin for donations.

== Installation ==

Go to `Settings -> General` and fill out the "NGP API Key", "Donation Support Phone Line", and "Addt'l Information for Donation Footer" fields.

* "NGP API Key" is how this plugin authenticates with your NGP VAN service. You need to make sure the API Credentials string you get from NGP is for the "CWP" API.
* "NGP Secure URL" is used if you need to specify a different secure URL than the site might have already been configured to use (for instance, your SSL cert is for donate.yourdomain.com, which points at your Wordpress site, but the site also loads under www.yourdomain.com).
* "Check to accept Amex" is a checkbox that, when checked, causes the American Express options for card-type to show up on the front-end.
* "Donation Support Phone Line" is shown with the error message when the contribution gets rejected by the NGP VAN server.
* "Addt'l Information for Donation Footer" might be used for listing things like donation mailing address, donation limits, taxable status of donations, etc.

If you want to use this plugin, including the donation portions, in a dev environment on your local machine, use ".dev" as the TLD (We develop in virtual hosts on our developer machines. Edit /etc/hosts to add a .dev url). When this plugin is live, it forces SSL on any pages with the donation shortcode. Make sure `$_SERVER['HTTPS']` is set, valid and "on".

== Usage ==

Place this short tag on the appropriate page or article:

== [ngp_show_donation] ==

You can set custom amounts for the donation amount in two ways:

1. Put the amounts in the embed tag: `[ngp_show_donation amounts="50,250,1000"]`
2. Put the amounts in a GET querystring: `http://mycamapign.com/donation?amounts=50,250,1000`

If you want to have a default donation amounts, put them in the embed tag and then override it with a querystring amounts when you need to.

You can source an article in two ways:

1. Put source in the embed tag: `[ngp_show_form source="hard-hitting-ad"]`
2. Put the source in a GET querystring: `http://mycamapign.com/donation?source=hard-hitting-ad`

If you want to have a default donation source, put it in the embed tag and then override it with a querystring source when you need to.

You can set custom thanks URL for the donation process by putting the url in the embed tag:

`[ngp_show_donation thanks_url="/thanks-for-your-donation"]`

The donations thanks URL defaults to: `/thank-you-for-your-contribution`

You can turn off the custom amount field:

`[ngp_show_donation custom_amt_off="true"]`

== Careful! ==

_If you turn off the custom amount field and you're using the Donation Invite form, you'll have constituents entering amounts that can't be found in the form and don't have a chance of getting placed in a custom amount field!_

== Donation Suggested jQuery ==

We use the following on our donation pages to make sure that the user understands that the radio buttons and the input field are for the same thing. If the user doesn't support javascript and the custom field holds a value, it always overrides whatever's selected in the radio buttons.

	$('.ngp_custom_dollar_amt').keyup(function() {
		if($(this).val()!='') { $('.Amount').attr('checked', false); }
	});
	$('.Amount').mouseup(function() {
		$('.ngp_custom_dollar_amt').val('');
	});

== [ngp_show_volunteer] ==

You can set custom thanks URL for the donation process by putting the url in the embed tag:

`[ngp_show_volunteer thanks_url="/thanks-for-your-work-pledge"]`

The volunteer thanks URL defaults to: `/thank-you-for-volunteering`

== [ngp_show_signup] ==

You can set custom thanks URL for the email signup process by putting the url in the embed tag:

`[ngp_show_signup thanks_url="/thanks-for-signing-up-for-incessant-emails"]`

The email signup thanks URL defaults to: `/thank-you-for-signing-up`

== Signup Extensive Options ==

Signup can make use of the COO API, if you happen to have credentials for that particular NGP API endpoint. This API allows you to send in less comprehensive data for signups.

You can find the three necessary pieces of information for configuring the plugin to use the COO API on the General Setting page.

When you've configured the plugin to use the COO API, you can specify what fields you want the signup embed tag to use. You must specify _at least_ Phone or Email.

`[ngp_show_signup fields="Name|Email|Phone|Zip"]`

Be aware that this data will end up in your NGP consituents database without any other accompanying information if a match cannot be made to existing data.

== [ngp_donation_invite_form url="/donate"] ==

You must set the url that the donation invite form heads to. It must be a relative URL.

If you want to have a donation source appended to the linked URL, put it in the embed tag:

`[ngp_donation_invite_form url="/donate" source="invite-form"]`

If you absolutely must have this invite form set custom amounts on your donation form, do it like this:

`[ngp_donation_invite_form url="/donate/?amounts=10,20,30,40,50"]`

== Changelog ==

= 1.2.4 =
* Another Alert.

= 1.2.3 =
* Fixed https link from donation invite form
* Added type "hidden" to source input on invite form

= 1.2.2 =
* (Another) Fix Readme

= 1.2.1 =
* Fix Readme

= 1.2 = 
* Donation Invitation Form & Functionality

= 1.1 = 
* COO API works for signups.

= 1.0 =
* First version. Migrated over NGP Donations plugin. Added Volunteer and Signup options.
