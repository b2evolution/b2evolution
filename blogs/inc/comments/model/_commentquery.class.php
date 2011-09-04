<?php
/**
 * This file implements the CommentQuery class.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}.
*
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * {@internal Open Source relicensing agreement:
 * EVO FACTORY grants Francois PLANQUE the right to license
 * EVO FACTORY contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author asimo: Evo Factory / Attila Simo
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/db/_sql.class.php', 'SQL' );

/**
 * CommentQuery: help constructing queries on Comments
 * @package evocore
 */
class CommentQuery extends SQL
{
	var $c;
	var $cl;
	var $post;
	var $author;
	var $author_email;
	var $author_url;
	var $url_match;
	var $include_emptyurl;
	var $author_IP;
	var $rating_toshow;
	var $rating_turn;
	var $rating_limit;
	var $show_statuses;
	var $types;
	var $keywords;
	var $phrase;
	var $exact;

	var $Blog;


	/**
	 * Constructor.
	 *
	 * @param string Name of table in database
	 * @param string Prefix of fields in the table
	 * @param string Name of the ID field (including prefix)
	 */
	function CommentQuery( $dbtablename = 'T_comments', $dbprefix = 'comment_', $dbIDname = 'comment_ID' )
	{
		$this->dbtablename = $dbtablename;
		$this->dbprefix = $dbprefix;
		$this->dbIDname = $dbIDname;

		$this->FROM( $this->dbtablename );

		# Add constraints to only include comments on items visible for the current User!
		$this->FROM_add('INNER JOIN T_items__item ON post_ID = comment_post_ID');
		$this->WHERE_and(statuses_where_clause());
	}


	/**
	 * Restrict to a specific comment
	 */
	function where_ID( $c = '', $author = '' )
	{
		$r = false;

		$this->c = $c;
		$this->author = $author;

		// if a comment number is specified, load that comment
		if( !empty($c) )
		{
			$this->WHERE_and( $this->dbIDname.' = '. intval($c) );
			$r = true;
		}

		// if a comment author is specified, load that comment
		if( !empty( $author ) )
		{
			global $DB;
			$this->WHERE_and( $this->dbprefix.'author = '.$DB->quote($author) );
			$r = true;
		}

		return $r;
	}


	/**
	 * Restrict to a specific list of comments
	 */
	function where_ID_list( $cl = '' )
	{
		$r = false;

		$this->cl = $cl;

		if( empty( $cl ) ) return $r; // nothing to do

		if( substr( $this->cl, 0, 1 ) == '-' )
		{	// List starts with MINUS sign:
			$eq = 'NOT IN';
			$this->cl = substr( $this->cl, 1 );
		}
		else
		{
			$eq = 'IN';
		}

		$c_ID_array = array();
		$c_id_list = explode( ',', $this->cl );
		foreach( $c_id_list as $c_id )
		{
			$c_ID_array[] = intval( $c_id );// make sure they're all numbers
		}

		$this->cl = implode( ',', $c_ID_array );

		$this->WHERE_and( $this->dbIDname.' '.$eq.'( '.$this->cl.' )' );
		$r = true;

		return $r;
	}


	/**
	 * Restrict to a specific post comments
	 */
	function where_post_ID( $post )
	{
		$this->post = $post;

		if( empty( $post ) )
		{
			return;
		}

		if( substr( $post, 0, 1 ) == '-' )
		{	// List starts with MINUS sign:
			$eq = 'NOT IN';
			$post_list = substr( $post, 1 );
		}
		else
		{
			$eq = 'IN';
			$post_list = $post;
		}

		$this->WHERE_and( $this->dbprefix.'post_ID '.$eq.' ('.$post_list.')' );
	}


	/**
	 * Restrict to specific authors
	 *
	 * @param string List of authors (author IDs) to restrict to (must have been previously validated)
	 */
	function where_author( $author )
	{
		$this->author = $author;

		if( empty( $author ) )
		{
			return;
		}

		if( substr( $author, 0, 1 ) == '-' )
		{	// List starts with MINUS sign:
			$eq = 'NOT IN';
			$author_list = substr( $author, 1 );
		}
		else
		{
			$eq = 'IN';
			$author_list = $author;
		}

		$this->WHERE_and( $this->dbprefix.'author_ID '.$eq.' ('.$author_list.')' );
	}


