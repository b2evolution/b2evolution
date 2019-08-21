<?php
/**
 * This file implements the Workflow Properties Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2019 by Francois Planque - {@link http://fplanque.com/}
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
class item_workflow_Widget extends ComponentWidget
{
	var $icon = 'check';

	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'item_workflow' );
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'item-workflow-properties-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Item Workflow Properties');
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 */
	function get_short_desc()
	{
		return format_to_output( T_('Item Workflow Properties') );
	}


	/**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Display information about item workflow properties.');
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
				'defaultvalue' => T_('Workflow Properties'),
			),
			'show_properties' => array(
				'type' => 'checklist',
				'label' => T_('Show Properties'),
				'options' => array(
					array( 'status', T_('Task status'), 1 ),
					array( 'user', T_('Assigned user'), 1 ),
					array( 'priority', T_('Priority'), 1 ),
					array( 'deadline', T_('Deadline'), 1 ),
				),
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
	 * @param array MUST contain at least the basic display params
	 */
	function display( $params )
	{
		global $Item, $current_User;

		if( empty( $Item ) )
		{ // Don't display this widget when no Item object
			return false;
		}

		if( ! $Item->get_coll_setting( 'use_workflow' ) )
		{	// Workflow is disabled for current Collection:
			return false;
		}

		if( ! is_logged_in() || ! $current_User->check_perm( 'blog_can_be_assignee', 'edit', false, $Item->get_blog_ID() ) )
		{	// Current User has no permission to be assigned for tasks of the Item's Collection:
			return false;
		}

		$this->init_display( $params );

		echo $this->disp_params['block_start'];
		$this->disp_title();
		echo $this->disp_params['block_body_start'];

		if( ! empty( $this->disp_params['show_properties']['status'] ) )
		{	// Display task status:
			echo '<p><b>'.T_('Task status').':</b> <span class="pointer">'.$Item->get( 't_extra_status' ).'</span></p>';
		}

		if( ! empty( $this->disp_params['show_properties']['user'] ) )
		{	// Display assigned user:
			$UserCache = & get_UserCache();
			$assigned_User = & $UserCache->get_by_ID( $Item->get( 'assigned_user_ID' ), false, false );
			echo '<p><b>'.T_('Assigned to').':</b> <span class="pointer">'.( $assigned_User ? $assigned_User->get_identity_link() : T_('No user') ).'</span></p>';
		}

		if( ! empty( $this->disp_params['show_properties']['priority'] ) )
		{	// Display priority:
			echo '<p><b>'.T_('Priority').':</b> <span class="pointer" style="color:'.item_priority_color( $Item->get( 'priority' ) ).'">'.item_priority_title( $Item->get( 'priority' ) ).'</span></p>';
		}

		if( ! empty( $this->disp_params['show_properties']['deadline'] ) )
		{	// Display deadline:
			echo '<p><b>'.T_('Deadline').':</b> <span class="pointer">'.( $Item->get( 'datedeadline' ) === NULL ? T_('None') : mysql2localedatetime( $Item->get( 'datedeadline' ) ) ).'</span></p>';
		}

		// Scroll to meta comment form on click to value of any workflow property:
		echo '<script>jQuery( ".evo_widget.widget_core_item_workflow .pointer" ).click( function() {
			location.href = location.href.replace( /#.*$/, "" ) + "#meta-comment-form";
		} )</script>';

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
		global $Collection, $Blog, $Item, $current_User;

		return array(
				'wi_ID'       => $this->ID, // Have the widget settings changed ?
				'set_coll_ID' => $Blog->ID, // Have the settings of the blog changed ? (ex: new skin)
				'user_ID'     => ( is_logged_in() ? $current_User->ID : 0 ), // Has the current User changed?
				'item_ID'     => empty( $Item ) ? 0 : $Item->ID, // Has the Item page changed?
			);
	}
}

?>