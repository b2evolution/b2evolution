<?php
/**
 * This file implements functions that got obsolete with version 0.9.2.
 *
 * For performance reasons you should delete (or rename) this file, but if you use some
 * of these functions in your skin or hack you'll have to leave it for obvious compatibility
 * reasons.
 * Of course, this file will not be (automatically) included at some point, so please
 * upgrade your skins and hacks.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004 by Vegar BERG GULDAL - {@link http://funky-m.com/}
 * Parts of this file are copyright (c)2005 by The University of North Carolina at Charlotte as contributed by Jason Edgecombe {@link http://tst.uncc.edu/team/members/jason_bio.php}.
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
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package obsolete
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER.
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );


// _user.funcs.php {{{

/**
 * get_usernumposts(-)
 * @deprecated by User::numposts()
 */
function get_usernumposts( $userid )
{
	global $DB;
	return $DB->get_var( "SELECT count(*)
												FROM T_posts
												WHERE post_creator_user_ID = $userid" );
}


/**
 * get_user_info(-)
 *
 * @deprecated by UserCache - not used in the core anymore
 */
function get_user_info( $show = '', $this_userdata )
{
	switch( $show )
	{
		case 'ID':
			$output = $this_userdata['ID'];
			break;

		case 'num_posts':
			$output = get_usernumposts( $this_userdata['ID'] );
			break;

		case 'level':
		case 'firstname':
		case 'lastname':
		case 'nickname':
		case 'idmode':
		case 'email':
		case 'url':
		case 'icq':
		case 'aim':
		case 'msn':
		case 'yim':
		case 'notify':
		case 'showonline':
		case 'locale':
			$output = $this_userdata['user_'. $show];
			break;

		case 'login':
		default:
			$output = $this_userdata['user_login'];
			break;
	}
	return trim($output);
}


/**
 * user_info(-)
 *
 * @deprecated by User - not used in the core anymore
 */
function user_info( $show = '', $format = 'raw', $display = true )
{
	global $current_User;

	$content = $current_User->get( $show );
	$content = format_to_output( $content, $format );
	if( $display )
		echo $content;
	else
		return $content;
}


/**
 * get_userdatabylogin(-)
 * @deprecated by UserCache::get_by_login()
 */
function get_userdatabylogin( $login )
{
	global $DB, $cache_userdata;
	if( empty($cache_userdata[$login]) )
	{
		$sql = "SELECT *
						FROM T_users
						WHERE user_login = '".$DB->escape($login)."'";
		$myrow = $DB->get_row( $sql, ARRAY_A );
		$cache_userdata[$login] = $myrow;
	}
	else
	{
		$myrow = $cache_userdata[$login];
	}
	return($myrow);
}


/**
 * get_userdata(-)
 * @deprecated by UserCache::get_by_ID()
 */
function get_userdata( $userid )
{
	global $DB, $cache_userdata;

	if( empty($cache_userdata[$userid] ) )
	{ // We do a progressive cache load because there can be many many users!
		$sql = "SELECT *
						FROM T_users
						WHERE ID = $userid";
		if( $myrow = $DB->get_row( $sql, ARRAY_A ) )
		{
			$cache_userdata[ $myrow['ID'] ] = $myrow;
		}
	}

	if( ! isset( $cache_userdata[$userid] ) )
	{
		die('Requested user does not exist!');
	}

	return $cache_userdata[$userid];
}


// _user.funcs.php }}}


// _trackback.funcs.php {{{

/**
 * This adds trackback autodiscovery information
 *
 * @deprecated deprecated by {@link Item::trackback_rdf()}
 */
function trackback_rdf($timezone=0)
{
	global $id, $blogfilename;	// fplanque added: $blogfilename
	// if (!stristr($_SERVER['HTTP_USER_AGENT'], 'W3C_Validator')) {
	// fplanque WARNING: this isn't a very clean way to validate :/
	// fplanque added: html comments (not perfect but better way of validating!)
		echo "<!--\n";
		echo '<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" '."\n";
		echo '    xmlns:dc="http://purl.org/dc/elements/1.1/"'."\n";
		echo '    xmlns:trackback="http://madskills.com/public/xml/rss/module/trackback/">'."\n";
		echo '<rdf:Description'."\n";
		echo '    rdf:about="';
		permalink_single();
		echo '"'."\n";
		echo '    dc:identifier="';
		permalink_single();
		echo '"'."\n";
		echo '    dc:title="'.format_to_output(get_the_title(),'xmlattr').'"'."\n";
		echo '    trackback:ping="'.trackback_url(0).'" />'."\n";
		echo '</rdf:RDF>';
		echo "-->\n";
	// }
}


/**
 *
 * @deprecated deprecated by {@link Item::trackback_url()}
 */
function trackback_url($display = 1)
{
	global $htsrv_url, $id;
	global $Settings;

	if( $Settings->get('links_extrapath') )
	{
		$tb_url = $htsrv_url.'trackback.php/'.$id;
	}
	else
	{
		$tb_url = $htsrv_url.'trackback.php?tb_id='.$id;
	}
	if ($display) {
		echo $tb_url;
	} else {
		return $tb_url;
	}
}


/**
 * Displays link to the trackback page
 * @deprecated deprecated by {@link Item::feedback_link()}
 */
function trackback_link($file='',$c=0,$pb=0)
{
	global $id;
	if( ($file == '') || ($file == '/')	)
		$file = get_bloginfo('blogurl');
	echo url_add_param( $file, 'p='.$id );
	if( $c == 1 )
	{ // include comments
		echo '&amp;c=1';
	}
	echo '&amp;tb=1';
	if( $pb == 1 )
	{ // include pingback
		echo '&amp;pb=1';
	}
	echo '#trackbacks';
}


/**
 *
 * @deprecated deprecated by {@link Item::feedback_link()}
 */
function trackback_popup_link($zero='#', $one='#', $more='#', $CSSclass='')
{
	global $blog, $id, $b2trackbackpopupfile, $b2commentsjavascript;
	echo '<a href="';
	if ($b2commentsjavascript) {
		echo url_add_param( get_bloginfo('blogurl'), 'template=popup&amp;p='.$id.'&amp;tb=1' );
		echo '" onclick="b2open(this.href); return false"';
	} else {
		// if comments_popup_script() is not in the template, display simple comment link
		trackback_link();
		echo '"';
	}
	if (!empty($CSSclass)) {
		echo ' class="'.$CSSclass.'"';
	}
	echo '>';
	trackback_number($zero, $one, $more);
	echo '</a>';
}

// _trackback.funcs.php }}}


// _item.funcs.php {{{

/**
 * the_author_posts(-)
 * @deprecated by User::getNumPosts() - not used in the core anymore
 */
function the_author_posts()
{
	global $postdata, $UserCache;

	$User =& $UserCache->get_by_ID($postdata['Author_ID']);
	echo $User->getNumPosts();
}


/**
 * the_title(-)
 *
 * Display post title
 * 03.10.10 - Updated function to allow for silent operations
 *
 * @deprecated deprecated by {@link Item::title()}
 */
function the_title(
	$before='',						// HTML/text to be displayed before title
	$after='', 						// HTML/text to be displayed after title
	$add_link = true, 		// Added link to this title?
	$format = 'htmlbody',	// Format to use (example: "htmlbody" or "xml")
	$disp = true )				// Display output?
{
	global $postdata;

	$title = get_the_title();
	$url = trim($postdata['Url']);

	if( empty($title) && $add_link )
	{
		$title = $url;
	}

	if( empty($title) )
		return;

	if( $add_link && (!empty($url)) )
	{
		$title = $before.'<a href="'.$url.'">'.$title.'</a>'.$after;
	}
	else
	{
		$title = $before.$title.$after;
	}

	//	ADDED: 03.10.08 by Travis S. :Support for silent operation
	$return_str = format_to_output( $title, $format );
	if( $disp == true )
		echo $return_str;
	else
		return $return_str;
}


/**
 * get_the_title(-)
 *
 * @deprecated
 */
function get_the_title()
{
	global $id,$postdata;
	$output = trim( $postdata['Title'] );
	return($output);
}


/**
 * the_ID(-)
 *
 *
 * @deprecated deprecated by {@link DataObject::ID()}
 *
 */
function the_ID()
{
	global $id;
	echo $id;
}


/**
 * the_status(-)
 *
 * Display post status
 *
 * @deprecated deprecated by {@link Item::status()}
 */
function the_status( $raw = true )
{
	global $post_statuses, $postdata;
	$status = $postdata['Status'];
	if( $raw )
		echo $status;
	else
		echo T_($post_statuses[$status]);
}


/**
 * the_lang(-)
 *
 * Display post language code
 *
 * @deprecated deprecated by {@link Item::lang()}
 */
function the_lang()
{
	global $postdata;
	echo $postdata['Locale'];
}


/**
 * the_language(-)
 *
 * Display post language name
 *
 * @deprecated deprecated by {@link Item::language()}
 */
function the_language()
{
	global $postdata, $languages;
	$post_lang = $postdata['Locale'];
	echo $languages[ $post_lang ];
}


/**
 * the_wordcount(-)
 * Display the number of words in the post
 *
 *
 * @deprecated deprecated by {@link Item::wordcount()}
 */
function the_wordcount()
{
	global $postdata;
	echo $postdata['Wordcount'];
}


/**
 * the_link(-)
 *
 * Display post link
 *
 * @deprecated deprecated by {@link Item::url_link()}
 */
function the_link( $before='', $after='', $format = 'htmlbody' )
{
	global $postdata;

	$url = trim($postdata['Url']);

	if( empty($url) )
	{
		return false;
	}

	$link = $before.'<a href="'.$url.'">'.$url.'</a>'.$after;

	echo format_to_output( $link, $format );
}


/**
 * {@internal preview_title(-)}}
 * @deprecated Not used in the core
 */
function preview_title( $string = '#', $before = ' ', $after = '' )
{
	global $preview;

	if( $preview )
	{
		echo $before;
		echo ($string == '#') ? T_('PREVIEW') : $string;
		echo $after;
	}
}


/**
 * the_content(-)
 *
 * @deprecated deprecated by {@link Item::content()}
 */
function the_content(
	$more_link_text='#',
	$stripteaser=0,
	$more_file='',
	$more_anchor='#',
	$before_more_link = '#',
	$after_more_link = '#',
	$format = 'htmlbody',
	$cut = 0,
	$dispmore = '#',  // 1 to display 'more' text, # for url parameter
	$disppage = '#' ) // page number to display specific page, # for url parameter
{
	global $id, $postdata, $pages, $multipage, $numpages;
	global $preview;

	// echo $format,'-',$cut,'-',$dispmore,'-',$disppage;

	if( $more_link_text == '#' )
	{ // TRANS: this is the default text for the extended post "more" link
		$more_link_text = '=> '.T_('Read more!');
	}

	if( $more_anchor == '#' )
	{ // TRANS: this is the default text displayed once the more link has been activated
		$more_anchor = '['.T_('More:').']';
	}

	if( $before_more_link == '#' )
		$before_more_link = '<p class="bMore">';

	if( $after_more_link == '#' )
		$after_more_link = '</p>';

	if( $dispmore === '#' )
	{
		global $more;
		$dispmore = $more;
	}

	if( $disppage === '#' )
	{
		global $page;
		$disppage = $page;
	}
	if( $disppage > $numpages ) $disppage = $numpages;
	// echo 'Using: dmore=', $dispmore, ' dpage=', $disppage;

	$output = '';
	if ($more_file != '')
		$file = $more_file;
	else
		$file = get_bloginfo('blogurl');

	$content = $pages[$disppage-1];
	$content = explode('<!--more-->', $content);

	if ((preg_match('/<!--noteaser-->/', $postdata['Content']) && ((!$multipage) || ($disppage==1))))
		$stripteaser=1;
	$teaser=$content[0];
	if (($dispmore) && ($stripteaser))
	{ // We don't want to repeat the teaser:
		$teaser='';
	}
	$output .= $teaser;

	if (count($content)>1)
	{
		if ($dispmore)
		{ // Viewer has already asked for more
			if( !empty($more_anchor) ) $output .= $before_more_link;
			$output .= '<a id="more'.$id.'" name="more'.$id.'"></a>'.$more_anchor;
			if( !empty($more_anchor) ) $output .= $after_more_link;
			$output .= $content[1];
		}
		else
		{ // We are offering to read more
			$more_link = gen_permalink( $file, $id, 'id', 'single', 1 );
			$output .= $before_more_link.'<a href="'.$more_link.'#more'.$id.'">'.$more_link_text.'</a>'.$after_more_link;
		}
	}
	if ($preview)
	{ // preview fix for javascript bug with foreign languages
		$output = preg_replace('/\%u([0-9A-F]{4,4})/e',  "'&#'.base_convert('\\1',16,10).';'", $output);
	}

	$content = format_to_output( $output, $format );

	if( ($format == 'xml') && $cut )
	{ // Let's cut this down...
		$blah = explode(' ', $content);
		if (count($blah) > $cut)
		{
			for ($i=0; $i<$cut; $i++)
			{
				$excerpt .= $blah[$i].' ';
			}
			$content = $excerpt . '...';
		}
	}
	echo $content;
}


/**
 * the_date(-)
 *
 * @deprecated deprecated by {@link ItemList::date_if_changed()}
 */
function the_date($d='', $before='', $after='', $echo = 1)
{
	global $id, $postdata, $day, $previousday, $newday;
	$the_date = '';
	if ($day != $previousday)
	{
		$the_date .= $before;
		if ($d=='') {
			$the_date .= mysql2date( locale_datefmt(), $postdata['Date']);
		} else {
			$the_date .= mysql2date( $d, $postdata['Date']);
		}
		$the_date .= $after;
		$previousday = $day;
	}
	if ($echo) {
		echo $the_date;
	} else {
		return $the_date;
	}
}


/**
 * the_time(-)
 *
 *
 * @deprecated deprecated by {@link Item::time()} / {@link Item::date()}
 *
 */
function the_time($d='', $echo = 1, $useGM = 0)
{
	global $id,$postdata;
	if ($d=='')
	{
		$the_time = mysql2date( locale_timefmt(), $postdata['Date'], $useGM);
	} else {
		$the_time = mysql2date( $d, $postdata['Date'], $useGM);
	}

	if ($echo)
	{
		echo $the_time;
	} else {
		return $the_time;
	}
}


/**
 * the_author(-)
 *
 * @deprecated deprecated by {@link User::prefered_name()}
 */
function the_author( $format = 'htmlbody' )
{
	global $authordata;
	switch( $authordata['user_idmode'] )
	{
		case 'nickname':
			$author = $authordata['user_nickname'];
			break;

		case 'login':
			$author = $authordata['user_login'];
			break;

		case 'firstname':
			$author = $authordata['user_firstname'];
			break;

		case 'lastname':
			$author = $authordata['user_lastname'];
			break;

		case 'namefl':
			$author = $authordata['user_firstname'].' '.$authordata['user_lastname'];
			break;

		case 'namelf':
			$author = $authordata['user_lastname'].' '.$authordata['user_firstname'];
			break;

		default:
			$author = $authordata['user_nickname'];
	}

	echo format_to_output( $author, $format );
}


/**
 * the_author_level(-)
 *
 * @deprecated deprecated by {@link User::level()}
 */
function the_author_level()
{
	global $authordata;
	echo $authordata['user_level'];
}


/**
 * the_author_login(-)
 *
 * @deprecated deprecated by {@link User::login()}
 */
function the_author_login( $format = 'htmlbody' )
{
	global $authordata;
	echo format_to_output( $authordata['user_login'], $format );
}


/**
 * the_author_firstname(-)
 *
 * @deprecated deprecated by {@link User::firstname()}
 */
function the_author_firstname( $format = 'htmlbody' )
{
	global $authordata;
	echo format_to_output( $authordata['user_firstname'], $format );
}


/**
 * the_author_lastname(-)
 *
 * @deprecated deprecated by {@link User::lastname()}
 */
function the_author_lastname( $format = 'htmlbody' )
{
	global $authordata;
	echo format_to_output( $authordata['user_lastname'], $format );
}


/**
 * the_author_nickname(-)
 *
 * @deprecated deprecated by {@link User::nickname()}
 */
function the_author_nickname( $format = 'htmlbody' )
{
	global $authordata;
	echo format_to_output( $authordata['user_nickname'], $format );
}


/**
 * the_author_ID(-)
 *
 * @deprecated deprecated by {@link DataObject::ID()}
 */
function the_author_ID()
{
	global $authordata;
	echo $authordata['ID'];
}


/**
 * the_author_email(-)
 *
 * @deprecated deprecated by {@link User::email()}
 */
function the_author_email( $format = 'raw' )
{
	global $authordata;
	echo format_to_output( antispambot($authordata['user_email']), $format );
}


/**
 * the_author_url(-)
 *
 * @deprecated deprecated by {@link User::url()}
 */
function the_author_url( $format = 'raw' )
{
	global $authordata;
	echo format_to_output( $authordata['user_url'], $format );
}


/**
 * the_author_icq(-)
 *
 * @deprecated deprecated by {@link User::icq()}
 */
function the_author_icq( $format = 'raw' )
{
	global $authordata;
	echo format_to_output( $authordata['user_icq'], $format );
}


/**
 * the_author_aim(-)
 *
 * @deprecated deprecated by {@link User::aim()}
 */
function the_author_aim( $format = 'raw' )
{
	global $authordata;
	echo format_to_output( str_replace(' ', '+', $authordata['user_aim']), $format );
}


/**
 * the_author_yim(-)
 *
 * @deprecated deprecated by {@link User::yim()}
 */
function the_author_yim( $format = 'raw' )
{
	global $authordata;
	echo format_to_output( $authordata['user_yim'], $format );
}


/**
 * the_author_msn(-)
 *
 * @deprecated deprecated by {@link User::msn()}
 */
function the_author_msn( $format = 'raw' )
{
	global $authordata;
	echo format_to_output( $authordata['user_msn'], $format );
}


/**
 * permalink_anchor(-)
 *
 * generate anchor for permalinks to refer to
 *
 * TODO: archives modes in clean mode
 *
 * @deprecated deprecated by {@link Item::anchor()}
 */
function permalink_anchor( $mode = 'id' )
{
	global $id, $postdata;
	switch(strtolower($mode))
	{
		case 'title':
			$title = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $postdata['Title']);
			echo '<a name="'.$title.'"></a>';
			break;
		case 'id':
		default:
			echo '<a name="'.$id.'"></a>';
			break;
	}
}


