<?php
/**
 * This file implements the Comment class.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004 by Daniel HAHLER - {@link http://thequod.de/contact}.
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
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER.
 * @author fplanque: François PLANQUE
 *
 * @version $Id$
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );

/**
 * Includes:
 */
require_once dirname(__FILE__). '/_dataobject.class.php';

/**
 * Comment Class
 *
 * @package evocore
 */
class Comment extends DataObject
{
	/**
	 * @access protected
	 */
	var $Item = NULL;
	var $author_User = NULL;
	var	$type;
	var	$status;
	var	$author;
	var	$author_email;
	var	$author_url;
	var	$author_ip;
	var	$date;
	var	$content;
	var	$karma;

	/**
	 * Comment::Comment(-)
	 *
	 * Constructor
	 */
	function Comment( $db_row = NULL )
	{
		global $tablecomments, $ItemCache, $UserCache;

		// Call parent constructor:
		parent::DataObject( $tablecomments, 'comment_', 'comment_ID' );

		if( $db_row == NULL )
		{
			echo 'null comment';
		}
		else
		{
			$this->ID = $db_row['comment_ID'];

			// Get parent Item
			$this->Item = $ItemCache->get_by_ID(  $db_row['comment_post_ID'] );

			// Get Author User
			$author_ID = $db_row['comment_author_ID'];
			if( !empty($author_ID) )
			{
				$this->author_User = & $UserCache->get_by_ID( $author_ID ); // NO COPY...(?)
			}

			$this->type = $db_row['comment_type'];
			$this->status = $db_row['comment_status'];
			$this->author = $db_row['comment_author'];
			$this->author_email = $db_row['comment_author_email'];
			$url = trim( $db_row['comment_author_url'] );
			$url = preg_replace('#&([^amp\;])#is', '&amp;$1', $url);	// Escape &
			$this->author_url = (!stristr($url, '://')) ? 'http://'.$url : $url;
			$this->author_ip = $db_row['comment_author_IP'];
			$this->date = $db_row['comment_date'];
			$this->content = $db_row['comment_content'];
			$this->karma = $db_row['comment_karma'];
		}
	}

	/**
	 * Comment::set(-)
	 *
	 * Set param value
	 */
	function set( $parname, $parvalue )
	{
		switch( $parname )
		{
			case 'Item':
				die ('coment->Post assignement not handled');

			case 'karma':
				parent::set_param( $parname, 'number', $parvalue );
			break;

			default:
				parent::set_param( $parname, 'string', $parvalue );
		}
	}


	/**
	 * Template function: display anchor for permalinks to refer to
	 *
	 * {@internal Comment::anchor(-) }}
	 */
	function anchor()
	{
		echo '<a name="c'.$this->ID.'"></a>';
	}


	/**
	 * Template function: display author of comment
	 *
	 * {@internal Comment::author(-) }}
	 *
	 * @param string String to display before author name if not a user
	 * @param string String to display after author name if not a user
	 * @param string String to display before author name if he's a user
	 * @param string String to display after author name if he's a user
	 * @param string Output format, see {@link format_to_output()}
	 * @param boolean true for link, false if you want NO html link
	 */
	function author( $before = '', $after = '#', $before_user = '', $after_user = '#',
										$format = 'htmlbody', $makelink = false )
	{
		if( $this->author_User !== NULL )
		{ // Author is a user
			if( $after_user == '#' ) $after_user = ' ['.T_('Member').']';
			echo $before_user;
			$this->author_User->prefered_name( $format );
			echo $after_user;
		}
		else
		{	// Display info recorded at edit time:
			if( strlen( $this->author_url ) <= 10 ) $makelink = false;
			if( $after == '#' ) $after = ' ['.T_('Visitor').']';
			echo $before;
			if( $makelink ) echo '<a href="'.$this->author_url.'">';
			$this->disp( 'author', $format );
			if( $makelink ) echo '</a>';
			echo $after;
		}
	}


	/**
	 * Template function: display comment's author's IP
	 *
	 * {@internal Comment::author_ip(-) }}
	 *
	 * @param string String to display before IP, if IP exists
	 * @param string String to display after IP, if IP exists
	 */
	function author_ip( $before='', $after='' )
	{
		if( !empty( $this->author_ip ) )
		{
			echo $before;
			echo $this->author_ip;
			echo $after;
		}
	}


