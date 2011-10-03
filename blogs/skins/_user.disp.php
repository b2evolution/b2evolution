<?php
/**
 * This is the template that displays the user profile page.
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package evoskins
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'regional/model/_country.class.php', 'Country' );

/**
* @var Blog
*/
global $Blog;
/**
 * @var GeneralSettings
 */
global $Settings;

$user_ID = param( 'user_ID', 'integer', '' );
if( empty($user_ID) )
{	// Grab the blog owner
	$user_ID = $Blog->owner_user_ID;
}

$UserCache = & get_UserCache();
/**
 * @var User
 */
$User = & $UserCache->get_by_ID( $user_ID );

/**
 * form to update the profile
 * @var Form
 */
$ProfileForm = new Form( '', 'ProfileForm' );

$ProfileForm->begin_form( 'bComment' );

echo $User->get_avatar_imgtag( 'fit-160x160', 'rightmargin', '', true );

$ProfileForm->begin_fieldset( T_('Identity') );

	$ProfileForm->info( T_('Name'), $User->get( 'preferredname' ) );
	$ProfileForm->info( T_('Login'), $User->get_colored_name() );

	if( ! empty( $User->gender ) )
	{
		$ProfileForm->info( T_( 'I am' ), $User->get_gender() );
	}

	if( ! empty( $User->ctry_ID ) )
	{
		$CountryCache = & get_CountryCache();
		$user_Country = $CountryCache->get_by_ID( $User->ctry_ID );
		$ProfileForm->info( T_( 'Country' ), $user_Country->get_name() );
	}

	$redirect_to = url_add_param( $Blog->gen_blogurl(), 'disp=msgform&recipient_id='.$User->ID, '&' );
	$msgform_url = $User->get_msgform_url( $Blog->get('msgformurl'), $redirect_to );
	if( !empty($msgform_url) )
	{
	  $ProfileForm->info( T_('Contact'), '<a href="'.$msgform_url.'">'.T_('Send a message').'</a>' );
	}
	else
	{
	  if( is_logged_in() && $User->accepts_pm() )
	  {
	    global $current_User;
	    if( $current_User->accepts_pm() )
	    {
	      $ProfileForm->info( T_('Contact'), T_('You cannot send a private message to yourself.') );
	    }
	    else
	    {
	      $ProfileForm->info( T_('Contact'), T_('This user can only be contacted through private messages but you are not allowed to send any private messages.') );
	    }
	  }
	  else
	  {
	    $ProfileForm->info( T_('Contact'), T_('This user does not wish to be contacted directly.') );
	  }
	}

	if( !empty($User->url) )
	{
		$ProfileForm->info( T_('Website'), '<a href="'.$User->url.'" rel="nofollow" target="_blank">'.$User->url.'</a>' );
	}

$ProfileForm->end_fieldset();


$ProfileForm->begin_fieldset( T_('Additional info') );

	// Load the user fields:
	$User->userfields_load();

	// fp> TODO: have some clean iteration support
	foreach( $User->userfields as $uf_ID => $uf_array )
	{
		if( $User->userfield_defs[$uf_array[0]][0] == 'text' )
		{ // convert textarea values
			$uf_array[1] = nl2br( $uf_array[1] );
		}
		$ProfileForm->info( $User->userfield_defs[$uf_array[0]][1], $uf_array[1] );
	}

$ProfileForm->end_fieldset();


$Plugins->trigger_event( 'DisplayProfileFormFieldset', array( 'Form' => & $ProfileForm, 'User' => & $User, 'edit_layout' => 'public' ) );

// Make sure we're below the floating user avatar on the right
echo '<div class="clear"></div>';

$ProfileForm->end_form();


/*
 * $Log$
 * Revision 1.28  2011/10/03 07:18:15  efy-yurybakh
 * set girl avatar in the test install mode
 *
 * Revision 1.27  2011/09/29 17:18:18  efy-yurybakh
 * remove a pipes in textarea
 *
 * Revision 1.26  2011/09/28 10:50:00  efy-yurybakh
 * User additional info fields
 *
 * Revision 1.25  2011/09/27 17:53:59  efy-yurybakh
 * add missing rel="lightbox" in front office
 *
 * Revision 1.24  2011/09/17 02:31:58  fplanque
 * Unless I screwed up with merges, this update is for making all included files in a blog use the same domain as that blog.
 *
 * Revision 1.23  2011/09/15 22:19:10  fplanque
 * CSS cleanup
 *
 * Revision 1.22  2011/09/14 22:18:10  fplanque
 * Enhanced addition user info fields
 *
 * Revision 1.21  2011/09/04 22:13:24  fplanque
 * copyright 2011
 *
 * Revision 1.20  2011/02/21 15:25:27  efy-asimo
 * Display user gender
 *
 * Revision 1.19  2010/11/24 15:27:18  efy-asimo
 * Add country to disp=user page
 *
 * Revision 1.18  2010/07/26 06:52:27  efy-asimo
 * MFB v-4-0
 *
 * Revision 1.17  2010/07/02 08:14:19  efy-asimo
 * Messaging redirect modification and "new user get a new blog" fix
 *
 * Revision 1.16  2010/05/07 06:12:38  efy-asimo
 * small modification about messaging
 *
 * Revision 1.15  2010/05/06 10:32:17  efy-asimo
 * messaging options fix update
 *
 * Revision 1.14  2010/02/23 05:07:19  sam2kb
 * New plugin hooks: DisplayProfileFormFieldset and ProfileFormSent
 *
 * Revision 1.13  2010/02/08 17:56:14  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.12  2010/01/30 18:55:37  blueyed
 * Fix "Assigning the return value of new by reference is deprecated" (PHP 5.3)
 *
 * Revision 1.11  2010/01/25 18:18:42  efy-yury
 * add : crumbs
 *
 * Revision 1.10  2010/01/23 12:00:12  efy-yury
 * add: crumbs
 *
 * Revision 1.9  2009/12/22 23:13:39  fplanque
 * Skins v4, step 1:
 * Added new disp modes
 * Hooks for plugin disp modes
 * Enhanced menu widgets (BIG TIME! :)
 *
 * Revision 1.8  2009/09/26 12:00:44  tblue246
 * Minor/coding style
 *
 * Revision 1.7  2009/09/25 07:33:31  efy-cantor
 * replace get_cache to get_*cache
 *
 * Revision 1.6  2009/08/30 00:54:46  fplanque
 * Cleaner userfield handling
 *
 * Revision 1.5  2009/03/08 23:57:56  fplanque
 * 2009
 *
 * Revision 1.4  2009/01/13 23:45:59  fplanque
 * User fields proof of concept
 *
 * Revision 1.3  2008/10/06 01:55:06  fplanque
 * User fields proof of concept.
 * Needs UserFieldDef and UserFieldDefCache + editing of fields.
 * Does anyone want to take if from there?
 *
 * Revision 1.2  2008/09/29 08:30:39  fplanque
 * Avatar support
 *
 * Revision 1.1  2008/04/13 23:38:54  fplanque
 * Basic public user profiles
 *
 */
?>
