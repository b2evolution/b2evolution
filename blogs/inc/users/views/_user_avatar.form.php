<?php

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var user permission, if user is only allowed to edit his profile
 */
global $user_profile_only;
/**
 * @var User
 */
global $edited_User;
/**
 * @var User
 */
global $current_User;
/**
 * @var current action
 */
global $action;

// Begin payload block:
$this->disp_payload_begin();

$Form = new Form( NULL, 'user_checkchanges' );

if( !$user_profile_only )
{
	$Form->global_icon( T_('Compose message'), 'comments', '?ctrl=threads&action=new&user_login='.$edited_User->login );
	$Form->global_icon( ( $action != 'view' ? T_('Cancel editing!') : T_('Close user profile!') ), 'close', regenerate_url( 'user_ID,action,ctrl', 'ctrl=users' ) );
}

$Form->begin_form( 'fform', sprintf( T_('Edit %s avatar'), $edited_User->dget('fullname').' ['.$edited_User->dget('login').']' ) );

	$Form->add_crumb( 'user' );
	$Form->hidden_ctrl();
	$Form->hidden( 'user_tab', 'avatar' );
	$Form->hidden( 'avatar_form', '1' );

	$Form->hidden( 'user_ID', $edited_User->ID );

	/***************  Avatar  **************/

$Form->begin_fieldset( T_('Avatar') );

global $admin_url;
$avatar_tag = $edited_User->get_avatar_imgtag();
if( $current_User->check_perm( 'users', 'all' ) )
{
	if( !empty( $avatar_tag ) )
	{
		$avatar_tag .= ' '.action_icon( T_( 'Remove' ), 'delete', '?ctrl=user&amp;user_tab=avatar&amp;user_ID='.$edited_User->ID.'&amp;action=remove_avatar&amp;'.url_crumb('user').'', T_( 'Remove' ) );
		if( $current_User->check_perm( 'files', 'view' ) )
		{
			$avatar_tag .= ' '.action_icon( T_( 'Change' ), 'link', '?ctrl=files&amp;user_ID='.$edited_User->ID, T_( 'Change' ).' &raquo;', 5, 5 );
		}
	}
	elseif( $current_User->check_perm( 'files', 'view' ) )
	{
		$avatar_tag .= ' '.action_icon( T_( 'Upload or choose an avatar' ), 'link', '?ctrl=files&amp;user_ID='.$edited_User->ID, T_( 'Upload/Select' ).' &raquo;', 5, 5 );
	}
}

$Form->info( T_( 'Avatar' ), $avatar_tag );

// fp> TODO: a javascript REFRAME feature would ne neat here: selecting a square area of the img and saving it as a new avatar image

$Form->end_fieldset();

$Form->end_form();

// End payload block:
$this->disp_payload_end();

/*
 * $Log$
 * Revision 1.6  2010/01/03 13:45:37  fplanque
 * set some crumbs (needs checking)
 *
 * Revision 1.5  2010/01/03 13:10:57  fplanque
 * set some crumbs (needs checking)
 *
 * Revision 1.4  2009/12/12 19:14:12  fplanque
 * made avatars optional + fixes on img props
 *
 * Revision 1.3  2009/11/21 13:39:05  efy-maxim
 * 'Cancel editing' fix
 *
 * Revision 1.2  2009/11/21 13:35:00  efy-maxim
 * log
 *
 */
?>
