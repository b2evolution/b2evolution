<?php
/**
 * This file updates the current user's subscriptions!
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2010 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * }}
 *
 * @package htsrv
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE
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
$Session->assert_received_crumb( 'subsform' );

// Getting GET or POST parameters:
param( 'checkuser_id', 'integer', true );
param( 'newuser_email', 'string', true );
param( 'newuser_notify', 'integer', 0 );
param( 'subs_blog_IDs', 'string', true );

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

/**
 * Additional checks:
 */
profile_check_params( array( 'email' => array($newuser_email, 'newuser_email') ) );


if( $Messages->count( 'error' ) )
{
	headers_content_mightcache( 'text/html', 0 );		// Do NOT cache error messages! (Users would not see they fixed them)

	// TODO: dh> display errors with the form itself
	$Messages->display( T_('Cannot update profile. Please correct the following errors:'),
			'[<a href="javascript:history.go(-1)">' . T_('Back to profile') . '</a>]' );
	exit(0);
}


// Do the profile update:
$current_User->set_email( $newuser_email );
$current_User->set( 'notify', $newuser_notify );

$current_User->dbupdate();


// Work the blogs:
$subscription_values = array();
$unsubscribed = array();
$subs_blog_IDs = explode( ',', $subs_blog_IDs );
foreach( $subs_blog_IDs as $loop_blog_ID )
{
	// Make sure no dirty hack is coming in here:
	$loop_blog_ID = intval( $loop_blog_ID );

	// Get checkbox values:
	$sub_items    = param( 'sub_items_'.$loop_blog_ID,    'integer', 0 );
	$sub_comments = param( 'sub_comments_'.$loop_blog_ID, 'integer', 0 );

	if( $sub_items || $sub_comments )
	{	// We have a subscription for this blog
		$subscription_values[] = "( $loop_blog_ID, $current_User->ID, $sub_items, $sub_comments )";
	}
	else
	{	// No subscription here:
		$unsubscribed[] = $loop_blog_ID;
	}
}

// Note: we do not check if subscriptions are allowed here, but we check at the time we're about to send something
if( count($subscription_values) )
{	// We need to record values:
	$DB->query( 'REPLACE INTO T_subscriptions( sub_coll_ID, sub_user_ID, sub_items, sub_comments )
								VALUES '.implode( ', ', $subscription_values ) );
}

if( count($unsubscribed) )
{	// We need to make sure some values are cleared:
	$DB->query( 'DELETE FROM T_subscriptions
								 WHERE sub_user_ID = '.$current_User->ID.'
								 	 AND sub_coll_ID IN ('.implode( ', ', $unsubscribed ).')' );
}


$Messages->add( T_('Your profile & subscriptions have been updated.'), 'success' );

// redirect Will save $Messages into Session:
header_redirect();

/*
 * $Log$
 * Revision 1.30  2010/02/08 17:51:16  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.29  2010/01/28 03:42:19  fplanque
 * minor
 *
 * Revision 1.28  2010/01/25 18:18:27  efy-yury
 * add : crumbs
 *
 * Revision 1.27  2009/12/04 23:27:49  fplanque
 * cleanup Expires: header handling
 *
 * Revision 1.26  2009/03/08 23:57:37  fplanque
 * 2009
 *
 * Revision 1.25  2008/09/28 08:06:03  fplanque
 * Refactoring / extended page level caching
 *
 * Revision 1.24  2008/02/19 11:11:16  fplanque
 * no message
 *
 * Revision 1.23  2008/01/21 09:35:23  fplanque
 * (c) 2008
 *
 * Revision 1.22  2007/11/24 17:34:15  blueyed
 * Add User->ID check for demo_mode where only login==demouser was checked (profile/subs update)
 *
 * Revision 1.21  2007/04/26 00:11:14  fplanque
 * (c) 2007
 *
 * Revision 1.20  2007/01/27 19:57:12  blueyed
 * Use param_error() in profile_check_params()
 *
 * Revision 1.19  2007/01/27 19:53:17  blueyed
 * Fixed charset when displaying errors
 *
 * Revision 1.18  2006/12/16 01:30:46  fplanque
 * Setting to allow/disable email subscriptions on a per blog basis
 *
 * Revision 1.17  2006/12/16 00:38:48  fplanque
 * Cleaned up subscription db handling
 *
 * Revision 1.16  2006/11/26 02:30:38  fplanque
 * doc / todo
 *
 * Revision 1.15  2006/11/24 18:27:22  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 *
 * Revision 1.14  2006/11/24 18:06:02  blueyed
 * Handle saving of $Messages centrally in header_redirect()
 *
 * Revision 1.13  2006/06/19 20:59:37  fplanque
 * noone should die anonymously...
 *
 * Revision 1.12  2006/04/22 02:36:38  blueyed
 * Validate users on registration through email link (+cleanup around it)
 *
 * Revision 1.11  2006/04/20 12:15:32  fplanque
 * no message
 *
 * Revision 1.10  2006/04/19 23:50:39  blueyed
 * Normalized Messages handling (error displaying and transport in Session)
 *
 * Revision 1.9  2006/04/19 20:13:48  fplanque
 * do not restrict to :// (does not catch subdomains, not even www.)
 *
 * Revision 1.8  2006/04/11 21:22:25  fplanque
 * partial cleanup
 *
 */
?>