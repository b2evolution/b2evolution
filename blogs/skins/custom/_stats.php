<?php 
	/*
	 * This is the template that displays stats for a blog
	 *
	 * This file is not meant to be called directly.
	 * It is meant to be called by an include in the _main.php template.
	 * To display the stats, you should call a stub AND pass the right parameters
	 * For example: /blogs/index.php?disp=stats
	 */
	if(substr(basename($_SERVER['SCRIPT_FILENAME']),0,1)=='_')
		die("Please, do not access this page directly.");

	/*
	 * We now call the default stats handler...
	 * However you can replace this file with the full handler (in /blogs) and customize it!
	 */
	require dirname(__FILE__).'/../../_stats.php';
?>