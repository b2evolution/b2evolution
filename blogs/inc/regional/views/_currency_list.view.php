<?php

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $dispatcher;

// Create result set:
$Results = & new Results(
							'SELECT * FROM T_currency', 'curr_' );
$Results->Cache = & get_Cache( 'CurrencyCache' );
$Results->title = T_('Currencies list');

if( $current_User->check_perm( 'options', 'edit', false ) )
{ // We have permission to modify:
	$Results->cols[] = array(
							'th' => T_('Code'),
							'order' => 'curr_code',
							'td' => '<strong><a href="'.$dispatcher.'?ctrl=currencies&amp;curr_ID=$curr_ID$&amp;action=edit" title="'.
											T_('Edit this currency...').'">$curr_code$</a></strong>',
						);	
}
else
{	// View only:
	$Results->cols[] = array(
							'th' => T_('Code'),
							'order' => 'curr_code',
							'td' => '<strong>$curr_code$</strong>',
						);

}

$Results->cols[] = array(
						'th' => T_('Shortcut'),
						'order' => 'curr_shortcut',
						'td' => '$curr_shortcut$',
					);

$Results->cols[] = array(
						'th' => T_('Name'),
						'order' => 'curr_name',
						'td' => '$curr_name$',
					);
					
if( $current_User->check_perm( 'options', 'edit', false ) )
{ // We have permission to modify:
	$Results->cols[] = array(
							'th' => T_('Actions'),
							'th_class' => 'shrinkwrap',
							'td_class' => 'shrinkwrap',
							'td' => action_icon( T_('Edit this currency...'), 'edit',
	                        '%regenerate_url( \'action\', \'curr_ID=$curr_ID$&amp;action=edit\')%' )
	                    .action_icon( T_('Duplicate this currency...'), 'copy',
	                        '%regenerate_url( \'action\', \'curr_ID=$curr_ID$&amp;action=new\')%' )
	                    .action_icon( T_('Delete this currency!'), 'delete',
	                        '%regenerate_url( \'action\', \'curr_ID=$curr_ID$&amp;action=delete\')%' ),
						);

  $Results->global_icon( T_('Create a new currency ...'), 'new', regenerate_url( 'action', 'action=new'), T_('New currency').' &raquo;', 3, 4  );
}

$Results->display();

?>