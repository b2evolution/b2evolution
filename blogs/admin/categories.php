<?php
/**
 * This file implements the UI controller for the categories management.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package admin
 *
 * $Id$
 */

/**
 * Includes:
 */
require_once( dirname(__FILE__).'/_header.php' );
$AdminUI->set_path( 'cats' );
$AdminUI->title = $AdminUI->title_titlearea = T_('Categories for blog:');
param( 'action', 'string', 'list' );
param( 'blog', 'integer', 0, true );

/**
 * Perform action:
 */
switch( $action )
{
	case 'new':
		$blog = autoselect_blog( $blog, 'blog_cats', '' );
		// New category form:
		param( 'parent_cat_ID', 'integer' );
		if( !empty($parent_cat_ID) )
		{
			$blog = get_catblog($parent_cat_ID);
		}
		break;


	case 'create':
		// INSERT new cat into db
		$Request->param_string_not_empty( 'cat_name', T_('Please enter a category name.') );

		$Request->param( 'parent_cat_ID', 'integer' );
		if( !empty($parent_cat_ID) )
		{ // We are creating a subcat
			$cat_blog_ID = get_catblog( $parent_cat_ID );
		}
		else
		{
			$Request->param( 'cat_blog_ID', 'integer', true );
		}

		// check permissions:
		$current_User->check_perm( 'blog_cats', '', true, $cat_blog_ID );

		if( ! $Messages->count('error') )
		{
			if( !empty($parent_cat_ID) )
			{ // We are creating a subcat
				// INSERT INTO DB
				$new_cat_ID = cat_create( $cat_name, $parent_cat_ID );
			}
			else
			{ // We are creating a new base cat
				// INSERT INTO DB
				$new_cat_ID = cat_create( $cat_name, 'NULL', $cat_blog_ID );
			}

	 		$Messages->add( T_('New category created.'), 'success' );
	 		$action = 'list';
		}

		if( !empty($parent_cat_ID) )
		{ // We are creating a subcat
			$blog = get_catblog( $parent_cat_ID );
		}
		else
		{ // We are creating a new base cat
			$blog = $cat_blog_ID;
		}
		unset( $cache_categories );
		break;


	case 'edit':
		// ---------- Cat edit form: ----------
		param( 'cat_ID', 'integer' );
		$blog = get_catblog($cat_ID);

		// check permissions:
		$current_User->check_perm( 'blog_cats', '', true, $blog );
		break;


	case 'update':
		//
		// Update cat in db:
		//
		$Request->param_string_not_empty( 'cat_name', T_('Please enter a category name.') );

		$Request->param( 'cat_ID', 'integer', true );
		//echo $cat_ID;
		$cat_blog_ID = get_catblog($cat_ID);

		$Request->param( 'cat_parent_ID', 'string', true );
		$cat_parent_ID_parts = explode( '_', $cat_parent_ID );
		$cat_parent_ID = $cat_parent_ID_parts[0];
		settype( $cat_parent_ID, 'integer' );
		if( $cat_parent_ID != 0 )
		{ // We have a new parent cat
			$parent_cat_blog_ID = get_catblog($cat_parent_ID);
		}
		else
		{ // We are moving to a blog root
			$parent_cat_blog_ID = $cat_parent_ID_parts[1];
			settype( $parent_cat_blog_ID, 'integer' );
		}

		// check permissions on source:
		$current_User->check_perm( 'blog_cats', '', true, $cat_blog_ID );

		if( $cat_blog_ID != $parent_cat_blog_ID )
		{ // We are moving to a different blog
			if( ! $allow_moving_chapters )
			{
				die( 'Moving chapters between blogs is disabled. Cat and parent must be in the same blog!' );
			}
			// check permissions on destination:
			$current_User->check_perm( 'blog_cats', '', true, $parent_cat_blog_ID );
		}

		if( ! $Messages->count('error') )
		{
			cat_update( $cat_ID, $cat_name, $cat_parent_ID, $parent_cat_blog_ID );
			$Messages->add( T_('Category updated.'), 'success' );
			unset( $cache_categories );
			$action = 'list';
		}

		$blog = $cat_blog_ID;
		break;


	case 'delete':
		// Delete cat from DB:
		param( 'cat_ID', 'integer' );
		$blog = get_catblog($cat_ID);

		// check permissions:
		$current_User->check_perm( 'blog_cats', '', true, $blog );

		$cat_name = get_catname($cat_ID);

		// DELETE FROM DB:
		$result = cat_delete( $cat_ID );
		if( $result !== 1 )
		{ // We got an error message!
			$Messages->add( T_('ERROR').': '.$result, 'error' );
		}
		else
		{
			$Messages->add( T_('Category deleted.'), 'success' );
		}
		echo "</div>\n";
		break;


	case 'list':
	  $blog = autoselect_blog( $blog, 'blog_cats', '' );

}


