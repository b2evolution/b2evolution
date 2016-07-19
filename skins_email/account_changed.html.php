<?php
/**
 * This is sent to ((SystemAdmins)) to notify them that a ((User)) account has been changed.
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

global $admin_url, $current_User;

// Default params:
$params = array_merge( array(
		'user_ID'           => 0,
		'fields'            => array(),
		'new_avatar_upload' => false,
		'avatar_changed'    => false,
	), $params );


echo '<p'.emailskin_style( '.p' ).'>'.sprintf( T_('There have been significant changes on this user profile made by %s'), '<b>'.$current_User->get( 'login' ).'</b>' ).':</p>'."\n";

echo '<table'.emailskin_style( 'table.email_table.bordered' ).' cellspacing="0">'."\n";
echo '<thead><tr><th'.emailskin_style( 'table.email_table.bordered thead th' ).'>'.T_('Field').'</th>'
		.'<th'.emailskin_style( 'table.email_table.bordered thead th' ).'>'.T_('Previous').'</th>'
		.'<th'.emailskin_style( 'table.email_table.bordered thead th' ).'>'.T_('New').'</th></tr></thead>'."\n";

foreach( $params['fields'] as $field_key => $field_data )
{
	$highlighted = '';
	if( $field_data['old'] != $field_data['new'] )
	{
		$td_class = emailskin_style( 'table.email_table.bordered tr.row_red td' );
	}
	else
	{
		$td_class = emailskin_style( 'table.email_table.bordered td' );
	}
	echo '<tr><th'.emailskin_style( 'table.email_table.bordered th' ).'>'.T_( $field_data['title'] ).'</th>'
			.'<td'.$td_class.'>'.( $field_data['old'] == '' ? '&nbsp;' : $field_data['old'] ).'</td>'
			.'<td'.$td_class.'>'.( $field_data['new'] == '' ? '&nbsp;' : $field_data['new'] ).'</td></tr>'."\n";
}

echo '</table>'."\n";

$UserCache = & get_UserCache();
if( $User = & $UserCache->get_by_ID( $params['user_ID'], false, false ) )
{
	$duplicated_files_message = '';
	if( $params['new_avatar_upload'] )
	{ // Get warning message about duplicated files when any new profile picture has been uploaded
		$FileCache = & get_FileCache();
		$new_File = & $FileCache->get_by_ID( $params['new_avatar_upload'] );
		$duplicated_files_message = $new_File->get_duplicated_files_message( array(
				'message'   => '<p'.emailskin_style( '.p' ).'><b'.emailskin_style( '.important' ).'>'
					.T_('WARNING: the same profile picture is used by these other users: %s.').'</b></p>'."\n",
				'use_style' => true
			) );
	}

	if( $params['avatar_changed'] )
	{ // If profile pictre has been changed
		echo '<p'.emailskin_style( '.p' ).'>'.T_('The main profile picture was changed to:').'</p>'."\n";
		echo '<p'.emailskin_style( '.p' ).'>'.$User->get_avatar_File()->get_tag( '', '', '', '', 'fit-320x320','original', '', '', '', '', '', '#', '', 1, 'none' ).'</p>'."\n";
	}
	elseif( $params['new_avatar_upload'] )
	{ // Display the newly uploaded file only if it was not set as main profile picture
		echo '<p'.emailskin_style( '.p' ).'>'.T_('A new profile picture file was uploaded:').'</p>'."\n";
		echo '<p'.emailskin_style( '.p' ).'>'.$new_File->get_tag( '', '', '', '', 'fit-320x320','original', '', '', '', '', '', '#', '', 1, 'none' ).'</p>'."\n";
	}
	// Display warning message about duplicated files 
	echo $duplicated_files_message;

	// User's pictures:
	echo '<p'.emailskin_style( '.p' ).'>'.T_('The current profile pictures for this account are:').'</p>'."\n";
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
	echo empty( $user_pictures ) ? '<p'.emailskin_style( '.p' ).'><b>'.T_('No pictures.').'</b></p>' : $user_pictures;
}

// Buttons:
echo '<div'.emailskin_style( 'div.buttons' ).'>'."\n";
echo get_link_tag( $admin_url.'?ctrl=user&user_tab=profile&user_ID='.$params['user_ID'], T_('Edit User'), 'div.buttons a+a.button_yellow' )."\n";
echo "</div>\n";

// Footer vars:
$params['unsubscribe_text'] = T_( 'If you don\'t want to receive any more notifications about user changes, click here:' )
			.' <a href="'.get_htsrv_url().'quick_unsubscribe.php?type=account_changed&user_ID=$user_ID$&key=$unsubscribe_key$"'.emailskin_style( '.a' ).'>'
			.T_('instant unsubscribe').'</a>.';

// ---------------------------- EMAIL FOOTER INCLUDED HERE ----------------------------
emailskin_include( '_email_footer.inc.html.php', $params );
// ------------------------------- END OF EMAIL FOOTER --------------------------------
?>
