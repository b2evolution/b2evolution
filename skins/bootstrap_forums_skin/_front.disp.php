<?php
/**
 * This is the template that displays the links to the latest comments for a blog
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template.
 * To display a feedback, you should call a stub AND pass the right parameters
 * For example: /blogs/index.php?disp=comments
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 * @subpackage bootstrap_forums
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $number_of_posts_in_cat, $cat;

// Breadcrumbs
skin_widget( array(
		// CODE for the widget:
		'widget' => 'breadcrumb_path',
		// Optional display params
		'block_start'      => '<ol class="breadcrumb">',
		'block_end'        => '</ol><div class="clear"></div>',
		'separator'        => '',
		'item_mask'        => '<li><a href="$url$">$title$</a></li>',
		'item_active_mask' => '<li class="active">$title$</li>',
	) );

$chapters = $Skin->get_chapters( $cat );

if( count( $chapters ) > 0 )
{
?>
	<div class="panel panel-default forums_list front_panel">
<?php
	foreach( $chapters as $Chapter )
	{ // Loop through categories:
		if( $Chapter->meta )
		{ // Meta category
			$chapters_children = $Chapter->children;
?>
		<header class="panel-heading meta_category"><a href="<?php echo $Chapter->get_permanent_url(); ?>" class="forumlink"><?php echo $Chapter->dget( 'name' ); ?></a></header>
<?php
		}
		else
		{ // Simple category with posts
			$chapters_children = array( $Chapter );
		}
?>
		<section class="table table-hover">
<?php
		foreach( $chapters_children as $Chapter )
		{ // Loop through categories:
			if( $Chapter->lock )
			{ // Set icon for locked chapter
				$chapter_icon = 'fa-lock big';
				$chapter_icon_title = T_('This forum is locked: you cannot post, reply to, or edit topics.');
			}
			else
			{ // Set icon for unlocked chapter
				$chapter_icon = 'fa-folder big';
				$chapter_icon_title = T_('No new posts');
			}
?>
		<article class="container group_row">
			<div class="ft_status__ft_title col-lg-8 col-md-7 col-sm-7 col-xs-6">
				<div class="ft_status"><i class="icon fa <?php echo $chapter_icon; ?>" title="<?php echo $chapter_icon_title; ?>"></i></div>
				<div class="ft_title ellipsis">
					<a href="<?php echo $Chapter->get_permanent_url(); ?>" class="forumlink"><?php echo $Chapter->dget( 'name' ); ?></a>
					<?php
					if( $Chapter->dget( 'description' ) != '' )
					{
						echo '<br /><span class="ft_desc">'.$Chapter->dget( 'description' ).'</span>';
					}
					if( count( $Chapter->children ) > 0 )
					{ // Subforums exist
						echo '<div class="subcats">';
						echo T_('Subforums').': ';
						$cc = 0;
						foreach( $Chapter->children as $child_Chapter )
						{ // Display subforum
							echo '<a href="'.$child_Chapter->get_permanent_url().'" class="forumlink">'.$child_Chapter->get('name').'</a>';
							echo $cc < count( $Chapter->children ) - 1 ? ', ' : '';
							$cc++;
						}
						echo '</div>';
					}
					?>
				</div>
			</div>
			<div class="ft_count col-lg-1 col-md-1 col-sm-1 col-xs-2"><?php printf( T_('%s topics'), '<b>'.get_postcount_in_category( $Chapter->ID ).'</b>' ); ?></div>
			<div class="ft_count second_of_class col-lg-1 col-md-1 col-sm-1 col-xs-2"><?php printf( T_('%s replies'), '<b>'.get_commentcount_in_category( $Chapter->ID ).'</b>' ); ?></div>
			<div class="ft_date col-lg-2 col-md-3 col-sm-3"><?php echo $Chapter->get_last_touched_date( 'D M j, Y H:i' ); ?></div>
			<!-- Apply this on XS size -->
			<div class="ft_date_shrinked col-xs-2"><?php echo $Chapter->get_last_touched_date( 'm/j/y' ); ?></div>
		</article>
<?php
		}
?>
		</section>
<?php
	} // End of categories loop.
?>
	</div>
<?php
}
?>