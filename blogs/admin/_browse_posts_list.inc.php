<?php
/**
 * This file implements the post browsing
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2005 by Francois PLANQUE - {@link http://fplanque.net/}.
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
 * @author fplanque: François PLANQUE.
 *
 * @version $Id$
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );

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
require_once( dirname(__FILE__).'/'.$admin_dirout.$core_subdir.'_calendar.class.php' );
require_once( dirname(__FILE__).'/'.$admin_dirout.$core_subdir.'_archivelist.class.php' );

echo '<div class="NavBar">';

	/*
	 * @movedTo _b2browse.php
	 */

	// Display title depending on selection params:
	request_title( '<h2>', '</h2>', '<br />', 'htmlbody', true, true, 'b2browse.php', 'blog='.$blog );

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
echo '<th>'.T_('Status')."</th>\n";
echo '<th>'.T_('Title')."</th>\n";
if( $Blog->allowcomments != 'never' )
{
	echo '<th>'.T_('Com')."</th>\n";
}
echo '<th>'.T_('Actions')."</th>\n";
echo "</tr>\n";

while( $Item = $MainList->get_item() )
{
	echo '<tr lang="';
	$Item->lang();
	echo '">';
	// We don't switch locales in the backoffice, since we use the user pref anyway

	echo '<td class="nowrap">';
	echo '<a href="';
	$Item->permalink();
	echo '" title="'.T_('Permanent link to full entry').'">'.get_icon( 'permalink' ).'</a> ';
	echo '<span class="date">';
	$Item->issue_date();
	echo '</span> <span class="time">';
	$Item->issue_time();
	echo "</span></td>\n";

	echo '<td>';
	$Item->Author->prefered_name();
	echo "</td>\n";

	echo '<td>';
	$Item->status();
	$Item->extra_status( ' - '.T_('Extra').':' );
	$Item->assigned_to( ' - '.T_('Assigned to').':');
	echo "</td>\n";

	echo '<td>';
	locale_flag( $Item->locale, 'w16px' );
	$Item->title();
	echo "</td>\n";

	if( $Blog->allowcomments != 'never' )
	{
		echo '<td>';
		echo '<a href="b2browse.php?tab=posts&amp;blog='.$blog.'&amp;p='.$Item->ID.'&amp;c=1" class="">';
		// TRANS: Link to comments for current post
		comments_number(T_('0 c'), T_('1 c'), T_('%d c'));
		trackback_number('', ' &middot; '.T_('1 tb'), ' &middot; '.T_('%d tb'));
		pingback_number('', ' &middot; '.T_('1 pb'), ' &middot; '.T_('%d pb'));
		echo '</a>';
		echo "</td>\n";
	}

	echo '<td>';
	// Display edit button if current user has the rights:
	$Item->edit_link( ' ', ' ', get_icon( 'edit' ), '#', '', $edit_item_url );

	// Display publish NOW button if current user has the rights:
	$Item->publish_link( ' ', ' ', '#', '#', '');

	// Display delete button if current user has the rights:
	$Item->delete_link( ' ', ' ', get_icon( 'delete' ), '#', '', false, $delete_item_url );

	echo "</td>\n";

 	echo '</tr>';

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