<?php
	/*
	 * This is the template that displays the calendar
	 *
	 * This file is not meant to be called directly.
	 * It is meant to be called by an include in the _main.php template.
	 */
	if(substr(basename($_SERVER['SCRIPT_FILENAME']),0,1)=='_')
		die("Please, do not access this page directly.");
	
	$Calendar = new Calendar( $blog, (empty($calendar)?$m:$calendar), $show_statuses, $timestamp_min, $timestamp_max );
	
	# You can customize the following as you wish.
	# Uncomment the lines you need
	
	// $Calendar->set( searchframe, 12 );	// How many month will we search back for a post before we give up

	// $Calendar->set( monthdisplay, 1 ); // set this to 0 if you don't want to display the month name
	// $Calendar->set( monthformat, 'F Y' );
	// $Calendar->set( monthstart, '<caption class="bCalendarMonth">' );
	// $Calendar->set( monthend, '</caption>' );
	
	// $Calendar->set( tablestart, '<table class="bCalendarTable" summary="Monthly calendar with links to each day\'s posts">' );
	// $Calendar->set( tableend, '</table>' );
	
	// $Calendar->set( rowstart, '<tr class="bCalendarRow">' );
	// $Calendar->set( rowend, '</tr>' );
	
	// $Calendar->set( headerdisplay, 1 );	// set this to 0 if you don't want to display "Mon Tue Wed..." 
	// $Calendar->set( headercellstart, '<th class="bCalendarHeaderCell" abbr="[abbr]">' );	// please leave $abbr there !
	// $Calendar->set( headercellend, '</th>' );
	
	// $Calendar->set( cellstart, '<td class="bCalendarCell">' );
	// $Calendar->set( cellend, '</td>' );
	
	// $Calendar->set( emptycellstart, '<td class="bCalendarEmptyCell">' );
	// $Calendar->set( emptycellend, '</td>' );
	// $Calendar->set( emptycellcontent, '&nbsp;' );
		

	// DUSPLAY NOW!
	$Calendar->display( );
	
?>
