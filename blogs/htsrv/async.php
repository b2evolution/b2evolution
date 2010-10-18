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
header_content_type( 'text/html', $io_charset );

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

		$text = trim( urldecode( param( 'q', 'string', '' ) ) );

		/**
		 * sam2kb> The code below decodes percent-encoded unicode string produced by Javascript "escape"
		 * function in format %uxxxx where xxxx is a Unicode value represented as four hexadecimal digits.
		 * Example string "MAMA" (cyrillic letters) encoded with "escape": %u041C%u0410%u041C%u0410
		 * Same word encoded with "encodeURI": %D0%9C%D0%90%D0%9C%D0%90
		 *
		 * jQuery hintbox plugin uses "escape" function to encode URIs
		 *
		 * More info here: http://en.wikipedia.org/wiki/Percent-encoding#Non-standard_implementations
		 */
		if( preg_match( '~%u[0-9a-f]{3,4}~i', $text ) && version_compare(PHP_VERSION, '5', '>=') )
		{	// Decode UTF-8 string (PHP 5 and up)
			$text = preg_replace( '~%u([0-9a-f]{3,4})~i', '&#x\\1;', $text );
			$text = html_entity_decode( $text, ENT_COMPAT, 'UTF-8' );
		}

		if( !empty( $text ) )
		{
			$SQL = new SQL();
			$SQL->SELECT( 'user_login' );
			$SQL->FROM( 'T_users' );
			$SQL->WHERE( 'user_login LIKE "'.$DB->escape($text).'%"' );
			$SQL->LIMIT( '10' );
			$SQL->ORDER_BY('user_login');

			echo implode("\n", $DB->get_col($SQL->get()));
		}

		exit(0);

	case 'set_comment_status':

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'comment' );

		global $blog;

		$blog = param( 'blogid', 'integer' );
		$edited_Comment = & Comment_get_by_ID( param( 'commentid', 'integer' ) );
		$moderation = param( 'moderation', 'string', NULL );
		$redirect_to = param( 'redirect_to', 'string', NULL );
		$current_User->check_perm( $edited_Comment->blogperm_name(), 'edit', true, $blog );

		$status = param( 'status', 'string' );
		$edited_Comment->set('status', $status );
		$edited_Comment->dbupdate();

		if( $moderation == NULL )
		{
			get_comments_awaiting_moderation( $blog );
		}
		else
		{
			echo_comment( $edited_Comment->ID, rawurlencode( $redirect_to ), true );
		}
		exit(0);

	case 'delete_comment':

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'comment' );

		global $blog;

		$blog = param( 'blogid', 'integer' );
		$edited_Comment = & Comment_get_by_ID( param( 'commentid', 'integer' ) );
		$current_User->check_perm( $edited_Comment->blogperm_name(), 'edit', true, $blog );

		$edited_Comment->dbdelete();

		get_comments_awaiting_moderation( $blog );
		exit(0);

	case 'delete_comments':

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'comment' );

		global $blog;

		$blog = param( 'blogid', 'integer' );
		$commentIds = param( 'commentIds', 'array' );
		$statuses = param( 'statuses', 'string', NULL );
		$item_ID = param( 'itemid', 'integer' );
		$currentpage = param( 'currentpage', 'integer', 1 );

		foreach( $commentIds as $commentID )
		{
			$edited_Comment = & Comment_get_by_ID( $commentID );
			$current_User->check_perm( $edited_Comment->blogperm_name(), 'edit', true, $blog );

			$edited_Comment->dbdelete();
		}

		if( strlen($statuses) > 2 )
		{
			$statuses = substr( $statuses, 1, strlen($statuses) - 2 );
		}
		$status_list = explode( ',', $statuses );
		if( $status_list == NULL )
		{
			$status_list = array( 'published', 'draft', 'deprecated' );
		}

		echo_item_comments( $blog, $item_ID, $status_list, $currentpage );
		exit(0);

	case 'delete_comment_url':
		// Delete spam URL from a comment directly in the dashboard - comment remains otherwise untouched
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'comment' );

		global $blog;

		$blog = param( 'blogid', 'integer' );
		$edited_Comment = & Comment_get_by_ID( param( 'commentid', 'integer' ) );
		$current_User->check_perm( $edited_Comment->blogperm_name(), 'edit', true, $blog );

		$edited_Comment->set( 'author_url', null );
		$edited_Comment->dbupdate();

		exit(0);

	case 'refresh_comments':

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'comment' );

		global $blog;

		$blog = param( 'blogid', 'integer' );

		get_comments_awaiting_moderation( $blog );
		exit(0);

	case 'refresh_item_comments':
		// refresh all item comments, or refresh all blog comments, if param itemid = -1
		load_funcs( 'items/model/_item.funcs.php' );

		$blog = param( 'blogid', 'integer' );
		$item_ID = param( 'itemid', 'integer', NULL );
		$statuses = param( 'statuses', 'string', NULL );
		$currentpage = param( 'currentpage', 'string', 1 );

		//$statuses = init_show_comments();
		if( strlen($statuses) > 2 )
		{
			$statuses = substr( $statuses, 1, strlen($statuses) - 2 );
		}
		$status_list = explode( ',', $statuses );
		if( $status_list == NULL )
		{
			$status_list = array( 'published', 'draft', 'deprecated' );
		}

		echo_item_comments( $blog, $item_ID, $status_list, $currentpage );
		exit(0);

	case 'get_tags':
		// Get list of tags, where $term matches at the beginning or anywhere (sorted)
		$Session->assert_received_crumb( 'item' ); // via item forms

		if( ! function_exists('json_encode') )
		{ // PHP 5.2 - to lazy to hunt for some backport. TODO: add backport of json_encode, e.g. into this file.
			exit(1);
		}
		$term = param('term', 'string');

		echo json_encode( $DB->get_col('
			(
			SELECT tag_name
			  FROM T_items__tag
			 WHERE tag_name LIKE '.$DB->quote($term.'%').'
			 ORDER BY tag_name
			) UNION (
			SELECT tag_name
			  FROM T_items__tag
			 WHERE tag_name LIKE '.$DB->quote('%'.$term.'%').'
			 ORDER BY tag_name
			)') );
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

	load_funcs( 'dashboard/model/_dashboard.funcs.php' );
	show_comments_awaiting_moderation( $blog_ID, $limit, array(), false );
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
 * Revision 1.60  2010/10/18 23:47:46  sam2kb
 * doc
 *
 * Revision 1.59  2010/10/17 23:12:57  sam2kb
 * Correctly decode utf-8 logins
 *
 * Revision 1.58  2010/10/05 15:33:15  efy-asimo
 * Ajax comment moderation - fix locale changes
 *
 * Revision 1.57  2010/09/28 13:03:16  efy-asimo
 * Paged comments on item full view
 *
 * Revision 1.56  2010/09/20 13:00:44  efy-asimo
 * dashboard ajax calls - fix
 *
 * Revision 1.55  2010/08/05 08:04:12  efy-asimo
 * Ajaxify comments on itemList FullView and commentList FullView pages
 *
 * Revision 1.54  2010/06/15 20:02:42  blueyed
 * async.php: add get_tags callback, to be used for tags auto completion.
 *
 * Revision 1.53  2010/06/15 19:54:28  blueyed
 * async.php: simplify get_login_list
 *
 * Revision 1.52  2010/06/01 11:33:19  efy-asimo
 * Split blog_comments advanced permission (published, deprecated, draft)
 * Use this new permissions (Antispam tool,when edit/delete comments)
 *
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