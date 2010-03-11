<?php
/**
 * This is the handler for asynchronous 'AJAX' calls.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * fp> TODO: it would be better to have the code for the actions below part of the controllers they belong to.
 * This would require some refectoring but would be better for maintenance and code clarity.
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
 * @package evocore
 *
 * @version $Id$
 */


/**
 * Do the MAIN initializations:
 */
require_once dirname(__FILE__).'/../conf/_config.php';

/**
 * HEAVY :(
 *
 * @todo dh> refactor _main.inc.php to be able to include small parts
 *           (e.g. $current_User, charset init, ...) only..
 *           It worked already for $DB (_connect_db.inc.php).
 * fp> I think I'll try _core_main.inc , _evo_main.inc , _blog_main.inc ; this file would only need _core_main.inc
 */
require_once $inc_path.'_main.inc.php';

param( 'action', 'string', '' );

// Check global permission:
if( empty($current_User) || ! $current_User->check_perm( 'admin', 'any' ) )
{	// No permission to access admin...
	require $adminskins_path.'_access_denied.main.php';
}


// Make sure the async responses are never cached:
header_nocache();


// Do not append Debuglog to response!
$debug = false;


// fp> Does the following have an HTTP fallback when Javascript/AJ is not available?
// dh> yes, but not through this file..
// dh> IMHO it does not make sense to let the "normal controller" handle the AJAX call
//     if there's something lightweight like calling "$UserSettings->param_Request()"!
//     Hmm.. bad example (but valid). Better example: something like the actions below, which
//     output only a small part of what the "real controller" does..
switch( $action )
{
	case 'add_plugin_sett_set':
		// Add a Plugin(User)Settings set (for "array" type settings):
    header_content_type( 'text/html' );

		param( 'plugin_ID', 'integer', true );

		$admin_Plugins = & get_Plugins_admin(); // use Plugins_admin, because a plugin might be disabled
		$Plugin = & $admin_Plugins->get_by_ID($plugin_ID);
		if( ! $Plugin )
		{
			bad_request_die('Invalid Plugin.');
		}
		param( 'set_type', 'string', '' ); // "Settings" or "UserSettings"
		if( $set_type != 'Settings' /* && $set_type != 'UserSettings' */ )
		{
			bad_request_die('Invalid set_type param!');
		}
		param( 'set_path', '/^\w+(?:\[\w+\])+$/', '' );

		load_funcs('plugins/_plugin.funcs.php');

		// Init the new setting set:
		_set_setting_by_path( $Plugin, $set_type, $set_path, array() );

		$r = get_plugin_settings_node_by_path( $Plugin, $set_type, $set_path, /* create: */ false );

		$Form = new Form(); // fake Form
		autoform_display_field( $set_path, $r['set_meta'], $Form, $set_type, $Plugin, NULL, $r['set_node'] );
		exit(0);

	case 'del_plugin_sett_set':
		// TODO: may use validation here..
		echo 'OK';
		exit(0);

	case 'admin_blogperms_set_layout':
		// Save blog permission tab layout into user settings. This gets called on JS-toggling.
		$UserSettings->param_Request( 'layout', 'blogperms_layout', 'string', $debug ? 'all' : 'default' );  // table layout mode
		exit(0);

	case 'set_item_link_position':
		
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'itemlink' );
		
		param('link_ID', 'integer', true);
		param('link_position', 'string', true);

		$LinkCache = & get_LinkCache();
		$Link = & $LinkCache->get_by_ID($link_ID);

		if( $Link->set('position', $link_position)
			&& $Link->dbupdate() )
		{
			echo 'OK';
		}
		else
		{ // return the current value on failure
			echo $Link->get('position');
		}
		exit(0);

	case 'get_login_list':
		// fp> TODO: is there a permission to just 'view' users? It would be appropriate here
		$current_User->check_perm( 'users', 'edit', true );

		$text = trim( param( 'q', 'string', '' ) );
		if( !empty( $text ) )
		{
			$SQL = new SQL();
			$SQL->SELECT( 'user_login' );
			$SQL->FROM( 'T_users' );
			$SQL->WHERE( 'user_login LIKE \''.$text.'%\'' );
			$SQL->LIMIT( '10' );
			$SQL->ORDER_BY('user_login');

			$options = '';
			foreach( $DB->get_results( $SQL->get() ) as $row )
			{
				$options .= $row->user_login."\n";
			}
			echo $options;
		}

		exit(0);

	case 'set_comment_status':
		
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'comment' );

		global $blog;

		$blog = param( 'blogid', 'integer' );
		$current_User->check_perm( 'blog_comments', 'edit', true, $blog );

		$edited_Comment = & Comment_get_by_ID( param( 'commentid', 'integer' ) );
		$status = param( 'status', 'string' );
		$edited_Comment->set('status', $status );
		$edited_Comment->dbupdate();

		get_comments_awaiting_moderation( $blog );
		exit(0);

	case 'delete_comment':
		
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'comment' );
		
		global $blog;

		$blog = param( 'blogid', 'integer' );
		$current_User->check_perm( 'blog_comments', 'edit', true, $blog );

		$edited_Comment = & Comment_get_by_ID( param( 'commentid', 'integer' ) );
		$edited_Comment->dbdelete();

		get_comments_awaiting_moderation( $blog );
		exit(0);

	case 'delete_comment_url':
		// Delete spam URL from a comment directly in the dashboard - comment remains otherwise untouched
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'comment' );
		
		global $blog;
		
		$blog = param( 'blogid', 'integer' );
		$current_User->check_perm( 'blog_comments', 'edit', true, $blog );
		
		$edited_Comment = & Comment_get_by_ID( param( 'commentid', 'integer' ) );
		$edited_Comment->set( 'author_url', null );
		$edited_Comment->dbupdate();
		
		exit(0);
	
	case 'refresh_comments':
		
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'comment' );
		
		global $blog;

		$blog = param( 'blogid', 'integer' );
		$current_User->check_perm( 'blog_comments', 'edit', true, $blog );
		
		get_comments_awaiting_moderation( $blog );
		exit(0);
}


