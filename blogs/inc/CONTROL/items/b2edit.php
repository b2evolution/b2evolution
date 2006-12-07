<?php
/**
 * This file implements the UI controller for editing posts.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'MODEL/items/_item.class.php' );

/**
 * @var AdminUI
 */
global $AdminUI;


$AdminUI->add_menu_entries(
		'edit',
		array(
				'simple' => array(
					'text' => T_('Simple'),
					'href' => 'admin.php?ctrl=edit&amp;tab=simple&amp;blog='.$blog,
					'onclick' => 'return b2edit_reload( document.getElementById(\'item_checkchanges\'), \'admin.php?ctrl=edit&amp;tab=simple&amp;blog='.$blog.'\' );',
					'name' => 'switch_to_simple_tab_nocheckchanges', // no bozo check
					),
				'expert' => array(
					'text' => T_('Expert'),
					'href' => 'admin.php?ctrl=edit&amp;tab=expert&amp;blog='.$blog,
					'onclick' => 'return b2edit_reload( document.getElementById(\'item_checkchanges\'), \'admin.php?ctrl=edit&amp;tab=expert&amp;blog='.$blog.'\' );',
					'name' => 'switch_to_expert_tab_nocheckchanges', // no bozo check
					),
			)
	);


// Get tab ("simple" or "expert") from Request or UserSettings:
$tab = $UserSettings->param_Request( 'tab', 'pref_edit_tab', 'string', NULL, true /* memorize */ );

$AdminUI->set_path( 'edit', $tab );

param( 'action', 'string', 'new', true );

// All statuses are allowed for display/acting on (including drafts and deprecated posts):
// fp> rem 06/09/06 $show_statuses = array( 'published', 'protected', 'private', 'draft', 'deprecated' );

/*
 * Load editable objects:
 */
if( param( 'link_ID', 'integer', NULL, false, false, false ) )
{
	$LinkCache = & get_Cache( 'LinkCache' );
	if( ($edited_Link = & $LinkCache->get_by_ID( $link_ID, false )) === false )
	{	// We could not find the linke to edit:
		$Messages->head = T_('Cannot edit link!');
		$Messages->add( T_('Requested link does not exist any longer.'), 'error' );
		unset( $edited_Link );
		unset( $link_ID );
	}
}


