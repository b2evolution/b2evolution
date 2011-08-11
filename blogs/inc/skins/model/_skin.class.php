<?php
/**
 * This file implements the Skin class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2010 by Francois PLANQUE - {@link http://fplanque.net/}
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
				array( 'table'=>'T_blogs', 'fk'=>'blog_skin_ID', 'msg'=>T_('%d blogs using this skin') ),
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
		global $Timer;

		$timer_name = 'skin_container('.$sco_name.')';
		$Timer->start($timer_name);

		if( false )
		{	// DEBUG:
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

		if( false )
		{	// DEBUG:
			echo '<img src="'.$rsc_url.'/img/blank.gif" alt="" class="clear" />';
			echo '</div>';
		}

		$Timer->pause($timer_name);
	}


	/**
	 * Discover containers included in skin file
	 */
	function discover_containers()
	{
		global $skins_path, $Messages;

		$this->container_list = array();

		if( ! $dir = @opendir($skins_path.$this->folder) )
		{	// Skin directory not found!
			$Messages->add( 'Cannot open skin directory.', 'error' ); // No trans
			return false;
		}

		// Go through all files in the skin directory:
		while( ( $file = readdir($dir) ) !== false )
		{
			$rf_main_subpath = $this->folder.'/'.$file;
			$af_main_path = $skins_path.$rf_main_subpath;

			if( !is_file( $af_main_path ) || ! preg_match( '¤\.php$¤', $file ) )
			{ // Not a php template file, go to next:
				continue;
			}

			if( ! is_readable($af_main_path) )
			{	// Cannot open PHP file:
				$Messages->add( sprintf( T_('Cannot read skin file &laquo;%s&raquo;!'), $rf_main_subpath ), 'error' );
				continue;
			}

			$file_contents = @file_get_contents( $af_main_path );
			if( ! is_string($file_contents) )
			{	// Cannot get contents:
				$Messages->add( sprintf( T_('Cannot read skin file &laquo;%s&raquo;!'), $rf_main_subpath ), 'error' );
				continue;
			}

			// DETECT if the file contains containers:
			// if( ! preg_match_all( '~ \$Skin->container\( .*? (\' (.+?) \' )|(" (.+?) ") ~xmi', $file_contents, $matches ) )
			if( ! preg_match_all( '~ (\$Skin->|skin_)container\( .*? ((\' (.+?) \')|(" (.+?) ")) ~xmi', $file_contents, $matches ) )
			{	// No containers in this file, go to next:
				continue;
			}

			// Merge matches from the two regexp parts (due to regexp "|" )
			$container_list = array_merge( $matches[4], $matches[6] );

			$c = 0;
			foreach( $container_list as $container )
			{
				if( empty($container) )
				{	// regexp empty match -- NOT a container:
					continue;
				}

				// We have one more container:
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

		// Get a list of all currently empty containers:
		$sql = 'SELECT sco_name
						FROM T_skins__container LEFT JOIN T_widget ON ( sco_name = wi_sco_name )
						WHERE sco_skin_ID = '.$this->ID.'
						GROUP BY sco_name
						HAVING COUNT(wi_ID) = 0';
		$empty_containers_list = $DB->get_col( $sql, 0, 'Get empty containers' );
		//pre_dump( $empty_containers_list );

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
	function disp_skinshot( $skin_folder, $skin_name, $function = NULL, $selected = false, $select_url = NULL, $function_url = NULL )
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
		// Make sure standard CSS is called ahead of custom CSS generated below:
		// require_css( 'style.css', true );

		// override in specific skins...
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


	function get_template( $name )
	{
		switch( $name )
		{
			case 'Results':
				// Results list:
				return array(
					'page_url' => '', // All generated links will refer to the current page
					'before' => '<div class="results">',
					'header_start' => '<div class="results_nav">',
						'header_text' => '<strong>'.T_('Pages').'</strong>: $prev$ $first$ $list_prev$ $list$ $list_next$ $last$ $next$',
						'header_text_single' => '',
					'header_end' => '</div>',
					'list_start' => '<table class="grouped" cellspacing="0">'."\n\n",
						'head_start' => "<thead>\n",
							'head_title' => '<tr><th colspan="$nb_cols$" class="title"><span style="float:right">$global_icons$</span>$title$</th>'
							                ."\n</tr>\n",
							'filters_start' => '<tr class="filters"><td colspan="$nb_cols$">',
							'filters_end' => '</td></tr>',
							'line_start_head' => '<tr>',  // TODO: fusionner avec colhead_start_first; mettre a jour admin_UI_general; utiliser colspan="$headspan$"
							'colhead_start' => '<th $class_attrib$>',
							'colhead_start_first' => '<th class="firstcol $class$">',
							'colhead_start_last' => '<th class="lastcol $class$">',
							'colhead_end' => "</th>\n",
							'sort_asc_off' => '<img src="../admin/img/grey_arrow_up.gif" alt="A" title="'.T_('Ascending order')
							                    .'" height="12" width="11" />',
							'sort_asc_on' => '<img src="../admin/img/black_arrow_up.gif" alt="A" title="'.T_('Ascending order')
							                    .'" height="12" width="11" />',
							'sort_desc_off' => '<img src="../admin/img/grey_arrow_down.gif" alt="D" title="'.T_('Descending order')
							                    .'" height="12" width="11" />',
							'sort_desc_on' => '<img src="../admin/img/black_arrow_down.gif" alt="D" title="'.T_('Descending order')
							                    .'" height="12" width="11" />',
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
					'list_end' => "</table>\n\n",
					'footer_start' => '<div class="results_nav">',
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
					'no_results_start' => '<table class="grouped" cellspacing="0">'."\n\n"
								                .'<tr><th class="title"><span style="float:right">$global_icons$</span>'
								                .'$title$</th></tr>'."\n",
					'no_results_end'   => '<tr class="lastline"><td class="firstcol lastcol">$no_results$</td></tr>'
								                .'</table>'."\n\n",
				'after' => '</div>',
				'sort_type' => 'basic'
				);

			case 'messages':
				return array(
					'show_only_date' => true,
					'show_columns' => 'login',
				);
		}

		return array();
	}
}


/*
 * $Log$
 * Revision 1.29  2011/08/11 09:05:09  efy-asimo
 * Messaging in front office
 *
 * Revision 1.28  2010/02/08 17:54:38  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.27  2010/01/13 23:40:22  fplanque
 * cleanup
 *
 * Revision 1.26  2009/12/20 20:38:13  fplanque
 * Enhanced skin containers reload for skin developers
 *
 * Revision 1.25  2009/11/30 23:16:24  fplanque
 * basic cache invalidation is working now
 *
 * Revision 1.24  2009/11/30 04:31:38  fplanque
 * BlockCache Proof Of Concept
 *
 * Revision 1.23  2009/11/30 00:22:05  fplanque
 * clean up debug info
 * show more timers in view of block caching
 *
 * Revision 1.22  2009/11/04 13:20:25  efy-maxim
 * some new functions
 *
 * Revision 1.21  2009/10/12 22:11:28  blueyed
 * Fix blank.gif some: use conditional comments, where marked as being required for IE. Add ALT tags and close tags.
 *
 * Revision 1.20  2009/10/04 18:25:13  blueyed
 * Add missing load_class call
 *
 * Revision 1.19  2009/09/26 12:00:43  tblue246
 * Minor/coding style
 *
 * Revision 1.18  2009/09/25 07:33:14  efy-cantor
 * replace get_cache to get_*cache
 *
 * Revision 1.17  2009/06/22 19:31:07  tblue246
 * Skin-specific translations ("locales" folder in the skin's folder, directory structure is the same as for plugins).
 *
 * Revision 1.16  2009/05/24 21:14:38  fplanque
 * _skin.class.php can now provide skin specific settings.
 * Demo: the custom skin has configurable header colors.
 * The settings can be changed through Blog Settings > Skin Settings.
 * Anyone is welcome to extend those settings for any skin you like.
 *
 * Revision 1.15  2009/05/23 22:57:32  fplanque
 * skin settings
 *
 * Revision 1.14  2009/05/23 22:49:10  fplanque
 * skin settings
 *
 * Revision 1.13  2009/05/23 20:20:18  fplanque
 * Skins can now have a _skin.class.php file to override default Skin behaviour. Currently only the default name but can/will be extended.
 *
 * Revision 1.12  2009/03/08 23:57:45  fplanque
 * 2009
 *
 * Revision 1.11  2009/02/05 21:33:34  tblue246
 * Allow the user to enable/disable widgets.
 * Todo:
 * 	* Fix CSS for the widget state bullet @ JS widget UI.
 * 	* Maybe find a better solution than modifying get_Cache() to get only enabled widgets... :/
 * 	* Buffer JS requests when toggling the state of a widget??
 *
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
