<?php
/**
 * This is the template that displays the item block in list
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template (or other templates)
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 * @subpackage bootstrap_forums
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// Default params:
$params = array_merge( array(
		'post_navigation' => 'same_category', // In this skin, it makes no sense to navigate in any different mode than "same category"
	), $params );

global $Item, $cat, $disp;

/**
 * @var array Save all statuses that used on this page in order to show them in the footer legend
 */
global $legend_statuses, $legend_icons;

if( ! is_array( $legend_statuses ) )
{ // Init this array only first time
	$legend_statuses = array();
}
if( ! is_array( $legend_icons ) )
{ // Init this array only first time
	$legend_icons = array();
}

// Calculate what comments has the Item:
$comments_number = generic_ctp_number( $Item->ID, 'comments', get_inskin_statuses( $Item->get_blog_ID(), 'comment' ) );

$status_icon = 'fa-comments';
$status_title = '';
$status_alt = T_('Discussion topic');
$legend_icons['topic_default'] = 1;
if( $Item->is_featured() || $Item->is_intro() )
{ // Special icon for featured & intro posts
	$status_icon = 'fa-bullhorn';
	$status_alt = T_('Sticky topic / Announcement');
	$status_title = '<strong>'.T_('Sticky').':</strong> ';
	$legend_icons['topic_sticky'] = 1;
}
elseif( $Item->comment_status == 'closed' || $Item->comment_status == 'disabled' || $Item->is_locked() )
{ // The post is closed for comments
	$status_icon = 'fa-lock';
	$status_alt = T_('This topic is locked: you cannot edit posts or make replies.');
	$legend_icons['topic_locked'] = 1;
}
elseif( $comments_number > 25 )
{ // Popular topic is when coummnets number is more than 25
	$status_icon = 'fa-star';
	$status_alt = T_('Popular topic');
	$legend_icons['topic_popular'] = 1;
}
$Item->load_Blog();
// There is a very restrictive case in which we display workflow:
$display_workflow = ( $disp == 'posts' ) &&
    ! empty( $Item ) &&
    is_logged_in() &&
    $Blog->get_setting( 'use_workflow' ) &&
    $current_User->check_perm( 'blog_can_be_assignee', 'edit', false, $Blog->ID ) &&
    $current_User->check_perm( 'item_post!CURSTATUS', 'edit', false, $Item );
?>

