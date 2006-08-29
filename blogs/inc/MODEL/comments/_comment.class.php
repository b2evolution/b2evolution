<?php
/**
 * This file implements the Comment class.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * {@internal Open Source relicensing agreement:
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
require_once dirname(__FILE__).'/../dataobjects/_dataobject.class.php';

/**
 * Comment Class
 *
 * @package evocore
 */
class Comment extends DataObject
{
	/**
	 * The item (parent) of this Comment (lazy-filled).
	 * @see Comment::get_Item()
	 * @see Comment::set_Item()
	 * @access protected
	 * @var Item
	 */
	var $Item;
	/**
	 * @var integer The ID of the comment's Item.
	 */
	var $item_ID;
	/**
	 * The comment's user, this is NULL for (anonymous) visitors (lazy-filled).
	 * @see Comment::get_author_User()
	 * @see Comment::set_author_User()
	 * @access protected
	 * @var User
	 */
	var $author_User;
	/**
	 * @var integer|NULL The ID of the author's user. NULL for anonymous visitors.
	 */
	var $author_ID;
	/**
	 * @var string Comment type: 'comment', 'linkback', 'trackback' or 'pingback'
	 */
	var $type;
	/**
	 * @var string Comment visibility status: 'published', 'deprecated', 'protected', 'private' or 'draft'
	 */
	var $status;
	/**
	 * @var string Name of the (anonymous) visitor (if any).
	 */
	var $author;
	/**
	 * @var string Email address of the (anonymous) visitor (if any).
	 */
	var $author_email;
	/**
	 * @var string URL/Homepage of the (anonymous) visitor (if any).
	 */
	var $author_url;
	/**
	 * @var string IP address of the comment's author (while posting).
	 */
	var $author_IP;
	/**
	 * @var string Date of the comment (MySQL DATETIME - use e.g. {@link mysql2timestamp()}); local time ({@link $localtimenow})
	 */
	var $date;
	/**
	 * @var string
	 */
	var $content;
	/**
	 * @var integer Spam karma of the comment (0-100), 0 being "probably no spam at all"
	 */
	var $spam_karma;
	/**
	 * @var boolean Does an anonymous commentator allow to send messages through a message form?
	 */
	var $allow_msgform;


	/**
	 * Constructor
	 */
	function Comment( $db_row = NULL )
	{
		// Call parent constructor:
		parent::DataObject( 'T_comments', 'comment_', 'comment_ID' );

		if( $db_row == NULL )
		{
			// echo 'null comment';
		}
		else
		{
			$this->ID = $db_row['comment_ID'];
			$this->item_ID = $db_row['comment_post_ID'];
			if( ! empty($db_row['comment_author_ID']) )
			{
				$this->author_user_ID = $db_row['comment_author_ID'];
			}
			$this->type = $db_row['comment_type'];
			$this->status = $db_row['comment_status'];
			$this->author = $db_row['comment_author'];
			$this->author_email = $db_row['comment_author_email'];
			$url = trim( $db_row['comment_author_url'] );
			$url = preg_replace('#&(?!amp;)#is', '&amp;', $url); // Escape &
			$this->author_url = (!stristr($url, '://')) ? 'http://'.$url : $url;
			$this->author_IP = $db_row['comment_author_IP'];
			$this->date = $db_row['comment_date'];
			$this->content = $db_row['comment_content'];
			$this->spam_karma = $db_row['comment_spam_karma'];
			$this->allow_msgform = $db_row['comment_allow_msgform'];
		}
	}


	/**
	 * Get the author User of the comment. This is NULL for anonymous visitors.
	 *
	 * @return User
	 */
	function & get_author_User()
	{
		if( isset($this->author_user_ID) && ! isset($this->author_User) )
		{
			$UserCache = & get_Cache( 'UserCache' );
			$this->author_User = & $UserCache->get_by_ID( $this->author_user_ID );
		}

		return $this->author_User;
	}


