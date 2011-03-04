<?php
/**
 * This is the template that displays the user avatar form. It gets POSTed to /htsrv/avatar_update.php.
 * 
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template.
 * To display a feedback, you should call a stub AND pass the right parameters
 * For example: /blogs/index.php?disp=avatar
 * Note: don't code this URL by hand, use the template functions to generate it!
 * 
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2010 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 * 
 * @package evoskins
 * 
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author efy-asimo: Attila Simo.
 *
 * @version $Id$
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'files/model/_filelist.class.php', 'FileList' );

global $Settings, $admin_url;

if( ! is_logged_in() )
{ // must be logged in!
	echo '<p class="error">'.T_( 'You are not logged in.' ).'</p>';
	return;
}

$redirect_to = param( 'redirect_to', 'string', '' );
$avatar_tag = $current_User->get_avatar_imgtag();

/**
 * form to update the avatar
 * @var Form
 */
$AvatarForm = new Form( $htsrv_url_sensitive.'avatar_update.php', 'AvatarForm', 'post', NULL, 'multipart/form-data' );

$AvatarForm->begin_form( 'bComment' );

	$AvatarForm->add_crumb( 'avatarform' );
	$AvatarForm->hidden( 'checkuser_id', $current_User->ID );
	$AvatarForm->hidden( 'redirect_to', url_rel_to_same_host($redirect_to, $htsrv_url_sensitive) );

	$profile_disp = ' <a href="'.get_user_profile_url().'"> &laquo; '.T_('Profile settings').'</a>';
	$AvatarForm->info( '', $profile_disp );

	$avatar_tag .= '<input name="remove_avatar" type="submit" value="'.T_( 'Remove' ).'" />';
	$AvatarForm->info( T_('Avatar'), $avatar_tag );

	if( $Settings->get('upload_enabled') )
	{
		$info_content = '<input name="uploadfile[]" type="file" size="15" />';
		$info_content .= '<input class="ActionButton" type="submit" value="&gt; '.T_('Upload!').'" />';
		$AvatarForm->info( T_('Upload a new avatar'), $info_content );
	}

	$FileRootCache = & get_FileRootCache();
	$user_FileRoot = & $FileRootCache->get_by_type_and_ID( 'user', $current_User->ID );
	$ads_list_path = get_canonical_path( $user_FileRoot->ads_path.'profile_pictures' );

	if( is_dir( $ads_list_path ) )
	{ // profile_picture folder exists in the user root dir
		$user_avatar_Filelist = new Filelist( $user_FileRoot, $ads_list_path );
		$user_avatar_Filelist->load();

		if( $user_avatar_Filelist->count() > 0 )
		{ // profile_pictures folder is not empty
			$info_content = '';
			while( $lFile = & $user_avatar_Filelist->get_next() )
			{ // Loop through all Files:
				$lFile->load_meta( true );
				if( $lFile->is_image() )
				{
					$info_content .= '<div class="avatartag">';
					$info_content .=  '<input type="image" src="'.$lFile->get_thumb_url( 'crop-64x64' ).'" name="update_avatar" value="'.$lFile->ID.'">';
					$info_content .=  '</div>';
				}
			}
			$AvatarForm->info( T_('Select a previously uploaded avatar'), $info_content );
		}
	}

$AvatarForm->end_form();


/**
 * $Log$
 * Revision 1.1  2011/03/04 08:20:45  efy-asimo
 * Simple avatar upload in the front office
 *
 */
?>