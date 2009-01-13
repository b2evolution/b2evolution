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
 * @copyright (c)2003-2008 by Francois PLANQUE - {@link http://fplanque.net/}.
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

$user_ID = param( 'user_ID', 'integer', '' );
if( empty($user_ID) )
{
	echo '<p class="error">'.T_('No user specified.').'</p>';
	return;
}

$UserCache = & get_Cache( 'UserCache' );
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

	// This totally needs to move into User object
	global $DB;
	$userfields = $DB->get_results( '
		SELECT ufdf_ID, ufdf_type, ufdf_name, uf_varchar
			FROM T_users__fields LEFT JOIN T_users__fielddefs ON uf_ufdf_ID = ufdf_ID
		 WHERE uf_user_ID = '.$User->ID );

	foreach( $userfields as $userfield )
	{
		$ProfileForm->info( $userfield->ufdf_name, $userfield->uf_varchar );
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