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
 * @copyright (c)2003-2009 by Francois PLANQUE - {@link http://fplanque.net/}
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
		// fp>max TODO: is there a permission to just 'view' users? It would be appropriate here
		$current_User->check_perm( 'users', 'edit', true );

		$text = trim( param( 'q', 'string', '' ) );
		if( !empty( $text ) )
		{
			$SQL = &new SQl();
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

	case 'get_comments_awaiting_moderation':

		$blog_ID = param( 'blogid', 'integer' );
		$current_User->check_perm( 'blog_comments', 'edit', true, $blog_ID );

		$limit = 5;

		$comment_IDs = array();
		$ids = param( 'ids', 'string', NULL );
		if( !empty( $ids ) )
		{
			$comment_IDs = explode( ',', $ids );
			$limit = $limit - count( $comment_IDs );
		}

		$BlogCache = & get_BlogCache();
		$Blog = & $BlogCache->get_by_ID( $blog_ID, false, false );

		$CommentList = & new CommentList( $Blog, "'comment','trackback','pingback'", array( 'draft' ), '',	'',	'DESC',	'',	$limit, $comment_IDs );

		$new_comment_IDs = array();
		while( $Comment = & $CommentList->get_next() )
		{ // Loop through comments:
			$new_comment_IDs[] = $Comment->ID;

			echo '<div id="comment_'.$Comment->ID.'" class="dashboard_post dashboard_post_'.($CommentList->current_idx % 2 ? 'even' : 'odd' ).'">';
			echo '<div class="floatright"><span class="note status_'.$Comment->status.'">';
			$Comment->status();
			echo '</div>';

			echo '<h3 class="dashboard_post_title">';
			echo $Comment->get_title(array('author_format'=>'<strong>%s</strong>'));
			$comment_Item = & $Comment->get_Item();
			echo ' '.T_('in response to')
					.' <a href="?ctrl=items&amp;blog='.$comment_Item->get_blog_ID().'&amp;p='.$comment_Item->ID.'"><strong>'.$comment_Item->dget('title').'</strong></a>';

			echo '</h3>';

			echo '<div class="notes">';
			$Comment->rating( array(
					'before'      => '',
					'after'       => ' &bull; ',
					'star_class'  => 'top',
				) );
			$Comment->date();
			if( $Comment->author_url( '', ' &bull; Url: <span class="bUrl">', '</span>' ) )
			{
				if( $current_User->check_perm( 'spamblacklist', 'edit' ) )
				{ // There is an URL and we have permission to ban...
					// TODO: really ban the base domain! - not by keyword
					echo ' <a href="'.$dispatcher.'?ctrl=antispam&amp;action=ban&amp;keyword='.rawurlencode(get_ban_domain($Comment->author_url))
						.'">'.get_icon( 'ban' ).'</a> ';
				}
			}
			$Comment->author_email( '', ' &bull; Email: <span class="bEmail">', '</span> &bull; ' );
			$Comment->author_ip( 'IP: <span class="bIP">', '</span> &bull; ' );
			$Comment->spam_karma( T_('Spam Karma').': %s%', T_('No Spam Karma') );
			echo '</div>';
		 ?>

		<div class="small">
			<?php $Comment->content() ?>
		</div>

		<div class="dashboard_action_area">
		<?php
			// Display edit button if current user has the rights:
			$Comment->edit_link( ' ', ' ', '#', '#', 'ActionButton');

			// Display publish NOW button if current user has the rights:
			$Comment->publish_link( ' ', ' ', '#', '#', 'PublishButton', '&amp;', true, true );

			// Display deprecate button if current user has the rights:
			$Comment->deprecate_link( ' ', ' ', '#', '#', 'DeleteButton', '&amp;', true, true );

			// Display delete button if current user has the rights:
			$Comment->delete_link( ' ', ' ', '#', '#', 'DeleteButton', false, '&amp;', true, true );
		?>
		<div class="clear"></div>
		</div>

		<?php
			echo '</div>';
		}

		echo '<input type="hidden" id="comments_'.param( 'ind', 'string' ).'" value="'.implode( ',', $new_comment_IDs ).'"/>';

		exit(0);

	case 'get_comments_awaiting_moderation_number':

		$blog_ID = param( 'blogid', 'integer' );
		$current_User->check_perm( 'blog_comments', 'edit', true, $blog_ID );

		$BlogCache = & get_BlogCache();
		$Blog = & $BlogCache->get_by_ID( $blog_ID, false, false );

		$sql = 'SELECT COUNT(*)
					FROM T_comments
						INNER JOIN T_items__item ON comment_post_ID = post_ID ';

		$sql .= 'INNER JOIN T_postcats ON post_ID = postcat_post_ID
					INNER JOIN T_categories othercats ON postcat_cat_ID = othercats.cat_ID ';

		$sql .= 'WHERE '.$Blog->get_sql_where_aggregate_coll_IDs('othercats.cat_blog_ID');
		$sql .= ' AND comment_type IN (\'comment\',\'trackback\',\'pingback\') ';
		$sql .= ' AND comment_status = \'draft\'';
		$sql .= ' AND '.statuses_where_clause();

		echo $DB->get_var( $sql );

		exit(0);

	case 'set_comment_status':

		$blog_ID = param( 'blogid', 'integer' );
		$current_User->check_perm( 'blog_comments', 'edit', true, $blog_ID );

		$edited_Comment = Comment_get_by_ID( param( 'commentid', 'integer' ) );
		$status = param( 'status', 'string' );
		$edited_Comment->set('status', $status );
		$edited_Comment->dbupdate();
		echo 'OK';
		exit(0);

	case 'delete_comment':

		$blog_ID = param( 'blogid', 'integer' );
		$current_User->check_perm( 'blog_comments', 'edit', true, $blog_ID );

		$edited_Comment = Comment_get_by_ID( param( 'commentid', 'integer' ) );
		$edited_Comment->dbdelete();
		echo 'OK';
		exit(0);
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