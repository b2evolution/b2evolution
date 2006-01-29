<?php
/**
 * This file implements the post browsing
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 * {@internal
 * b2evolution is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * b2evolution is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with b2evolution; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
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

if( false )
{	/**
	 * This is ugly, sorry, but I temporarily need this until NuSphere fixes their CodeInsight :'(
	 */
	include('_header.php');
	include('b2browse.php');
}

/**
 * Includes:
 */
require_once( dirname(__FILE__).'/'.$admin_dirout.$core_subdir.'_itemlist.class.php' );

echo '<div class="NavBar">';

	/*
	 * @movedTo _b2browse.php
	 */

	// Display title depending on selection params:
	request_title( '<h2>', '</h2>', '<br />', 'htmlbody', true, false /* does not handle context */ );

	if( !$posts )
	{
		if( $posts_per_page )
		{
			$posts = $posts_per_page;
		}
		else
		{
			$posts = 10;
			$posts_per_page = $posts;
		}
	}

	if( !$poststart )
	{
		$poststart = 1;
	}

	if( !$postend )
	{
		$postend = $poststart + $posts - 1;
	}

	$nextXstart = $postend + 1;
	$nextXend = $postend + $posts;

	$previousXstart = ($poststart - $posts);
	$previousXend = $poststart - 1;
	if( $previousXstart < 1 )
	{
		$previousXstart = 1;
	}

	require dirname(__FILE__). '/_edit_navbar.php';

echo '</div>';

/*
 * Display posts:
 */
echo '<table class="grouped">';
echo "<tr>\n";
echo '<th>'.T_('Issue date')."</th>\n";
echo '<th>'.T_('Author')."</th>\n";
echo '<th colspan="2">'.T_('Visibility')."</th>\n";
echo '<th>'.T_('Title')."</th>\n";
if( $Blog->ID == 1 )
{ // "All blogs": display name of blog
	echo '<th>'.T_('Blog')."</th>\n";
}
echo '<th>'.T_('Actions')."</th>\n";
echo "</tr>\n";

$line_count = 0;
while( $Item = $MainList->get_item() )
{
	echo '<tr lang="';
	$Item->lang();
	echo '"';
	if( $line_count % 2 )
	{
		echo ' class="odd"';
	}
	echo '>';
	// We don't switch locales in the backoffice, since we use the user pref anyway

	echo '<td class="nowrap">';
	echo '<a href="';
	$Item->permalink();
	echo '" title="'.T_('Permanent link to full entry').'">'.get_icon( 'permalink' ).'</a> ';
	echo '<span class="date">';
	$Item->issue_date();
	echo "</span></td>\n";

	echo '<td>';
	$Item->Author->preferred_name();
	echo "</td>\n";

	echo '<td class="shrinkwrap">';
	// Display publish NOW button if current user has the rights:
	$Item->publish_link( ' ', ' ', get_icon( 'publish' ), '#', '');

	// Display deprecate if current user has the rights:
	$Item->deprecate_link( ' ', ' ', get_icon( 'deprecate' ), '#', '');
	echo "</td>\n";

	echo '<td>';
	$Item->status();
	echo "</td>\n";


	echo '<td>';
	locale_flag( $Item->locale, 'w16px' );

	if( $Blog->allowcomments != 'never' )
	{ // TODO: should use $Item->get_Blog() for $Blog == 1 (see also <th> for this).
		$nb_comments = generic_ctp_number($Item->ID, 'feedback');
		echo ' <a href="b2browse.php?tab=posts&amp;blog='.$blog.'&amp;p='.$Item->ID.'&amp;c=1&amp;tb=1&amp;pb=1"
						title="'.sprintf( T_('%d feedbacks'), $nb_comments ).'" class="">';
		if( $nb_comments )
		{
			echo get_icon( 'comments' );
		}
		else
		{
			echo get_icon( 'nocomment' );
		}
		echo '</a> ';
	}

	echo '<a href="b2browse.php?tab=posts&amp;blog='.$blog.'&amp;p='.$Item->ID.'&amp;c=1&amp;tb=1&amp;pb=1" class="">';
	$Item->title( '', '', false );
	echo '</a>';

	$Item->extra_status( ' - '.T_('Extra').':' );
	$Item->assigned_to( ' '.get_icon( 'assign', 'imgtag', array('title'=>T_('Assigned to')) ) );

	echo "</td>\n";


	if( $Blog->ID == 1 )
	{ // "All blogs": display name of blog, linked to browse this blog.
		echo '<td>';
		$Item_Blog = & $Item->get_Blog();

		echo '<a href="'.regenerate_url( 'blog', 'blog='.$Item_Blog->ID )
			.'" title="'.$Item_Blog->dget( 'name', 'htmlattr' )
			.'">'.$Item_Blog->dget( 'shortname', 'htmlattr' ).'</a>';
		echo "</td>\n";
	}

	// Actions:
	echo '<td class="shrinkwrap">';
	// Display edit button if current user has the rights:
	$Item->edit_link( ' ', ' ', get_icon( 'edit' ), '#', '', $edit_item_url );

	// Display delete button if current user has the rights:
	$Item->delete_link( ' ', ' ', get_icon( 'delete' ), '#', '', false, $delete_item_url );

	echo "</td>\n";

	echo '</tr>';
	$line_count++;
}
echo '</table>';