/**
 * permalink_single(-)
 *
 * Permalink forced to a single post
 *
 * @deprecated deprecated by {@link Item::permalink()}
 */
function permalink_single($file='')
{
	global $id;
	if (empty($file)) $file = get_bloginfo('blogurl');
	echo gen_permalink( $file, $id, 'id', 'single' );
}


/**
 * {@internal the_permalink(-) }}
 *
 * @deprecated deprecated by {@link $Item::permalink()}
 */
function the_permalink()
{
	global $Item;
	$Item->permalink();
}



/**
 * is_new_day(-)
 *
 * @deprecated Not used in the core.
 */
function is_new_day()
{
	global $day, $previousday;
	if ($day != $previousday) {
		return(1);
	} else {
		return(0);
	}
}


// _item.funcs.php }}}


// _comment.funcs.php {{{

/**
 * get_commentdata(-)
 *
 * @deprecated Not used in the core anymore.
 */
function get_commentdata($comment_ID,$no_cache=0)
{ // less flexible, but saves mysql queries
	global $DB, $rowc, $id, $commentdata, $baseurl;

	if ($no_cache)
	{
		$query = "SELECT *
							FROM T_comments
							WHERE comment_ID = $comment_ID";
		$myrow = $DB->get_row( $query, ARRAY_A );
	}
	else
	{
		$myrow['comment_ID'] = $rowc->comment_ID;
		$myrow['comment_post_ID'] = $rowc->comment_post_ID;
		$myrow['comment_author'] = $rowc->comment_author;
		$myrow['comment_author_email'] = $rowc->comment_author_email;
		$myrow['comment_author_url'] = $rowc->comment_author_url;
		$myrow['comment_author_IP'] = $rowc->comment_author_IP;
		$myrow['comment_date'] = $rowc->comment_date;
		$myrow['comment_content'] = $rowc->comment_content;
		$myrow['comment_karma'] = $rowc->comment_karma;
		$myrow['comment_type'] = $rowc->comment_type;
		if( isset($rowc->ID) ) $myrow['post_ID'] = $rowc->ID;
		if( isset($rowc->post_title) ) $myrow['post_title'] = $rowc->post_title;
		if( isset($rowc->blog_name) ) $myrow['blog_name'] = $rowc->blog_name;
		if( isset($rowc->blog_siteurl) ) $myrow['blog_siteurl'] = $baseurl.$rowc->blog_siteurl;
		if( isset($rowc->blog_stub) ) $myrow['blog_stub'] = $rowc->blog_stub;
	}
	return($myrow);
}