<article class="container group_row posts_panel">
	<!-- Post Block -->
	<div class="ft_status__ft_title col-lg-8 col-md-8 col-sm-6 col-xs-12">

		<!-- Thread icon -->
		<div class="ft_status_topic">
			<a href="<?php echo $Item->permanent_url(); ?>">
				<i class="icon fa <?php echo $status_icon; ?>" title="<?php echo $status_alt; ?>"></i>
			</a>
		</div>

		<!-- Title / excerpt -->
		<div class="ft_title">
			<div class="posts_panel_title_wrapper">
				<div class="cell1">
					<div class="wrap">
						<?php
						echo $status_title;

						if( $Item->Blog->get_setting( 'track_unread_content' ) )
						{ // Display icon about unread status
							$Item->display_unread_status();
							// Update legend array to display the unread status icons in footer legend:
							switch( $Item->get_read_status() )
							{
								case 'new':
									$legend_icons['topic_new'] = 1;
									break;
								case 'updated':
									$legend_icons['topic_updated'] = 1;
									break;
							}
						}

						// Flag:
						$Item->flag();

						// Title:
						$Item->title( array(
								'link_class'      => 'topictitle ellipsis'.( $Item->get_read_status() != 'read' ? ' unread' : '' ),
								'post_navigation' => $params['post_navigation'],
							) );
						?>
					</div>
				</div>

				<?php
				if( $Skin->enabled_status_banner( $Item->status ) )
				{ // Status:
					$Item->format_status( array(
							'template' => '<div class="cell2"><div class="evo_status evo_status__$status$ badge" data-toggle="tooltip" data-placement="top" title="'.get_status_tooltip_title( $Item->status ).'">$status_title$</div></div>',
						) );
					$legend_statuses[] = $Item->status;
				}
				?>

			</div>
			<?php
			$Item->excerpt( array(
					'before' => '<div class="small ellipsis">',
					'after'  => '</div>',
				) );
			?>
		</div>

		<!-- Chapter -->
		<div class="ft_author_info ellipsis">
			<?php echo sprintf( T_('In %s'), $Item->get_chapter_links() ); ?>
		</div>
	</div>

	<!-- Replies Block -->
	<?php
	if( ! $display_workflow )
	{ // --------------------------------------------------------------------------------------------------------------------------
		echo '<div class="ft_count col-lg-1 col-md-1 col-sm-1 col-xs-5">';
		if( $comments_number == 0 && $Item->comment_status == 'disabled' )
		{ // The comments are disabled:
			echo T_('n.a.');
		}
		else if( $latest_Comment = & $Item->get_latest_Comment() )
		{	// At least one reply exists:
			printf( T_('%s replies'), '<div><a href="'.$latest_Comment->get_permanent_url().'" title="'.T_('View latest comment').'">'.$comments_number.'</a></div>' );
		}
		else
		{	// No replies yet:
			printf( T_('%s replies'), '<div>0</div>' );
		}

		echo '</div>';
	} // --------------------------------------------------------------------------------------------------------------------------

	echo '<!-- Assigned User Block -->';
	if( $display_workflow )
	{ // ==========================================================================================================================
		$assigned_User = $Item->get_assigned_User();
		$priority_color = item_priority_color( $Item->priority );
		$url = $Item->get_permanent_url().'#workflow_panel';

		// We offer 2 modes of displaying workflow.
		$worfklow_display_mode = $Skin->get_setting('workflow_display_mode'); // Possible values = 'assignee_and_status' or 'status_and_author'

		if( $worfklow_display_mode == 'assignee_and_status' )
		{
			if( $assigned_User )
			{
				echo '<div class="ft_assigned col-lg-2 col-md-2 col-sm-3 col-sm-offset-0 col-xs-6">';
				echo '<div class="ft_assigned_header">';
				echo '<a href="'.$url.'"  style="color: '.$priority_color.';">'.T_('Assigned to').':</a>';
				echo '</div>';

				// Assigned user avatar
				$Item->assigned_to2( array(
						'thumb_class' => 'ft_assigned_avatar',
						'link_class' => 'ft_assigned_avatar',
						'thumb_size'   => 'crop-top-32x32'
					) );

				echo '<div class="ft_assigned_info">';
				// Assigned user login
				$Item->assigned_to2( array(
					  'after' => '<br />',
						'link_text' => 'name'
					) );
			}
			else
			{
				echo '<div class="ft_not_assigned col-lg-2 col-md-2 col-sm-3 col-sm-offset-0 col-xs-6">';
				echo '<div class="ft_assigned_header">';
				echo '<a href="'.$url.'" style="color: '.$priority_color.';">'.T_('Not assigned').'</a>';
				echo '</div>';
				echo '<div class="ft_assigned_info">';
			}

			// Workflow status
			echo '<span><a href="'.$url.'">'.item_td_task_cell( 'status', $Item, false ).'</a></span>';
			echo '</div>';

			echo '</div>'; // /col
		}
		else
		{ // 'status_and_author'
			echo '<div class="ft_workflow_info ft_workflow_status_and_author col-lg-2 col-md-2 col-sm-3 col-sm-offset-0 col-xs-6">';
			echo '<div class="ft_date_header">';	// fp> temp hack to get correct style

			// Workflow status
			echo '<b><a href="'.$url.'">'.item_td_task_cell( 'status', $Item, false ).'</a></b>';
			echo '</div>';

			// b2evonet:
			$Item->author( array(
						'before'      => '',
						'after'       => '',
						'before_user' => '',
						'after_user'  => '',
						'link_text'   => 'only_avatar',
						'link_class'  => 'ft_author_avatar'
					) );

			echo '<div style="padding-left: 42px;">';

			// Post author
			echo $Item->author( array(
					'before'      => '',
					'before_user' => '',
					'after'       => '<br />',
					'after_user'  => '<br />',
					'link_text'   => 'auto',
				) );

			$Item->issue_date( array( 'date_format' => locale_datefmt() ) );

			echo '</div>';

			echo '</div>'; // /col
		}
	}	// ==========================================================================================================================

	echo '<!-- Last Comment Block -->';
	if( $display_workflow )
	{ // ==========================================================================================================================
		echo '<div class="ft_workflow_info ft_workflow_last_comment col-lg-2 col-md-2 col-sm-3 col-sm-offset-0 col-xs-6">';
		echo '<div class="ft_date_header">';
		if( $comments_number == 0 && $Item->comment_status == 'disabled' )
		{ // The comments are disabled:
			echo T_('n.a.');
		}
		else if( $latest_Comment = & $Item->get_latest_Comment() )
		{	// At least one reply exists:
			printf( T_('%s replies'), '<a href="'.$latest_Comment->get_permanent_url().'" title="'.T_('View latest comment').'">'.$comments_number.'</a>' );
		}
		else
		{	// No replies yet:
			printf( T_('%s replies'), '0' );
		}
		echo '</div>';
	} // ==========================================================================================================================
	else
	{ // --------------------------------------------------------------------------------------------------------------------------
		echo '<div class="ft_date col-lg-3 col-md-3 col-sm-4" style="margin-top: 12px;">';
	} // --------------------------------------------------------------------------------------------------------------------------

	if( $latest_Comment = & $Item->get_latest_Comment() )
	{ // Display info about last comment
		$latest_Comment->author2( array(
					'before'      => '',
					'after'       => '',
					'before_user' => '',
					'after_user'  => '',
					'link_text'   => 'only_avatar',
					'link_class'  => 'ft_author_avatar',
					'thumb_class' => 'ft_author_avatar',
				) );
		echo '<div style="padding-left: 42px;">';

		// Last comment author
		$latest_Comment->author2( array(
				'before'      => '',
				'before_user' => '',
				'after'       => '<br />',
				'after_user'  => '<br />',
				'link_text'   => 'auto',
			) );

		// Last comment date
		echo '<span class="last_mod_date">';
		$latest_Comment->date( $display_workflow ? locale_datefmt() : locale_extdatefmt().' '.locale_shorttimefmt() );
		echo '</span>';

		echo ' <a class="nowrap"  href="'.$latest_Comment->get_permanent_url().'" title="'.T_('View latest post')
				.'" class="icon_latest_reply"><i class="fa fa-arrow-right"></i>&nbsp;<i class="fa fa-file-o"></i></a>';
		echo '</div>';
	}
	else
	{ // No comments, Display info of post
		$Item->author( array(
					'before'      => '',
					'after'       => '',
					'before_user' => '',
					'after_user'  => '',
					'link_text'   => 'only_avatar',
					'link_class'  => 'ft_author_avatar'
				) );

		echo '<div style="padding-left: 42px;">';

		// Post author
		echo $Item->author( array(
				'before'      => '',
				'before_user' => '',
				'after'       => '<br />',
				'after_user'  => '<br />',
				'link_text'   => 'auto',
			) );

		// Last modification date
		echo '<span class="last_mod_date">';
		echo $display_workflow ? $Item->get_mod_date( locale_datefmt() ) : $Item->get_mod_date( locale_extdatefmt().' '.locale_shorttimefmt() );
		echo '</span>';

		echo ' <a class="nowrap" href="'.$Item->get_permanent_url().'" title="'.T_('View latest post')
				.'" class="icon_latest_reply"><i class="fa fa-arrow-right"></i>&nbsp;<i class="fa fa-file-o"></i></a>';
		echo '</div>';
	}
	echo '</div>';
	?>

	<?php if (! $display_workflow ) { ?>
	<!-- This is shrinked date that applies on lower screen res -->
	<div class="ft_date_shrinked item_list col-xs-6">
		<?php
		if( $latest_Comment = & $Item->get_latest_Comment() )
		{ // Display info about last comment
			$latest_Comment->author2( array(
							'link_text' => 'auto',
							'after' => ' ',
							'after_user' => ' '
				) );

			echo '<span class="datestamp_shrinked">';
			$latest_Comment->date( locale_datefmt() );
			echo '</span>';

			echo ' <a href="'.$latest_Comment->get_permanent_url().'" title="'.T_('View latest post')
					.'" class="icon_latest_reply"><i class="fa fa-arrow-right"></i>&nbsp;<i class="fa fa-file-o"></i></a>';
		}
		else
		{ // No comments, Display info of post
			echo $Item->author( array(
					'link_text' => 'auto',
					'after' => ' ',
					'after_user' => ' ',
				) );

			echo $Item->get_mod_date( locale_datefmt() );

			echo ' <a href="'.$Item->get_permanent_url().'" title="'.T_('View latest post').
					'" class="icon_latest_reply"><i class="fa fa-arrow-right"></i>&nbsp;<i class="fa fa-file-o"></i></a>';
		}
		?>
	</div>
	<?php } ?>
</article>