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

global $central_antispam_Module, $DB;

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

$Form = new Form();

$Form->begin_form( 'fform' );

$Form->add_crumb( 'cakeywordsimport' );
$Form->hidden_ctrl();
$Form->hidden( 'confirm', 1 );

$Form->begin_fieldset( T_('Import keywords') );

if( count( $keywords ) > 0 )
{	// Suggest to import keywords only if at least one is found:
	$keywords_options = array();
	foreach( $keywords as $keywords_status => $keywords_count )
	{
		switch( $keywords_status )
		{
			case 'central':
				$keywords_date_start = '[2014-01-01] [00:00:00]';
				break;
			case 'reported':
				$keywords_date_start = '[2015-01-01] [00:00:00]';
				break;
			case 'local':
			default:
				$keywords_date_start = '[2016-01-01] [00:00:00]';
				break;
		}
		$keywords_options[] = array( 'import_keywords[]', $keywords_status, sprintf( T_('Import %s keywords with status "%s" and set report dates starting at %s with 1 second increments'), $keywords_count, $keywords_status, $keywords_date_start ), 1 );
	}
	$Form->checklist( $keywords_options, '', '', false, false, array( 'wide' => true ) );
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