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
emailskin_include( '_email_header.inc.txt.php', $params );
// ------------------------------- END OF EMAIL HEADER --------------------------------

global $admin_url, $htsrv_url;

// Default params:
$params = array_merge( array(
		'country'     => '',
		'reg_country' => '',
		'fullname'    => '',
		'gender'      => '',
		'locale'      => '',
		'source'      => '',
		'trigger_url' => '',
		'initial_hit' => '',
		'login'       => '',
		'email'       => '',
		'new_user_ID' => '',
	), $params );


echo T_('A new user has registered on the site').":";
echo "\n\n";

echo T_('Login').": ".$params['login']."\n";
echo T_('Email').": ".$params['email']."\n";

if( $params['fullname'] != '' )
{ // Full name is entered
	echo T_('Full name').": ".$params['fullname']."\n";
}

if( $params['reg_country'] > 0 )
{ // Country field is entered
	load_class( 'regional/model/_country.class.php', 'Country' );
	$CountryCache = & get_CountryCache();
	$reg_Country = $CountryCache->get_by_ID( $params['reg_country'] );
	echo T_('Registration Country').": ".$reg_Country->get_name()."\n";
}

if( $params['country'] > 0 )
{ // Country field is entered
	load_class( 'regional/model/_country.class.php', 'Country' );
	$CountryCache = & get_CountryCache();
	$user_Country = $CountryCache->get_by_ID( $params['country'] );
	echo T_('Profile Country').": ".$user_Country->get_name()."\n";
}

if( !empty( $params['source'] ) )
{ // Source is defined
	echo T_('Registration Source').": ".$params['source']."\n";
}

if( $params['gender'] == 'M' )
{ // Gender is Male
	echo T_('I am').": ".T_('A man')."\n";
}
else if( $params['gender'] == 'F' )
{ // Gender is Female
	echo T_('I am').": ".T_('A woman')."\n";
}

if( !empty( $params['locale'] ) )
{ // Locale field is entered
	global $locales;
	echo T_('Locale').": ".$locales[ $params['locale'] ]['name']."\n";
}

if( !empty( $params['trigger_url'] ) )
{ // Trigger page
	echo T_('Registration Trigger Page').": ".$params['trigger_url']."\n";
}

if( !empty ( $params['initial_hit'] ) )
{ // Hit info
	echo T_('Initial page').": ".T_('Collection')." ".$params['initial_hit']->hit_coll_ID." - ".$params['initial_hit']->hit_uri."\n";
	echo T_('Initial referer').": ".$params['initial_hit']->hit_referer."\n";
}

echo "\n";

if( ! empty ( $params['level'] ) )
{	// User level:
	echo T_('Assigned Level').": ".$params['level']."\n";
}

if( ! empty ( $params['group'] ) )
{	// User group:
	echo T_('Assigned Group').": ".$params['group']."\n";
}

echo "\n";
echo T_('Edit user').': '.$admin_url.'?ctrl=user&user_tab=profile&user_ID='.$params['new_user_ID']."\n";
echo T_('Recent registrations').': '.$admin_url.'?ctrl=users&action=show_recent'."\n";

// Footer vars:
$params['unsubscribe_text'] = T_( 'If you don\'t want to receive any more notifications about new user registrations, click here:' ).' '.
		$htsrv_url.'quick_unsubscribe.php?type=user_registration&user_ID=$user_ID$&key=$unsubscribe_key$';

// ---------------------------- EMAIL FOOTER INCLUDED HERE ----------------------------
emailskin_include( '_email_footer.inc.txt.php', $params );
// ------------------------------- END OF EMAIL FOOTER --------------------------------
?>