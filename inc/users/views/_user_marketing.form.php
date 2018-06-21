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

global $servertimenow, $admin_url, $user_tags, $action;

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

echo_user_actions( $Form, $edited_User, $action );

$form_text_title = T_( 'User marketing settings' ); // used for js confirmation message on leave the changed form
$form_title = get_usertab_header( $edited_User, 'marketing', '<span class="nowrap">'.T_( 'User marketing settings' ).'</span>'.get_manual_link( 'user-marketing-tab' ) );

$Form->begin_form( 'fform', $form_title, array( 'title' => ( isset( $form_text_title ) ? $form_text_title : $form_title ) ) );

$Form->add_crumb( 'user' );
$Form->hidden_ctrl();
$Form->hidden( 'user_tab', 'marketing' );

$Form->hidden( 'user_ID', $edited_User->ID );
$Form->hidden( 'edited_user_login', $edited_User->login );

$Form->begin_fieldset( T_('Tags').get_manual_link('user-marketing-tags') );

	if( $action != 'view' )
	{	// If current user can edit this user:
		$Form->usertag_input( 'edited_user_tags', param( 'edited_user_tags', 'string', $user_tags ), 40, T_('Tags'), '', array(
				'maxlength' => 255,
				'style'     => 'width: 100%;',
			) );
	}
	else
	{	// If current user cannot edit this user:
		$Form->info( T_('Tags'), $user_tags );
	}

$Form->end_fieldset(); // user tags

if( $action != 'view' )
{	// If current user can edit this user:
	$Form->buttons( array( array( '', 'actionArray[update]', T_('Save Changes!'), 'SaveButton' ) ) );
}

$Form->end_form();

// Display numbers of users queued for the edited Automation Step:
$SQL = new SQL( 'Get automations for the edited User #'.$edited_User->ID );
$SQL->SELECT( 'autm_ID, autm_name, step_ID, step_label, step_type, step_info, step_order, aust_next_exec_ts' );
$SQL->FROM( 'T_automation__user_state' );
$SQL->FROM_add( 'INNER JOIN T_automation__automation ON aust_autm_ID = autm_ID' );
$SQL->FROM_add( 'LEFT JOIN T_automation__step ON aust_next_step_ID = step_ID' );
$SQL->WHERE( 'aust_user_ID = '.$edited_User->ID );

$Results = new Results( $SQL->get(), 'ustep_', '--A' );

if( $action != 'view' )
{	// If current user can edit this user:
	$Results->global_icon( T_('Add user to an automation...'), 'new', regenerate_url( 'action,user_tab', 'action=new_automation&amp;user_tab=automation' ), T_('Add user to an automation...'), 3, 4, array(
			'class' => 'action_icon btn-primary',
			'onclick' => 'return add_user_automation( '.$edited_User->ID.' )'
		) );
}

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
		'td'       => '%mysql2localedatetime_spans( #aust_next_exec_ts# )%',
		'th_class' => 'shrinkwrap',
		'td_class' => 'timestamp',
	);

$Results->cols[] = array(
		'th'       => T_('Actions'),
		'td'       => action_icon( T_('Remove this user from automation'), 'delete', $admin_url.'?ctrl=user&amp;action=remove_automation&amp;user_ID='.$edited_User->ID.'&amp;autm_ID=$autm_ID$&amp;'.url_crumb( 'user' ) ),
		'th_class' => 'shrinkwrap',
		'td_class' => 'shrinkwrap',
	);

$Results->display();

// Display email campaigns
$campaign_SQL = new SQL( 'Get email campaigns for the edited Uer #'.$edited_User->ID );
$campaign_SQL->SELECT( 'ecmp_ID, ecmp_name, csnd_last_sent_ts, csnd_last_open_ts, csnd_last_click_ts, csnd_cta1, csnd_cta2, csnd_cta3, csnd_like, csnd_status, enls_subscribed, csnd_emlog_ID' );
$campaign_SQL->FROM( 'T_email__campaign' );
$campaign_SQL->FROM_add( 'INNER JOIN T_email__newsletter_subscription ON enls_user_ID = '.$edited_User->ID.' AND enls_enlt_ID = ecmp_enlt_ID' );
$campaign_SQL->FROM_add( 'INNER JOIN T_email__campaign_send ON csnd_camp_ID = ecmp_ID' );
$campaign_SQL->WHERE( 'csnd_user_ID = '.$edited_User->ID );

