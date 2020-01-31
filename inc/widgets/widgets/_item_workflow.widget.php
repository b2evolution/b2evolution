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
		return T_('Workflow Properties');
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
			'allow_edit' => array(
				'label' => T_( 'Allow editing' ),
				'type' => 'checkbox',
				'note' => T_( 'Check to enable editing of workflow properties if current user has permission.' ),
				'defaultvalue' => 0,
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

		$params = array_merge( array(
				'widget_item_workflow_template' => '<p><b>$title$:</b> $workflow_property_value$</p>',
			), $params );

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
		global $ReqURL;

		if( empty( $Item ) )
		{ // Don't display this widget when no Item object
			$this->display_error_message( 'Widget "'.$this->get_name().'" is hidden because there is no Item.' );
			return false;
		}

		if( ! $Item->get_coll_setting( 'use_workflow' ) )
		{	// Workflow is disabled for current Collection:
			$this->display_debug_message( 'Widget "'.$this->get_name().'" is hidden because workflow is disabled for Item\'s Collection.' );
			return false;
		}

		if( ! is_logged_in() || ! $current_User->check_perm( 'blog_can_be_assignee', 'edit', false, $Item->get_blog_ID() ) )
		{	// Current User has no permission to be assigned for tasks of the Item's Collection:
			$this->display_debug_message( 'Widget "'.$this->get_name().'" is hidden because you don\'t have a permission to be assigned for tasks of the Item\'s Collection.' );
			return false;
		}

		$allow_edit = $this->disp_params['allow_edit'] &&
				is_logged_in() &&
				$current_User->check_perm( 'admin', 'restricted' ) &&
				$current_User->check_perm( 'options', 'edit' );

		$this->init_display( $params );

		echo $this->disp_params['block_start'];
		$this->disp_title();
		echo $this->disp_params['block_body_start'];

		echo '<div id="evo_widget_item_workflow_properties_'.$this->ID.'">';
		
			if( ! empty( $this->disp_params['show_properties']['status'] ) )
			{	// Display task status:
				if( $allow_edit )
				{
					$this->display_workflow_property( T_('Task status'), '<a href="'.$ReqURL.'">'.$Item->get( 't_extra_status').'</a>' );
				}
				else
				{
					$this->display_workflow_property( T_('Task status'), $Item->get( 't_extra_status' ) );
				}
			}

			if( ! empty( $this->disp_params['show_properties']['user'] ) )
			{	// Display assigned user:
				$UserCache = & get_UserCache();
				$assigned_User = & $UserCache->get_by_ID( $Item->get( 'assigned_user_ID' ), false, false );
				$this->display_workflow_property( T_('Assigned to'), ( $assigned_User ? $assigned_User->get_identity_link() : T_('No user') ) );
			}

			if( ! empty( $this->disp_params['show_properties']['priority'] ) )
			{	// Display priority:
				if( $allow_edit )
				{
					$this->display_workflow_property( T_('Priority'), '<a href="'.$ReqURL.'" style="color:'.item_priority_color( $Item->get( 'priority' ) ).'">'
							.item_priority_title( $Item->get( 'priority' ) ).'</a>' );
				}
				else
				{
					$this->display_workflow_property( T_('Priority'), '<span style="color:'.item_priority_color( $Item->get( 'priority' ) ).'">'
							.item_priority_title( $Item->get( 'priority' ) ).'</span>' );
				}
			}

			if( ! empty( $this->disp_params['show_properties']['deadline'] ) )
			{	// Display deadline:
				if( $allow_edit )
				{
					$this->display_workflow_property( T_('Deadline'), '<a href="'.$ReqURL.'">'.( $Item->get( 'datedeadline' ) === NULL ? T_('None') : mysql2localedatetime( $Item->get( 'datedeadline' ) ) ).'</a>' );
				}
				else
				{
					$this->display_workflow_property( T_('Deadline'), ( $Item->get( 'datedeadline' ) === NULL ? T_('None') : mysql2localedatetime( $Item->get( 'datedeadline' ) ) ) );
				}
			}

		echo '</div>';

		if( $allow_edit )
		{
			$Form = new Form( get_htsrv_url().'item_edit.php' );
			$Form->switch_layout( 'linespan' );

			echo '<div class="evo_widget_item_workflow_form" id="evo_widget_item_workflow_form_'.$this->ID.'" style="display:none;">';
				

				$Form->begin_form( 'evo_item_workflow_form' );

				$Form->add_crumb( 'item' );
				$Form->hidden( 'blog', $Item->get_blog_ID() );
				$Form->hidden( 'post_ID', $Item->ID );
				$Form->hidden( 'redirect_to', $Item->get_permanent_url() );

				if( ! empty( $this->disp_params['show_properties']['status'] ) )
				{	// Display task status:
					$Item->display_workflow_field( 'status', $Form );
				}

				if( ! empty( $this->disp_params['show_properties']['user'] ) )
				{	// Display assigned user:
					$Item->display_workflow_field( 'user', $Form );
				}

				if( ! empty( $this->disp_params['show_properties']['priority'] ) )
				{	// Display priority:
					$Item->display_workflow_field( 'priority', $Form );
				}

				if( ! empty( $this->disp_params['show_properties']['deadline'] ) )
				{	// Display deadline:
					$Item->display_workflow_field( 'deadline', $Form );
				}

				$Form->button( array( 'submit', 'actionArray[update_workflow]', T_('Update'), 'SaveButton' ) );

			echo '</div>';
			$Form->end_form();

			?>
			<script>
			jQuery( '#evo_widget_item_workflow_properties_<?php echo $this->ID;?> a' ).click( function() {
					jQuery( '#evo_widget_item_workflow_form_<?php echo $this->ID;?>' ).show();
					jQuery( '#evo_widget_item_workflow_properties_<?php echo $this->ID;?>' ).hide();
					return false;
				} );
			</script>
			<?php
		}

		echo $this->disp_params['block_body_end'];
		echo $this->disp_params['block_end'];

		return true;
	}


	/**
	 * Display item workflow property
	 *
	 * @param string Title
	 * @param string Value
	 * @param string Additional attributes
	 */
	function display_workflow_property( $title, $value )
	{
		echo str_replace( array( '$title$', '$workflow_property_value$' ),
				array( $title, $value ),
				$this->disp_params['widget_item_workflow_template'] );
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