/**
 * comments_popup_link(-)
 *
 * @deprecated deprecated by {@link Item::feedback_link()}
 */
function comments_popup_link($zero='#', $one='#', $more='#', $CSSclass='')
{
	global $blog, $id, $b2commentspopupfile, $b2commentsjavascript;
	echo '<a href="';
	if($b2commentsjavascript)
	{
		echo url_add_param( get_bloginfo('blogurl'), 'template=popup&amp;p='.$id.'&amp;c=1' );
		echo '" onclick="b2open(this.href); return false"';
	}
	else
	{ // if comments_popup_script() is not in the template, display simple comment link
		comments_link();
		echo '"';
	}
	if (!empty($CSSclass)) {
		echo ' class="'.$CSSclass.'"';
	}
	echo '>';
	comments_number($zero, $one, $more);
	echo '</a>';
}


/**
 * comment_ID(-)
 *
 * @deprecated deprecated by {@link DataObject::ID()}
 */
function comment_ID()
{
	global $commentdata;	echo $commentdata['comment_ID'];
}


/**
 * comment_author(-)
 *
 * @deprecated deprecated by {@link Comment::author()}
 */
function comment_author()
{
	global $commentdata;
	echo $commentdata['comment_author'];
}


/**
 * comment_author_email(-)
 *
 * @deprecated deprecated by {@link Comment::author_email()}
 */
