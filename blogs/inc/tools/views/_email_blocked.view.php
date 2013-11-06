<?php
/**
 * This file implements the UI view for Tools > Email > Addresses
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2013 by Francois Planque - {@link http://fplanque.com/}
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
 * {@internal Open Source relicensing agreement:
 * }}
 *
 * @package admin
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $blog, $admin_url, $UserSettings, $email, $statuses;

param( 'email', 'string', '', true );
param( 'statuses', 'array', array( 'warning', 'suspicious3' ), true );

// Create result set:

$SQL = new SQL();
$SQL->SELECT( 'SQL_NO_CACHE emblk_ID, emblk_address, emblk_status, emblk_last_sent_ts, emblk_sent_count, emblk_sent_last_returnerror, emblk_last_error_ts, 
( emblk_prmerror_count + emblk_tmperror_count + emblk_spamerror_count + emblk_othererror_count ) AS emblk_all_count,
emblk_prmerror_count, emblk_tmperror_count, emblk_spamerror_count, emblk_othererror_count, 
COUNT( user_ID ) AS users_count' );
$SQL->FROM( 'T_email__blocked' );
$SQL->FROM_add( 'LEFT JOIN T_users ON user_email = emblk_address' );
$SQL->GROUP_BY( 'emblk_ID' );

$CountSQL = new SQL();
$CountSQL->SELECT( 'SQL_NO_CACHE COUNT(emblk_ID)' );
$CountSQL->FROM( 'T_email__blocked' );

if( !empty( $email ) )
{	// Filter by email
	$SQL->WHERE_and( 'emblk_address LIKE '.$DB->quote( $email ) );
	$CountSQL->WHERE_and( 'emblk_address LIKE '.$DB->quote( $email ) );
}
if( !empty( $statuses ) )
{	// Filter by statuses
	$SQL->WHERE_and( 'emblk_status IN ('.$DB->quote( $statuses ).')' );
	$CountSQL->WHERE_and( 'emblk_status IN ('.$DB->quote( $statuses ).')' );
}

$Results = new Results( $SQL->get(), 'emblk_', '---D', $UserSettings->get( 'results_per_page' ), $CountSQL->get() );

$Results->title = T_('Email addresses');

$Results->global_icon( T_('Create a new email address...'), 'new', $admin_url.'?ctrl=email&amp;tab=blocked&amp;action=blocked_new', T_('Add an email address').' &raquo;', 3, 4 );

/**
 * Callback to add filters on top of the result set
 *
 * @param Form
 */
function filter_email_blocked( & $Form )
{
	$Form->text_input( 'email', get_param( 'email' ), 40, T_('Email') );

	$statuses = emblk_get_status_titles();
	foreach( $statuses as $status_value => $status_title )
	{	// Display the checkboxes to filter by status
		$Form->checkbox( 'statuses[]', in_array( $status_value, get_param( 'statuses' ) ), $status_title, '', '', $status_value );
	}
}
$Results->filter_area = array(
	'callback' => 'filter_email_blocked',
	'presets' => array(
		'all'       => array( T_('All'), $admin_url.'?ctrl=email&amp;tab=blocked&amp;statuses[]=unknown&amp;statuses[]=warning&amp;statuses[]=suspicious1&amp;statuses[]=suspicious2&amp;statuses[]=suspicious3&amp;statuses[]=prmerror&amp;statuses[]=spammer'),
		'errors'    => array( T_('Errors'), $admin_url.'?ctrl=email&amp;tab=blocked&amp;statuses[]=warning&amp;statuses[]=suspicious1&amp;statuses[]=suspicious2&amp;statuses[]=suspicious3&amp;statuses[]=prmerror&amp;statuses[]=spammer'),
		'attention' => array( T_('Need Attention'), $admin_url.'?ctrl=email&amp;tab=blocked&amp;statuses[]=warning&amp;statuses[]=suspicious3'),
		)
	);

/**
 * Decode the special symbols
 * It used to avoid an error, because a symbol '%' is special symbol of class Result to parse the functions
 *
 * @param string Url
 * @param string Url
 */
function url_decode_special_symbols( $url )
{
	return str_replace(
			array( '%5B', '%5D' ),
			array( '[',   ']' ),
			$url
		);
}
$Results->cols[] = array(
		'th' => T_('ID'),
		'order' => 'emblk_ID',
		'th_class' => 'shrinkwrap',
		'td_class' => 'right',
		'td' => '$emblk_ID$',
	);

$Results->cols[] = array(
		'th' => T_('Address'),
		'order' => 'emblk_address',
		'td' => '<a href="'.url_decode_special_symbols( regenerate_url( 'email,action,emblk_ID', 'email=$emblk_address$' ) ).'">$emblk_address$</a>',
		'th_class' => 'shrinkwrap',
	);

$Results->cols[] = array(
		'th' => T_('Status'),
		'order' => 'emblk_status',
		'td' => '$emblk_status$',
		'td' => /* Check permission: */$current_User->check_perm( 'emails', 'edit' ) ?
			/* Current user can edit emails */'<a href="#" rel="$emblk_status$">%emblk_get_status_title( #emblk_status# )%</a>' :
			/* No edit, only view the status */'%emblk_get_status_title( #emblk_status# )%',
		'th_class' => 'shrinkwrap',
		'td_class' => 'shrinkwrap emblk_status_edit',
		'extra' => array ( 'style' => 'background-color: %emblk_get_status_color( "#emblk_status#" )%;', 'format_to_output' => false )
	);

