<?php
/**
 * This file implements the Skin class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2008 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2005-2006 by PROGIDISTRI - {@link http://progidistri.com/}.
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
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Skin Class
 *
 * @package evocore
 */
class Skin extends DataObject
{
	var $name;
	var $folder;
	var $type;

	/**
	 * Lazy filled.
	 * @var array
	 */
	var $container_list = NULL;


	/**
	 * Constructor
	 *
	 * @param table Database row
	 */
	function Skin( $db_row = NULL, $skin_folder = NULL, $name = NULL )
	{
		// Call parent constructor:
		parent::DataObject( 'T_skins__skin', 'skin_', 'skin_ID' );

		$this->delete_restrictions = array(
				array( 'table'=>'T_blogs', 'fk'=>'blog_skin_ID', 'msg'=>T_('%d blogs using this skin') ),
			);

		$this->delete_cascades = array(
				array( 'table'=>'T_skins__container', 'fk'=>'sco_skin_ID', 'msg'=>T_('%d linked containers') ),
			);

		if( is_null($db_row) )
		{	// We are creating an object here:
			$this->init( $skin_folder, $name );
		}
		else
		{	// Wa are loading an object:
			$this->ID = $db_row->skin_ID;
			$this->name = $db_row->skin_name;
			$this->folder = $db_row->skin_folder;
			$this->type = $db_row->skin_type;
		}
	}


  /**
	 *
	 * @param string
	 * @param string NULL for default  (used by installer; TODO: override with class for Atom ans RSS 2.0)
	 */
	function init( $skin_folder, $name = NULL )
	{
		$this->set( 'folder', $skin_folder );	// Must be set before name for get_default_name() to work
		$this->set( 'name', empty( $name ) ? $this->get_default_name() : $name );
		$this->set( 'type', substr($skin_folder,0,1) == '_' ? 'feed' : 'normal' );
	}


	/**
	 * Install a skin
	 *
	 * @todo do not install if skin doesn't exist. Important for upgrade. Need to NOT fail if ZERO skins installed though :/
	 *
	 * @param string
	 * @param string NULL for default  (used by installer; TODO: override with class for Atom ans RSS 2.0)
	 */
	function install( $skin_folder, $name = NULL )
	{
		$this->init( $skin_folder, $name );

		// Look for containers in skin file:
		$this->discover_containers();

		// INSERT NEW SKIN INTO DB:
		$this->dbinsert();
	}


  /**
	 * Get default name for the skin.
	 * Note: the admin can customize it.
	 */
	function get_default_name()
	{
		return $this->folder;
	}

	/**
	 * Get the customized name for the skin.
	 */
	function get_name()
	{
		return $this->name;
	}

	/**
	 * Load data from Request form fields.
	 *
	 * @return boolean true if loaded data seems valid.
	 */
	function load_from_Request()
	{
		// Name
		param_string_not_empty( 'skin_name', T_('Please enter a name.') );
		$this->set_from_Request( 'name' );

		// Skin type
		param( 'skin_type', 'string' );
		$this->set_from_Request( 'type' );

		return ! param_errors_detected();
	}


	/**
	 * Display a container
	 *
	 * @todo fp> if it doesn't get any skin specific, move it outta here! :P
	 * fp> Do we need Skin objects in the frontoffice at all? -- Do we want to include the dispatcher into the Skin object? WARNING: globals
	 * fp> We might want to customize the container defaults. -- Per blog or per skin?
	 *
	 * @param string
	 * @param array
	 */
	function container( $sco_name, $params = array() )
	{
		/**
		 * Blog currently displayed
		 * @var Blog
		 */
		global $Blog;
		global $admin_url, $rsc_url;

		if( false )
		{	// DEBUG:
			echo '<div class="debug_container">';
			echo '<div class="debug_container_name"><span class="debug_container_action"><a href="'
						.$admin_url.'?ctrl=widgets&amp;blog='.$Blog->ID.'">Edit</a></span>'.$sco_name.'</div>';
		}

    /**
		 * @var WidgetCache
		 */
   	$WidgetCache = & get_Cache( 'WidgetCache' );
		$Widget_array = & $WidgetCache->get_by_coll_container( $Blog->ID, $sco_name );

		if( !empty($Widget_array) )
		{
			foreach( $Widget_array as $ComponentWidget )
			{
				// Let the Widget display itself (with contextual params):
				$ComponentWidget->display( $params );
			}
		}

		if( false )
		{	// DEBUG:
			echo '<img src="'.$rsc_url.'/img/blank.gif" alt="" class="clear">';
			echo '</div>';
		}
	}


