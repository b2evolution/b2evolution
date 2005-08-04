<?php
	/**
	 * This is the template that displays the calendar
	 *
	 * This file is not meant to be called directly.
	 * It is meant to be called by an include in the _main.php template.
	 *
	 * b2evolution - {@link http://b2evolution.net/}
	 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
	 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}
	 *
	 * @package evoskins
	 * @subpackage custom
	 */
	if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );

	$Calendar = & new Calendar( $blog, (empty($calendar) ? $m : $calendar), $show_statuses, $timestamp_min, $timestamp_max );

	# You can customize the following as you wish.
	# Uncomment the lines you need

	// $Calendar->set( 'searchframe', 12 );	// How many month will we search back for a post before we give up

	// $Calendar->set( 'displaycaption', 1 ); // set this to 0 if you don't want to display the month name
	// $Calendar->set( 'monthformat', 'F Y' );
	// $Calendar->set( 'monthstart', '<caption class="bCalendarCaption">' );
	// $Calendar->set( 'monthend', '</caption>' );

	// $Calendar->set( 'tablestart', '<table class="bCalendarTable" summary="Monthly calendar with links to each day\'s posts">' );
	// $Calendar->set( 'tableend', '</table>' );

	// $Calendar->set( 'rowstart', '<tr class="bCalendarRow">' );
	// $Calendar->set( 'rowend', '</tr>' );

	// $Calendar->set( 'headerdisplay', 1 );	// set this to 0 if you don't want to display "Mon Tue Wed..."
	// $Calendar->set( 'headercellstart', '<th class="bCalendarHeaderCell" abbr="[abbr]">' );	// please leave $abbr there !
	// $Calendar->set( 'headercellend', '</th>' );

	// $Calendar->set( 'cellstart', '<td class="bCalendarCell">' );
	// $Calendar->set( 'cellend', '</td>' );

	// $Calendar->set( 'emptycellstart', '<td class="bCalendarEmptyCell">' );
	// $Calendar->set( 'emptycellend', '</td>' );
	// $Calendar->set( 'emptycellcontent', '&nbsp;' );

	$Calendar->set( 'browseyears', 1 );  // allow browsing years in the calendar's caption (month must be displayed)

	$Calendar->set( 'navigation', 'tfoot' );

	// $Calendar->set( 'linktomontharchive', 0 );  // uncomment to display month not as link to archive of month

	// -- Display number of posts with days/months ---
	// - set to '' (empty) to disable
	// - %d gets replaced with the number of posts on that day/month
	// - required spaces must be set
	// $Calendar->set( 'postcount_month_cell', '' );                           // in table cell (behind day)
	// $Calendar->set( 'postcount_month_atitle', T_('%d posts');   // in archive links title tag
	// $Calendar->set( 'postcount_year_cell', ' (%d)';                         // in table cell (behind abbr of month)
	// $Calendar->set( 'postcount_year_atitle', T_('%d posts');  // in archive links title tag


	// DISPLAY NOW!
	$Calendar->display( );

?>