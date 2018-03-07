<?php
/**
 * This file display the automation form
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


global $edited_AutomationStep, $action, $admin_url;

// Get Automation of the creating/editing Step:
$step_Automation = & $edited_AutomationStep->get_Automation();

// Determine if we are creating or updating:
$creating = is_create_action( $action );

$Form = new Form( NULL, 'automation_checkchanges', 'post', 'compact' );

$edit_automation_url = regenerate_url( 'action,step_ID', 'action=edit&amp;autm_ID='.$step_Automation->ID );

$Form->global_icon( T_('Cancel editing').'!', 'close', $edit_automation_url );

$Form->begin_form( 'fform', sprintf( $creating ? ( $edited_AutomationStep->ID > 0 ? T_('Duplicate step') : T_('New step') ) : T_('Step') ).get_manual_link( 'automation-step-form' ) );

$Form->add_crumb( 'automationstep' );
$Form->hidden( 'action', $creating ? ( $edited_AutomationStep->ID > 0 ? 'duplicate_step' : 'create_step' ) : 'update_step' );
$Form->hidden( 'autm_ID', $step_Automation->ID );
$Form->hiddens_by_key( get_memorized( 'action'.( $creating && $edited_AutomationStep->ID == 0 ? ',step_ID' : '' ) ) );

$Form->info( T_('Automation'), '<a href="'.$edit_automation_url.'">'.$step_Automation->get( 'name' ).'</a>' );

if( $step_Automation->ID > 0 )
{
	$Form->info( T_('ID'), $step_Automation->ID );
}

$Form->text_input( 'step_order', $edited_AutomationStep->get( 'order' ), 10, T_('Order'), '', array( 'maxlength' => 11, 'required' => ! $creating, 'note' => $creating ? T_('Leave empty to set an order automatically.') : '' ) );

$Form->select_input_array( 'step_type', $edited_AutomationStep->get( 'type' ), step_get_type_titles(), T_('Type'), '', array( 'force_keys_as_values' => true, 'required' => true ) );

// IF Condition:
$Form->info_field( T_('IF Condition'), '<div id="step_if_condition"></div>', array( 'class' => 'ffield_step_if_condition' ) );
$Form->hidden( 'step_if_condition', '' );

// Email Campaign:
$EmailCampaignCache = & get_EmailCampaignCache();
$EmailCampaignCache->load_all();
$Form->select_input_object( 'step_email_campaign',
	( $edited_AutomationStep->get( 'type' ) == 'send_campaign' ? $edited_AutomationStep->get( 'info' ) : '' ),
	$EmailCampaignCache, T_('Email Campaign'), array( 'allow_none' => true, 'required' => true ) );

// Notification message:
$Form->textarea_input( 'step_notification_message', (
		$edited_AutomationStep->get( 'type' ) == 'notify_owner'
		? $edited_AutomationStep->get( 'info' )
		: 'The User $login$ has reached step $step_number$ (ID: $step_ID$) in automation $automation_name$ (ID: $automation_ID$)'
	), 10, T_('Notification message') );

// Usertag:
$Form->text_input( 'step_usertag', ( in_array( $edited_AutomationStep->get( 'type' ), array( 'add_usertag', 'remove_usertag' ) ) ? $edited_AutomationStep->get( 'info' ) : '' ),
	80, T_('Usertag'), '', array( 'maxlength' => 200 ) );

// Newsletter:
$NewsletterCache = & get_NewsletterCache();
$newsletter_ID = ( in_array( $edited_AutomationStep->get( 'type' ), array( 'subscribe', 'unsubscribe' ) ) ? intval( $edited_AutomationStep->get( 'info' ) ) : 0 );
$NewsletterCache->load_where( 'enlt_active = 1 OR enlt_ID = '.$newsletter_ID );
$Form->select_input_object( 'step_newsletter', $newsletter_ID, $NewsletterCache, T_('List'), array( 'required' => true, 'allow_none' => true ) );

// Automation:
$AutomationCache = & get_AutomationCache();
$AutomationCache->load_all();
$automation_ID = ( $edited_AutomationStep->get( 'type' ) == 'start_automation' ? intval( $edited_AutomationStep->get( 'info' ) ) : 0 );
$Form->select_input_object( 'step_automation', $automation_ID, $AutomationCache, T_('Automation'), array( 'required' => true, 'allow_none' => true ) );

// Load all steps of the edited step's automation excluding current step:
$AutomationStepCache = & get_AutomationStepCache();
$AutomationStepCache->clear();
$AutomationStepCache->load_where( 'step_autm_ID = '.$step_Automation->ID
	.( ! $creating ? ' AND step_ID != '.$edited_AutomationStep->ID : '' ) );
$next_step_prepend_options = array( '' => T_('Continue') );
if( ! $creating )
{	// Display special label for option with current Step:
	$next_step_prepend_options[ $edited_AutomationStep->ID ] = T_('Loop');
}
else
{	// If new step is creating we should use special key because we don't know step ID here,
	// On inserting new Step we replace this temp key with real ID of new inserted Step:
	$next_step_prepend_options['loop'] = T_('Loop');
}
$next_step_prepend_options[-1] = T_('STOP');

$Form->begin_line( '<span id="step_result_label_yes">'.T_( step_get_result_label( $edited_AutomationStep->get( 'type' ), 'YES' ) ).'</span>', 'step_yes_next' );
	$Form->select_input_object( 'step_yes_next_step_ID', $edited_AutomationStep->get( 'yes_next_step_ID' ), $AutomationStepCache, '', array( 'prepend_options' => $next_step_prepend_options ) );
	$Form->duration_input( 'step_yes_next_step_delay', $edited_AutomationStep->get( 'yes_next_step_delay' ), T_('Delay'), 'days', 'minutes', array(
			'none_value_label' => '0',
			'allow_none_title' => false,
		) );
$Form->end_line();

$Form->begin_line( '<span id="step_result_label_no">'.T_( step_get_result_label( $edited_AutomationStep->get( 'type' ), 'NO' ) ).'</span>', 'step_no_next' );
	$Form->select_input_object( 'step_no_next_step_ID', $edited_AutomationStep->get( 'no_next_step_ID' ), $AutomationStepCache, '', array( 'prepend_options' => $next_step_prepend_options ) );
	$Form->duration_input( 'step_no_next_step_delay', $edited_AutomationStep->get( 'no_next_step_delay' ), T_('Delay'), 'days', 'minutes', array(
			'none_value_label' => '0',
			'allow_none_title' => false,
		) );
$Form->end_line();

$Form->begin_line( '<span id="step_result_label_error">'.T_( step_get_result_label( $edited_AutomationStep->get( 'type' ), 'ERROR' ) ).'</span>', 'step_error_next' );
	$Form->select_input_object( 'step_error_next_step_ID', $edited_AutomationStep->get( 'error_next_step_ID' ), $AutomationStepCache, '', array( 'prepend_options' => $next_step_prepend_options ) );
	$Form->duration_input( 'step_error_next_step_delay', $edited_AutomationStep->get( 'error_next_step_delay' ), T_('Delay'), 'days', 'minutes', array(
			'none_value_label' => '0',
			'allow_none_title' => false,
		) );
$Form->end_line();

// These form input vars are used to build elements for "IF Condition", see JS code below:
$Form->switch_layout( 'none' );
$Form->output = false;
$form_duration_selector = $Form->duration_input( '$duration_selector$', '', '', 'days', 'minutes', array(
		'none_value_label' => '0',
		'allow_none_title' => false,
	) );
$NewsletterCache = & get_NewsletterCache();
$NewsletterCache->clear();
global $DB;
$NewsletterCache->load_where( 'enlt_ID IN ( '.$DB->quote( $step_Automation->get_newsletter_IDs() ).' )' );
$lastsent_list_prepend_options = array(
		-1 => T_('Any list'),
		'' => T_('Any list tied to step automation'),
	);
$form_newsletter_selector = $Form->select_input_object( '$newsletter_selector$', '', $NewsletterCache, '', array( 'prepend_options' => $lastsent_list_prepend_options ) );
$Form->output = true;
$Form->switch_layout( NULL );

$Form->end_form( array(
		array( 'submit', 'submit', ( $creating ? T_('Record') : T_('Save Changes!') ), 'SaveButton' )
	) );

if( ! $creating )
{	// Display numbers of users queued for the edited Automation Step:
	$SQL = new SQL( 'Get all users queued for automation step #'.$edited_AutomationStep->ID );
	$SQL->SELECT( 'aust_autm_ID, aust_user_ID, aust_next_exec_ts, user_login' );
	$SQL->FROM( 'T_automation__user_state' );
	$SQL->FROM_add( 'INNER JOIN T_users ON user_ID = aust_user_ID' );
	$SQL->WHERE( 'aust_next_step_ID = '.$edited_AutomationStep->ID );

	$count_SQL = new SQL( 'Get a count of users queued for automation step #'.$edited_AutomationStep->ID );
	$count_SQL->SELECT( 'COUNT( aust_user_ID )' );
	$count_SQL->FROM( 'T_automation__user_state' );
	$count_SQL->WHERE( 'aust_next_step_ID = '.$edited_AutomationStep->ID );

	$Results = new Results( $SQL->get(), 'aust_', '-A', NULL, $count_SQL->get() );

	$Results->title = T_('Users queued').get_manual_link( 'automation-step-users-queued' );

	$Results->cols[] = array(
			'th'    => T_('User'),
			'order' => 'aust_user_ID',
			'td'    => '%get_user_identity_link( "", #aust_user_ID# )%',
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
			'td'       => '%autm_td_users_actions( #aust_autm_ID#, #aust_user_ID#, #user_login#, '.$edited_AutomationStep->ID.', '.$edited_AutomationStep->get( 'order' ).' )%',
			'th_class' => 'shrinkwrap',
			'td_class' => 'shrinkwrap',
		);

	$Results->display( NULL, 'session' );

	// Init JS for form to requeue automation:
	echo_requeue_automation_js();
}
?>
<script type="text/javascript">
// Suggest default values only for new creating Step:
<?php if( $edited_AutomationStep->ID > 0 ) { ?>
set_default_next_step_data = false;
<?php } else { ?>
set_default_next_step_data = true;
jQuery( '#step_yes_next_step_ID, #step_no_next_step_ID, #step_error_next_step_ID,' +
				'#step_yes_next_step_delay_value, #step_no_next_step_delay_value, #step_error_next_step_delay_value' +
				'#step_yes_next_step_delay_name, #step_no_next_step_delay_name, #step_error_next_step_delay_name' ).change( function()
{	// Stop to suggest default values if at least one setting of next steps is chagned by user:
	set_default_next_step_data = false;
} );
<?php } ?>
/**
 * Update form depending on step type
 *
 * @param string Step type
 */