function comment_author_email()
{
	global $commentdata;
	echo antispambot( $commentdata['comment_author_email'] );
}


/**
 * comment_author_email_link(-)
 *
 * @deprecated deprecated by {@link Comment::author_email()}
 */
function comment_author_email_link($linktext='', $before='', $after='')
{
	global $commentdata;
	$email=$commentdata['comment_author_email'];
	if ((!empty($email)) && ($email != '@')) {
		$display = ($linktext != '') ? $linktext : antispambot($email);
		echo $before;
		echo '<a href="mailto:'.antispambot($email).'">'.$display.'</a>';
		echo $after;
	}
}


/**
 * comment_author_url_link(-)
 *
 * @deprecated deprecated by {@link $Comment->author_url()}
 */
function comment_author_url_link($linktext='', $before='', $after='')
{
	global $commentdata;
	$url = trim($commentdata['comment_author_url']);
	$url = preg_replace('#&([^amp\;])#is', '&amp;$1', $url);
	$url = (!stristr($url, '://')) ? 'http://'.$url : $url;
	if ((!empty($url)) && ($url != 'http://') && ($url != 'http://url'))
	{
		$display = ($linktext != '') ? $linktext : $url;
		echo $before;
		echo '<a href="'.$url.'">'.$display.'</a>';
		echo $after;
	}
}


