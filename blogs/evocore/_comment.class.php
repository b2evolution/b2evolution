<?php
/**
 * This file implements the Comment class.
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
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER.
 * @author fplanque: Francois PLANQUE
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

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
	var $Item;
	var $author_User;
	var $type;
	var $status;
	var $author;
	var $author_email;
	var $author_url;
	var $author_IP;
	var $date;
	var $content;

	/**
	 * Comment::Comment(-)
	 *
	 * Constructor
	 */
	function Comment( $db_row = NULL )
	{
		global $ItemCache, $UserCache;

		// Call parent constructor:
		parent::DataObject( 'T_comments', 'comment_', 'comment_ID' );

		if( $db_row == NULL )
		{
			// echo 'null comment';
		}
		else
		{
			$this->ID = $db_row['comment_ID'];

			// Get parent Item
			$this->Item = & $ItemCache->get_by_ID(  $db_row['comment_post_ID'] );

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
			$this->author_IP = $db_row['comment_author_IP'];
			$this->date = $db_row['comment_date'];
			$this->content = $db_row['comment_content'];
		}
	}


	/**
	 * Set param value
	 *
	 * @param string parameter name
	 * @param mixed parameter value
	 * @return boolean true, if a value has been set; false if it has not changed
	 */
	function set( $parname, $parvalue )
	{
		switch( $parname )
		{
			default:
				return parent::set_param( $parname, 'string', $parvalue );
		}
	}


	/**
	 * Set Item this comment relates to
	 */
	function set_Item( & $Item )
	{
		$this->Item = & $Item;
		parent::set_param( 'post_ID', 'number', $Item->ID );
	}


	/**
	 * Set author User of this comment
	 */
	function set_author_User( & $author_User )
	{
		$this->author_User = & $author_User;
		parent::set_param( 'author_ID', 'number', $author_User->ID );
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
			$this->author_User->preferred_name( $format );
			echo $after_user;
		}
		else
		{ // Display info recorded at edit time:
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
	 * @param string String to display before IP, if IP exists
	 * @param string String to display after IP, if IP exists
	 */
	function author_ip( $before='', $after='' )
	{
		if( !empty( $this->author_IP ) )
		{
			echo $before;
			echo $this->author_IP;
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
		{ // If email exists:
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
		{ // If URL exists:
			echo $before;
			if( $makelink ) echo '<a href="'.$url.'" rel="nofollow">';
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
		{ // If User has no permission to edit comments:
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
		{ // If User has permission to edit comments:
			return false;
		}

		if( $text == '#' ) $text = T_('Delete');
		if( $title == '#' ) $title = T_('Delete this comment');

		$url = $admin_url.'edit_actions.php?action=deletecomment&amp;comment_ID='.$this->ID;

		echo $before;
		if( $button )
		{ // Display as button
			echo '<input type="button"';
			echo ' value="'.$text.'" title="'.$title.'" onclick="if ( confirm(\'';
			echo TS_('You are about to delete this comment!\\n\'Cancel\' to stop, \'OK\' to delete.');
			echo '\') ) { document.location.href=\''.$url.'\' }"';
			if( !empty( $class ) ) echo ' class="'.$class.'"';
			echo '/>';
		}
		else
		{ // Display as link
			echo '<a href="'.$url.'" title="'.$title.'" onclick="return confirm(\'';
			echo TS_('You are about to delete this comment!\\n\'Cancel\' to stop, \'OK\' to delete.');
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
		{ // This comment is from a registered user:
			if( empty($this->author_User->email) )
			{ // We have no email for this Author :(
				return false;
			}
			$form_url = url_add_param( $form_url, 'recipient_id='.$this->author_User->ID );
		}
		else
		{ // This comment is from a visitor:
			if( empty($this->author_email) )
			{ // We have no email for this comment :(
				return false;
			}
		}

		$form_url = url_add_param( $form_url, 'comment_id='.$this->ID );
		$form_url = url_add_param( $form_url, 'post_id='.$this->Item->ID );

		if( $title == '#' ) $title = T_('Send email to comment author');
		if( $text == '#' ) $text = get_icon( 'email', 'imgtag', array( 'class' => 'middle', 'title' => $title ) );

		echo $before;
		echo '<a href="'.$form_url.'" title="'.$title.'"';
		if( !empty( $class ) ) echo ' class="'.$class.'"';
		echo '>'.$text.'</a>';
		echo $after;

		return true;
	}


	/**
	 * Generate permalink to this comment.
	 *
	 * @param string 'urltitle', 'pid', 'archive#id' or 'archive#title'
	 * @param string url to use
	 */
	function get_permalink( $mode = '', $blogurl='' )
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

		$post_permalink = $this->Item->get_permalink( $mode, $blogurl );
		return $post_permalink.'#c'.$this->ID;
	}


	/**
	 * Template function: display permalink to this comment
	 *
	 * @param string 'urltitle', 'pid', 'archive#id' or 'archive#title'
	 * @param string url to use
	 */
	function permalink( $mode = '', $blogurl='' )
	{
		echo $this->get_permalink( $mode, $blogurl );
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


	/**
	 * Send email notifications to subscribed users:
	 *
	 * @todo shall we notify suscribers of blog were this is in extra-cat?
	 * @todo cache message by locale
	 * @todo Indicator in url to see where the user came from (&from=subnote ["subscription notification") - Problem: too long urls.
	 */
	function send_email_notifications()
	{
		global $DB, $admin_url, $debug, $Debuglog;

		// Get list of users who want to be notfied:
		// TODO: also use extra cats/blogs??
		$sql = 'SELECT DISTINCT user_email, user_locale
							FROM T_subscriptions INNER JOIN T_users ON sub_user_ID = user_ID
						 WHERE sub_coll_ID = '.$this->Item->blog_ID.'
						   AND sub_comments <> 0
						   AND LENGTH(TRIM(user_email)) > 0';
		$notify_list = $DB->get_results( $sql );

		// Preprocess list:
		$notify_array = array();
		foreach( $notify_list as $notification )
		{
			$notify_array[$notification->user_email] = $notification->user_locale;
		}

		// Check if we need to add the author:
		$item_author_User = & $this->Item->Author;
		if( $item_author_User->notify
				&& ( ! empty( $item_author_User->email ) ) )
		{ // Author wants to be notified:
			$notify_array[$item_author_User->email] = $item_author_User->locale;
		}

		if( ! count($notify_array) )
		{ // No-one to notify:
			return false;
		}

		/*
		 * We have a list of email addresses to notify:
		 */
		if( !is_null( $this->author_User ) )
		{ // Comment from a registered user:
			$mail_from = '"'.$this->author_User->get('preferredname').'" <'.$this->author_User->get('email').'>';
		}
		elseif( empty( $email ) )
		{
			global $notify_from;
			$mail_from = $notify_from;
		}
		else
		{
			$mail_from = "\"$this->author\" <$this->author_email>";
		}

		$Blog = & $this->Item->getBlog();

		// Send emails:
		foreach( $notify_array as $notify_email => $notify_locale )
		{
			locale_temp_switch($notify_locale);

			switch( $this->type )
			{
				case 'trackback':
					/* TRANS: Subject of the mail to send on new trackbacks. First %s is the blog's shortname, the second %s is the item's title. */
					$subject = T_('[%s] New trackback on "%s"');
					break;

				default:
					/* TRANS: Subject of the mail to send on new comments. First %s is the blog's shortname, the second %s is the item's title. */
					$subject = T_('[%s] New comment on "%s"');
			}

			$subject = sprintf( $subject, $Blog->get('shortname'), $this->Item->get('title') );

			$notify_message = T_('Blog').': '.$Blog->get('shortname')
				.' ( '.str_replace('&amp;', '&', $Blog->get('blogurl'))." )\n"
				.T_('Post').': '.$this->Item->get('title')
				.' ( '.str_replace('&amp;', '&', $this->Item->get_permalink( 'pid' ))." )\n";
				// We use pid to get a short URL and avoid it to wrap on a new line in the mail which may prevent people from clicking

			switch( $this->type )
			{
				case 'trackback':
					$user_domain = gethostbyaddr($this->author_IP);
					$notify_message .= T_('Website').": $this->author (IP: $this->author_IP, $user_domain)\n";
					$notify_message .= T_('Url').": $this->author_url\n";
					break;

				default:
					if( !is_null( $this->author_User ) )
					{ // Comment from a registered user:
						$notify_message .= T_('Author').': '.$this->author_User->get('preferredname').' ('.$this->author_User->get('login').")\n";
					}
					else
					{ // Comment from visitor:
						$user_domain = gethostbyaddr($this->author_IP);
						$notify_message .= T_('Author').": $this->author (IP: $this->author_IP, $user_domain)\n";
						$notify_message .= T_('Email').": $this->author_email\n";
						$notify_message .= T_('Url').": $this->author_url\n";
					}
			}

			$notify_message .=
				T_('Comment').': '.str_replace('&amp;', '&', $this->get_permalink( 'pid' ))."\n" // We use pid to get a short URL and avoid it to wrap on a new line in the mail which may prevent people from clicking
				.$this->get('content')."\n\n"
				.T_('Edit/Delete').': '.$admin_url.'b2browse.php?blog='.$this->Item->blog_ID.'&p='.$this->Item->ID."&c=1\n\n"
				.T_('Edit your subscriptions/notifications').': '.str_replace('&amp;', '&', url_add_param( $Blog->get( 'blogurl' ), 'disp=subs' ) )."\n";

			if( $debug )
			{
				$mail_dump = "Sending notification to $notify_email:<pre>Subject: $subject\n$notify_message</pre>";

				if( $debug >= 2 )
				{ // output mail content - NOTE: this will kill sending of headers.
					echo "<p>$mail_dump</p>";
				}

				$Debuglog->add( $mail_dump, 'notification' );
			}

			send_mail( $notify_email, $subject, $notify_message, $mail_from );

			locale_restore_previous();
		}
	}


	/**
	 * Get karma and set it before adding the Comment to DB.
	 *
	 * @return boolean true on success
	 */
	function dbinsert()
	{
		global $Plugins;

		$spam_karma = $Plugins->trigger_karma_collect( 'GetKarmaForComment', array( 'Comment' => & $this ) );

		$this->set( 'spam_karma', $spam_karma );

		return parent::dbinsert();
	}

}
/*
 * $Log$
 * Revision 1.23  2006/01/26 23:08:35  blueyed
 * Plugins enhanced.
 *
 * Revision 1.22  2005/12/12 19:21:21  fplanque
 * big merge; lots of small mods; hope I didn't make to many mistakes :]
 *
 * Revision 1.21  2005/12/11 19:59:51  blueyed
 * Renamed gen_permalink() to get_permalink()
 *
 * Revision 1.20  2005/11/04 21:42:22  blueyed
 * Use setter methods to set parameter values! dataobject::set_param() won't pass the parameter to dbchange() if it is already set to the same member value.
 *
 * Revision 1.19  2005/11/04 18:30:59  fplanque
 * no message
 *
 * Revision 1.18  2005/11/04 13:50:57  blueyed
 * Dataobject::set_param() / set(): return true if a value has been set and false if it did not change. It will not get considered for dbchange() then, too.
 *
 * Revision 1.17  2005/10/07 20:18:57  blueyed
 * Added TRANS comments
 *
 * Revision 1.16  2005/10/03 17:26:44  fplanque
 * synched upgrade with fresh DB;
 * renamed user_ID field
 *
 * Revision 1.15  2005/09/29 15:07:30  fplanque
 * spelling
 *
 * Revision 1.14  2005/09/06 17:13:54  fplanque
 * stop processing early if referer spam has been detected
 *
 * Revision 1.13  2005/08/30 18:26:04  fplanque
 * comment spam issues
 *
 * Revision 1.12  2005/08/09 15:22:40  fplanque
 * no message
 *
 * Revision 1.11  2005/08/08 22:35:56  blueyed
 * DEbuglog for send_email_notifications(), whitespace/code layout.
 *
 * Revision 1.10  2005/05/25 18:31:01  fplanque
 * implemented email notifications for new posts
 *
 * Revision 1.9  2005/05/25 17:13:33  fplanque
 * implemented email notifications on new comments/trackbacks
 *
 * Revision 1.8  2005/04/28 20:44:20  fplanque
 * normalizing, doc
 *
 * Revision 1.7  2005/04/12 18:58:16  fplanque
 * use TS_() instead of T_() for JavaScript strings
 *
 * Revision 1.6  2005/04/07 17:55:50  fplanque
 * minor changes
 *
 * Revision 1.5  2005/03/06 16:30:40  blueyed
 * deprecated global table names.
 *
 * Revision 1.4  2005/02/28 09:06:32  blueyed
 * removed constants for DB config (allows to override it from _config_TEST.php), introduced EVO_CONFIG_LOADED
 *
 * Revision 1.3  2004/12/09 21:21:19  fplanque
 * introduced foreign key support
 *
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
?>