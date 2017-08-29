<?php
/**
 * This file implements the page_404_not_found Widget class.
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
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
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
class page_404_not_found_Widget extends ComponentWidget
{
	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'page_404_not_found' );
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'page-404-not-found-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('404 Not Found');
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 */
	function get_short_desc()
	{
		return format_to_output( T_('404 Not Found') );
	}


	/**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Display page "404 Not Found".');
	}


	/**
	 * Get definitions for editable params
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_param_definitions( $params )
	{
		$r = array_merge( array(
				'title' => array(
					'label' => T_('Title'),
					'size' => 40,
					'note' => T_('This is the title to display'),
					'defaultvalue' => '',
				),
				'text' => array(
					'label' => T_('Text'),
					'note' => '',
					'type' => 'html_textarea',
					'defaultvalue' => '',
				),
			), parent::get_param_definitions( $params ) );

		return $r;
	}


	/**
	 * Display the widget!
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function display( $params )
	{
		global $baseurl, $app_name;

		$this->init_display( $params );

		if( empty( $this->disp_params['title'] ) )
		{	// Default title:
			$this->disp_params['title'] = T_('404 Not Found');
		}

		if( empty( $this->disp_params['text'] ) )
		{	// Default title:
			$this->disp_params['text'] = sprintf( T_('%s cannot resolve the requested URL.'), '<a href="'.$baseurl.'">'.$app_name.'</a>' );
		}

		echo $this->disp_params['block_start'];

		$this->disp_title();

		echo $this->disp_params['block_body_start'];
		echo $this->disp_params['text'];
		echo $this->disp_params['block_body_end'];

		echo $this->disp_params['block_end'];

		return true;
	}
}

?>
