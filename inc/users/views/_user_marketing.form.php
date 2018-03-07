<?php
/**
 * This file implements the UI view for user marketing settings which are visible only for admin users.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var instance of User class
 */
global $edited_User, $UserSettings, $Settings, $Plugins;

global $current_User;

global $servertimenow, $admin_url, $user_tags;

if( ! $current_User->can_moderate_user( $edited_User->ID ) )
{ // Check permission:
	debug_die( T_( 'You have no permission to see this tab!' ) );
}

// Begin payload block:
$this->disp_payload_begin();

// ------------------- PREV/NEXT USER LINKS -------------------
user_prevnext_links( array(
		'user_tab' => 'marketing'
	) );
// ------------- END OF PREV/NEXT USER LINKS -------------------

$user_status_icons = get_user_status_icons();

$Form = new Form( NULL, 'user_checkchanges' );

$Form->title_fmt = '<div class="row"><span class="col-xs-12 col-lg-6 col-lg-push-6 text-right">$global_icons$</span><div class="col-xs-12 col-lg-6 col-lg-pull-6">$title$</div></div>'."\n";

echo_user_actions( $Form, $edited_User, 'edit' );

$form_text_title = T_( 'User marketing settings' ); // used for js confirmation message on leave the changed form
$form_title = get_usertab_header( $edited_User, 'marketing', '<span class="nowrap">'.T_( 'User marketing settings' ).'</span>'.get_manual_link( 'user-marketing-tab' ) );

$Form->begin_form( 'fform', $form_title, array( 'title' => ( isset( $form_text_title ) ? $form_text_title : $form_title ) ) );

$Form->add_crumb( 'user' );
$Form->hidden_ctrl();
$Form->hidden( 'user_tab', 'marketing' );

$Form->hidden( 'user_ID', $edited_User->ID );
$Form->hidden( 'edited_user_login', $edited_User->login );

$Form->begin_fieldset( T_('Tags').get_manual_link('user-marketing-tags') );

	$Form->text_input( 'edited_user_tags', param( 'edited_user_tags', 'string', $user_tags ), 40, T_('Tags'), '', array(
		'maxlength' => 255,
		'style'     => 'width: 100%;',
		'input_prefix' => '<div class="input-group user_admin_tags" style="width: 100%">',
		'input_suffix' => '</div>',
	) );
	?>
	<script type="text/javascript">
	function init_autocomplete_tags( selector )
	{
		var tags = jQuery( selector ).val();
		var tags_json = new Array();
		if( tags.length > 0 )
		{ // Get tags from <input>
			tags = tags.split( ',' );
			for( var t in tags )
			{
				tags_json.push( { id: tags[t], name: tags[t] } );
			}
		}

		jQuery( selector ).tokenInput( '<?php echo get_restapi_url().'usertags' ?>',
		{
			theme: 'facebook',
			queryParam: 's',
			propertyToSearch: 'name',
			tokenValue: 'name',
			preventDuplicates: true,
			prePopulate: tags_json,
			hintText: '<?php echo TS_('Type in a tag') ?>',
			noResultsText: '<?php echo TS_('No results') ?>',
			searchingText: '<?php echo TS_('Searching...') ?>',
			jsonContainer: 'tags',
		} );
	}

	jQuery( document ).ready( function()
	{
		jQuery( '#edited_user_tags' ).hide();
		init_autocomplete_tags( '#edited_user_tags' );
		<?php
			// Don't submit a form by Enter when user is editing the tags
			echo get_prevent_key_enter_js( '#token-input-edited_user_tags' );
		?>
	} );
	</script>
	<?php

$Form->end_fieldset(); // user tags

$action_buttons = array( array( '', 'actionArray[update]', T_('Save Changes!'), 'SaveButton' ) );

$Form->buttons( $action_buttons );

$Form->end_form();

// Display numbers of users queued for the edited Automation Step:
$SQL = new SQL( 'Get automations for the edited User #'.$edited_User->ID );
$SQL->SELECT( 'autm_ID, autm_name, step_ID, step_label, step_type, step_info, step_order, aust_next_exec_ts' );
$SQL->FROM( 'T_automation__user_state' );
$SQL->FROM_add( 'INNER JOIN T_automation__automation ON aust_autm_ID = autm_ID' );
$SQL->FROM_add( 'LEFT JOIN T_automation__step ON aust_next_step_ID = step_ID' );
$SQL->WHERE( 'aust_user_ID = '.$edited_User->ID );

$Results = new Results( $SQL->get(), 'ustep_', '--A' );

$Results->global_icon( T_('Add user to an automation...'), 'new', regenerate_url( 'action,user_tab', 'action=new_automation&amp;user_tab=automation' ), T_('Add user to an automation...'), 3, 4, array(
		'class' => 'action_icon btn-primary',
		'onclick' => 'return add_user_automation( '.$edited_User->ID.' )'
	) );

$Results->title = T_('Automations').get_manual_link( 'user-automations' );

$Results->cols[] = array(
		'th'    => T_('Automation'),
		'order' => 'autm_name',
		'td'    => ( $current_User->check_perm( 'options', 'edit' )
			? '<a href="'.$admin_url.'?ctrl=automations&amp;action=edit&amp;autm_ID=$autm_ID$"><b>$autm_name$</b></a>'
			: '$autm_name$' ),
	);

$Results->cols[] = array(
		'th'    => T_('Next step'),
		'order' => 'step_order, step_label',
		'td'    => '%step_td_user_state( #step_ID#, #step_label#, #step_type#, #step_info#, #step_order# )%',
	);

$Results->cols[] = array(
		'th'       => T_('Next execution time'),
		'order'    => 'aust_next_exec_ts',
		'td'       => '%mysql2localedatetime( #aust_next_exec_ts# )%',
		'th_class' => 'shrinkwrap',
		'td_class' => 'nowrap',
	);

$Results->cols[] = array(
		'th'       => T_('Actions'),
		'td'       => action_icon( T_('Remove this user from automation'), 'delete', $admin_url.'?ctrl=user&amp;action=remove_automation&amp;user_ID='.$edited_User->ID.'&amp;autm_ID=$autm_ID$&amp;'.url_crumb( 'user' ) ),
		'th_class' => 'shrinkwrap',
		'td_class' => 'shrinkwrap',
	);

$Results->display();

// End payload block:
$this->disp_payload_end();
?>