<?php
/**
 * This file updates the current user's profile!
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package htsrv
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE
 * @author blueyed: Daniel HAHLER
 *
 *
 * @todo integrate it into the skins to avoid ugly die() on error and confusing redirect on success.
 *
 * @version $Id$
 */

/**
 * Initialize everything:
 */
require_once dirname(__FILE__).'/../conf/_config.php';

require_once $inc_path.'_main.inc.php';

global $Session;

// Check that this action request is not a CSRF hacked request:
$Session->assert_received_crumb( 'profileform' );

// Getting GET or POST parameters:
param( 'checkuser_id', 'integer', '' );
param( 'newuser_firstname', 'string', '' );
param( 'newuser_lastname', 'string', '' );
param( 'newuser_nickname', 'string', '' );
param( 'newuser_idmode', 'string', '' );
param( 'newuser_locale', 'string', $default_locale );
param( 'newuser_url', 'string', '' );
param( 'newuser_email', 'string', '' );
param( 'allow_pm', 'integer', 0 );           // checkbox
param( 'allow_email', 'integer', 0 );        // checkbox
param( 'newuser_notify', 'integer', 0 );        // checkbox
param( 'newuser_ctry_ID', 'integer', 0 );
param( 'newuser_showonline', 'integer', 0 );    // checkbox
param( 'newuser_gender', 'string', NULL );
param( 'pass1', 'string', '' );
param( 'pass2', 'string', '' );
param( 'newuser_postcode', 'string', '' );
param( 'newuser_age_min', 'string', '' );
param( 'newuser_age_max', 'string', '' );

/**
 * Basic security checks:
 */
if( ! is_logged_in() )
{ // must be logged in!
	bad_request_die( T_('You are not logged in.') );
}

if( $checkuser_id != $current_User->ID )
{ // Can only edit your own profile
	bad_request_die( 'You are not logged in under the same account you are trying to modify.' );
}

if( $demo_mode && ($current_User->ID == 1 || $current_User->login == 'demouser') )
{
	bad_request_die( 'Demo mode: you can\'t edit the admin/demouser profile!<br />[<a href="javascript:history.go(-1)">'
		. T_('Back to profile') . '</a>]' );
}


// Trigger event: a Plugin could add a $category="error" message here..
// This must get triggered before any internal validation and must pass all relevant params.
$Plugins->trigger_event( 'ProfileFormSent', array(
		'newuser_firstname' => & $newuser_firstname,
		'newuser_lastname' => & $newuser_lastname,
		'newuser_nickname' => & $newuser_nickname,
		'newuser_idmode' => & $newuser_idmode,
		'newuser_locale' => & $newuser_locale,
		'newuser_url' => & $newuser_url,
		'newuser_email' => & $newuser_email,
		'allow_pm' => & $allow_pm,
		'allow_email' => & $allow_email,
		'newuser_notify' => & $newuser_notify,
		'newuser_ctry_ID' => & $newuser_ctry_ID,
		'newuser_showonline' => & $newuser_showonline,
		'newuser_gender' => & $newuser_gender,
		'pass1' => & $pass1,
		'pass2' => & $pass2,
		'User' => & $current_User,
	) );


/**
 * Additional checks:
 */
profile_check_params( array(
	'nickname' => $newuser_nickname,
	'email' => $newuser_email,
	'url' => $newuser_url,
	'pass1' => $pass1,
	'pass2' => $pass2,
	'pass_required' => false ), $current_User );


if( $Messages->has_errors() )
{
	headers_content_mightcache( 'text/html', 0 );		// Do NOT cache error messages! (Users would not see they fixed them)

	// TODO: dh> these error should get displayed with the profile form itself, or at least there should be a "real HTML page" here (without JS-backlink)
	$Messages->display( T_('Cannot update profile. Please correct the following errors:'),
		'[<a href="javascript:history.go(-1)">' . T_('Back to profile') . '</a>]' );
	exit(0);
}


// Do the update:

$updatepassword = '';
if( !empty($pass1) )
{
	$newuser_pass = md5($pass1);
	$current_User->set( 'pass', $newuser_pass );
}

