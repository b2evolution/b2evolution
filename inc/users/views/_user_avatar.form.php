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
		'skin_form_params'       => array(),
		'form_class_user_avatar' => 'bComment',
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
	$form_text_title = '<span class="nowrap">'.T_( 'Edit profile picture' ).'</span>'.get_manual_link( 'user-profile-picture-tab' ); // used for js confirmation message on leave the changed form
	$form_title = get_usertab_header( $edited_User, 'avatar', $form_text_title );
	$form_class = 'fform';
	$Form->title_fmt = '<div class="row"><span class="col-xs-12 col-lg-6 col-lg-push-6 text-right">$global_icons$</span><div class="col-xs-12 col-lg-6 col-lg-pull-6">$title$</div></div>'."\n";
	$ctrl_param = '?ctrl=user&amp;user_tab=avatar&amp;user_ID='.$edited_User->ID;
}
else
{
	global $Collection, $Blog;
	$form_title = '';
	$form_class = $params['form_class_user_avatar'];
	$ctrl_param = url_add_param( $Blog->gen_blogurl(), 'disp='.$disp );
}

$Form->begin_form( $form_class, $form_title, array( 'title' => ( isset( $form_text_title ) ? $form_text_title : $form_title ) ) );

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

$Form->begin_fieldset( $is_admin ? T_('Profile picture').get_manual_link( 'user-profile-picture-tab' ) : '', array( 'class'=>'fieldset clear' ) );

global $admin_url;
$avatar_tag = $edited_User->get_avatar_imgtag( 'fit-320x320', 'avatar', '', true, '', 'user_pictures' );
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

$can_moderate_user = $current_User->can_moderate_user( $edited_User->ID );
if( $edited_User->has_avatar() && ( $avatar_Link = & $edited_User->get_avatar_Link() ) )
{
	$action_picture_links = '';
	if( ( $current_User->ID == $edited_User->ID ) || $can_moderate_user )
	{ // Display actions only if current user can edit this user
		if( is_admin_page() )
		{
			$remove_picture_url = $ctrl_param.'&amp;action=remove_avatar&amp;'.url_crumb('user');
			$delete_picture_url = $ctrl_param.'&amp;action=delete_avatar&amp;file_ID='.$edited_User->avatar_file_ID.'&amp;'.url_crumb('user');
		}
		else
		{
			$remove_picture_url = get_htsrv_url().'profile_update.php?user_tab=avatar&amp;blog='.$Blog->ID.'&amp;action=remove_avatar&amp;'.url_crumb('user');
			$delete_picture_url = get_htsrv_url().'profile_update.php?user_tab=avatar&amp;blog='.$Blog->ID.'&amp;action=delete_avatar&amp;file_ID='.$edited_User->avatar_file_ID.'&amp;'.url_crumb('user');
		}

		$rotate_icons = $edited_User->get_rotate_avatar_icons( $edited_User->avatar_file_ID, array(
				'before' => '',
				'after'  => '<br />',
				'text'   => ' '.T_('Rotate'),
			) );

		$crop_icon = $edited_User->get_crop_avatar_icon( $edited_User->avatar_file_ID, array(
				'before'  => '',
				'after'   => '<br />',
				'text'    => ' '.T_('Crop'),
				'onclick' => 'return user_crop_avatar( '.$edited_User->ID.', '.$edited_User->avatar_file_ID.' )'
			) );

		$remove_picture_text = T_( 'No longer use this as main profile picture' );
		$delete_picture_text = T_( 'Delete this profile picture' );

		$forbid_link = '';
		$duplicated_files_message = '';
		if( is_admin_page() && $can_moderate_user )
		{ // Only if current user can edit this user
			// Allow to forbid main picture
			$forbid_picture_text = T_( 'Forbid using as main profile picture' );
			$forbid_picture_url = $ctrl_param.'&amp;action=forbid_avatar&amp;'.url_crumb('user');
			$forbid_link = action_icon( $forbid_picture_text, 'move_down_orange', $forbid_picture_url, ' '.$forbid_picture_text, 3, 4 ).'<br />';
			// Display a message about the duplicated profile picture
			$avatar_File = & $avatar_Link->get_File();
			$duplicated_files_message = $avatar_File->get_duplicated_files_message( array(
					'message' => '<p class="duplicated_avatars">'
						.get_icon( 'warning_yellow', 'imgtag', array( 'style' => 'padding-left:16px') ).' '
						.T_('Also used by: %s').'</p>'
				) );
		}

		$action_picture_links = '<div class="avatar_actions">'.
				action_icon( $remove_picture_text, 'move_down', $remove_picture_url, ' '.$remove_picture_text, 3, 4 ).'<br />'.
				$forbid_link.
				action_icon( $delete_picture_text, 'delete', $delete_picture_url, ' '.$delete_picture_text, 3, 4, array( 'onclick' => 'return confirm(\''.TS_('Are you sure want to delete this picture?').'\');' ) ).'<br />'.
				$rotate_icons.
				$crop_icon.
				$duplicated_files_message.
			'</div><div class="clear"></div>';
	}

	$avatar_tag = '<div class="avatar_main_frame">'.$avatar_tag.$action_picture_links.'</div>';
}

$Form->info( T_( 'Current profile picture' ), $avatar_tag );

