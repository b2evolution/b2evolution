<?php
/*
 * b2evolution - http://b2evolution.net/
 *
 * Copyright (c) 2003 by Francois PLANQUE - http://fplanque.net/
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 *
 * This file built upon code from original b2 - http://cafelog.com/
 */

/*
 * generic_ctp_number(-) 
 *
 * generic comments/trackbacks/pingbacks numbering
 *
 * fplanque: added stuff to load all number for this page at ounce
 */
function generic_ctp_number($post_id, $mode = 'comments') 
{
	global $debug, $postdata, $tablecomments, $querycount, $cache_ctp_number, $use_cache, $preview;
	if( $preview )
	{	// we are in preview mode, no comments yet!
		return 0;
	}

	// fplanque added: load whole cache
	if (!isset($cache_ctp_number) || (!$use_cache))
	{
		global $postIDlist, $postIDarray;
		// if( $debug ) echo "LOADING generic_ctp_number CACHE for posts: $postIDlist<br />";
		foreach( $postIDarray as $tmp_post_id)
		{		// Initializes each post to nocount!
				$cache_ctp_number[$tmp_post_id] = array( 'comments' => 0, 'trackbacks' => 0, 'pingbacks' => 0, 'ctp' => 0);
		}
		$query = "SELECT comment_post_ID, comment_type, COUNT(*) AS type_count FROM $tablecomments WHERE comment_post_ID IN ($postIDlist) GROUP BY comment_post_ID, comment_type";
		$result = mysql_query($query) or mysql_oops($query);
		$querycount++;
		while($row = mysql_fetch_object($result)) 
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
	if (!isset($cache_ctp_number[$post_id]) || (!$use_cache)) 
	{	// this should be extremely rare...
		// echo "CACHE not set for $post_id";
		$post_id = intval($post_id);
		$query = "SELECT comment_post_ID, comment_type, COUNT(*) AS type_count FROM $tablecomments WHERE comment_post_ID = $post_id GROUP BY comment_post_ID, comment_type";
		$result = mysql_query($query) or mysql_oops($query);
		$querycount++;
		while($row = mysql_fetch_object($result)) 
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
	} else {
		$ctp_number = $cache_ctp_number[$post_id];
	}
	if (($mode != 'comments') && ($mode != 'trackbacks') && ($mode != 'pingbacks') && ($mode != 'ctp')) {
		$mode = 'ctp';
	}
	return $ctp_number[$mode];
}


/*
 * get_commentdata(-)
 */
function get_commentdata($comment_ID,$no_cache=0) 
{ // less flexible, but saves mysql queries
	global $rowc,$id,$commentdata,$tablecomments,$querycount, $baseurl;
	if ($no_cache) 
	{
		$query="SELECT * FROM $tablecomments WHERE comment_ID = $comment_ID";
		// fplanque TODO: add post title etc?
		$result=mysql_query($query);
		$querycount++;
		$myrow = mysql_fetch_array($result);
	} 
	else
	{
		$myrow['comment_ID']=$rowc->comment_ID;
		$myrow['comment_post_ID']=$rowc->comment_post_ID;
		$myrow['comment_author']=$rowc->comment_author;
		$myrow['comment_author_email']=$rowc->comment_author_email;
		$myrow['comment_author_url']=$rowc->comment_author_url;
		$myrow['comment_author_IP']=$rowc->comment_author_IP;
		$myrow['comment_date']=$rowc->comment_date;
		$myrow['comment_content']=$rowc->comment_content;
		$myrow['comment_karma']=$rowc->comment_karma;
		$myrow['comment_type']=$rowc->comment_type;
		if( isset($rowc->ID) ) $myrow['post_ID']=$rowc->ID;	
		if( isset($rowc->post_title) ) $myrow['post_title']=$rowc->post_title;
		if( isset($rowc->blog_name) ) $myrow['blog_name']=$rowc->blog_name;
		if( isset($rowc->blog_siteurl) ) $myrow['blog_siteurl']=$baseurl.$rowc->blog_siteurl;
		if( isset($rowc->blog_stub) ) $myrow['blog_stub']=$rowc->blog_stub;
	}
	return($myrow);
}



/* 
 * TEMPLATE functions
 */




/***** Comment tags *****/



/*
 * comments_number(-)
 */
function comments_number( $zero='#', $one='#', $more='#' ) 
{
	if( $zero == '#' ) $zero = T_('Leave a comment');
	if( $one == '#' ) $one = T_('1 comment');
	if( $more == '#' ) $more = T_('% comments');

	// original hack by dodo@regretless.com
	global $id,$postdata,$tablecomments,$c,$querycount,$cache_commentsnumber,$use_cache;
	$number = generic_ctp_number($id, 'comments');
	if ($number == 0) {
		$blah = $zero;
	} elseif ($number == 1) {
		$blah = $one;
	} elseif ($number  > 1) {
		$n = $number;
		$more=str_replace('%', $n, $more);
		$blah = $more;
	}
	echo $blah;
}

/*
 * comments_link(-)
 *
 * Displays link to comments page
 */
function comments_link($file='', $tb=0, $pb=0 ) 
{
	global $id;
	if( ($file == '') || ($file == '/')	)
		$file = get_bloginfo('blogurl');
	echo $file.'?p='.$id.'&amp;c=1';
	if( $tb == 1 )
	{	// include trackback // fplanque: added
		echo '&amp;tb=1';
	}
	if( $pb == 1 )
	{	// include pingback // fplanque: added
		echo '&amp;pb=1';
	}
	echo '#comments';
}


/*
 * comments_popup_script(-)
 *
 * This will include the javascript that is required to open comments, trackback and pingback in popup windows.
You should put this tag before the </head> tag in your template.
 *
 * fplanque: added resizable !!!
 */
function comments_popup_script($width=560, $height=400, $file='comment_popup.php', $trackbackfile='trackback_popup.php', $pingbackfile='pingback_popup.php') 
{
	global $b2commentspopupfile, $b2trackbackpopupfile, $b2pingbackpopupfile, $b2commentsjavascript;
	$b2commentspopupfile = $file;
	$b2trackbackpopupfile = $trackbackfile;
	$b2pingbackpopupfile = $pingbackfile;
	$b2commentsjavascript = 1;
?>
	<script language="javascript" type="text/javascript">
	<!--
		function b2open( url ) 
		{
			window.open( url, '_blank', 'width=<?php echo $width; ?>,height=<?php echo $height; ?>,scrollbars,status,resizable');
		}
		//-->
		</script>
<?php }


/* 
 * comments_popup_link(-)
 *
 */
function comments_popup_link($zero='#', $one='#', $more='#', $CSSclass='') 
{
	global $blog, $id, $b2commentspopupfile, $b2commentsjavascript;
	echo '<a href="';
	if($b2commentsjavascript)
	{
		echo get_bloginfo('blogurl').'?template=popup&amp;p='.$id.'&amp;c=1';
		echo '" onclick="b2open(this.href); return false"';
	} 
	else 
	{	// if comments_popup_script() is not in the template, display simple comment link
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

/*
 * comment_ID(-)
 */
function comment_ID() {
	global $commentdata;	echo $commentdata['comment_ID'];
}

/*
 * comment_author(-)
 */
function comment_author() {
	global $commentdata;	echo stripslashes($commentdata['comment_author']);
}

/*
 * comment_author_email(-)
 */
function comment_author_email() {
	global $commentdata;	echo antispambot(stripslashes($commentdata['comment_author_email']));
}

/*
 * comment_author_url(-)
 */
function comment_author_url() {
	global $commentdata;
	$url = trim(stripslashes($commentdata['comment_author_url']));
	$url = (!stristr($url, '://')) ? 'http://'.$url : $url;
	// convert & into &amp;
	$url = preg_replace('#&([^amp\;])#is', '&amp;$1', $url);
	if ($url != 'http://url') {
		echo $url;
	}
}

/*
 * comment_author_email_link(-)
 */
function comment_author_email_link($linktext='', $before='', $after='') {
	global $commentdata;
	$email=$commentdata['comment_author_email'];
	if ((!empty($email)) && ($email != '@')) {
		$display = ($linktext != '') ? $linktext : antispambot(stripslashes($email));
		echo $before;
		echo '<a href="mailto:'.antispambot(stripslashes($email)).'">'.$display.'</a>';
		echo $after;
	}
}


/*
 * comment_author_url_link(-)
 */
function comment_author_url_link($linktext='', $before='', $after='') {
	global $commentdata;
	$url = trim(stripslashes($commentdata['comment_author_url']));
	$url = preg_replace('#&([^amp\;])#is', '&amp;$1', $url);
	$url = (!stristr($url, '://')) ? 'http://'.$url : $url;
	if ((!empty($url)) && ($url != 'http://') && ($url != 'http://url')) {
		$display = ($linktext != '') ? $linktext : stripslashes($url);
		echo $before;
		echo '<a href="'.stripslashes($url).'" target="_blank">'.$display.'</a>';
		echo $after;
	}
}

/*
 * comment_author_IP(-)
 */
function comment_author_IP() {
	global $commentdata;	echo stripslashes($commentdata['comment_author_IP']);
}

/*
 * comment_text(-)
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

/*
 * comment_date(-)
 */
function comment_date($d='') {
	global $commentdata;
	if ($d == '') {
		echo mysql2date( locale_datefmt(), $commentdata['comment_date']);
	} else {
		echo mysql2date($d, $commentdata['comment_date']);
	}
}

/*
 * comment_time(-)
 */
function comment_time($d='') {
	global $commentdata;
	if ($d == '') {
		echo mysql2date( locale_timefmt(), $commentdata['comment_date']);
	} else {
		echo mysql2date($d, $commentdata['comment_date']);
	}
}

/*
 * comment_post_title(-)
 * fplanque added
 */
function comment_post_title() 
{
	global $commentdata;
	$title = $commentdata['post_title'];
	echo format_to_output( $title, 'htmlbody' );
}

/*
 * comment_post_link(-)
 * fplanque added
 */
function comment_post_link() 
{
	global $commentdata;
	echo full_post_link($commentdata['post_ID'], $commentdata['blog_siteurl']."/".$commentdata['blog_stub']);	// Links to original blog for the post
}


/*
 * comment_blog_name(-)
 * fplanque added
 */
function comment_blog_name() 
{
	global $commentdata;	echo stripslashes($commentdata['blog_name']);
}

/*****
 * /Comment tags 
 *****/



?>