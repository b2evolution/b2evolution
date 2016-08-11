<?php
/**
 * This is sent to ((SystemAdmins)) to notify them that a new ((User)) account has been created.
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

global $admin_url;

// Default params:
$params = array_merge( array(
		'country'     => '',
		'reg_country' => '',
		'fullname '   => '',
		'gender'      => '',
		'locale'      => '',
		'source'      => '',
		'trigger_url' => '',
		'initial_hit' => '',
		'login'       => '',
		'email'       => '',
		'new_user_ID' => '',
	), $params );


echo '<p'.emailskin_style( '.p' ).'>'.T_('A new user has registered on the site').':</p>'."\n";

echo '<table'.emailskin_style( 'table.email_table' ).'>'."\n";
echo '<tr><th'.emailskin_style( 'table.email_table th' ).'>'.T_('Login').':</th><td'.emailskin_style( 'table.email_table td' ).'>'.get_user_colored_login_link( $params['login'], array( 'use_style' => true, 'protocol' => 'http:' ) ).'</td></tr>'."\n";
echo '<tr><th'.emailskin_style( 'table.email_table th' ).'>'.T_('Email').':</th><td'.emailskin_style( 'table.email_table td' ).'>'.$params['email'].'</td></tr>'."\n";

if( $params['fullname'] != '' )
{ // Full name is entered
	echo '<tr><th'.emailskin_style( 'table.email_table th' ).'>'.T_('Full name').':</th><td'.emailskin_style( 'table.email_table td' ).'>'.$params['fullname'].'</td></tr>'."\n";
}

if( $params['reg_country'] > 0 )
{ // Country field is entered
	load_class( 'regional/model/_country.class.php', 'Country' );
	$CountryCache = & get_CountryCache();
	$reg_Country = $CountryCache->get_by_ID( $params['reg_country'] );
	echo '<tr><th'.emailskin_style( 'table.email_table th' ).'>'.T_('Registration Country').':</th><td'.emailskin_style( 'table.email_table td' ).'>'.$reg_Country->get_name().'</td></tr>'."\n";
}

if( $params['country'] > 0 )
{ // Country field is entered
	load_class( 'regional/model/_country.class.php', 'Country' );
	$CountryCache = & get_CountryCache();
	$user_Country = $CountryCache->get_by_ID( $params['country'] );
	echo '<tr><th'.emailskin_style( 'table.email_table th' ).'>'.T_('Profile Country').':</th><td'.emailskin_style( 'table.email_table td' ).'>'.$user_Country->get_name().'</td></tr>'."\n";
}

if( !empty( $params['source'] ) )
{ // Source is defined
	echo '<tr><th'.emailskin_style( 'table.email_table th' ).'>'.T_('Registration Source').':</th><td'.emailskin_style( 'table.email_table td' ).'>'.$params['source'].'</td></tr>'."\n";
}

if( $params['gender'] == 'M' )
{ // Gender is Male
	echo '<tr><th'.emailskin_style( 'table.email_table th' ).'>'.T_('I am').':</th><td'.emailskin_style( 'table.email_table td' ).'>'.T_('A man').'</td></tr>'."\n";
}
else if( $params['gender'] == 'F' )
{ // Gender is Female
	echo '<tr><th'.emailskin_style( 'table.email_table th' ).'>'.T_('I am').':</th><td'.emailskin_style( 'table.email_table td' ).'>'.T_('A woman').'</td></tr>'."\n";
}

if( !empty( $params['locale'] ) )
{ // Locale field is entered
	global $locales;
	echo '<tr><th'.emailskin_style( 'table.email_table th' ).'>'.T_('Locale').':</th><td'.emailskin_style( 'table.email_table td' ).'>'.$locales[ $params['locale'] ]['name'].'</td></tr>'."\n";
}

if( !empty( $params['trigger_url'] ) )
{ // Trigger page
	echo '<tr><th'.emailskin_style( 'table.email_table th' ).'>'.T_('Registration Trigger Page').':</th><td'.emailskin_style( 'table.email_table td' ).'>'.get_link_tag( $params['trigger_url'], '', '.a' ).'</td></tr>'."\n";
}

if( ! empty( $params['initial_hit'] ) )
{ // Hit info
	echo '<tr><th'.emailskin_style( 'table.email_table th' ).'>'.T_('Initial page').':</th><td'.emailskin_style( 'table.email_table td' ).'>'.T_('Collection')." ".$params['initial_hit']->hit_coll_ID." - ".$params['initial_hit']->hit_uri.'</td></tr>'."\n";
	echo '<tr><th'.emailskin_style( 'table.email_table th' ).'>'.T_('Initial referer').':</th><td'.emailskin_style( 'table.email_table td' ).'>'.get_link_tag( $params['initial_hit']->hit_referer, '', '.a' ).'</td></tr>'."\n";
}

echo '<tr><td'.emailskin_style( 'table.email_table td' ).' colspan=2>&nbsp;</td></tr>'."\n";

if( ! empty( $params['level'] ) )
{	// User level:
	echo '<tr><th'.emailskin_style( 'table.email_table th' ).'>'.T_('Assigned Level').':</th><td'.emailskin_style( 'table.email_table td' ).'>'.$params['level'].'</td></tr>'."\n";
}

if( ! empty( $params['group'] ) )
{	// User group:
	echo '<tr><th'.emailskin_style( 'table.email_table th' ).'>'.T_('Assigned Group').':</th><td'.emailskin_style( 'table.email_table td' ).'>'.$params['group'].'</td></tr>'."\n";
}

echo '</table>'."\n";

// Buttons:
echo '<div'.emailskin_style( 'div.buttons' ).'>'."\n";
echo get_link_tag( $admin_url.'?ctrl=user&user_tab=profile&user_ID='.$params['new_user_ID'], T_('Edit User'), 'div.buttons a+a.button_yellow' )."\n";
echo get_link_tag( $admin_url.'?ctrl=users&action=show_recent', T_('View recent registrations'), 'div.buttons a+a.button_gray' )."\n";
echo "</div>\n";

// Footer vars:
$params['unsubscribe_text'] = T_( 'If you don\'t want to receive any more notifications about new user registrations, click here:' )
			.' <a href="'.get_htsrv_url().'quick_unsubscribe.php?type=user_registration&user_ID=$user_ID$&key=$unsubscribe_key$"'.emailskin_style( '.a' ).'>'
			.T_('instant unsubscribe').'</a>.';

// ---------------------------- EMAIL FOOTER INCLUDED HERE ----------------------------
emailskin_include( '_email_footer.inc.html.php', $params );
// ------------------------------- END OF EMAIL FOOTER --------------------------------
?>