	/**
	 * Discover containers included in skin file
	 * @todo browse all *.tpl.php
	 */
	function discover_containers()
	{
		global $skins_path, $Messages;

		$this->container_list = array();

		if( ! $dir = @opendir($skins_path.$this->folder) )
		{
			$Messages->add( 'Cannot open skin directory.', 'error' ); // No trans
			return false;
		}

		while( ( $file = readdir($dir) ) !== false )
		{
			$rf_main_subpath = $this->folder.'/'.$file;
			$af_main_path = $skins_path.$rf_main_subpath;

			if( !is_file( $af_main_path ) || ! preg_match( '¤\.php$¤', $file ) )
			{ // Not a php template file
				continue;
			}

			if( ! is_readable($af_main_path) )
			{
				$Messages->add( sprintf( T_('Cannot read skin file &laquo;%s&raquo;!'), $rf_main_subpath ), 'error' );
				continue;
			}

			$file_contents = @file_get_contents( $af_main_path );
			if( ! is_string($file_contents) )
			{
				$Messages->add( sprintf( T_('Cannot read skin file &laquo;%s&raquo;!'), $rf_main_subpath ), 'error' );
				continue;
			}


			// if( ! preg_match_all( '~ \$Skin->container\( .*? (\' (.+?) \' )|(" (.+?) ") ~xmi', $file_contents, $matches ) )
			if( ! preg_match_all( '~ (\$Skin->|skin_)container\( .*? ((\' (.+?) \')|(" (.+?) ")) ~xmi', $file_contents, $matches ) )
			{	// No containers in this file
				continue;
			}

			// Merge matches from the two regexp parts (due to regexp "|" )
			$container_list = array_merge( $matches[4], $matches[6] );

			$c = 0;
			foreach( $container_list as $container )
			{
				if( empty($container) )
				{	// regexp empty match
					continue;
				}

				$c++;

				if( in_array( $container, $this->container_list ) )
				{	// we already have that one
					continue;
				}

				$this->container_list[] = $container;
			}

			if( $c )
			{
				$Messages->add( sprintf( T_('%d containers have been found in skin template &laquo;%s&raquo;.'), $c, $rf_main_subpath ), 'success' );
			}
		}

		// pre_dump( $this->container_list );

		if( empty($this->container_list) )
		{
			$Messages->add( T_('No containers found in this skin!'), 'error' );
			return false;
		}

		return true;
	}