$Results->cols[] = array(
		'th_group' => T_('Send messages'),
		'th' => T_('Last sent date'),
		'order' => 'emblk_last_sent_ts',
		'default_dir' => 'D',
		'td_class' => 'timestamp',
		'td' => '%mysql2localedatetime_spans( #emblk_last_sent_ts#, "M-d" )%',
	);

$Results->cols[] = array(
		'th_group' => T_('Send messages'),
		'th' => T_('Sent count'),
		'order' => 'emblk_sent_count',
		'default_dir' => 'D',
		'td' => '<a href="'.$admin_url.'?ctrl=email&amp;filter=new&amp;email=$emblk_address$">$emblk_sent_count$</a>',
		'td_class' => 'right'
	);

$Results->cols[] = array(
		'th_group' => T_('Send messages'),
		'th' => T_('Since last error'),
		'order' => 'emblk_sent_last_returnerror',
		'default_dir' => 'D',
		'td' => '$emblk_sent_last_returnerror$',
		'td_class' => 'right'
	);

$Results->cols[] = array(
		'th_group' => T_('Returned error messages'),
		'th' => T_('Last error date'),
		'order' => 'emblk_last_error_ts',
		'default_dir' => 'D',
		'td_class' => 'timestamp',
		'td' => '%mysql2localedatetime_spans( #emblk_last_error_ts#, "M-d" )%',
	);

$Results->cols[] = array(
		'th_group' => T_('Returned error messages'),
		'th' => T_('Count'),
		'order' => 'emblk_all_count',
		'default_dir' => 'D',
		'td' => '$emblk_all_count$',
		'td_class' => 'right'
	);

$Results->cols[] = array(
		'th_group' => T_('Returned error messages'),
		'th' => T_('Permanent errors'),
		'order' => 'emblk_prmerror_count',
		'default_dir' => 'D',
		'td' => '$emblk_prmerror_count$',
		'td_class' => 'right'
	);

$Results->cols[] = array(
		'th_group' => T_('Returned error messages'),
		'th' => T_('Temporary errors'),
		'order' => 'emblk_tmperror_count',
		'default_dir' => 'D',
		'td' => '$emblk_tmperror_count$',
		'td_class' => 'right'
	);

$Results->cols[] = array(
		'th_group' => T_('Returned error messages'),
		'th' => T_('Spam errors'),
		'order' => 'emblk_spamerror_count',
		'default_dir' => 'D',
		'td' => '$emblk_spamerror_count$',
		'td_class' => 'right'
	);

$Results->cols[] = array(
		'th_group' => T_('Returned error messages'),
		'th' => T_('Other errors'),
		'order' => 'emblk_othererror_count',
		'default_dir' => 'D',
		'td' => '$emblk_othererror_count$',
		'td_class' => 'right'
	);

$Results->cols[] = array(
		'th' => T_('Users'),
		'order' => 'users_count',
		'default_dir' => 'D',
		'td' => '<a href="'.$admin_url.'?ctrl=users&amp;filter=new&amp;keywords=$emblk_address$" title="'.format_to_output( T_('Go to users list with this email address'), 'htmlattr' ).'">$users_count$</a>',
		'td_class' => 'right'
	);

$Results->cols[] = array(
		'th' => T_('Actions'),
		'th_class' => 'shrinkwrap',
		'td_class' => 'shrinkwrap',
		'td' => action_icon( T_('Filter the returned emails by this email address...'), 'magnifier', $admin_url.'?ctrl=email&amp;tab=return&amp;email=$emblk_address$' )
			.action_icon( T_('Edit this email address...'), 'properties', $admin_url.'?ctrl=email&amp;tab=blocked&amp;emblk_ID=$emblk_ID$' )
			.action_icon( T_('Delete this email address!'), 'delete', url_decode_special_symbols( regenerate_url( 'emblk_ID,action', 'emblk_ID=$emblk_ID$&amp;action=blocked_delete&amp;'.url_crumb('email_blocked') ) ) )
	);

// Display results:
$Results->display();

if( $current_User->check_perm( 'emails', 'edit' ) )
{	// Check permission to edit emails:
?>
<script type="text/javascript">
jQuery( document ).ready( function()
{
	jQuery( '.emblk_status_edit' ).editable( htsrv_url + 'async.php?action=emblk_status_edit&<?php echo url_crumb( 'emblkstatus' )?>',
	{
	data : function( value, settings )
		{
			value = ajax_debug_clear( value );
			var re =  /rel="(.*)"/;
			var result = value.match(re);
			return {<?php
$status_titles = emblk_get_status_titles();
foreach( $status_titles as $status_value => $status_title )
{
	echo '	\''.$status_value.'\': \''.$status_title.'\',';
}
?>
				'selected' : result[1]
			}
		},
	type     : 'select',
	name     : 'new_status',
	tooltip  : 'Click to edit',
	event    : 'click',
	callback : function( settings, original )
		{
			//evoFadeSuccess( this );
			jQuery( this ).html( ajax_debug_clear( settings ) );
			var link = jQuery( this ).find( 'a' );
			jQuery( this ).css( 'background-color', link.attr( 'color' ) );
			link.removeAttr( 'color' );
		},
	onsubmit: function( settings, original ) {
	},
	submitdata: function( value, settings ) {
			var id = jQuery( ':first', jQuery( this ).parent() ).text();
			return { emblk_ID: id }
		},
	onerror : function( settings, original, xhr ) {
			evoFadeFailure( original );
		}
	} );
} );
</script>
<?php
}

/*
 * $Log$
 * Revision 1.2  2013/11/06 08:04:55  efy-asimo
 * Update to version 5.0.1-alpha-5
 *
 */
?>