// fp> TODO: a javascript REFRAME feature would ne neat here: selecting a square area of the img and saving it as a new avatar image

if( ( $current_User->ID == $edited_User->ID ) || $can_moderate_user )
{
	// Upload or select:
	global $Settings;
	if( $Settings->get('upload_enabled') && ( $Settings->get( 'fm_enable_roots_user' ) ) )
	{ // Upload is enabled and we have permission to use it...
		$user_avatars = $edited_User->get_avatar_Links();
		if( count( $user_avatars ) > 0 )
		{
			$info_content = '';
			foreach( $user_avatars as $user_Link )
			{
				$info_content .= '<div class="avatartag avatar_rounded">';
				$info_content .= $user_Link->get_tag( array(
						'before_image'        => '',
						'before_image_legend' => '',
						'after_image_legend'  => '',
						'after_image'         => '',
						'image_size'          => 'crop-top-160x160',
						'image_link_title'    => $edited_User->login,
						'image_link_rel'      => 'lightbox[user_pictures]',
					) );
				if( $user_Link->File->get( 'can_be_main_profile' ) )
				{ // Link to set picture as Main
					$url_update = is_admin_page() ?
						regenerate_url( '', 'user_tab=avatar&user_ID='.$edited_User->ID.'&action=update_avatar&file_ID='.$user_Link->File->ID.'&'.url_crumb( 'user' ), '', '&') :
						get_htsrv_url().'profile_update.php?user_tab=avatar&blog='.$Blog->ID.'&user_ID='.$edited_User->ID.'&action=update_avatar&file_ID='.$user_Link->File->ID.'&'.url_crumb( 'user' );
					$info_content .= '<br />'.action_icon( T_('Use as main picture'), 'move_up', $url_update, T_('Main'), 3, 4, array(), array( 'style' => 'margin-right:4px' ) );
				}
				elseif( is_admin_page() && $can_moderate_user )
				{ // Link to Restore picture if it was forbidden (only for admins)
					$url_restore = regenerate_url( '', 'user_tab=avatar&user_ID='.$edited_User->ID.'&action=restore_avatar&file_ID='.$user_Link->File->ID.'&'.url_crumb( 'user' ), '', '&');
					$info_content .= '<br />'.action_icon( T_('Restore to use as main picture'), 'move_up', $url_restore, T_('Restore'), 3, 4, array(), array( 'style' => 'margin-right:4px' ) );
				}
				else
				{ // Display empty line
					$info_content .= '<br />';
				}
				// Link to Delete picture
				$url_delete = is_admin_page() ?
					regenerate_url( '', 'user_tab=avatar&user_ID='.$edited_User->ID.'&action=delete_avatar&file_ID='.$user_Link->File->ID.'&'.url_crumb( 'user' ), '', '&') :
					get_htsrv_url().'profile_update.php?user_tab=avatar&blog='.$Blog->ID.'&user_ID='.$edited_User->ID.'&action=delete_avatar&file_ID='.$user_Link->File->ID.'&'.url_crumb( 'user' );
				$info_content .= '<br />'.action_icon( T_('Delete this picture'), 'delete', $url_delete, T_('Delete'), 3, 4, array( 'onclick' => 'return confirm(\''.TS_('Are you sure want to delete this picture?').'\');' ), array( 'style' => 'margin-right:4px' ) );
				// Links to rotate picture
				$info_content .= $edited_User->get_rotate_avatar_icons( $user_Link->File->ID );
				$info_content .= $edited_User->get_crop_avatar_icon( $user_Link->File->ID, array(
						'onclick' => 'return user_crop_avatar( '.$edited_User->ID.', '.$user_Link->File->ID.' )'
					) );
				if( is_admin_page() && $can_moderate_user )
				{ // Only if current user can edit this user
					// Display a message about the duplicated profile picture
					$info_content .= $user_Link->File->get_duplicated_files_message( array(
							'message' => '<div class="duplicated_avatars">'
								.get_icon( 'warning_yellow', 'imgtag', array( 'style' => 'padding-left:16px') ).' '
								.T_('Also used by: %s').'</div>'
						) );
				}
				$info_content .= '</div>';
			}
			$Form->info( T_('Other pictures'), $info_content );
		}

		$Form->hidden( 'action', 'upload_avatar' );
		// The following is mainly a hint to the browser.
		$Form->hidden( 'MAX_FILE_SIZE', $Settings->get( 'upload_maxkb' )*1024 );

		// Upload
		$Form->file_input( 'uploadfile[]', NULL, T_('Upload a new picture'), '', array( 'size' => 10 ) );

		$action_buttons = array( array( 'submit', NULL, '> '.T_('Upload!'), 'btn btn-primary ActionButton' ) );
		$Form->buttons( $action_buttons );
	}

	$more_content = '';

	if( $current_User->check_perm( 'files', 'view' ) )
	{
		$more_content .= '<a href="'.$admin_url.'?ctrl=files&amp;user_ID='.$edited_User->ID.'">';
		$more_content .= T_( 'Use the file manager to assign a new profile picture' ).'</a>';
	}

	if( ! empty( $more_content ) )
	{
		$Form->info( T_('More functions'), $more_content );
	}
}

$Form->end_fieldset();

$Form->end_form();

?>