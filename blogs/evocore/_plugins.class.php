<?php
/**
 * This file implements the PluginS class.
 *
 * This is where you can plug-in some plugins :D
 *
 * This file is part of the b2evolution project - {@link http://b2evolution.net/}
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 * {@internal
 * b2evolution is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * b2evolution is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with b2evolution; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * }}
 *
 * {@internal
 * Daniel HAHLER grants François PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: François PLANQUE - {@link http://fplanque.net/}
 * @author blueyed: Daniel HAHLER
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Includes:
 */
require_once dirname(__FILE__).'/_plugin.class.php';

/**
 * Plugins Class
 *
 * This is where you can plug-in some plugins :D
 *
 * @package evocore
 */
class Plugins
{
	/**#@+
	 * @access private
	 */

	/**
	 * Array of loaded plug-ins:
	 */
	var $Plugins = array();

	/**
	 * Indexes:
	 * @todo updates
	 */
	var $index_Plugins = array();
	var $index_name_Plugins = array();

	/**
	 * Path to plug-ins:
	 */
	var $plugins_path;

	/**
	 * Has the plug initialized? (plugins loaded?)
	 * @var boolean
	 */
	var $initialized = false;

	/**
	 * Current object idx in array:
	 * @var integer
	 */
	var $current_idx = 0;

	/**#@-*/


