<?php
/**
 * This file display the keywords import
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $central_antispam_Module, $DB, $localtimenow;

$keywords_SQL = new SQL( 'Get a count of keywords that can be imported as local reports' );
$keywords_SQL->SELECT( 'askw_source, COUNT( askw_ID )' );
$keywords_SQL->SELECT_add( ', CASE
		WHEN askw_source = "central"  THEN 1
		WHEN askw_source = "reported" THEN 2
		WHEN askw_source = "local"    THEN 3
	END AS source_order' );
$keywords_SQL->FROM( 'T_antispam__keyword' );
$keywords_SQL->WHERE( 'askw_string NOT IN ( SELECT cakw_keyword FROM T_centralantispam__keyword )' );
$keywords_SQL->ORDER_BY( 'source_order' );
$keywords_SQL->GROUP_BY( 'askw_source' );
$keywords = $DB->get_assoc( $keywords_SQL->get(), $keywords_SQL->title );

// Check if at least one keyword exists in Central Antispam:
$ca_keywords_count = $DB->get_var( 'SELECT cakw_ID FROM T_centralantispam__keyword LIMIT 1', 0, NULL,
	'Check if at least one keyword exists in Central Antispam' );

$Form = new Form();

$Form->begin_form( 'fform' );

$Form->add_crumb( 'cakeywordsimport' );
$Form->hidden_ctrl();
$Form->hidden( 'confirm', 1 );

$Form->begin_fieldset( T_('Import keywords') );

if( count( $keywords ) > 0 )
{	// Suggest to import keywords only if at least one is found:
	$default_date_start = array(
			'central'  => $ca_keywords_count ? date( 'Y-m-d H:i:s', $localtimenow ) : '2014-01-01 00:00:00',
			'reported' => $ca_keywords_count ? date( 'Y-m-d H:i:s', $localtimenow ) : '2015-01-01 00:00:00',
			'local'    => $ca_keywords_count ? date( 'Y-m-d H:i:s', $localtimenow ) : '2016-01-01 00:00:00',
		);
	foreach( $keywords as $keywords_status => $keywords_count )
	{
		$Form->switch_layout( 'compact_form' );
		$Form->output = false;
		$keywords_date_start = $Form->date_input( 'date_start_'.$keywords_status, $default_date_start[ $keywords_status ], '' )
			.' '.$Form->time_input( 'time_start_'.$keywords_status, $default_date_start[ $keywords_status ], '', array( 'note' => '' ) );
		$Form->output = true;
		$Form->switch_layout( NULL );

		$Form->begin_line( '', NULL, '', array( 'wide' => true ) );
			$Form->checkbox_input( 'import_keywords[]', ( $keywords_status != 'local' ), '', array( 'value' => $keywords_status ) );
			printf( T_('Import %s keywords with status "%s" and set report dates starting at %s with 1 second increments'), $keywords_count, $keywords_status, $keywords_date_start );
		$Form->end_line();
	}
}
else
{
	echo '<p>'.T_('No new keywords to import').'</p>';
}

$Form->end_fieldset();

if( count( $keywords ) )
{
	$Form->end_form( array( array( 'submit', 'actionArray[import]', T_('Confirm Import'), 'SaveButton' ) ) );
}
else
{
	$Form->end_form();
}
?>