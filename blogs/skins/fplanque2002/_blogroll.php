<?php
	/*
	 * This is the template that displays the blogroll
	 *
	 * This file is not meant to be called directly.
	 * It is meant to be called by an include in the _main.php template.
	 */
	if(substr(basename($_SERVER['SCRIPT_FILENAME']),0,1)=='_')
		die("Please, do not access this page directly.");
		
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

	/*
	 * This skin has no special formatting for the blogroll, so...
	 * However you can replace this file with the full handler (in /blogs) and customize it!
	 * all we want to do here, is call the default blogroll handler.
	 */
	require dirname(__FILE__).'/../../_blogroll.php';

?>