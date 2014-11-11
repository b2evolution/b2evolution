<?php
/**
 * This file implements the Widget class.
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
 * @author fplanque: Francois PLANQUE
 * @author blueyed: Daniel HAHLER
 *
 * @version $Id: _uiwidget.class.php 6972 2014-06-24 19:12:29Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Widget class which provides an interface to widget methods for other classes.
 *
 * It provides a method {@link replace_vars()} that can be used to replace object properties in given strings.
 * You can also register global action icons.
 *
 * @package evocore
 * @abstract
 */
class Widget
{
	/**
	 * Display parameters.
	 * Example params would be 'block_start' and 'block_end'.
	 * Params may contain special variables that will be replaced by replace_vars()
	 * Different types of Widgets will expect different parameters.
	 * @var array
	 */
	var $params = NULL;

	/**
	 * Title of the widget (to be displayed)
	 */
	var $title;

	/**
	 * List of registered global action icons that get substituted through '$global_icons$'.
	 * @see global_icon()
	 */
	var $global_icons = array();

	/**
	 * Top block which located near second level tabs
	 */
	var $top_block = '';

	/**
	 * Constructor
	 *
	 * @param string template name to get from $AdminUI
	 */
	function Widget( $ui_template = NULL )
	{
		global $AdminUI;

		if( !empty( $ui_template ) )
		{ // Get template params from Admin Skin:
			$this->params = $AdminUI->get_template( $ui_template );
		}
	}


	/**
	 * Registers a global action icon
	 *
	 * @param string TITLE text (IMG and A link)
	 * @param string icon code for {@link get_icon()}
	 * @param string URL to link to
	 * @param integer 1-5: weight of the icon. the icon will be displayed only if its weight is >= than the user setting threshold
	 * @param integer 1-5: weight of the word. the word will be displayed only if its weight is >= than the user setting threshold
	 * @param array Additional attributes to the A tag. See {@link action_icon()}.
	 */
	function global_icon( $title, $icon, $url, $word = '', $icon_weight = 3, $word_weight = 2, $link_attribs = array( 'class'=>'action_icon' ) )
	{
		$this->global_icons[] = array(
			'title' => $title,
			'icon'  => $icon,
			'url'   => $url,
			'word'  => $word,
			'icon_weight'  => $icon_weight,
			'word_weight'  => $word_weight,
			'link_attribs' => $link_attribs );
	}


  /**
	 * Display a template param without replacing variables
	 */
	function disp_template_raw( $param_name )
	{
		echo $this->params[ $param_name ];
	}


  /**
	 * Display a template param with its variables replaced
	 */
	function disp_template_replaced( $param_name )
	{
		echo $this->replace_vars( $this->params[ $param_name ] );
	}


	/**
	 * Replaces $vars$ with appropriate values.
	 *
	 * You can give an alternative string to display, if the substituted variable
	 * is empty, like:
	 * <code>$vars "Display if empty"$</code>
	 *
	 * @param string template
	 * @param array optional params that are put into {@link $this->params}
	 *              to be accessible by derived replace_callback() methods
	 * @return string The substituted string
	 */
	function replace_vars( $template, $params = NULL )
	{
		if( !is_null( $params ) )
		{
			$this->params = $params;
		}

		return preg_replace_callback(
			'~\$([a-z_]+)(?:\s+"([^"]*)")?\$~', # pattern
			array( $this, 'replace_callback_wrapper' ), # callback
			$template );
	}


	/**
	 * This is an additional wrapper to {@link replace_vars()} that allows to react
	 * on the return value of it.
	 *
	 * Used by replace_callback()
	 *
	 * @param array {@link preg_match() preg match}
	 * @return string
	 */
	function replace_callback_wrapper( $match )
	{
		// Replace the variable with its content (which will be computed on the fly)
		$r = $this->replace_callback( $match );

		if( empty($r) )
		{	// Empty result
			if( !empty($match[2]) )
			{
				return $match[2]; // "display if empty"
			}

			// return $match[1];
		}
		return $r;
	}


