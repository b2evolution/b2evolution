<?php
/**
 * This file implements the recursive chapter list.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );
//____________________ Callbacks functions to display categories list _____________________

/**
 * @var Blog
 */
global $Blog;

global $Settings;

global $ChapterCache;

global $line_class;

global $permission_to_edit;

global $subset_ID;

global $current_default_cat_ID;

global $Session, $AdminUI;

$result_fadeout = $Session->get( 'fadeout_array' );

$current_default_cat_ID = $Blog->get_setting('default_cat_ID');

$line_class = 'odd';


/**
 * Generate category line when it has children
 *
 * @param object Chapter we want to display
 * @param integer Level of the category in the recursive tree
 * @return string HTML
 */
function cat_line( $Chapter, $level )
{
	global $line_class, $permission_to_edit, $current_User, $Settings;
	global $ChapterCache, $current_default_cat_ID;
	global $number_of_posts_in_cat;

	global $Session;
	$result_fadeout = $Session->get( 'fadeout_array' );

	$line_class = $line_class == 'even' ? 'odd' : 'even';

	// ID
	$r = '<tr id="tr-'.$Chapter->ID.'"class="'.$line_class.
					' chapter_parent_'.( $Chapter->parent_ID ? $Chapter->parent_ID : '0' ).
					// Fadeout?
					( isset($result_fadeout) && in_array( $Chapter->ID, $result_fadeout ) ? ' fadeout-ffff00': '' ).'">
					<td class="firstcol shrinkwrap">'.
						$Chapter->ID.'
				</td>';

	// Default
	if( $current_default_cat_ID == $Chapter->ID )
	{
		$makedef_icon = get_icon( 'enabled', 'imgtag', array( 'title' => format_to_output( T_( 'This is the default category' ), 'htmlattr' ) ) );
	}
	else
	{
		$makedef_url = regenerate_url( 'action,cat_ID', 'cat_ID='.$Chapter->ID.'&amp;action=make_default&amp;'.url_crumb('element') );
		$makedef_title = format_to_output( T_('Click to make this the default category'), 'htmlattr' );
		$makedef_icon = '<a href="'.$makedef_url.'" title="'.$makedef_title.'">'.get_icon( 'disabled', 'imgtag', array( 'title' => $makedef_title ) ).'</a>';
	}
	$r .= '<td class="center">'.$makedef_icon.'</td>';

	// Name
	if( $permission_to_edit )
	{	// We have permission permission to edit:
		$edit_url = regenerate_url( 'action,cat_ID', 'cat_ID='.$Chapter->ID.'&amp;action=edit' );
		$r .= '<td>
						<strong style="padding-left: '.($level).'em;"><a href="'.$edit_url.'" title="'.T_('Edit...').'">'.$Chapter->dget('name').'</a></strong>
					 </td>';
	}
	else
	{
		$r .= '<td>
						 <strong style="padding-left: '.($level).'em;">'.$Chapter->dget('name').'</strong>
					 </td>';
	}

	// URL "slug"
	$edit_url = regenerate_url( 'action,cat_ID', 'cat_ID='.$Chapter->ID.'&amp;action=edit' );
	$r .= '<td><a href="'.htmlspecialchars($Chapter->get_permanent_url()).'">'.$Chapter->dget('urlname').'</a></td>';

	// Order
	if( $Chapter->get_parent_subcat_ordering() == 'manual' )
	{ // Manual ordering
		$r .= '<td class="center cat_order_edit" rel="'.$Chapter->ID.'">'
						.'<a href="#">'.( $Chapter->get( 'order' ) === NULL ? '-' : $Chapter->dget( 'order' ) ).'</a>'
					.'</td>';
	}
	else
	{ // Alphabetic ordering
		$r .= '<td class="center">'.T_('Alphabetic').'</td>';
	}

	if( $permission_to_edit )
	{	// We have permission permission to edit, so display these columns:

		if( $Chapter->meta )
		{
			$makemeta_icon = 'enabled';
			$makemeta_title = format_to_output( T_('Click to revert this from meta category'), 'htmlattr' );
			$action = 'unset_meta';
		}
		else
		{
			$makemeta_icon = 'disabled';
			$makemeta_title = format_to_output( T_('Click to make this as meta category'), 'htmlattr' );
			$action = 'set_meta';
		}
		// Meta
		$makemeta_url = regenerate_url( 'action,cat_ID', 'cat_ID='.$Chapter->ID.'&amp;action='.$action.'&amp;'.url_crumb('element') );
		$r .= '<td class="center"><a href="'.$makemeta_url.'" title="'.$makemeta_title.'">'.get_icon( $makemeta_icon, 'imgtag', array( 'title' => $makemeta_title ) ).'</a></td>';

		// Lock
		if( $Chapter->lock )
		{
			$makelock_icon = 'file_not_allowed';
			$makelock_title = format_to_output( T_('Unlock category'), 'htmlattr' );
			$action = 'unlock';
		}
		else
		{
			$makelock_icon = 'file_allowed';
			$makelock_title = format_to_output( T_('Lock category'), 'htmlattr' );
			$action = 'lock';
		}
		$makelock_url = regenerate_url( 'action,cat_ID', 'cat_ID='.$Chapter->ID.'&amp;action='.$action.'&amp;'.url_crumb('element') );
		$r .= '<td class="center"><a href="'.$makelock_url.'" title="'.$makelock_title.'">'.get_icon( $makelock_icon, 'imgtag', array( 'title' => $makelock_title ) ).'</a></td>';
	}

	// Posts
	if( isset($number_of_posts_in_cat[$Chapter->ID]) )
	{
		$r .= '<td class="center">'.(int)$number_of_posts_in_cat[$Chapter->ID].'</td>';
	}
	else
	{	// no posts in this category
		$r .= '<td class="center"> - </td>';
	}

	// Actions
	$r .= '<td class="lastcol shrinkwrap">';
	if( $permission_to_edit )
	{	// We have permission permission to edit, so display action column:
		$r .= action_icon( T_('Edit...'), 'edit', $edit_url );
		if( $Settings->get('allow_moving_chapters') )
		{ // If moving cats between blogs is allowed:
			$r .= action_icon( T_('Move to a different blog...'), 'file_move', regenerate_url( 'action,cat_ID', 'cat_ID='.$Chapter->ID.'&amp;action=move' ), T_('Move') );
		}
		$r .= action_icon( T_('New...'), 'new', regenerate_url( 'action,cat_ID,cat_parent_ID', 'cat_parent_ID='.$Chapter->ID.'&amp;action=new' ) )
					.action_icon( T_('Delete...'), 'delete', regenerate_url( 'action,cat_ID', 'cat_ID='.$Chapter->ID.'&amp;action=delete&amp;'.url_crumb('element') ) );
	}
	$r .= '</td>';
	$r .=	'</tr>';

	return $r;
}