switch($action)
{
	case 'delete_link':
		// Delete a link:

		// TODO: check perm!

		// Unlink File from Item:
		if( isset( $edited_Link ) )
		{
			// TODO: get Item from Link to check perm

			// TODO: check item EDIT permissions!

			// Delete from DB:
			$msg = sprintf( T_('Link from &laquo;%s&raquo; deleted.'), $edited_Link->Item->dget('title') );
			$edited_Link->dbdelete( true );
			unset($edited_Link);
			$Messages->add( $msg, 'success' );
		}
		// This will eventually boil down to editing, so we need to prepare...


	case 'edit':
		/*
		 * --------------------------------------------------------------------
		 * Display post editing form
		 */
		param( 'post', 'integer', true, true );
		$ItemCache = & get_Cache( 'ItemCache' );
		$edited_Item = & $ItemCache->get_by_ID( $post );
		$post_locale = $edited_Item->get( 'locale' );
		$cat = $edited_Item->get( 'main_cat_ID' );
		$blog = get_catblog($cat);
		$BlogCache = & get_Cache( 'BlogCache' );
		$Blog = & $BlogCache->get_by_ID( $blog );

		$js_doc_title_prefix = T_('Editing post').': ';
		$AdminUI->title = $js_doc_title_prefix.$edited_Item->dget( 'title', 'htmlhead' );
		$AdminUI->title_titlearea = sprintf( T_('Editing post #%d in blog: %s'), $edited_Item->ID, $Blog->get('name') );

		$post_status = $edited_Item->get( 'status' );
		// Check permission:
		$current_User->check_perm( 'blog_post_statuses', $post_status, true, $blog );

		$post_title = $edited_Item->get( 'title' );
		$post_urltitle = $edited_Item->get( 'urltitle' );
		$post_url = $edited_Item->get( 'url' );
		$content = $edited_Item->get( 'content' );
		$post_trackbacks = '';
		$post_comment_status = $edited_Item->get( 'comment_status' );
		$post_extracats = postcats_get_byID( $post );

		break;


	case 'editcomment':
		/*
		 * --------------------------------------------------------------------
		 * Display comment in edit form
		 */
		param( 'comment', 'integer', true );
		$edited_Comment = Comment_get_by_ID( $comment );

		$AdminUI->title = T_('Editing comment').' #'.$edited_Comment->ID;

		$edited_Comment_Item = & $edited_Comment->get_Item();
		$blog = $edited_Comment_Item->blog_ID;
		$BlogCache = & get_Cache( 'BlogCache' );
		$Blog = & $BlogCache->get_by_ID( $blog );

		// Check permission:
		$current_User->check_perm( 'blog_comments', 'any', true, $blog );

		break;


	case 'edit_switchtab': // this gets set as action by JS, when we switch tabs
	case 'create_switchtab': // this gets set as action by JS, when we switch tabs
	default:
		/*
		 * --------------------------------------------------------------------
		 * New post form  (can be a bookmarklet form if mode == bookmarklet )
		 */
		param( 'blog', 'integer', 0 );

		// Set AdminUI title:
		if( $action == 'edit_switchtab' )
		{
			$post = param( 'post_ID', 'integer', true );
			$ItemCache = & get_Cache( 'ItemCache' );
			$edited_Item = & $ItemCache->get_by_ID($post_ID);

			param( 'blog', 'integer', true );
			$BlogCache = & get_Cache( 'BlogCache' );
			$Blog = $BlogCache->get_by_ID( $blog );

			$js_doc_title_prefix = T_('Editing post').': ';
			$AdminUI->title = $js_doc_title_prefix.$edited_Item->dget( 'title', 'htmlhead' );
			$AdminUI->title_titlearea = sprintf( T_('Editing post #%d in blog: %s'), $edited_Item->ID, $Blog->get('name') );
		}
		else
		{
			$edited_Item = & new Item();

			$AdminUI->title = T_('New post in blog:').' ';
			$AdminUI->title_titlearea = $AdminUI->title;
			$js_doc_title_prefix = $AdminUI->title;
		}

		$blog = autoselect_blog( $blog, 'blog_post_statuses', 'any' );

		// Generate available blogs list:
		$blogListButtons = $AdminUI->get_html_collection_list( 'blog_post_statuses', 'any', $pagenow.'?blog=%d', NULL, '',
												'return b2edit_reload( document.getElementById(\'item_checkchanges\'), \''.$pagenow.'\', %d )' );

		if( !$blog )
		{
			$Messages->add( sprintf( T_('Since you\'re a newcomer, you\'ll have to wait for an admin to authorize you to post. You can also <a %s>e-mail the admin</a> to ask for a promotion. When you\'re promoted, just reload this page and you\'ll be able to blog. :)'),
											'href="mailto:'.$admin_email.'?subject=b2-promotion"' ), 'error' );
			$action = 'nil';
			break;
		}

		$BlogCache = & get_Cache( 'BlogCache' );
		$Blog = & $BlogCache->get_by_ID( $blog );

		if( ! blog_has_cats( $blog ) )
		{
			$Messages->add( T_('Since this blog has no categories, you cannot post to it. You must create categories first.'), 'error' );
			$action = 'nil';
			break;
		}

		// Check permission:
		$current_User->check_perm( 'blog_post_statuses', 'any', true, $blog );

		// Okay now we know we can use at least one status,
		// but we need to make sure the requested/default one is ok...
		param( 'post_status', 'string',  $default_post_status );		// 'published' or 'draft' or ...
		if( ! $current_User->check_perm( 'blog_post_statuses', $post_status, false, $blog ) )
		{ // We need to find another one:
			if( $current_User->check_perm( 'blog_post_statuses', 'published', false, $blog ) )
				$post_status = 'published';
			elseif( $current_User->check_perm( 'blog_post_statuses', 'protected', false, $blog ) )
				$post_status = 'protected';
			elseif( $current_User->check_perm( 'blog_post_statuses', 'private', false, $blog ) )
				$post_status = 'private';
			elseif( $current_User->check_perm( 'blog_post_statuses', 'draft', false, $blog ) )
				$post_status = 'draft';
			else
				$post_status = 'deprecated';
		}

		$edited_Item->blog_ID = $blog;

		// Note: most params are handled by "$edited_Item->load_from_Request();" below..

		param( 'post_extracats', 'array', array() );
		param( 'edit_date', 'integer', 0 ); // checkbox
		$default_main_cat = param( 'post_category', 'integer', $edited_Item->main_cat_ID );
		if( $default_main_cat && $allow_cross_posting < 3 && get_catblog($default_main_cat) != $blog )
		{ // the main cat is not in the list of categories; this happens, if the user switches blogs during editing: setting it to 0 uses the first cat in the list
			$default_main_cat = 0;
		}
		$post_extracats = param( 'post_extracats', 'array', $post_extracats );

		$edited_Item->load_from_Request(); // needs Blog set

		break;

}


// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();


/**
 * Display payload:
 */
switch( $action )
{
	case 'nil':
		break;

	case 'edit_switchtab':
		// We come back to editing after a tab switch:
		$creating = true; // used by cat_select_before_each()
		$bozo_start_modified = true;	// We want to start with a form being already modified
	case 'delete_link':
	case 'edit':
		/*
		 * --------------------------------------------------------------------
		 * Display post editing form
		 */
		// Display edit form:
		$form_action = '?ctrl=editactions';
		$next_action = 'update';

		// Display VIEW:
		switch( $tab )
		{
			case 'simple':
				$AdminUI->disp_view( 'items/_item_simple.form.php' );
				break;

			case 'expert':
			default:
				$AdminUI->disp_view( 'items/_item.form.php' );
				break;
		}

		break;

	case 'editcomment':
		/*
		 * --------------------------------------------------------------------
		 * Display comment in edit form
		 */
		// Display VIEW:
		$AdminUI->disp_view( 'comments/_comment.form.php' );

		break;

		$next_action = 'update';
		break;

	case 'create_switchtab':
		// We come back to creating after a tab switch:
		$bozo_start_modified = true;	// We want to start with a form being already modified
	default:
		/*
		 * --------------------------------------------------------------------
		 * New post form  (can be a bookmarklet form if mode == bookmarklet )
		 */
		// Display edit form:
		$form_action = '?ctrl=editactions';
		$next_action = 'create';
		$creating = true; // used by cat_select_before_each()

		// Display VIEW:
		switch( $tab )
		{
			case 'simple':
				$AdminUI->disp_view( 'items/_item_simple.form.php' );
				break;

			case 'expert':
			default:
				$AdminUI->disp_view( 'items/_item.form.php' );
				break;
		}
}


// Add event to the item title field to update document title and init it (important when switching tabs/blogs):
?>

<script type="text/javascript">

	<?php
	if( isset($js_doc_title_prefix) )
	{ // dynamic document.title handling:
		?>
		if( post_title_elt = document.getElementById('post_title') )
		{
			/**
			 * Updates document.title according to the item title field (post_title)
			 */
			function evo_update_document_title()
			{
				var posttitle = document.getElementById('post_title').value;

				document.title = document.title.replace( /(<?php echo preg_quote( trim($js_doc_title_prefix) /* e.g. FF2 trims document.title */ ) ?>).*$/, '$1 '+posttitle );
			}

			addEvent( post_title_elt, 'keyup', evo_update_document_title, false );

			// Init:
			evo_update_document_title();
		}
		<?php
	}
	?>


	// Handle "edit timestamp" field:
	if( edit_date_elt = document.getElementById('edit_date') )
	{
		/**
		 * If user modified date, check the checkbox:
		 */
		function evo_check_edit_date()
		{
			edit_date_elt.checked = true;
		}

		if( item_issue_date_elt = document.getElementById('item_issue_date') )
		{
			addEvent( item_issue_date_elt, 'change', evo_check_edit_date, false ); // TODO: check in IE
		}
		if( item_issue_time_elt = document.getElementById('item_issue_time') )
		{
			addEvent( item_issue_time_elt, 'change', evo_check_edit_date, false ); // TODO: check in IE
		}
	}

</script>

<?php


// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

?>