	/**
	 * Callback function used to replace only necessary values in template.
	 *
	 * This gets used by {@link replace_vars()} to replace $vars$.
	 *
	 * @param array {@link preg_match() preg match}. Index 1 is the template variable.
	 * @return string to be substituted
	 */
	function replace_callback( $matches )
	{
		//echo $matches[1];
		switch( $matches[1] )
		{
			case 'global_icons' :
				// Icons for the whole result set:
				return $this->gen_global_icons();

			case 'title':
				// Results title:
				// Espace $title$ strings from the title to avoid infinite loop replacing
				$escaped_title = str_replace( '$title$', '&#36;title&#36;', $this->title );
				// Replace vars on the title
				$result = $this->replace_vars( $escaped_title );
				// Replace back the $title$ strings and return the result
				return str_replace( '&#36;title&#36;', '$title$', $result );

			case 'no_results':
				// No Results text:
				return $this->no_results_text;

			case 'top_block':
				// Top block:
				return $this->top_block;

			case 'prefix' :
				//prefix
				return $this->param_prefix;

			default:
				return '[Unknown:'.$matches[1].']';
		}
	}


	/**
	 * Generate img tags for registered icons, through {@link global_icon()}.
	 *
	 * This is used by the default callback to replace '$global_icons$'.
	 */
	function gen_global_icons()
	{
		$r = '';

		foreach( $this->global_icons as $icon_params )
		{
			$r .= action_icon( $icon_params['title'], $icon_params['icon'], $icon_params['url'], $icon_params['word'],
						$icon_params['icon_weight'], $icon_params['word_weight'], $icon_params['link_attribs'] );
		}

		return $r;
	}

}


/**
 * Class Table
 * @todo dh> shouldn't this be in a separate file?
 * @package evocore
 */
class Table extends Widget
{
	/**
	 * Total number of pages
	 */
	var $total_pages = 1;

	/**
	 * Number of cols.
	 */
	var $nb_cols;

	/**
	 * Number of lines already displayed
	 */
	var $displayed_lines_count;

	/**
	 * Number of cols already displayed (in current line)
	 */
	var $displayed_cols_count;

	/**
	 * @var array
	 */
	var $fadeout_array;

	var $fadeout_count = 0;

	/**
	 * @var boolean
	 */
	var $is_fadeout_line;

	var $no_results_text;


	/**
	 * URL param names
	 */
	var $param_prefix;


	/**
	 * Parameters for the filter area:
	 */
	var $filter_area;


	/**
	 * Constructor
	 *
	 * @param string template name to get from $AdminUI
	 * @param string prefix to differentiate page/order/filter params
	 */
	function Table( $ui_template = NULL, $param_prefix = '' )
	{
		parent::Widget( $ui_template );

		$this->param_prefix = $param_prefix;

		$this->no_results_text = T_('No results.');
	}


	/**
	 * Initialize things in order to be ready for displaying.
	 *
	 * Lazy fills $this->params
	 *
	 * @param array ***please document***
	 * @param array Fadeout settings array( 'key column' => array of values ) or 'session'
	 */
	function display_init( $display_params = NULL, $fadeout = NULL )
	{
		global $AdminUI, $Session, $Debuglog;

		if( empty( $this->params ) && isset( $AdminUI ) )
		{ // Use default params from Admin Skin:
			$this->params = $AdminUI->get_template( 'Results' );
		}

		// Make sure we have display parameters:
		if( !is_null($display_params) )
		{ // Use passed params:
			//$this->params = & $display_params;
			if( !empty( $this->params ) )
			{
				$this->params = array_merge( $this->params, $display_params );
			}
			else
			{
				$this->params = & $display_params;
			}
		}


		if( $fadeout == 'session' )
		{	// Get fadeout_array from session:
			if( ($this->fadeout_array = $Session->get('fadeout_array')) && is_array( $this->fadeout_array ) )
			{
				$Debuglog->add( 'UIwidget: Got fadeout_array from session data.', 'results' );
				$Session->delete( 'fadeout_array' );
			}
			else
			{
				$this->fadeout_array = NULL;
			}
		}
		else
		{
			$this->fadeout_array = $fadeout;
		}
	}


