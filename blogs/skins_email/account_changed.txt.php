<?php
/**
 * This is sent to ((SystemAdmins)) to notify them that a ((User)) account has been changed.
 *
 * For more info about email skins, see: http://b2evolution.net/man/themes-templates-skins/email-skins/
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 *
 * @version $Id: account_changed.txt.php 7576 2014-11-05 11:28:50Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// ---------------------------- EMAIL HEADER INCLUDED HERE ----------------------------
emailskin_include( '_email_header.inc.txt.php', $params );
// ------------------------------- END OF EMAIL HEADER --------------------------------

global $admin_url, $htsrv_url, $current_User;

// Default params:
$params = array_merge( array(
		'user_ID' => 0,
		'fields'  => array(),
		'new_avatar_upload' => false,
		'avatar_changed'    => false,
	), $params );

$cell_length = 20;
$row_separator = str_repeat( '-', $cell_length * 3 + 10 )."\n";

echo sprintf( T_('There have been significant changes on this user profile made by %s'), $current_User->get( 'login' ) ).':'."\n\n";

echo $row_separator;
echo str_pad( T_('Field'), $cell_length ).' | '
		.str_pad( T_('Previous'), $cell_length ).' | '
		.str_pad( T_('New'), $cell_length )."\n";
echo $row_separator;

foreach( $params['fields'] as $field_key => $field_data )
{
	$highlighted = '';
	if( $field_data['old'] != $field_data['new'] )
	{
		$highlighted = '*';
	}
	echo str_pad( $highlighted.T_( $field_data['title'] ).$highlighted, $cell_length ).' | '
			.str_pad( $field_data['old'], $cell_length ).' | '
			.str_pad( $field_data['new'], $cell_length )."\n";
echo $row_separator;
}
echo "\n";

$UserCache = & get_UserCache();
if( $User = & $UserCache->get_by_ID( $params['user_ID'], false, false ) )
{
	if( $params['avatar_changed'] )
	{
		echo T_('The main profile picture was changed.')."\n";
	}
	elseif( $params['new_avatar_upload'] )
	{ // Display that a new file was uploaded
		echo T_('A new profile picture file was uploaded.')."\n";
	}

	// A count of user's pictures:
	echo sprintf( T_('The user has %s profile pictures.'), count( $User->get_avatar_Links( false ) ) )."\n\n";
}

// Buttons:
echo T_('Edit user').': '.$admin_url.'?ctrl=user&user_tab=profile&user_ID='.$params['user_ID']."\n";

// Footer vars:
$params['unsubscribe_text'] = T_( 'If you don\'t want to receive any more notifications about user changes, click here:' ).' '.
		$htsrv_url.'quick_unsubscribe.php?type=account_changed&user_ID=$user_ID$&key=$unsubscribe_key$';

// ---------------------------- EMAIL FOOTER INCLUDED HERE ----------------------------
emailskin_include( '_email_footer.inc.txt.php', $params );
// ------------------------------- END OF EMAIL FOOTER --------------------------------
?>
