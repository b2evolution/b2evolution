<?php
/**
 * This file implements the Timer class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 * @author blueyed: Daniel HAHLER.
 *
 * @version $Id: _timer.class.php 6135 2014-03-08 07:54:05Z manuel $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


// DEBUG: (Turn switch on or off to log debug info for specified category)
$GLOBALS['debug_timer'] = true;


/**
 * This is a simple class to allow timing/profiling of code portions.
 */
class Timer
{
	/**
	 * Remember times.
	 *
	 * We store for each category (primary key) the state, start/resume time and the total passed time.
	 *
	 * @access protected
	 */
	var $_times = array();


	/**
	 * @access protected
	 * @var integer Level of internal indentation, used to indent Debuglog messages.
	 */
	var $indent = 0;


	/**
	 * Constructor.
	 *
	 * @param string|NULL If a category is given the timer starts right away.
	 */
	function Timer( $category = NULL )
	{
		if( is_string($category) )
		{
			$this->start( $category );
		}
	}


	/**
	 * Reset a timer category.
	 */
	function reset( $category )
	{
		$this->_times[$category] = array( 'total' => 0, 'count' => 0 );
	}


	/**
	 * Start a timer.
	 */
	function start( $category, $log = true )
	{
		$this->reset( $category );
		$this->resume( $category, $log );
	}


	/**
	 * Stops a timer category. It may me resumed later on, see {@link resume()}. This is an alias for {@link pause()}.
	 *
	 * @return boolean false, if the timer had not been started.
	 */
	function stop( $category )
	{
		global $Debuglog;

		if( ! $this->pause( $category ) )
			return false;

		$Debuglog->add( str_repeat('&nbsp;', $this->indent*4).$category.' stopped at '.$this->get_duration( $category, 3 ), 'timer' );

		return true;
	}


	/**
	 * Pauses a timer category. It may me resumed later on, see {@link resume()}.
	 *
	 * NOTE: The timer needs to be started, either through the {@link Timer() Constructor} or the {@link start()} method.
	 *
	 * @return boolean false, if the timer had not been started.
	 */
	function pause( $category, $log = true )
	{
		global $Debuglog;
		$since_resume = $this->get_current_microtime() - $this->_times[$category]['resumed'];
		if( $log )
		{
			$this->indent--;
			if( $this->indent < 0 ) $this->indent = 0;
			$Debuglog->add( str_repeat('&nbsp;', $this->indent*4).$category.' paused at '.$this->get_duration( $category, 3 ).' (<strong>+'.number_format($since_resume, 4).'</strong>)', 'timer' );
		}
		if( $this->get_state($category) != 'running' )
		{ // Timer is not running!
			$Debuglog->add("Warning: tried to pause already paused '$category'.", 'timer');
			return false;
		}

		$this->_times[$category]['total'] += $since_resume;
		$this->_times[$category]['state'] = 'paused';

		return true;
	}


	/**
	 * Resumes the timer on a category.
	 */
	function resume( $category, $log = true )
	{
		global $Debuglog;

		if( !isset($this->_times[$category]['total']) )
		{
			$this->start( $category, $log );
			return;
		}

		$this->_times[$category]['resumed'] = $this->get_current_microtime();
		$this->_times[$category]['count']++;

		$this->_times[$category]['state'] = 'running';

		if( $log )
		{
			$Debuglog->add( str_repeat('&nbsp;', $this->indent*4).$category.' resumed at '.$this->get_duration( $category, 3 ), 'timer' );
			$this->indent++;
		}
	}


	/**
	 * Get the duration for a given category in seconds,microseconds (configurable number of decimals)
	 *
	 * @param string Category name
	 * @param integer Number of decimals after dot.
	 * @return string
	 */
	function get_duration( $category, $decimals = 3 )
	{
		return number_format( $this->get_microtime($category), $decimals ); // TODO: decimals/seperator by locale!
	}


	/**
	 * Log a duration with Application Performance Monitor
	 *
	 * @param mixed $category
	 */
	function log_duration( $category )
	{
		apm_log_custom_metric( $category, $this->get_microtime($category) * 1000 );
	}


	/**
	 * Get number of timer resumes (includes start).
	 *
	 * @return integer
	 */
	function get_count( $category )
	{
		if( isset( $this->_times[$category] ) )
		{
			return $this->_times[$category]['count'];
		}

		return false;
	}


	/**
	 * Get the time that was spent in the given category in seconds with microsecond precision
	 *
	 * @return float (seconds.microseconds)
	 */
	function get_microtime( $category )
	{
		switch( $this->get_state($category) )
		{
			case 'running':
				// The timer is running, we need to return the additional time since the last resume.
				return $this->_times[$category]['total']
					+ $this->get_current_microtime() - $this->_times[$category]['resumed'];

			case 'paused':
				return $this->_times[$category]['total'];

			default:
				return (float)0;
		}
	}


	/**
	 * Get the state a category timer is in.
	 *
	 * @return string 'unknown', 'not initialised', 'running', 'paused'
	 */
	function get_state( $category )
	{
		if( !isset($this->_times[$category]) )
		{
			return 'unknown';
		}

		if( !isset($this->_times[$category]['state']) )
		{
			return 'not initialised';
		}

		return $this->_times[$category]['state'];
	}


	/**
	 * Get a list of used categories.
	 *
	 * @return array
	 */
	function get_categories()
	{
		return array_keys( $this->_times );
	}


	/**
	 * Get the current time with microsecond precision
	 *
	 * @return float (seconds.microseconds)
	 */
	function get_current_microtime()
	{
		list($usec, $sec) = explode(' ', microtime());
		return ((float)$usec + (float)$sec);
	}
}
?>