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
 * @copyright (c)2003-2009 by Francois PLANQUE - {@link http://fplanque.net/}.
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

/**
* @var Blog
*/
global $Blog;

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
$ProfileForm = & new Form( '', 'ProfileForm' );

$ProfileForm->begin_form( 'bComment' );

echo $User->get_avatar_imgtag( 'fit-160x160', 'rightmargin' );

$ProfileForm->begin_fieldset( T_('Identity') );

	$ProfileForm->info( T_('Name'), $User->get( 'preferredname' ) );
  $ProfileForm->info( T_('Login'), $User->get('login') );

	$msgform_url = $User->get_msgform_url( $Blog->get('msgformurl') );
	if( !empty($msgform_url) )
	{
	  $ProfileForm->info( T_('Contact'), '<a href="'.$msgform_url.'">'.T_('Send a message').'</a>' );
	}
	else
	{
	  $ProfileForm->info( T_('Contact'), T_('This user does not wish to be contacted directly.') );
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
	foreach( $User->userfields as $uf_ID=>$uf_array )
	{
		$ProfileForm->info( $User->userfield_defs[$uf_array[0]][1], $uf_array[1] );
	}

$ProfileForm->end_fieldset();


$ProfileForm->begin_fieldset( T_('Miscellaneous') );

	$ProfileForm->info( T_('Locale'), $User->get( 'locale' ) );
	$ProfileForm->info( T_('Level'), $User->get('level') );
	$ProfileForm->info( T_('Posts'), $User->get('num_posts') );

$ProfileForm->end_fieldset();

$ProfileForm->end_form();


/*
 * $Log$
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