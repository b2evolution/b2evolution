<?php
/**
 * This file implements the UI view for the referering searches stats.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2009 by Francois PLANQUE - {@link http://fplanque.net/}
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

/**
 * View funcs
 */
require_once dirname(__FILE__).'/_stats_view.funcs.php';


global $blog, $admin_url, $rsc_url;

// Create result set:
$Results = & new Results( "
	 	 SELECT hit_ID, hit_datetime, hit_referer, dom_name, hit_blog_ID, hit_uri, hit_remote_addr, blog_shortname,
	 	 				keyp_phrase, hit_serprank
		 	 FROM T_hitlog INNER JOIN T_basedomains ON dom_ID = hit_referer_dom_ID
					  INNER JOIN T_useragents ON hit_agnt_ID = agnt_ID
					  LEFT JOIN T_track__keyphrase ON hit_keyphrase_keyp_ID = keyp_ID
					  LEFT JOIN T_blogs ON hit_blog_ID = blog_ID
		  WHERE hit_referer_type = 'search'
			 			AND agnt_type = 'browser'"
		.( empty($blog) ? '' : "AND hit_blog_ID = $blog " ), 'lstsrch', 'D' );

$Results->title = T_('Search browser hits');

// datetime:
$Results->cols[0] = array(
		'th' => T_('Date Time'),
		'order' => 'hit_ID', // This field is index, much faster than actually sorting on the datetime!
		'td_class' => 'timestamp',
		'td' => '%mysql2localedatetime_spans( \'$hit_datetime$\' )%',
	);

// Referer:
$Results->cols[1] = array(
		'th' => T_('Referer'),
		'order' => 'dom_name',
		'td_class' => 'nowrap',
	);
if( $current_User->check_perm( 'stats', 'edit' ) )
{
	$Results->cols[1]['td'] = '<a href="%regenerate_url( \'action\', \'action=delete&amp;hit_ID=$hit_ID$\')%" title="'
			.T_('Delete this hit!').'">'.get_icon('delete').'</a> '
			.'<a href="$hit_referer$" target="_blank">$dom_name$</a>';
}
else
{
	$Results->cols[1]['td'] = '<a href="$hit_referer$">$dom_name$</a>';
}

// Keywords:
$Results->cols[] = array(
		'th' => T_('Search keywords'),
		'order' => 'keyp_phrase',
		'td' => '%stats_search_keywords( #keyp_phrase#, 45 )%',
	);

// Serp Rank:
$Results->cols[] = array(
		'th' => T_('SR'),
		'order' => 'hit_serprank',
		'td_class' => 'center',
		'td' => '$hit_serprank$',
	);

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
		'th' => T_('Remote IP'),
		'order' => 'hit_remote_addr',
		'td' => '% $GLOBALS[\'Plugins\']->get_trigger_event( \'FilterIpAddress\', $tmp_params = array(\'format\'=>\'htmlbody\', \'data\'=>\'$hit_remote_addr$\') ) %',
	);

// Display results:
$Results->display();

echo '<p class="notes">'.T_('These are hits from people who came to this blog system through a search engine. (Search engines must be listed in /conf/_stats.php)').'</p>';

/*
 * $Log$
 * Revision 1.11  2009/07/09 00:11:18  fplanque
 * minor
 *
 * Revision 1.10  2009/07/08 01:45:48  sam2kb
 * Added param $length to stats_search_keywords()
 * Changed keywords length for better accessibility on low resolution screens
 *
 * Revision 1.9  2009/07/06 06:51:03  sam2kb
 * Added target="_blank" on referer URLs
 *
 * Revision 1.8  2009/05/10 00:28:51  fplanque
 * serp rank logging
 *
 * Revision 1.7  2009/03/08 23:57:45  fplanque
 * 2009
 *
 * Revision 1.6  2008/05/26 19:30:38  fplanque
 * enhanced analytics
 *
 * Revision 1.5  2008/05/10 22:59:10  fplanque
 * keyphrase logging
 *
 * Revision 1.4  2008/02/19 11:11:18  fplanque
 * no message
 *
 * Revision 1.3  2008/02/14 02:19:52  fplanque
 * cleaned up stats
 *
 * Revision 1.2  2008/01/21 09:35:34  fplanque
 * (c) 2008
 *
 * Revision 1.1  2007/06/25 11:01:05  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.7  2007/04/26 00:11:13  fplanque
 * (c) 2007
 *
 * Revision 1.6  2007/03/20 09:53:26  fplanque
 * Letting boggers view their own stats.
 * + Letthing admins view the aggregate by default.
 *
 * Revision 1.5  2006/11/26 01:42:10  fplanque
 * doc
 */
?>