/**
 * Generate category line when it has no children
 *
 * @param object Chapter generic category we want to display
 * @param integer Level of the category in the recursive tree
 * @return string HTML
 */
function cat_no_children( $Chapter, $level )
{
	return '';
}


/**
 * Generate code when entering a new level
 *
 * @param int level of the category in the recursive tree
 * @return string HTML
 */
function cat_before_level( $level )
{
	return '';
}

/**
 * Generate code when exiting from a level
 *
 * @param int level of the category in the recursive tree
 * @return string HTML
 */
function cat_after_level( $level )
{
	return '';
}


$callbacks = array(
	'line'         => 'cat_line',
	'no_children'  => 'cat_no_children',
	'before_level' => 'cat_before_level',
	'after_level'  => 'cat_after_level'
);

//____________________________________ Display generic categories _____________________________________

$Table = new Table();

$Table->title = sprintf( T_('Categories for blog: %s'), $Blog->get_maxlen_name( 50 ).get_manual_link( 'categories-tab' ) );

$Table->global_icon( T_('Refresh'), 'refresh', regenerate_url( 'action,'.$ChapterCache->dbIDname ), T_('Refresh'), 3, 4, array( 'class' => 'action_icon btn-warning' ) );
$Table->global_icon( T_('Create a new category...'), 'new', regenerate_url( 'action,'.$ChapterCache->dbIDname, 'action=new' ), T_('New category').' &raquo;', 3, 4, array( 'class' => 'action_icon btn-primary' ) );

$Table->cols[] = array(
						'th' => T_('ID'),
					);
$Table->cols[] = array(
						'th' => T_('Default'),
						'th_class' => 'shrinkwrap',
					);