	/**
	 * Display options area
	 *
	 * @param string name of the option ( ma_colselect, tsk_filter....)
	 * @param string area name ( colselect_area, filter_area )
	 * @param string option title
	 * @param string submit button title
	 * @param string default folde state when is empty in the session
	 *
	 */
	function display_option_area( $option_name, $area_name, $option_title, $submit_title, $default_folde_state = 'expanded' )
	{
		global $debug, $Session;

		// Do we already have a form?
		$create_new_form = ! isset( $this->Form );

		echo $this->replace_vars( $this->params['filters_start'] );

		$fold_state = $Session->get( $option_name );

		if( empty( $fold_state ) )
		{
			$fold_state = $default_folde_state;
		}

		//__________________________________  Toogle link _______________________________________

		if( $fold_state == 'collapsed' )
		{
			echo '<a class="filters_title" href="'.regenerate_url( 'action,target', 'action=expand_filter&target='.$option_name ).'"
								onclick="return toggle_filter_area(\''.$option_name.'\');" >'
						.get_icon( 'expand', 'imgtag', array( 'id' => 'clickimg_'.$option_name ) );
		}
		else
		{
			echo '<a class="filters_title" href="'.regenerate_url( 'action,target', 'action=collapse_filter&target='.$option_name ).'"
								onclick="return toggle_filter_area(\''.$option_name.'\');" >'
						.get_icon( 'collapse', 'imgtag', array( 'id' => 'clickimg_'.$option_name ) );
		}
		echo $option_title.'</a>:';

		//____________________________________ Filters preset ____________________________________

		if( !empty( $this->{$area_name}['presets'] ) )
		{ // We have preset filters
			$r = array();
			// Loop on all preset filters:
			foreach( $this->{$area_name}['presets'] as $key => $preset )
			{
				if( method_exists( $this, 'is_filtered' ) && !$this->is_filtered()
							&& get_param( $this->param_prefix.'filter_preset' ) == $key )
				{ // The list is not filtered and the filter preset is selected, so no link on:
					$r[] = '['.$preset[0].']';
				}
				else
				{	// Display preset filter link:
					if( isset( $preset[2] ) )
					{	// Link with additional params
						$r[] = '<span '.$preset[2].'>[<a href="'.$preset[1].'">'.$preset[0].'</a>]</span>';
					}
					else
					{
						$r[] = '[<a href="'.$preset[1].'">'.$preset[0].'</a>]';
					}
				}
			}

			echo ' '.implode( ' ', $r );
		}

		//_________________________________________________________________________________________

		if( $debug > 1 )
		{
			echo ' <span class="notes">('.$option_name.':'.$fold_state.')</span>';
			echo ' <span id="asyncResponse"></span>';
		}

		// Begining of the div:
		echo '<div id="clickdiv_'.$option_name.'"';
		if( $fold_state == 'collapsed' )
		{
			echo ' style="display:none;"';
		}
		echo '>';

		//_____________________________ Form and callback _________________________________________

		if( !empty($this->{$area_name}['callback']) )
		{	// We want to display filtering form fields:

			if( $create_new_form )
			{	// We do not already have a form surrounding the whole results list:

				if( !empty( $this->{$area_name}['url_ignore'] ) )
				{
					$ignore = $this->{$area_name}['url_ignore'];
				}
				else
				{
					$ignore = $this->page_param;
				}

// fp> CHECKPOINT: If filters break, revert this to 'post'.
				$this->Form = new Form( regenerate_url( $ignore, '', '', '&' ), $this->param_prefix.'form_search', 'get', 'blockspan' ); // COPY!!

				$this->Form->begin_form( '' );
			}

			$submit_name = empty( $this->{$area_name}['submit'] ) ? 'colselect_submit' : $this->{$area_name}['submit'];
			$this->Form->submit( array( $submit_name, $submit_title, 'filter' ) );

			$func = $this->{$area_name}['callback'];
			$func( $this->Form );

			if( $create_new_form )
			{	// We do not already have a form surrounding the whole result list:
				$this->Form->end_form( '' );
				unset( $this->Form );	// forget about this temporary form
			}
		}

		echo '</div>';

		echo $this->params['filters_end'];
	}


	/**
	 * Display the column selection
	 */
	function display_colselect()
	{
		if( empty( $this->colselect_area ) )
		{	// We don't want to display a col selection section:
			return;
		}

		$option_name = $this->param_prefix.'colselect';

		$this->display_option_area( $option_name, 'colselect_area', T_('Columns'), T_('Apply'), 'collapsed');
	}


