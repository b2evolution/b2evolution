<?php
/**
 * This file updates the current user's subscriptions!
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 * {@internal
 * b2evolution is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * b2evolution is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with b2evolution; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * In addition, as a special exception, the copyright holders give permission to link
 * the code of this program with the PHP/SWF Charts library by maani.us (or with
 * modified versions of this library that use the same license as PHP/SWF Charts library
 * by maani.us), and distribute linked combinations including the two. You must obey the
 * GNU General Public License in all respects for all of the code used other than the
 * PHP/SWF Charts library by maani.us. If you modify this file, you may extend this
 * exception to your version of the file, but you are not obligated to do so. If you do
 * not wish to do so, delete this exception statement from your version.
 * }}
 *
 * @package htsrv
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: François PLANQUE
 *
 * @todo integrate it into the skins to avoid ugly die() on error and confusing redirect on success.
 *
 * @version $Id$
 */

/**
 * Initialize everything:
 */
require_once dirname(__FILE__).'/../evocore/_main.inc.php';

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
	die( T_('You are not logged in.') );
}

if( $checkuser_id != $current_User->ID )
{ // Can only edit your own profile
	die( 'You are not logged in under the same account you are trying to modify.' );
}

if( $demo_mode && ($current_User->login == 'demouser') )
{
	die( 'Demo mode: you can\'t edit the demouser profile!<br />[<a href="javascript:history.go(-1)">'
				. T_('Back to profile') . '</a>]' );
}

/**
 * Additional checks:
 */
profile_check_params( array( 'email' => $newuser_email ) );


if( $Messages->count( 'error' ) )
{
	$Messages->display( T_('Cannot update profile. Please correct the following errors:'),
			'[<a href="javascript:history.go(-1)">' . T_('Back to profile') . '</a>]' );
	die();
}


// Do the profile update:
$current_User->set( 'email', $newuser_email );
$current_User->set( 'notify', $newuser_notify );

$current_User->dbupdate();


// Work the blogs:
$values = array();
$subs_blog_IDs = explode( ',', $subs_blog_IDs );
foreach( $subs_blog_IDs as $loop_blog_ID )
{
	// Make sure no dirty hack is coming in here:
	$loop_blog_ID = intval( $loop_blog_ID );

	// Get checkbox values:
	$sub_items    = param( 'sub_items_'.$loop_blog_ID,    'integer', 0 );
	$sub_comments = param( 'sub_comments_'.$loop_blog_ID, 'integer', 0 );

	$values[] = "( $loop_blog_ID, $current_User->ID, $sub_items, $sub_comments )";
}

if( count($values) )
{	// We need to record vales:
	$DB->query( 'REPLACE INTO T_subscriptions( sub_coll_ID, sub_user_ID, sub_items, sub_comments )
								VALUES '.implode( ', ', $values ) );
}

// TODO: Redirect is confusing, as it gives no feedback to the user..
header_nocache();
header_redirect();
?>