<?php
/**
 * This is sent to ((SystemAdmins)) to notify them that a new ((User)) account has been created.
 *
 * For more info about email skins, see: http://b2evolution.net/man/themes-templates-skins/email-skins/
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 *
 * @version $Id: account_new.html.php 6135 2014-03-08 07:54:05Z manuel $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// ---------------------------- EMAIL HEADER INCLUDED HERE ----------------------------
emailskin_include( '_email_header.inc.html.php', $params );
// ------------------------------- END OF EMAIL HEADER --------------------------------

global $admin_url, $htsrv_url;

// Default params:
$params = array_merge( array(
		'country'     => '',
		'firstname'   => '',
		'gender'      => '',
		'locale'      => '',
		'source'      => '',
		'trigger_url' => '',
		'initial_hit' => '',
		'login'       => '',
		'email'       => '',
		'new_user_ID' => '',
	), $params );


echo '<p>'.T_('A new user has registered on the site').':</p>'."\n";

echo '<table class="email_table">'."\n";
echo '<tr><th>'.T_('Login').':</th><td>'.get_user_colored_login( $params['login'] ).'</td></tr>'."\n";
echo '<tr><th>'.T_('Email').':</th><td>'.$params['email'].'</td></tr>'."\n";

if( $params['country'] > 0 )
{ // Country field is entered
	load_class( 'regional/model/_country.class.php', 'Country' );
	$CountryCache = & get_CountryCache();
	$user_Country = $CountryCache->get_by_ID( $params['country'] );
	echo '<tr><th>'.T_('Country').':</th><td>'.$user_Country->get_name().'</td></tr>'."\n";
}

if( $params['firstname'] != '' )
{ // First name is entered
	echo '<tr><th>'.T_('First name').':</th><td>'.$params['firstname'].'</td></tr>'."\n";
}

if( $params['gender'] == 'M' )
{ // Gender is Male
	echo '<tr><th>'.T_('I am').':</th><td>'.T_('A man').'</td></tr>'."\n";
}
else if( $params['gender'] == 'F' )
{ // Gender is Female
	echo '<tr><th>'.T_('I am').':</th><td>'.T_('A woman').'</td></tr>'."\n";
}

if( !empty( $params['locale'] ) )
{ // Locale field is entered
	global $locales;
	echo '<tr><th>'.T_('Locale').':</th><td>'.$locales[ $params['locale'] ]['name'].'</td></tr>'."\n";
}

if( !empty( $params['source'] ) )
{ // Source is defined
	echo '<tr><th>'.T_('Registration Source').':</th><td>'.$params['source'].'</td></tr>'."\n";
}

if( !empty( $params['trigger_url'] ) )
{ // Trigger page
	echo '<tr><th>'.T_('Registration Trigger Page').':</th><td>'.get_link_tag( $params['trigger_url'] ).'</td></tr>'."\n";
}

if( !empty ( $params['initial_hit'] ) )
{ // Hit info
	echo '<tr><th>'.T_('Initial page').':</th><td>'.T_('Blog')." ".$params['initial_hit']->hit_coll_ID." - ".$params['initial_hit']->hit_uri.'</td></tr>'."\n";
	echo '<tr><th>'.T_('Initial referer').':</th><td>'.get_link_tag( $params['initial_hit']->hit_referer ).'</td></tr>'."\n";
}

echo '</table>'."\n";

// Buttons:
echo '<div class="buttons">'."\n";
echo get_link_tag( $admin_url.'?ctrl=user&user_tab=profile&user_ID='.$params['new_user_ID'], T_('Edit User'), 'button_yellow' )."\n";
echo get_link_tag( $admin_url.'?ctrl=users&action=show_recent', T_('View recent registrations'), 'button_gray' )."\n";
echo "</div>\n";

// Footer vars:
$params['unsubscribe_text'] = T_( 'If you don\'t want to receive any more notifications about new user registrations, click here:' )
			.' <a href="'.$htsrv_url.'quick_unsubscribe.php?type=user_registration&user_ID=$user_ID$&key=$unsubscribe_key$">'
			.T_('instant unsubscribe').'</a>.';

// ---------------------------- EMAIL FOOTER INCLUDED HERE ----------------------------
emailskin_include( '_email_footer.inc.html.php', $params );
// ------------------------------- END OF EMAIL FOOTER --------------------------------
?>