/**
 * comment_author_IP(-)
 *
 * @deprecated deprecated by {@link Comment::author_ip()}
 */
function comment_author_IP() {
	global $commentdata;
	echo $commentdata['comment_author_IP'];
}


/**
 * comment_text(-)
 *
 * @deprecated deprecated by {@link $Comment::content()}
 */
function comment_text()
{
	global $commentdata;

	$comment = $commentdata['comment_content'];
	$comment = str_replace('<trackback />', '', $comment);
	$comment = str_replace('<pingback />', '', $comment);
	$comment = format_to_output( $comment, 'htmlbody' );
	echo $comment;
}


/**
 * comment_date(-)
 *
 * @deprecated deprecated by {@link $Comment::date()}
 */
function comment_date($d='') {
	global $commentdata;
	if( $d == '' )
		echo mysql2date( locale_datefmt(), $commentdata['comment_date']);
	else
		echo mysql2date($d, $commentdata['comment_date']);
}


/**
 * comment_time(-)
 *
 * @deprecated deprecated by {@link $Comment::time()}
 */
function comment_time( $d = '' )
{
	global $commentdata;
	if( $d == '' )
		echo mysql2date( locale_timefmt(), $commentdata['comment_date']);
	else
		echo mysql2date($d, $commentdata['comment_date']);
}


/**
 * comment_post_title(-)
 * fplanque added
 *
 * @deprecated deprecated by {@link $Comment::post_title()}
 */
function comment_post_title()
{
	global $commentdata;
	$title = $commentdata['post_title'];
	echo format_to_output( $title, 'htmlbody' );
}


/**
 * comment_blog_name(-)
 * fplanque added
 *
 * @deprecated
 */
function comment_blog_name( $disp = true )
{
	global $commentdata;
	$blog_name = $commentdata['blog_name'];
	if( !$disp )
		return $blog_name;
	echo $blog_name;
}


/**
 * comment_post_link(-)
 * fplanque added
 *
 * @deprecated deprecated by {@link $Comment::post_link()}
 */
function comment_post_link()
{
	global $commentdata;
	echo gen_permalink( $commentdata['blog_siteurl']. '/'. $commentdata['blog_stub'], $commentdata['post_ID'],	'id', 'single' );
}


// _comment.funcs.php }}}



/*
 * $Log$
 * Revision 1.3  2005/02/19 18:20:47  blueyed
 * obsolete functions removed
 *
 * Revision 1.2  2005/02/15 22:35:49  blueyed
 * doc
 *
 */
?>