if( $MainList->get_total_num_posts() )
{ // don't display navbar twice if we have no post
	echo '<div class="NavBar">';
	require dirname(__FILE__). '/_edit_navbar.php';
	echo '</div>';
}
?>

<p class="center">
	<a href="<?php echo $add_item_url ?>"><img src="img/new.gif" width="13" height="13" class="middle" alt="" />
		<?php echo T_('New post...') ?></a>
</p>

<?php
/*
 * @movedTo _browse_posts_sidebar.inc.php
 */

/*
 * $Log$
 * Revision 1.19  2006/01/29 20:36:35  blueyed
 * Renamed Item::getBlog() to Item::get_Blog()
 *
 * Revision 1.18  2006/01/09 17:21:06  fplanque
 * no message
 *
 * Revision 1.17  2005/12/12 19:21:20  fplanque
 * big merge; lots of small mods; hope I didn't make to many mistakes :]
 *
 * Revision 1.16  2005/11/25 22:45:37  fplanque
 * no message
 *
 * Revision 1.15  2005/11/24 20:23:37  blueyed
 * minor (translation)
 *
 * Revision 1.14  2005/09/29 15:07:29  fplanque
 * spelling
 *
 * Revision 1.13  2005/09/06 17:13:53  fplanque
 * stop processing early if referer spam has been detected
 *
 * Revision 1.12  2005/09/01 17:11:46  fplanque
 * no message
 *
 * Revision 1.11  2005/08/31 19:06:41  fplanque
 * minor
 *
 * Revision 1.10  2005/08/26 16:34:51  fplanque
 * no message
 *
 * Revision 1.9  2005/08/17 21:01:34  fplanque
 * Selection of multiple authors with (-) option.
 * Selection of multiple categories with (-) and (*) options.
 *
 * Revision 1.8  2005/08/03 21:05:00  fplanque
 * cosmetic cleanup
 *
 * Revision 1.7  2005/08/02 18:13:55  fplanque
 * added "Deprecate now" function
 *
 * Revision 1.6  2005/07/04 13:41:38  fplanque
 * use TODO: instead of FIXME: in order to be consistent...
 *
 * Revision 1.5  2005/06/23 20:05:15  blueyed
 * For Blog 1 display the blog where the post is really from. Doc.
 *
 * Revision 1.4  2005/05/26 19:11:08  fplanque
 * no message
 *
 * Revision 1.3  2005/04/28 20:44:17  fplanque
 * normalizing, doc
 *
 * Revision 1.2  2005/04/15 18:02:57  fplanque
 * finished implementation of properties/meta data editor
 * started implementation of files to items linking
 *
 * Revision 1.1  2005/03/14 19:54:42  fplanque
 * no message
 *
 */
?>