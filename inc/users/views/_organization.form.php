<?php
/**
 * This file implements the UI view for the user organization properties.
 *
 * Called by {@link b2users.php}
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * @var Organization
 */
global $edited_Organization;

// Determine if we are creating or updating...
global $action;

$creating = is_create_action( $action );

$Form = new Form( NULL, 'organization_checkchanges', 'post', 'compact' );

if( ! $creating )
{
	$Form->global_icon( T_('Delete this organization!'), 'delete', regenerate_url( 'action', 'action=delete&amp;'.url_crumb('organization') ) );
}
$Form->global_icon( T_('Cancel editing!'), 'close', regenerate_url( 'action,org_ID' ) );

$Form->begin_form( 'fform', ( $creating ? T_('New organization') : T_('Organization') ).get_manual_link( 'organization-form' ) );

	$Form->add_crumb( 'organization' );

	$Form->hiddens_by_key( get_memorized( 'action' ) ); // (this allows to come back to the right list order & page)

	$Form->text_input( 'org_name', $edited_Organization->name, 32, T_('Name'), '', array( 'maxlength' => 255, 'required' => true ) );

	$Form->text_input( 'org_url', $edited_Organization->url, 32, T_('Url'), '', array( 'maxlength' => 2000 ) );

if( $creating )
{
	$Form->end_form( array( array( 'submit', 'actionArray[create]', T_('Record'), 'SaveButton' ),
													array( 'submit', 'actionArray[create_new]', T_('Record, then Create New'), 'SaveButton' ),
													array( 'submit', 'actionArray[create_copy]', T_('Record, then Create Similar'), 'SaveButton' ) ) );
}
else
{
	$Form->end_form( array( array( 'submit', 'actionArray[update]', T_('Save Changes!'), 'SaveButton' ) ) );
}


if( $edited_Organization->ID > 0 )
{ // Display users of this organization
	users_results_block( array(
			'org_ID'               => $edited_Organization->ID,
			'filterset_name'       => 'orgusr_'.$edited_Organization->ID,
			'results_param_prefix' => 'orgusr_',
			'results_title'        => T_('Users of this organization').get_manual_link('users_and_groups'),
			'results_order'        => '/uorg_accepted/D',
			'page_url'             => get_dispctrl_url( 'organizations', 'action=edit&amp;org_ID='.$edited_Organization->ID ),
			'display_orgstatus'    => true,
			'display_ID'           => false,
			'display_btn_adduser'  => false,
			'display_btn_addgroup' => false,
			'display_blogs'        => false,
			'display_source'       => false,
			'display_regdate'      => false,
			'display_regcountry'   => false,
			'display_update'       => false,
			'display_lastvisit'    => false,
			'display_contact'      => false,
			'display_reported'     => false,
			'display_group'        => false,
			'display_level'        => false,
			'display_status'       => false,
			'display_actions'      => false,
			'display_newsletter'   => false,
		) );
}

// AJAX changing of an accept status of organizations for each user
echo_user_organization_js();
?>