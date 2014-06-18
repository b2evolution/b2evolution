<?php
/**
 * This file implements the recursive chapter list.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
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
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id: _chapter_list.view.php 6225 2014-03-16 10:01:05Z attila $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );
//____________________ Callbacks functions to display categories list _____________________

/**
 * @var Blog
 */
global $Blog;

global $Settings;

global $GenericCategoryCache;

global $line_class;

global $permission_to_edit;

global $subset_ID;

global $current_default_cat_ID;

global $Session;

$result_fadeout = $Session->get( 'fadeout_array' );

$current_default_cat_ID = $Blog->get_setting('default_cat_ID');

$line_class = 'odd';


/**
 * Generate category line when it has children
 *
 * @param Chapter generic category we want to display
 * @param int level of the category in the recursive tree
 * @return string HTML
 */
function cat_line( $Chapter, $level )
{
	global $line_class, $permission_to_edit, $current_User, $Settings;
	global $GenericCategoryCache, $current_default_cat_ID;
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
	$r .= '<td><a href="'.evo_htmlspecialchars($Chapter->get_permanent_url()).'">'.$Chapter->dget('urlname').'</a></td>';

	// Order
	if( $Settings->get('chapter_ordering') == 'manual' )
	{
		$r .= '<td class="center">'.$Chapter->dget('order').'</td>';
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
 * @param Chapter generic category we want to display
 * @param int level of the category in the recursive tree
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
	'line' 			 	 => 'cat_line',
	'no_children'  => 'cat_no_children',
	'before_level' => 'cat_before_level',
	'after_level'	 => 'cat_after_level'
);

//____________________________________ Display generic categories _____________________________________

$Table = new Table();

$Table->title = sprintf( T_('Categories for blog: %s'), $Blog->get_maxlen_name( 50 ) );

$Table->global_icon( T_('Create a new category...'), 'new', regenerate_url( 'action,'.$GenericCategoryCache->dbIDname, 'action=new' ), T_('New category').' &raquo;', 3, 4  );

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
if( $Settings->get('chapter_ordering') == 'manual' )
{
	$Table->cols[] = array(
							'th' => T_('Order'),
							'th_class' => 'shrinkwrap',
						);
}
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

$Table->display_init( NULL, $result_fadeout );

// add an id for jquery to hook into
// TODO: fp> Awfully dirty. This should be handled by the Table object
$Table->params['list_start'] = str_replace( '<table', '<table id="chapter_list"', $Table->params['list_start'] );

$Table->display_head();

echo $Table->params['content_start'];

$Table->display_list_start();

	$Table->display_col_headers();

	$Table->display_body_start();

	echo $GenericCategoryCache->recurse( $callbacks, $subset_ID );

	$Table->display_body_end();

$Table->display_list_end();


/* fp> TODO: maybe... (a general group move of posts would be more useful actually)
echo '<p class="note">'.T_('<strong>Note:</strong> Deleting a category does not delete posts from that category. It will just assign them to the parent category. When deleting a root category, posts will be assigned to the oldest remaining category in the same collection (smallest category number).').'</p>';
*/

global $Settings, $dispatcher;

echo '<p class="note">'.sprintf( T_('<strong>Note:</strong> Ordering of categories is currently set to %s in the %sblogs settings%s.'),
	$Settings->get('chapter_ordering') == 'manual' ? /* TRANS: Manual here = "by hand" */ T_('Manual ') : T_('Alphabetical'), '<a href="'.$dispatcher.'?ctrl=collections&tab=settings#categories">', '</a>' ).'</p> ';

if( ! $Settings->get('allow_moving_chapters') )
{ // TODO: check perm
	echo '<p class="note">'.sprintf( T_('<strong>Note:</strong> Moving categories across blogs is currently disabled in the %sblogs settings%s.'), '<a href="'.$dispatcher.'?ctrl=collections&tab=settings#categories">', '</a>' ).'</p> ';
}

//Flush fadeout
$Session->delete( 'fadeout_array');

?>