function step_type_update_info( step_type )
{
	jQuery( '#ffield_step_email_campaign, .ffield_step_if_condition, #ffield_step_notification_message, #ffield_step_usertag, #ffield_step_newsletter, #ffield_step_automation' ).hide();
	jQuery( '#ffield_step_no_next' ).show();
	jQuery( '#ffield_step_error_next' ).show();

	switch( step_type )
	{
		case 'send_campaign':
			jQuery( '#ffield_step_email_campaign' ).show();
			jQuery( '#step_result_label_yes' ).html( '<?php echo TS_( step_get_result_label( 'send_campaign', 'YES' ) ); ?>' );
			jQuery( '#step_result_label_no' ).html( '<?php echo TS_( step_get_result_label( 'send_campaign', 'NO' ) ); ?>' );
			jQuery( '#step_result_label_error' ).html( '<?php echo TS_( step_get_result_label( 'send_campaign', 'ERROR' ) ); ?>' );
			if( set_default_next_step_data )
			{	// Suggest default values:
				jQuery( '#step_yes_next_step_ID, #step_no_next_step_ID' ).val( '' );
				jQuery( '#step_error_next_step_ID' ).val( 'loop' );
				jQuery( '#step_yes_next_step_delay_value' ).val( '3' );
				jQuery( '#step_yes_next_step_delay_name' ).val( 'day' );
				jQuery( '#step_no_next_step_delay_value' ).val( '0' );
				jQuery( '#step_no_next_step_delay_name' ).val( 'second' );
				jQuery( '#step_error_next_step_delay_value' ).val( '7' );
				jQuery( '#step_error_next_step_delay_name' ).val( 'day' );
			}
			break;

		case 'notify_owner':
			jQuery( '#ffield_step_notification_message' ).show();
			jQuery( '#ffield_step_no_next' ).hide();
			jQuery( '#step_result_label_yes' ).html( '<?php echo TS_( step_get_result_label( 'notify_owner', 'YES' ) ); ?>' );
			jQuery( '#step_result_label_error' ).html( '<?php echo TS_( step_get_result_label( 'notify_owner', 'ERROR' ) ); ?>' );
			if( set_default_next_step_data )
			{	// Suggest default values:
				jQuery( '#step_yes_next_step_ID' ).val( '' );
				jQuery( '#step_yes_next_step_delay_value' ).val( '0' );
				jQuery( '#step_yes_next_step_delay_name' ).val( 'second' );
				jQuery( '#step_error_next_step_ID' ).val( 'loop' );
				jQuery( '#step_error_next_step_delay_value' ).val( '4' );
				jQuery( '#step_error_next_step_delay_name' ).val( 'hour' );
			}
			break;

		case 'add_usertag':
		case 'remove_usertag':
			jQuery( '#ffield_step_usertag' ).show();
			jQuery( '#ffield_step_error_next' ).hide();
			jQuery( '#step_result_label_yes' ).html( step_type == 'add_usertag' ? '<?php echo TS_( step_get_result_label( 'add_usertag', 'YES' ) ); ?>' : '<?php echo TS_( step_get_result_label( 'remove_usertag', 'YES' ) ); ?>' );
			jQuery( '#step_result_label_no' ).html( step_type == 'add_usertag' ? '<?php echo TS_( step_get_result_label( 'add_usertag', 'NO' ) ); ?>' : '<?php echo TS_( step_get_result_label( 'remove_usertag', 'NO' ) ); ?>' );
			if( set_default_next_step_data )
			{	// Suggest default values:
				jQuery( '#step_yes_next_step_ID, #step_no_next_step_ID' ).val( '' );
				jQuery( '#step_yes_next_step_delay_value, #step_no_next_step_delay_value' ).val( '0' );
				jQuery( '#step_yes_next_step_delay_name, #step_no_next_step_delay_name' ).val( 'second' );
			}
			break;

		case 'subscribe':
		case 'unsubscribe':
			jQuery( '#ffield_step_newsletter' ).show();
			jQuery( '#step_result_label_yes' ).html( step_type == 'subscribe' ? '<?php echo TS_( step_get_result_label( 'subscribe', 'YES' ) ); ?>' : '<?php echo TS_( step_get_result_label( 'unsubscribe', 'YES' ) ); ?>' );
			jQuery( '#step_result_label_no' ).html( step_type == 'subscribe' ? '<?php echo TS_( step_get_result_label( 'subscribe', 'NO' ) ); ?>' : '<?php echo TS_( step_get_result_label( 'unsubscribe', 'NO' ) ); ?>' );
			jQuery( '#step_result_label_error' ).html( step_type == 'subscribe' ? '<?php echo TS_( step_get_result_label( 'subscribe', 'ERROR' ) ); ?>' : '<?php echo TS_( step_get_result_label( 'unsubscribe', 'ERROR' ) ); ?>' );
			if( set_default_next_step_data )
			{	// Suggest default values:
				jQuery( '#step_yes_next_step_ID, #step_no_next_step_ID' ).val( '' );
				jQuery( '#step_yes_next_step_delay_value, #step_no_next_step_delay_value' ).val( '0' );
				jQuery( '#step_yes_next_step_delay_name, #step_no_next_step_delay_name' ).val( 'second' );
				jQuery( '#step_error_next_step_ID' ).val( 'loop' );
				jQuery( '#step_error_next_step_delay_value' ).val( '7' );
				jQuery( '#step_error_next_step_delay_name' ).val( 'day' );
			}
			break;

		case 'start_automation':
			jQuery( '#ffield_step_automation' ).show();
			jQuery( '#step_result_label_yes' ).html( '<?php echo TS_( step_get_result_label( 'start_automation', 'YES' ) ); ?>' );
			jQuery( '#step_result_label_no' ).html( '<?php echo TS_( step_get_result_label( 'start_automation', 'NO' ) ); ?>' );
			jQuery( '#step_result_label_error' ).html( '<?php echo TS_( step_get_result_label( 'start_automation', 'ERROR' ) ); ?>' );
			if( set_default_next_step_data )
			{	// Suggest default values:
				jQuery( '#step_yes_next_step_ID, #step_no_next_step_ID' ).val( '' );
				jQuery( '#step_yes_next_step_delay_value, #step_no_next_step_delay_value' ).val( '0' );
				jQuery( '#step_yes_next_step_delay_name, #step_no_next_step_delay_name' ).val( 'second' );
				jQuery( '#step_error_next_step_ID' ).val( 'loop' );
				jQuery( '#step_error_next_step_delay_value' ).val( '7' );
				jQuery( '#step_error_next_step_delay_name' ).val( 'day' );
			}
			break;

		case 'if_condition':
		default:
			jQuery( '.ffield_step_if_condition' ).show();
			jQuery( '#step_result_label_yes' ).html( '<?php echo TS_( step_get_result_label( 'if_condition', 'YES' ) ); ?>' );
			jQuery( '#step_result_label_no' ).html( '<?php echo TS_( step_get_result_label( 'if_condition', 'NO' ) ); ?>' );
			jQuery( '#step_result_label_error' ).html( '<?php echo TS_( step_get_result_label( 'if_condition', 'ERROR' ) ); ?>' );
			if( set_default_next_step_data )
			{	// Suggest default values:
				jQuery( '#step_yes_next_step_ID' ).val( '' );
				jQuery( '#step_no_next_step_ID' ).val( 'loop' );
				jQuery( '#step_error_next_step_ID' ).val( '-1' );
				jQuery( '#step_yes_next_step_delay_value, #step_no_next_step_delay_value, #step_error_next_step_delay_value' ).val( '0' );
				jQuery( '#step_yes_next_step_delay_name, #step_no_next_step_delay_name, #step_error_next_step_delay_name' ).val( 'second' );
				jQuery( '#step_no_next_step_delay_value' ).val( '12' );
				jQuery( '#step_no_next_step_delay_name' ).val( 'hour' );
			}
			break;
	}
}
jQuery( '#step_type' ).change( function()
{
	step_type_update_info( jQuery( this ).val() );
} );
jQuery( document ).ready( function()
{
	step_type_update_info( jQuery( '#step_type' ).val() );

	// Initialize Query Builder for the field "IF Condition":
	var operators_equal = ['equal', 'not_equal'];
	var operators_default = ['equal', 'not_equal', 'less', 'less_or_equal', 'greater', 'greater_or_equal', 'between', 'not_between'];
	var operators_listsend = ['less', 'less_or_equal', 'greater', 'greater_or_equal'];
	jQuery( '#step_if_condition' ).queryBuilder(
	{
		plugins: ['bt-tooltip-errors'],
		icons: {
			add_group: 'fa fa-plus-circle',
			add_rule: 'fa fa-plus',
			remove_group: 'fa fa-close',
			remove_rule: 'fa fa-close',
			error: 'fa fa-warning',
		},
		lang: {
			operators: {
				equal: '=',
				not_equal: '&#8800;',
				less: '<',
				less_or_equal: '&#8804;',
				greater: '>',
				greater_or_equal: '&#8805;',
			}
		},

		filters: [
		{
			id: 'user_tag',
			label: '<?php echo TS_('User tag' ); ?>',
			type: 'string',
			operators: operators_equal,
		},
		{
			id: 'user_status',
			label: '<?php echo TS_('User Account status' ); ?>',
			type: 'string',
			operators: operators_equal,
			input: 'select',
			values: {
			<?php
				$user_statuses = get_user_statuses();
				foreach( $user_statuses as $user_status_key => $user_status_title )
				{
					echo '\''.$user_status_key.'\': \''.format_to_js( $user_status_title ).'\', ';
				}
			?>
			}
		},
		{
			id: 'date',
			label: '<?php echo TS_('Current date' ); ?>',
			type: 'date',
			operators: operators_default,
			plugin: 'datepicker',
			plugin_config: {
				dateFormat: '<?php echo jquery_datepicker_datefmt(); ?>',
				monthNames: <?php echo jquery_datepicker_month_names(); ?>,
				dayNamesMin: <?php echo jquery_datepicker_day_names(); ?>,
				firstDay: '<?php echo locale_startofweek(); ?>',
			},
			validation: {
				format: '<?php echo strtoupper( jquery_datepicker_datefmt() ); ?>'
			},
		},
		{
			id: 'time',
			label: '<?php echo TS_('Current time' ); ?>',
			type: 'time',
			operators: operators_default,
			placeholder: '23:59',
			validation: {
				format: 'HH:mm'
			},
		},
		{
			id: 'day',
			label: '<?php echo TS_('Current day of the week' ); ?>',
			type: 'integer',
			operators: operators_default,
			input: 'select',
			values: {
				1: '<?php echo TS_('Monday'); ?>',
				2: '<?php echo TS_('Tuesday'); ?>',
				3: '<?php echo TS_('Wednesday'); ?>',
				4: '<?php echo TS_('Thursday'); ?>',
				5: '<?php echo TS_('Friday'); ?>',
				6: '<?php echo TS_('Saturday'); ?>',
				7: '<?php echo TS_('Sunday'); ?>'
			}
		},
		{
			id: 'month',
			label: '<?php echo TS_('Current month' ); ?>',
			type: 'integer',
			operators: operators_default,
			input: 'select',
			values: {
				1: '<?php echo TS_('January'); ?>',
				2: '<?php echo TS_('February'); ?>',
				3: '<?php echo TS_('March'); ?>',
				4: '<?php echo TS_('April'); ?>',
				5: '<?php echo TS_('May '); ?>',
				6: '<?php echo TS_('June'); ?>',
				7: '<?php echo TS_('July'); ?>',
				8: '<?php echo TS_('August'); ?>',
				9: '<?php echo TS_('September'); ?>',
				10: '<?php echo TS_('October'); ?>',
				11: '<?php echo TS_('November'); ?>',
				12: '<?php echo TS_('December'); ?>'
			}
		},
		{
			id: 'listsend_last_sent_to_user',
			label: '<?php echo TS_('Last sent list to user' ); ?>',
			operators: operators_listsend,
			validation: {
				allow_empty_value: true
			},
			input: evo_query_builder_listsend_selectors,
			valueGetter: evo_query_builder_listsend_value_getter,
			valueSetter: evo_query_builder_listsend_value_setter
		},
		{
			id: 'listsend_last_opened_by_user',
			label: '<?php echo TS_('Last opened list by user' ); ?>',
			operators: operators_listsend,
			validation: {
				allow_empty_value: true
			},
			input: evo_query_builder_listsend_selectors,
			valueGetter: evo_query_builder_listsend_value_getter,
			valueSetter: evo_query_builder_listsend_value_setter
		},
		{
			id: 'listsend_last_clicked_by_user',
			label: '<?php echo TS_('Last clicked list by user' ); ?>',
			operators: operators_listsend,
			validation: {
				allow_empty_value: true
			},
			input: evo_query_builder_listsend_selectors,
			valueGetter: evo_query_builder_listsend_value_getter,
			valueSetter: evo_query_builder_listsend_value_setter
		}
		],
		// Prefill the field "IF Condition" with stored data from DB:
		rules: <?php echo $edited_AutomationStep->get( 'if_condition_js_object' ); ?>
	} );
} );

