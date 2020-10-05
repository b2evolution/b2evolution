<?php
/**
 * This file implements the Item Checklist Lines Widget class.
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
class item_checklist_lines_Widget extends ComponentWidget
{
	var $icon = 'check';

	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'item_checklist_lines' );
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'item-checklist-lines-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Checklist Lines');
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 */
	function get_short_desc()
	{
		return format_to_output( T_('Checklist lines') );
	}


	/**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Display an Item\'s checklist lines.');
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
					'note' => T_( 'Check to enable AJAX editing of checklist lines if current user has permission.' ),
					'defaultvalue' => 1,
				),
			), parent::get_param_definitions( $params ) );

		if( isset( $r['allow_blockcache'] ) )
		{	// Disable "allow blockcache" because this widget uses the selected items:
			$r['allow_blockcache']['defaultvalue'] = false;
			$r['allow_blockcache']['disabled'] = 'disabled';
			$r['allow_blockcache']['note'] = T_('This widget cannot be cached in the block cache.');
		}

		return $r;
	}


	/**
	 * Request all required css and js files for this widget
	 */
	function request_required_files()
	{
		global $Item;

		if( ! empty( $Item ) && $this->get_param( 'allow_edit' ) && $Item->can_meta_comment() )
		{	// Load JS to edit checklist lines if it is enabled by widget setting and current User has a permission to edit them:
			require_js_defer( '#jquery#', 'blog' );
			require_js_defer( '#jqueryUI#', 'blog' );
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
		$this->init_display( $params );

		if( ! empty( $params['Item'] ) )
		{	// Used Item provided by params:
			$Item = $params['Item'];
		}
		else
		{	// use global Item:
			global $Item;
		}

		if( empty( $Item ) )
		{	// Don't display this widget when no Item object
			$this->display_error_message( 'Widget "'.$this->get_name().'" is hidden because there is no Item object.' );
			return false;
		}

		// Check permission to add/edit/delete checklist lines:
		$can_update = $this->get_param( 'allow_edit' ) && $Item->can_meta_comment();

		// Get existing checklist lines:
		$checklist_lines = $Item->get_checklist_lines();

		if( ! $Item->can_see_meta_comments() || // Current User has no perm to view checklist lines
		    ( empty( $checklist_lines ) && ! $can_update ) ) // No
		{	// Nothing to display because current User cannot see this OR the Item has no checklist lines:
			$this->display_debug_message( 'Widget "'.$this->get_name().'" is hidden because you cannot see this or current Item has no checklist lines.' );
			return false;
		}

		echo $this->disp_params['block_start'];
		$this->disp_title();
		echo $this->disp_params['block_body_start'];
		
		echo '<div class="checklist_wrapper">';
		$Form = new Form();
		$Form->switch_layout( 'linespan' );

		if( $can_update )
		{
			$Form->output = false;

			$js_config = array(
				'item_ID'                  => $Item->ID,
				'checklist_line_template'  => '<div class="checkbox checklist_line">
												<label>
													<input id="checklist_line_$checklist_line_ID$" type="checkbox" value="$checklist_line_ID$">
													<span class="checklist_line_label">$checklist_line_label$</span>
												</label>'.
												action_icon( T_('Delete'), 'delete', '#', NULL, NULL, NULL, array( 'class' => 'checklist_line_delete', 'style' => 'visibility:hidden;' ) ).
											'</div>',
				'checklist_line_input_template' => $Form->textarea_input( '$checklist_line_ID$', '$checklist_line_label$', 1, '', array(
						'class'       => 'checklist_line_input',
						'placeholder' => T_('Add an item'),
						'hide_label'  => true,
						'maxlength'   => 10000
					) ),
				'crumb_checklist_line' => get_crumb( 'collections_checklist_line' ),
				'button_label_add' => T_('Add'),
				'button_label_add_an_item' => T_('Add an item'),
			);

			expose_var_to_js( 'evo_init_checklist_lines_config', evo_json_encode( $js_config ) );

			$Form->output = true;
		}

		echo '<div class="checklist_lines">';

		// Extra drop area for checklist lines to first position:
		echo '<div class="checklist_droparea"></div>';

		foreach( $checklist_lines as $line )
		{
			echo '<div class="checkbox checklist_line">';
			echo '<label>';
			echo '<input id="checklist_line_'.$line->check_ID.'" type="checkbox" value="'.$line->check_ID.'"'
					.( $line->check_checked ? ' checked="checked"' : '' ).( $can_update ? '' : ' disabled="disabled"' ).'>';
			echo '<span class="checklist_line_label">'.format_to_output( $line->check_label ).'</span>';
			echo '</label>';
			if( $can_update )
			{
				echo action_icon( T_('Delete'), 'delete', '#', NULL, NULL, NULL, array( 'class' => 'checklist_line_delete', 'style' => 'visibility:hidden;' ) );
			}
			echo '</div>';
		}

		echo '</div>';

		if( $can_update )
		{
			$Form->switch_template_parts( array(
				'fieldstart' => '',
				'fieldend'   => '',
			));
			$Form->textarea_input( 'checklist_input_'.$this->ID, NULL, 1, '', array(
					'class'       => 'add_checklist_line_input checklist_line_input',
					'placeholder' => T_('Add an item'),
					'hide_label'  => true,
					'maxlength'   => 10000,
					'style'       => 'display:none',
				) );
			
			echo '<button type="button" class="btn btn-default btn-xs checklist_add_btn">'.T_('Add an item').'</button>';
			echo '<button type="button" class="btn btn-link btn-xs checklist_close_btn" style="display:none;">'.get_icon( 'close' ).'</button>';
		}

		echo '</div>';

		echo $this->disp_params['block_body_end'];
		echo $this->disp_params['block_end'];

		return true;
	}
}
?>
