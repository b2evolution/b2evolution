<?php
/**
 * This is the HTML template of email message for new user registration
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2013 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

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


$message_additional_info = '';

if( $params['country'] > 0 )
{	// Country field is entered
	load_class( 'regional/model/_country.class.php', 'Country' );
	$CountryCache = & get_CountryCache();
	$user_Country = $CountryCache->get_by_ID( $params['country'] );
	$message_additional_info .= T_('Country').": ".$user_Country->get_name().'<br />';
}

if( $params['firstname'] != '' )
{	// First name is entered
	$message_additional_info .= T_('First name').": ".$params['firstname'].'<br />';
}

if( $params['gender'] == 'M' )
{	// Gender is Male
	$message_additional_info .= T_('I am').": ".T_('A man').'<br />';
}
else if( $params['gender'] == 'F' )
{	// Gender is Female
	$message_additional_info .= T_('I am').": ".T_('A woman').'<br />';
}

if( !empty( $params['locale'] ) )
{	// Locale field is entered
	global $locales;
	$message_additional_info .= T_('Locale').": ".$locales[ $params['locale'] ]['name'].'<br />';
}

if( !empty( $params['source'] ) )
{	// Source is defined
	$message_additional_info .= T_('Registration Source').": ".$params['source'].'<br />';
}

if( !empty( $params['trigger_url'] ) )
{	// Trigger page
	$message_additional_info .= T_('Registration Trigger Page').": ".get_link_tag( $params['trigger_url'] ).'<br />';
}

if( !empty ( $params['initial_hit'] ) )
{	// Hit info
	$message_additional_info .= T_('Initial page').": ".T_('Blog')." ".$params['initial_hit']->hit_blog_ID." - ".$params['initial_hit']->hit_uri.'<br />';
	$message_additional_info .= T_('Initial referer').": ".get_link_tag( $params['initial_hit']->hit_referer ).'<br />';
}

echo T_('New user registration').":";
echo '<br /><br />';

echo T_('Login').": ".$params['login'].'<br />';
echo T_('Email').": ".$params['email'].'<br />';
echo $message_additional_info;
echo '<br />';

echo T_('Edit user').': '.get_link_tag( $admin_url.'?ctrl=user&user_tab=profile&user_ID='.$params['new_user_ID'] ).'<br />';

echo T_('Recent registrations').': '.get_link_tag( $admin_url.'?ctrl=users&action=show_recent' ).'<br />';
echo '<br />';
echo T_( 'If you don\'t want to receive any more notifications about new user registrations, click here' ).': '
		.$htsrv_url.'quick_unsubscribe.php?type=user_registration&user_ID=$user_ID$&key=$unsubscribe_key$'.'<br />';
?>