	/**
	 * Restrict to specific authors email
	 *
	 * @param string List of authors email to restrict to (must have been previously validated)
	 */
	function where_author_email( $author_email )
	{
		$this->author_email = $author_email;

		if( empty( $author_email ) )
		{
			return;
		}

		if( substr( $author_email, 0, 1 ) == '-' )
		{	// List starts with MINUS sign:
			$eq = 'NOT IN';
			$author_email_list = substr( $author_email, 1 );
		}
		else
		{
			$eq = 'IN';
			$author_email_list = $author_email;
		}

		$this->WHERE_and( $this->dbprefix.'author_email '.$eq.' ('.$author_email_list.')' );
	}


	/**
	 * Restrict to specific author url
	 *
	 * @param string authors url to restrict to
	 * @param string equal or not equal restriction
	 * @param boolean include or not comments, with no url
	 */
	function where_author_url( $author_url, $url_match, $include_emptyurl )
	{
		$this->author_url = $author_url;
		$this->url_match = $url_match;
		$this->include_emptyurl = $include_emptyurl;

		if( empty( $author_url ) )
		{
			return;
		}

		if( empty( $url_match) )
		{
			$url_match = "IN";
		}
		elseif( $url_match == '=' )
		{
			$author_url = '%'.$author_url.'%';
			$url_match = 'LIKE';
		}
		elseif( $url_match == '!=' )
		{
			$author_url = '%'.$author_url.'%';
			$url_match = 'NOT LIKE';
		}

		$include_empty = '';
		if( $include_emptyurl )
		{	// include comments with no url
			$include_empty = ' OR '.$this->dbprefix.'author_url IS NULL';
		}

		$this->WHERE_and( $this->dbprefix.'author_url '.$url_match.' ("'.$author_url.'")'.$include_empty );
	}


	/**
	 * Restrict to specific author IPs
	 *
	 * @param string List of authors IPs to restrict to (must have been previously validated)
	 */
	function where_author_IP( $author_IP )
	{
		$this->author_IP = $author_IP;

		if( empty( $author_IP ) )
		{
			return;
		}

		if( substr( $author_IP, 0, 1 ) == '-' )
		{	// List starts with MINUS sign:
			$eq = 'NOT IN';
			$author_IP_list = substr( $author_IP, 1 );
		}
		else
		{
			$eq = 'IN';
			$author_IP_list = $author_IP;
		}

		$this->WHERE_and( $this->dbprefix.'author_IP '.$eq.' ('.$author_IP_list.')' );
	}


	/**
	 * Restrict to specific rating or rating interval
	 *
	 * @param array show none rated comments or show comments with specific rating values or none/both
	 * @param string search above, below or exact rating values
	 * @param integer rating value - limit
	 */
	function where_rating( $rating_toshow, $rating_turn, $rating_limit )
	{
		$this->rating_toshow = $rating_toshow;
		$this->rating_turn = $rating_turn;
		$this->rating_limit = $rating_limit;

		if( empty( $rating_toshow) )
		{	//	no nead where condition for ratings
			return;
		}

		$search = '';
		if( in_array( 'norating', $rating_toshow ) )
		{	//	include comments with no rating
			$search = $this->dbprefix.'rating IS NULL';
		}

		if( in_array( 'haverating', $rating_toshow ) )
		{	//	specify search direction
			switch( $rating_turn )
			{
				case 'exact':
					$comp = '=';
					break;
				case 'above':
					$comp = '>=';
					break;
				case 'below':
					$comp = '<=';
					break;
				default:
					debug_die( 'Unknown search direction!' );
			}
			if( $search != '' )
			{
				$search = ' '.$search.' OR ';
				$where_end = ' )';
			}
			$search = $search.$this->dbprefix.'rating '.$comp.' '.$rating_limit;
		}

		$this->WHERE_and( $search );
	}