	/**
	 * Display the filtering form
	 */
	function display_filters()
	{
		if( empty( $this->filter_area ) )
		{	// We don't want to display a filters section:
			return;
		}

		if( empty( $this->param_prefix ) )
		{	// Deny to use a list without prefix
			debug_die( 'You must define a $param_prefix before you can use filters.' );
		}

		$option_name = $this->param_prefix.'filters';

		$submit_title = !empty( $this->filter_area['submit_title'] ) ? $this->filter_area['submit_title'] : T_('Filter list');

		$this->display_option_area( $option_name, 'filter_area', T_('Filters'), $submit_title, 'expanded' );
	}


	/**
	 * Display list/table start.
	 *
	 * Typically outputs UL or TABLE tags.
	 */
	function display_list_start()
	{
		if( $this->total_pages == 0 )
		{	// There are no results! Nothing to display!
			echo $this->replace_vars( $this->params['no_results_start'] );
		}
		else
		{	// We have rows to display:
			echo $this->params['list_start'];
		}
	}


	/**
	 * Display list/table end.
	 *
	 * Typically outputs </ul> or </table>
	 */
	function display_list_end()
	{
		if( $this->total_pages == 0 )
		{ // There are no results! Nothing to display!
			echo $this->replace_vars( $this->params['no_results_end'] );
		}
		else
		{	// We have rows to display:
			echo $this->params['list_end'];
		}
	}


	/**
	 * Display list/table head.
	 *
	 * This includes list head/title and filters.
	 * EXPERIMENTAL: also dispays <tfoot>
	 */
	function display_head()
	{
		if( is_ajax_content() )
		{	// Don't display this content on AJAX request
			return;
		}

		// DISPLAY TITLE:
		if( isset($this->title) )
		{ // A title has been defined for this result set:
			echo $this->replace_vars( $this->params['head_title'] );
		}

		// DISPLAY FILTERS:
		$this->display_filters();

		// DISPLAY COL SELECTION
		$this->display_colselect();


		// Experimental:
		/*echo $this->params['tfoot_start'];
		echo $this->params['tfoot_end'];*/
	}



