<?php
/**
 * This file implements the item_attachments Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
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
class item_attachments_Widget extends ComponentWidget
{
	var $icon = 'link';

	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'item_attachments' );
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'item-attachments-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Attachments');
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 */
	function get_short_desc()
	{
		return format_to_output( T_('Item Attachments') );
	}


	/**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Display item attachments.');
	}


	/**
	 * Get definitions for editable params
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_param_definitions( $params )
	{
		global $admin_url;

		// Get available templates:
		$context = 'item_details';
		$TemplateCache = & get_TemplateCache();
		$TemplateCache->load_by_context( $context );

		$r = array_merge( array(
			'title' => array(
				'label' => T_('Title'),
				'size' => 40,
				'note' => T_('This is the title to display'),
				'defaultvalue' => T_('Attachments').':',
			),
			'template' => array(
				'label' => T_('Template'),
				'type' => 'select',
				'options' => $TemplateCache->get_code_option_array(),
				'defaultvalue' => 'item_details_files_list',
				'input_suffix' => ( check_user_perm( 'options', 'edit' ) ? '&nbsp;'
						.action_icon( '', 'edit', $admin_url.'?ctrl=templates&amp;context='.$context, NULL, NULL, NULL,
						array( 'onclick' => 'return b2template_list_highlight( this )', 'target' => '_blank' ),
						array( 'title' => T_('Manage templates').'...' ) ) : '' ),
				'class' => 'evo_template_select',
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
		global $Item;

		if( empty( $Item ) )
		{	// Don't display this widget when no Item object:
			$this->display_error_message( 'Widget "'.$this->get_name().'" is hidden because there is no Item.' );
			return false;
		}

		$this->init_display( $params );

		$TemplateCache = & get_TemplateCache();
		if( ! $TemplateCache->get_by_code( $this->disp_params['template'], false, false ) )
		{	// No template:
			$this->display_error_message( sprintf( 'Template not found: %s', '<code>'.$this->disp_params['template'].'</code>' ) );
			return false;
		}

		$this->disp_params = array_merge( array(
				'widget_item_attachments_params' => array(),
				'image_attachment' => true,
			), $this->disp_params );

		$item_files = render_template_code( $this->disp_params['template'], $this->disp_params );

		if( empty( $item_files ) )
		{	// Don't display this widget when Item has no attachments:
			$this->disp_params = array_merge( array(
				'hide_header_title' => true,
			), $this->disp_params );
			$this->display_debug_message( 'Widget "'.$this->get_name().'" is hidden because Item has no attachments.' );
			return false;
		}

		echo $this->disp_params['block_start'];
		$this->disp_title();
		echo $this->disp_params['block_body_start'];

		// Display attachments/files that are linked to the current item:
		echo $item_files;

		echo $this->disp_params['block_body_end'];
		echo $this->disp_params['block_end'];

		return true;
	}


	/**
	 * Maybe be overriden by some widgets, depending on what THEY depend on..
	 *
	 * @return array of keys this widget depends on
	 */
	function get_cache_keys()
	{
		global $Collection, $Blog, $Item;

		return array(
				'wi_ID'        => $this->ID, // Have the widget settings changed ?
				'set_coll_ID'  => $Blog->ID, // Have the settings of the blog changed ? (ex: new skin)
				'cont_coll_ID' => $Blog->ID, // Has the content of the displayed blog changed ?
				'item_ID'      => ( empty( $Item->ID ) ? 0 : $Item->ID ), // Has the Item page changed?
				'template_code' => $this->get_param( 'template' ), // Has the Template changed?
			);
	}
}

?>
