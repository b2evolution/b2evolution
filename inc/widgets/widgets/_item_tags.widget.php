<?php
/**
 * This file implements the item_tags Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2013 by Francois Planque - {@link http://fplanque.com/}
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
class item_tags_Widget extends ComponentWidget
{
	var $icon = 'tag';

	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'item_tags' );
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'item-tags-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Item Tags');
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 */
	function get_short_desc()
	{
		return format_to_output( T_('Item Tags') );
	}


	/**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Display item tags.');
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
					'defaultvalue' => '',
				),
				'before_list' => array(
					'label' => T_( 'Before List' ),
					'size' => 40,
					'note' => T_( 'Label before the list of tags' ),
					'defaultvalue' => T_('Tags').': '
				),
				'allow_edit' => array(
					'label' => T_( 'Allow editing' ),
					'type' => 'checkbox',
					'note' => T_( 'Check to enable AJAX editing of item tags if current user has permission.' ),
					'defaultvalue' => 0,
				),
			), parent::get_param_definitions( $params ) );

		return $r;
	}


	/**
	 * Request all required css and js files for this widget
	 */
	function request_required_files()
	{
		global $Item, $current_User;

		if( ! empty( $Item ) && $this->get_param( 'allow_edit' ) && is_logged_in() && $current_User->check_perm( 'item_post!CURSTATUS', 'edit', false, $Item ) )
		{	// Load JS to edit tags if it is enabled by widget setting and current User has a permission to edit them:
			init_tokeninput_js( 'blog' );
			require_js( '#jquery#' );
			require_js( 'jquery/jquery.cookie.min.js' );
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
		global $Item, $current_User;

		if( empty( $Item ) )
		{ // Don't display this widget when no Item object
			return;
		}

		if( ! ( $this->get_param( 'allow_edit' ) && $Item->can_be_edited() ) &&
		    ! count( $Item->get_tags() ) )
		{	// Nothing to display because current User cannot edit the Item and the Item has no tags:
			return;
		}

		$this->init_display( $params );

		// We renamed some params; older skin may use the old names; let's convert those params now:
		$this->convert_legacy_param( 'widget_coll_item_tags_before', 'widget_item_tags_before' );
		$this->convert_legacy_param( 'widget_coll_item_tags_after', 'widget_item_tags_after' );
		$this->convert_legacy_param( 'widget_coll_item_tags_separator', 'widget_item_tags_separator' );

		$this->disp_params = array_merge( array(
				'widget_item_tags_before'      => '<nav class="small post_tags">',
				'widget_item_tags_before_list' => $this->disp_params['before_list'],
				'widget_item_tags_after'       => '</nav>',
				'widget_item_tags_separator'   => '',
			), $this->disp_params );

		echo $this->disp_params['block_start'];
		$this->disp_title();
		echo $this->disp_params['block_body_start'];

		$tags_params = array(
				'separator' => $this->disp_params['widget_item_tags_separator'],
			);

		if( $this->get_param( 'allow_edit' ) && $Item->can_be_edited() )
		{	// Allow to edit tags if it is enabled by widget setting and current User has a permission to edit them:

			if( isset( $_COOKIE['quick_item_tags'] ) )
			{
				$quick_item_tags = explode( ',', $_COOKIE['quick_item_tags'] );
			}
			else
			{
				$quick_item_tags = array();
			}

			$quick_tag_buttons = '<div class="evo_widget_item_tags_quick_tags btn-group">';

			foreach( $quick_item_tags as $item_tag )
			{
				$quick_tag_buttons .= '<button type="button" class="btn btn-default btn-xs" onclick="add_quick_tag( this )">'.format_to_output( $item_tag ).'</button>';
			}

			$quick_tag_buttons .= '</div>';

			echo '<span id="evo_widget_item_tags_edit_form_'.$this->ID.'" style="display:none">';
			echo $this->disp_params['widget_item_tags_before'].( $this->disp_params['widget_item_tags_before_list'] ? $this->disp_params['widget_item_tags_before_list'].' ' : '' );
			$Form = new Form();
			$Form->switch_layout( 'none' );
			$Form->begin_form();
			$Form->add_crumb( 'collections_update_tags' );
			$Form->text_input( 'item_tags', implode( ', ', $Item->get_tags() ), 40, '' );
			echo $quick_tag_buttons;
			$Form->end_form();
			echo_autocomplete_tags( array(
					'item_ID'        => $Item->ID,
					'update_by_ajax' => true,
					'use_quick_tags' => true,
				) );
			echo $this->disp_params['widget_item_tags_after'];
			echo '</span>';

			// Action icon to display a form to edit tags:
			$this->disp_params['widget_item_tags_before'] = '<span id="evo_widget_item_tags_list_'.$this->ID.'"">'.$this->disp_params['widget_item_tags_before'];
			$this->disp_params['widget_item_tags_after'] .= ' '.action_icon( T_('Edit tags'), 'edit',
					$Item->get_edit_url( array( 'force_backoffice_editing' => true ) ).'#itemform_adv_props',
					NULL, NULL, NULL, array( 'id' => 'evo_widget_item_tags_edit_icon_'.$this->ID ) )
				.'</span>'
				// JS to activate an edit tags form:
				.'<script>
				function add_quick_tag( obj )
				{
					var item_tag = jQuery( obj ).text();
					jQuery( "#item_tags" ).tokenInput( "add", { id: item_tag, name: item_tag } );
				}

				jQuery( "#evo_widget_item_tags_edit_icon_'.$this->ID.'" ).click( function()
				{
					jQuery( "#evo_widget_item_tags_edit_form_'.$this->ID.'" ).show();
					jQuery( "#evo_widget_item_tags_edit_form_'.$this->ID.' input" ).focus();
					jQuery( "#evo_widget_item_tags_list_'.$this->ID.'" ).hide();
					return false;
				} );
				</script>';
		}

		if( $this->get_param( 'allow_edit' ) &&
		    is_logged_in() &&
		    $current_User->check_perm( 'admin', 'restricted' ) &&
		    $current_User->check_perm( 'options', 'edit' ) )
		{	// Use different style for edit mode, make tag icon as link to edit item tag in back-office:
			global $admin_url, $ReqURL;
			$tags_params['before_tag'] = '<span>'.action_icon( T_('Edit tag'), 'tag', $admin_url.'?ctrl=itemtags&amp;action=edit&amp;tag_ID=$tag_ID$&amp;return_to='.rawurlencode( $ReqURL ) );
			$tags_params['after_tag'] = '</span>';
		}

		echo $this->disp_params['widget_item_tags_before'];

		// Display a list of all tags attached to the Item:
		$tags_params['before'] = ( $this->disp_params['widget_item_tags_before_list'] ? $this->disp_params['widget_item_tags_before_list'].' ' : '' );
		$tags_params['after'] = '';
		$Item->tags( $tags_params );

		echo $this->disp_params['widget_item_tags_after'];

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
				'item_ID'      => $Item->ID, // Has the Item page changed?
				'user_ID'      => ( is_logged_in() ? $current_User->ID : 0 ), // Has the current User changed?
			);
	}
}

?>