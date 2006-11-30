<?php
/**
 * This file implements the {@link Plugins_admin} class, which gets used for administrative
 * handling of the {@link Plugin Plugins}.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2006 by Daniel HAHLER - {@link http://daniel.hahler.de/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * @author blueyed: Daniel HAHLER
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * A Plugins object that loads all Plugins, not just the enabled ones. This is needed for the backoffice plugin management.
 *
 * @package evocore
 */
class Plugins_admin extends Plugins
{
	/**
	 * Load all plugins (not just enabled ones).
	 */
	var $sql_load_plugins_table = '
			SELECT plug_ID, plug_priority, plug_classname, plug_code, plug_name, plug_shortdesc, plug_apply_rendering, plug_status, plug_version, plug_spam_weight
			  FROM T_plugins
			 ORDER BY plug_priority, plug_classname';

	/**
	 * @var boolean Gets used in base class
	 * @static
	 */
	var $is_admin_class = true;


	/**
	 * Discover and register all available plugins below {@link $plugins_path}.
	 */
	function discover()
	{
		global $Debuglog, $Timer;

		$Timer->resume('plugins_discover');

		$Debuglog->add( 'Discovering plugins...', 'plugins' );

		$Timer->resume('plugins_discover::get_filenames');
		$plugin_files = get_filenames( $this->plugins_path, true, false );
		$Timer->pause('plugins_discover::get_filenames');

		foreach( $plugin_files as $path )
		{
			if( ! preg_match( '~/_([^/]+)\.plugin\.php$~', $path, $match ) && is_file( $path ) )
			{
				continue;
			}
			$classname = $match[1].'_plugin';

			if( substr( dirname($path), 0, 1 ) == '_' )
			{ // Skip plugins which are in a directory that starts with an underscore ("_")
				continue;
			}

			if( $this->get_by_classname($classname) )
			{
				$Debuglog->add( 'Skipping duplicate plugin (classname '.$classname.')!', array('error', 'plugins') );
				continue;
			}

			// TODO: check for parse errors before, e.g. through /htsrc/async.php..?!

			$this->register( $classname, 0, -1, NULL, $path ); // auto-generate negative ID; will return string on error.
		}

		$Timer->pause('plugins_discover');
	}


}


/* {{{ Revision log:
 * $Log$
 * Revision 1.1  2006/11/30 05:43:40  blueyed
 * Moved Plugins::discover() to Plugins_admin::discover(); Renamed Plugins_no_DB to Plugins_admin_no_DB (and deriving from Plugins_admin)
 *
 * }}}
 */
?>