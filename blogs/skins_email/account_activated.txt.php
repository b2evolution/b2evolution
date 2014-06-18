<?php
/**
 * This is sent to ((SystemAdmins)) to notify them that a new ((User)) account has been activated.
 *
 * For more info about email skins, see: http://b2evolution.net/man/themes-templates-skins/email-skins/
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 *
 * @version $Id: account_activated.txt.php 6135 2014-03-08 07:54:05Z manuel $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// ---------------------------- EMAIL HEADER INCLUDED HERE ----------------------------
emailskin_include( '_email_header.inc.txt.php', $params );
// ------------------------------- END OF EMAIL HEADER --------------------------------

global $Settings, $UserSettings, $admin_url, $htsrv_url;

// Default params:
$params = array_merge( array(
		'User' => NULL,
		'activated_by_admin' => '',// Login of admin which activated current user account
	), $params );


$activated_User = $params['User'];

if( empty( $params['activated_by_admin'] ) )
{ // Current user activated own account
	echo T_('New user account activated').':';
}
else
{ // Admin activated current user account
	printf( T_('New user account activated by %s'), $params['activated_by_admin'] ).':';
}
echo "\n\n";
echo T_('Login').": ".$activated_User->login."\n";
echo T_('Email').": ".$activated_User->email."\n";

if( $activated_User->ctry_ID > 0 )
{	// Country field is defined
	load_class( 'regional/model/_country.class.php', 'Country' );
	echo T_('Country').": ".$activated_User->get_country_name()."\n";
}

if( $activated_User->firstname != '' )
{	// First name is defined
	echo T_('First name').": ".$activated_User->firstname."\n";
}

if( $activated_User->gender == 'M' )
{	// Gender is Male
	echo T_('I am').": ".T_('A man')."\n";
}
else if( $activated_User->gender == 'F' )
{	// Gender is Female
	echo T_('I am').": ".T_('A woman')."\n";
}

if( $Settings->get( 'registration_ask_locale' ) && $activated_User->locale != '' )
{	// Locale field is defined
	global $locales;
	echo T_('Locale').": ".$locales[$activated_User->locale]['name']."\n";
}

if( !empty( $activated_User->source ) )
{	// Source is defined
	echo T_('Registration Source').": ".$activated_User->source."\n";
}

$registration_trigger_url = $UserSettings->get( 'registration_trigger_url', $activated_User->ID );
if( !empty( $registration_trigger_url ) )
{	// Trigger page
	echo T_('Registration Trigger Page').": ".$registration_trigger_url."\n";
}

$initial_blog_ID = $UserSettings->get( 'initial_blog_ID', $activated_User->ID );
if( !empty( $initial_blog_ID ) )
{	// Hit info
	echo T_('Initial page').": ".T_('Blog')." ".$UserSettings->get( 'initial_blog_ID', $activated_User->ID )." - ".$UserSettings->get( 'initial_URI', $activated_User->ID )."\n";
	echo T_('Initial referer').": ".$UserSettings->get( 'initial_referer', $activated_User->ID )."\n";
}

// A count of user's pictures:
$user_pictures_count = count( $activated_User->get_avatar_Links( false ) );
echo sprintf( T_('The user has %s profile pictures.'), $user_pictures_count )."\n\n";


echo T_('Edit user').': '.$admin_url.'?ctrl=user&user_tab=profile&user_ID='.$activated_User->ID."\n";
echo T_('Recent registrations').': '.$admin_url.'?ctrl=users&action=show_recent'."\n";

// Footer vars:
$params['unsubscribe_text'] = T_( 'If you don\'t want to receive any more notification when an account was activated by email, click here:' ).' '.
		$htsrv_url.'quick_unsubscribe.php?type=account_activated&user_ID=$user_ID$&key=$unsubscribe_key$';

// ---------------------------- EMAIL FOOTER INCLUDED HERE ----------------------------
emailskin_include( '_email_footer.inc.txt.php', $params );
// ------------------------------- END OF EMAIL FOOTER --------------------------------
?>