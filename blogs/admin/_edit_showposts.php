<?php
/**
 * Displays list of post / browsing
 *
 * b2evolution - {@link http://b2evolution.net/}
 *
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 *
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package admin
 */
?>
<div class="bPosts">
	<div class="bPost">
	<?php
	require_once( dirname(__FILE__). '/'. $admin_dirout. '/'. $core_subdir. '/_class_itemlist.php' );
	require_once( dirname(__FILE__). '/'. $admin_dirout. '/'. $core_subdir. '/_class_calendar.php' );
	require_once( dirname(__FILE__). '/'. $admin_dirout. '/'. $core_subdir. '/_class_archivelist.php' );

	param( 'safe_mode', 'integer', 0 );         // Blogger style
	param( 'p', 'integer' );                    // Specific post number to display
	param( 'm', 'integer', '', true );          // YearMonth(Day) to display
	param( 'w', 'integer', '', true );          // Week number
	param( 'cat', 'string', '', true );         // List of cats to restrict to
	param( 'catsel', 'array', array(), true );  // Array of cats to restrict to
	param( 'author', 'integer', '', true );     // List of authors to restrict to
	param( 'order', 'string', 'DESC', true );   // ASC or DESC
	param( 'orderby', 'string', '', true );     // list of fields to order by
	param( 'posts', 'integer', '', true );      // # of posts to display on the page
	param( 'paged', 'integer', '', true );      // List page number in paged display
	param( 'poststart', 'integer', 1, true );   // Start results at this position
	param( 'postend', 'integer', '', true );    // End results at this position
	param( 's', 'string', '', true );           // Search string
	param( 'sentence', 'string', 'AND', true ); // Search for sentence or for words
	param( 'exact', 'integer', '', true );      // Require exact match of title or contents
	$preview = 0;
	param( 'c', 'string' );
	param( 'tb', 'integer', 0 );
	param( 'pb', 'integer', 0 );
	param( 'show_status', 'array', array( 'published', 'protected', 'private', 'draft', 'deprecated' ), true );	// Array of cats to restrict to
	$show_statuses = $show_status;
	param( 'show_past', 'integer', '0', true );
	param( 'show_future', 'integer', '0', true );
	if( ($show_past == 0) && ( $show_future == 0 ) )
	{
		$show_past = 1;
		$show_future = 1;
	}
	$timestamp_min = ( $show_past == 0 ) ? 'now' : '';
	$timestamp_max = ( $show_future == 0 ) ? 'now' : '';

	// Getting current blog info:
	$blogparams = get_blogparams_by_ID( $blog );

	// Get the posts to display:
	$MainList = & new ItemList( $blog, $show_statuses, $p, $m, $w, $cat, $catsel, $author, $order, $orderby, $posts, $paged, $poststart, $postend, $s, $sentence, $exact, $preview, '', '', $timestamp_min, $timestamp_max );

	$posts_per_page = $MainList->posts_per_page;
	$what_to_show = $MainList->what_to_show;
	$request = & $MainList->request;
	$result_num_rows = $MainList->get_num_rows();
	$postIDlist = & $MainList->postIDlist;
	$postIDarray = & $MainList->postIDarray;


	echo '<h2>';
	single_cat_title();
	single_month_title( T_(' Date range: '), 'htmlbody' );
	single_post_title();
	echo '</h2>';

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
	?>
	</div>
	<?php
	while( $Item = $MainList->get_item() )
	{
		?>
		<div class="bPost<?php $Item->scope( 'raw' ) ?>" lang="<?php $Item->lang() ?>">
			<?php $Item->anchor(); ?>
			<div class="bSmallHead">
				<?php
					echo '<strong>';
					$Item->issue_date(); echo ' @ '; $Item->issue_time();
					echo '</strong>';
					// TRANS: backoffice: each post is prefixed by "date BY author IN categories"
					echo ' ', T_('by'), ' ';
					$Item->Author->prefered_name();
					echo ' (';
					$Item->Author->login();
					echo ', ', T_('level:');
					$Item->Author->level();
					echo '), ';
					// TRANS: backoffice: each post is prefixed by "date BY author IN categories"
					echo T_('in'), ' ';
					$Item->categories( false );
					echo ' - ';
					$Item->language();
					echo ' - ', T_('Scope'), ': ';
					$Item->scope();
				?>
			</div>

			<h3 class="bTitle"><?php $Item->title() ?></h3>

			<div class="bText">
				<?php
				if( $safe_mode ) echo "<xmp>";
				$Item->content();
				if( $safe_mode ) echo "</xmp>";
				?>
			</div>

			<p style="clear:both;">
				<a href="<?php $Item->permalink() ?>" title="<?php echo T_('Permanent link to full entry') ?>" class="rightmargin"><img src="img/chain_link.gif" alt="<?php echo T_('Permalink') ?>" width="14" height="14" border="0" class="middle" /></a>
				<?php
				if( $current_User->check_perm( 'blog_post_statuses', $Item->get( 'scope' ), false, $blog ) )
				{
				?>
				<form action="b2edit.php" method="get" class="inline">
					<input type="hidden" name="action" value="edit">
					<input type="hidden" name="post" value="<?php $Item->ID() ?>">
					<input type="submit" name="submit" value="<?php /* TRANS: Edit button text (&nbsp; for extra space) */ echo T_('&nbsp; Edit &nbsp;') ?>" class="search" />
				</form>
				<?php
				}

				if( $current_User->check_perm( 'blog_del_post', 'any', false, $blog ) )
				{
				?>
				<form action="edit_actions.php" method="get" class="inline">
					<input type="hidden" name="action" value="delete"><input type="hidden" name="post" value="<?php $Item->ID() ?>"><input type="submit" name="submit" value="<?php echo T_('Delete') ?>" class="search" onclick="return confirm('<?php echo T_('You are about to delete this post!\\n\\\'Cancel\\\' to stop, \\\'OK\\\' to delete.') ?>')" />
				</form>
				<?php
				}

				if( ($postdata['Status'] != 'published')
						&& $current_User->check_perm( 'blog_post_statuses', 'published', false, $blog )
						&& $current_User->check_perm( 'edit_timestamp' ) )
				{
				?>
				<form action="edit_actions.php" method="get" class="inline"><input type="hidden" name="action" value="publish"><input type="hidden" name="post_ID" value="<?php $Item->ID() ?>"><input type="submit" name="submit" value="<?php echo T_('Publish NOW!') ?>" class="search" title="<?php echo T_('Publish now using current date and time.') ?>" />
				</form>
				<?php
				}
				?>
				[ <a href="b2browse.php?blog=<?php echo $blog ?>&p=<?php $Item->ID() ?>&c=1"><?php
				// TRANS: Link to comments for current post
				comments_number(T_('no comment'), T_('1 comment'), T_('% comments'));
				trackback_number('', ' &middot; '.T_('1 Trackback'), ' &middot; '.T_('% Trackbacks'));
				pingback_number('', ' &middot; '.T_('1 Pingback'), ' &middot; '.T_('% Pingbacks'));
				?></a> ]
			</p>

			<?php

			// comments
			if( $c )
			{ // We have request display of comments
				?>
				<a name="comments"></a>
				<h4><?php echo T_('Comments'), ', ', T_('Trackbacks'), ', ', T_('Pingbacks') ?>:</h4>
				<?php
			
				$CommentList = & new CommentList( 0, "'comment','trackback','pingback'", $show_statuses, $Item->ID, '', 'ASC' );
				
				$CommentList->display_if_empty( 
											'<div class="bComment"><p>' . 
											T_('No feedback for this post yet...') . 
											'</p></div>' );
			
				while( $Comment = $CommentList->get_next() )
				{	// Loop through comments:	
					?>
					<!-- ---------- START of a COMMENT/TB/PB ---------- -->
					<div class="bComment">
						<div class="bSmallHead">
							<?php
							$Comment->date();
							echo ' @ ';
							$Comment->time( 'H:i' );
							if( $Comment->author_url( '', ' &middot; Url: ', '' )
									&& $current_User->check_perm( 'spamblacklist', 'edit' ) )
							{ // There is an URL and we have permission to ban...
								$baseDomain = preg_replace("/http:\/\//i", "", $Comment->author_url);
								$baseDomain = preg_replace("/^www\./i", "", $baseDomain);
								$baseDomain = preg_replace("/\/.*/i", "", $baseDomain);
								?>
								<a href="b2antispam.php?action=ban&keyword=<?php echo urlencode($baseDomain) ?>"><img src="img/noicon.gif" class="middle" alt="<?php echo /* TRANS: Abbrev. */ T_('Ban') ?>" title="<?php echo T_('Ban this domain!') ?>" /></a>&nbsp;
								<?php 
							} 
							$Comment->author_email( '', ' &middot; Email: ' );
							$Comment->author_ip( ' &middot; IP: ' );
						 ?>
						</div>
						<div class="bCommentTitle">
						<?php
							switch( $Comment->get( 'type' ) )
							{
								case 'comment': // Display a comment: 
									echo T_('Comment from:') ?> 
									<?php break;
			
								case 'trackback': // Display a trackback:
									echo T_('Trackback from:') ?> 
									<?php break;
			
								case 'pingback': // Display a pingback:
									echo T_('Pingback from:') ?> 
									<?php break;
							} 
						?>
						<?php $Comment->author() ?> 
						</div>
						<div class="bCommentText">
							<?php $Comment->content() ?>
						</div>
						<p>
						<a href="<?php $Comment->permalink() ?>" title="<?php echo T_('Permamnent link to this comment')	?>" class="rightmargin"><img src="img/chain_link.gif" alt="<?php echo T_('Permalink') ?>" width="14" height="14" border="0" class="middle" /></a>
						<?php
						if( $current_User->check_perm( 'blog_comments', '', false, $blog ) )
						{	// If SUer has permission to edit comments:
						?>
						<form action="b2edit.php" method="get" class="inline">
							<input type="hidden" name="action" value="editcomment">
							<input type="hidden" name="comment" value="<?php $Comment->ID() ?>">
							<input type="submit" name="submit" value="<?php echo T_('&nbsp; Edit &nbsp;') ?>" class="search" />
						</form>
						<form action="edit_actions.php" method="get" class="inline">
							<input type="hidden" name="action" value="deletecomment"><input type="hidden" name="comment_ID" value="<?php $Comment->ID() ?>"><input type="submit" name="submit" value="<?php echo T_('Delete') ?>" class="search" onclick="return confirm('<?php printf( T_('You are about to delete this comment!\\n\\\'Cancel\\\' to stop, \\\'OK\\\' to delete.'), $row->post_title ) ?>')" />
						</form>
						<?php
						}
						?>
						</p>
	
					</div>
					<!-- ---------- END of a COMMENT/TB/PB ---------- -->
					<?php //end of the loop, don't delete
				}

				if( $Item->can_comment() )
				{ // User can leave a comment
				?>
				<!-- ---------- FORM to add a comment ---------- -->
				<h4><?php echo T_('Leave a comment') ?>:</h4>

				<form action="<?php echo $htsrv_url ?>/comment_post.php" method="post" class="bComment">

					<input type="hidden" name="comment_post_ID" value="<?php echo $id; ?>" />
					<input type="hidden" name="redirect_to" value="<?php echo $_SERVER["REQUEST_URI"]; ?>" />

					<fieldset>
						<div class="label"><label for="author"><?php echo T_('Name') ?>:</label></div>
						<div class="input"><input type="text" name="author" id="author" value="<?php echo $user_nickname ?>" size="40" class="bComment" /></div>
					</fieldset>


					<fieldset>
						<div class="label"><label for="email"><?php echo T_('Email') ?>:</label></div>
						<div class="input"><input type="text" name="email" id="email" value="<?php echo $user_email ?>" size="40" class="bComment" /><br />
							<span class="notes"><?php echo T_('Your email address will <strong>not</strong> be displayed on this site.') ?></span>
						</div>
					</fieldset>

					<fieldset>
						<div class="label"><label for="url"><?php echo T_('Site/Url') ?>:</label></div>
						<div class="input"><input type="text" name="url" id="url" value="<?php echo $user_url ?>" size="40" class="bComment" /><br />
							<span class="notes"><?php echo T_('Your URL will be displayed.') ?></span>
						</div>
					</fieldset>

					<fieldset>
						<div class="label"><label for="comment"><?php echo T_('Comment text') ?>:</label></div>
						<div class="input"><textarea cols="40" rows="12" name="comment" id="comment" class="bComment"></textarea><br />
							<span class="notes"><?php echo T_('Allowed XHTML tags'), ': ', htmlspecialchars(str_replace( '><',', ', $comment_allowed_tags)), '<br />', T_('URLs, email, AIM and ICQs will be converted automatically.'); ?></span>
						</div>
					</fieldset>

					<?php if(substr($comments_use_autobr,0,4) == 'opt-') { ?>
					<fieldset>
						<div class="label"><label><?php echo T_('Options') ?>:</label></div>
						<div class="input"><input type="checkbox" name="comment_autobr" value="1" <?php if ($comments_use_autobr == 'opt-out') echo ' checked="checked"' ?> id="comment_autobr" /> <label for="comment_autobr"><?php echo T_('Auto-BR') ?></label> <span class="notes"><?php echo T_('(Line breaks become &lt;br&gt;)') ?></span>
						</div>
					</fieldset>
					<?php } ?>

					<fieldset>
						<div class="input">
							<input type="submit" name="submit" value="<?php echo T_('Send comment') ?>" class="search" />
						</div>
					</fieldset>

					<div class="clear"></div>

				</form>
				<!-- ---------- END of FORM to add a comment ---------- -->
				<?php
				} // / can comment
		} // / comments requested
	?>
	</div>
	<?php
	}
	?>
	<div class="bPost">
		<?php require dirname(__FILE__). '/_edit_navbar.php'; ?>
	</div>