$current_User->set( 'firstname', $newuser_firstname );
$current_User->set( 'lastname', $newuser_lastname );
$current_User->set( 'nickname', $newuser_nickname );
$current_User->set_email( $newuser_email );
$current_User->set( 'url', $newuser_url );
$current_User->set( 'idmode', $newuser_idmode );
$current_User->set( 'locale', $newuser_locale );
// set allow_msgform:
// 0 - none,
// 1 - only private message,
// 2 - only email,
// 3 - private message and email
$newuser_allow_msgform = 0;
if( $allow_pm )
{ // PM is enabled
	$newuser_allow_msgform = 1;
}
if( $allow_email )
{ // email is enabled
	$newuser_allow_msgform = $newuser_allow_msgform + 2;
}
$current_User->set( 'allow_msgform', $newuser_allow_msgform );
$current_User->set( 'notify', $newuser_notify );
$current_User->set( 'ctry_ID', $newuser_ctry_ID );
$current_User->set( 'showonline', $newuser_showonline );
$current_User->set( 'gender', $newuser_gender );
$current_User->set( 'postcode', $newuser_postcode );
$current_User->set( 'age_min', $newuser_age_min );
$current_User->set( 'age_max', $newuser_age_mac );

// Set Messages into user's session, so they get restored on the next page (after redirect):
if( $current_User->dbupdate() )
{
	$Messages->add( T_('Your profile has been updated.'), 'success' );
}
else
{
	$Messages->add( T_('Your profile has not been changed.'), 'note' );
}


// redirect Will save $Messages into Session:
header_redirect();

/*
 * $Log$
 * Revision 1.71  2011/09/15 22:34:09  fplanque
 * cleanup
 *
 * Revision 1.70  2011/09/15 20:51:09  efy-abanipatra
 * user postcode,age_min,age_mac added.
 *
 * Revision 1.69  2011/09/14 23:42:16  fplanque
 * moved icq aim yim msn to additional userfields
 *
 * Revision 1.68  2011/09/04 22:13:13  fplanque
 * copyright 2011
 *
 * Revision 1.67  2010/11/25 15:16:34  efy-asimo
 * refactor $Messages
 *
 * Revision 1.66  2010/11/24 16:05:52  efy-asimo
 * User country and gender options modifications
 *
 * Revision 1.65  2010/11/24 14:55:30  efy-asimo
 * Add user gender
 *
 * Revision 1.64  2010/07/19 09:35:02  efy-asimo
 * Fix messaging permission setup
 * Update comments number per page
 *
 * Revision 1.63  2010/02/23 05:06:55  sam2kb
 * New plugin hooks: DisplayProfileFormFieldset and ProfileFormSent
 *
 * Revision 1.62  2010/02/08 17:51:14  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.61  2010/01/25 18:18:25  efy-yury
 * add : crumbs
 *
 * Revision 1.60  2009/12/04 23:27:49  fplanque
 * cleanup Expires: header handling
 *
 * Revision 1.59  2009/03/20 03:38:04  fplanque
 * rollback -- http://forums.b2evolution.net/viewtopic.php?t=18269
 *
 * Revision 1.55  2009/03/08 23:57:37  fplanque
 * 2009
 *
 * Revision 1.54  2008/09/28 08:06:03  fplanque
 * Refactoring / extended page level caching
 *
 * Revision 1.53  2008/02/19 11:11:16  fplanque
 * no message
 *
 * Revision 1.52  2008/01/21 09:35:23  fplanque
 * (c) 2008
 *
 * Revision 1.51  2007/11/24 17:34:14  blueyed
 * Add User->ID check for demo_mode where only login==demouser was checked (profile/subs update)
 *
 * Revision 1.50  2007/04/26 00:11:14  fplanque
 * (c) 2007
 *
 * Revision 1.49  2007/01/27 19:52:51  blueyed
 * Fixed charset when displaying errors
 *
 * Revision 1.48  2006/11/26 02:30:38  fplanque
 * doc / todo
 *
 * Revision 1.47  2006/11/24 18:27:22  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 *
 * Revision 1.46  2006/11/24 18:06:02  blueyed
 * Handle saving of $Messages centrally in header_redirect()
 *
 * Revision 1.45  2006/06/19 20:59:37  fplanque
 * noone should die anonymously...
 *
 * Revision 1.44  2006/04/22 02:36:38  blueyed
 * Validate users on registration through email link (+cleanup around it)
 *
 * Revision 1.43  2006/04/20 12:15:32  fplanque
 * no message
 *
 * Revision 1.42  2006/04/19 23:50:39  blueyed
 * Normalized Messages handling (error displaying and transport in Session)
 *
 * Revision 1.41  2006/04/19 20:13:48  fplanque
 * do not restrict to :// (does not catch subdomains, not even www.)
 *
 * Revision 1.40  2006/04/11 21:22:25  fplanque
 * partial cleanup
 *
 */
?>