<?php
/**
 * This file implements the AbstractImport class designed to handle any kind of Imports.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2019 by Francois Planque - {@link http://fplanque.com/}.
*
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Abstract Import Class
 *
 * @package evocore
 */
class AbstractImport
{
	var $import_code;
	var $coll_ID;
	var $log_file = true;
	var $log_errors_num = 0;

	/**
	 * Get collection
	 *
	 * @param object|NULL|FALSE Collection
	 */
	function & get_Blog()
	{
		$BlogCache = & get_BlogCache();
		$Blog = & $BlogCache->get_by_ID( $this->coll_ID );

		return $Blog;
	}


	/**
	 * Start to log into file on disk
	 */
	function start_log()
	{
		global $baseurl, $media_path, $rsc_url, $app_version_long;

		// Get file path for log:
		$log_file_path = $media_path.'import/logs/'
			// Current data/time:
			.date( 'Y-m-d-H-i-s' ).'-'
			// Site base URL:
			.str_replace( '/', '-', preg_replace( '#^https?://#i', '', trim( $baseurl, '/' ) ) ).'-'
			// Collection short name:
			.( $import_Blog = & $this->get_Blog() ? preg_replace( '#[^a-z\d]+#', '-', strtolower( $import_Blog->get( 'shortname' ) ) ).'-' : '' )
			// Suffix for this import tool:
			.$this->import_code.'-import-log-'
			// Random hash:
			.generate_random_key( 16 ).'.html';

		// Try to create folder for log files:
		if( ! mkdir_r( $media_path.'import/logs/' ) )
		{	// Display error if folder cannot be created for log files:
			$this->display_log_file_error( 'Cannot create the folder <code>'.$media_path.'import/logs/</code> for log files!' );
			return false;
		}

		if( ! ( $this->log_file_handle = fopen( $log_file_path, 'w' ) ) )
		{	// Display error if the log fiel cannot be created in the log folder:
			$this->display_log_file_error( 'Cannot create the file <code>'.$log_file_path.'</code> for current log!' );
			return false;
		}

		// Display where log will be stored:
		echo '<b>Log file:</b> <code>'.$log_file_path.'</code><br />';

		// Write header of the log file:
		$this->log_to_file( '<!DOCTYPE html>'."\r\n"
			.'<html lang="en-US">'."\r\n"
			.'<head>'."\r\n"
			.'<link href="'.$rsc_url.'css/bootstrap/bootstrap.css?v='.$app_version_long.'" type="text/css" rel="stylesheet" />'."\r\n"
			.'<link href="'.$rsc_url.'build/bootstrap-backoffice-b2evo_base.bundle.css?v='.$app_version_long.'" type="text/css" rel="stylesheet" />'."\r\n"
			.'</head>'."\r\n"
			.'<body>' );
	}


	/**
	 * Display error when log cannot be stored in file on disk
	 *
	 * @param string Message
	 */
	function display_log_file_error( $message )
	{
		if( empty( $this->log_file_error_reported ) )
		{	// Report only first detected error to avoid next duplicated errors on screen:
			echo '<p class="text-danger"><span class="label label-danger">ERROR</span> '.$message.'</p>';
			$this->log_file_error_reported = true;
		}
	}


	/**
	 * End of log into file on disk
	 */
	function end_log()
	{
		// Write footer of the log file:
		$this->log_to_file( '</body>'."\r\n"
			.'</html>' );

		if( isset( $this->log_file_handle ) && $this->log_file_handle )
		{	// Close the log file:
			fclose( $this->log_file_handle );
		}
	}