</div>


<!-- ================================== START OF SIDEBAR ================================== -->

<div class="bSideBar">

	<div class="bSideItem">
		<h3><?php $Blog->disp( 'name', 'htmlbody' ) ?></h3>
		<?php
		$Calendar = & new Calendar( $blog, ( empty($calendar) ? $m : $calendar ), '', $timestamp_min, $timestamp_max );

		$Calendar->display( $pagenow, 'blog='. $blog );
		?>
	</div>

	<div class="bSideItem">
		<form name="searchform" method="get" action="<?php echo $pagenow ?>">
			<h3><span style="float:right"><input type="submit" name="submit" value="<?php echo T_('Search') ?>" class="search" /></span><?php echo T_('Search') ?></h3>

			<input type="hidden" name="blog" value="<?php echo $blog ?>">

			<fieldset title="Posts to show">
				<legend><?php echo T_('Posts to show') ?></legend>
				<div>
				<input type="checkbox" name="show_past" value="1" id="ts_min" class="checkbox" <?php if( $show_past ) echo 'checked="checked" ' ?>/><label for="ts_min"><?php echo T_('Past') ?></label><br />

				<input type="checkbox" name="show_future" value="1" id="ts_max" class="checkbox" <?php if( $show_future ) echo 'checked="checked" ' ?>/><label for="ts_max"><?php echo T_('Future') ?></label>
				</div>

				<div>
				<input type="checkbox" name="show_status[]" value="published" id="sh_published" class="checkbox" <?php if( in_array( "published", $show_status ) ) echo 'checked="checked" ' ?>/><label for="sh_published"><?php echo T_('Published (Public)') ?></label><br/>

				<input type="checkbox" name="show_status[]" value="protected" id="sh_protected" class="checkbox" <?php if( in_array( "protected", $show_status ) ) echo 'checked="checked" ' ?>/><label for="sh_protected"><?php echo T_('Protected (Members only)') ?></label><br/>

				<input type="checkbox" name="show_status[]" value="private" id="sh_private" class="checkbox" <?php if( in_array( "private", $show_status ) ) echo 'checked="checked" ' ?>/><label for="sh_private"><?php echo T_('Private (You only)') ?></label><br/>

				<input type="checkbox" name="show_status[]" value="draft" id="sh_draft" class="checkbox" <?php if( in_array( "draft", $show_status ) ) echo 'checked ' ?>/><label for="sh_draft"><?php echo T_('Draft (Not published!)') ?></label><br />

				<input type="checkbox" name="show_status[]" value="deprecated" id="sh_deprecated" class="checkbox" <?php if( in_array( "deprecated", $show_status ) ) echo 'checked="checked" ' ?>/><label for="sh_deprecated"><?php echo T_('Deprecated (Not published!)') ?></label><br/>


				</div>

			</fieldset>

			<fieldset title="Text">
				<legend><?php echo T_('Title / Text contains') ?></legend>
				<div>
				<input type="text" name="s" size="20" value="<?php echo htmlspecialchars($s) ?>" class="SearchField" />
				</div>
				<?php echo T_('Words') ?>: <input type="radio" name="sentence" value="AND" id="sentAND" class="checkbox" <?php if( $sentence=='AND' ) echo 'checked="checked" ' ?>/><label for="sentAND"><?php echo T_('AND') ?></label>
				<input type="radio" name="sentence" value="OR" id="sentOR" class="checkbox" <?php if( $sentence=='OR' ) echo 'checked="checked" ' ?>/><label for="sentOR"><?php echo T_('OR') ?></label>
				<input type="radio" name="sentence" value="sentence" class="checkbox" id="sentence" <?php if( $sentence=='sentence' ) echo 'checked="checked" ' ?>/><label for="sentence"><?php echo T_('Entire phrase') ?></label>
			</fieldset>

			<fieldset title="Archives">
				<legend><?php echo T_('Archives') ?></legend>
				<ul>
				<?php
				// this is what will separate your archive links
				$archive_line_start = '<li>';
				$archive_line_end = '</li>';
				// this is what will separate dates on weekly archive links
				$archive_week_separator = ' - ';

				$dateformat = locale_datefmt();
				$archive_day_date_format = $dateformat;
				$archive_week_start_date_format = $dateformat;
				$archive_week_end_date_format   = $dateformat;

				$arc_link_start = $pagenow. '?blog='. $blog. '&amp;';

				$ArchiveList = & new ArchiveList( $blog, get_settings('archive_mode'), $show_statuses,	$timestamp_min, $timestamp_max, 36 );

				while( $ArchiveList->get_item( $arc_year, $arc_month, $arc_dayofmonth, $arc_w, $arc_count, $post_ID, $post_title) )
				{
					echo $archive_line_start;
					switch( get_settings('archive_mode') )
					{
						case 'monthly':
							// --------------------------------- MONTHLY ARCHIVES ---------------------------------
							$arc_m = $arc_year.zeroise($arc_month,2);
							echo '<input type="radio" name="m" value="'. $arc_m. '" class="checkbox"';
							if( $m == $arc_m ) echo ' checked="checked"' ;
							echo ' />';
							echo '<a href="'. $arc_link_start. 'm='. $arc_m. '">';
							echo T_($month[zeroise($arc_month,2)]), ' ', $arc_year;
							echo "</a> ($arc_count)";
							break;

						case 'daily':
							// --------------------------------- DAILY ARCHIVES -----------------------------------
							$arc_m = $arc_year.zeroise($arc_month,2).zeroise($arc_dayofmonth,2);
							echo '<input type="radio" name="m" value="'. $arc_m. '" class="checkbox"';
							if( $m == $arc_m ) echo ' checked="checked"' ;
							echo ' />';
							echo '<a href="'. $arc_link_start. 'm='. $arc_m. '">';
							echo mysql2date($archive_day_date_format, $arc_year. '-'. zeroise($arc_month,2). '-'. zeroise($arc_dayofmonth,2). ' 00:00:00');
							echo "</a> ($arc_count)";
							break;

						case 'weekly':
							// --------------------------------- WEEKLY ARCHIVES ---------------------------------
							$arc_ymd = $arc_year. '-'. zeroise($arc_month,2). '-'. zeroise($arc_dayofmonth,2);
							$arc_week = get_weekstartend($arc_ymd, $start_of_week);
							$arc_week_start = date_i18n($archive_week_start_date_format, $arc_week['start']);
							$arc_week_end = date_i18n($archive_week_end_date_format, $arc_week['end']);
							echo '<a href="'. $arc_link_start. 'm='. $arc_year. '&amp;w='. $arc_w. '">';
							echo $arc_week_start. $archive_week_separator. $arc_week_end;
							echo '</a>';
						break;

						case 'postbypost':
						default:
							// ------------------------------- POSY BY POST ARCHIVES -----------------------------
							echo '<a href="'. $arc_link_start. 'p='. $post_ID. '">';
							if ($post_title) {
								echo strip_tags($post_title);
							} else {
								echo $post_ID;
							}
							echo '</a>';
					}

					echo $archive_line_end."\n";
				}
				?>

				</ul>
			</fieldset>

			<fieldset title="Categories">
				<legend><?php echo T_('Categories') ?></legend>
				<ul>
				<?php
				$cat_line_start = '<li>';
				$cat_line_end = '</li>';
				$cat_group_start = '<ul>';
				$cat_group_end = '</ul>';
				# When multiple blogs are listed on same page:
				$cat_blog_start = '<li><strong>';
				$cat_blog_end = '</strong></li>';


				// ----------------- START RECURSIVE CAT LIST ----------------
				cat_query();	// make sure the caches are loaded
				if( ! isset( $cat_array ) ) $cat_array = array();
				function cat_list_before_first( $parent_cat_ID, $level )
				{	// callback to start sublist
					global $cat_group_start;
					if( $level > 0 ) echo "\n",$cat_group_start,"\n";
				}
				function cat_list_before_each( $cat_ID, $level )
				{	// callback to display sublist element
					global $blog, $cat_array, $cat_line_start, $pagenow;
					$cat = get_the_category_by_ID( $cat_ID );
					echo $cat_line_start;
					echo '<label><input type="checkbox" name="catsel[]" value="'. $cat_ID. '" class="checkbox"';
					if( in_array( $cat_ID, $cat_array ) )
					{	// This category is in the current selection
						echo ' checked="checked"';
					}
					echo ' />';
					echo '<a href="', $pagenow, '?blog=', $blog, '&amp;cat=', $cat_ID, '">', $cat['cat_name'], '</a> (', $cat['cat_postcount'] ,')';
					if( in_array( $cat_ID, $cat_array ) )
					{	// This category is in the current selection
						echo "*";
					}
					echo '</label>';
				}
				function cat_list_after_each( $cat_ID, $level )
				{	// callback to display sublist element
					global $cat_line_end;
					echo $cat_line_end,"\n";
				}
				function cat_list_after_last( $parent_cat_ID, $level )
				{	// callback to end sublist
					global  $cat_group_end;
					if( $level > 0 ) echo $cat_group_end,"\n";
				}

				if( $blog > 1 )
				{	// We want to display cats for one blog
					cat_children( $cache_categories, $blog, NULL, 'cat_list_before_first', 'cat_list_before_each', 'cat_list_after_each', 'cat_list_after_last', 0 );
				}
				else
				{	// We want to display cats for all blogs
					for( $curr_blog_ID=blog_list_start('stub');
								$curr_blog_ID!=false;
								 $curr_blog_ID=blog_list_next('stub') )
					{

						echo $cat_blog_start;
						?>
						<a href="<?php blog_list_iteminfo('blogurl') ?>"><?php blog_list_iteminfo('name') ?></a>
						<?php
						echo $cat_blog_end;

						// run recursively through the cats
						cat_children( $cache_categories, $curr_blog_ID, NULL, 'cat_list_before_first', 'cat_list_before_each', 'cat_list_after_each', 'cat_list_after_last', 1 );
					}
				}
				// ----------------- END RECURSIVE CAT LIST ----------------
				?>
				</ul>
			</fieldset>

			<input type="submit" name="submit" value="<?php echo T_('Search') ?>" class="search" />
			[<a href="<?php echo $pagenow,'?blog=',$blog ?>"><?php echo T_('Reset') ?></a>]
		</form>

	</div>

</div>
<div style="clear:both;"></div>