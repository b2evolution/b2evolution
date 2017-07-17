<?php
/**
 * This file display the Antispam IP ranges
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Slug
 */
global $edited_IPRange;

// Determine if we are creating or updating...
global $action;
$creating = $action == 'iprange_new';

$Form = new Form( NULL, 'iprange_checkchanges', 'post', 'compact' );

$Form->global_icon( T_('Delete this IP range!'), 'delete', regenerate_url( 'iprange_ID,action', 'iprange_ID='.$edited_IPRange->ID.'&amp;action=iprange_delete&amp;'.url_crumb( 'iprange' ) ) );
$Form->global_icon( T_('Cancel editing').'!', 'close', regenerate_url( 'action,iprange_ID' ) );

$Form->begin_form( 'fform', ( $creating ?  T_('New IP Range') : T_('IP Range') ).get_manual_link( 'ip-range-editing' ) );

	$Form->add_crumb( 'iprange' );
	$Form->hidden( 'action',  $creating ? 'iprange_create' : 'iprange_update' );
	$Form->hidden_ctrl();
	$Form->hidden( 'tab', get_param( 'tab' ) );
	$Form->hidden( 'tab3', get_param( 'tab3' ) );
	$Form->hidden( 'iprange_ID', param( 'iprange_ID', 'integer', 0 ) );

	$Form->select_input_array( 'aipr_status', $edited_IPRange->get( 'status' ), aipr_status_titles() , T_('Status'), '', array( 'force_keys_as_values' => true, 'background_color' => aipr_status_colors(), 'required' => true ) );

	$Form->text_input( 'aipr_IPv4start', int2ip( $edited_IPRange->get( 'IPv4start' ) ), 50, T_('IP Range Start'), '', array( 'maxlength' => 15, 'required' => true ) );

	$Form->text_input( 'aipr_IPv4end', int2ip( $edited_IPRange->get( 'IPv4end' ) ), 50, T_('IP Range End'), '', array( 'maxlength' => 15, 'required' => true ) );

	$Form->info( T_('User count'), (int)$edited_IPRange->get( 'user_count' ) );

	$Form->info( T_('Block count'), (int)$edited_IPRange->get( 'block_count' ) );

$Form->end_form( array( array( 'submit', 'save', ( $creating ? T_('Record') : T_('Save Changes!') ), 'SaveButton' ) ) );

if( $edited_IPRange->ID > 0 )
{	// Display members of this organization:
	users_results_block( array(
			'reg_ip_min'           => int2ip( $edited_IPRange->get( 'IPv4start' ) ),
			'reg_ip_max'           => int2ip( $edited_IPRange->get( 'IPv4end' ) ),
			'filterset_name'       => 'iprange_'.$edited_IPRange->ID,
			'results_param_prefix' => 'ipruser_',
			'results_title'        => T_('Users registered through this IP Range').get_manual_link( 'ip-range-users' ),
			'results_order'        => '/user_created_datetime/D',
			'page_url'             => get_dispctrl_url( 'antispam', 'tab3=ipranges&action=iprange_edit&amp;iprange_ID='.$edited_IPRange->ID ),
			'display_ID'           => false,
			'display_btn_adduser'  => false,
			'display_btn_addgroup' => false,
			'display_blogs'        => false,
			'display_source'       => false,
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

?>
<script type="text/javascript">
jQuery( document ).ready( function()
{
	jQuery( '#delete_iprange_conflicts' ).click( function()
	{	// Submit form with deleting of all IP range conflicts:
		jQuery( 'form#iprange_checkchanges' )
			.append( '<input type="hidden" name="delete_conflicts" value="1" />' )
			.submit();
	} );
} );
</script>