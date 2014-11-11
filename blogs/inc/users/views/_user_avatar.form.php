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
/**
 * @var the action destination of the form (NULL for pagenow)
 */
global $form_action;


// Default params:
$default_params = array(
		'skin_form_params' => array(),
	);

if( isset( $params ) )
{	// Merge with default params
	$params = array_merge( $default_params, $params );
}
else
{	// Use a default params
	$params = $default_params;
}


// ------------------- PREV/NEXT USER LINKS -------------------
user_prevnext_links( array(
		'user_tab' => 'avatar'
	) );
// ------------- END OF PREV/NEXT USER LINKS -------------------


$Form = new Form( $form_action, 'user_checkchanges', 'post', NULL, 'multipart/form-data' );

$Form->switch_template_parts( $params['skin_form_params'] );

if( !$user_profile_only )
{
	echo_user_actions( $Form, $edited_User, $action );
}

$is_admin = is_admin_page();
if( $is_admin )
{
	$form_title = get_usertab_header( $edited_User, 'avatar', T_( 'Edit profile picture' ) );
	$form_class = 'fform';
	$Form->title_fmt = '<span style="float:right">$global_icons$</span><div>$title$</div>'."\n";
	$ctrl_param = '?ctrl=user&amp;user_tab=avatar&amp;user_ID='.$edited_User->ID;
}
else
{
	global $Blog;
	$form_title = '';
	$form_class = 'bComment';
	$ctrl_param = url_add_param( $Blog->gen_blogurl(), 'disp='.$disp );
}

$Form->begin_form( $form_class, $form_title );

	$Form->add_crumb( 'user' );
	if( $is_admin )
	{
		$Form->hidden_ctrl();
	}
	else
	{
		$Form->hidden( 'disp', $disp );
	}
	$Form->hidden( 'user_tab', 'avatar' );
	$Form->hidden( 'avatar_form', '1' );

	$Form->hidden( 'user_ID', $edited_User->ID );
	if( isset( $Blog ) )
	{
		$Form->hidden( 'blog', $Blog->ID );
	}

	/***************  Avatar  **************/

$Form->begin_fieldset( $is_admin ? T_('Profile picture') : '', array( 'class'=>'fieldset clear' ) );

global $admin_url;
$avatar_tag = $edited_User->get_avatar_imgtag( ( is_admin_page() ? 'fit-320x320' : 'fit-160x160' ), 'avatar', '', true, '', 'user_pictures' );
if( empty( $avatar_tag ) )
{
	if( ( $current_User->ID == $edited_User->ID ) )
	{
		$avatar_tag = T_( 'You currently have no profile picture.' );
	}
	else
	{
		$avatar_tag = T_( 'This user currently has no profile picture.' );
	}
}

if( $edited_User->has_avatar() )
{
	if( is_admin_page() )
	{
		$remove_picture_url = $ctrl_param.'&amp;action=remove_avatar&amp;'.url_crumb('user');
		$delete_picture_url = $ctrl_param.'&amp;action=delete_avatar&amp;file_ID='.$edited_User->avatar_file_ID.'&amp;'.url_crumb('user');
	}
	else
	{
		$remove_picture_url = get_secure_htsrv_url().'profile_update.php?user_tab=avatar&amp;blog='.$Blog->ID.'&amp;action=remove_avatar&amp;'.url_crumb('user');
		$delete_picture_url = get_secure_htsrv_url().'profile_update.php?user_tab=avatar&amp;blog='.$Blog->ID.'&amp;action=delete_avatar&amp;file_ID='.$edited_User->avatar_file_ID.'&amp;'.url_crumb('user');
	}

	$rotate_icons = $edited_User->get_rotate_avatar_icons( $edited_User->avatar_file_ID, array(
			'before' => '<p class="center">',
			'after'  => '</p>'
		) );

	$remove_picture_text = T_( 'No longer use this as main profile picture' );
	$delete_picture_text = T_( 'Delete this profile picture' );

	$action_picture_links = '<div>'.
			'<p class="center">'.action_icon( $remove_picture_text, 'move_down', $remove_picture_url, $remove_picture_text, 3, 4, array( 'style' => 'display:block;text-indent:-16px;padding-left:16px' ), array( 'style' => 'margin-right:4px' ) ).'</p>'.
			'<p class="center">'.action_icon( $delete_picture_text, 'xross', $delete_picture_url, $delete_picture_text, 3, 4, array( 'style' => 'display:block;text-indent:-16px;padding-left:16px', 'onclick' => 'return confirm(\''.TS_('Are you sure want to delete this picture?').'\');' ), array( 'style' => 'margin-right:4px' ) ).'</p>'.
			$rotate_icons.
		'</div>';

	$avatar_tag = '<div class="avatar_main_frame">'.$avatar_tag.$action_picture_links.'<div class="clear"></div></div>';
}