	/**
	 * Restrict to specific statuses
	 *
	 * @param string List of statuses to restrict to (must have been previously validated)
	 */
	function where_statuses( $show_statuses )
	{
		if( empty( $show_statuses ) )
		{ // initialize if emty
			$show_statuses = array( 'published', 'draft', 'deprecated' );
		}
		$this->show_statuses = $show_statuses;

		$list = '';
		$sep = '';
		foreach( $show_statuses as $status )
		{
			$list .= $sep.'\''.$status.'\'';
			$sep = ',';
		}

		$this->WHERE_and( $this->dbprefix.'status IN ('.$list.')' );
	}


	/**
	 * Restrict to specific item types
	 *
	 * @param string List of types to restrict to (must have been previously validated)
	 */
	function where_types( $types )
	{
		$this->types = $types;

		if( empty( $types ) )
		{
			return;
		}

		$list = '';
		$sep = '';
		foreach( $types as $type )
		{
			$list .= $sep.'\''.$type.'\'';
			$sep = ',';
		}

		$this->WHERE_and( $this->dbprefix.'type IN ('.$list.')' );
	}


	/**
	 * Restrict with keywords
	 *
	 * @param string Keyword search string
	 * @param mixed Search for entire phrase or for individual words
	 * @param mixed Require exact match of author or contents
	 */
	function where_keywords( $keywords, $phrase, $exact )
	{
		global $DB;

		$this->keywords = $keywords;
		$this->phrase = $phrase;
		$this->exact = $exact;

		if( empty($keywords) )
		{
			return;
		}

		$search = '';

		if( $exact )
		{	// We want exact match of author or contents
			$n = '';
		}
		else
		{ // The words/sentence are/is to be included in in the author or the contents
			$n = '%';
		}

		if( ($phrase == '1') or ($phrase == 'sentence') )
		{ // Sentence search
			$keywords = $DB->escape(trim($keywords));
			$search .= '('.$this->dbprefix.'author LIKE \''. $n. $keywords. $n. '\') OR ('.$this->dbprefix.'content LIKE \''. $n. $keywords. $n.'\')';
		}
		else
		{ // Word search
			if( strtoupper( $phrase ) == 'OR' )
				$swords = 'OR';
			else
				$swords = 'AND';

			// puts spaces instead of commas
			$keywords = preg_replace('/, +/', '', $keywords);
			$keywords = str_replace(',', ' ', $keywords);
			$keywords = str_replace('"', ' ', $keywords);
			$keywords = trim($keywords);
			$keyword_array = explode(' ',$keywords);
			$join = '';
			for ( $i = 0; $i < count($keyword_array); $i++)
			{
				$search .= ' '. $join. ' ( ('.$this->dbprefix.'author LIKE \''. $n. $DB->escape($keyword_array[$i]). $n. '\')
																OR ('.$this->dbprefix.'content LIKE \''. $n. $DB->escape($keyword_array[$i]). $n.'\') ) ';
				$join = $swords;
			}
		}

		//echo $search;
		$this->WHERE_and( $search );
	}


	/**
	 * Restrict to specific blog
	 *
	 * @param integer blog to restrict to
	 */
	function blog_restrict( $Blog )
	{
		$this->Blog = $Blog;

		if( empty($Blog) )
		{
			return;
		}

		$this->FROM_add( 'INNER JOIN T_postcats ON '.$this->dbprefix.'post_ID = postcat_post_ID
						 INNER JOIN T_categories othercats ON postcat_cat_ID = othercats.cat_ID ' );

		$this->WHERE_and( $Blog->get_sql_where_aggregate_coll_IDs('othercats.cat_blog_ID') );
	}

}


/*
 * $Log$
 * Revision 1.8  2011/09/04 22:13:15  fplanque
 * copyright 2011
 *
 * Revision 1.7  2010/07/26 06:52:16  efy-asimo
 * MFB v-4-0
 *
 */
?>