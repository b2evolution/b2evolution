<?php
/**
 * This is the handler for asynchronous 'AJAX' calls.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * fp> TODO: it would be better to have the code for the actions below part of the controllers they belong to.
 * This would require some refectoring but would be better for maintenance and code clarity.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evocore
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

// create global $blog variable
global $blog;
// Init $blog with NULL to avoid injections, it will get the correct value from param where it is required
$blog = NULL;

param( 'action', 'string', '' );

// Check global permission:
if( $action != 'test_api' && ( empty($current_User) || ! $current_User->check_perm( 'admin', 'restricted' ) ) )
{	// No permission to access admin... (Exclude action of API testing in order to make a quick request without logging in)
	require $adminskins_path.'_access_denied.main.php';
}

// Send the predefined cookies:
evo_sendcookies();

// Make sure the async responses are never cached:
header_nocache();
header_content_type( 'text/html', $io_charset );

// Save current debug values
$current_debug = $debug;
$current_debug_jslog = $debug_jslog;

// Do not append Debuglog to response!
$debug = false;

// Do not append Debug JSlog to response!
$debug_jslog = false;

// Don't check new updates from b2evolution.net (@see b2evonet_get_updates()),
// in order to don't break the response data:
$allow_evo_stats = false;

// Init AJAX log
$Ajaxlog = new Log();

$Ajaxlog->add( sprintf( T_('action: %s'), $action ), 'note' );

$incorrect_action = false;

$add_response_end_comment = true;

// fp> Does the following have an HTTP fallback when Javascript/AJ is not available?
// dh> yes, but not through this file..
// dh> IMHO it does not make sense to let the "normal controller" handle the AJAX call
//     if there's something lightweight like calling "$UserSettings->param_Request()"!
//     Hmm.. bad example (but valid). Better example: something like the actions below, which
//     output only a small part of what the "real controller" does..
switch( $action )
{
	case 'get_whois_info':
		param( 'query', 'string' );
		param( 'window_height', 'integer' );

		load_class('_ext/phpwhois/whois.main.php', 'whois' );

		$whois = new Whois();

		// Set to true if you want to allow proxy requests
		$allowproxy = false;

		// get faster but less acurate results
		$whois->deep_whois = empty( $_GET['fast'] );

		// To use special whois servers (see README)
		//$whois->UseServer( 'uk', 'whois.nic.uk:1043?{hname} {ip} {query}' );
		//$whois->UseServer( 'au', 'whois-check.ausregistry.net.au' );

		// Comment the following line to disable support for non ICANN tld's
		$whois->non_icann = true;

		$result = $whois->Lookup( $query );

		$winfo = '<pre style="height: '.( $window_height - 200 ).'px; overflow: auto;">';
		if( ! empty( $result['rawdata'] ) )
		{
			for( $i = 0; $i < count( $result['rawdata'] ); $i++ )
			{
				// Highlight lines starting with orgname: or org-name: (case insensitive)
				if( preg_match( '/^(orgname:|org-name:|descr:)/i', $result['rawdata'][$i] ) )
				{
					$result['rawdata'][$i] = '<span style="font-weight: bold; background-color: yellow;">'.$result['rawdata'][$i].'</span>';
				}

				if( preg_match_all( '#[-a-zA-Z0-9@:%_\+.~\#?&//=]{2,256}\.[a-z]{2,4}\b(\/[-a-zA-Z0-9@:%_\+.~\#?&//=;]*)?#si', $result['rawdata'][$i], $matches ) )
				{
					foreach( $matches as $match )
					{
						if( filter_var( $match[0], FILTER_VALIDATE_EMAIL ) )
						{ // check if valid email
							$href_string = 'mailto://'.$match[0];
							$result['rawdata'][$i] = str_replace( $match[0], '<a href="'.$href_string.'" target="_blank">'.$match[0].'</a>', $result['rawdata'][$i] );
						}
						else
						{ // check if valid URL
							$href_string = ( ! preg_match( '#^(ht|f)tps?://#', $match[0] ) ) // check if protocol not present
									? 'http://' . $match[0] // temporarily add one
									: $match[0]; // use current
							if( filter_var( $href_string, FILTER_VALIDATE_URL ) )
							{
								$result['rawdata'][$i] = str_replace( $match[0], '<a href="'.$href_string.'" target="_blank">'.$match[0].'</a>', $result['rawdata'][$i] );
							}
						}
					}
				}
			}
			$winfo .= format_to_output( implode( $result['rawdata'], "\n" ) );
		}
		else
		{
			$winfo = format_to_output( implode( $whois->Query['errstr'], "\n" ) )."<br></br>";
		}
		$winfo .= '</pre>';

		echo $winfo;
		break;

	case 'add_plugin_sett_set':
		// Dislay a new Plugin(User)Settings set ( it's used only from plugins with "array" type settings):

		// This does not require CSRF because it doesn't update the db, it only displays a new block of empty plugin setting fields

		// Check permission to view plugin settings:
		$current_User->check_perm( 'options', 'view', true );

		// Set admin skin, used for buttons, @see button_class()
		$admin_skin = $UserSettings->get( 'admin_skin', $current_User->ID );
		require_once $adminskins_path.$admin_skin.'/_adminUI.class.php';
		$AdminUI = new AdminUI();

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

		// Get the new plugin setting set and display it with a fake Form
		$r = get_plugin_settings_node_by_path( $Plugin, $set_type, $set_path, /* create: */ false );

		$Form = new Form(); // fake Form to display plugin setting
		autoform_display_field( $set_path, $r['set_meta'], $Form, $set_type, $Plugin, NULL, $r['set_node'] );
		break;

	case 'edit_comment':
		// Used to edit a comment from back-office (Note: Only for meta comments now!)

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'comment' );

		$comment_ID = param( 'commentid', 'integer' );

		// Get Comment by ID
		$CommentCache = & get_CommentCache();
		$edited_Comment = & $CommentCache->get_by_ID( $comment_ID );
		// Load Item
		$edited_Comment_Item = & $edited_Comment->get_Item();

		// Check user permission to edit this meta comment
		$current_User->check_perm( 'meta_comment', 'edit', true, $edited_Comment );

		// Load Blog of the Item
		$Collection = $Blog = & $edited_Comment_Item->get_Blog();

		$comment_action = param( 'comment_action', 'string' );

		switch( $comment_action )
		{
			case 'form':
				// Display a form to change a content of the Comment
				$comment_title = '';
				$comment_content = htmlspecialchars_decode( $edited_Comment->content );

				// Format content for editing, if we were not already in editing...
				$Plugins_admin = & get_Plugins_admin();
				$params = array( 'object_type' => 'Comment', 'object_Blog' => & $Blog );
				$Plugins_admin->unfilter_contents( $comment_title /* by ref */, $comment_content /* by ref */, $edited_Comment_Item->get_renderers_validated(), $params );

				// Display <textarea> to change a content
				$rows_approx = round( strlen( $comment_content ) / 200 );
				$rows_number = substr_count( $comment_content, "\n" );
				$rows_number = ( ( $rows_number > 3 && $rows_number > $rows_approx ) ? $rows_number : $rows_approx ) + 2;
				echo '<textarea class="form_textarea_input form-control" rows="'.$rows_number.'">'.$comment_content.'</textarea>';
				// Display a button to Save the changes
				echo '<input type="button" value="'.T_('Save Changes!').'" class="SaveButton btn btn-primary" onclick="edit_comment( \'update\', '.$edited_Comment->ID.' )" /> ';
				// Display a button to Cancel the changes
				echo '<input type="button" value="'.T_('Cancel').'" class="ResetButton btn btn-danger" onclick="edit_comment( \'cancel\', '.$edited_Comment->ID.' )" />';
				break;

			case 'update':
				// Save the changed content
				if( $Blog->get_setting( 'allow_html_comment' ) )
				{ // HTML is allowed for this comment
					$text_format = 'html';
				}
				else
				{ // HTML is disallowed for this comment
					$text_format = 'htmlspecialchars';
				}

				$comment_content = param( 'comment_content', $text_format );

				// Trigger event: a Plugin could add a $category="error" message here..
				// This must get triggered before any internal validation and must pass all relevant params.
				// The OpenID plugin will validate a given OpenID here (via redirect and coming back here).
				$Plugins->trigger_event( 'CommentFormSent', array(
						'dont_remove_pre' => true,
						'comment_item_ID' => $edited_Comment_Item->ID,
						'comment' => & $comment_content,
						'renderers' => $edited_Comment->get_renderers_validated(),
					) );

				// Update the content
				$edited_Comment->set( 'content', $comment_content );
				$edited_Comment->dbupdate();

				// Display new content
				$edited_Comment->content( 'htmlbody', 'true' );
				break;

			case 'cancel':
				// The changes were canceled, Display old content
				$edited_Comment->content( 'htmlbody', 'true' );
				break;
		}

		break;

	case 'get_opentrash_link':
		// Used to get a link 'Open recycle bin' in order to show it in the header of comments list

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'comment' );

		// Set admin skin, used for buttons, @see button_class()
		$admin_skin = $UserSettings->get( 'admin_skin', $current_User->ID );
		require_once $adminskins_path.$admin_skin.'/_adminUI.class.php';
		$AdminUI = new AdminUI();

		param( 'blog', 'integer', 0 );

		echo get_opentrash_link( true, true, array(
				'before' => ' <span id="recycle_bin">',
				'after' => '</span>',
				'class' => 'btn btn-default'
			) );
		break;

	case 'delete_comment':
		// Delete a comment from the list on dashboard, on comments full text view screen or on a view item screen

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'comment' );

		$result_success = false;

		// Set admin skin, used for buttons, @see button_class()
		$admin_skin = $UserSettings->get( 'admin_skin', $current_User->ID );
		require_once $adminskins_path.$admin_skin.'/_adminUI.class.php';
		$AdminUI = new AdminUI();

		// Check comment moderate permission below after we have the $edited_Comment objects

		$is_admin_page = true;
		$blog = param( 'blogid', 'integer' );
		$comment_ID = param( 'commentid', 'integer' );
		$statuses = param( 'statuses', 'string', NULL );
		$expiry_status = param( 'expiry_status', 'string', 'active' );
		$item_ID = param( 'itemid', 'integer' );
		$currentpage = param( 'currentpage', 'integer', 1 );
		$limit = param( 'limit', 'integer', 0 );
		$request_from = param( 'request_from', 'string', NULL );
		$comment_type = param( 'comment_type', 'string', 'feedback' );

		$edited_Comment = & Comment_get_by_ID( $comment_ID, false );
		if( $edited_Comment !== false )
		{ // The comment still exists
			// Check permission:
			$current_User->check_perm( 'comment!CURSTATUS', 'delete', true, $edited_Comment );

			$result_success = $edited_Comment->dbdelete();
		}

		if( $result_success === false )
		{ // Some errors on deleting of the comment, Exit here
			header_http_response( '500 '.T_('Comment cannot be deleted!'), 500 );
			exit(0);
		}

		if( in_array( $request_from, array( 'items', 'comments' ) ) )
		{	// AJAX request goes from backoffice and ctrl = items or comments:

			// In case of comments_fullview we must set a filterset name to be abble to restore filterset.
			// If $item_ID is not valid, then this requests came from the comments_fullview
			// TODO: asimo> This should be handled with a better solution
			$filterset_name = /*'';*/( $item_ID > 0 ) ? '' : 'fullview';

			echo_item_comments( $blog, $item_ID, $statuses, $currentpage, $limit, array(), $filterset_name, $expiry_status, $comment_type );
		}
		break;

	case 'delete_comment_url':
		// Delete spam URL from a comment directly in the dashboard - comment remains otherwise untouched

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'comment' );

		// Check comment edit permission below after we have the $edited_Comment object

		$blog = param( 'blogid', 'integer' );
		$edited_Comment = & Comment_get_by_ID( param( 'commentid', 'integer' ), false );
		if( $edited_Comment !== false && $edited_Comment->author_url != NULL )
		{	// The comment still exists
			// Check permission:
			$current_User->check_perm( 'comment!CURSTATUS', 'edit', true, $edited_Comment );

			$edited_Comment->set( 'author_url', NULL );
			$edited_Comment->dbupdate();
		}

		break;

	case 'refresh_comments':
		// Refresh the comments list on dashboard by clicking on the refresh icon or after ban url
		// Refresh item comments on the item view screen, or refresh all blog comments on comments view, if param itemid = -1
		// A refresh is used on the actions:
		// 1) click on the refresh icon.
		// 2) limit by selected status(radioboxes 'Draft', 'Published', 'All comments').
		// 3) ban by url of a comment

		$is_admin_page = true;
		$blog = param( 'blogid', 'integer' );
		$item_ID = param( 'itemid', 'integer', NULL );
		$statuses = param( 'statuses', 'string', NULL );
		$expiry_status = param( 'expiry_status', 'string', 'active' );
		$currentpage = param( 'currentpage', 'string', 1 );
		$request_from = param( 'request_from', 'string', 'items' );
		$comment_type = param( 'comment_type', 'string', 'feedback' );

		// Check minimum permissions ( The comment specific permissions are checked when displaying the comments )
		$current_User->check_perm( 'blog_ismember', 'view', true, $blog );

		// Set admin skin, used for buttons, @see button_class()
		$admin_skin = $UserSettings->get( 'admin_skin', $current_User->ID );
		require_once $adminskins_path.$admin_skin.'/_adminUI.class.php';
		$AdminUI = new AdminUI();

		if( in_array( $request_from, array( 'items', 'comments' ) ) )
		{	// AJAX request goes from backoffice and ctrl = items or comments
			echo_item_comments( $blog, $item_ID, $statuses, $currentpage, NULL, array(), '', $expiry_status, $comment_type );
		}
		elseif( $request_from == 'dashboard' || $request_from == 'coll_settings' )
		{ // AJAX request goes from backoffice dashboard
			load_funcs( 'dashboard/model/_dashboard.funcs.php' );
			show_comments_awaiting_moderation( $blog, NULL, 10, array(), false );
		}
		break;

	case 'dom_type_edit':
		// Update type of a reffering domain from list screen by clicking on the type column

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'domtype' );

		// Check permission:
		$current_User->check_perm( 'stats', 'edit', true );

		load_funcs('sessions/model/_hitlog.funcs.php');

		$dom_type = param( 'new_dom_type', 'string' );
		$dom_name = param( 'dom_name', 'string' );

		$DB->query( 'UPDATE T_basedomains
						SET dom_type = '.$DB->quote($dom_type).'
						WHERE dom_name =' . $DB->quote($dom_name));
		echo '<a href="#" rel="'.$dom_type.'">'.stats_dom_type_title( $dom_type ).'</a>';
		break;

	case 'dom_status_edit':
		// Update status of a reffering domain from list screen by clicking on the type column

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'domstatus' );

		// Check permission:
		$current_User->check_perm( 'stats', 'edit', true );

		load_funcs('sessions/model/_hitlog.funcs.php');

		$dom_status = param( 'new_dom_status', 'string' );
		$dom_name = param( 'dom_name', 'string' );

		$DB->query( 'UPDATE T_basedomains
						SET dom_status = '.$DB->quote($dom_status).'
						WHERE dom_name =' . $DB->quote($dom_name));
		echo '<a href="#" rel="'.$dom_status.'" color="'.stats_dom_status_color( $dom_status ).'">'.stats_dom_status_title( $dom_status ).'</a>';
		break;

	case 'iprange_status_edit':
		// Update status of IP range from list screen by clicking on the status column

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'iprange' );

		// Check permission:
		$current_User->check_perm( 'spamblacklist', 'edit', true );

		$new_status = param( 'new_status', 'string' );
		$iprange_ID = param( 'iprange_ID', 'integer', true );

		$DB->query( 'UPDATE T_antispam__iprange
						SET aipr_status = '.( empty( $new_status ) ? 'NULL' : $DB->quote( $new_status ) ).'
						WHERE aipr_ID =' . $DB->quote( $iprange_ID ) );
		echo '<a href="#" rel="'.$new_status.'" color="'.aipr_status_color( $new_status ).'">'.aipr_status_title( $new_status ).'</a>';
		break;

	case 'emadr_status_edit':
		// Update status of email address

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'emadrstatus' );

		// Check permission:
		$current_User->check_perm( 'emails', 'edit', true );

		$new_status = param( 'new_status', 'string' );
		$emadr_ID = param( 'emadr_ID', 'integer', true );

		load_funcs('tools/model/_email.funcs.php');

		$DB->query( 'UPDATE T_email__address
						SET emadr_status = '.( empty( $new_status ) ? 'NULL' : $DB->quote( $new_status ) ).'
						WHERE emadr_ID =' . $DB->quote( $emadr_ID ) );
		echo '<a href="#" rel="'.$new_status.'" color="'.emadr_get_status_color( $new_status ).'">'.emadr_get_status_title( $new_status ).'</a>';
		break;

	case 'user_level_edit':
		// Update level of an user from list screen by clicking on the level column

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'userlevel' );

		$user_level = param( 'new_user_level', 'integer' );
		$user_ID = param( 'user_ID', 'integer' );

		// Check permission:
		$current_User->can_moderate_user( $user_ID, true );

		$UserCache = & get_UserCache();
		if( $User = & $UserCache->get_by_ID( $user_ID, false ) )
		{
			$User->set( 'level', $user_level );
			$User->dbupdate();
			echo '<a href="#" rel="'.$user_level.'">'.$user_level.'</a>';
		}
		break;

	case 'group_level_edit':
		// Update level of a group from list screen by clicking on the level column

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'grouplevel' );

		// Check permission:
		$current_User->check_perm( 'users', 'edit', true );

		$group_level = param( 'new_group_level', 'integer' );
		$group_ID = param( 'group_ID', 'integer' );

		$GroupCache = & get_GroupCache();
		if( $Group = & $GroupCache->get_by_ID( $group_ID, false, false ) )
		{
			$Group->set( 'level', $group_level );
			$Group->dbupdate();
			echo '<a href="#" rel="'.$group_level.'">'.$group_level.'</a>';
		}
		break;

	case 'country_status_edit':
		// Update status of Country from list screen by clicking on the status column

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'country' );

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		load_funcs( 'regional/model/_regional.funcs.php' );

		$new_status = param( 'new_status', 'string' );
		$ctry_ID = param( 'ctry_ID', 'integer', true );

		$DB->query( 'UPDATE T_regional__country
						SET ctry_status = '.( empty( $new_status ) ? 'NULL' : $DB->quote( $new_status ) ).'
						WHERE ctry_ID =' . $DB->quote( $ctry_ID ) );
		echo '<a href="#" rel="'.$new_status.'" color="'.ctry_status_color( $new_status ).'">'.ctry_status_title( $new_status ).'</a>';
		break;

	case 'item_task_edit':
		// Update task fields of Item from list screen by clicking on the cell

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'itemtask' );

		$field = param( 'field', 'string' );
		if( ! in_array( $field, array( 'priority', 'status', 'assigned' ) ) )
		{ // Invalid field
			$Ajaxlog->add( sprintf( 'Invalid field: %s', $field ), 'error' );
			break;
		}

		$post_ID = param( 'post_ID', 'integer', true );

		$ItemCache = & get_ItemCache();
		$Item = & $ItemCache->get_by_ID( $post_ID );

		// Check permission:
		$current_User->check_perm( 'item_post!CURSTATUS', 'edit', true, $Item );

		$new_attrs = '';
		switch( $field )
		{
			case 'priority':
				// Update task priority
				$new_value = param( 'new_priority', 'integer', NULL );
				$new_attrs = ' color="'.item_priority_color( $new_priority ).'"';
				$new_title = item_priority_title( $new_priority );
				$Item->set_from_Request( 'priority', 'new_priority', true );
				$Item->dbupdate();
				break;

			case 'assigned':
				// Update task assigned user
				$new_assigned_ID = param( 'new_assigned_ID', 'integer', NULL );
				$new_assigned_login = param( 'new_assigned_login', 'string', NULL );
				if( $Item->assign_to( $new_assigned_ID, $new_assigned_login ) )
				{ // An assigned user can be changed
					$Item->dbupdate();
				}
				else
				{ // Error on changing of an assigned user
					load_funcs('_core/_template.funcs.php');
					headers_content_mightcache( 'text/html', 0, '#', false );		// Do NOT cache error messages! (Users would not see they fixed them)
					header_http_response('400 Bad Request');
					// This message is displayed after an input field
					echo T_('Username not found!');
					die(2); // Error code 2. Note: this will still call the shutdown function.
					// EXIT here!
				}

				if( empty( $Item->assigned_user_ID ) )
				{
					$new_title = T_('No user');
				}
				else
				{
					$is_admin_page = true;
					$UserCache = & get_UserCache();
					$User = & $UserCache->get_by_ID( $Item->assigned_user_ID );
					$new_title = $User->get_colored_login( array( 'mask' => '$avatar$ $login$' ) );
				}
				$new_value = $Item->assigned_user_ID;
				break;

			case 'status':
				// Update task status
				$new_value = param( 'new_status', 'string', NULL );
				// Remove '_' that is used to don't break a sorting by name on jeditable:
				$new_value = intval( str_replace( '_', '', $new_value ) );
				set_param( 'new_status', $new_value );

				$Item->set_from_Request( 'pst_ID', 'new_status', true );
				$Item->dbupdate();

				$new_title = empty( $Item->pst_ID ) ? T_('No status') : $Item->get( 't_extra_status' );
				if( ! empty( $new_value ) )
				{	// Add '_' to don't break a sorting by name on jeditable:
					$new_value = '_'.$new_value;
				}
				break;
		}

		// Return a link to make the cell editable on next time
		echo '<a href="#" rel="'.$new_value.'"'.$new_attrs.'>'.$new_title.'</a>';
		break;

	case 'item_order_edit':
		// Update an order of Item from list screen by clicking on the cell:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'itemorder' );

		$item_order = param( 'new_item_order', 'string' );
		$post_ID = param( 'post_ID', 'integer' );

		$ItemCache = & get_ItemCache();
		$Item = & $ItemCache->get_by_ID( $post_ID );

		// Check permission:
		$current_User->check_perm( 'item_post!CURSTATUS', 'edit', true, $Item );

		if( $item_order === '-' || $item_order === '' )
		{	// Set NULL for these values:
			$item_order = NULL;
		}
		else
		{	// Make an order to integer:
			$item_order = intval( $item_order );
		}

		$Item->set( 'order', $item_order, true );
		$Item->dbupdate();

		// Return a link to make the cell editable on next time:
		echo '<a href="#" rel="'.$Item->ID.'">'.( $item_order === NULL ? '-' : $item_order ).'</a>';
		break;

	case 'cat_order_edit':
		// Update order of a chapter from list screen by clicking on the order column

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'catorder' );

		$blog = param( 'blogid', 'integer' );
		$cat_order = param( 'new_cat_order', 'string' );
		$cat_ID = param( 'cat_ID', 'integer' );

		// Check permission:
		$current_User->check_perm( 'blog_cats', 'edit', true, $blog );

		if( $cat_order === '-' || $cat_order === '' || intval( $cat_order ) == '' )
		{ // Set NULL for these values
			$cat_order = NULL;
		}

		$ChapterCache = & get_ChapterCache();
		if( $Chapter = & $ChapterCache->get_by_ID( $cat_ID, false ) )
		{ // Update cat order if it exists in DB
			$Chapter->set( 'order', ( $cat_order === '' ? NULL : $cat_order ), true );
			$Chapter->dbupdate();
			echo '<a href="#">'.( $cat_order === NULL ? '-' : $cat_order ).'</a>';
		}
		break;

	case 'get_goals':
		// Get option list with goals by selected category
		$blog = param( 'blogid', 'integer' );

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'itemgoal' );

		// Check permission:
		$current_User->check_perm( 'blog_post_statuses', 'edit', true, $blog );

		$cat_ID = param( 'cat_id', 'integer', 0 );

		$SQL = new SQL();
		$SQL->SELECT( 'goal_ID, goal_name' );
		$SQL->FROM( 'T_track__goal' );
		$SQL->WHERE( 'goal_redir_url IS NULL' );
		if( empty( $cat_ID ) )
		{ // Select the goals without category
			$SQL->WHERE_and( 'goal_gcat_ID IS NULL' );
		}
		else
		{ // Get the goals from a selected category
			$SQL->WHERE_and( 'goal_gcat_ID = '.$DB->quote( $cat_ID ) );
		}
		$goals = $DB->get_assoc( $SQL->get() );

		echo '<option value="">'.T_('None').'</option>';

		foreach( $goals as $goal_ID => $goal_name )
		{
			echo '<option value="'.$goal_ID.'">'.$goal_name.'</option>';
		}

		break;

	case 'conflict_files':
		// Replace old file with new and set new name for old file

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'conflictfiles' );

		param( 'fileroot_ID', 'string' );

		// Check permission:
		$current_User->check_perm( 'files', 'add', true, $fileroot_ID );

		param( 'path', 'filepath' );
		param( 'oldfile', 'filepath' );
		param( 'newfile', 'filepath' );
		param( 'format', 'string' );

		$fileroot = explode( '_', $fileroot_ID );
		$fileroot_type = $fileroot[0];
		$fileroot_type_ID = empty( $fileroot[1] ) ? 0 : $fileroot[1];

		$result = replace_old_file_with_new( $fileroot_type, $fileroot_type_ID, $path, $newfile, $oldfile, false );

		$data = array();
		if( $format == 'full_path_link' )
		{ // User link with full path to file
			$FileCache = & get_FileCache();
			$new_File = & $FileCache->get_by_root_and_path( $fileroot_type, $fileroot_type_ID, trailing_slash( $path ).$newfile, true );
			$old_File = & $FileCache->get_by_root_and_path( $fileroot_type, $fileroot_type_ID, trailing_slash( $path ).$oldfile, true );
			$data['new'] = $new_File->get_view_link();
			$data['old'] = $old_File->get_view_link();
		}
		else
		{ // Simple text format
			$data['new'] = $newfile;
			$data['old'] = $oldfile;
		}
		if( $result !== true )
		{ // Send an error if it was created during the replacing
			$data['error'] = $result;
		}

		echo evo_json_encode( $data );
		exit(0);

	case 'link_attachment':
		// The content for popup window to link the files to the items/comments

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'link' );

		// Check permission:
		$current_User->check_perm( 'files', 'view' );

		param( 'iframe_name', 'string', '' );
		param( 'link_owner_type', 'string', true );
		param( 'link_owner_ID', 'integer', true );
		// Additional params, Used to highlight file/folder
		param( 'root', 'string', '' );
		param( 'path', 'filepath', '' );
		param( 'fm_highlight', 'string', '' );

		$additional_params = empty( $root ) ? '' : '&amp;root='.$root;
		$additional_params .= empty( $path ) ? '' : '&amp;path='.$path;
		$additional_params .= empty( $fm_highlight ) ? '' : '&amp;fm_highlight='.$fm_highlight;

		echo '<div style="background:#FFF;height:90%">'
				.'<span id="link_attachment_loader" class="loader_img absolute_center" title="'.T_('Loading...').'"></span>'
				.'<iframe src="'.$admin_url.'?ctrl=files&amp;mode=upload&amp;ajax_request=1&amp;iframe_name='.$iframe_name.'&amp;fm_mode=link_object&amp;link_type='.$link_owner_type.'&amp;link_object_ID='.$link_owner_ID.$additional_params.'"'
					.' width="100%" height="100%" marginwidth="0" marginheight="0" align="top" scrolling="auto" frameborder="0"'
					.' onload="document.getElementById(\'link_attachment_loader\').style.display=\'none\'">loading</iframe>'
			.'</div>';

		break;

	case 'file_attachment':
		// The content for popup window to link the files to the items/comments

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'file' );

		// Check permission:
		$current_User->check_perm( 'files', 'view' );

		param( 'iframe_name', 'string', '' );
		param( 'field_name', 'string', '' );
		// Additional params, Used to highlight file/folder
		param( 'root', 'string', '' );
		param( 'path', 'string', '' );
		param( 'fm_highlight', 'string', '' );

		$additional_params = empty( $root ) ? '' : '&amp;root='.$root;
		$additional_params .= empty( $path ) ? '' : '&amp;path='.$path;
		$additional_params .= empty( $fm_highlight ) ? '' : '&amp;fm_highlight='.$fm_highlight;
		//$additional_params .= empty( $field_name ) ? '' : '&amp;field_name='.$field_name;

		echo '<div style="background:#FFF;height:90%">'
				.'<span id="link_attachment_loader" class="loader_img absolute_center" title="'.T_('Loading...').'"></span>'
				.'<iframe src="'.$admin_url.'?ctrl=files&amp;mode=upload&amp;field_name='.$field_name.'&amp;ajax_request=1&amp;iframe_name='.$iframe_name.'&amp;fm_mode=file_select'.$additional_params.'"'
					.' width="100%" height="100%" marginwidth="0" marginheight="0" align="top" scrolling="auto" frameborder="0"'
					.' onload="document.getElementById(\'link_attachment_loader\').style.display=\'none\'">loading</iframe>'
			.'</div>';

		break;

	case 'import_files':
		// The content for popup window to import the files for XML importer

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'import' );

		$FileRootCache = & get_FileRootCache();
		$FileRoot = & $FileRootCache->get_by_type_and_ID( 'import', '0', true );

		// Check permission:
		$current_User->check_perm( 'files', 'view', true, $FileRoot );

		echo '<div style="background:#FFF;height:80%">'
				.'<span id="import_files_loader" class="loader_img absolute_center" title="'.T_('Loading...').'"></span>'
				.'<iframe src="'.$admin_url.'?ctrl=files&amp;mode=import&amp;ajax_request=1&amp;root=import_0"'
					.' width="100%" height="100%" marginwidth="0" marginheight="0" align="top" scrolling="auto" frameborder="0"'
					.' onload="document.getElementById(\'import_files_loader\').style.display=\'none\'">loading</iframe>'
			.'</div>';

		break;

	case 'test_api':
		// Spec action to test API from ctrl=system:
		echo 'ok';
		break;

	default:
		$incorrect_action = true;
		break;
}

if( !$incorrect_action )
{
	if( $current_debug || $current_debug_jslog )
	{	// debug is ON
		$Ajaxlog->display( NULL, NULL, true, 'all',
						array(
								'error' => array( 'class' => 'jslog_error', 'divClass' => false ),
								'note'  => array( 'class' => 'jslog_note',  'divClass' => false ),
							), 'ul', 'jslog' );
	}

	if( $add_response_end_comment )
	{ // add ajax response end comment
		echo '<!-- Ajax response end -->';
	}

	exit(0);
}

?>