$Form->info( T_( 'Current profile picture' ), $avatar_tag );

// fp> TODO: a javascript REFRAME feature would ne neat here: selecting a square area of the img and saving it as a new avatar image

if( ( $current_User->ID == $edited_User->ID ) || ( $current_User->check_perm( 'users', 'edit' ) ) )
{
	// Upload or select:
	global $Settings;
	if( $Settings->get('upload_enabled') && ( $Settings->get( 'fm_enable_roots_user' ) ) )
	{	// Upload is enabled and we have permission to use it...
		$user_avatars = $edited_User->get_avatar_Links();
		if( count( $user_avatars ) > 0 )
		{
			$info_content = '';
			foreach( $user_avatars as $user_Link )
			{
				if( is_admin_page() )
				{
					$url_update = regenerate_url( '', 'user_tab=avatar&user_ID='.$edited_User->ID.'&action=update_avatar&file_ID='.$user_Link->File->ID.'&'.url_crumb('user'), '', '&');
					$url_delete = regenerate_url( '', 'user_tab=avatar&user_ID='.$edited_User->ID.'&action=delete_avatar&file_ID='.$user_Link->File->ID.'&'.url_crumb('user'), '', '&');
				}
				else
				{
					$url_update = get_secure_htsrv_url().'profile_update.php?user_tab=avatar&blog='.$Blog->ID.'&user_ID='.$edited_User->ID.'&action=update_avatar&file_ID='.$user_Link->File->ID.'&'.url_crumb('user');
					$url_delete = get_secure_htsrv_url().'profile_update.php?user_tab=avatar&blog='.$Blog->ID.'&user_ID='.$edited_User->ID.'&action=delete_avatar&file_ID='.$user_Link->File->ID.'&'.url_crumb('user');
				}
				$info_content .= '<div class="avatartag avatar_rounded">';
				$info_content .= $user_Link->get_tag( array(
						'before_image'        => '',
						'before_image_legend' => '',
						'after_image_legend'  => '',
						'after_image'         => '',
						'image_size'          => is_admin_page() ? 'crop-top-160x160' : 'crop-top-80x80',
						'image_link_title'    => $edited_User->login,
						'image_link_rel'      => 'lightbox[user_pictures]',
					) );
				$info_content .= '<br />'.action_icon( T_('Use as main picture'), 'move_up', $url_update, T_('Main'), 3, 4, array(), array( 'style' => 'margin-right:4px' ) );
				$info_content .= '<br />'.action_icon( T_('Delete this picture'), 'xross', $url_delete, T_('Delete'), 3, 4, array( 'onclick' => 'return confirm(\''.TS_('Are you sure want to delete this picture?').'\');' ), array( 'style' => 'margin-right:4px' ) );
				$info_content .= $edited_User->get_rotate_avatar_icons( $user_Link->File->ID );
				$info_content .= '</div>';
			}
			$Form->info( T_('Other pictures'), $info_content );
		}

		$Form->hidden( 'action', 'upload_avatar' );
		// The following is mainly a hint to the browser.
		$Form->hidden( 'MAX_FILE_SIZE', $Settings->get( 'upload_maxkb' )*1024 );

		// Upload
		$info_content = '<input name="uploadfile[]" type="file" size="10" />';
		$info_content .= '<input class="btn btn-primary ActionButton" type="submit" value="&gt; './* TRANS: action */ T_('Upload!').'" />';
		$Form->info( T_('Upload a new picture'), $info_content );
	}

	$more_content = '';

	if( $current_User->check_perm( 'files', 'view' ) )
	{
		$more_content .= '<div><a href="'.$admin_url.'?ctrl=files&amp;user_ID='.$edited_User->ID.'">';
		$more_content .= T_( 'Use the file manager to assign a new profile picture' ).'</a></div>';
	}

	if( ! empty( $more_content ) )
	{
		$Form->info( T_('More functions'), $more_content );
	}
}

$Form->end_fieldset();

$Form->end_form();

?>