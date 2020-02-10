<?php
/**
 * This file implements the item_info_line Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}
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
 * @author erhsatingin: Erwin Rommel Satingin.
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
class item_info_line_Widget extends ComponentWidget
{
	var $icon = 'info';

	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'item_info_line' );
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'info-line-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Info Line');
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 */
	function get_short_desc()
	{
		return format_to_output( T_('Item Info Line') );
	}


	/**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Display information about the item.');
	}


	/**
	 * Get definitions for editable params
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_param_definitions( $params )
	{
		global $current_User, $admin_url;

		// Get available templates:
		$TemplateCache = & get_TemplateCache();
		$TemplateCache->load_where( 'tpl_parent_tpl_ID IS NULL' );

		$r = array_merge( array(
				'title' => array(
					'label' => T_( 'Title' ),
					'size' => 40,
					'note' => T_( 'This is the title to display' ),
					'defaultvalue' => '',
				),
				'template' => array(
					'label' => T_('Template'),
					'type' => 'select',
					'options' => $TemplateCache->get_code_option_array(),
					'defaultvalue' => 'item_details_infoline_standard',
					'input_suffix' => ( is_logged_in() && $current_User->check_perm( 'options', 'edit' ) ? '&nbsp;'
							.action_icon( '', 'edit', $admin_url.'?ctrl=templates', NULL, NULL, NULL, array(), array( 'title' => T_('Manage templates').'...' ) ) : '' ),
				),
			), parent::get_param_definitions( $params ) );

		if( isset( $r['allow_blockcache'] ) )
		{	// Disable "allow blockcache" because this widget displays dynamic data:
			$r['allow_blockcache']['defaultvalue'] = false;
			$r['allow_blockcache']['disabled'] = 'disabled';
			$r['allow_blockcache']['note'] = T_('This widget cannot be cached in the block cache.');
		}

		return $r;
	}


	/**
	 * Display the widget!
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function display( $params )
	{
		global $Item;

		if( empty( $Item ) )
		{	// Don't display this widget when there is no Item object:
			$this->display_error_message( 'Widget "'.$this->get_name().'" is hidden because there is no Item.' );
			return false;
		}

		$params = array_merge( array(
			'author_link_text' => 'preferredname',
			'block_body_start' => '<div>',
			'block_body_end'   => '</div>',
		), $params );

		$this->init_display( $params );

		$TemplateCache = & get_TemplateCache();
		if( ! $TemplateCache->get_by_code( $this->disp_params['template'], false, false ) )
		{
			$this->display_error_message( sprintf( 'Template not found: %s', '<code>'.$this->disp_params['template'].'</code>' ) );
			return false;
		}

		$template_code = $this->disp_params['template'];

		$info_line = render_template_code( $template_code, $params );

		if( ! empty( $info_line ) )
		{
			echo $this->disp_params['block_start'];

			$this->disp_title();

			echo $this->disp_params['block_body_start'];

			echo $info_line;

			echo $this->disp_params['block_body_end'];
			echo $this->disp_params['block_end'];

			return true;
		}

		$this->display_debug_message();
		return false;
	}
}

?>
