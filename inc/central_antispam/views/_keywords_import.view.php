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
$keywords_SQL->SELECT( 'SQL_NO_CACHE COUNT( askw_ID )' );
$keywords_SQL->FROM( 'T_antispam__keyword' );
$keywords_SQL->WHERE( 'askw_string NOT IN ( SELECT cakw_keyword FROM T_centralantispam__keyword )' );
$keywords_count = intval( $DB->get_var( $keywords_SQL->get(), 0, NULL, $keywords_SQL->title ) );

$Form = new Form();

$Form->begin_form( 'fform' );

$Form->add_crumb( 'cakeywordsimport' );
$Form->hidden_ctrl();
$Form->hidden( 'confirm', 1 );

$Form->begin_fieldset( $central_antispam_Module->T_('Import keywords') );

echo '<p>';
if( $keywords_count > 0 )
{
	printf( $central_antispam_Module->T_('%d new keywords will be imported as local reports'), $keywords_count );
}
else
{
	echo $central_antispam_Module->T_('No new keywords to import');
}
echo '</p>';

$Form->end_fieldset();

if( $keywords_count > 0 )
{
	$Form->end_form( array( array( 'submit', 'actionArray[import]', $central_antispam_Module->T_('Confirm Import'), 'SaveButton' ) ) );
}
else
{
	$Form->end_form();
}
?>