function evo_query_builder_listsend_selectors( rule, input_name )
{
	input_name = input_name.replace( /_value_0$/, '' );

	var form_duration_selector = '<?php echo format_to_js( $form_duration_selector ); ?>'
		.replace( /\$duration_selector\$_value/g, input_name + '_value' )
		.replace( /\$duration_selector\$_name/g, input_name + '_period' );

	var form_newsletter_selector = '<?php echo TS_('List').': '.format_to_js( $form_newsletter_selector ); ?>'
		.replace( /\$newsletter_selector\$/g, input_name + '_newsletter' )

	return form_duration_selector + form_newsletter_selector;
}
function evo_query_builder_listsend_value_getter( rule )
{
	return rule.$el.find('.rule-value-container [name$=_value]').val()
		+ ':' + rule.$el.find('.rule-value-container [name$=_period]').val()
		+ ':' + rule.$el.find('.rule-value-container [name$=_newsletter]').val();
}
function evo_query_builder_listsend_value_setter( rule, value )
{
	var val = value.split( ':' );
	rule.$el.find( '.rule-value-container [name$=_value]' ).val( val[0] ).trigger( 'change' );
	rule.$el.find( '.rule-value-container [name$=_period]' ).val( val[1] ).trigger( 'change' );
	rule.$el.find( '.rule-value-container [name$=_newsletter]' ).val( val[2] ).trigger( 'change' );
}

// Prepare form before submitting:
jQuery( 'form' ).on( 'submit', function()
{
	if( jQuery( '#step_type' ).val() == 'if_condition' )
	{	// Convert "IF Condition" field to JSON format:
		var result = jQuery( '#step_if_condition' ).queryBuilder( 'getRules' );
		if( result === null )
		{	// Stop submitting on wrong SQL:
			return false;
		}
		else
		{	// Set query rules to hidden field before submitting:
			jQuery( 'input[name=step_if_condition]' ).val( JSON.stringify( result ) );
		}
	}
} );
</script>