	/**
	 * Get the Item this comment relates to
	 *
	 * @return Item
	 */
	function & get_Item()
	{
		if( ! isset($this->Item) )
		{
			$ItemCache = & get_Cache( 'ItemCache' );
			$this->Item = & $ItemCache->get_by_ID( $this->item_ID );
		}

		return $this->Item;
	}


	/**
	 * Get a member param by its name
	 *
	 * @param mixed Name of parameter
	 * @return mixed Value of parameter
	 */
	function get( $parname )
	{
		global $post_statuses;

		switch( $parname )
		{
			case 't_status':
				// Text status:
				return T_( $post_statuses[$this->status] );
		}

		return parent::get( $parname );
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
	 * @param Item
	 */
	function set_Item( & $Item )
	{
		$this->Item = & $Item;
		$this->item_ID = $Item->ID;
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
	 * Set the spam karma, as a number.
	 * @param integer Spam karma (-100 - 100)
	 * @access protected
	 */
	function set_spam_karma( $spam_karma )
	{
		return parent::set_param( 'spam_karma', 'number', $spam_karma );
	}


	/**
	 * Get the anchor-ID of the comment
	 *
	 * @return string
	 */
	function get_anchor()
	{
		return 'c'.$this->ID;
	}


	/**
	 * Template function: display anchor for permalinks to refer to
	 */
	function anchor()
	{
		echo '<a id="'.$this->get_anchor().'"></a>';
	}


	/**
	 * Get the comment author's name.
	 *
	 * @return string
	 */
	function get_author_name()
	{
		if( $this->get_author_User() )
		{
			return $this->author_User->preferred_name( 'raw', false );
		}
		else
		{
			return $this->author;
		}
	}


	/**
	 * Get the EMail of the comment's author.
	 *
	 * @return string
	 */
	function get_author_email()
	{
		if( $this->get_author_User() )
		{ // Author is a user
			return $this->author_User->get('email');
		}
		else
		{
			return $this->author_email;
		}
	}


	/**
	 * Get the URL of the comment's author.
	 *
	 * @return string
	 */
	function get_author_url()
	{
		if( $this->get_author_User() )
		{ // Author is a user
			return $this->author_User->get('url');
		}
		else
		{
			return $this->author_url;
		}
	}


	/**
	 * Template function: display author of comment
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
		global $Plugins;

		$r = '';

		if( $this->get_author_User() )
		{ // Author is a user
			if( strlen( $this->author_User->url ) <= 10 ) $makelink = false;
			if( $after_user == '#' ) $after_user = ' ['.T_('Member').']';
			$r .= $before_user;
			if( $makelink ) $r .= '<a href="'.$this->author_User->url.'">';
			$r .= $this->author_User->preferred_name( $format, false );
			if( $makelink ) $r .= '</a>';
			$r .= $after_user;
		}
		else
		{ // Display info recorded at edit time:
			if( strlen( $this->author_url ) <= 10 ) $makelink = false;
			if( $after == '#' ) $after = ' ['.T_('Visitor').']';
			$r .= $before;

			if( $makelink ) $r .= '<a href="'.$this->author_url.'">';
			$r .= $this->dget( 'author', $format );
			if( $makelink ) $r .= '</a>';
			$r .= $after;
		}

		$Plugins->trigger_event( 'FilterCommentAuthor', array( 'data' => & $r, 'makelink' => $makelink, 'Comment' => $this ) );

		echo $r;
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
	 * @param string String to display for link: leave empty to display email
	 * @param string String to display before email, if email exists
	 * @param string String to display after email, if email exists
	 * @param boolean false if you want NO html link
	 */
	function author_email( $linktext='', $before='', $after='', $makelink = true )
	{
		$email = $this->get_author_email();

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
	 * @param string String to display for link: leave empty to display URL
	 * @param string String to display before link, if link exists
	 * @param string String to display after link, if link exists
	 * @param boolean false if you want NO html link
	 * @return boolean true if URL has been displayed
	 */
	function author_url( $linktext='', $before='', $after='', $makelink = true )
	{
		global $Plugins;

		$url = $this->get_author_url();

		if( strlen( $url ) < 10 )
		{
			return false;
		}

		// If URL exists:
		$r = $before;
		if( $makelink )
		{
			$r .= '<a href="'.$url.'">';
		}
		$r .= ( empty($linktext) ? $url : $linktext );
		if( $makelink ) $r .= '</a>';
		$r .= $after;

		$Plugins->trigger_event( 'FilterCommentAuthorUrl', array( 'data' => & $r, 'makelink' => $makelink, 'Comment' => $this ) );

		echo $r;
		return true;
	}


	/**
	 * Template function: display spam karma of the comment (in percent)
	 *
	 * "%s" gets replaced by the karma value
	 *
	 * @param string Template string to display, if we have a karma value
	 * @param string Template string to display, if we have no karma value (pre-Phoenix)
	 */
	function spam_karma( $template = '%s%', $template_unknown = NULL )
	{
		if( isset($this->spam_karma) )
		{
			echo str_replace( '%s', $this->spam_karma, $template );
		}
		else
		{
			if( ! isset($template_unknown) )
			{
				echo /* TRANS: "not available" */ T_('N/A');
			}
			else
			{
				echo $template_unknown;
			}
		}
	}


	/**
	 * Provide link to edit a comment if user has edit rights
	 *
	 * @param string to display before link
	 * @param string to display after link
	 * @param string link text
	 * @param string link title
	 * @param string class name
	 * @return boolean
	 */
	function edit_link( $before = ' ', $after = ' ', $text = '#', $title = '#', $class = '' )
	{
		global $current_User, $admin_url;

		if( ! is_logged_in() ) return false;

		$this->get_Item();

		if( ! $current_User->check_perm( 'blog_comments', '', false, $this->Item->get( 'blog_ID' ) ) )
		{ // If User has no permission to edit comments:
			return false;
		}

		if( $text == '#' ) $text = get_icon( 'edit' ).' '.T_('Edit...');
		if( $title == '#' ) $title = T_('Edit this comment');

		echo $before;
		echo '<a href="'.$admin_url.'?ctrl=edit&amp;action=editcomment&amp;comment='.$this->ID;
		echo '" title="'.$title.'"';
		if( !empty( $class ) ) echo ' class="'.$class.'"';
		echo '>'.$text.'</a>';
		echo $after;

		return true;
	}


	/**
	 * Displays button for deleeing the Comment if user has proper rights
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

		$this->get_Item();

		if( ! $current_User->check_perm( 'blog_comments', '', false, $this->Item->get( 'blog_ID' ) ) )
		{ // If User has permission to edit comments:
			return false;
		}

		if( $text == '#' )
		{ // Use icon+text as default, if not displayed as button (otherwise just the text)
			if( ! $button )
			{
				$text = get_icon( 'delete', 'imgtag' ).' '.T_('Delete!');
			}
			else
			{
				$text = T_('Delete!');
			}
		}
		if( $title == '#' ) $title = T_('Delete this comment');

		$url = $admin_url.'?ctrl=editactions&amp;action=deletecomment&amp;comment_ID='.$this->ID;

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
	 * Provide link to deprecate a comment if user has edit rights
	 *
	 * @param string to display before link
	 * @param string to display after link
	 * @param string link text
	 * @param string link title
	 * @param string class name
	 * @param string glue between url params
	 * @param boolean save context?
	 */
	function get_deprecate_link( $before = ' ', $after = ' ', $text = '#', $title = '#', $class = '', $glue = '&amp;', $save_context = false )
	{
		global $current_User, $admin_url;

		if( ! is_logged_in() ) return false;

		$this->get_Item();

		if( ($this->status == 'deprecated') // Already deprecateded!
			|| ! $current_User->check_perm( 'blog_comments', '', false, $this->Item->get( 'blog_ID' ) ) )
		{ // If User has permission to edit comments:
			return false;
		}

		if( $text == '#' ) $text = get_icon( 'deprecate', 'imgtag' ).' '.T_('Deprecate!');
		if( $title == '#' ) $title = T_('Deprecate this comment!');

		$r = $before;
		$r .= '<a href="';
		//if( $save_context )
		{
		}
		//else
		{
			$r .= $admin_url.'?ctrl=editactions'.$glue.'action=deprecate_comment'.$glue.'comment_ID='.$this->ID;
		}
		$r .= '" title="'.$title.'"';
		if( !empty( $class ) ) $r .= ' class="'.$class.'"';
		$r .= '>'.$text.'</a>';
		$r .= $after;

		return $r;
	}


	/**
	 * Display link to deprecate a comment if user has edit rights
	 *
	 * @param string to display before link
	 * @param string to display after link
	 * @param string link text
	 * @param string link title
	 * @param string class name
	 * @param string glue between url params
	 * @param boolean save context?
	 */
	function deprecate_link( $before = ' ', $after = ' ', $text = '#', $title = '#', $class = '', $glue = '&amp;', $save_context = false )
	{
		echo $this->get_deprecate_link( $before, $after, $text, $title, $class, $glue, $save_context );
	}


	/**
	 * Provide link to publish a comment if user has edit rights
	 *
	 * @param string to display before link
	 * @param string to display after link
	 * @param string link text
	 * @param string link title
	 * @param string class name
	 * @param string glue between url params
	 * @param boolean save context?
	 */
	function get_publish_link( $before = ' ', $after = ' ', $text = '#', $title = '#', $class = '', $glue = '&amp;', $save_context = false )
	{
		global $current_User, $admin_url;

		if( ! is_logged_in() ) return false;

		$this->get_Item();

		if( ($this->status == 'published') // Already published!
			|| ! $current_User->check_perm( 'blog_comments', '', false, $this->Item->get( 'blog_ID' ) ) )
		{ // If User has permission to edit comments:
			return false;
		}

		if( $text == '#' ) $text = get_icon( 'publish', 'imgtag' ).' '.T_('Publish!');
		if( $title == '#' ) $title = T_('Publish this comment!');

		$r = $before;
		$r .= '<a href="';
		//if( $save_context )
		{
		}
		//else
		{
			$r .= $admin_url.'?ctrl=editactions'.$glue.'action=publish_comment'.$glue.'comment_ID='.$this->ID;
		}
		$r .= '" title="'.$title.'"';
		if( !empty( $class ) ) $r .= ' class="'.$class.'"';
		$r .= '>'.$text.'</a>';
		$r .= $after;

		return $r;
	}


	/**
	 * Display link to publish a comment if user has edit rights
	 *
	 * @param string to display before link
	 * @param string to display after link
	 * @param string link text
	 * @param string link title
	 * @param string class name
	 * @param string glue between url params
	 * @param boolean save context?
	 */
	function publish_link( $before = ' ', $after = ' ', $text = '#', $title = '#', $class = '', $glue = '&amp;', $save_context = false )
	{
		echo $this->get_publish_link( $before, $after, $text, $title, $class, $glue, $save_context );
	}


	/**
	 * Provide link to message form for this comment's author
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
		if( $this->get_author_User() )
		{ // This comment is from a registered user:
			if( empty($this->author_User->email) )
			{ // We have no email for this Author :(
				return false;
			}
			elseif( empty($this->author_User->allow_msgform) )
			{ // User does not allow message form
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
			elseif( empty($this->allow_msgform) )
			{ // Anonymous commentator does not allow message form (for this comment)
				return false;
			}
		}

		$form_url = url_add_param( $form_url, 'comment_id='.$this->ID.'&amp;post_id='.$this->item_ID.'&amp;redirect_to='.rawurlencode(regenerate_url()) );

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
	 * Note: This actually only returns the URL, to get a real link, use Comment::get_permanent_link()
	 *
	 * @param string 'urltitle', 'pid', 'archive#id' or 'archive#title'
	 * @param string url to use
	 */
	function get_permanent_url( $mode = '', $blogurl='' )
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

		$this->get_Item();
		$post_permalink = $this->Item->get_permanent_url( $mode, $blogurl );
		return $post_permalink.'#'.$this->get_anchor();
	}


	/**
	 * Template function: display permalink to this comment
	 *
	 * Note: This actually only returns the URL, to get a real link, use Comment::permanent_link()
	 *
	 * @param string 'urltitle', 'pid', 'archive#id' or 'archive#title'
	 * @param string url to use
	 */
	function permanent_url( $mode = '', $blogurl='' )
	{
		echo $this->get_permanent_url( $mode, $blogurl );
	}


	/**
	 * Returns a permalink link to the Comment
	 *
	 * Note: If you only want the permalink URL, use Comment::get_permanent_url()
	 *
	 * @param string link text or special value: '#', '#icon#', '#text#'
	 * @param string link title
	 * @param string class name
	 */
	function get_permanent_link( $text = '#', $title = '#', $class = '' )
	{
		global $current_User, $baseurl;

		switch( $text )
		{
			case '#':
				$text = get_icon( 'permalink' ).T_('Permalink');
				break;

			case '#icon#':
				$text = get_icon( 'permalink' );
				break;

			case '#text#':
				$text = T_('Permalink');
				break;
		}

		if( $title == '#' ) $title = T_('Permanent link to this comment');

		$url = $this->get_permanent_url();

		// Display as link
		$r = '<a href="'.$url.'" title="'.$title.'"';
		if( !empty( $class ) ) $r .= ' class="'.$class.'"';
		$r .= '>'.$text.'</a>';

		return $r;
	}


	/**
	 * Displays a permalink link to the Comment
	 *
	 * Note: If you only want the permalink URL, use Comment::permanent_url()
	 *
	 * @param string link text
	 * @param string link title
	 * @param string class name
	 */
	function permanent_link( $text = '#', $title = '#', $class = '' )
	{
		echo $this->get_permanent_link( $text, $title, $class );
	}


	/**
	 * Template function: get content of comment
	 *
	 * @param string Output format, see {@link format_to_output()}
	 * @return string
	 */
	function get_content( $format = 'htmlbody' )
	{
		global $Plugins;

		$comment = $this->content;
		// fp> obsolete: $comment = str_replace('<trackback />', '', $comment);
		$Plugins->trigger_event( 'FilterCommentContent', array( 'data' => & $comment, 'Comment' => $this ) );
		$comment = format_to_output( $comment, $format );

		return $comment;
	}


	/**
	 * Template function: display content of comment
	 *
	 * @param string Output format, see {@link format_to_output()}
	 */
	function content( $format = 'htmlbody' )
	{
		echo $this->get_content( $format );
	}


	/**
	 * Template function: display date (datetime) of comment
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
	 * Template function: display status of comment
	 *
	 * Statuses:
	 * - published
	 * - deprecated
	 * - protected
	 * - private
	 * - draft
	 *
	 * @param string Output format, see {@link format_to_output()}
	 */
	function status( $format = 'htmlbody' )
	{
		global $post_statuses;

		if( $format == 'raw' )
		{
			$this->disp( 'status', 'raw' );
		}
		else
		{
			echo format_to_output( $this->get('t_status'), $format );
		}
	}


	/**
	 * Send email notifications to subscribed users:
	 *
	 * @todo shall we notify suscribers of blog were this is in extra-cat?
	 * @todo cache message by locale like {@link Item::send_email_notifications()}
	 * @todo Indicator in url to see where the user came from (&from=subnote ["subscription notification"]) - Problem: too long urls.
	 * @todo "Beautify" like {@link Item::send_email_notifications()} ?
	 * @todo Should include "visibility status" in the mail to the Item's Author
	 */
	function send_email_notifications()
	{
		global $DB, $admin_url, $debug, $Debuglog;

		$this->get_Item();

		// Get list of users who want to be notfied:
		// TODO: also use extra cats/blogs??
		// So far you get notifications for everything. We'll need a setting to decide if you want to received unmoderated (aka unpublished) comments or not.
	// Note: users receive comments on their own posts. This is done on purpose. Otherwise they think it's broken when they test the app.
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
		$item_author_User = & $this->Item->get_creator_User();
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
		if( $this->get_author_User() )
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

		$Blog = & $this->Item->get_Blog();

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
				.' ( '.str_replace('&amp;', '&', $this->Item->get_permanent_url( 'pid' ))." )\n";
				// We use pid to get a short URL and avoid it to wrap on a new line in the mail which may prevent people from clicking

			switch( $this->type )
			{
				case 'trackback':
					$user_domain = gethostbyaddr($this->author_IP);
					$notify_message .= T_('Website').": $this->author (IP: $this->author_IP, $user_domain)\n";
					$notify_message .= T_('Url').": $this->author_url\n";
					break;

				default:
					if( $this->get_author_User() )
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
				T_('Comment').': '.str_replace('&amp;', '&', $this->get_permanent_url( 'pid' ))."\n" // We use pid to get a short URL and avoid it to wrap on a new line in the mail which may prevent people from clicking
				.$this->get('content')."\n\n"
				.T_('Edit/Delete').': '.$admin_url.'?ctrl=browse&tab=posts&blog='.$this->Item->blog_ID.'&p='.$this->Item->ID."&c=1\n\n"
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
	 * Trigger event AfterCommentUpdate after calling parent method.
	 *
	 * @return boolean true on success
	 */
	function dbupdate()
	{
		global $Plugins;

		if( $r = parent::dbupdate() )
		{
			$Plugins->trigger_event( 'AfterCommentUpdate', $params = array( 'Comment' => & $this ) );
		}

		return $r;
	}


	/**
	 * Get karma and set it before adding the Comment to DB.
	 *
	 * @return boolean true on success, false if it did not get inserted
	 */
	function dbinsert()
	{
		/**
		 * @var Plugins
		 */
		global $Plugins;
		global $Settings;

		// Get karma percentage (interval -100 - 100)
		$spam_karma = $Plugins->trigger_karma_collect( 'GetSpamKarmaForComment', array( 'Comment' => & $this ) );

		$this->set_spam_karma( $spam_karma );

		// Change status accordingly:
		if( ! is_null($spam_karma) )
		{
			if( $spam_karma < $Settings->get('antispam_threshold_publish') )
			{ // Publish:
				$this->set( 'status', 'published' );
			}
			elseif( $spam_karma > $Settings->get('antispam_threshold_delete') )
			{ // Delete/No insert:
				return false;
			}
		}

		if( $r = parent::dbinsert() )
		{
			$Plugins->trigger_event( 'AfterCommentInsert', $params = array( 'Comment' => & $this ) );
		}

		return $r;
	}


	/**
	 * Trigger event AfterCommentDelete after calling parent method.
	 *
	 * @return boolean true on success
	 */
	function dbdelete()
	{
		global $Plugins;

		// remember ID, because parent method resets it to 0
		$old_ID = $this->ID;

		if( $r = parent::dbdelete() )
		{
			// re-set the ID for the Plugin event
			$this->ID = $old_ID;

			$Plugins->trigger_event( 'AfterCommentDelete', $params = array( 'Comment' => & $this ) );

			$this->ID = 0;
		}

		return $r;
	}

}


/*
 * $Log$
 * Revision 1.42  2006/08/29 18:36:17  blueyed
 * doc
 *
 * Revision 1.41  2006/08/29 00:26:11  fplanque
 * Massive changes rolling in ItemList2.
 * This is somehow the meat of version 2.0.
 * This branch has gone officially unstable at this point! :>
 *
 * Revision 1.40  2006/08/19 07:56:30  fplanque
 * Moved a lot of stuff out of the automatic instanciation in _main.inc
 *
 * Revision 1.39  2006/08/19 02:15:07  fplanque
 * Half kille dthe pingbacks
 * Still supported in DB in case someone wants to write a plugin.
 *
 * Revision 1.38  2006/07/26 17:15:44  blueyed
 * Replaced "name" attribute with "id" for anchors
 *
 * Revision 1.37  2006/07/04 17:32:29  fplanque
 * no message
 *
 * Revision 1.36  2006/06/22 18:37:47  fplanque
 * fixes
 *
 * Revision 1.35  2006/05/30 20:32:56  blueyed
 * Lazy-instantiate "expensive" properties of Comment and Item.
 *
 * Revision 1.34  2006/05/19 18:15:05  blueyed
 * Merged from v-1-8 branch
 *
 * Revision 1.33.2.1  2006/05/19 15:06:24  fplanque
 * dirty sync
 *
 * Revision 1.33  2006/05/04 10:05:39  blueyed
 * Fixed anchor in notification mails and shortened again, because of length.. probably it does not make sense to have get_anchor() anyway.. dunno..
 *
 * Revision 1.32  2006/05/04 04:07:24  blueyed
 * After posting a comment, add the anchor to the redirect param; also use more distinctive anchor name for comments
 *
 * Revision 1.31  2006/05/02 04:36:24  blueyed
 * Spam karma changed (-100..100 instead of abs/max); Spam weight for plugins; publish/delete threshold
 *
 * Revision 1.30  2006/05/02 01:27:55  blueyed
 * Moved nofollow handling to basic antispam plugin; added Filter events to Comment class
 *
 * Revision 1.29  2006/05/01 22:20:20  blueyed
 * Made rel="nofollow" optional (enabled); added Antispam settings page
 *
 * Revision 1.28  2006/04/29 23:27:10  blueyed
 * Only trigger update/insert/delete events if parent returns true
 *
 * Revision 1.27  2006/04/24 15:43:35  fplanque
 * no message
 *
 * Revision 1.26  2006/04/21 23:14:16  blueyed
 * Add Messages according to Comment's status.
 *
 * Revision 1.25  2006/04/21 18:10:53  blueyed
 * todos
 *
 * Revision 1.24  2006/04/20 00:00:21  blueyed
 * Fixed delete-link-button
 *
 * Revision 1.23  2006/04/19 22:08:16  blueyed
 * Fixed spam_karma()
 *
 * Revision 1.22  2006/04/19 19:52:27  blueyed
 * url-encode redirect_to param
 *
 * Revision 1.21  2006/04/19 13:05:21  fplanque
 * minor
 *
 * Revision 1.20  2006/04/18 20:17:25  fplanque
 * fast comment status switching
 *
 * Revision 1.19  2006/04/18 19:29:51  fplanque
 * basic comment status implementation
 *
 * Revision 1.18  2006/03/28 22:24:46  blueyed
 * Fixed logical spam karma issues
 *
 * Revision 1.17  2006/03/28 14:12:19  fplanque
 * minor fix
 *
 * Revision 1.16  2006/03/23 22:13:50  blueyed
 * doc
 *
 * Revision 1.15  2006/03/19 17:54:26  blueyed
 * Opt-out for email through message form.
 *
 * Revision 1.14  2006/03/18 23:38:44  blueyed
 * Decent getters; allow_msgform added
 *
 * Revision 1.13  2006/03/18 19:17:53  blueyed
 * Removed remaining use of $img_url
 *
 * Revision 1.12  2006/03/12 23:08:58  fplanque
 * doc cleanup
 *
 * Revision 1.11  2006/03/12 20:58:59  blueyed
 * doc
 *
 * Revision 1.9  2006/03/11 21:50:16  blueyed
 * Display spam_karma with comments
 *
 * Revision 1.8  2006/03/11 12:45:54  blueyed
 * fixed stupid regexp
 *
 * Revision 1.6  2006/03/09 22:29:59  fplanque
 * cleaned up permanent urls
 *
 * Revision 1.5  2006/03/09 21:58:52  fplanque
 * cleaned up permalinks
 *
 * Revision 1.4  2006/03/09 15:23:26  fplanque
 * fixed broken images
 *
 * Revision 1.3  2006/03/06 20:03:40  fplanque
 * comments
 *
 * Revision 1.1  2006/02/23 21:11:57  fplanque
 * File reorganization to MVC (Model View Controller) architecture.
 * See index.hml files in folders.
 * (Sorry for all the remaining bugs induced by the reorg... :/)
 *
 * Revision 1.24  2006/01/29 20:36:35  blueyed
 * Renamed Item::getBlog() to Item::get_Blog()
 *
 * Revision 1.23  2006/01/26 23:08:35  blueyed
 * Plugins enhanced. */
?>