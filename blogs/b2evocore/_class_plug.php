<?php
/**
 * This file implements the Plug class (EXPERIMENTAL)
 *
 * This is where you can plug-in some plug-ins :)
 * 
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package b2evocore
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );

require_once dirname(__FILE__)."/$core_dirout/$plugins_subdir/plugin.class.php";

/**
 * Plug Class
 */
class Plug
{
	/**#@+
	 * @access private
	 */

	var $collection;

	/**
	 * Array of loaded plug-ins:
	 */
	var $Plugins = array();
	var $index_Plugins = array();

	/**
	 * Path to plug-ins:
	 */
	var $plugins_path;

	/**
	 * Has the plug initialized? (plugins loaded?)
	 */
	var $initialized = false;
	
	/**
	 * Current object idx in array:
	 */
	var $current_idx = 0;
	
	/**#@-*/
	
	/* 
	 * Constructor
	 *
	 * {@internal Plug::Plug(-)}}
	 *
	 * @param string collection = name of plugins subdir
	 */
	function Plug( $collection )
	{
		global $core_dirout, $plugins_subdir;
		
		$this->collection = $collection;
		// Set plugin path for this collection:
		$this->plugins_path = dirname(__FILE__).'/'.$core_dirout.'/'.$plugins_subdir.'/'.$collection.'s';
		 
	}	

	/* 
	 * Initialize Plug if it has not been done before.
	 *
	 * Load the plugins.
	 *
	 * {@internal Plug::init(-)}}
	 */
	function init( )
	{
		if( ! $this->initialized )
		{
			// Go through directory:
			$this_dir = dir( $this->plugins_path );
			while( $this_file = $this_dir->read())
			{
				if( preg_match( '/^_.+\.'.$this->collection.'\.php$/', $this_file ) && is_file( $this->plugins_path. '/'. $this_file ) )
				{	// Valid plugin file name:
					// echo 'Loading ', $this_file, '...<br />';
					// Load the plugin:
					require $this->plugins_path. '/'. $this_file;
				}
			}
		
			// Sort array by priority:
			usort( $this->Plugins, 'sort_Plugin' );
		
			$this->initialized = true;
		}
	}

	/* 
	 * Register a plugin.
	 *
	 * Will be called by plugin includes when they are called by init()
	 *
	 * {@internal Plug::register(-)}}
	 *
	 * @access private
	 */
	function register( & $Plugin )
	{
		$this->Plugins[] = & $Plugin;
		$this->index_Plugins[ $Plugin->code ] = & $Plugin;
	}
	
		
	/* 
	 * Get next plugin in list:
	 *
	 * {@internal Plug::get_next(-)}}
	 *
	 * @return Plugin (false if no more plugin).
	 */
	function get_next()
	{
		$this->init();	// Init if not done yet.

		if( $this->current_idx >= count( $this->Plugins ) )
		{
			return false;
		}
		
		return $this->Plugins[ $this->current_idx++ ];
	}

	/**
	 * Rewind iterator
	 *
	 * {@internal Plug::restart(-) }}
	 */
	function restart()
	{
		$this->current_idx = 0;
	}
	
}

function sort_Plugin( & $a, & $b )
{
	return $a->priority - $b->priority;
}
?>
