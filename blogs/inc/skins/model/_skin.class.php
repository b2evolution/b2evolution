<?php
/**
 * This file implements the Skin class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
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
 * @version $Id: _skin.class.php 7178 2014-07-23 08:11:33Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


load_class( '_core/model/dataobjects/_dataobject.class.php', 'DataObject' );


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
	 * The translations keyed by locale. They get loaded through include() of _global.php.
	 * @see Skin::T_()
	 * @var array
	 */
	var $_trans = array();


	/**
	 * Constructor
	 *
	 * @param table Database row
	 */
	function Skin( $db_row = NULL, $skin_folder = NULL )
	{
		// Call parent constructor:
		parent::DataObject( 'T_skins__skin', 'skin_', 'skin_ID' );

		$this->delete_restrictions = array(
				array( 'table'=>'T_coll_settings', 'fk'=>'cset_value', 'msg'=>T_('%d blogs using this skin'), 
						'and_condition' => '( cset_name = "normal_skin_ID" OR cset_name = "mobile_skin_ID" OR cset_name = "tablet_skin_ID" )' ),
				array( 'table'=>'T_settings', 'fk'=>'set_value', 'msg'=>T_('This skin is set as default skin.'), 
						'and_condition' => '( set_name = "def_normal_skin_ID" OR set_name = "def_mobile_skin_ID" OR set_name = "def_tablet_skin_ID" )' ),
			);

		$this->delete_cascades = array(
				array( 'table'=>'T_skins__container', 'fk'=>'sco_skin_ID', 'msg'=>T_('%d linked containers') ),
			);

		if( is_null($db_row) )
		{	// We are creating an object here:
			$this->set( 'folder', $skin_folder );
			$this->set( 'name', $this->get_default_name() );
			$this->set( 'type', $this->get_default_type() );
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
	 * Install current skin to DB
	 */
	function install()
	{
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
	 * Get default type for the skin.
	 */
	function get_default_type()
	{
		return (substr($this->folder,0,1) == '_' ? 'feed' : 'normal');
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
	 * Load params
	 */
	function load_params_from_Request()
	{
		load_funcs('plugins/_plugin.funcs.php');

		// Loop through all widget params:
		foreach( $this->get_param_definitions( array('for_editing'=>true) ) as $parname => $parmeta )
		{
			if( isset( $parmeta['type'] ) && $parmeta['type'] == 'color' && empty( $parmeta['valid_pattern'] ) )
			{ // Set default validation for color fields
				$parmeta['valid_pattern'] = array(
						'pattern' => '~^(#([a-f0-9]{3}){1,2})?$~i',
						'error'   => T_('Invalid color code.')
					);
			}
			autoform_set_param_from_request( $parname, $parmeta, $this, 'Skin' );
		}
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
		global $Timer, $Session;

		$timer_name = 'skin_container('.$sco_name.')';
		$Timer->start($timer_name);

		$show_debug_containers = $Session->get( 'debug_containers_'.$Blog->ID ) == 1;

		if( $show_debug_containers )
		{ // DEBUG:
			echo '<div class="debug_container">';
			echo '<div class="debug_container_name"><span class="debug_container_action"><a href="'
						.$admin_url.'?ctrl=widgets&amp;blog='.$Blog->ID.'">Edit</a></span>'.$sco_name.'</div>';
		}

		/**
		 * @var EnabledWidgetCache
		 */
		$EnabledWidgetCache = & get_EnabledWidgetCache();
		$Widget_array = & $EnabledWidgetCache->get_by_coll_container( $Blog->ID, $sco_name );

		if( !empty($Widget_array) )
		{
			foreach( $Widget_array as $ComponentWidget )
			{	// Let the Widget display itself (with contextual params):
				$widget_timer_name = 'Widget->display('.$ComponentWidget->code.')';
				$Timer->start($widget_timer_name);
				$ComponentWidget->display_with_cache( $params, array(
						// 'sco_name' => $sco_name, // fp> not sure we need that for now
					) );
				$Timer->pause($widget_timer_name);
			}
		}

		if( $show_debug_containers )
		{ // DEBUG:
			echo get_icon( 'pixel', 'imgtag', array( 'class' => 'clear' ) );
			echo '</div>';
		}

		$Timer->pause($timer_name);
	}


	/**
	 * Discover containers included in skin files only in the given folder
	 *
	 * @param string Folder name
	 * @param array Exclude the files
	 * @return array Files that were prepared
	 */
	function discover_containers_by_folder( $folder, $exclude_files = array() )
	{
		global $skins_path, $Messages;

		if( ! $dir = @opendir( $skins_path.$folder ) )
		{ // Skin directory not found!
			$Messages->add( T_('Cannot open skin directory.'), 'error' ); // No trans
			return false;
		}

		// Store the file names to return
		$files = array();

		// Go through all files in the skin directory:
		while( ( $file = readdir( $dir ) ) !== false )
		{
			if( in_array( $file, $exclude_files ) )
			{ // Skip this file
				continue;
			}

			$rf_main_subpath = trim( $folder.'/'.$file, '/' );
			$af_main_path = $skins_path.$rf_main_subpath;

			if( !is_file( $af_main_path ) || ! preg_match( '~\.php$~', $file ) )
			{ // Not a php template file, go to next:
				continue;
			}

			if( ! is_readable( $af_main_path ) )
			{ // Cannot open PHP file:
				$Messages->add( sprintf( T_('Cannot read skin file &laquo;%s&raquo;!'), $rf_main_subpath ), 'error' );
				continue;
			}

			$file_contents = @file_get_contents( $af_main_path );
			if( ! is_string( $file_contents ) )
			{ // Cannot get contents:
				$Messages->add( sprintf( T_('Cannot read skin file &laquo;%s&raquo;!'), $rf_main_subpath ), 'error' );
				continue;
			}

			$files[] = $files;

			// DETECT if the file contains containers:
			// if( ! preg_match_all( '~ \$Skin->container\( .*? (\' (.+?) \' )|(" (.+?) ") ~xmi', $file_contents, $matches ) )
			if( ! preg_match_all( '~ (\$Skin->|skin_)container\( .*? ((\' (.+?) \')|(" (.+?) ")) ~xmi', $file_contents, $matches ) )
			{ // No containers in this file, go to next:
				continue;
			}

			// Merge matches from the two regexp parts (due to regexp "|" )
			$container_list = array_merge( $matches[4], $matches[6] );

			$c = 0;
			foreach( $container_list as $container )
			{
				if( empty( $container ) )
				{ // regexp empty match -- NOT a container:
					continue;
				}

				// We have one more container:
				$c++;

				if( in_array( $container, $this->container_list ) )
				{ // we already have that one
					continue;
				}

				$this->container_list[] = $container;
			}

			if( $c )
			{
				$Messages->add( sprintf( T_('%d containers have been found in skin template &laquo;%s&raquo;.'), $c, $rf_main_subpath ), 'success' );
			}
		}

		return $files;
	}


	/**
	 * Discover containers included in skin files
	 */
	function discover_containers()
	{
		global $Messages;

		$this->container_list = array();

		// Find the containers in the current skin folder
		$skin_files = $this->discover_containers_by_folder( $this->folder );

		// Find the containers in the root skins folder with excluding the files that are contained in the skin folder
		$this->discover_containers_by_folder( '', $skin_files );

		if( empty( $this->container_list ) )
		{
			$Messages->add( T_('No containers found in this skin!'), 'error' );
			return false;
		}

		return true;
	}


	/**
	 * Get the list of containers that have been previously discovered for this skin.
	 *
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

		// Get a list of all currently empty containers:
		$sql = 'SELECT sco_name
						  FROM T_skins__container LEFT JOIN T_widget ON ( sco_name = wi_sco_name )
						 WHERE sco_skin_ID = '.$this->ID.'
						 GROUP BY sco_name
						HAVING COUNT(wi_ID) = 0';
		$empty_containers_list = $DB->get_col( $sql, 0, 'Get empty containers' );
		//pre_dump( $empty_containers_list );

		// Look for containers in skin file:
		$this->discover_containers();

		// Delete empty containers:
		foreach( $empty_containers_list as $empty_container )
		{
			if( !in_array( $empty_container, $this->container_list ) )
			{	// This container has been removed from the skin + it's empty, so delete it from DB:
				$DB->query( 'DELETE FROM T_skins__container
									WHERE sco_name = '.$DB->quote($empty_container) );
			}
		}

		// Make sure new containers are added:
		if( ! empty( $this->container_list ) )
		{
			$values = array();
			foreach( $this->container_list as $container_name )
			{
				$values [] = '( '.$this->ID.', '.$DB->quote($container_name).' )';
			}

			$DB->query( 'REPLACE INTO T_skins__container( sco_skin_ID, sco_name )
										VALUES '.implode( ',', $values ), 'Insert containers' );
		}
	}


	/**
	 * Display skinshot for skin folder in various places.
	 *
	 * Including for NON installed skins.
	 *
	 * @static
	 */
	function disp_skinshot( $skin_folder, $skin_name, $disp_params = array() )
	{
		global $skins_path, $skins_url;

		$disp_params = array_merge( array(
				'selected'        => false,
				'skinshot_class'  => 'skinshot',
				'skin_compatible' => true,
			), $disp_params );

		if( isset( $disp_params[ 'select_url' ] ) )
		{
			$select_a_begin = '<a href="'.$disp_params[ 'select_url' ].'" title="'.T_('Select this skin!').'">';
			$select_a_end = '</a>';
		}
		elseif( isset( $disp_params[ 'function_url' ] ) )
		{
			$select_a_begin = '<a href="'.$disp_params[ 'function_url' ].'" title="'.T_('Install NOW!').'">';
			$select_a_end = '</a>';
		}
		else
		{
			$select_a_begin = '';
			$select_a_end = '';
		}

		echo '<div class="'.$disp_params[ 'skinshot_class' ].'">';
		echo '<div class="skinshot_placeholder';
		if( $disp_params[ 'selected' ] )
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
		if( isset( $disp_params[ 'function' ] ) && isset( $disp_params[ 'function_url' ] ) )
		{
			echo '<div class="actions">';
			switch( $disp_params[ 'function' ] )
			{
				case 'install':
					// Display a link to install the skin
					if( $disp_params[ 'skin_compatible' ] )
					{ // If skin is compatible for current selected type
						echo '<a href="'.$disp_params[ 'function_url' ].'" title="'.T_('Install NOW!').'">';
						echo T_('Install NOW!').'</a>';
					}
					else
					{ // Inform about skin type is wrong for current case
						echo '<a href="'.$disp_params[ 'function_url' ].'" title="'.T_('Install NOW!').'" class="red">';
						echo T_('Wrong Type!').'</a> ';
						echo get_icon( 'help', 'imgtag', array( 'title' => T_('This skin does not fit the blog type you are trying to create.') ) );
					}
					break;

				case 'select':
					// Display a link to preview the skin
					echo '<a href="'.$disp_params[ 'function_url' ].'" target="_blank" title="'.T_('Preview blog with this skin in a new window').'">';
					echo T_('Preview').'</a>';
					break;
			}
			echo '</div>';
		}
		echo '<strong>'.$skin_name.'</strong>';
		echo '</div>';
		echo '</div>';
	}


	/**
	 * Get definitions for editable params
	 *
	 * @todo this is destined to be overridden by derived Skin classes
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_param_definitions( $params )
	{
		$r = array();

		return $r;
	}


	/**
 	 * Get a skin specific param value from current Blog
 	 *
	 */
	function get_setting( $parname )
	{
		/**
		 * @var Blog
		 */
		global $Blog;

		// Name of the setting in the blog settings:
		$blog_setting_name = 'skin'.$this->ID.'_'.$parname;

		$value = $Blog->get_setting( $blog_setting_name );

		if( ! is_null( $value ) )
		{	// We have a value for this param:
			return $value;
		}

		// Try default values:
		$params = $this->get_param_definitions( NULL );
		if( isset( $params[$parname]['defaultvalue'] ) )
		{	// We ahve a default value:
			return $params[$parname]['defaultvalue'] ;
		}

		return NULL;
	}


	/**
	 * Get current skin post navigation setting.
	 * Possible values:
	 *    - NULL - In this case the Blog post navigation setting will be used
	 *    - 'same_category' - to always navigate through the same category in this skin
	 *    - 'same_author' - to always navigate through the same authors in this skin
	 * 
	 * Set this to not NULL only if the same post navigation should be used in every Blog where this skin is used
	 */
	function get_post_navigation()
	{
		return NULL;
	}


	/**
	 * Get current skin path
	 * @return string
	 */
	function get_path()
	{
		global $skins_path;

		return trailing_slash($skins_path.$this->folder);
	}


	/**
	 * Get current skin URL
	 * @return string
	 */
	function get_url()
	{
		global $skins_url;

		return trailing_slash($skins_url.$this->folder);
	}


	/**
	 * Set a skin specific param value for current Blog
	 *
	 * @param string parameter name
	 * @param mixed parameter value
	 */
	function set_setting( $parname, $parvalue )
	{
		/**
		 * @var Blog
		 */
		global $Blog;

		// Name of the setting in the blog settings:
		$blog_setting_name = 'skin'.$this->ID.'_'.$parname;

		$Blog->set_setting( $blog_setting_name, $parvalue );
	}


	/**
	 * Save skin specific settings for current blgo to DB
	 */
	function dbupdate_settings()
	{
		/**
		 * @var Blog
		 */
		global $Blog;

		$Blog->dbupdate();
	}


	/**
	 * Get ready for displaying the skin.
	 *
	 * This may register some CSS or JS...
	 */
	function display_init()
	{
		// Add CSS:
		// require_css( 'basic_styles.css', 'blog' ); // the REAL basic styles
		// require_css( 'basic.css', 'blog' ); // Basic styles
		// require_css( 'blog_base.css', 'blog' ); // Default styles for the blog navigation
		// require_css( 'item_base.css', 'blog' ); // Default styles for the post CONTENT
		// require_css( 'b2evo_base.bundle.css', 'blog' ); // Concatenation of the above
		require_css( 'b2evo_base.bmin.css', 'blog' ); // Concatenation + Minifaction of the above

		// Make sure standard CSS is called ahead of custom CSS generated below:
		require_css( 'style.css', true );

		// Colorbox (a lightweight Lightbox alternative) allows to zoom on images and do slideshows with groups of images:
		if( $this->get_setting( 'colorbox' ) )
		{	// This can be enabled by a setting in skins where it may be relevant
			require_js_helper( 'colorbox', 'blog' );
		}

		// override this in specific skins...
	}


	/**
	 * Translate a given string, in the Skin's context.
	 *
	 * This means, that the translation is obtained from the Skin's
	 * "locales" folder.
	 *
	 * It uses the global/regular {@link T_()} function as a fallback.
	 *
	 * @param string The string (english), that should be translated
	 * @param string Requested locale ({@link $current_locale} gets used by default)
	 * @return string The translated string.
	 *
	 * @uses T_()
	 * @since 3.2.0 (after beta)
	 */
	function T_( $string, $req_locale = '' )
	{
		global $skins_path;

		if( ( $return = T_( $string, $req_locale, array(
								'ext_transarray' => & $this->_trans,
								'alt_basedir'    => $skins_path.$this->folder,
							) ) ) == $string )
		{	// This skin did not provide a translation - fallback to global T_():
			return T_( $string, $req_locale );
		}

		return $return;
	}


	/**
	 * Translate and escape single quotes.
	 *
	 * This is to be used mainly for Javascript strings.
	 *
	 * @param string String to translate
	 * @param string Locale to use
	 * @return string The translated and escaped string.
	 *
	 * @uses Skin::T_()
	 * @since 3.2.0 (after beta)
	 */
	function TS_( $string, $req_locale = '' )
	{
		return str_replace( "'", "\\'", $this->T_( $string, $req_locale ) );
	}


	/**
	 * Those templates are used for example by the messaging screens.
	 */
	function get_template( $name )
	{
		switch( $name )
		{
			case 'Results':
				// Results list:
				return array(
					'page_url' => '', // All generated links will refer to the current page
					'before' => '<div class="results">',
					'content_start' => '<div id="$prefix$ajax_content">',
					'header_start' => '<div class="results_nav">',
						'header_text' => '<strong>'.T_('Pages').'</strong>: $prev$ $first$ $list_prev$ $list$ $list_next$ $last$ $next$',
						'header_text_single' => '',
					'header_end' => '</div>',
					'head_title' => '<div class="title"><span style="float:right">$global_icons$</span>$title$</div>'
							            ."\n",
					'filters_start' => '<div class="filters">',
					'filters_end' => '</div>',
					'list_start' => '<div class="table_scroll">'."\n"
					               .'<table class="grouped" cellspacing="0">'."\n",
						'head_start' => '<thead>'."\n",
							'line_start_head' => '<tr>',  // TODO: fusionner avec colhead_start_first; mettre a jour admin_UI_general; utiliser colspan="$headspan$"
							'colhead_start' => '<th $class_attrib$ $title_attrib$>',
							'colhead_start_first' => '<th class="firstcol $class$" $title_attrib$>',
							'colhead_start_last' => '<th class="lastcol $class$" $title_attrib$>',
							'colhead_end' => "</th>\n",
							'sort_asc_off' => get_icon( 'sort_asc_off' ),
							'sort_asc_on' => get_icon( 'sort_asc_on' ),
							'sort_desc_off' => get_icon( 'sort_desc_off' ),
							'sort_desc_on' => get_icon( 'sort_desc_on' ),
							'basic_sort_off' => '',
							'basic_sort_asc' => get_icon( 'ascending' ),
							'basic_sort_desc' => get_icon( 'descending' ),
						'head_end' => "</thead>\n\n",
						'tfoot_start' => "<tfoot>\n",
						'tfoot_end' => "</tfoot>\n\n",
						'body_start' => "<tbody>\n",
							'line_start' => '<tr class="even">'."\n",
							'line_start_odd' => '<tr class="odd">'."\n",
							'line_start_last' => '<tr class="even lastline">'."\n",
							'line_start_odd_last' => '<tr class="odd lastline">'."\n",
								'col_start' => '<td $class_attrib$>',
								'col_start_first' => '<td class="firstcol $class$">',
								'col_start_last' => '<td class="lastcol $class$">',
								'col_end' => "</td>\n",
							'line_end' => "</tr>\n\n",
							'grp_line_start' => '<tr class="group">'."\n",
							'grp_line_start_odd' => '<tr class="odd">'."\n",
							'grp_line_start_last' => '<tr class="lastline">'."\n",
							'grp_line_start_odd_last' => '<tr class="odd lastline">'."\n",
										'grp_col_start' => '<td $class_attrib$ $colspan_attrib$>',
										'grp_col_start_first' => '<td class="firstcol $class$" $colspan_attrib$>',
										'grp_col_start_last' => '<td class="lastcol $class$" $colspan_attrib$>',
								'grp_col_end' => "</td>\n",
							'grp_line_end' => "</tr>\n\n",
						'body_end' => "</tbody>\n\n",
						'total_line_start' => '<tr class="total">'."\n",
							'total_col_start' => '<td $class_attrib$>',
							'total_col_start_first' => '<td class="firstcol $class$">',
							'total_col_start_last' => '<td class="lastcol $class$">',
							'total_col_end' => "</td>\n",
						'total_line_end' => "</tr>\n\n",
					'list_end' => "</table></div>\n\n",
					'footer_start' => '<div class="results_nav nav_footer">',
					'footer_text' => '<strong>'.T_('Pages').'</strong>: $prev$ $first$ $list_prev$ $list$ $list_next$ $last$ $next$'
					                  /* T_('Page $scroll_list$ out of $total_pages$   $prev$ | $next$<br />'. */
					                  /* '<strong>$total_pages$ Pages</strong> : $prev$ $list$ $next$' */
					                  /* .' <br />$first$  $list_prev$  $list$  $list_next$  $last$ :: $prev$ | $next$') */,
					'footer_text_single' => '',
					'footer_text_no_limit' => '', // Text if theres no LIMIT and therefor only one page anyway
						'prev_text' => T_('Previous'),
						'next_text' => T_('Next'),
						'no_prev_text' => '',
						'no_next_text' => '',
						'list_prev_text' => T_('...'),
						'list_next_text' => T_('...'),
						'list_span' => 11,
						'scroll_list_range' => 5,
					'footer_end' => "</div>\n\n",
					'no_results_start' => '<table class="grouped" cellspacing="0">'."\n",
					'no_results_end'   => '<tr class="lastline"><td class="firstcol lastcol">$no_results$</td></tr>'
					                      .'</table>'."\n\n",
				'content_end' => '</div>',
				'after' => '</div>',
				'sort_type' => 'basic'
				);

			case 'messages':
				return array(
					'show_only_date' => true,
					'show_columns' => 'login',
				);

			case 'blockspan_form':
				// blockspan Form settings:
				return array(
					'layout' => 'blockspan',		// Temporary dirty hack
					'formstart' => '',
					'title_fmt' => '$title$'."\n", // TODO: icons
					'no_title_fmt' => '',          //           "
					'fieldset_begin' => '<fieldset $fieldset_attribs$>'."\n"
															.'<legend $title_attribs$>$fieldset_title$</legend>'."\n",
					'fieldset_end' => '</fieldset>'."\n",
					'fieldstart' => '<span class="block" $ID$>',
					'labelclass' => '',
					'labelstart' => '',
					'labelend' => "\n",
					'labelempty' => '',
					'inputstart' => '',
					'infostart' => '',
					'inputend' => "\n",
					'fieldend' => '</span>'.get_icon( 'pixel' )."\n",
					'buttonsstart' => '',
					'buttonsend' => "\n",
					'customstart' => '',
					'customend' => "\n",
					'note_format' => ' <span class="notes">%s</span>',
					'formend' => '',
				);
		}

		return array();
	}
}

?>