	/**
	 * Display column headers
	 */
	function display_col_headers()
	{
		echo $this->params['head_start'];

		if( isset( $this->cols ) )
		{

			if( !isset($this->nb_cols) )
			{	// Needed for sort strings:
				$this->nb_cols = count($this->cols);
			}


			$th_group_activated = false;

			// Loop on all columns to see if we have th_group columns:
			foreach( $this->cols as $col )
			{
				if( isset( $col['th_group'] )	)
				{	// We have a th_group column, so break:
					$th_group_activated = true;
					break;
				}
			}

			$current_th_group_colspan = 1;
			$current_th_colspan = 1;
			$current_th_group_title = NULL;
			$current_th_title = NULL;
			$header_cells = array();

			// Loop on all columns to get an array of header cells description
			// Each header cell will have a colspan and rowspan value
			// The line 0 is reserved for th_group
			// The line 1 is reserved for th
			foreach( $this->cols as $key=>$col )
			{
				//_______________________________ TH GROUP __________________________________

				if( isset( $col['th_group'] ) )
				{	// The column has a th_group
					if( is_null( $current_th_group_title ) || $col['th_group'] != $current_th_group_title )
					{	// It's the begining of a th_group colspan (line0):

						//Initialize current th_group colspan to 1 (line0):
						$current_th_group_colspan = 1;

						// Set colspan and rowspan colum for line0 to 1:
						$header_cells[0][$key]['colspan'] = 1;
						$header_cells[0][$key]['rowspan'] = 1;
					}
					else
					{	// The column is part of a th group colspan
						// Update the first th group colspan cell
						$header_cells[0][$key-$current_th_group_colspan]['colspan']++;

						// Set the colspan column to 0 to not display it
						$header_cells[0][$key]['colspan'] = 0;
						$header_cells[0][$key]['rowspan'] = 0;

						//Update current th_group colspan to 1 (line0):
						$current_th_group_colspan++;
					}

					// Update current th group title:
					$current_th_group_title = 	$col['th_group'];
				}

				//___________________________________ TH ___________________________________

				if( is_null( $current_th_title ) || $col['th'] != $current_th_title )
				{	// It's the begining of a th colspan (line1)

					//Initialize current th colspan to 1 (line1):
					$current_th_colspan = 1;

					// Update current th title:
					$current_th_title = $col['th'];

					if( $th_group_activated  && !isset( $col['th_group'] ) )
					{ // We have to lines and the column has no th_group, so it will be a "rowspan2"

						// Set the cell colspan and rowspan values for the line0:
						$header_cells[0][$key]['colspan'] = 1;
						$header_cells[0][$key]['rowspan'] = 2;

						// Set the cell colspan and rowspan values for the line1, to do not display it:
						$header_cells[1][$key]['colspan'] = 0;
						$header_cells[1][$key]['rowspan'] = 0;
					}
					else
					{	// The cell has no rowspan
						$header_cells[1][$key]['colspan'] = 1;
						$header_cells[1][$key]['rowspan'] = 1;
					}
				}
				else
				{	// The column is part of a th colspan
					if( $th_group_activated && !isset( $col['th_group'] ) )
					{	// We have to lines and the column has no th_group, the colspan is "a rowspan 2"

						// Update the first th cell colspan in line0
						$header_cells[0][$key-$current_th_colspan]['colspan']++;

						// Set the cell colspan to 0 in line0 to not display it:
						$header_cells[0][$key]['colspan'] = 0;
						$header_cells[0][$key]['rowspan'] = 0;
					}
					else
					{ // Update the first th colspan cell in line1
						$header_cells[1][$key-$current_th_colspan]['colspan']++;
					}

					// Set the cell colspan to 0 in line1 to do not display it:
					$header_cells[1][$key]['colspan'] = 0;
					$header_cells[1][$key]['rowspan'] = 0;

					$current_th_colspan++;
				}
			}

			// ________________________________________________________________________________

			if( !$th_group_activated )
			{	// We have only the "th" line to display
				$start = 1;
			}
			else
			{	// We have the "th_group" and the "th" lines to display
				$start = 0;
			}

			//__________________________________________________________________________________

			// Loop on all headers lines:
			for( $i = $start; $i <2 ; $i++ )
			{
				echo $this->params['line_start_head'];
				// Loop on all headers lines cells to display them:
				foreach( $header_cells[$i] as $key=>$cell )
				{
					if( $cell['colspan'] )
					{	// We have to dispaly cell:
						if( $i == 0 && $cell['rowspan'] != 2 )
						{	// The cell is a th_group
							$th_title = $this->cols[$key]['th_group'];
							$col_order = isset( $this->cols[$key]['order_group'] );
						}
						else
						{	// The cell is a th
							$th_title = $this->cols[$key]['th'];
							$col_order = isset( $this->cols[$key]['order'] )
							|| isset( $this->cols[$key]['order_objects_callback'] )
							|| isset( $this->cols[$key]['order_rows_callback'] );
						}


						if( isset( $this->cols[$key]['th_class'] ) )
						{	// We have a class for the th column
							$class = $this->cols[$key]['th_class'];
						}
						else
						{	// We have no class for the th column
							$class = '';
						}

						if( $key == 0 && isset($this->params['colhead_start_first']) )
						{ // Display first column start:
							$output = $this->params['colhead_start_first'];

							// Add the total column class in the grp col start first param class:
							$output = str_replace( '$class$', $class, $output );
						}
						elseif( ( $key + $cell['colspan'] ) == (count( $this->cols) ) && isset($this->params['colhead_start_last']) )
						{ // Last column can get special formatting:
							$output = $this->params['colhead_start_last'];

							// Add the total column class in the grp col start end param class:
							$output = str_replace( '$class$', $class, $output );
						}
						else
						{ // Display regular colmun start:
							$output = $this->params['colhead_start'];

							// Replace the "class_attrib" in the grp col start param by the td column class
							$output = str_replace( '$class_attrib$', 'class="'.$class.'"', $output );
						}

						// Replace column header title attribute
						if( isset( $this->cols[$key]['th_title'] ) )
						{ // Column header title is set
							$output = str_replace( '$title_attrib$', ' title="'.$this->cols[$key]['th_title'].'"', $output );
						}
						else
						{ // Column header title is not set, replace with empty string
							$output = str_replace( '$title_attrib$', '', $output );
						}

						// Set colspan and rowspan values for the cell:
						$output = preg_replace( '#(<)([^>]*)>$#', '$1$2 colspan="'.$cell['colspan'].'" rowspan="'.$cell['rowspan'].'">' , $output );

						echo $output;

						if( $col_order )
						{ // The column can be ordered:
							$col_sort_values = $this->get_col_sort_values( $key );


							// Determine CLASS SUFFIX depending on wether the current column is currently sorted or not:
							if( !empty($col_sort_values['current_order']) )
							{ // We are currently sorting on the current column:
								$class_suffix = '_current';
							}
							else
							{	// We are not sorting on the current column:
								$class_suffix = '_sort_link';
							}

							// Display title depending on sort type/mode:
							if( $this->params['sort_type'] == 'single' )
							{ // single column sort type:

								// Title with toggle:
								echo '<a href="'.$col_sort_values['order_toggle'].'"'
											.' title="'.T_('Change Order').'"'
											.' class="single'.$class_suffix.'"'
											.'>'.$th_title.'</a>';

								// Icon for ascending sort:
								echo '<a href="'.$col_sort_values['order_asc'].'"'
											.' title="'.T_('Ascending order').'"'
											.'>'.$this->params['sort_asc_'.($col_sort_values['current_order'] == 'ASC' ? 'on' : 'off')].'</a>';

								// Icon for descending sort:
								echo '<a href="'.$col_sort_values['order_desc'].'"'
											.' title="'.T_('Descending order').'"'
											.'>'.$this->params['sort_desc_'.($col_sort_values['current_order'] == 'DESC' ? 'on' : 'off')].'</a>';

							}
							else
							{ // basic sort type (toggle single column):

								if( $col_sort_values['current_order'] == 'ASC' )
								{ // the sorting is ascending and made on the current column
									$sort_icon = $this->params['basic_sort_asc'];
								}
								elseif( $col_sort_values['current_order'] == 'DESC' )
								{ // the sorting is descending and made on the current column
									$sort_icon = $this->params['basic_sort_desc'];
								}
								else
								{ // the sorting is not made on the current column
									$sort_icon = $this->params['basic_sort_off'];
								}

								// Toggle Icon + Title
								// Set link title only if the column header title was not set
								$link_title = isset( $this->cols[$key]['th_title'] ) ? '' : ' title="'.T_('Change Order').'"';
								echo '<a href="'.$col_sort_values['order_toggle'].'"'
											.$link_title
											.' class="basic'.$class_suffix.'"'
											.'>'.$sort_icon.' '.$th_title.'</a>';

							}

						}
						elseif( $th_title )
						{ // the column can't be ordered, but we still have a header defined:
							echo '<span>'.$th_title.'</span>';
						}
						// </td>
						echo $this->params['colhead_end'];
					}
				}
				// </tr>
				echo $this->params['line_end'];
			}
		} // this->cols not set

		echo $this->params['head_end'];
	}


