<?php
	/*
	 * This is the template that displays the blogroll
	 *
	 * This file is not meant to be called directly.
	 * It is meant to be called by an include in the _main.php template.
	 */
	if(substr(basename($_SERVER['SCRIPT_FILENAME']),0,1)=='_')
		die("Please, do not access this page directly.");

	if( ! $blogroll_blog )
	{	// No blogroll blog defined, revert to basic blogroll:
		bloginfo('blogroll');
		return;
	}


	# maximum number of blogroll entries to display:
	if(!isset($blogroll_limit)) $blogroll_limit = 20;
	# global blogroll delimiters:
	if(!isset($blogroll_main_start)) $blogroll_main_start = '';
	if(!isset($blogroll_main_end)) $blogroll_main_end = '';
	# Category delimiters:
	if(!isset($blogroll_catname_before)) $blogroll_catname_before = '<h4>';
	if(!isset($blogroll_catname_after)) $blogroll_catname_after = '</h4><ul>';
	if(!isset($blogroll_catlist_end)) $blogroll_catlist_end = '</ul>';
	# Item delimiters:
	if(!isset($blogroll_item_before)) $blogroll_item_before = '<li>';
	if(!isset($blogroll_item_after)) $blogroll_item_after = '</li>';


	// --- //
	
	
	// Load the blogroll blog:
	$BlogRollList = & new ItemList( $blogroll_blog, array(), '', '', '', $blogroll_cat, $blogroll_catsel, '', 'ASC', 'category title', '', '', '', '', '', '', '', '', $blogroll_limit, 'posts', $timestamp_min, $timestamp_max );

	
	// Dirty trick until we get everything into objects:
	$saved_blog = $blog;  
	$blog = $blogroll_blog;

	// Open the global list
	echo $blogroll_main_start;
		
	$previous_cat = '';
	$blogroll_cat = '';

	while( $BlogRollList->get_category_group() )
	{
		// Open new cat:
		echo $blogroll_catname_before;
		the_category();
		echo $blogroll_catname_after;
		
		while( $Item = $BlogRollList->get_item() )
		{
			echo $blogroll_item_before;
			$Item->title(); 
			echo ' ';
			$Item->content( 1, 0, T_('more'), '[', ']' );	// Description + more link 
			?>
			<a href="<?php $Item->permalink() ?>" title="<?php echo T_('Permanent link to full entry') ?>"><img src="img/icon_minipost.gif" alt="<?php echo T_('Permalink') ?>" width="12" height="9" class="middle" /></a>
			<?php
			echo $blogroll_item_after;
		}

		// Close cat
		echo $blogroll_catlist_end;
	}
	// Close the global list
	echo $blogroll_main_end;
	
	// Restore after dirty trick:
	$blog = $saved_blog;		
?>