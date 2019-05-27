<?php
/**
 * This file implements the UI controller for orders.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2019 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


load_funcs( 'orders/model/_orders.funcs.php' );

// Check minimum permission:
$current_User->check_perm( 'admin', 'normal', true );
$current_User->check_perm( 'options', 'view', true );

// Get action parameter from request:
param_action();

if( param( 'payt_ID', 'integer', 0, true ) )
{	// Load payment from DB or cache:
	$PaymentCache = & get_PaymentCache();
	if( ( $edited_Payment = & $PaymentCache->get_by_ID( $payt_ID, false ) ) === false )
	{	
		unset( $edited_Payment );
		forget_param( 'payt_ID' );
		$Messages->add( sprintf( T_('Requested &laquo;%s&raquo; object does not exist any longer.'), T_('Payment') ), 'error' );
		$action = 'nil';
	}
}

// Set options path:
$AdminUI->set_path( 'site', 'payments' );

$AdminUI->breadcrumbpath_init( false );
$AdminUI->breadcrumbpath_add( T_('Site'), $admin_url.'?ctrl=dashboard' );
$AdminUI->breadcrumbpath_add( T_('Payments'), $admin_url.'?ctrl=payments' );

// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

// Begin payload block:
$AdminUI->disp_payload_begin();

evo_flush();

switch( $action )
{
	case 'view':
		// View details of payment:
		$AdminUI->disp_view( 'orders/views/_payment.form.php' );
		break;

	default:
		// Display a list of payments:
		$AdminUI->disp_view( 'orders/views/_payments.view.php' );
		break;
}

// End payload block:
$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();
?>