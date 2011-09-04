<?php
/**
 * This file implements the recursive chapter list.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}
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
 * @version $Id$
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

	$r = '<tr id="tr-'.$Chapter->ID.'"class="'.$line_class.
					' chapter_parent_'.( $Chapter->parent_ID ? $Chapter->parent_ID : '0' ).
					// Fadeout?
					( isset($result_fadeout) && in_array( $Chapter->ID, $result_fadeout ) ? ' fadeout-ffff00': '' ).'">
					<td class="firstcol shrinkwrap">'.
						$Chapter->ID.'
				</td>';

	$makedef_url = regenerate_url( 'action,cat_ID', 'cat_ID='.$Chapter->ID.'&amp;action=make_default&amp;'.url_crumb('element') );
	$makedef_title = format_to_output( T_('Click to make this the default category'), 'htmlattr' );

	if( $current_default_cat_ID == $Chapter->ID )
	{
		$makedef_icon = 'enabled';
	}
	else
	{
		$makedef_icon = 'disabled';
	}
	$r .= '<td class="center"><a href="'.$makedef_url.'" title="'.$makedef_title.'">'.get_icon($makedef_icon, 'imgtag').'</a></td>';

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

	$edit_url = regenerate_url( 'action,cat_ID', 'cat_ID='.$Chapter->ID.'&amp;action=edit' );
	$r .= '<td><a href="'.htmlspecialchars($Chapter->get_permanent_url()).'">'.$Chapter->dget('urlname').'</a></td>';

	if( $Settings->get('chapter_ordering') == 'manual' )
	{
		$r .= '<td class="center">'.$Chapter->dget('order').'</td>';
	}

	if( isset($number_of_posts_in_cat[$Chapter->ID]) )
	{
		$r .= '<td class="center">'.(int)$number_of_posts_in_cat[$Chapter->ID].'</td>';
	}
	else
	{	// no posts in this category
		$r .= '<td class="center"> - </td>';
	}

	$r .= '<td class="lastcol shrinkwrap">';
	if( $permission_to_edit )
	{	// We have permission permission to edit, so display action column:
		$r .= '<a href="'.$makedef_url.'" title="'.$makedef_title.'">'.get_icon('activate', 'imgtag').'</a>';
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
$Table->params['head_title'] = str_replace( '<table', '<table id="chapter_list"', $Table->params['head_title'] );

$Table->display_list_start();

$Table->display_head();

$Table->display_body_start();

echo $GenericCategoryCache->recurse( $callbacks, $subset_ID );

$Table->display_body_end();

$Table->display_list_end();


/* fp> TODO: maybe... (a general group move of posts would be more useful actually)
echo '<p class="note">'.T_('<strong>Note:</strong> Deleting a category does not delete posts from that category. It will just assign them to the parent category. When deleting a root category, posts will be assigned to the oldest remaining category in the same collection (smallest category number).').'</p>';
*/

global $Settings, $dispatcher;
if( ! $Settings->get('allow_moving_chapters') )
{	// TODO: check perm
	echo '<p class="note">'.sprintf( T_('<strong>Note:</strong> Moving categories across blogs is currently disabled in the %sglobal settings%s.'), '<a href="'.$dispatcher.'?ctrl=features#categories">', '</a>' ).'</p> ';
}

echo '<p class="note">'.sprintf( T_('<strong>Note:</strong> Ordering of categories is currently set to %s in the %sglobal settings%s.'),
	$Settings->get('chapter_ordering') == 'manual' ? /* TRANS: Manual here = "by hand" */ T_('Manual ') : T_('Alphabetical'), '<a href="'.$dispatcher.'?ctrl=features#categories">', '</a>' ).'</p> ';

