<?php 
	/*
	 * This is the template that displays the archive directory for a blog
	 *
	 * This file is not meant to be called directly.
	 * It is meant to be called by an include in the _main.php template.
	 * To display the archive directory, you should call a stub AND pass the right parameters
	 * For example: /blogs/index.php?disp=arcdir
	 */
	if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );

	if($disp == 'arcdir')  
	{ // We have asked to display full archives:
		echo '<ul>';
		/*
		 * This file is basically just a trick where we set the number of entries to display
		 * to "no limit"...
		 */
		$archive_limit = '';
		/*
		 * And then... call the default archive include.
		 */
		require dirname(__FILE__).'/_archives.php';
		unset( $archive_limit );
		echo '</ul>';
	}
?>