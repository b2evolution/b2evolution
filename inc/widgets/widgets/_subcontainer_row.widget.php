<?php
/**
 * This file implements the subcontainer Widget class, and it is used to embed a widget container into a widget
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
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
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'widgets/model/_widget.class.php', 'ComponentWidget' );

/**
 * ComponentWidget Class
 *
 * A ComponentWidget is a displayable entity that can be placed into a Container on a web page.
 *
 * @package evocore
 */
class subcontainer_row_Widget extends ComponentWidget
{
	var $icon = 'cubes';

	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'subcontainer_row' );
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'columns-subcontainers-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Columns (Sub-Containers)');
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 */
	function get_short_desc()
	{
		return format_to_output( $this->disp_params['title'] );
	}


	/**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Arrange several Sub-Containers into columns.');
	}


	/**
	 * Get definitions for editable params
	 *
	 * @param $params
	 */
	function get_param_definitions( $params )
	{
		// Get options array with groups for collection and shared sub-containers:
		$WidgetContainerCache = & get_WidgetContainerCache();
		$subcontainers_options = $WidgetContainerCache->get_subcontainers_option_array( $this->get_container_param( 'skin_type' ) );

		$widget_params =  array(
			'title' => array(
				'label' => T_('Block title'),
				'size' => 60,
			) );
		for( $i = 1; $i <= 6; $i++ )
		{	// 6 columns for widget containers:
			$widget_params['column'.$i.'_container'] = array(
				'label' => sprintf( T_('Sub-Container for Column %d'), $i ),
				'note' => T_('This Sub-Container will hold the widgets/contents for this column.'),
				'type' => 'select',
				'options' => $subcontainers_options,
				'defaultvalue' => ''
			);
			$widget_params['column'.$i.'_class'] = array(
				'label' => sprintf( T_('CSS Classes for Column %d'), $i ),
				'note' => T_('Use to determine column arrangement.'),
				'size' => 60,
				'defaultvalue' => 'col-lg-4 col-md-6 col-sm-6 col-xs-12'
			);
		}

		$r = array_merge( $widget_params, parent::get_param_definitions( $params ) );

		if( isset( $r['allow_blockcache'] ) )
		{	// Disable "allow blockcache":
			$r['allow_blockcache']['defaultvalue'] = false;
			$r['allow_blockcache']['disabled'] = 'disabled';
			$r['allow_blockcache']['note'] = T_('This widget cannot be cached in the block cache.');
		}

		return $r;
	}


	/**
	 * Update the DB based on previously recorded changes
	 */
	function dbupdate()
	{
		global $DB;

		$DB->begin();

		$result = true;

		for( $i = 1; $i <= 6; $i++ )
		{
			if( $this->get_param( 'column'.$i.'_container' ) == '!create_new' )
			{	// This is a request to create new sub-container:
				$new_container_code = $this->create_auto_subcontainer( ' Column '.$i );
				if( $new_container_code === false )
				{	// Stop updating if some new container cannot be created:
					$result = false;
					break;
				}
				// Use new created sub-container for this updating widget:
				$this->set( 'column'.$i.'_container', $new_container_code );
			}
		}

		// Do update only if all requested sub-containers have been created successfully:
		$result = $result && parent::dbupdate();

		if( $result )
		{
			$DB->commit();
		}
		else
		{
			$DB->rollback();
		}

		return $result;
	}


	/**
	 * Display the widget!
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function display( $params )
	{
		$this->init_display( $params );

		// START DISPLAY:
		echo $this->disp_params['block_start'];

		// Display title if requested
		$this->disp_title();

		echo $this->disp_params['block_body_start'];

		echo $this->disp_params['rwd_start'];

		if( isset( $params['override_params_for_'.$this->code] ) )
		{	// Use specific widget params if they are defined for this widget by code:
			$params = array_merge( $params, $params['override_params_for_'.$this->code] );
		}

		for( $i = 1; $i <= 6; $i++ )
		{
			if( empty( $this->disp_params['column'.$i.'_container'] ) )
			{	// Skip column without selected container:
				continue;
			}

			echo str_replace( '$wi_rwd_block_class$', $this->disp_params['column'.$i.'_class'], $this->disp_params['rwd_block_start'] );

			// Display widget container of the column:
			$this->display_column_container( $this->disp_params['column'.$i.'_container'], $params );

			echo $this->disp_params['rwd_block_end'];
		}

		echo $this->disp_params['rwd_end'];

		echo $this->disp_params['block_body_end'];

		echo $this->disp_params['block_end'];

		return true;
	}


	/**
	 * Display widget container of one column
	 *
	 * @param string Sub-container code
	 * @param array Params
	 */
	function display_column_container( $subcontainer_code, $params )
	{
		global $Blog, $Timer, $displayed_subcontainers, $Session;

		// Get container type "coll"/"shared" and real container code from stored value:
		$code_data = explode( ':', $subcontainer_code, 2 );
		if( count( $code_data ) != 2 || ! in_array( $code_data[0], array( 'coll', 'shared' ) ) )
		{	// Invalid container code:
			echo '<div class="alert alert-danger">'.'Widget "'.$this->get_name().'" has a wrong container code "'.$subcontainer_code.'".'.'</div>';
			return false;
		}

		$subcontainer_coll_ID = ( $code_data[0] == 'coll' ? $this->get_coll_ID() : NULL );
		$subcontainer_code = $code_data[1];

		// Get subcontainer name:
		$WidgetContainerCache = & get_WidgetContainerCache();
		$WidgetContainer = & $WidgetContainerCache->get_by_coll_skintype_code( $subcontainer_coll_ID, $this->get_container_param( 'skin_type' ), $subcontainer_code );
		$subcontainer_name = $WidgetContainer ? $WidgetContainer->get( 'name' ) : $subcontainer_code;

		if( ! isset( $displayed_subcontainers ) )
		{	// Initialize the dispalyed subcontainers array at first usage:
			// Use this array to avoid embedded containers display in infinite loop
			$displayed_subcontainers = array();
		}
		elseif( in_array( $subcontainer_code, $displayed_subcontainers ) )
		{	// Do not try do display the same subcontainer which were already displayed to avoid infinite display:
			echo '<div class="alert alert-danger">'.sprintf( T_('Cannot include container "%s" because it would create an infinite loop.'), $subcontainer_name ).'</div>';
			return;
		}

		// Add this subcontainer to the displayed_containers array:
		$displayed_subcontainers[] = $subcontainer_code;

		// Initialize params for current subcontainer:
		$subcontainer_params = widget_container_customize_params( $params, $subcontainer_code, $subcontainer_name );

		echo $subcontainer_params['container_start'];

		// Get enabled widgets of the container:
		$EnabledWidgetCache = & get_EnabledWidgetCache();
		$container_widgets = & $EnabledWidgetCache->get_by_coll_container( $subcontainer_coll_ID, $subcontainer_code, true );

		if( ! empty( $container_widgets ) )
		{
			foreach( $container_widgets as $ComponentWidget )
			{	// Let the Widget display itself (with contextual params):
				$widget_timer_name = 'Widget->display('.$ComponentWidget->code.')';
				$Timer->start( $widget_timer_name );
				// Clear the display params in order to use new custom if they are defined for this widget from skin side by param "override_params_for_subcontainer_row":
				// (otherwise the params will be used from first initialized widget container by $subcontainer_code)
				$ComponentWidget->disp_params = NULL;
				$ComponentWidget->display_with_cache( $params );
				$Timer->pause( $widget_timer_name );
			}
		}
		elseif( is_logged_in() && $Session->get( 'designer_mode_'.$Blog->ID ) )
		{	// Display text for empty container on designer mode:
			echo '<div class="red">'.T_('Empty Sub-Container').'</div>';
		}

		echo $subcontainer_params['container_end'];

		// Remove the last item which must be this container from the end of the displayed containers:
		array_pop( $displayed_subcontainers );
	}
}

?>