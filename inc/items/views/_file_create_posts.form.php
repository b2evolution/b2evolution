<?php
/**
 * This file implements the UI for make posts from images in file upload.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'items/model/_item.class.php', 'Item' );
load_class( 'files/model/_filelist.class.php', 'FileList' );

global $post_extracats, $fm_FileRoot, $edited_Item, $blog;

$edited_Item = new Item();

$Form = new Form( NULL, 'pre_post_publish' );

$Form->begin_form( 'fform', T_('Posts preview') );
$Form->hidden_ctrl();

$images_list = param( 'fm_selected', 'array:string' );
foreach( $images_list as $key => $item )
{
	$Form->hidden( 'fm_selected['.$key.']', $item );
}
// fp> TODO: cleanup all this crap:
$Form->hidden( 'confirmed', get_param( 'confirmed' ) );
$Form->hidden( 'md5_filelist', get_param( 'md5_filelist' ) );
$Form->hidden( 'md5_cwd', get_param( 'md5_cwd' ) );
$Form->hidden( 'locale', get_param( 'locale' ) );
$Form->hidden( 'blog', get_param( 'blog' ) );
$Form->hidden( 'mode', get_param( 'mode' ) );
$Form->hidden( 'root', get_param( 'root' ) );
$Form->hidden( 'path', get_param( 'path' ) );
$Form->hidden( 'fm_mode', get_param( 'fm_mode' ) );
$Form->hidden( 'linkctrl', get_param( 'linkctrl' ) );
$Form->hidden( 'linkdata', get_param( 'linkdata' ) );
$Form->hidden( 'iframe_name', get_param( 'iframe_name' ) );
$Form->hidden( 'fm_filter', get_param( 'fm_filter' ) );
$Form->hidden( 'fm_filter_regex', get_param( 'fm_filter_regex' ) );
$Form->hidden( 'iframe_name', get_param( 'iframe_name' ) );
$Form->hidden( 'fm_flatmode', get_param( 'fm_flatmode' ) );
$Form->hidden( 'fm_order', get_param( 'fm_order' ) );
$Form->hidden( 'fm_orderasc', get_param( 'fm_orderasc' ) );
$Form->hidden( 'crumb_file', get_param( 'crumb_file' ) );

$post_extracats = array();
$post_counter = 0;


/**
 * Get the categories list
 *
 * @param integer Parent category ID
 * @param integer Level
 * @return array Categories
 */
function fcpf_categories_select( $parent_category_ID = -1, $level = 0 )
{
	global $blog, $DB;
	$result_Array = array();

	$SQL = new SQL();
	$SQL->SELECT( 'cat_ID, cat_name' );
	$SQL->FROM( 'T_categories' );
	$SQL->WHERE( 'cat_blog_ID = '.$DB->quote( $blog ) );
	if( $parent_category_ID == -1 )
	{
		$SQL->WHERE_and( 'cat_parent_ID IS NULL' );
	}
	else
	{
		$SQL->WHERE( 'cat_parent_ID = '.$DB->quote( $parent_category_ID ) );
	}
	$SQL->ORDER_BY( 'cat_name' );
	$categories = $DB->get_results( $SQL->get() );

	if( ! empty( $categories ) )
	{
		foreach( $categories as $category )
		{
			$result_Array[] = array(
					'value' => $category->cat_ID,
					'label' => str_repeat( '&nbsp;&nbsp;&nbsp;', $level ).$category->cat_name
				);

			$child_Categories_opts = fcpf_categories_select( $category->cat_ID, $level + 1 );
			if( $child_Categories_opts != '' )
			{
				foreach( $child_Categories_opts as $cat )
				{
					$result_Array[] = $cat;
				}
			}
		}
	}
	return $result_Array;
}

$FileCache = & get_FileCache();

// Check if current user can add new categories
$user_has_cat_perms = $current_User->check_perm( 'blog_cats', '', false, $blog );

// Get the categories
$categories = fcpf_categories_select();

foreach( $images_list as $item )
{
	$File = & $FileCache->get_by_root_and_path( $fm_FileRoot->type,  $fm_FileRoot->in_type_ID, urldecode( $item ), true );
	$title = $File->get( 'title' );
	if( empty( $title ) )
	{
		$title = basename( urldecode( $File->get( 'name' ) ) );
	}
	$Form->begin_fieldset( T_('Post #').( $post_counter + 1 ).get_manual_link( 'creating-posts-from-files' ) );
	$Form->text_input( 'post_title['.$post_counter.']', $title, 40, T_('Post title') );

	if( $post_counter != 0 )
	{ // The posts after first
		if( $post_counter == 1 )
		{ // Add new option to select a category from previous post
			$categories = array_merge(
				array(
					array(
						'value' => 'same',
						'label' => T_('Same as above').'<br />',
					)
				), $categories );
		}
		// Use the same category for all others after first
		$selected_category_ID = 'same';
	}
	else
	{ // First post, Use a default category as selected on load form
		global $Blog;
		$selected_category_ID = isset( $Blog ) ? $Blog->get_default_cat_ID() : 1;
	}

	if( $user_has_cat_perms )
	{ // Field to create a new category if current user has the rights
		$categories[] = array(
				'value'  => 'new',
				'label'  => T_('New').':',
				'suffix' => '<input type="text" id="new_categories['.$post_counter.']" name="new_categories['.$post_counter.']" class="form_text_input" maxlength="255" size="25" />'
			);
	}

	$Form->radio_input( 'category['.$post_counter.']', $selected_category_ID, $categories, T_('Category'), array( 'suffix' => '<br />' ) );
	// Clear last option to create a new for next item with other $post_counter
	array_pop( $categories );

	$Form->info( T_('Post content'), '<img src="'.$fm_FileRoot->ads_url.urldecode( $item ).'" width="200" />' );

	$Form->end_fieldset();

	$post_counter++;
}
$edited_Item = NULL;

$visibility_statuses = get_visibility_statuses( 'notes-string', array(), true, $blog );
if( empty( $visibility_statuses ) )
{
	$visibility_statuses = get_visibility_statuses( 'notes-string' );
	if( isset( $visibility_statuses[ $Blog->get_setting( 'default_post_status' ) ] ) )
	{ // Current user can create a post only with default status
		$Form->info( T_('Status of new posts'), $visibility_statuses[ $Blog->get_setting( 'default_post_status' ) ] );
	}
}
else
{ // Display a list with the post statuses
	$Form->select_input_array( 'post_status', $Blog->get_setting( 'default_post_status' ), $visibility_statuses, T_('Status of new posts') );
}

$Form->end_form( array( array( 'submit', 'actionArray[make_posts_from_files]', T_('Make posts'), 'ActionButton') ) );

?>
<script type="text/javascript">
jQuery( 'input[id^=new_categories]' ).focus( function()
{
	var num = jQuery( this ).attr( 'id' ).replace( /new_categories\[(\d+)\]/gi, '$1' );
	jQuery( 'input[name=category\\[' + num + '\\]]' ).attr( 'checked', 'checked' );
} );
</script>