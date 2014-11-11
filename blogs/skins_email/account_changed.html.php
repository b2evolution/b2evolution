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
 * @version $Id: account_changed.html.php 7585 2014-11-06 12:18:40Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// ---------------------------- EMAIL HEADER INCLUDED HERE ----------------------------
emailskin_include( '_email_header.inc.html.php', $params );
// ------------------------------- END OF EMAIL HEADER --------------------------------

global $admin_url, $htsrv_url, $current_User;

// Default params:
$params = array_merge( array(
		'user_ID'           => 0,
		'fields'            => array(),
		'new_avatar_upload' => false,
		'avatar_changed'    => false,
	), $params );


echo '<p>'.sprintf( T_('There have been significant changes on this user profile made by %s'), '<b>'.$current_User->get( 'login' ).'</b>' ).':</p>'."\n";

echo '<table class="email_table bordered" cellspacing="0">'."\n";
echo '<thead><tr><th>'.T_('Field').'</th>'
		.'<th>'.T_('Previous').'</th>'
		.'<th>'.T_('New').'</th></tr></thead>'."\n";

foreach( $params['fields'] as $field_key => $field_data )
{
	$highlighted = '';
	if( $field_data['old'] != $field_data['new'] )
	{
		$highlighted = ' class="row_red"';
	}
	echo '<tr'.$highlighted.'><th>'.T_( $field_data['title'] ).'</th>'
			.'<td>'.( $field_data['old'] == '' ? '&nbsp;' : $field_data['old'] ).'</td>'
			.'<td>'.( $field_data['new'] == '' ? '&nbsp;' : $field_data['new'] ).'</td></tr>'."\n";
}

echo '</table>'."\n";

$UserCache = & get_UserCache();
if( $User = & $UserCache->get_by_ID( $params['user_ID'], false, false ) )
{
	if( $params['avatar_changed'] )
	{
		echo '<p>'.T_('The main profile picture was changed to:').'</p>'."\n";
		echo '<p>'.$User->get_avatar_File()->get_tag( '', '', '', '', 'fit-320x320' ).'</p>'."\n";
	}
	elseif( $params['new_avatar_upload'] )
	{ // Display the newly uploaded file only if it was not set as main profile picture
		$FileCache = & get_FileCache();
		$new_File = & $FileCache->get_by_ID( $params['new_avatar_upload'] );
		echo '<p>'.T_('A new profile picture file was uploaded:').'</p>'."\n";
		echo '<p>'.$new_File->get_tag( '', '', '', '', 'fit-320x320' ).'</p>'."\n";
	}

	// User's pictures:
	echo '<p>'.T_('The current profile pictures for this account are:').'</p>'."\n";
	$user_pictures = '';

	$user_avatars = $User->get_avatar_Links( false );
	foreach( $user_avatars as $user_Link )
	{
		$user_pictures .= $user_Link->get_tag( array(
				'before_image'        => '',
				'before_image_legend' => '',
				'after_image_legend'  => '',
				'after_image'         => ' ',
				'image_size'          => 'crop-top-160x160',
			) );
	}
	echo empty( $user_pictures ) ? '<p><b>'.T_('No pictures.').'</b></p>' : $user_pictures;
}

// Buttons:
echo '<div class="buttons">'."\n";
echo get_link_tag( $admin_url.'?ctrl=user&user_tab=profile&user_ID='.$params['user_ID'], T_('Edit User'), 'button_yellow' )."\n";
echo "</div>\n";

// Footer vars:
$params['unsubscribe_text'] = T_( 'If you don\'t want to receive any more notifications about user changes, click here:' )
			.' <a href="'.$htsrv_url.'quick_unsubscribe.php?type=account_changed&user_ID=$user_ID$&key=$unsubscribe_key$">'
			.T_('instant unsubscribe').'</a>.';

// ---------------------------- EMAIL FOOTER INCLUDED HERE ----------------------------
emailskin_include( '_email_footer.inc.html.php', $params );
// ------------------------------- END OF EMAIL FOOTER --------------------------------
?>
