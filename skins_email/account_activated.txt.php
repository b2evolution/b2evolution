<?php
/**
 * This is sent to ((SystemAdmins)) to notify them that a new ((User)) account has been activated.
 *
 * For more info about email skins, see: http://b2evolution.net/man/themes-templates-skins/email-skins/
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// ---------------------------- EMAIL HEADER INCLUDED HERE ----------------------------
emailskin_include( '_email_header.inc.txt.php', $params );
// ------------------------------- END OF EMAIL HEADER --------------------------------

global $Settings, $UserSettings, $admin_url;

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
echo /* TRANS: noun */ T_('Login').": ".$activated_User->login."\n";
echo T_('Email').": ".$activated_User->email."\n";

$fullname = $activated_User->get( 'fullname' );
if( $fullname != '' )
{	// First name is defined
	echo T_('Full name').": ".$fullname."\n";
}

if( $activated_User->reg_ctry_ID > 0 )
{	// Country field is defined
	load_class( 'regional/model/_country.class.php', 'Country' );
	$CountryCache = & get_CountryCache();
	$reg_Country = $CountryCache->get_by_ID( $activated_User->reg_ctry_ID );
	echo T_('Registration Country').": ".$reg_Country->get_name()."\n";
}

$user_domain = $UserSettings->get( 'user_registered_from_domain', $activated_User->ID );
if( ! empty( $user_domain ) )
{	// Get user domain status if domain field is defined:
	load_funcs( 'sessions/model/_hitlog.funcs.php' );
	$DomainCache = & get_DomainCache();
	$Domain = & get_Domain_by_subdomain( $user_domain );
	$dom_status_titles = stats_dom_status_titles();
	$dom_status = $dom_status_titles[ $Domain ? $Domain->get( 'status' ) : 'unknown' ];
	echo T_('Registration Domain').": ".$user_domain.' ('.$dom_status.')'."\n";
}

if( $activated_User->ctry_ID > 0 )
{	// Country field is defined
	load_class( 'regional/model/_country.class.php', 'Country' );
	echo T_('Profile Country').": ".$activated_User->get_country_name()."\n";
}

if( !empty( $activated_User->source ) )
{	// Source is defined
	echo T_('Registration Source').": ".$activated_User->source."\n";
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

echo "\n";

if( ! empty( $activated_User->level ) )
{	// User level:
	echo T_('Assigned Level').": ".$activated_User->level."\n";
}

if( $user_Group = & $activated_User->get_Group() )
{	// User group:
	echo T_('Assigned Group').": ".$user_Group->get_name()."\n";
}

// A count of user's pictures:
$user_pictures_count = count( $activated_User->get_avatar_Links( false ) );
echo "\n".sprintf( T_('The user has %s profile pictures.'), $user_pictures_count )."\n\n";


echo T_('Edit user').': '.$admin_url.'?ctrl=user&user_tab=profile&user_ID='.$activated_User->ID."\n";
echo T_('Recent registrations').': '.$admin_url.'?ctrl=users&action=show_recent'."\n";

// Footer vars:
$params['unsubscribe_text'] = T_( 'If you don\'t want to receive any more notification when an account was activated by email, click here:' ).' '.
		get_htsrv_url().'quick_unsubscribe.php?type=account_activated&user_ID=$user_ID$&key=$unsubscribe_key$';

// ---------------------------- EMAIL FOOTER INCLUDED HERE ----------------------------
emailskin_include( '_email_footer.inc.txt.php', $params );
// ------------------------------- END OF EMAIL FOOTER --------------------------------
?>