/**
 * Display page header, menus & messages:
 */
// Generate available blogs list:
$blogListButtons = $AdminUI->get_html_collection_list( 'blog_cats', '', $pagenow.'?blog=%d' );
require dirname(__FILE__).'/_menutop.php';



/**
 * Display payload:
 */
switch($action)
{
	case 'new':
	case 'create': // in case of an error
		// New category form:
		$AdminUI->disp_payload_begin();

		$Form = & new Form( 'categories.php' );

		$Form->global_icon( T_('Cancel editing!'), 'close', regenerate_url( 'action,parent_cat_ID' ) );

		if( !empty($parent_cat_ID) )
		{ // We are creating a subcat
			$parent_cat_name = get_catname($parent_cat_ID);

			$Form->begin_form( 'fform', sprintf( T_('New sub-category in category: %s'), $parent_cat_name ) );

			$Form->hidden( 'parent_cat_ID', $parent_cat_ID );
		}
		else
		{ // We are creating a new base cat
			$blogparams = get_blogparams_by_ID( $blog );

			$Form->begin_form( 'fform', sprintf( T_('New category in blog: %s'), $blogparams->blog_name ) );

			$Form->hidden( 'cat_blog_ID', $blog );
		}

		$Form->hidden( 'action', 'create' );

    $Form->text( 'cat_name', '', 40, T_('New category name'), '', 80 );

		$Form->end_form( array( array( 'submit', 'submit', T_('Create category'), 'SaveButton' ),
														array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );

		$AdminUI->disp_payload_end();
		break;




	case 'edit':
	case 'update': // in case of an error
		// ---------- Cat edit form: ----------
		$cat_name = get_catname($cat_ID);
		$cat_parent_ID = get_catparent($cat_ID);

		$AdminUI->disp_payload_begin();

		$Form = & new Form( 'categories.php' );

		$Form->global_icon( T_('Cancel editing!'), 'close', regenerate_url( 'action,cat_ID' ) );

		$Form->begin_form( 'fform', T_('Properties for category:').' '.format_to_output( $cat_name, 'htmlbody' ) );

		$Form->hidden( 'action', 'update' );
		$Form->hidden( 'cat_ID', $cat_ID );

		$Form->text( 'cat_name', $cat_name, 40, T_('New category name'), '', 80 );

		// ----------------- START RECURSIVE CAT LIST ----------------
		cat_query( 'none' );	// make sure the caches are loaded

		function cat_move_before_first( $parent_cat_ID, $level )
		{ // callback to start sublist
			return "\n<ul>\n";
		}

		function cat_move_before_each( $curr_cat_ID, $level )
		{ // callback to display sublist element
			global $cat_ID; // This is the category being currently edited !!
			global $cat_parent_ID;	// This is the old parent ID
			if( $curr_cat_ID == $cat_ID )
			{ // We have reached current category.
				// This branch cannot become a parent!
				return true;
			}
			$cat = get_the_category_by_ID( $curr_cat_ID );
			$r = '<li>';
			$r .= '<input type="radio" id="cat_parent_ID'.$curr_cat_ID.'" name="cat_parent_ID" value="'.$curr_cat_ID.'"';
			if( $cat_parent_ID == $curr_cat_ID )
			{
				$r .= ' checked="checked"';
			}
			$r .= '/> <label for="cat_parent_ID'.$curr_cat_ID.'"><strong>'.$cat['cat_name'].'</strong></label>';
			if( $cat_parent_ID == $curr_cat_ID )
			{
				$r .= ' &lt;= '.T_('Old Parent');
			}
			return $r;
		}

		function cat_move_after_each( $curr_cat_ID, $level )
		{ // callback after each sublist element
			return "</li>\n";
		}

		function cat_move_after_last( $parent_cat_ID, $level )
		{ // callback to end sublist
			return "</ul>\n";
		}

		$r = '';

		if( $allow_moving_chapters )
		{ // If moving cats between blogs is allowed:
			foreach( $cache_blogs as $i_blog )
			{ // run recursively through the cats of each blog
				$current_blog_ID = $i_blog->blog_ID;
				if( ! $current_User->check_perm( 'blog_cats', '', false, $current_blog_ID ) )
					continue;

				$r .= "<h4>".$i_blog->blog_name."</h4>\n";

				$r .= '<input type="radio" id="cat_parent_none_'.$current_blog_ID.'" name="cat_parent_ID" value="0_'.$current_blog_ID.'"';
				if( (! $cat_parent_ID) && ($current_blog_ID == $blog) )
				{
					$r .= ' checked="checked"';
				}
				$r .= '/> <label for="cat_parent_none_'.$current_blog_ID.'"><strong>'.T_('Root (No parent)').'</strong></label>';
				if( (! $cat_parent_ID) && ($current_blog_ID == $blog) )
				{
					$r .= ' &lt;= '.T_('Old Parent');
				}
				// RECURSE:
				$r .= cat_children( $cache_categories, $current_blog_ID, NULL, 'cat_move_before_first', 'cat_move_before_each', 'cat_move_after_each', 'cat_move_after_last' );
			}

			$r .= '<p class="extracatnote">'.T_('Note: Moving categories across blogs is enabled. Use with caution.').'</p> ';
		}
		else
		{ // Moving cats between blogs is disabled
			$r .= '<input type="radio" id="cat_parent_none_'.$blog.'" name="cat_parent_ID" value="0_'.$blog.'"';
			if( ! $cat_parent_ID )
			{
				$r .= ' checked="checked"';
			}
			$r .= '/> <label for="cat_parent_none_'.$blog.'"><strong>'.T_('Root (No parent)').'</strong></label>';
			if( ! $cat_parent_ID )
			{
				$r .= ' &lt;= '.T_('Old Parent');
			}
			// RECURSE:
			$r .= cat_children( $cache_categories, $blog, NULL, 'cat_move_before_first', 'cat_move_before_each', 'cat_move_after_each', 'cat_move_after_last' );

			if( ! is_null( $allow_moving_chapters ) )
			{
				$r .= '<p class="extracatnote">'.T_('Note: Moving categories across blogs is disabled.').'</p> ';
			}
		}

		// ----------------- END RECURSIVE CAT LIST ----------------

		$Form->info( T_('New parent category'), $r );


		$Form->end_form( array(
			array( 'submit', 'submit', T_('Edit category'), 'SaveButton' ),
			array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );

		$AdminUI->disp_payload_end();
		break;


	default:
		// Just display cat list for this blog:
		if( $blog == 0 )
		{ // No blog could be selected:
			?>
			<div class="panelinfo">
			<p><?php echo T_('Sorry, you have no permission to edit/view any category\'s properties.' ) ?></p>
			</div>
			<?php
			break;
		}

		// List the cats:
		require dirname(__FILE__).'/_cats_list.php';
}

require dirname(__FILE__).'/_footer.php';

/*
 * $Log$
 * Revision 1.1  2005/10/31 00:15:27  blueyed
 * Removed b2categories.php to categories.php
 *
 * Revision 1.48  2005/10/28 20:08:46  blueyed
 * Normalized AdminUI
 *
 * Revision 1.47  2005/09/01 17:11:46  fplanque
 * no message
 *
 * Revision 1.46  2005/07/29 19:46:10  blueyed
 * Important whitespace between <input> and <label>.
 *
 * Revision 1.45  2005/06/06 17:59:37  fplanque
 * user dialog enhancements
 *
 * Revision 1.44  2005/06/03 15:12:30  fplanque
 * error/info message cleanup
 *
 * Revision 1.43  2005/05/10 18:35:37  fplanque
 * refactored/normalized category handling
 * (though there's still a lot to do before this gets as clean as desired...)
 *
 */
?>