$Table->cols[] = array(
						'th' => T_('Name'),
					);
$Table->cols[] = array(
						'th' => T_('URL "slug"'),
					);

$Table->cols[] = array(
						'th' => T_('Order'),
						'th_class' => 'shrinkwrap',
					);

if( $permission_to_edit )
{	// We have permission permission to edit, so display these columns:
	$Table->cols[] = array(
						'th' => T_('Meta'),
						'th_class' => 'shrinkwrap',
					);

	$Table->cols[] = array(
						'th' => T_('Lock'),
						'th_class' => 'shrinkwrap',
					);
}

// TODO: dh> would be useful to sort by this
$Table->cols[] = array(
						'th' => T_('Posts'),
						'th_class' => 'shrinkwrap',
					);

if( $permission_to_edit )
{	// We have permission permission to edit, so display action column:
	$Table->cols[] = array(
							'th' => T_('Actions'),
						);
}

// Get # of posts for each category
global $number_of_posts_in_cat;
$number_of_posts_in_cat = $DB->get_assoc('
	SELECT cat_ID, count(postcat_post_ID) c
	FROM T_categories LEFT JOIN T_postcats ON postcat_cat_ID = cat_id
	WHERE cat_blog_ID = '.$DB->quote($subset_ID).'
	GROUP BY cat_ID');

$Table->display_init( array( 'list_attrib' => 'id="chapter_list"' ), $result_fadeout );

$results_params = $AdminUI->get_template( 'Results' );

echo $results_params['before'];

$Table->display_head();

echo $Table->params['content_start'];

$Table->display_list_start();

	$Table->display_col_headers();

	$Table->display_body_start();

	echo $ChapterCache->recurse( $callbacks, $subset_ID, NULL, 0, 0, array( 'sorted' => true ) );

	$Table->display_body_end();

$Table->display_list_end();

echo $Table->params['content_end'];

echo $results_params['after'];

/* fp> TODO: maybe... (a general group move of posts would be more useful actually)
echo '<p class="note">'.T_('<strong>Note:</strong> Deleting a category does not delete posts from that category. It will just assign them to the parent category. When deleting a root category, posts will be assigned to the oldest remaining category in the same collection (smallest category number).').'</p>';
*/

global $Settings, $dispatcher;

// Use a wrapper div to have margin around the form
echo '<div id="form_wrapper" style="margin: 2ex auto 1ex">';

$Form = new Form( NULL, 'cat_order_checkchanges', 'post', 'compact' );
$Form->begin_form( 'fform', T_('Category order').get_manual_link('categories_order') );
$Form->add_crumb( 'collection' );
$Form->hidden( 'ctrl', 'coll_settings' );
$Form->hidden( 'action', 'update' );
$Form->hidden( 'blog', $Blog->ID );
$Form->hidden( 'tab', 'chapters' );
$Form->radio_input( 'category_ordering', $Blog->get_setting('category_ordering'), array(
					array( 'value'=>'alpha', 'label'=>T_('Alphabetically') ),
					array( 'value'=>'manual', 'label'=>T_('Manually') ),
			 ), T_('Sort categories'), array( 'note'=>'('.T_('Note: can be overridden for sub-categories').')' ) );
$Form->end_form( array( array( 'submit', 'submit', T_('Save Changes!'), 'SaveButton' ) )  );

echo '</div>'; // form wrapper end

if( ! $Settings->get('allow_moving_chapters') )
{ // TODO: check perm
	echo '<p class="alert alert-info">'.sprintf( T_('<strong>Note:</strong> Moving categories across blogs is currently disabled in the %sblogs settings%s.'), '<a href="'.$dispatcher.'?ctrl=collections&tab=blog_settings#fieldset_wrapper_categories">', '</a>' ).'</p> ';
}

//Flush fadeout
$Session->delete( 'fadeout_array');


// Print JS to edit order of the chapters inline
echo_editable_column_js( array(
	'column_selector' => '.cat_order_edit',
	'ajax_url'        => get_secure_htsrv_url().'async.php?action=cat_order_edit&blogid='.$Blog->ID.'&'.url_crumb( 'catorder' ),
	'new_field_name'  => 'new_cat_order',
	'ID_value'        => 'jQuery( this ).attr( "rel" )',
	'ID_name'         => 'cat_ID',
	'field_type'      => 'text' ) );
?>