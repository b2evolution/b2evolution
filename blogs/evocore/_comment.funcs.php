<?php
/**
 * This file implements Comment handling functions.
  *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 * {@internal
 * b2evolution is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * b2evolution is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with b2evolution; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * }}
 *
 * {@internal
 * Daniel HAHLER grants François PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * @todo implement CommentCache based on LinkCache
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author cafelog (team)
 * @author blueyed: Daniel HAHLER.
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );

/**
 * Includes:
 */
require_once dirname(__FILE__).'/_comment.class.php';

/**
 * Generic comments/trackbacks/pingbacks counting
 *
 * @todo check this in a multiblog page...
 *
 * @param
 * @param string what to count
 */
function generic_ctp_number( $post_id, $mode = 'comments' )
{
	global $DB, $debug, $postdata, $cache_ctp_number, $preview;
	if( $preview )
	{ // we are in preview mode, no comments yet!
		return 0;
	}

	/*
	 * Make sure cache is loaded for current display list:
	 */
	if( !isset($cache_ctp_number) )
	{
		global $postIDlist, $postIDarray;
		// if( $debug ) echo "LOADING generic_ctp_number CACHE for posts: $postIDlist<br />";
		foreach( $postIDarray as $tmp_post_id)
		{		// Initializes each post to nocount!
				$cache_ctp_number[$tmp_post_id] = array( 'comments' => 0, 'trackbacks' => 0, 'pingbacks' => 0, 'ctp' => 0);
		}
		$query = "SELECT comment_post_ID, comment_type, COUNT(*) AS type_count
							FROM T_comments
							WHERE comment_post_ID IN ($postIDlist)
							GROUP BY comment_post_ID, comment_type";

		foreach( $DB->get_results( $query ) as $row )
		{
			switch( $row->comment_type )
			{
				case 'comment';
					$cache_ctp_number[$row->comment_post_ID]['comments'] = $row->type_count;
					break;

				case 'trackback';
					$cache_ctp_number[$row->comment_post_ID]['trackbacks'] = $row->type_count;
					break;

				case 'pingback';
					$cache_ctp_number[$row->comment_post_ID]['pingbacks'] = $row->type_count;
					break;
			}
			$cache_ctp_number[$row->comment_post_ID]['ctp'] += $row->type_count;
		}
	}
	/*	else
	{
		echo "cache set";
	}*/


	if( !isset($cache_ctp_number[$post_id]) )
	{ // this should be extremely rare...
		// echo "CACHE not set for $post_id";
		$post_id = intval($post_id);
		$query = "SELECT comment_post_ID, comment_type, COUNT(*) AS type_count
							FROM T_comments
							WHERE comment_post_ID = $post_id
							GROUP BY comment_post_ID, comment_type";

		foreach( $DB->get_results( $query ) as $row )
		{
			switch( $row->comment_type )
			{
				case 'comment';
					$cache_ctp_number[$row->comment_post_ID]['comments'] = $row->type_count;
					break;

				case 'trackback';
					$cache_ctp_number[$row->comment_post_ID]['trackbacks'] = $row->type_count;
					break;

				case 'pingback';
					$cache_ctp_number[$row->comment_post_ID]['pingbacks'] = $row->type_count;
					break;
			}
			$cache_ctp_number[$row->comment_post_ID]['ctp'] += $row->type_count;
		}
	}
	else
	{
		$ctp_number = $cache_ctp_number[$post_id];
	}

	if( ($mode != 'comments') && ($mode != 'trackbacks') && ($mode != 'pingbacks') )
	{
		$mode = 'ctp';
	}

	return $ctp_number[$mode];
}


/**
 * Get a Comment by ID. Exits if the requested comment does not exist!
 *
 * {@internal Comment_get_by_ID(-)}}
 *
 * @param integer
 * @return Comment
 */
function Comment_get_by_ID( $comment_ID )
{
	global $DB, $cache_Comments;

	if( empty($cache_Comments[$comment_ID]) )
	{ // Load this entry into cache:
		$query = "SELECT *
							FROM T_comments
							WHERE comment_ID = $comment_ID";
		if( $row = $DB->get_row( $query, ARRAY_A ) )
		{
			$cache_Comments[$comment_ID] = new Comment( $row ); // COPY !
		}
	}

	if( empty( $cache_Comments[ $comment_ID ] ) ) die('Requested comment does not exist!');

	return $cache_Comments[ $comment_ID ];
}


/*
 * last_comments_title(-)
 *
 * @movedTo _obsolete092.php
 */


/***** Comment tags *****/

/**
 * comments_number(-)
 *
 * @deprecated deprecated by {@link Item::feedback_link()}, used in _edit_showposts.php
 */
