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


	// --- //
	
	$dateformat = locale_datefmt();
	$time_difference=get_settings('time_difference');
	
	if (!$archive_date_format_over_ride) 
	{
		$archive_day_date_format = $dateformat;
		$archive_week_start_date_format = $dateformat;
		$archive_week_end_date_format   = $dateformat;
	}
		
	$ArchiveList = new ArchiveList( $blog, $archive_mode, $show_statuses,	$timestamp_min, $timestamp_max, $archive_limit );
	
	while( $ArchiveList->get_item( $arc_year, $arc_month, $arc_dayofmonth, $arc_w, $arc_count, $post_ID, $post_title) )
	{
		echo $archive_line_start;
		switch( $archive_mode )
		{
			case 'monthly':
				// --------------------------------- MONTHLY ARCHIVES ---------------------------------------
				echo '<a href="';
				archive_link( $arc_year, $arc_month );
				echo '">';
				echo T_($month[zeroise($arc_month,2)]),' ',$arc_year;
				echo '</a> <span class="dimmed">('.$arc_count.')</span>';
				break;
	
			case 'daily':
				// --------------------------------- DAILY ARCHIVES ---------------------------------------
				echo '<a href="';
				archive_link( $arc_year, $arc_month, $arc_dayofmonth );
				echo '">';
				echo mysql2date($archive_day_date_format, $arc_year.'-'.zeroise($arc_month,2).'-'.zeroise($arc_dayofmonth,2).' 00:00:00');
				echo '</a> <span class="dimmed">('.$arc_count.')</span>';
				break;
	
			case 'weekly':
				// --------------------------------- WEEKLY ARCHIVES ---------------------------------------
				$arc_ymd = $arc_year.'-'.zeroise($arc_month,2).'-' .zeroise($arc_dayofmonth,2);
				$arc_week = get_weekstartend($arc_ymd, $start_of_week);
				$arc_week_start = date_i18n($archive_week_start_date_format, $arc_week['start']);
				$arc_week_end = date_i18n($archive_week_end_date_format, $arc_week['end']);
				echo '<a href="';
				archive_link( $arc_year, '', '', $arc_w );
				echo '">';
				echo $arc_week_start.$archive_week_separator.$arc_week_end;
				echo '</a>';
			break;
	
			case 'postbypost':
			default:
				// --------------------------------- POSY BY POST ARCHIVES ---------------------------------------
				echo '<a href="';
				permalink_link( '', 'id', $post_ID );
				echo '">';
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