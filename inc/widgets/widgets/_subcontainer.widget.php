<?php
/**
 * This file implements the subcontainer Widget class, and it is used to embed a widget container into a widget
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
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
 * @author asimo: Evo Factory / Attila Simo
 *
 * @version $Id: _subcontainer.widget.php 10060 2016-03-09 10:40:31Z yura $
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
class subcontainer_Widget extends ComponentWidget
{
	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'subcontainer' );
	}


	function display( $params )
	{
		global $Blog, $Timer, $displayed_subcontainers;

		// Set the subcontainer code which will be displayed
		$subcontainer_code = $this->disp_params['container'];

		if( ! isset( $displayed_subcontainers ) )
		{ // Initialize the dispalyed subcontainers array at first usage
			// Use this array to avoid embedded containers display in infinite loop
			$displayed_subcontainers = array();
		}
		elseif( in_array( $subcontainer_code, $displayed_subcontainers ) )
		{ // Do not try do display the same subcontainer which were already displayed to avoid infinite display
			return;
		}

		// Add this subcontainer to the displayed_containers array
		$displayed_subcontainers[] = $subcontainer_code;

		$this->init_display( $params );

		// START DISPLAY:
		echo $this->disp_params['block_start'];

		// Display title if requested
		$this->disp_title();

		echo $this->disp_params['block_body_start'];

		/**
		 * @var EnabledWidgetCache
		 */
		$EnabledWidgetCache = & get_EnabledWidgetCache();
		$Widget_array = & $EnabledWidgetCache->get_by_coll_container( $Blog->ID, $subcontainer_code, true );

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

		echo $this->disp_params['block_body_end'];

		echo $this->disp_params['block_end'];

		// Remove the last item which must be this container from the end of the displayed containers
		array_pop( $displayed_subcontainers );

		return true;
	}

	/**
	 * Get name of widget
	 */
	function get_name()
	{
		$title = T_( 'Subcontainer' );
		return $title;
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
		return T_('Embed any container into a widget. Useful to use widget containers embedded into others.');
	}


	/**
	 * Get definitions for editable params
	 *
	 * @param $params
	 */
	function get_param_definitions( $params )
	{
		global $DB, $Blog;

		$WidgetContainerCache = & get_WidgetContainerCache();
		$container_options = array();
		foreach( $WidgetContainerCache->get_by_coll_ID( $Blog->ID ) as $WidgetContainer )
		{
			$container_options[$WidgetContainer->get( 'code' )] = $WidgetContainer->get( 'name' );
		}

		$r = array_merge( array(
				'title' => array(
					'label' => T_('Block title'),
					'size' => 60,
				),
				'container' => array(
					'label' => T_('Container'),
					'note' => T_( 'The container which will be embedded.' ),
					'type' => 'select',
					'options' => $container_options,
					'defaultvalue' => ''
				),
			), parent::get_param_definitions( $params )	);

		if( isset( $r['allow_blockcache'] ) )
		{ // Disable "allow blockcache"
			$r['allow_blockcache']['defaultvalue'] = false;
			$r['allow_blockcache']['disabled'] = 'disabled';
			$r['allow_blockcache']['note'] = T_('This widget cannot be cached in the block cache.');
		}

		return $r;
	}
}

?>