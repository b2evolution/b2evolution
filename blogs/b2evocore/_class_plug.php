<?php
/**
 * This file implements the Plug class. (EXPERIMENTAL)
 *
 * This is where you can plug-in some plug-ins :)
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package evocore
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );

/**
 * Includes:
 */
require_once dirname(__FILE__).'/_class_plugin.php';

/**
 * Plug Class
 *
 * @package evocore
 */
class Plug
{
	/**#@+
	 * @access private
	 */

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

	/**
	 * Constructor
	 *
	 * {@internal Plug::Plug(-)}}
	 *
	 */
	function Plug( )
	{
		global $core_dirout, $plugins_subdir;

		// Set plugin path:
		$this->plugins_path = dirname(__FILE__).'/'.$core_dirout.$plugins_subdir;

	}


	/**
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
				if( preg_match( '/^_.+\.plugin\.php$/', $this_file ) && is_file( $this->plugins_path. '/'. $this_file ) )
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


	/**
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
		// Memorizes Plugin in sequential array:
	 	$this->Plugins[] = & $Plugin;
		// Memorizes Plugin in code hash array:
		$this->index_Plugins[ $Plugin->code ] = & $Plugin;

		// Request event callback registrations:
		// events = $Plugin->RegisterEvents();

	}


	/**
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


	/**
	 * Call the plugins for a given event
	 *
	 * {@internal Plug::call_plugins(-)}}
	 *
	 * @param string event name, see {@link Plugin}
	 * @param array Associative array of parameters
	 */
	function call_plugins( $event, $params )
	{
		$this->init();	// Init if not done yet.

		$this->restart(); // Just in case.

		while( $loop_Plugin = $this->get_next() )
		{ // Go through whole list of plugins
			//echo ' ',$loop_Plugin->code, ':';

			$loop_Plugin->$event( $params );

		}
	}


	/**
	 * Validate renderer list
	 *
	 * {@internal Renderer::validate_list(-)}}
	 *
	 * @param array renderer codes
	 * @return array validated array
	 */
	function validate_list( $renderers = array('default') )
	{
		$this->init();	// Init if not done yet.

		$this->restart(); // Just in case.

		$validated_renderers = array();

		while( $loop_RendererPlugin = $this->get_next() )
		{ // Go through whole list of renders
			// echo ' ',$loop_RendererPlugin->code;

			switch( $loop_RendererPlugin->apply_when )
			{
				case 'stealth':
				case 'always':
					// echo 'FORCED';
					$validated_renderers[] = $loop_RendererPlugin->code;
					break;

				case 'opt-out':
					if( in_array( $loop_RendererPlugin->code, $renderers ) // Option is activated
						|| in_array( 'default', $renderers ) ) // OR we're asking for default renderer set
					{
						// echo 'OPT';
						$validated_renderers[] = $loop_RendererPlugin->code;
					}
					// else echo 'NO';
					break;

				case 'opt-in':
				case 'lazy':
					if( in_array( $loop_RendererPlugin->code, $renderers ) ) // Option is activated
					{
						// echo 'OPT';
						$validated_renderers[] = $loop_RendererPlugin->code;
					}
					// else echo 'NO';
					break;

				case 'never':
					// echo 'NEVER';
					continue;	// STOP, don't render, go to next renderer
			}
		}
		// echo count( $validated_renderers );
		return $validated_renderers;
	}


	/**
	 * Render the content
	 *
	 * {@internal Renderer::render(-)}}
	 *
	 * @param string content to render
	 * @param array renderer codes
	 * @param string Output format, see {@link format_to_output()}
	 * @return string rendered content
	 */
	function render( & $content, & $renderers, $format )
	{
		$this->init();	// Init if not done yet.

		$this->restart(); // Just in case.

		// echo implode(',',$renderers);

		while( $loop_RendererPlugin = $this->get_next() )
		{ // Go through whole list of renders
			//echo ' ',$loop_RendererPlugin->code, ':';

			switch( $loop_RendererPlugin->apply_when )
			{
				 case 'stealth':
				 case 'always':
					// echo 'FORCED ';
					$loop_RendererPlugin->render( $content, $format );
					break;

				 case 'opt-out':
				 case 'opt-in':
				 case 'lazy':
					if( in_array( $loop_RendererPlugin->code, $renderers ) )
					{	// Option is activated
						// echo 'OPT ';
						$loop_RendererPlugin->render( $content, $format );
					}
					// else echo 'NOOPT ';
					break;

				 case 'never':
					// echo 'NEVER ';
					break;	// STOP, don't render, go to next renderer
			}
		}

		return $content;
	}


	/**
	 * quick-render a string with a single renderer
	 *
	 * @param string what to render
	 * @param string renderercode
	 * @param string format to output, see {@link format_to_output()}
	 */
	function quick( $string, $renderercode, $format )
	{
		$this->init();
		if( isset($this->index_Plugins[ $renderercode ]) )
		{
			$this->index_Plugins[ $renderercode ]->render( $string, $format );
			return $string;
		}
		else
		{
			return format_to_output( $string, $format );
		}
	}
	
}



function sort_Plugin( & $a, & $b )
{
	return $a->priority - $b->priority;
}
?>