	/**
	 * Template function: display link to comment author's provided email
	 *
	 * {@internal Comment::author_email(-) }}
	 *
	 * @param string String to display for link: leave empty to display email
	 * @param string String to display before email, if email exists
	 * @param string String to display after email, if email exists
	 * @param boolean false if you want NO html link
	 */
	function author_email( $linktext='', $before='', $after='', $makelink = true )
	{
		if( $this->author_User !== NULL )
		{ // Author is a user
			$email = $this->author_User->get('email');
		}
		else
		{
			$email = $this->author_email;
		}

		if( strlen( $email ) > 5 )
		{	// If email exists:
			echo $before;
			if( $makelink ) echo '<a href="mailto:'.$email.'">';
			echo ($linktext != '') ? $linktext : $email;
			if( $makelink ) echo '</a>';
			echo $after;
		}
	}


	/**
	 * Template function: display link to comment author's provided URL
	 *
	 * {@internal Comment::author_url(-) }}
	 *
	 * @param string String to display for link: leave empty to display URL
	 * @param string String to display before link, if link exists
	 * @param string String to display after link, if link exists
	 * @param boolean false if you want NO html link
	 * @return boolean true if URL has been displayed
	 */
	function author_url( $linktext='', $before='', $after='', $makelink = true )
	{
		if( $this->author_User !== NULL )
		{ // Author is a user
			$url = $this->author_User->get('url');
		}
		else
		{
			$url = $this->author_url;
		}

		if( strlen( $url ) > 10 )
		{	// If URL exists:
			echo $before;
			if( $makelink ) echo '<a href="'.$url.'">';
			echo ($linktext != '') ? $linktext : $url;
			if( $makelink ) echo '</a>';
			echo $after;
			return true;
		}

		return false;
	}


	/**
	 * Provide link to edit a comment if user has edit rights
	 *
	 * {@internal Comment::edit_link(-)}}
	 *
	 * @param string to display before link
	 * @param string to display after link
	 * @param string link text
	 * @param string link title
	 * @param string class name
	 */
	function edit_link( $before = ' ', $after = ' ', $text = '#', $title = '#', $class = '' )
	{
		global $current_User, $admin_url;

		if( ! is_logged_in() ) return false;

		if( ! $current_User->check_perm( 'blog_comments', '', false, $this->Item->get( 'blog_ID' ) ) )
		{	// If User has no permission to edit comments:
			return false;
		}

		if( $text == '#' ) $text = T_('Edit');
		if( $title == '#' ) $title = T_('Edit this comment');

		echo $before;
		echo '<a href="'.$admin_url.'b2edit.php?action=editcomment&amp;comment='.$this->ID;
		echo '" title="'.$title.'"';
		if( !empty( $class ) ) echo ' class="'.$class.'"';
		echo '>'.$text.'</a>';
		echo $after;

		return true;
	}


	/**
	 * Displays button for deleeing the Comment if user has proper rights
	 *
	 * {@internal Comment::delete_link(-)}}
	 *
	 * @param string to display before link
	 * @param string to display after link
	 * @param string link text
	 * @param string link title
	 * @param string class name
	 * @param boolean true to make this a button instead of a link
	 */
	function delete_link( $before = ' ', $after = ' ', $text = '#', $title = '#', $class = '', $button  = false )
	{
		global $current_User, $admin_url;

 		if( ! is_logged_in() ) return false;

	 	if( ! $current_User->check_perm( 'blog_comments', '', false, $this->Item->get( 'blog_ID' ) ) )
		{	// If User has permission to edit comments:
			return false;
		}

		if( $text == '#' ) $text = T_('Delete');
		if( $title == '#' ) $title = T_('Delete this comment');

		$url = $admin_url.'edit_actions.php?action=deletecomment&amp;comment_ID='.$this->ID;

		echo $before;
		if( $button )
		{	// Display as button
			echo '<input type="button"';
			echo ' value="'.$text.'" title="'.$title.'" onclick="if ( confirm(\'';
			/* TRANS: Warning this is a javascript string */
			echo T_('You are about to delete this comment!\\n\\\'Cancel\\\' to stop, \\\'OK\\\' to delete.');
			echo '\') ) { document.location.href=\''.$url.'\' }"';
			if( !empty( $class ) ) echo ' class="'.$class.'"';
			echo '/>';
		}
		else
		{	// Display as link
			echo '<a href="'.$url.'" title="'.$title.'" onclick="return confirm(\'';
			/* TRANS: Warning this is a javascript string */
			echo T_('You are about to delete this comment!\\n\\\'Cancel\\\' to stop, \\\'OK\\\' to delete.');
			echo '\')"';
			if( !empty( $class ) ) echo ' class="'.$class.'"';
			echo '>'.$text.'</a>';
		}
		echo $after;

		return true;
	}


