<div class="bPosts">
	<div class="bPost">
	<?php
	
	set_param( 'show_status', 'array', array( 'draft', 'published'), true );	// Array of cats to restrict to
	$show_statuses = '';
	$status_sep = '';
	foreach( $show_status as $this_status )
	{
		$show_statuses .= $status_sep."'".$this_status."'";
		$status_sep = ',';
	}

	set_param( 'show_past', 'integer', '0', true ); 
	set_param( 'show_future', 'integer', '0', true ); 
	if( ($show_past == 0) && ( $show_future == 0 ) )
	{
		$show_past = 1;
		$show_future = 1;
	}
	if( $show_past == 0 ) $timestamp_min = 'now';
	if( $show_future == 0 ) $timestamp_max = 'now';



	$skin = '';						// No skin will be used! We do the display below
	include(dirname(__FILE__).'/_blog_main.php');
	
	echo '<h2>';
	single_cat_title( " Category: ", 'htmlbody' );
	single_month_title( " Date: ", 'htmlbody' );
	single_post_title( " Post details: ", 'htmlbody'  );
	if ($c == "last") echo "Last comments";
	if ($stats) echo "Statistics";
	echo '</h2>';
	
	if (!$posts) 
	{
		if ($posts_per_page) 
		{
			$posts=$posts_per_page;
		} 
		else 
		{
			$posts=10;
			$posts_per_page=$posts;
		}
	}
	
	if (!$poststart) 
	{
		$poststart=1;
		$postend=$posts;
	}
	
	$nextXstart=$postend+1;
	$nextXend=$postend+$posts;
	
	$previousXstart=($poststart-$posts);
	$previousXend=$poststart-1;
	if( $previousXstart < 1 )
	{
		$previousXstart = 1;
	}
	// these lines are b2's "motor", do not alter nor remove them


	include dirname(__FILE__)."/_edit_navbar.php"; 
	?>
	</div>
	<?php
	while( $MainList->get_item() ) 
	{
		?>
  	<div class="bPost<?php the_status() ?>" lang="<?php the_lang() ?>">	
			<?php permalink_anchor(); ?>
			<div class="bSmallHead">
				<b><?php the_time('Y/m/d @ H:i:s'); ?></b> 
				by <?php the_author() ?> (<a href="javascript:profile(<?php the_author_ID() ?>)"><?php the_author_nickname() ?></a>), 
				in <?php the_categories( false, "<strong>", "</strong>", "", "", "<em>", "</em>") ?>
				- <?php the_language() ?>
				- Status: <?php the_status() ?>
			</div>
				
			<h3 class="bTitle"><?php the_title() ?></h3>

			<div class="bText">
				<?php
				if ($safe_mode)
					echo "<xmp>";
				the_content();
				if ($safe_mode)
					echo "</xmp>";
				?>
			</div>

			<p>
			<?php
			if (($user_level > $authordata[13]) or ($user_login == $authordata[1])) 
			{
				?>
				<form action="b2edit.php" method="get" class="inline">
					<input type="hidden" name="action" value="edit">
					<input type="hidden" name="post" value="<?php echo $postdata["ID"] ?>">
					<input type="submit" name="submit" value="&nbsp; Edit &nbsp;" class="search" />
				</form>
				<form action="edit_actions.php" method="get" class="inline">
					<input type="hidden" name="blog" value="<?php echo $blog ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="post" value="<?php echo $postdata["ID"] ?>"><input type="submit" name="submit" value="Delete" class="search" onclick="return confirm('You are about to delete this post \'<?php echo $row->post_title ?>\'\n\'Cancel\' to stop, \'OK\' to delete.')" />
				</form>
			<?php } ?>
				[ <a href="b2edit.php?blog=<?php echo $blog ?>&p=<?php echo $id ?>&c=1"><?php comments_number('no comment', '1 comment', "% comments") ?><?php trackback_number('', ', 1 trackback', ', % trackbacks') ?><?php pingback_number('', ', 1 pingback', ', % pingbacks') ?></a>
				- <a href="<?php permalink_single(); ?>" title="permalink">Permalink</a> ]
			</p>
				
			<?php

			// comments
			if (($withcomments) or ($c)) 
			{
				$queryc = "SELECT * FROM $tablecomments WHERE comment_post_ID = $id ORDER BY comment_date";
				$resultc = mysql_query($queryc);
				if ($resultc) {
				?>

				<a name="comments"></a>
				<h4>Comments:</h4>

				<?php
				while($rowc = mysql_fetch_object($resultc))
				{
					$commentdata = get_commentdata($rowc->comment_ID);
					?>
					<div class="bComment">
					<div class="bSmallHead">
						<b><?php comment_time('Y/m/d @ H:i:s'); ?></b> 
						by <b><?php comment_author() ?> ( <?php comment_author_email_link() ?> / <?php comment_author_url_link() ?> )</b> (IP: <?php comment_author_IP() ?>)
					</div>
					<div class="bText">
						<?php comment_text() ?>
					</div>
					<p>
					<?php 
					if (($user_level > $authordata[13]) or ($user_login == $authordata[1])) 
					{
					?>
					<form action="b2edit.php" method="get" class="inline">
						<input type="hidden" name="blog" value="<?php echo $blog ?>">
						<input type="hidden" name="action" value="editcomment">
						<input type="hidden" name="comment" value="<?php echo $commentdata['comment_ID'] ?>">
						<input type="submit" name="submit" value="&nbsp; Edit &nbsp;" class="search" />
					</form>
					<form action="edit_actions.php" method="get" class="inline">
						<input type="hidden" name="blog" value="<?php echo $blog ?>"><input type="hidden" name="action" value="deletecomment"><input type="hidden" name="comment" value="<?php echo $commentdata['comment_ID'] ?>"><input type="hidden" name="p" value="<?php echo $postdata["ID"] ?>"><input type="submit" name="submit" value="Delete" class="search" onclick="return confirm('You are about to delete this comment\n\'Cancel\' to stop, \'OK\' to delete.')" />
					</form>
					<?php
					}
					?>
					</p>
	
				</div>

				<?php //end of the loop, don't delete
				}

				if ($comment_error)
					echo "<p><font color=\"red\">Error: please fill the required fields (name & comment)</font></p>";
				?>

				<h4>Leave a comment:</h4>

				<!-- form to add a comment -->
				<form action="<?php echo $baseurl, '/', $pathhtsrv ?>/comment_post.php" method="post" class="bComment">
				
					<input type="hidden" name="comment_post_ID" value="<?php echo $id; ?>" />
					<input type="hidden" name="redirect_to" value="<?php echo $_SERVER["REQUEST_URI"]; ?>" />
					
					<fieldset>
						<div class="label"><label for="author">Name:</label></div>
						<div class="input"><input type="text" name="author" id="author" value="<?php echo $user_nickname ?>" size="40" tabindex="1" class="bComment" /></div>
					</fieldset>
			
					
					<fieldset>
						<div class="label"><label for="email">Email:</label></div>
						<div class="input"><input type="text" name="email" id="email" value="<?php echo $user_email ?>" size="40" tabindex="2" class="bComment" /><br />
							<span class="notes">Your email address will <strong>not</strong> be displayed on this site.</span>
						</div>
					</fieldset>
					
					<fieldset>
						<div class="label"><label for="url">Site/Url:</label></div>
						<div class="input"><input type="text" name="url" id="url" value="<?php echo $user_url ?>" size="40" tabindex="3" class="bComment" /><br />
							<span class="notes">Your URL will be displayed.</span>
						</div>
					</fieldset>
							
					<fieldset>
						<div class="label"><label for="comment">Comment text:</label></div>
						<div class="input"><textarea cols="40" rows="12" name="comment" id="comment" tabindex="4" class="bComment"></textarea><br />
							<span class="notes">Allowed XHTML tags: <?php echo htmlspecialchars(str_replace( '><',', ', $comment_allowed_tags)) ?><br />
							URLs, email, AIM and ICQs will be converted automatically.</span>
						</div>
					</fieldset>
							
					<?php if(substr($comments_use_autobr,0,4) == 'opt-') { ?>
					<fieldset>
						<div class="label">Options:</div>
						<div class="input"><input type="checkbox" name="comment_autobr" value="1" <?php if ($comments_use_autobr == 'opt-out') echo " checked=\"checked\"" ?> tabindex="6" id="comment_autobr" /> <label for="comment_autobr">Auto-BR</label> <span class="notes">(Line breaks become &lt;br&gt;)</span>
						</div>
					</fieldset>
					<?php } ?>
				
					<fieldset>
						<div class="input">
							<input type="submit" name="submit" class="buttonarea" value="Send comment" tabindex="8" />
						</div>
					</fieldset>
				
					<div class="clear"></div>
				
				</form>


				<?php // if you delete this the sky will fall on your head
				}
			}
			?>
	</div>
	<?php
	}
	?>
	<div class="bPost">
		<?php include dirname(__FILE__)."/_edit_navbar.php"; ?>
	</div>

