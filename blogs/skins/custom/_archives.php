<?php
	/*
	 * This is the template that displays the links to the archives for a blog
	 *
	 * This file is not meant to be called directly.
	 * It is meant to be called by an include in the _main.php template.
	 */
	if(substr(basename($_SERVER['SCRIPT_FILENAME']),0,1)=='_')
		die("Please, do not access this page directly.");

	# number of archive entries to display:
	if(!isset($archive_limit)) $archive_limit = 12;
	# this is what will separate your archive links
	if(!isset($archive_line_start)) $archive_line_start = '<li>';				
	if(!isset($archive_line_end)) $archive_line_end = '</li>';				
	# this is what will separate dates on weekly archive links
	if(!isset($archive_week_separator)) $archive_week_separator = ' - ';
	# override general date format ? 0 = no: use the date format set in Options, 1 = yes: override
	if(!isset($archive_date_format_over_ride)) $archive_date_format_over_ride = 0;
	# options for daily archive (only if you override the general date format)
	if(!isset($archive_day_date_format)) $archive_day_date_format = 'Y/m/d';
	# options for weekly archive (only if you override the general date format)
	if(!isset($archive_week_start_date_format)) $archive_week_start_date_format = 'Y/m/d';
	if(!isset($archive_week_end_date_format)) $archive_week_end_date_format   = 'Y/m/d';
	
	/*
	 * We now call the default archives handler...
	 * However you can replace this file with the full handler (in /blogs) and customize it!
	 */
	include dirname(__FILE__).'/../../_archives.php';

?>