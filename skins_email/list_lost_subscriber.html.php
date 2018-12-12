<?php
/**
 * This is sent to ((Admins)) to notify them that a new ((User)) account has been activated.
 *
 * For more info about email skins, see: http://b2evolution.net/man/themes-templates-skins/email-skins/
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// ---------------------------- EMAIL HEADER INCLUDED HERE ----------------------------
emailskin_include( '_email_header.inc.html.php', $params );
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

echo '<p'.emailskin_style( '.p' ).'>';
if( empty( $params['unsubscribed_by_admin'] ) )
{	// Current user unsubscribed:
	echo T_('A user unsubscribed from your list/s').':';
}
else
{	// Admin unsubscribed user:
	printf( T_('A user was unsubscribed from your list/s by %s').':', get_user_colored_login_link( $params['unsubscribed_by_admin'], array( 'use_style' => true, 'protocol' => 'http:', 'login_text' => 'name' ) ) ).':';
}
echo '</p>'."\n";

// List of newsletters the user subscribed to:
if( $params['newsletters'] )
{
	echo '<ol>'."\n";
	foreach( $params['newsletters'] as $newsletter )
	{
		echo '<li>'.$newsletter->get( 'name' ).'</li>'."\n";
	}
	echo '</ol>'."\n";
}

// List of user tags applied:
if( $params['usertags'] )
{
	$tags = explode( ',', $params['usertags'] );
	echo '<p'.emailskin_style( '.p' ).'>';
	echo T_('User tags set as part of the unsubscription').':'."\n";
	foreach( $tags as $tag )
	{
		echo '<span'.emailskin_style( '.label+.label-default' ).'>'.$tag.'</span>'."\n";
	}
	echo '</p>'."\n";
}

echo '<table'.emailskin_style( 'table.email_table' ).'>'."\n";
echo '<tr><th'.emailskin_style( 'table.email_table th' ).'>'./* TRANS: noun */ T_('Login').':</th><td'.emailskin_style( 'table.email_table td' ).'>'.$subscribed_User->get_colored_login( array( 'mask' => '$avatar$ $login$', 'protocol' => 'http:' ) ).'</td></tr>'."\n";
echo '<tr><th'.emailskin_style( 'table.email_table th' ).'>'.T_('Email').':</th><td'.emailskin_style( 'table.email_table td' ).'>'.$subscribed_User->email.'</td></tr>'."\n";

$fullname = $subscribed_User->get( 'fullname' );

if( $fullname != '' )
{ // Full name is defined
	echo '<tr><th'.emailskin_style( 'table.email_table th' ).'>'.T_('Full name').':</th><td'.emailskin_style( 'table.email_table td' ).'>'.$fullname.'</td></tr>'."\n";
}

if( $subscribed_User->reg_ctry_ID > 0 )
{ // Country field is defined
	load_class( 'regional/model/_country.class.php', 'Country' );
	$CountryCache = & get_CountryCache();
	$reg_Country = $CountryCache->get_by_ID( $subscribed_User->reg_ctry_ID );
	echo '<tr><th'.emailskin_style( 'table.email_table th' ).'>'.T_('Registration Country').': </th><td'.emailskin_style( 'table.email_table td' ).'>'.$reg_Country->get_name().'</td></tr>'."\n";
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
	echo '<tr><th'.emailskin_style( 'table.email_table th' ).'>'.T_('Registration Domain').': </th>'.
			'<td'.emailskin_style( 'table.email_table td' ).'>'.$user_domain.' ('.$dom_status.')'.
			( ! empty( $user_ip_address ) ? ' '.get_link_tag( $admin_url.'?ctrl=antispam&action=whois&query='.$user_ip_address, 'WHOIS', 'div.buttons a+a.btn-default+a.btn-sm' ) : '' ).
			'</td></tr>'."\n";
}

if( $subscribed_User->ctry_ID > 0 )
{ // Country field is defined
	load_class( 'regional/model/_country.class.php', 'Country' );
	echo '<tr><th'.emailskin_style( 'table.email_table th' ).'>'.T_('Profile Country').': </th><td'.emailskin_style( 'table.email_table td' ).'>'.$subscribed_User->get_country_name().'</td></tr>'."\n";
}

echo '<tr><td'.emailskin_style( 'table.email_table td' ).' colspan=2>&nbsp;</td></tr>'."\n";

$initial_sess_ID = $UserSettings->get( 'initial_sess_ID', $subscribed_User->ID );
if( ! empty( $initial_sess_ID ) )
{	// Initial session ID:
	echo '<tr><th'.emailskin_style( 'table.email_table th' ).'>'.T_('Session ID').':</th><td'.emailskin_style( 'table.email_table td' ).'>'.get_link_tag( $admin_url.'?ctrl=stats&tab=hits&blog=0&sess_ID='.$initial_sess_ID, $initial_sess_ID, '.a' ).'</td></tr>'."\n";
}
$initial_blog_ID = $UserSettings->get( 'initial_blog_ID', $subscribed_User->ID );
if( !empty( $initial_blog_ID ) )
{ // Hit info
	echo '<tr><th'.emailskin_style( 'table.email_table th' ).'>'.T_('Initial referer').':</th><td'.emailskin_style( 'table.email_table td' ).'>'.get_link_tag( $UserSettings->get( 'initial_referer', $subscribed_User->ID ), '', '.a' ).'</td></tr>'."\n";
	echo '<tr><th'.emailskin_style( 'table.email_table th' ).'>'.T_('Initial page').':</th><td'.emailskin_style( 'table.email_table td' ).'>'.T_('Collection')." ".$UserSettings->get( 'initial_blog_ID', $subscribed_User->ID )." - ".$UserSettings->get( 'initial_URI', $subscribed_User->ID ).'</td></tr>'."\n";
}

