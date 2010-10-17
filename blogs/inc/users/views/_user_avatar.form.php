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

$Form = new Form( NULL, 'user_checkchanges', 'post', NULL, 'multipart/form-data' );

if( !$user_profile_only )
{
	$Form->global_icon( T_('Delete this user!'), 'delete', '?ctrl=users&amp;action=delete&amp;user_ID='.$edited_User->ID.'&amp;'.url_crumb('user'), ' '.T_('Delete'), 3, 4  );
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
if( $current_User->check_perm( 'users', 'all' ) || ( $current_User->ID == $edited_User->ID ) )
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

if( ( $current_User->check_perm( 'users', 'all' ) ) || ( $current_User->ID == $edited_User->ID ) )
{
	// Upload or select:
	global $Settings;
	if( $Settings->get('upload_enabled') && ( $Settings->get( 'fm_enable_roots_user' ) ) )
	{	// Upload is enabled and we have permission to use it...
		load_class( 'files/model/_filelist.class.php', 'Filelist' );
		load_class( 'files/model/_fileroot.class.php', 'FileRoot' );
		$path = 'profile_pictures';

		$Form->hidden( 'ctrl', 'upload' );
		$Form->hidden( 'root', FileRoot::gen_ID( 'user', $edited_User->ID ) );
		$Form->hidden( 'path', $path );
		$Form->hidden( 'action', 'avatar_upload' );
		$Form->hidden( 'redirect_to', regenerate_url( '', 'user_tab=avatar&user_ID='.$edited_User->ID.'&action=update_avatar', '', '&') );
		// The following is mainly a hint to the browser.
		$Form->hidden( 'MAX_FILE_SIZE', $Settings->get( 'upload_maxkb' )*1024 );
		$Form->hiddens_by_key( get_memorized('ctrl') );
		$Form->add_crumb( 'file' );

		// upload part
		$info_content = '<input name="uploadfile[]" type="file" size="10" />';
		$info_content .= '<input class="ActionButton" type="submit" value="&gt; '.T_('Upload!').'" />';
		$Form->info( T_('Upload a new avatar'), $info_content );

		$FileRootCache = & get_FileRootCache();
		$user_FileRoot = & $FileRootCache->get_by_type_and_ID( 'user', $edited_User->ID );
		$ads_list_path = get_canonical_path( $user_FileRoot->ads_path.$path );

		if( is_dir( $ads_list_path ) )
		{ // profile_picture folder exists in the user root dir
			$user_avatar_Filelist = new Filelist( $user_FileRoot, $ads_list_path );
			$user_avatar_Filelist->load();

			if( $user_avatar_Filelist->count() > 0 )
			{ // profile_pictures folder is not empty
				$info_content = '';
				while( $lFile = & $user_avatar_Filelist->get_next() )
				{ // Loop through all Files:
					$lFile->load_meta();
					if( $lFile->is_image() )
					{
						$url = regenerate_url( '', 'user_tab=avatar&user_ID='.$edited_User->ID.'&action=update_avatar&file_ID='.$lFile->ID.'&'.url_crumb('user'), '', '&');
						$info_content .= '<div class="avatartag">';
						$info_content .= '<a href="'.$url.'">'.'<img '.$lFile->get_thumb_imgtag( 'crop-64x64' ).'</a>';
						$info_content .= '</div>';
					}
				}
				$Form->info( T_('Select a previously uploaded avatar'), $info_content );
			}
		}
	}
}

$Form->end_fieldset();

$Form->end_form();

// End payload block:
$this->disp_payload_end();

/*
 * $Log$
 * Revision 1.9  2010/10/17 18:53:04  sam2kb
 * Added a link to delete edited user
 *
 * Revision 1.8  2010/10/12 13:22:17  efy-asimo
 * Allow users to change their own avatars - fix
 *
 * Revision 1.7  2010/09/16 14:12:24  efy-asimo
 * New avatar upload
 *
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