</div>


<!-- ================================== START OF SIDEBAR ================================== -->

<div class="bSideBar">

	<div class="bSideItem">
	  <h3><?php bloginfo('name') ?></h3>
		<?php 
		$Calendar = new Calendar( $blog, ( empty($calendar) ? $m : $calendar ), '', $timestamp_min, $timestamp_max );
		
		$Calendar->display( $pagenow, 'blog='.$blog );
		?>
	</div>

	<div class="bSideItem">
		<form name="searchform" method="get" action="<?php echo $pagenow ?>">
			<h3><span style="float:right"><input type="submit" name="submit" value="Search" class="search" /></span>Search</h3>

			<input type="hidden" name="blog" value="<?php echo $blog ?>">

			<fieldset title="Posts to show">
				<legend>Posts to show</legend>
				<div>
				<input type="checkbox" name="show_past" value="1" id="ts_min" class="checkbox" <?php if( $show_past ) echo 'checked ' ?>/><label for="ts_min">Past</label><br />
				<input type="checkbox" name="show_future" value="1" id="ts_max" class="checkbox" <?php if( $show_future ) echo 'checked ' ?>/><label for="ts_max">Future</label>
				</div>
				
				<div>
				<input type="checkbox" name="show_status[]" value="draft" id="sh_draft" class="checkbox" <?php if( in_array( "draft", $show_status ) ) echo 'checked ' ?>/><label for="ts_min">Draft</label><br />
				<input type="checkbox" name="show_status[]" value="published" id="sh_published" class="checkbox" <?php if( in_array( "published", $show_status ) ) echo 'checked ' ?>/><label for="ts_max">Published</label>
				</div>
				
			</fieldset>

			<fieldset title="Text">
				<legend>Text</legend>
				<div>
				<input type="text" name="s" size="20" value="<?php echo $s ?>" class="SearchField" />
				</div>
				Words: <input type="radio" name="sentence" value="AND" id="sentAND" class="checkbox" <?php if( $sentence=='AND' ) echo 'checked ' ?>/><label for="sentAND">AND</label>
				<input type="radio" name="sentence" value="OR" id="sentOR" class="checkbox" <?php if( $sentence=='OR' ) echo 'checked ' ?>/><label for="sentOR">OR</label>
				<input type="radio" name="sentence" value="sentence" class="checkbox" id="sentence" <?php if( $sentence=='sentence' ) echo 'checked ' ?>/><label for="sentence">Sentence</label>
			</fieldset>

			<fieldset title="Archives">
				<legend>Archives</legend>
				<ul>
				<?php
				// this is what will separate your archive links
				$archive_line_start = '<li>';				
				$archive_line_end = '</li>';				
				// this is what will separate dates on weekly archive links
				$archive_week_separator = ' - ';
					
				$dateformat=get_settings('date_format');
				$time_difference=get_settings('time_difference');
				$archive_day_date_format = $dateformat;
				$archive_week_start_date_format = $dateformat;
				$archive_week_end_date_format   = $dateformat;

				$arc_link_start = $pagenow.$querystring_start.'blog'.$querystring_equal.$blog.$querystring_separator;

				$ArchiveList = new ArchiveList( $blog, $archive_mode, $show_statuses,	$timestamp_min, $timestamp_max, 36 );
				
				while( $ArchiveList->get_item( $arc_year, $arc_month, $arc_dayofmonth, $arc_w, $arc_count, $post_ID, $post_title) )
				{
					echo $archive_line_start;
					switch( $archive_mode )
					{
						case 'monthly':
							// --------------------------------- MONTHLY ARCHIVES ---------------------------------
							$arc_m = $arc_year.zeroise($arc_month,2);
							echo '<input type="radio" name="m" value="'.$arc_m.'" class="checkbox"';
							if( $m == $arc_m ) echo ' checked' ;
							echo ' />';
							echo '<a href="'.$arc_link_start.'m'.$querystring_equal.$arc_m.'">';
							echo $month[zeroise($arc_month,2)],' ',$arc_year;
							echo "</a> ($arc_count)";
							break;
				
						case 'daily':
							// --------------------------------- DAILY ARCHIVES -----------------------------------
							$arc_m = $arc_year.zeroise($arc_month,2).zeroise($arc_dayofmonth,2);
							echo '<input type="radio" name="m" value="'.$arc_m.'" class="checkbox"';
							if( $m == $arc_m ) echo ' checked' ;
							echo ' />';
							echo '<a href="'.$arc_link_start.'m'.$querystring_equal.$arc_m.'">';
							echo mysql2date($archive_day_date_format, $arc_year.'-'.zeroise($arc_month,2).'-'.zeroise($arc_dayofmonth,2).' 00:00:00');
							echo "</a> ($arc_count)";
							break;
				
						case 'weekly':
							// --------------------------------- WEEKLY ARCHIVES ---------------------------------
							$arc_ymd = $arc_year.'-'.zeroise($arc_month,2).'-' .zeroise($arc_dayofmonth,2);
							$arc_week = get_weekstartend($arc_ymd, $start_of_week);
							$arc_week_start = date_i18n($archive_week_start_date_format, $arc_week['start']);
							$arc_week_end = date_i18n($archive_week_end_date_format, $arc_week['end']);
							echo '<a href="'.$arc_link_start.'m'.$querystring_equal.$arc_year.$querystring_separator.'w'.$querystring_equal.$arc_w.'">';
							echo $arc_week_start.$archive_week_separator.$arc_week_end;
							echo '</a>';
						break;
				
						case 'postbypost':
						default:
							// ------------------------------- POSY BY POST ARCHIVES -----------------------------
							echo '<a href="'.$arc_link_start.'p'.$querystring_equal.$post_ID.'">';
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
				<legend>Categories</legend>
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
					global $blog, $querystring_start, $querystring_equal, $querystring_separator, $cat_array, $cat_line_start, $pagenow;
					$cat = get_the_category_by_ID( $cat_ID );
					echo $cat_line_start;
					echo '<label><input type="checkbox" name="catsel[]" value="'.$cat_ID.'" class="checkbox"';
					if( in_array( $cat_ID, $cat_array ) )
					{	// This category is in the current selection
						echo " checked";
					}
					echo ' />';
					echo "<a href=\"".$pagenow.$querystring_start."blog".$querystring_equal.$blog.$querystring_separator."cat".$querystring_equal.$cat_ID."\">".$cat['cat_name']."</a> (", $cat['cat_postcount'] ,')';
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
					cat_children( $cache_categories, $blog, NULL, cat_list_before_first, cat_list_before_each, cat_list_after_each, cat_list_after_last, 0 );
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
						cat_children( $cache_categories, $curr_blog_ID, NULL, cat_list_before_first, cat_list_before_each, cat_list_after_each, cat_list_after_last, 1 );
					}
				}
				// ----------------- END RECURSIVE CAT LIST ----------------
				?>
				</ul>
			</fieldset>
			
			<input type="submit" name="submit" value="Search" class="search" />
			[<a href="<?php echo $pagenow,'?blog=',$blog ?>">Reset</a>]
		</form>
		
	</div>

</div>
<div style="clear:both;"></div>