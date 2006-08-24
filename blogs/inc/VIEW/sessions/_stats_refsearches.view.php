<?php
/**
 * This file implements the UI view for the referering searches stats.
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

echo '<h2>'.T_('Search browser hits').':</h2>';

echo '<p>'.T_('These are hits from people who came to this blog system through a search engine. (Search engines must be listed in /conf/_stats.php)').'</p>';

// Create result set:
$Results = & new Results( "
	 	 SELECT hit_ID, hit_datetime, hit_referer, dom_name, hit_blog_ID, hit_uri, hit_remote_addr, blog_shortname
		 	 FROM T_hitlog INNER JOIN T_basedomains ON dom_ID = hit_referer_dom_ID
					  INNER JOIN T_useragents ON hit_agnt_ID = agnt_ID
					  LEFT JOIN T_blogs ON hit_blog_ID = blog_ID
		  WHERE hit_referer_type = 'search'
			 			AND agnt_type = 'browser'"
		.( empty($blog) ? '' : "AND hit_blog_ID = $blog " ), 'lstsrch', 'D' );

$Results->title = T_('Search browser hits');

// datetime:
$Results->cols[0] = array(
		'th' => T_('Date Time'),
		'order' => 'hit_datetime',
		'td_class' => 'timestamp',
		'td' => '%mysql2localedatetime_spans( \'$hit_datetime$\' )%',
	);

// Referer:
$Results->cols[1] = array(
		'th' => T_('Referer'),
		'order' => 'dom_name',
	);
if( $current_User->check_perm( 'stats', 'edit' ) )
{
	$Results->cols[1]['td'] = '<a href="%regenerate_url( \'action\', \'action=delete&amp;hit_ID=$hit_ID$\')%" title="'
			.T_('Delete this hit!').'">'.get_icon('delete').'</a> '
			.'<a href="$hit_referer$">$dom_name$</a>';
}
else
{
	$Results->cols[1]['td'] = '<a href="$hit_referer$">$dom_name$</a>';
}

// Keywords:
$Results->cols[] = array(
		'th' => T_('Search keywords'),
		'td' => '%stats_search_keywords( #hit_referer# )%',
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
		'th' => '<span title="'.T_('Remote address').'">'.T_('IP').'</span>',
		'order' => 'hit_remote_addr',
		'td' => '% $GLOBALS[\'Plugins\']->get_trigger_event( \'FilterIpAddress\', $tmp_params = array(\'format\'=>\'htmlbody\', \'data\'=>\'$hit_remote_addr$\') ) %',
	);

// Display results:
$Results->display();



// TOP REFERING SEARCH ENGINES
?>

<h3><?php echo T_('Top refering search engines') ?>:</h3>

<?php
global $res_stats, $row_stats;
refererList(20,'global',0,0,"'search'",'dom_name',$blog,true);
if( count( $res_stats ) )
{
	?>
	<table class="grouped" cellspacing="0">
		<?php
		$count = 0;
		foreach( $res_stats as $row_stats )
		{
			?>
			<tr <?php if( $count%2 == 1 ) echo 'class="odd"'; ?>>
				<td class="firstcol"><a href="<?php stats_referer() ?>"><?php stats_basedomain() ?></a></td>
				<td class="right"><?php stats_hit_count() ?></td>
				<td class="right"><?php stats_hit_percent() ?></td>
			</tr>
		<?php
		$count++;
		}
		?>
	</table>
<?php
}



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