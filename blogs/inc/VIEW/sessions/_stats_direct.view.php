<?php
/**
 * This file implements the UI view for the Direct accesses stats.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://cvs.sourceforge.net/viewcvs.py/evocms/)
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
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $blog, $admin_url, $rsc_url;

?>
<h2><?php echo T_('Direct browser hits') ?></h2>
<p><?php echo T_('These are browser hits from people who came to this blog system by direct access (either by typing the URL directly, or using a bookmark. Invalid (too short) referers are also listed here.)') ?></p>
<?php
// Create result set:
$Results = & new Results( "
	SELECT hit_ID, hit_datetime, hit_blog_ID, hit_uri, hit_remote_addr, blog_shortname
		FROM T_hitlog INNER JOIN T_useragents ON hit_agnt_ID = agnt_ID
				 LEFT JOIN T_blogs ON hit_blog_ID = blog_ID
	 WHERE hit_referer_type = 'direct'
		 AND agnt_type = 'browser'"
		.( empty($blog) ? '' : "AND hit_blog_ID = $blog "), 'lstref_', 'D' );

$Results->title = T_('Direct browser hits');

// datetime:
$Results->cols[] = array(
		'th' => T_('Date Time'),
		'order' => 'hit_datetime',
		'td_class' => 'timestamp',
		'td' => '%mysql2localedatetime_spans( \'$hit_datetime$\' )%',
	);

// Referer:
if( $current_User->check_perm( 'stats', 'edit' ) )
{
	$Results->cols[] = array(
			'th' => /* TRANS: Abbrev. for Delete (stats) */ T_('Del'),
			'td' => ' <a href="%regenerate_url( \'action\', \'action=delete&amp;hit_ID=$hit_ID$\')%" title="'
						 .T_('Delete this hit!').'">'.get_icon('delete').'</a>',
		);
}

// Target Blog:
if( empty($blog) )
{
	$Results->cols[] = array(
			'th' => T_('Target Blog'),
			'order' => 'hit_blog_ID',
			'td' => '$blog_shortname$',
		);
}

// Requested URI (linked to blog's baseurlroot+URI):
$Results->cols[] = array(
		'th' => T_('Requested URI'),
		'order' => 'hit_uri',
		'td' => '%stats_format_req_URI( #hit_blog_ID#, #hit_uri# )%',
	);

// Remote address (IP):
$Results->cols[] = array(
		'th' => '<span title="'.T_('Remote address').'">'.T_('IP').'</span>',
		'order' => 'hit_remote_addr',
		'td' => '% $GLOBALS[\'Plugins\']->get_trigger_event( \'FilterIpAddress\', $tmp_params = array(\'format\'=>\'htmlbody\', \'data\'=>\'$hit_remote_addr$\') ) %',
	);

// Display results:
$Results->display();

/*
 * $Log$
 * Revision 1.2  2006/08/24 21:41:13  fplanque
 * enhanced stats
 *
 * Revision 1.1  2006/07/12 18:07:06  fplanque
 * splitted stats into different views
 *
 */
?>