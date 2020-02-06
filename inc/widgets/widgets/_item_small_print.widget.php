<?php
/**
 * This file implements the item_small_print Widget class.
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
 * @version $Id: _item_small_print.widget.php 10056 2015-10-16 12:47:15Z yura $
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
class item_small_print_Widget extends ComponentWidget
{
	var $icon = 'info-circle';

	/**
	 * Constructor
	 * @param object $db_row
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'item_small_print' );
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'small-print-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Small Print');
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 */
	function get_short_desc()
	{
		return format_to_output( T_('Small Print') );
	}


	/**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Print small information about item.');
	}


	/**
	 * Get definitions for editable params
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param array $params local params like 'for_editing' => true
	 * @return array
	 */
	function get_param_definitions( $params )
	{
		global $current_User, $admin_url;

		load_funcs( 'files/model/_image.funcs.php' );

		// Get available templates:
		$TemplateCache = & get_TemplateCache();
		$TemplateCache->load_where( 'tpl_parent_tpl_ID IS NULL' );
		$template_options = array( NULL => T_('No template / use settings below').':' ) + $TemplateCache->get_code_option_array();

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
					'options' => $template_options,
					'defaultvalue' => NULL,
					'input_suffix' => ( is_logged_in() && $current_User->check_perm( 'options', 'edit' ) ? '&nbsp;'
							.action_icon( '', 'edit', $admin_url.'?ctrl=templates', NULL, NULL, NULL, array(), array( 'title' => T_('Manage templates').'...' ) ) : '' ),
				),
				'format' => array(
					'label' => T_('Format'),
					'note' => T_('Select what format should be displayed'),
					'type' => 'select',
					'options' => array(
							'standard' => T_('Blog standard'),
							'revision' => T_('Revisions'),
						),
					'defaultvalue' => 'standard',
				),
				'avatar_size' => array(
					'label' => T_('Avatar Size'),
					'note' => '',
					'type' => 'select',
					'options' => get_available_thumb_sizes(),
					'defaultvalue' => 'crop-top-32x32',
				),
			), parent::get_param_definitions( $params ) );

		return $r;
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
	 * @param array $params MUST contain at least the basic display params
	 * @return bool
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

		if( $this->disp_params['template'] )
		{
			load_funcs( 'templates/model/_template.funcs.php' );
			$TemplateCache = & get_TemplateCache();
			if( ! $TemplateCache->get_by_code( $this->disp_params['template'], false, false ) )
			{
				$this->display_error_message( sprintf( 'Template not found: %s', '<code>'.$this->disp_params['template'].'</code>' ) );
				return false;
			}

			$template = $this->disp_params['template'];

			// The $this->disp_params is temporarily not merged to the params passed to the template renderer.
			// That array may contain params that are not compatible with all template renderers.
			// For example $params['format'] => 'standard' passed to $Item->categories()
			$small_print = render_template_code( $template, array_merge( array(
					'author_avatar_size'  => $this->disp_params['avatar_size'],
					'author_avatar_class' => 'leftmargin',
				)/*, $this->disp_params*/ ) );
		}
		else
		{	// Build an automatic template:
			$template = '';

			// We renamed some params; older skin may use the old names; let's convert those params now:
			$this->convert_legacy_param( 'widget_coll_small_print_before', 'widget_item_small_print_before' );
			$this->convert_legacy_param( 'widget_coll_small_print_after', 'widget_item_small_print_after' );
			$this->convert_legacy_param( 'widget_coll_small_print_display_author', 'widget_item_small_print_display_author' );

			$this->disp_params = array_merge( array(
					'widget_item_small_print_before'    => '',
					'widget_item_small_print_after'     => '',
					'widget_item_small_print_separator' => ' &bull; ', 
				), $this->disp_params );

			if( $this->disp_params['format'] == 'standard' )
			{	// Blog standard
				$template = '[author;link_text=only_avatar] [flag_icon]';

				if( isset( $Skin ) && $Skin->get_setting( 'display_post_date' ) )
				{
					$template .= '[issue_time;time_format=#extended_date;before= '.T_('This entry was posted on').' ]';
					$template .= '[issue_time;time_format=#short_time;before= '.T_('at'). ' ]';
					$template .= '[author;link_text=auto;before= '.T_('by').' ]';
				}
				else
				{
	
					$template .= '[author;before= '.T_('This entry was posted by').' ;time_format=#extended_date]';
				}

				$template .= '[categories;before= '.T_('and is filed under').' ] [tags;before='.T_('Tags').': ] [edit_link]';

				$widget_params = array(
					'author_avatar_size'  => $this->disp_params['avatar_size'],
					'author_avatar_class' => 'leftmargin',
					
					'before_categories'           => T_('and is filed under').' ',
					'after_categories'            => '.',
					'categories_include_main'     => true,
					'categories_include_other'    => true,
					'categories_include_external' => true,
					'categories_link_categories'  => true,
					
					'before_tags'    => T_('Tags').': ',
					'after_tags'     => '',
					'tags_separator' => ', ',

					'before_edit_link' => '',
					'after_edit_link'  => '',
				);
			}
			else
			{	// Revisions
				$template = '[flag_icon]';
				$template .= '[author;link_text=auto;before= '.T_('Created by').' ]';
				$template .= '[lastedit_user;link_text=auto;before= '.T_('Last edit by').' ]';
				$template .= ' '.T_('on').' [mod_date;date_format=#extended_date]';
				$template .= '[history_link;text='.T_('View change history').']';
				$template .= '[propose_change_link;text='.T_('Propose a change').']';

				$widget_params = array(
					'after_author'        => $this->disp_params['widget_item_small_print_separator'],

					'before_history_link' => $this->disp_params['widget_item_small_print_separator'],
					'after_history_link'  => '',

					'before_propose_change_link' => $this->disp_params['widget_item_small_print_separator'],
					'after_propose_change_link'  => '',
				);
			}

			// The $this->disp_params is temporarily not merged to the params passed to the template renderer.
			// That array may contain params that are not compatible with all template renderers.
			// For example $params['format'] => 'standard' passed to $Item->categories()
			$small_print = render_template( $template, array_merge( $widget_params/*, $this->disp_params*/ ) );

			if( ! empty( $small_print ) )
			{
				$small_print = $this->disp_params['widget_item_small_print_before'].$small_print.$this->disp_params['widget_item_small_print_after'];
			}
		}

		if( ! empty( $small_print ) )
		{
			echo add_tag_class( $this->disp_params['block_start'], 'clearfix' );
			
			$this->disp_title();
			
			echo $this->disp_params['block_body_start'];

			echo $small_print;

			echo $this->disp_params['block_body_end'];
			echo $this->disp_params['block_end'];

			return true;
		}

		$this->display_debug_message();
		return false;
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
				'user_ID'      => ( is_logged_in() ? $current_User->ID : 0 ), // Has the current User changed?
				'cont_coll_ID' => empty( $this->disp_params['blog_ID'] ) ? $Blog->ID : $this->disp_params['blog_ID'], // Has the content of the displayed blog changed ?
				'item_ID'      => $Item->ID, // Has the Item page changed?
				'item_user_flag_'.$Item->ID => ( is_logged_in() ? $current_User->ID : 0 ), // Has the Item data per current User changed?
			);
	}


	/**
	 * Display debug message e-g on designer mode when we need to show widget when nothing to display currently
	 *
	 * @param string Message
	 */
	function display_debug_message( $message = NULL )
	{
		if( $this->mode == 'designer' )
		{	// Display message on designer mode:
			echo $this->disp_params['block_start'];
			$this->disp_title();
			echo $this->disp_params['block_body_start'];
			echo $this->disp_params['widget_item_small_print_before'];
			echo $message;
			echo $this->disp_params['widget_item_small_print_after'];
			echo $this->disp_params['block_body_end'];
			echo $this->disp_params['block_end'];
		}
	}
}

?>