	/**
	 * Constructor
	 *
	 * {@internal Plugins::Plugins(-)}}
	 *
	 */
	function Plugins( )
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
	 * {@internal Plugins::init(-)}}
	 */
	function init()
	{
		global $DB, $Debuglog, $Timer;

		if( ! $this->initialized )
		{
			$Timer->resume( 'plugin_init' );
			$Debuglog->add( 'Loading plugins...' );
			foreach( $DB->get_results( '
					SELECT * FROM T_plugins
					ORDER BY plug_priority', ARRAY_A ) as $row )
			{ // Loop through installed plugins:
				$filename = $this->plugins_path.'_'.str_replace( '_plugin', '.plugin', $row['plug_classname'] ).'.php';
				if( ! is_file( $filename ) )
				{ // Plugin not found!
					$Debuglog->add( 'Plugin not found: '.$filename, 'plugins' );
					// TODO: Automatically remove? Display note on admin/plugins.php?
					continue;
				}
				// Load the plugin:
				$Debuglog->add( 'Loading plugin: '.$row['plug_classname'], 'plugins' );
				require_once $filename;
				// Register the plugin:
				$this->register( $row['plug_classname'], $row['plug_ID'], $row['plug_priority'] );
			}

			$Timer->pause( 'plugin_init' );
			$this->initialized = true;
		}
	}


	/**
	 * Discover and load all available plugins.
	 *
	 * {@internal Plugins::discover(-)}}
	 */
	function discover()
	{
		global $Debuglog;

		if( ! $this->initialized )
		{
			$Debuglog->add( 'Discovering plugins...' );

			// Go through directory:
			$this_dir = dir( $this->plugins_path );
			while( $this_file = $this_dir->read() )
			{
				if( preg_match( '/^_(.+)\.plugin\.php$/', $this_file, $matches ) && is_file( $this->plugins_path. '/'. $this_file ) )
				{ // Valid plugin file name:
					$Debuglog->add( 'Loading plugin: '.$this_file, 'plugins' );
					// Load the plugin:
					require_once $this->plugins_path.$this_file;
					// Register the plugin:
					$classname = $matches[1].'_plugin';
					$this->register( $classname, 0 );
				}
			}

			$this->sort( 'priority' );

			$this->initialized = true;
		}
	}


	/**
	 * Sort the list of {@link Plugins}.
	 *
	 * WARNING: do NOT sort by anything else than priority unless you're handling a list of NOT-YET-INSTALLED plugins
	 *
	 * @param string Order: 'priority' (default), 'name'
	 */
	function sort( $order = 'priority' )
	{
		switch( $order )
		{
			case 'name':
				usort( $this->Plugins, 'sort_Plugin_name' );
				break;

			default:
				// Sort array by priority:
				usort( $this->Plugins, 'sort_Plugin_priority' );
		}
	}


	/**
	 * Install a plugin
	 *
	 * Records it in the database
	 *
	 * {@internal Plugins::install(-)}}
	 */
	function install( $plugin_name )
	{
		global $DB, $Debuglog;

		$this->init();  // Init if not done yet.

		// Load the plugin:
		$filename = $this->plugins_path.'_'.str_replace( '_plugin', '.plugin', $plugin_name ).'.php';
		require_once $filename;

		// Register the plugin:
		$Plugin = & $this->register( $plugin_name, 0 );	// ID will be set a few lines below

		$this->sort( 'priority' );

		// Record into DB
		//$DB->begin();

		//$max_order = $DB->get_var( 'SELECT MAX(plug_order) FROM T_plugin' );

		$DB->query( "INSERT INTO T_plugins( plug_classname, plug_priority )
									VALUES( '$plugin_name', $Plugin->priority ) " );

		//$DB->commit();

		$Plugin->ID = $DB->insert_id;
		$Debuglog->add( 'New plugin: '.$Plugin->name.' ID: '.$Plugin->ID, 'plugins' );
	}


	/**
	 * Uninstall a plugin
	 *
	 * Removes it from the database
	 *
	 * {@internal Plugins::uninstall(-)}}
	 *
	 * @return boolean success
	 */
	function uninstall( $plugin_ID )
	{
		global $DB, $Debuglog;

		$this->init();  // Init if not done yet.

		// Delete from DB
		if( ! $DB->query( "DELETE FROM T_plugins
		                    WHERE plug_ID = $plugin_ID" ) )
		{ // Nothing removed!?
			return false;
		}

		// for( $i = 0; $i < count( $this->Plugins ); $i++ )
		$move_by = 0;
		$items = count($this->Plugins);
		foreach( $this->Plugins as $key => $Plugin )
		{ // Go through plugins:
			if( $Plugin->ID == $plugin_ID )
			{ // This one must be unregistered...
				unset( $this->index_Plugins[ $Plugin->code ] );
				unset( $this->index_name_Plugins[ $Plugin->classname ] );
				$move_by--;
			}
			elseif($move_by)
			{ // This is a normal one but must be moved up
				$this->Plugins[$key+$move_by] = & $this->Plugins[$key];
			}

			if( $key >= $items+$move_by )
			{ // We are reaching the end of the array, we should unset
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
	 * {@internal Plugins::register(-)}}
	 *
	 * @param string name of plugin class to instanciate & register
	 * @param int ID in database (0 if not installed)
	 * @param int Priority in database (-1 to keep default)
	 * @return Plugin ref to newly created plugin
	 * @access private
	 */
	function & register( $classname, $ID = 0, $priority = -1 )
	{
		global $Debuglog;

		if( !class_exists( $classname ) )
		{ // the given class does not exist
			// TODO: Add Message to log category 'plugin_errors' that gets displayed in Settings / Plugins (on discover)
			$Debuglog->add( 'ERROR: Plugin class for ['.$classname.'] not defined - must match the filename.', 'plugins' );
			return false;
		}
		$Plugin = new $classname;	// COPY !

		// Tell him his ID :)
		$Plugin->ID = $ID;
		// Tell him his name :)
		$Plugin->classname = $classname;
		// Tell him his priority:
		if( $priority > -1 ) { $Plugin->priority = $priority; }

		// Memorizes Plugin in sequential array:
		$this->Plugins[] = & $Plugin;
		// Memorizes Plugin in code hash array:
		$this->index_Plugins[ $Plugin->code ] = & $Plugin;
		$this->index_name_Plugins[ $Plugin->classname ] = & $Plugin;

		// Request event callback registrations:
		// events = $Plugin->RegisterEvents();

		return $Plugin;
	}


	/**
	 * Count # of registrations of same plugin
	 *
	 * {@internal Plugins::count_regs(-)}}
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
	 * {@internal Plugins::get_next(-)}}
	 *
	 * @return Plugin (false if no more plugin).
	 */
	function & get_next()
	{
		$this->init();  // Init if not done yet.

		if( $this->current_idx >= count( $this->Plugins ) )
		{
			$r = false;
			return $r;
		}

		return $this->Plugins[ $this->current_idx++ ];
	}


	/**
	 * Rewind iterator
	 *
	 * {@internal Plugins::restart(-) }}
	 */
	function restart()
	{
		$this->current_idx = 0;
	}


	/**
	 * Call all plugins for a given event
	 *
	 * {@internal Plugins::trigger_event(-)}}
	 *
	 * @param string event name, see {@link Plugin}
	 * @param array Associative array of parameters
	 */
	function trigger_event( $event, $params = NULL )
	{
		global $Debuglog, $Timer;

		$this->init();  // Init if not done yet.

		$this->restart(); // Just in case.

		if( is_null($params) )
		{
			$params = array();
		}

		$Debuglog->add( 'Trigger event '.$event, 'plugins' );

		while( $loop_Plugin = & $this->get_next() )
		{ // Go through whole list of plugins
			//echo ' ',$loop_Plugin->code, ':';

			if( method_exists( $loop_Plugin, $event ) )
			{
				$debug_params = $params;
				if( $event == 'LoginAttempt' )
				{ // Hide passworts for LoginAttempt from Debuglog!
					if( isset($debug_params['pass']) )
					{
						$debug_params['pass'] = '-hidden-';
					}
					if( isset($debug_params['pass_md5']) )
					{
						$debug_params['pass_md5'] = '-hidden-';
					}
				}
				$Debuglog->add( 'Calling '.get_class($loop_Plugin).'->'.$event.'( '.var_export( $debug_params, true ).' )', 'plugins' );

				$Timer->resume( 'plugin_'.$loop_Plugin->code );
				$loop_Plugin->$event( $params );
				$Timer->pause( 'plugin_'.$loop_Plugin->code );
			}
		}
	}


	/**
	 * Validate renderer list
	 *
	 * {@internal Plugins::validate_list(-)}}
	 *
	 * @param array renderer codes
	 * @return array validated array
	 */
	function validate_list( $renderers = array('default') )
	{
		$this->init();  // Init if not done yet.

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
	 * {@internal Plugins::render(-)}}
	 *
	 * @param string content to render
	 * @param array renderer codes
	 * @param string Output format, see {@link format_to_output()}
	 * @return string rendered content
	 */
	function render( & $content, & $renderers, $format, $type = 'ItemContent' )
	{
		$this->init();  // Init if not done yet.

		$this->restart(); // Just in case.

		// echo implode(',',$renderers);

		$params = array(
											'type'   => $type,
											'data'   => & $content,
											'format' => $format
										);

		while( $loop_RendererPlugin = $this->get_next() )
		{ // Go through whole list of renders
			//echo ' ',$loop_RendererPlugin->code, ':';

			switch( $loop_RendererPlugin->apply_when )
			{
				case 'stealth':
				case 'always':
					// echo 'FORCED ';
					$loop_RendererPlugin->Render( $params );
					break;

				case 'opt-out':
				case 'opt-in':
				case 'lazy':
					if( in_array( $loop_RendererPlugin->code, $renderers ) )
					{ // Option is activated
						// echo 'OPT ';
						$loop_RendererPlugin->Render( $params );
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
	 * Quick-render a string with a single plugin.
	 *
	 * @param string Plugin code (must have render() method)
	 * @param array data
	 * @param string format to output, see {@link format_to_output()}
	 */
	function quick( $pluginCode, $params )
	{
		$this->init();

		if( !is_array($params) )
		{
			$params = array( 'format' => 'htmlbody', 'data' => $params );
		}
		else
		{
			$params = $params; // copy
		}

		if( isset($this->index_Plugins[ $pluginCode ]) )
		{
			$this->index_Plugins[ $pluginCode ]->render( $params );
			return $params['data'];
		}
		else
		{
			return format_to_output( $params['data'], $format );
		}
	}


	/**
	 * Call a specific plugin by its code.
	 *
	 * This will call the SkinTag event handler.
	 *
	 * {@internal Plugins::call_by_code(-)}}
	 *
	 * @param string plugin code
	 * @param array Associative array of parameters
	 * @return boolean
	 */
	function call_by_code( $code, $params )
	{
		global $Debuglog;

		$this->init();

		if( ! isset($this->index_Plugins[ $code ]) )
		{ // Plugins is not registered
			$Debuglog->add( 'Requested plugin ['.$code.'] is not registered!', 'plugins' );
			return false;
		}

		$this->index_Plugins[ $code ]->SkinTag( $params );

		return true;
	}


	/**
	 * Get a specific plugin by its name.
	 *
	 * {@internal Plugins::get_by_name(-)}}
	 *
	 * @param string plugin name
	 * @return Plugin|false
	 */
	function & get_by_name( $plugin_name )
	{
		global $Debuglog;

		$this->init();
		if( ! isset($this->index_name_Plugins[ $plugin_name ]) )
		{ // Plugin is not registered
			$Debuglog->add( 'Requested plugin ['.$plugin_name.'] not found!', 'plugins' );
			return false;
		}

		return $this->index_name_Plugins[ $plugin_name ];
	}

}


/**
 * Callback function to sort plugins by priority
 */
function sort_Plugin_priority( & $a, & $b )
{
	return $a->priority - $b->priority;
}

/**
 * Callback function to sort plugins by name
 *
 * WARNING: do NOT sort by anything else than priority unless you're handling a list of NOT-YET-INSTALLED plugins
 */
function sort_Plugin_name( & $a, & $b )
{
	return strcasecmp( $a->name, $b->name );
}

/*
 * $Log$
 * Revision 1.14  2005/11/29 14:42:28  blueyed
 * todo
 *
 * Revision 1.13  2005/11/25 15:45:39  blueyed
 * Hide passwords in Debuglog! (for event LoginAttempt)
 *
 * Revision 1.12  2005/11/24 20:43:56  blueyed
 * Timer, doc
 *
 * Revision 1.11  2005/09/18 01:46:55  blueyed
 * Fixed E_NOTICE for return by reference (PHP 4.4.0)
 *
 * Revision 1.10  2005/09/06 17:13:55  fplanque
 * stop processing early if referer spam has been detected
 *
 * Revision 1.9  2005/03/02 18:44:27  fplanque
 * comments
 *
 * Revision 1.8  2005/03/02 17:07:34  blueyed
 * no message
 *
 * Revision 1.7  2005/02/28 09:06:33  blueyed
 * removed constants for DB config (allows to override it from _config_TEST.php), introduced EVO_CONFIG_LOADED
 *
 * Revision 1.6  2005/02/21 00:48:15  blueyed
 * parse error fixed
 *
 * Revision 1.5  2005/02/20 22:41:44  blueyed
 * sort(), use method_exists() for trigger_event, sort_Plugin_name() added, doc, whitespace
 *
 * Revision 1.4  2005/02/08 04:45:02  blueyed
 * improved $DB get_results() handling
 *
 * Revision 1.3  2004/12/17 20:41:14  fplanque
 * cleanup
 *
 * Revision 1.2  2004/10/16 01:31:22  blueyed
 * documentation changes
 *
 * Revision 1.1  2004/10/13 22:46:32  fplanque
 * renamed [b2]evocore/*
 *
 * Revision 1.6  2004/10/12 16:12:18  fplanque
 * Edited code documentation.
 *
 */
?>