function comments_number( $zero='#', $one='#', $more='#' )
{
	if( $zero == '#' ) $zero = T_('Leave a comment');
	if( $one == '#' ) $one = T_('1 comment');
	if( $more == '#' ) $more = T_('%d comments');

	// original hack by dodo@regretless.com
	global $id, $postdata, $c, $cache_commentsnumber;
	$number = generic_ctp_number($id, 'comments');
	if ($number == 0) {
		$blah = $zero;
	} elseif ($number == 1) {
		$blah = $one;
	} elseif ($number  > 1) {
		$n = $number;
		$more = str_replace('%d', $n, $more);
		$blah = $more;
	}
	echo $blah;
}


/**
 * {@internal comments_link(-)}}
 *
 * Displays link to comments page
 *
 * @deprecated deprecated by {@link Item::feedback_link()}, used in rss2.php
 */
function comments_link($file='', $tb=0, $pb=0 )
{
	global $id;
	if( ($file == '') || ($file == '/')	)
		$file = get_bloginfo('blogurl');
	echo url_add_param( $file, 'p='. $id. '&amp;c=1' );
	if( $tb == 1 )
	{ // include trackback // fplanque: added
		echo '&amp;tb=1';
	}
	if( $pb == 1 )
	{ // include pingback // fplanque: added
		echo '&amp;pb=1';
	}
	echo '#comments';
}


/**
 * This will include the javascript that is required to open comments,
 * trackback and pingback in popup windows.
 *
 * You should put this tag before the </head> tag in your template.
 *
 * {@internal comments_popup_script(-)}}
 *
 * @param integer width of window or false
 * @param integer height of window or false
 * @param boolean do you want a scrollbar
 * @param boolean do you want a status bar
 * @param boolean do you want the windows to be resizable
 */
function comments_popup_script( $width = 600, $height = 450,
																$scrollbars = true, $status = true, $resizable = true )
{
	global $b2commentsjavascript;

	$b2commentsjavascript = true;
	$properties = array();
	if( $width ) $properties[] = 'width='.$width;
	if( $height ) $properties[] = 'height='.$height;
	$properties[] = 'scrollbars='.intval( $scrollbars );
	$properties[] = 'status='.intval( $status );
	$properties[] = 'resizable='.intval( $resizable );
	$properties = implode( ',', $properties );
	?>

	<script language="javascript" type="text/javascript">
		<!--
		function b2open( url )
		{
			window.open( url, '_blank', '<?php echo $properties; ?>');
		}
		//-->
	</script>

	<?php
}


/**
 * comment_author_url(-)
 *
 * @deprecated deprecated by {@link Comment::author_url()}
 */
function comment_author_url($echo=true)
{
	global $commentdata;
	$url = trim($commentdata['comment_author_url']);
	$url = (!stristr($url, '://')) ? 'http://'.$url : $url;
	// convert & into &amp;
	$url = preg_replace('#&([^amp\;])#is', '&amp;$1', $url);
	if ($url != 'http://')
	{
		if ($echo)
			echo $url;
		else
			return $url;
	}
}


/**
 * comment_author_url_basedomain(-)
 *
 * @uses comment_author_url()
 * @deprecated
 */
function comment_author_url_basedomain( $disp = true )
{
	global $commentdata;
	$url = comment_author_url(false);
	$baseDomain = getBaseDomain( $url );
	if( $disp )
		echo $baseDomain;
	else
		return $baseDomain;
}

/*
 * $Log$
 * Revision 1.10  2005/03/14 20:22:19  fplanque
 * refactoring, some cacheing optimization
 *
 * Revision 1.9  2005/03/09 14:54:26  fplanque
 * refactored *_title() galore to requested_title()
 *
 * Revision 1.8  2005/02/28 09:06:32  blueyed
 * removed constants for DB config (allows to override it from _config_TEST.php), introduced EVO_CONFIG_LOADED
 *
 * Revision 1.7  2005/02/16 15:48:06  fplanque
 * merged with work app :p
 *
 * Revision 1.6  2005/02/15 22:05:06  blueyed
 * Started moving obsolete functions to _obsolete092.php..
 *
 * Revision 1.5  2005/02/08 04:45:02  blueyed
 * improved $DB get_results() handling
 *
 * Revision 1.4  2004/12/17 20:41:13  fplanque
 * cleanup
 *
 * Revision 1.3  2004/12/15 20:50:34  fplanque
 * heavy refactoring
 * suppressed $use_cache and $sleep_after_edit
 * code cleanup
 *
 * Revision 1.2  2004/10/14 18:31:25  blueyed
 * granting copyright
 *
 * Revision 1.1  2004/10/13 22:46:32  fplanque
 * renamed [b2]evocore/*
 *
 * Revision 1.42  2004/10/12 17:22:29  fplanque
 * Edited code documentation.
 *
 */
?>