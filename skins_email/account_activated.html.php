<?php
/**
 * This is sent to ((Admins)) to notify them that a new ((User)) account has been activated.
 *
 * For more info about email skins, see: http://b2evolution.net/man/themes-templates-skins/email-skins/
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// ---------------------------- EMAIL HEADER INCLUDED HERE ----------------------------
emailskin_include( '_email_header.inc.html.php', $params );
// ------------------------------- END OF EMAIL HEADER --------------------------------

global $Settings, $UserSettings, $admin_url;

// Default params:
$params = array_merge( array(
		'User' => NULL,
		'activated_by_admin' => '',// Login of admin which activated current user account
	), $params );


$activated_User = $params['User'];

echo '<p'.emailskin_style( '.p' ).'>';
if( empty( $params['activated_by_admin'] ) )
{ // Current user activated own account
	echo T_('New user account activated').':';
}
else
{ // Admin activated current user account
	printf( T_('New user account activated by %s'), $params['activated_by_admin'] ).':';
}
echo '</p>'."\n";

echo '<table'.emailskin_style( 'table.email_table' ).'>'."\n";
echo '<tr><th'.emailskin_style( 'table.email_table th' ).'>'.T_('Login').':</th><td'.emailskin_style( 'table.email_table td' ).'>'.$activated_User->get_colored_login( array( 'mask' => '$avatar$ $login$', 'protocol' => 'http:' ) ).'</td></tr>'."\n";
echo '<tr><th'.emailskin_style( 'table.email_table th' ).'>'.T_('Email').':</th><td'.emailskin_style( 'table.email_table td' ).'>'.$activated_User->email.'</td></tr>'."\n";

$fullname = $activated_User->get( 'fullname' );

if( $fullname != '' )
{ // Full name is defined
	echo '<tr><th'.emailskin_style( 'table.email_table th' ).'>'.T_('Full name').':</th><td'.emailskin_style( 'table.email_table td' ).'>'.$fullname.'</td></tr>'."\n";
}

if( $activated_User->reg_ctry_ID > 0 )
{ // Country field is defined
	load_class( 'regional/model/_country.class.php', 'Country' );
	$CountryCache = & get_CountryCache();
	$reg_Country = $CountryCache->get_by_ID( $activated_User->reg_ctry_ID );
	echo '<tr><th'.emailskin_style( 'table.email_table th' ).'>'.T_('Registration Country').': </th><td'.emailskin_style( 'table.email_table td' ).'>'.$reg_Country->get_name().'</td></tr>'."\n";
}

$user_domain = $UserSettings->get( 'user_domain', $activated_User->ID );
if( ! empty( $user_domain ) )
{	// Get user domain status if domain field is defined:
	load_funcs( 'sessions/model/_hitlog.funcs.php' );
	$DomainCache = & get_DomainCache();
	$Domain = & get_Domain_by_subdomain( $user_domain );
	$dom_status_titles = stats_dom_status_titles();
	$dom_status = $dom_status_titles[ $Domain ? $Domain->get( 'status' ) : 'unknown' ];
	echo '<tr><th'.emailskin_style( 'table.email_table th' ).'>'.T_('Registration Domain').': </th><td'.emailskin_style( 'table.email_table td' ).'>'.$user_domain.' ('.$dom_status.')'.'</td></tr>'."\n";
}

if( $activated_User->ctry_ID > 0 )
{ // Country field is defined
	load_class( 'regional/model/_country.class.php', 'Country' );
	echo '<tr><th'.emailskin_style( 'table.email_table th' ).'>'.T_('Profile Country').': </th><td'.emailskin_style( 'table.email_table td' ).'>'.$activated_User->get_country_name().'</td></tr>'."\n";
}

if( !empty( $activated_User->source ) )
{ // Source is defined
	echo '<tr><th'.emailskin_style( 'table.email_table th' ).'>'.T_('Registration Source').':</th><td'.emailskin_style( 'table.email_table td' ).'>'.$activated_User->source.'</td></tr>'."\n";
}

if( $activated_User->gender == 'M' )
{ // Gender is Male
	echo '<tr><th'.emailskin_style( 'table.email_table th' ).'>'.T_('I am').':</th><td'.emailskin_style( 'table.email_table td' ).'>'.T_('A man').'</td></tr>'."\n";
}
else if( $activated_User->gender == 'F' )
{ // Gender is Female
	echo '<tr><th'.emailskin_style( 'table.email_table th' ).'>'.T_('I am').':</th><td'.emailskin_style( 'table.email_table td' ).'>'.T_('A woman').'</td></tr>'."\n";
}

if( $Settings->get( 'registration_ask_locale' ) && $activated_User->locale != '' )
{ // Locale field is defined
	global $locales;
	echo '<tr><th'.emailskin_style( 'table.email_table th' ).'>'.T_('Locale').':</th><td'.emailskin_style( 'table.email_table td' ).'>'.$locales[$activated_User->locale]['name'].'</td></tr>'."\n";
}

$registration_trigger_url = $UserSettings->get( 'registration_trigger_url', $activated_User->ID );
if( !empty( $registration_trigger_url ) )
{ // Trigger page
	echo '<tr><th'.emailskin_style( 'table.email_table th' ).'>'.T_('Registration Trigger Page').':</th><td'.emailskin_style( 'table.email_table td' ).'>'.get_link_tag( $registration_trigger_url, '', '.a' ).'</td></tr>'."\n";
}

$initial_blog_ID = $UserSettings->get( 'initial_blog_ID', $activated_User->ID );
if( !empty( $initial_blog_ID ) )
{ // Hit info
	echo '<tr><th'.emailskin_style( 'table.email_table th' ).'>'.T_('Initial page').':</th><td'.emailskin_style( 'table.email_table td' ).'>'.T_('Collection')." ".$UserSettings->get( 'initial_blog_ID', $activated_User->ID )." - ".$UserSettings->get( 'initial_URI', $activated_User->ID ).'</td></tr>'."\n";
	echo '<tr><th'.emailskin_style( 'table.email_table th' ).'>'.T_('Initial referer').':</th><td'.emailskin_style( 'table.email_table td' ).'>'.get_link_tag( $UserSettings->get( 'initial_referer', $activated_User->ID ), '', '.a' ).'</td></tr>'."\n";
}

echo '<tr><td'.emailskin_style( 'table.email_table td' ).' colspan=2>&nbsp;</td></tr>'."\n";

if( ! empty( $activated_User->level ) )
{	// User level:
	echo '<tr><th'.emailskin_style( 'table.email_table th' ).'>'.T_('Assigned Level').':</th><td'.emailskin_style( 'table.email_table td' ).'>'.$activated_User->level.'</td></tr>'."\n";
}

if( $user_Group = & $activated_User->get_Group() )
{	// User group:
	echo '<tr><th'.emailskin_style( 'table.email_table th' ).'>'.T_('Assigned Group').':</th><td'.emailskin_style( 'table.email_table td' ).'>'.$user_Group->get_name().'</td></tr>'."\n";
}

echo '</table>'."\n";

// User's pictures:
echo '<p'.emailskin_style( '.p' ).'>'.T_('The current profile pictures for this account are:').'</p>'."\n";
$user_pictures = '';
$user_avatars = $activated_User->get_avatar_Links( false );
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

// Buttons:
echo '<div'.emailskin_style( 'div.buttons' ).'>'."\n";
echo get_link_tag( $admin_url.'?ctrl=user&user_tab=profile&user_ID='.$activated_User->ID, T_('Edit User'), 'div.buttons a+a.button_yellow' )."\n";
echo get_link_tag( $admin_url.'?ctrl=users&action=show_recent', T_('View recent registrations'), 'div.buttons a+a.button_gray' )."\n";
echo "</div>\n";

// Footer vars:
$params['unsubscribe_text'] = T_( 'If you don\'t want to receive any more notification when an account was activated by email, click here:' )
			.' <a href="'.get_htsrv_url().'quick_unsubscribe.php?type=account_activated&user_ID=$user_ID$&key=$unsubscribe_key$"'.emailskin_style( '.a' ).'>'
			.T_('instant unsubscribe').'</a>.';

// ---------------------------- EMAIL FOOTER INCLUDED HERE ----------------------------
emailskin_include( '_email_footer.inc.html.php', $params );
// ------------------------------- END OF EMAIL FOOTER --------------------------------
?>