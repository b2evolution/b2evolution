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
	 * Load the installed plugins.
	 *
	 * {@internal Plug::init(-)}}
	 */
	function init( )
	{
		global $DB, $Debuglog;

		if( ! $this->initialized )
		{
			$Debuglog->add( 'Loading plugins...' );
			$rows = $DB->get_results( 'SELECT *
																	 FROM T_plugins
																	ORDER BY plug_priority', ARRAY_A );
			if( count($rows) ) foreach( $rows as $row )
			{	// Loop through installed plugins:
				$filename = $this->plugins_path.'_'.str_replace( '_plugin', '.plugin', $row['plug_classname'] ).'.php';
				if( ! is_file( $filename ) )
				{	// Plugin not found!
					$Debuglog->add( 'Plugin not found: '.$filename );
					continue;
				}
				// Load the plugin:
				$Debuglog->add( 'Loading plugin: '.$row['plug_classname'] );
				require_once $filename;
				// Register the plugin:
				$this->register( $row['plug_classname'], $row['plug_ID'] );
			}

			$this->initialized = true;
		}
	}


	/**
	 * Discover and load all available plugins plugins.
	 *
	 * {@internal Plug::discover(-)}}
	 */
	function discover()
	{
		global $Debuglog;

		if( ! $this->initialized )
		{
			$Debuglog->add( 'Discovering plugins...' );

			// Go through directory:
			$this_dir = dir( $this->plugins_path );
			while( $this_file = $this_dir->read())
			{
				if( preg_match( '/^_(.+)\.plugin\.php$/', $this_file, $matches ) && is_file( $this->plugins_path. '/'. $this_file ) )
				{	// Valid plugin file name:
	        $Debuglog->add( 'Loading plugin: '.$this_file );
					// Load the plugin:
					require_once $this->plugins_path.$this_file;
					// Register the plugin:
					$classname = $matches[1].'_plugin';
					$this->register( $classname, 0 );
				}
			}

			// Sort array by priority:
			usort( $this->Plugins, 'sort_Plugin' );

			$this->initialized = true;
		}
	}


	/**
	 * Install a plugin
	 *
	 * Records it in the database
	 *
	 * {@internal Plug::install(-)}}
	 */
	function install( $plugin_name )
	{
		global $DB, $Debuglog;

		$this->init();	// Init if not done yet.

		// Load the plugin:
		$filename = $this->plugins_path.'_'.str_replace( '_plugin', '.plugin', $plugin_name ).'.php';
		require_once $filename;

		// Register the plugin:
		$Plugin = & $this->register( $plugin_name, 0 );	// ID will be set a few lines below

		// Sort array by priority:
		usort( $this->Plugins, 'sort_Plugin' );

		// Record into DB
		//$DB->begin();

		//$max_order = $DB->get_var( 'SELECT MAX(plug_order) FROM T_plugin' );

		$DB->query( "INSERT INTO T_plugins( plug_classname, plug_priority )
									VALUES( '$plugin_name', $Plugin->priority ) " );

		//$DB->commit();

		$Plugin->ID = $DB->insert_id;
		$Debuglog->add( 'New plugin: '.$Plugin->name.' ID: '.$Plugin->ID );

	}


	/**
	 * Uninstall a plugin
	 *
	 * Removes it from the database
	 *
	 * {@internal Plug::uninstall(-)}}
	 *
	 * @return boolean success
	 */
	function uninstall( $plugin_ID )
	{
		global $DB, $Debuglog;

		$this->init();	// Init if not done yet.

		// Delete from DB
		if( ! $DB->query( "DELETE FROM T_plugins
												WHERE plug_ID = $plugin_ID" ) )
		{	// Nothing removed!?
			return false;
		}

		// for( $i = 0; $i < count( $this->Plugins ); $i++ )
		$move_by = 0;
		$items = count($this->Plugins);
		foreach( $this->Plugins as $key => $Plugin )
		{	// Go through plugins:
			if( $Plugin->ID == $plugin_ID )
			{	// This one must be unregistered...
				unset( $this->index_Plugins[ $Plugin->code ] );
				$move_by--;
			}
			elseif($move_by)
			{	// This is a normal one but must be moved up
				$this->Plugins[$key+$move_by] = & $this->Plugins[$key];
			}

			if( $key >= $items+$move_by )
			{	// We are reaching the end of the array, we should unset
				unset($this->Plugins[$key]);
			}
		}
		// unset( $this->Plugins[ $key ] );
		return true;
	}


	/**
	 * Register a plugin.
	 *
	 * Will be called by plugin includes when they are called by init()
	 *
	 * {@internal Plug::register(-)}}
	 *
	 * @param string name of plugin class to instanciate & register
	 * @param int ID in database (0 if not installed)
	 * @return Plugin ref to newly created plugin
	 * @access private
	 */
	function & register( $classname, $ID = 0 )
	{
		$Plugin = new $classname;	// COPY !

		// Tell him his ID :)
		$Plugin->ID = $ID;
		// Tell him his name :)
		$Plugin->classname = $classname;

		// Memorizes Plugin in sequential array:
	 	$this->Plugins[] = & $Plugin;
		// Memorizes Plugin in code hash array:
		$this->index_Plugins[ $Plugin->code ] = & $Plugin;

		// Request event callback registrations:
		// events = $Plugin->RegisterEvents();

		return $Plugin;
	}


  /**
	 * Count # of registrations of same plugin
	 *
	 * {@internal Plug::count_regs(-)}}
	 *
	 * @param string class name
	 * @return int # of regs
	 */
	function count_regs( $classname )
	{
		$count = 0;

 		foreach( $this->Plugins as $Plugin )
		{
			if( $Plugin->classname == $classname )
			{
				$count++;
			}
		}
		return $count;
	}


	/**
	 * Get next plugin in list:
	 *
	 * {@internal Plug::get_next(-)}}
	 *
	 * @return Plugin (false if no more plugin).
	 */
	function & get_next()
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

		while( $loop_Plugin = & $this->get_next() )
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


/**
 * Callback function to sort plugins by priority
 */
function sort_Plugin( & $a, & $b )
{
	return $a->priority - $b->priority;
}
?>