	/**
	 *
	 */
	function display_body_start()
	{
		echo $this->params['body_start'];

		$this->displayed_lines_count = 0;

	}


	/**
	 *
	 */
	function display_body_end()
	{
		echo $this->params['body_end'];
	}


	/**
	 *
	 */
	function display_line_start( $is_last = false, $is_fadeout_line = false )
	{
		if( $this->displayed_lines_count % 2 )
		{ // Odd line:
			if( $is_last )
				echo $this->params['line_start_odd_last'];
			else
				echo $this->params['line_start_odd'];
		}
		else
		{ // Even line:
			if( $is_last )
				echo $this->params['line_start_last'];
			else
				echo $this->params['line_start'];
		}

		$this->displayed_cols_count = 0;

		$this->is_fadeout_line = $is_fadeout_line;
	}


	/**
	 *
	 */
	function display_line_end()
	{
		echo $this->params['line_end'];

		$this->displayed_lines_count ++;
	}


	/**
	 * Start a column (data).
	 *
	 * @param array Additional attributes for the <td> tag (attr_name => attr_value).
	 */
	function display_col_start( $extra_attr = array() )
	{
		// Get colum definitions for current column:
		$col = $this->cols[$this->displayed_cols_count];

		if( isset( $col['td_class'] ) )
		{	// We have a class for the total column
			$class = $col['td_class'];
		}
		else
		{	// We have no class for the total column
			$class = '';
		}

		/**
		 * Update class and add a fadeout ID for fadeout list results
		 */
		if( $this->is_fadeout_line )
		{
			// echo ' fadeout '.$this->fadeout_count;
			$class .= ' fadeout-ffff00" id="fadeout-'.$this->fadeout_count;
			$this->fadeout_count++;
		}

		if( ($this->displayed_cols_count == 0) && isset($this->params['col_start_first']) )
		{ // Display first column column start:
			$output = $this->params['col_start_first'];
			// Add the total column class in the col start first param class:
			$output = str_replace( '$class$', $class, $output );
		}
		elseif( ( $this->displayed_cols_count == count($this->cols)-1) && isset($this->params['col_start_last']) )
		{ // Last column can get special formatting:
			$output = $this->params['col_start_last'];
			// Add the total column class in the col start end param class:
			$output = str_replace( '$class$', $class, $output );
		}
		else
		{ // Display regular colmun start:
			$output = $this->params['col_start'];
			// Replace the "class_attrib" in the total col start param by the td column class
			$output = str_replace( '$class_attrib$', 'class="'.$class.'"', $output );
		}

		// Custom attributes:
		// Tblue> TODO: Make this more elegant (e. g.: replace "$extra_attr$" with the attributes string).
		if( $extra_attr )
		{
			if ( ! isset ($extra_attr['format_to_output']))
			{
				$output = substr( $output, 0, -1 ).get_field_attribs_as_string( $extra_attr ).'>';
			}
			else
			{
				$format_to_output = $extra_attr['format_to_output'];
				unset($extra_attr['format_to_output']);
				$output = substr( $output, 0, -1 ).get_field_attribs_as_string( $extra_attr, $format_to_output ).'>';


			}

		}
		// Check variables in column declaration:
		$output = $this->parse_class_content( $output );
		echo $output;
	}


