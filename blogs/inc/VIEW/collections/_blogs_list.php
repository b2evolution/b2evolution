<?php
/**
 * This file implements the UI view for the blogs list on blog management screens.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004 by The University of North Carolina at Charlotte as contributed by Jason Edgecombe {@link http://tst.uncc.edu/team/members/jason_bio.php}.
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package admin
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var User
 */
global $current_User;
/**
 * @var GeneralSettings
 */
global $Settings;

global $dispatcher;

$count = 0;
for( $curr_blog_ID = blog_list_start(); $curr_blog_ID != false; $curr_blog_ID = blog_list_next() )
{
	if( ! $current_User->check_perm( 'blog_properties', 'any', false, $curr_blog_ID )
	 && ! $current_User->check_perm( 'blog_cats', '', false, $curr_blog_ID ) )
	{ // Current user is not allowed to edit properties...
		continue;
	}

	// TABLE HEADER:
	if( !isset( $atleastoneshown ) )
	{ // Display headers the first time we find an viewable blog
		// TODO 0.9.1 : prewalk the list, this will also allow to know if at least one blog can be deleted
		$atleastoneshown = true;
		?>
		<div class="panelblock">
		<h2><?php echo T_('Blogs') ?>:</h2>
		<table class="grouped" cellspacing="0">
		<tr>
			<th class="firstcol"><?php echo T_('Blog') ?></th>
			<th><?php echo T_('Full Name') ?></th>
			<th><?php echo T_('Blog URL') ?></th>
			<th><?php echo T_('Static File') ?></th>
			<th><?php echo T_('Locale') ?></th>
			<th><?php echo T_('Actions') ?></th>
		</tr>
		<?php
	}

	?>
	<tr <?php if( $count % 2 == 1 ) echo 'class="odd"' ?>>
		<td class="firstcol"><strong>
			<?php
			blog_list_iteminfo('ID');
			echo '&nbsp;';
			if( $current_User->check_perm( 'blog_properties', 'edit', false, $curr_blog_ID ) )
			{
				$edit_url = regenerate_url( 'ctrl', 'ctrl=coll_settings&amp;blog='.$curr_blog_ID );
				echo action_icon( T_('Properties'), 'properties', $edit_url );
				echo '&nbsp;<a href="'.$edit_url.'" title="'.T_('Properties').'">';
				blog_list_iteminfo('shortname');
				echo '</a>';
			}
			else
			{
				blog_list_iteminfo('shortname');
			} ?>
			</strong>
			<?php
			if( $curr_blog_ID == $Settings->get('default_blog_ID') )
			{
				echo ' <span title="'.T_('Default blog on index.php').'">('.T_('Default').')</span>';
			}
			?>
		</td>

		<td><?php blog_list_iteminfo('name') ?></td>

		<td><a href="<?php blog_list_iteminfo('blogurl') ?>"><?php blog_list_iteminfo('suburl') ?></a></td>

		<td>
			<?php if( ($staticfilename = blog_list_iteminfo('staticfilename',false)) )
			{ // It is possible to generate a static page
			?>
			<a href="<?php blog_list_iteminfo('staticurl') ?>"><?php echo $staticfilename ?></a>
			<?php if( $current_User->check_perm( 'blog_genstatic', 'any', false, $curr_blog_ID ) )
				{ // Permission to generate a static page ?>
					[<a href="?ctrl=collections&amp;action=GenStatic&amp;blog=<?php blog_list_iteminfo('ID') ?>"><?php
						/* TRANS: abbrev. for "generate !" */ echo T_('Gen!') ?></a>]
					<?php
				}
			}
			?>
		</td>

		<td class="shrinkwrap"><?php locale_flag( blog_list_iteminfo('locale', false) ) ?></td>

		<?php

		// ACTION COLUMN:
		echo '<td class="shrinkwrap">';

		if( $current_User->check_perm( 'blog_properties', 'edit', false, $curr_blog_ID ) )
		{
			echo action_icon( T_('Edit settings...'), 'edit', regenerate_url( 'ctrl', 'ctrl=coll_settings&amp;blog='.$curr_blog_ID ) );
		}

		if( $current_User->check_perm( 'blog_cats', '', false, $curr_blog_ID ) )
		{
			echo action_icon( T_('Edit categories...'), 'edit', regenerate_url( 'ctrl', 'ctrl=chapters&amp;blog='.$curr_blog_ID ) );
		}

		if( $current_User->check_perm( 'blog_properties', 'edit', false, $curr_blog_ID ) )
		{
			echo action_icon( T_('Copy this blog...'), 'copy', regenerate_url( '', 'action=copy&amp;blog='.$curr_blog_ID ) );
			?>
			<a href="<?php echo regenerate_url( '', 'action=delete&amp;blog='.$curr_blog_ID ) ?>" style="color:red;font-weight:bold;" onclick="return confirm('<?php printf( TS_('Are you sure you want to delete blog #%d ?\\n\\nWARNING: This will delete ALL POST, COMMENTS,\\nCATEGORIES and other data related to that Blog!\\n\\nThis CANNOT be undone!'), $curr_blog_ID) ?>')"><?php echo get_icon( 'delete' ) ?></a>
			<?php
		}

		echo '</td>';

	echo '</tr>';

	$count++;
}

if( !isset( $atleastoneshown ) )
{ // no blog was listed because user has no rights
	echo '<div class="panelinfo">';
	echo '<P>'.T_('Sorry, you have no permission to edit/view any blog\'s properties.' ).'</p>';
}
else
{ // close table
	?>
	</table>
	<?php
}

if( $current_User->check_perm( 'blogs', 'create' ) )
{
	echo '<p class="center">'.action_icon( T_('New blog...'), 'new', url_add_param( $dispatcher, 'ctrl=collections&amp;action=new' ), T_('New blog...'), 3, 4 ).'</p>';
}
?>
</div>