<?php
	/*
	 * This is the template that displays the linkblog
	 *
	 * This file is not meant to be called directly.
	 * It is meant to be called by an include in the _main.php template.
	 */
	if(substr(basename($_SERVER['SCRIPT_FILENAME']),0,1)=='_')
		die("Please, do not access this page directly.");
		
	# maximum number of linkblog entries to display:
	if(!isset($linkblog_limit)) $linkblog_limit = 20;
	# global linkblog delimiters:
	if(!isset($linkblog_main_start)) $linkblog_main_start = '<div class="bSideItem"><h3>'.
																														T_('Linkblog').'</h3>';
	if(!isset($linkblog_main_end)) $linkblog_main_end = '</div>';
	# Category delimiters:
	if(!isset($linkblog_catname_before)) $linkblog_catname_before = '<h4>';
	if(!isset($linkblog_catname_after)) $linkblog_catname_after = '</h4><ul>';
	if(!isset($linkblog_catlist_end)) $linkblog_catlist_end = '</ul>';
	# Item delimiters:
	if(!isset($linkblog_item_before)) $linkblog_item_before = '<li>';
	if(!isset($linkblog_item_after)) $linkblog_item_after = '</li>';

	/*
	 * This skin has no special formatting for the linkblog, so...
	 * However you can replace this file with the full handler (in /blogs) and customize it!
	 * all we want to do here, is call the default linkblog handler.
	 */
	require get_path('skins').'/_linkblog.php';

?>