if( $subscribed_User->gender == 'M' )
{ // Gender is Male
	echo '<tr><th'.emailskin_style( 'table.email_table th' ).'>'.T_('I am').':</th><td'.emailskin_style( 'table.email_table td' ).'>'.T_('A man').'</td></tr>'."\n";
}
else if( $subscribed_User->gender == 'F' )
{ // Gender is Female
	echo '<tr><th'.emailskin_style( 'table.email_table th' ).'>'.T_('I am').':</th><td'.emailskin_style( 'table.email_table td' ).'>'.T_('A woman').'</td></tr>'."\n";
}

if( $Settings->get( 'registration_ask_locale' ) && $subscribed_User->locale != '' )
{ // Locale field is defined
	global $locales;
	echo '<tr><th'.emailskin_style( 'table.email_table th' ).'>'.T_('Locale').':</th><td'.emailskin_style( 'table.email_table td' ).'>'.$locales[$subscribed_User->locale]['name'].'</td></tr>'."\n";
}

$registration_trigger_url = $UserSettings->get( 'registration_trigger_url', $subscribed_User->ID );
if( !empty( $registration_trigger_url ) )
{ // Trigger page
	echo '<tr><th'.emailskin_style( 'table.email_table th' ).'>'.T_('Registration Trigger Page').':</th><td'.emailskin_style( 'table.email_table td' ).'>'.get_link_tag( $registration_trigger_url, '', '.a' ).'</td></tr>'."\n";
}

if( !empty( $subscribed_User->source ) )
{ // Source is defined
	echo '<tr><th'.emailskin_style( 'table.email_table th' ).'>'.T_('Registration Source').':</th><td'.emailskin_style( 'table.email_table td' ).'>'.$subscribed_User->source.'</td></tr>'."\n";
}

echo '<tr><td'.emailskin_style( 'table.email_table td' ).' colspan=2>&nbsp;</td></tr>'."\n";

if( ! empty( $subscribed_User->level ) )
{	// User level:
	echo '<tr><th'.emailskin_style( 'table.email_table th' ).'>'.T_('Assigned Level').':</th><td'.emailskin_style( 'table.email_table td' ).'>'.$subscribed_User->level.'</td></tr>'."\n";
}

if( $user_Group = & $subscribed_User->get_Group() )
{	// User group:
	echo '<tr><th'.emailskin_style( 'table.email_table th' ).'>'.T_('Assigned Group').':</th><td'.emailskin_style( 'table.email_table td' ).'>'.$user_Group->get_name().'</td></tr>'."\n";
}

echo '</table>'."\n";

// User's pictures:
echo '<p'.emailskin_style( '.p' ).'>'.T_('The current profile pictures for this account are:').'</p>'."\n";
$user_pictures = '';
$user_avatars = $subscribed_User->get_avatar_Links( false );
foreach( $user_avatars as $user_Link )
{
	$user_pictures .= $user_Link->get_tag( array(
			'before_image'        => '',
			'before_image_legend' => '',
			'after_image_legend'  => '',
			'after_image'         => ' ',
			'image_size'          => 'crop-top-80x80'
		) );
}
echo empty( $user_pictures ) ? '<p'.emailskin_style( '.p' ).'><b>'.T_('No pictures.').'</b></p>' : $user_pictures;

// Account closure notice:
if( $params['user_account_closed'] )
{
	echo '<p'.emailskin_style( '.p' ).'>';
	echo T_('The user was automatically unsubscribed due to account closure.');
	echo '</p>'."\n";
}

// Buttons:
echo '<div'.emailskin_style( 'div.buttons' ).'>'."\n";
echo get_link_tag( $admin_url.'?ctrl=user&user_tab=profile&user_ID='.$subscribed_User->ID, T_('Edit User'), 'div.buttons a+a.btn-primary' )."\n";
echo "</div>\n";

// Footer vars:
$params['unsubscribe_text'] = T_( 'If you don\'t want to receive any more notification when a user unsubscribes from one of your lists, click here:' )
			.' <a href="'.get_htsrv_url().'quick_unsubscribe.php?type=list_lost_subscriber&user_ID=$user_ID$&key=$unsubscribe_key$"'.emailskin_style( '.a' ).'>'
			.T_('instant unsubscribe').'</a>.';

// ---------------------------- EMAIL FOOTER INCLUDED HERE ----------------------------
emailskin_include( '_email_footer.inc.html.php', $params );
// ------------------------------- END OF EMAIL FOOTER --------------------------------
?>