<?php
/**
 * This file implements the Item Checklist Items Widget class.
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
 *
 * @version $Id: $
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
class item_checklist_items_Widget extends ComponentWidget
{
	var $icon = 'check';

	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'item_checklist_items' );
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'item-checklist-items-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Checklist Items');
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 */
	function get_short_desc()
	{
		return format_to_output( T_('Checklist items') );
	}


	/**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Display checklist items.');
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
					'label' => T_( 'Title' ),
					'size' => 40,
					'note' => T_( 'This is the title to display' ),
					'defaultvalue' => 'Checklist',
				),
				'allow_edit' => array(
					'label' => T_( 'Allow editing' ),
					'type' => 'checkbox',
					'note' => T_( 'Check to enable AJAX editing of checklist items if current user has permission.' ),
					'defaultvalue' => 1,
				),
			), parent::get_param_definitions( $params ) );

		return $r;
	}


	/**
	 * Request all required css and js files for this widget
	 */
	function request_required_files()
	{
		global $Item;

		if( ! empty( $Item ) && $this->get_param( 'allow_edit' ) && $Item->can_meta_comment() )
		{	// Load JS to edit checklist items if it is enabled by widget setting and current User has a permission to edit them:
			require_js_defer( '#jquery#', 'blog' );
		}
	}


	/**
	 * Prepare display params
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function init_display( $params )
	{
		global $preview;

		parent::init_display( $params );

		if( $preview )
		{	// Disable block caching for this widget when item is previewed currently:
			$this->disp_params['allow_blockcache'] = 0;
		}
	}


	/**
	 * Display the widget!
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function display( $params )
	{
		global $Item;

		$this->init_display( $params );
		

		if( empty( $Item ) )
		{	// Don't display this widget when no Item object
			$this->display_error_message( 'Widget "'.$this->get_name().'" is hidden because there is no Item object.' );
			return false;
		}

		if( ! ( $this->get_param( 'allow_edit' ) && $Item->can_meta_comment() ) &&
			! count( $Item->get_checklist_items() ) )
		{	// Nothing to display because current User cannot edit the Item and the Item has no checklist items:
			$this->display_debug_message( 'Widget "'.$this->get_name().'" is hidden because Item has no checklist items and you cannot add new for the Item.' );
			return false;
		}

		// Check permission:
		$can_update = $Item->can_meta_comment() && $this->get_param( 'allow_edit' );

		$this->disp_params = array_merge( array(
				'widget_item_checklist_before'             => '<div class="item_checklist">',
				'widget_item_checklist_after'              => '</div>',
			), $this->disp_params );

		echo $this->disp_params['block_start'];
		$this->disp_title();
		echo $this->disp_params['block_body_start'];
		
		$Form = new Form();
		$Form->begin_form();
		$Form->switch_layout( 'linespan' );

		if( $can_update )
		{
			$Form->output = false;

			$js_config = array(
				'item_ID'                  => $Item->ID,
				'checklist_item_template'  => '<div class="checkbox checklist_item">
												<label>
													<input id="$checklist_item_ID$" type="checkbox" value="$checklist_item_value$">
													<span class="checklist_item_label">$checklist_item_label$</span>
												</label>'.
												action_icon( T_('Delete'), 'delete', '#', NULL, NULL, NULL, array( 'class' => 'checklist_item_delete', 'style' => 'visibility:hidden;' ) ).
											'</div>',
				'checklist_item_input_template' => $Form->textarea_input( '$checklist_item_ID$', '$checklist_item_label$', 1, '', array(
						'class'       => 'checklist_item_input',
						'placeholder' => T_('Add an item'),
						'hide_label'  => true,
						'maxlength'   => 10000
					) ),
				'crumb_checklist_item' => get_crumb( 'collections_checklist_item' ),
				'button_label_add' => T_('Add'),
				'button_label_add_an_item' => T_('Add an item'),
			);

			expose_var_to_js( 'evo_init_checklist_items_config', evo_json_encode( $js_config ) );

			$Form->output = true;
		}

		echo $this->disp_params['widget_item_checklist_before'];
		echo '<div class="checklist_items form-group">';

		$checklist_items = $Item->get_checklist_items();

		foreach( $checklist_items as $item )
		{
			echo '<div class="checkbox checklist_item">';
			echo '<label>';
			echo '<input id="checklist_item_'.$item->check_ID.'" type="checkbox" value="'.$item->check_ID.'"'
					.( $item->check_checked ? ' checked="checked"' : '' ).( $can_update ? '' : ' disabled="disabled"' ).'>';
			echo '<span class="checklist_item_label">'.$item->check_label.'</span>';
			echo '</label>';
			if( $can_update )
			{
				echo action_icon( T_('Delete'), 'delete', '#', NULL, NULL, NULL, array( 'class' => 'checklist_item_delete', 'style' => 'visibility:hidden;' ) );
			}
			echo '</div>';
		
		}

		echo '</div>';
		echo $this->disp_params['widget_item_checklist_after'];

		if( $can_update )
		{
			$Form->textarea_input( 'checklist_input_'.$this->ID, NULL, 1, '', array(
					'class'       => 'add_checklist_item_input checklist_item_input',
					'placeholder' => T_('Add an item'),
					'hide_label'  => true,
					'maxlength'   => 10000,
					'style'       => 'display: none',
				) );
			
			echo '<button type="button" class="btn btn-default checklist_add_btn">'.T_('Add an item').'</button>';
			echo '<button type="button" class="btn btn-link checklist_close_btn" style="display:none;">'.get_icon( 'close' ).'</button>';
		}

		$Form->end_form();

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
		global $Collection, $Blog, $current_User, $Item;

		return array(
				'wi_ID'        => $this->ID, // Have the widget settings changed ?
				'set_coll_ID'  => $Blog->ID, // Have the settings of the blog changed ? (ex: new skin)
				'cont_coll_ID' => empty( $this->disp_params['blog_ID'] ) ? $Blog->ID : $this->disp_params['blog_ID'], // Has the content of the displayed blog changed ?
				'item_ID'      => ( empty( $Item->ID ) ? 0 : $Item->ID ), // Has the Item page changed?
				'user_ID'      => ( is_logged_in() ? $current_User->ID : 0 ), // Has the current User changed?
			);
	}
}
?>