$campaign_Results = new Results( $campaign_SQL->get(), 'ucamp_', 'D' );
$Results->Cache = & get_EmailCampaignCache();
$campaign_Results->title = T_('Email campaigns').get_manual_link( 'email-campaign-recipients' );

$campaign_Results->cols[] = array(
	'th' => T_('ID'),
	'order' => 'ecmp_ID',
	'th_class' => 'shrinkwrap',
	'td_class' => 'right',
	'td' => '$ecmp_ID$',
);

$campaign_Results->cols[] = array(
	'th' => T_('Campaign name'),
	'order' => 'ecmp_name',
	'td' => '<a href="'.$admin_url.'?ctrl=campaigns&amp;action=edit&amp;ecmp_ID=$ecmp_ID$"><b>$ecmp_name$</b></a>',
);


$campaign_Results->cols[] = array(
		'th' => T_('List Status'),
		'th_class' => 'shrinkwrap',
		'td_class' => 'nowrap',
		'order' => 'enls_user_ID',
		'td' => '~conditional( #enls_subscribed# > 0, \''.format_to_output( T_('Still subscribed'), 'htmlattr' ).'\', \''.format_to_output( T_('Unsubscribed'), 'htmlattr' ).'\' )~',
	);

$campaign_Results->cols[] = array(
		'th' => T_('Campaign Status'),
		'th_class' => 'shrinkwrap',
		'td_class' => 'center nowrap',
		'order' => 'csnd_status',
		'td' => '%user_td_campaign_status( #csnd_status#, #csnd_emlog_ID# )%'
	);

$campaign_Results->cols[] = array(
		'th' => T_('Send date'),
		'th_class' => 'shrinkwrap',
		'td_class' => 'timestamp',
		'order' => 'csnd_last_sent_ts',
		'default_dir' => 'D',
		'td' => '%user_td_emlog_date( #csnd_last_sent_ts# )%',
	);

$campaign_Results->cols[] = array(
	'th' => T_('Last opened'),
	'th_class' => 'shrinkwrap',
	'td_class' => 'timestamp',
	'order' => 'csnd_last_open_ts',
	'default_dir' => 'D',
	'td' => '%user_td_emlog_date( #csnd_last_open_ts# )%',
);

$campaign_Results->cols[] = array(
	'th' => T_('Last clicked'),
	'th_class' => 'shrinkwrap',
	'td_class' => 'timestamp',
	'order' => 'csnd_last_click_ts',
	'default_dir' => 'D',
	'td' => '%user_td_emlog_date( #csnd_last_click_ts# )%',
);

$campaign_Results->cols[] = array(
	'th' => /* TRANS: Call To Action 1*/ T_('CTA1'),
	'th_class' => 'shrinkwrap',
	'td_class' => 'center',
	'order' => 'csnd_cta1',
	'td' => '%user_td_cta( #csnd_cta1# )%'
);

$campaign_Results->cols[] = array(
	'th' => /* TRANS: Call To Action 2*/ T_('CTA2'),
	'th_class' => 'shrinkwrap',
	'td_class' => 'center',
	'order' => 'csnd_cta2',
	'td' => '%user_td_cta( #csnd_cta2# )%'
);

$campaign_Results->cols[] = array(
	'th' => /* TRANS: Call To Action 3*/ T_('CTA3'),
	'th_class' => 'shrinkwrap',
	'td_class' => 'center',
	'order' => 'csnd_cta3',
	'td' => '%user_td_cta( #csnd_cta3# )%'
);

$campaign_Results->cols[] = array(
	'th' => T_('Liked'),
	'th_class' => 'shrinkwrap',
	'td_class' => 'center',
	'order' => 'csnd_like',
	'td' => '%user_td_liked_email( #csnd_like# )%'
);
if( $action != 'view' )
{	// If current user can edit this user:
	$campaign_Results->cols[] = array(
		'th' => T_('Actions'),
		'th_class' => 'small',
		'td_class' => 'shrinkwrap small',
		'td' => '%user_td_campaign_actions( #ecmp_ID#, '.$edited_User->ID.', #csnd_status# )%'
	);
}

$campaign_Results->display();


// End payload block:
$this->disp_payload_end();
?>