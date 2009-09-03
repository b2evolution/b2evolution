<?php

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $dispatcher;

// Create result set:
$Results = & new Results(
							'SELECT * FROM T_country', 'ctry_' );
$Results->Cache = & get_Cache( 'CountryCache' );
$Results->title = T_('Countries list');

if( $current_User->check_perm( 'options', 'edit', false ) )
{ // We have permission to modify:
	$Results->cols[] = array(
							'th' => T_('Code'),
							'order' => 'ctry_code',
							'td' => '<strong><a href="'.$dispatcher.'?ctrl=countries&amp;ctry_ID=$ctry_ID$&amp;action=edit" title="'.
											T_('Edit this country...').'">$ctry_code$</a></strong>',
						);
}
else
{	// View only:
	$Results->cols[] = array(
							'th' => T_('Code'),
							'order' => 'ctry_code',
							'td' => '<strong>$ctry_code$</strong>',
						);

}

$Results->cols[] = array(
						'th' => T_('Name'),
						'order' => 'ctry_name',
						'td' => '$ctry_name$',
					);

if( $current_User->check_perm( 'options', 'edit', false ) )
{ // We have permission to modify:
	$Results->cols[] = array(
							'th' => T_('Actions'),
							'th_class' => 'shrinkwrap',
							'td_class' => 'shrinkwrap',
							'td' => action_icon( T_('Edit this country...'), 'edit',
	                        '%regenerate_url( \'action\', \'ctry_ID=$ctry_ID$&amp;action=edit\')%' )
	                    .action_icon( T_('Duplicate this country...'), 'copy',
	                        '%regenerate_url( \'action\', \'ctry_ID=$ctry_ID$&amp;action=new\')%' )
	                    .action_icon( T_('Delete this country!'), 'delete',
	                        '%regenerate_url( \'action\', \'ctry_ID=$ctry_ID$&amp;action=delete\')%' ),
						);

  $Results->global_icon( T_('Create a new country ...'), 'new', regenerate_url( 'action', 'action=new'), T_('New country').' &raquo;', 3, 4  );
}

$Results->display();

?>