/**
 * Get comments awaiting moderation
 *
 * @param integer blog_ID
 */
function get_comments_awaiting_moderation( $blog_ID )
{
	$limit = 5;

	$comment_IDs = array();
	$ids = param( 'ids', 'string', NULL );
	if( !empty( $ids ) )
	{
		$comment_IDs = explode( ',', $ids );
		$limit = $limit - count( $comment_IDs );
	}

	load_funcs( 'dashboard/model/_dashboard.funcs.php' );
	show_comments_awaiting_moderation( $blog_ID, $limit, $comment_IDs, false );
}


/**
 * Call the handler/dispatcher (it is a common handler for asynchronous calls -- both AJax calls and HTTP GET fallbacks)
 */
require_once $inc_path.'_async.inc.php';


// Debug info:
echo '-expand='.$expand;
echo '-collapse='.$collapse;

/*
 * $Log$
 * Revision 1.51  2010/03/11 10:34:21  efy-asimo
 * Rewrite CommentList to CommentList2 task
 *
 * Revision 1.50  2010/03/02 12:37:08  efy-asimo
 * remove show_comments_awaiting_moderation function from _misc_funcs.php to _dashboard.func.php
 *
 * Revision 1.49  2010/02/28 23:38:38  fplanque
 * minor changes
 *
 * Revision 1.48  2010/02/26 21:23:52  fplanque
 * rollback - did not seem right
 *
 * Revision 1.47  2010/02/26 08:34:33  efy-asimo
 * dashboard -> ban icon should be javascripted task
 *
 * Revision 1.46  2010/02/09 17:20:33  efy-yury
 * &new -> new
 *
 * Revision 1.45  2010/02/08 17:50:53  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.44  2010/01/31 17:39:41  efy-asimo
 * delete url from comments in dashboard and comments form
 *
 * Revision 1.43  2010/01/30 10:29:05  efy-yury
 * add: crumbs
 *
 * Revision 1.42  2010/01/30 03:40:11  fplanque
 * minor
 *
 * Revision 1.41  2010/01/29 17:21:37  efy-yury
 * add: crumbs in ajax calls
 *
 * Revision 1.40  2009/12/10 21:32:47  efy-maxim
 * 1. single ajax call
 * 2. comments of protected post fix
 *
 * Revision 1.39  2009/12/04 23:27:49  fplanque
 * cleanup Expires: header handling
 *
 * Revision 1.38  2009/12/03 11:38:37  efy-maxim
 * ajax calls have been improved
 *
 * Revision 1.37  2009/12/02 00:05:52  fplanque
 * no message
 *
 * Revision 1.36  2009/12/01 13:56:57  efy-maxim
 * check permissions
 *
 * Revision 1.35  2009/11/30 00:22:04  fplanque
 * clean up debug info
 * show more timers in view of block caching
 *
 * Revision 1.34  2009/11/27 12:29:04  efy-maxim
 * drop down
 *
 * Revision 1.33  2009/11/26 10:30:52  efy-maxim
 * ajax actions have been moved to async.php
 *
 * Revision 1.32  2009/10/17 14:49:46  fplanque
 * doc
 *
 * Revision 1.31  2009/10/11 03:00:10  blueyed
 * Add "position" and "order" properties to attachments.
 * Position can be "teaser" or "aftermore" for now.
 * Order defines the sorting of attachments.
 * Needs testing and refinement. Upgrade might work already, be careful!
 *
 * Revision 1.30  2009/09/25 07:32:51  efy-cantor
 * replace get_cache to get_*cache
 *
 * Revision 1.29  2009/03/08 23:57:35  fplanque
 * 2009
 *
 * Revision 1.28  2008/09/28 08:06:03  fplanque
 * Refactoring / extended page level caching
 *
 * Revision 1.27  2008/02/19 11:11:16  fplanque
 * no message
 *
 * Revision 1.26  2008/01/21 09:35:23  fplanque
 * (c) 2008
 *
 * Revision 1.25  2007/12/23 20:10:49  fplanque
 * removed suspects
 *
 * Revision 1.24  2007/06/25 10:58:49  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.23  2007/06/19 20:41:10  fplanque
 * renamed generic functions to autoform_*
 *
 * Revision 1.22  2007/06/19 00:03:27  fplanque
 * doc / trying to make sense of automatic settings forms generation.
 *
 * Revision 1.21  2007/04/26 00:11:14  fplanque
 * (c) 2007
 *
 * Revision 1.20  2006/12/06 23:32:34  fplanque
 * Rollback to Daniel's most reliable password hashing design. (which is not the last one)
 * This not only strengthens the login by providing less failure points, it also:
 * - Fixes the login in IE7
 * - Removes the double "do you want to memorize this password' in FF.
 *
 * Revision 1.19  2006/12/05 01:04:03  blueyed
 * Fixed add_plugin_sett_set AJAX callback
 *
 * Revision 1.18  2006/12/04 00:18:52  fplanque
 * keeping the login hashing
 *
 * Revision 1.15  2006/12/03 18:18:17  blueyed
 * doc
 *
 * Revision 1.14  2006/12/02 22:57:37  fplanque
 * Not releasable. Discussion by email.
 *
 * Revision 1.13  2006/11/29 03:25:53  blueyed
 * Enhanced password hashing during login: get the password salt through async request + cleanup
 *
 * Revision 1.12  2006/11/28 01:10:46  blueyed
 * doc/discussion
 *
 * Revision 1.11  2006/11/28 00:47:16  fplanque
 * doc
 *
 * Revision 1.10  2006/11/24 18:27:22  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 *
 * Revision 1.9  2006/11/18 01:27:39  blueyed
 * Always include jQuery in backoffice (it gets cached and can now be used anywhere freely); Update $UserSettings from (blogperms_)toggle_layout (this and related JS moved out of _menutop.php)
 *
 * Revision 1.8  2006/11/16 23:43:39  blueyed
 * - "key" entry for array-type Plugin(User)Settings can define an input field for the key of the settings entry
 * - cleanup
 *
 * Revision 1.7  2006/11/15 22:03:17  blueyed
 * Use Plugins_admin, because a Plugin might be disabled, when editing its settings
 *
 * Revision 1.6  2006/11/10 16:37:30  blueyed
 * Send charset
 *
 * Revision 1.5  2006/11/09 23:40:57  blueyed
 * Fixed Plugin UserSettings array type editing; Added jquery and use it for AJAHifying Plugin (User)Settings editing of array types
 *
 * Revision 1.4  2006/11/02 18:14:59  fplanque
 * normalized
 *
 * Revision 1.3  2006/11/02 02:04:08  blueyed
 * QUESTION
 *
 * Revision 1.2  2006/10/14 04:43:55  blueyed
 * MFB: E_FATAL for anon user
 *
 * Revision 1.1  2006/06/01 19:06:27  fplanque
 * a taste of Ajax in the framework
 *
 */
?>