	/**
	 * Provide link to message form for this comment's author
	 *
	 * {@internal Comment::msgform_link(-)}}
	 *
	 * @param string url of the message form
	 * @param string to display before link
	 * @param string to display after link
	 * @param string link text
	 * @param string link title
	 * @param string class name
	 */
	function msgform_link( $form_url, $before = ' ', $after = ' ', $text = '#', $title = '#', $class = '' )
	{
		global $img_url;

		if( $this->author_User !== NULL )
		{	// This comment is from a registered user:
			if( empty($this->author_User->email) )
			{	// We have no email for this Author :(
				return false;
			}
			$form_url = url_add_param( $form_url, 'recipient_id='.$this->author_User->ID );
		}
		else
		{	// This comment is from a visitor:
			if( empty($this->author_email) )
			{	// We have no email for this comment :(
				return false;
			}
		}

		$form_url = url_add_param( $form_url, 'comment_id='.$this->ID );
		$form_url = url_add_param( $form_url, 'post_id='.$this->Item->ID );

		if( $text == '#' ) $text = '<img src="'.$img_url.'envelope.gif" height="10" width="13" class="middle" alt="'.T_('EMail').'" />';
		if( $title == '#' ) $title = T_('Send email to comment author');

		echo $before;
		echo '<a href="'.$form_url.'" title="'.$title.'"';
		if( !empty( $class ) ) echo ' class="'.$class.'"';
		echo '>'.$text.'</a>';
		echo $after;

		return true;
	}


	/**
	 * Template function: display permalink to this comment
	 *
	 * {@internal Comment::permalink(-) }}
	 *
	 * @param string 'urltitle', 'pid', 'archive#id' or 'archive#title'
	 * @param string url to use
	 */
	function permalink( $mode = '', $blogurl='' )
	{
		global $Settings;

		if( empty( $mode ) )
			$mode = $Settings->get( 'permalink_type' );

		// some permalink modes are not acceptable here:
		switch( $mode )
		{
			case 'archive#id':
			case 'archive#title':
			  $mode = 'pid';
		}

		$post_permalink = $this->Item->gen_permalink( $mode, $blogurl );
		echo $post_permalink.'#c'.$this->ID;
	}

	/**
	 * Template function: display content of comment
	 *
	 * {@internal Comment::content(-) }}
	 *
	 * @param string Output format, see {@link format_to_output()}
	 */
	function content( $format = 'htmlbody' )
	{
		$comment = $this->content;
		$comment = str_replace('<trackback />', '', $comment);
		$comment = str_replace('<pingback />', '', $comment);
		$comment = format_to_output( $comment, $format );
		echo $comment;
	}

	/**
	 * Template function: display date (datetime) of comment
	 *
	 * {@internal Comment::date(-) }}
	 *
	 * @param string date/time format: leave empty to use locale default date format
	 * @param boolean true if you want GMT
	 */
	function date( $format='', $useGM = false )
	{
		if( empty($format) )
			echo mysql2date( locale_datefmt(), $this->date, $useGM);
		else
			echo mysql2date( $format, $this->date, $useGM);
	}

	/**
	 * Template function: display time (datetime) of comment
	 *
	 * {@internal Comment::time(-) }}
	 *
	 * @param string date/time format: leave empty to use locale default time format
	 * @param boolean true if you want GMT
	 */
	function time( $format='', $useGM = false )
	{
		if( empty($format) )
			echo mysql2date( locale_timefmt(), $this->date, $useGM );
		else
			echo mysql2date( $format, $this->date, $useGM );
	}


/*
 * $Log$
 * Revision 1.2  2004/10/14 18:31:25  blueyed
 * granting copyright
 *
 * Revision 1.1  2004/10/13 22:46:32  fplanque
 * renamed [b2]evocore/*
 *
 * Revision 1.33  2004/10/11 19:13:14  fplanque
 * Edited code documentation.
 *
 * Revision 1.32  2004/10/11 19:02:04  fplanque
 * Edited code documentation.
 *
 */
}
?>