	/**
	 * Get a log message
	 *
	 * @param string Message
	 * @param string Type: 'success', 'error', 'warning', 'info'
	 * @param string HTML tag for type/styled log: 'p', 'span', 'b', etc.
	 * @param boolean TRUE to display label
	 * @return string|FALSE Formatted log message, FALSE - when message should not be displayed
	 */
	function get_log( $message, $type = NULL, $type_html_tag = 'p', $display_label = true )
	{
		if( $message === '' )
		{	// Don't log empty strings:
			return false;
		}

		switch( $type )
		{
			case 'success':
				$before = '<'.$type_html_tag.' class="text-success"> ';
				$after = '</'.$type_html_tag.'>';
				break;

			case 'error':
				$before = '<'.$type_html_tag.' class="text-danger">'.( $display_label ? '<span class="label label-danger">ERROR</span>' : '' ).' ';
				$after = '</'.$type_html_tag.'>';
				break;

			case 'warning':
				$before = '<'.$type_html_tag.' class="text-warning">'.( $display_label ? '<span class="label label-warning">WARNING</span>' : '' ).' ';
				$after = '</'.$type_html_tag.'>';
				break;

			case 'info':
				$before = '<'.$type_html_tag.' class="text-info">'.( $display_label ? '<span class="label label-info">INFO</span>' : '' ).' ';
				$after = '</'.$type_html_tag.'>';
				break;

			default:
				$before = '';
				$after = '';
				break;
		}

		return $before.$message.$after;
	}


	/**
	 * Log a message on screen and into file on disk
	 *
	 * @param string Message
	 * @param string Type: 'success', 'error', 'warning'
	 * @param string HTML tag for type/styled log: 'p', 'span', 'b', etc.
	 * @param boolean TRUE to display label
	 */
	function log( $message, $type = NULL, $type_html_tag = 'p', $display_label = true )
	{
		$message = $this->get_log( $message, $type, $type_html_tag, $display_label );

		if( $message === false )
		{	// Skip when message should not be displayed:
			return;
		}

		if( $display_label && ( $type == 'error' || $type == 'warning' ) )
		{	// Count a number of errors + warnings:
			$this->log_errors_num++;
		}

		// Display message on screen:
		echo $message;
		evo_flush();

		// Try to store a message into the log file on the disk:
		$this->log_to_file( $message );
	}


	/**
	 * Log SUCCESS message on screen and into file on disk
	 *
	 * @param string Message
	 * @param string HTML tag for type/styled log: 'p', 'span', 'b', etc.
	 * @param boolean TRUE to display label
	 */
	function log_success( $message, $type_html_tag = 'p', $display_label = true )
	{
		$this->log( $message, 'success', $type_html_tag, $display_label );
	}


	/**
	 * Log ERROR message on screen and into file on disk
	 *
	 * @param string Message
	 * @param string HTML tag for type/styled log: 'p', 'span', 'b', etc.
	 * @param boolean TRUE to display label
	 */
	function log_error( $message, $type_html_tag = 'p', $display_label = true )
	{
		$this->log( $message, 'error', $type_html_tag, $display_label );
	}


	/**
	 * Log WARNING message on screen and into file on disk
	 *
	 * @param string Message
	 * @param string HTML tag for type/styled log: 'p', 'span', 'b', etc.
	 * @param boolean TRUE to display label
	 */
	function log_warning( $message, $type_html_tag = 'p', $display_label = true )
	{
		$this->log( $message, 'warning', $type_html_tag, $display_label );
	}


	/**
	 * Log INFO message on screen and into file on disk
	 *
	 * @param string Message
	 * @param string HTML tag for type/styled log: 'p', 'span', 'b', etc.
	 * @param boolean TRUE to display label
	 */
	function log_info( $message, $type_html_tag = 'p', $display_label = true )
	{
		$this->log( $message, 'info', $type_html_tag, $display_label );
	}


	/**
	 * Log a message into file on disk
	 *
	 * @param string Message
	 */
	function log_to_file( $message )
	{
		if( ! $this->log_file )
		{	// Don't log into file:
			return;
		}

		if( ! isset( $this->log_file_handle ) || ! $this->log_file_handle )
		{	// Log must be started:
			$this->display_log_file_error( 'You must start log by function <code>'.get_class( $this ).'->start_log()</code>!' );
			return;
		}

		// Put a message into the log file on the disk:
		fwrite( $this->log_file_handle, $message."\r\n" );
	}
}
?>