  /**
	 *
	 */
	function display_col_end()
	{
		echo $this->params['col_end'];

		$this->displayed_cols_count ++;
	}


	/**
	 * Widget callback for template vars.
	 *
	 * This allows to replace template vars, see {@link Widget::replace_callback()}.
	 *
	 * @return string
	 */
	function replace_callback( $matches )
	{
		// echo '['.$matches[1].']';
		switch( $matches[1] )
		{
			case 'nb_cols' :
				// Number of columns in result:
				if( !isset($this->nb_cols) )
				{
					$this->nb_cols = count($this->cols);
				}
				return $this->nb_cols;

			default :
				return parent::replace_callback( $matches );
		}
	}

	/**
	 * Handle variable subtitutions for class column contents.
	 *
	 * This is one of the key functions to look at when you want to use the Results class.
	 * - #var#
	 */
	function parse_class_content( $content )
	{
		// Make variable substitution for RAWS:
		while (preg_match('!\# (\w+) \#!ix', $content, $matchesarray))
		{ // Replace all matches to the content of the current row's cell. That means that several variables can be inserted to the class.
			if (! empty($this->rows[$this->current_idx]->$matchesarray[1]))
			{
				$content = str_replace($matchesarray[0],$this->rows[$this->current_idx]->$matchesarray[1] , $content);
			}
			else
			{
				$content = str_replace($matchesarray[0], 'NULL' , $content);
			}
		}

		while (preg_match('#% (.+?) %#ix', $content, $matchesarray))
		{
			 eval('$result = '.$matchesarray[1].';');
			 $content = str_replace($matchesarray[0],$result, $content);
		}

		return $content;
	}


	/**
	 * Init results params from skin template params. It's used when Results table is filled from ajax result.
	 *
	 * @param string the template param which can have values( 'admin', 'front' )
	 * @param string the name of the skin
	 */
	function init_params_by_skin( $skin_type, $skin_name )
	{
		switch( $skin_type )
		{
			case 'admin': // admin skin type
				global $adminskins_path;
				require_once $adminskins_path.$skin_name.'/_adminUI.class.php';
				$this->params = AdminUI::get_template( 'Results' );
				break;

			case 'front': // front office skin type
				global $skins_path;
				require_once $skins_path.$skin_name.'/_skin.class.php';
				$this->params = Skin::get_template( 'Results' );
				break;

			default:
				debug_die( 'Invalid results template param!' );
		}
	}

}

?>