<?php
/**
 * This file implements the Skin class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
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
	function Skin( $db_row = NULL )
	{
		// Call parent constructor:
		parent::DataObject( 'T_skins__skin', 'skin_', 'skin_ID' );

		$this->delete_cascades = array(
				array( 'table'=>'T_skins__container', 'fk'=>'sco_skin_ID', 'msg'=>T_('%d link containers') ),
			);

		if( is_null($db_row) )
		{	// We are creating an object here:
			$this->type = 'normal';
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
	 *	Display a container
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

		// echo '<div>Debug: container: '.$sco_name.'</div>';

		// Make sure we have the basic params we expect:
		$params = array_merge( array(
					'block_start' => '<div class="$wi_class$">',
					'block_end' => '</div>',
					'block_title_start' => '<h3>',
					'block_title_end' => '</h3>',
				), $params );

   	$WidgetCache = & get_Cache( 'WidgetCache' );
		$Widget_array = & $WidgetCache->get_by_coll_container( $Blog->ID, $sco_name );

		if( !empty($Widget_array) )
		{
			foreach( $Widget_array as $ComponentWidget )
			{
				// Let the Widget dispolay itself (with contextual params):
				$ComponentWidget->display( $params );
			}
		}
	}


	/**
	 * Discover containers included in skin file
	 * @todo
	 */
	function discover_containers()
	{
		global $skins_path, $Messages;

		$rf_main_subpath = $this->folder.'/_main.php';
		$af_main_path = $skins_path.$rf_main_subpath;

		if( ! is_readable($af_main_path) )
		{
			$Messages->add( sprintf( T_('Cannot read skin file &laquo;%s&raquo;!'), $rf_main_subpath ), 'error' );
			return false;
		}

		$file_contents = @file_get_contents( $af_main_path );
		if( ! is_string($file_contents) )
		{
			$Messages->add( sprintf( T_('Cannot read skin file &laquo;%s&raquo;!'), $rf_main_subpath ), 'error' );
			return false;
		}


		// if( ! preg_match_all( '~ \$Skin->container\( .*? (\' (.+?) \' )|(" (.+?) ") ~xmi', $file_contents, $matches ) )
		if( ! preg_match_all( '~ \$Skin->container\( .*? ((\' (.+?) \')|(" (.+?) ")) ~xmi', $file_contents, $matches ) )
		{
			$Messages->add( sprintf( T_('No containers found in skin file &laquo;%s&raquo;!'), $rf_main_subpath ), 'error' );
			return false;
		}

		// Merge matches from the two regexp parts (due to regexp "|" )
		$container_list = array_merge( $matches[3], $matches[5] );

		// Filter out empty elements (due to regexp "|" )
		$this->container_list = array_filter( $container_list, create_function( '$a', 'return !empty($a);' ) );

		// pre_dump( $this->container_list );


		$Messages->add( sprintf( T_('%d containers have been found in the skin file.'), count( $this->container_list ) ), 'success' );
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

		if( parent::dbinsert() !== false )
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
			$select_a_begin = '';
			$select_a_end = '';
		}

		echo '<div class="skinshot">';
		echo '<div class="skinshot_placeholder';
		if( $selected )
		{
			echo ' current';
		}
		echo '">';
		if( file_exists( $skins_path.$skin_folder.'/skinshot.jpg' ) )
		{
			echo $select_a_begin;
			echo '<img src="'.$skins_url.$skin_folder.'/skinshot.jpg" width="240" height="180" alt="'.$skin_folder.'" />';
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
					echo '<a href="?ctrl=skins&amp;action=create&amp;skin_folder='.rawurlencode($skin_folder).'" title="'.T_('Install NOW!').'">';
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