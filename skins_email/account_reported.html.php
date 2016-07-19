<?php
/**
 * This is sent to ((SystemAdmins)) to notify them that a ((User)) account has been reported by another user.
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
		'login'          => '',
		'email'          => '',
		'report_status'  => '',
		'report_info'    => '',
		'user_ID'        => '',
		'reported_by'    => '', // Login of user who has reported this user account
	), $params );

echo '<p'.emailskin_style( '.p' ).'>'.sprintf( T_('A user account was reported by %s'), get_user_colored_login_link( $params['reported_by'], array( 'use_style' => true, 'protocol' => 'http:' ) ) )."</p>\n";

echo '<p'.emailskin_style( '.p' ).'>'.T_('Login').": ".get_user_colored_login_link( $params['login'], array( 'use_style' => true, 'protocol' => 'http:' ) )."</p>\n";
echo '<p'.emailskin_style( '.p' ).'>'.T_('Email').": ".$params['email']."</p>\n";
echo '<p'.emailskin_style( '.p' ).'>'.T_('Reported as').": ".$params['report_status']."</p>\n";
echo '<p'.emailskin_style( '.p' ).'>'.T_('Extra info').": ".nl2br( $params['report_info'] )."</p>\n";

// User's pictures:
echo '<p'.emailskin_style( '.p' ).'>'.T_('The current profile pictures for this account are:').'</p>'."\n";
$user_pictures = '';
$UserCache = & get_UserCache();
if( $User = $UserCache->get_by_ID( $params['user_ID'], false, false ) )
{
	$user_avatars = $User->get_avatar_Links( false );
	foreach( $user_avatars as $user_Link )
	{
		$user_pictures .= $user_Link->get_tag( array(
				'before_image'        => '',
				'before_image_legend' => '',
				'after_image_legend'  => '',
				'after_image'         => ' ',
				'image_size'          => 'crop-top-80x80',
			) );
	}
}
echo empty( $user_pictures ) ? '<p'.emailskin_style( '.p' ).'><b>'.T_('No pictures.').'</b></p>' : $user_pictures;

// Buttons:
echo '<div'.emailskin_style( 'div.buttons' ).'>'."\n";
echo get_link_tag( $admin_url.'?ctrl=user&user_tab=admin&user_ID='.$params['user_ID'], T_( 'Edit User account' ), 'div.buttons a+a.button_yellow' )."\n";
echo "</div>\n";

// Footer vars:
$params['unsubscribe_text'] = T_( 'If you don\'t want to receive any more notification when an account was reported, click here:' )
			.' <a href="'.get_htsrv_url().'quick_unsubscribe.php?type=account_reported&user_ID=$user_ID$&key=$unsubscribe_key$"'.emailskin_style( '.a' ).'>'
			.T_('instant unsubscribe').'</a>.';

// ---------------------------- EMAIL FOOTER INCLUDED HERE ----------------------------
emailskin_include( '_email_footer.inc.html.php', $params );
// ------------------------------- END OF EMAIL FOOTER --------------------------------
?>