//Flush fadeout
$Session->delete( 'fadeout_array');
/*
 * $Log$
 * Revision 1.31  2011/09/04 22:13:13  fplanque
 * copyright 2011
 *
 * Revision 1.30  2011/01/02 02:25:33  sam2kb
 * Fixed http://forums.b2evolution.net/viewtopic.php?t=21653
 *
 * Revision 1.29  2011/01/02 02:20:25  sam2kb
 * typo: explicitely => explicitly
 *
 * Revision 1.28  2010/10/19 02:00:53  fplanque
 * MFB
 *
 * Revision 1.27  2010/10/16 22:04:28  sam2kb
 * Fixed hard-coded table prefix
 *
 * Revision 1.26  2010/07/26 06:52:15  efy-asimo
 * MFB v-4-0
 *
 * Revision 1.25.2.1  2010/07/06 00:24:37  fplanque
 * I believe the code for # of posts was unnecessarily complex.
 *
 * Revision 1.25  2010/05/24 19:13:44  sam2kb
 * Properly encode "title" text
 *
 * Revision 1.24  2010/05/18 07:08:51  efy-asimo
 * Notice in chapter_list_view - fix
 *
 * Revision 1.23  2010/04/30 20:37:10  blueyed
 * Add "Number of posts" column to chapter view. This is useful to get an overview about how often a category is being used.
 *
 * Revision 1.22  2010/02/08 17:52:07  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.21  2010/01/30 18:55:20  blueyed
 * Fix "Assigning the return value of new by reference is deprecated" (PHP 5.3)
 *
 * Revision 1.20  2010/01/13 19:19:47  efy-yury
 * update cahpters: crumbs, fadeouts, redirect, action_icon
 *
 * Revision 1.19  2010/01/03 13:10:58  fplanque
 * set some crumbs (needs checking)
 *
 * Revision 1.18  2009/07/06 23:52:24  sam2kb
 * Hardcoded "admin.php" replaced with $dispatcher
 *
 * Revision 1.17  2009/05/10 00:34:26  fplanque
 * better TRANS fix
 *
 * Revision 1.16  2009/04/28 19:52:39  blueyed
 * trans fix
 *
 * Revision 1.15  2009/04/13 11:33:33  tblue246
 * Fix translation conflict
 *
 * Revision 1.14  2009/03/08 23:57:42  fplanque
 * 2009
 *
 * Revision 1.13  2009/02/21 22:46:24  fplanque
 * ok I tried creating 10 categories in a row and I went nuts without the extra "add here" icon.
 *
 * Revision 1.12  2009/02/18 17:03:40  yabs
 * minor
 *
 * Revision 1.11  2009/02/18 10:15:47  yabs
 * Adding drag n drop hooks
 *
 * Revision 1.10  2009/02/13 13:58:41  waltercruz
 * Trying to clean (a bit) our UI
 *
 * Revision 1.9  2009/01/28 22:34:21  fplanque
 * Default cat for each blog can now be chosen explicitly
 *
 * Revision 1.8  2009/01/28 21:23:22  fplanque
 * Manual ordering of categories
 *
 * Revision 1.7  2008/12/28 22:55:55  fplanque
 * increase blog name max length to 255 chars
 *
 * Revision 1.6  2008/01/21 09:35:26  fplanque
 * (c) 2008
 *
 * Revision 1.5  2007/09/08 20:23:03  fplanque
 * action icons / wording
 *
 * Revision 1.4  2007/09/08 18:21:13  blueyed
 * isset() check for $result_fadeout[$GenericCategoryCache->dbIDname], failed for "cat_ID" in chapters controller
 *
 * Revision 1.3  2007/09/04 13:47:48  fplanque
 * fixed fadeout
 *
 * Revision 1.2  2007/09/04 13:23:18  fplanque
 * Fixed display for category screen.
 *
 * Revision 1.1  2007/06/25 10:59:27  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.10  2007/04/26 00:11:05  fplanque
 * (c) 2007
 *
 * Revision 1.9  2007/01/07 05:28:15  fplanque
 * i18n wording
 *
 * Revision 1.8  2006/12/11 00:32:26  fplanque
 * allow_moving_chapters stting moved to UI
 * chapters are now called categories in the UI
 *
 * Revision 1.7  2006/12/10 02:07:09  fplanque
 * doc
 *
 * Revision 1.6  2006/12/10 01:52:27  fplanque
 * old cats are now officially dead :>
 *
 * Revision 1.5  2006/12/09 17:59:34  fplanque
 * started "moving chapters accross blogs" feature
 *
 * Revision 1.4  2006/12/09 02:37:44  fplanque
 * Prevent user from creating loops in the chapter tree
 * (still needs a check before writing to DB though)
 *
 * Revision 1.3  2006/11/24 18:27:25  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 */
?>
