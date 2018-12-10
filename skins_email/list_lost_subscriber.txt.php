<?php
/**
 * This is sent to ((SystemAdmins)) to notify them that a new ((User)) account has been activated.
 *
 * For more info about email skins, see: http://b2evolution.net/man/themes-templates-skins/email-skins/
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// ---------------------------- EMAIL HEADER INCLUDED HERE ----------------------------
emailskin_include( '_email_header.inc.txt.php', $params );
// ------------------------------- END OF EMAIL HEADER --------------------------------

global $Settings, $UserSettings, $admin_url;

// Default params:
$params = array_merge( array(
		'subscribed_User' => NULL,
		'newsletters'     => array(),
		'usertags'        => '', // new user tags being set as part of the new subscription
		'unsubscribed_by_admin' => '', // Login of admin which unsubscribed the user
		'user_account_closed' => false, // unsubscribed because account was closed
	), $params );


$subscribed_User = $params['subscribed_User'];

if( empty( $params['unsubscribed_by_admin'] ) )
{	// Current user unsubscribed:
	echo T_('A user unsubscribed from your list/s').':';
}
else
{	// Admin unsubscribed user:
	printf( T_('A user was unsubscribed from your list/s by %s').':', $params['unsubscribed_by_admin'] ).':';
}
echo "\n\n";

// List of newsletters the user subscribed to:
if( $params['newsletters'] )
{
	foreach( $params['newsletters'] as $newsletter )
	{
		echo "\t".'- '.$newsletter->get( 'name' )."\n";
	}
	echo "\n\n";
}

echo /* TRANS: noun */ T_('Login').": ".$subscribed_User->login."\n";
echo T_('Email').": ".$subscribed_User->email."\n";

$fullname = $subscribed_User->get( 'fullname' );
if( $fullname != '' )
{	// First name is defined
	echo T_('Full name').": ".$fullname."\n";
}

if( $subscribed_User->reg_ctry_ID > 0 )
{	// Country field is defined
	load_class( 'regional/model/_country.class.php', 'Country' );
	$CountryCache = & get_CountryCache();
	$reg_Country = $CountryCache->get_by_ID( $subscribed_User->reg_ctry_ID );
	echo T_('Registration Country').": ".$reg_Country->get_name()."\n";
}

$user_domain = $UserSettings->get( 'user_registered_from_domain', $subscribed_User->ID );
if( ! empty( $user_domain ) )
{	// Get user domain status if domain field is defined:
	$user_ip_address = int2ip( $UserSettings->get( 'created_fromIPv4', $subscribed_User->ID ) );
	load_funcs( 'sessions/model/_hitlog.funcs.php' );
	$DomainCache = & get_DomainCache();
	$Domain = & get_Domain_by_subdomain( $user_domain );
	$dom_status_titles = stats_dom_status_titles();
	$dom_status = $dom_status_titles[ $Domain ? $Domain->get( 'status' ) : 'unknown' ];
	echo T_('Registration Domain').": ".$user_domain.' ('.$dom_status.')'.
			( ! empty( $user_ip_address ) ? ' '.$admin_url.'?ctrl=antispam&action=whois&query='.$user_ip_address : '' )."\n";
}

if( $subscribed_User->ctry_ID > 0 )
{	// Country field is defined
	load_class( 'regional/model/_country.class.php', 'Country' );
	echo T_('Profile Country').": ".$subscribed_User->get_country_name()."\n";
}

echo "\n";

$initial_sess_ID = $UserSettings->get( 'initial_sess_ID', $subscribed_User->ID );
if( ! empty( $initial_sess_ID ) )
{	// Initial session ID:
	echo T_('Session ID').': '.$initial_sess_ID.' - '.$admin_url.'?ctrl=stats&tab=hits&blog=0&sess_ID='.$initial_sess_ID."\n";
}
$initial_blog_ID = $UserSettings->get( 'initial_blog_ID', $subscribed_User->ID );
if( !empty( $initial_blog_ID ) )
{	// Hit info
	echo T_('Initial referer').": ".$UserSettings->get( 'initial_referer', $subscribed_User->ID )."\n";
	echo T_('Initial page').": ".T_('Blog')." ".$UserSettings->get( 'initial_blog_ID', $subscribed_User->ID )." - ".$UserSettings->get( 'initial_URI', $subscribed_User->ID )."\n";
}

if( $subscribed_User->gender == 'M' )
{	// Gender is Male
	echo T_('I am').": ".T_('A man')."\n";
}
else if( $subscribed_User->gender == 'F' )
{	// Gender is Female
	echo T_('I am').": ".T_('A woman')."\n";
}

if( $Settings->get( 'registration_ask_locale' ) && $subscribed_User->locale != '' )
{	// Locale field is defined
	global $locales;
	echo T_('Locale').": ".$locales[$subscribed_User->locale]['name']."\n";
}

$registration_trigger_url = $UserSettings->get( 'registration_trigger_url', $subscribed_User->ID );
if( !empty( $registration_trigger_url ) )
{	// Trigger page
	echo T_('Registration Trigger Page').": ".$registration_trigger_url."\n";
}

if( !empty( $subscribed_User->source ) )
{	// Source is defined
	echo T_('Registration Source').": ".$subscribed_User->source."\n";
}

echo "\n";

if( ! empty( $subscribed_User->level ) )
{	// User level:
	echo T_('Assigned Level').": ".$subscribed_User->level."\n";
}

if( $user_Group = & $subscribed_User->get_Group() )
{	// User group:
	echo T_('Assigned Group').": ".$user_Group->get_name()."\n";
}

// A count of user's pictures:
$user_pictures_count = count( $subscribed_User->get_avatar_Links( false ) );
echo "\n".sprintf( T_('The user has %s profile pictures.'), $user_pictures_count )."\n\n";


// List of user tags applied:
if( $params['usertags'] )
{
	$tags = explode( ',', $params['usertags'] );
	echo T_('User tags set as part of the unsubscription').':'."\n";
	echo implode( ', ', $tags )."\n\n";
}

// Account closure notice:
if( $params['user_account_closed'] )
{
	echo T_('The user was automatically unsubscribed due to account closure.')."\n\n";
}

// Footer vars:
$params['unsubscribe_text'] = T_( 'If you don\'t want to receive any more notification when a user unsubscribes from one of your lists, click here:' ).' '.
		get_htsrv_url().'quick_unsubscribe.php?type=list_lost_subscriber&user_ID=$user_ID$&key=$unsubscribe_key$';

// ---------------------------- EMAIL FOOTER INCLUDED HERE ----------------------------
emailskin_include( '_email_footer.inc.txt.php', $params );
// ------------------------------- END OF EMAIL FOOTER --------------------------------
?>