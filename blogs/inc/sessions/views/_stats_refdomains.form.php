<?php
/**
 * This file display the Domain edit form
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id: _stats_refdomains.form.php 6135 2014-03-08 07:54:05Z manuel $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $edited_Domain;

// Determine if we are creating or updating...
global $action;
$creating = $action == 'domain_new';

$Form = new Form( NULL, 'domain_checkchanges', 'post', 'compact' );

$Form->global_icon( T_('Cancel editing!'), 'close', regenerate_url( 'action,domain_ID' ) );

$Form->begin_form( 'fform', $creating ?  T_('New Domain') : T_('Domain') );

	$Form->add_crumb( 'domain' );
	$Form->hidden( 'action', 'domain_update' );
	$Form->hidden( 'dom_ID', $edited_Domain->ID );
	$Form->hidden_ctrl();
	$Form->hidden( 'tab', get_param( 'tab' ) );
	$Form->hidden( 'tab_from', get_param( 'tab_from' ) );
	$Form->hidden( 'tab3', get_param( 'tab3' ) );

	$Form->text_input( 'dom_name', $edited_Domain->get( 'name' ), 50, T_('Name'), '', array( 'maxlength' => 15, 'required' => true ) );

	$Form->select_input_array( 'dom_type', $edited_Domain->get( 'type' ), stats_dom_type_titles() , T_('Referrer type'), '', array( 'force_keys_as_values' => true, 'required' => true ) );

	$Form->select_input_array( 'dom_status', $edited_Domain->get( 'status' ), stats_dom_status_titles() , T_('Spam status'), '', array( 'force_keys_as_values' => true, 'background_color' => stats_dom_status_colors(), 'required' => true ) );

$Form->end_form( array( array( 'submit', 'submit', $creating ? T_('Record') : T_('Save Changes!'), 'SaveButton' ) ) );

?>