	/**
	 * @return array
	 */
	function get_containers()
	{
    /**
		 * @var DB
		 */
		global $DB;

		if( is_null( $this->container_list ) )
		{
			$this->container_list = $DB->get_col(
				'SELECT sco_name
					 FROM T_skins__container
					WHERE sco_skin_ID = '.$this->ID, 0, 'get list of containers for skin' );
		}

		return $this->container_list;
	}

	/**
	 * Update the DB based on previously recorded changes
	 *
	 * @return boolean true
	 */
	function dbupdate()
	{
		global $DB;

		$DB->begin();

		if( parent::dbupdate() !== false )
		{	// Skin updated, also save containers:
			$this->db_save_containers();
		}

		$DB->commit();

		return true;
	}


	/**
	 * Insert object into DB based on previously recorded changes.
	 *
	 * @return boolean true
	 */
	function dbinsert()
	{
		global $DB;

		$DB->begin();

		if( parent::dbinsert() )
		{	// Skin saved, also save containers:
			$this->db_save_containers();
		}

		$DB->commit();

		return true;
	}


	/**
	 * Save containers
	 *
	 * to be called by dbinsert / dbupdate
	 */
	function db_save_containers()
	{
		global $DB;

		if( empty( $this->container_list ) )
		{
			return false;
		}

		$values = array();
		foreach( $this->container_list as $container_name )
		{
			$values [] = '( '.$this->ID.', '.$DB->quote($container_name).' )';
		}

		$DB->query( 'REPLACE INTO T_skins__container( sco_skin_ID, sco_name )
									VALUES '.implode( ',', $values ), 'Insert containers' );

		return true;
	}


	/**
	 * Display skinshot for skin folder in various places.
	 *
	 * Including for NON installed skins.
	 *
	 * @static
	 */
	function disp_skinshot( $skin_folder, $function = NULL, $selected = false, $select_url = NULL, $function_url = NULL )
	{
		global $skins_path, $skins_url;

		if( !empty($select_url) )
		{
			$select_a_begin = '<a href="'.$select_url.'" title="'.T_('Select this skin!').'">';
			$select_a_end = '</a>';
		}
		else
		{
			$select_a_begin = '<a href="'.$function_url.'" title="'.T_('Install NOW!').'">';
			$select_a_end = '</a>';
		}

		echo '<div class="skinshot">';
		echo '<div class="skinshot_placeholder';
		if( $selected )
		{
			echo ' current';
		}
		echo '">';
		if( file_exists( $skins_path.$skin_folder.'/skinshot.png' ) )
		{
			echo $select_a_begin;
			echo '<img src="'.$skins_url.$skin_folder.'/skinshot.png" width="240" height="180" alt="'.$skin_folder.'" />';
			echo $select_a_end;
		}
		elseif( file_exists( $skins_path.$skin_folder.'/skinshot.jpg' ) )
		{
			echo $select_a_begin;
			echo '<img src="'.$skins_url.$skin_folder.'/skinshot.jpg" width="240" height="180" alt="'.$skin_folder.'" />';
			echo $select_a_end;
		}
		elseif( file_exists( $skins_path.$skin_folder.'/skinshot.gif' ) )
		{
			echo $select_a_begin;
			echo '<img src="'.$skins_url.$skin_folder.'/skinshot.gif" width="240" height="180" alt="'.$skin_folder.'" />';
			echo $select_a_end;
		}
		else
		{
			echo '<div class="skinshot_noshot">'.T_('No skinshot available for').'</div>';
			echo '<div class="skinshot_name">'.$select_a_begin.$skin_folder.$select_a_end.'</div>';
		}
		echo '</div>';
		echo '<div class="legend">';
		if( !empty( $function) )
		{
			echo '<div class="actions">';
			switch( $function )
			{
				case 'install':
					echo '<a href="'.$function_url.'" title="'.T_('Install NOW!').'">';
					echo T_('Install NOW!').'</a>';
					break;

				case 'select':
					echo '<a href="'.$function_url.'" target="_blank" title="'.T_('Preview blog with this skin in a new window').'">';
					echo T_('Preview').'</a>';
					break;
			}
			echo '</div>';
		}
		echo '<strong>'.$skin_folder.'</strong>';
		echo '</div>';
		echo '</div>';
	}


}


/*
 * $Log$
 * Revision 1.10  2008/04/24 02:01:04  fplanque
 * experimental
 *
 * Revision 1.9  2008/03/21 17:41:56  fplanque
 * custom 404 pages
 *
 * Revision 1.8  2008/01/21 09:35:35  fplanque
 * (c) 2008
 *
 * Revision 1.7  2007/12/22 21:02:50  fplanque
 * minor
 *
 * Revision 1.6  2007/10/08 08:32:56  fplanque
 * widget fixes
 *
 * Revision 1.5  2007/09/29 03:42:12  fplanque
 * skin install UI improvements
 *
 * Revision 1.4  2007/09/12 01:18:32  fplanque
 * translation updates
 *
 * Revision 1.3  2007/07/09 19:49:29  fplanque
 * Look for containers in all skin templates.
 *
 * Revision 1.2  2007/06/27 02:23:24  fplanque
 * new default template for skins named index.main.php
 *
 * Revision 1.1  2007/06/25 11:01:32  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.21  2007/06/24 18:28:55  fplanque
 * refactored skin install
 *
 * Revision 1.20  2007/06/20 21:42:13  fplanque
 * implemented working widget/plugin params
 *
 * Revision 1.19  2007/05/28 01:36:24  fplanque
 * enhanced blog list widget
 *
 * Revision 1.18  2007/05/08 00:42:07  fplanque
 * public blog list as a widget
 *
 * Revision 1.17  2007/05/07 23:26:19  fplanque
 * public blog list as a widget
 *
 * Revision 1.16  2007/05/07 18:59:45  fplanque
 * renamed skin .page.php files to .tpl.php
 *
 * Revision 1.15  2007/04/26 00:11:12  fplanque
 * (c) 2007
 *
 * Revision 1.14  2007/03/19 21:21:00  blueyed
 * fixed typo
 *
 * Revision 1.13  2007/03/18 01:39:54  fplanque
 * renamed _main.php to main.page.php to comply with 2.0 naming scheme.
 * (more to come)
 *
 * Revision 1.12  2007/02/05 00:35:43  fplanque
 * small adjustments
 *
 * Revision 1.11  2007/01/23 21:45:25  fplanque
 * "enforce" foreign keys
 *
 * Revision 1.10  2007/01/14 00:45:13  fplanque
 * bugfix
 *
 * Revision 1.9  2007/01/13 18:37:29  fplanque
 * doc
 *
 * Revision 1.8  2007/01/12 02:40:26  fplanque
 * widget default params proof of concept
 * (param customization to be done)
 *
 * Revision 1.7  2007/01/12 00:39:11  fplanque
 * bugfix
 *
 * Revision 1.6  2007/01/11 20:44:19  fplanque
 * skin containers proof of concept
 * (no params handling yet though)
 *
 * Revision 1.5  2007/01/08 02:11:56  fplanque
 * Blogs now make use of installed skins
 * next step: make use of widgets inside of skins
 *
 * Revision 1.4  2007/01/07 23:38:20  fplanque
 * discovery of skin containers
 *
 * Revision 1.3  2007/01/07 19:40:18  fplanque
 * discover skin containers
 *
 * Revision 1.2  2007/01/07 05:32:11  fplanque
 * added some more DB skin handling (install+uninstall+edit properties ok)
 * still useless though :P
 * next step: discover containers in installed skins
 *
 * Revision 1.1  2006/12/29 01:10:06  fplanque
 * basic skin registering
 *
 */
?>