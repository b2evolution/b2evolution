<?php
/**
 * This file implements the Comment class.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/dataobjects/_dataobject.class.php', 'DataObject' );

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
	 * The ID of the comment's Item.
	 * @var integer
	 */
	var $item_ID;
	/**
	 * Comment previous item ID. It will be set only if the comment item ID was changed.
	 * @var string
	 */
	var $previous_item_ID;
	/**
	 * The comment's user, this is NULL for (anonymous) visitors (lazy-filled).
	 * @see Comment::get_author_User()
	 * @see Comment::set_author_User()
	 * @access protected
	 * @var User
	 */
	var $author_User;
	/**
	 * The ID of the author's user. NULL for anonymous visitors.
	 * @var integer
	 */
	var $author_user_ID;
	/**
	 * Comment type: 'comment', 'linkback', 'trackback' or 'pingback'
	 * @var string
	 */
	var $type;
	/**
	 * Comment visibility status: 'published', 'deprecated', 'redirected', 'protected', 'private' or 'draft'
	 * @var string
	 */
	var $status;
	/**
	 * Comment previous visibility status. It will be set only if the comment status was changed.
	 * @var string
	 */
	var $previous_status;
	/**
	 * Name of the (anonymous) visitor (if any).
	 * @var string
	 */
	var $author;
	/**
	 * Email address of the (anonymous) visitor (if any).
	 * @var string
	 */
	var $author_email;
	/**
	 * URL/Homepage of the (anonymous) visitor (if any).
	 * @var string
	 */
	var $author_url;
	/**
	 * IP address of the comment's author (while posting).
	 * @var string
	 */
	var $author_IP;
	/**
	 * ID of Country that is detected by IP address
	 * @var integer
	 */
	var $IP_ctry_ID;
	/**
	 * Date of the comment (MySQL DATETIME - use e.g. {@link mysql2timestamp()}); local time ({@link $localtimenow})
	 * @var string
	 */
	var $date;
	/**
	 * @var string
	 */
	var $content;
	/**
	 * @var string
	 */
	var $renderers;
	/**
	 * Star rating from 0 to 5
	 * @var integer
	 */
	var $rating;
	/**
	 * @var integer
	 */
	var $featured;
	/**
	 * Karma
	 * @var integer
	 */
	var $karma;
	/**
	 * Spam karma of the comment (0-100), 0 being "probably no spam at all"
	 * @var integer
	 */
	var $spam_karma;
	/**
	 * Does an anonymous commentator allow to send messages through a message form?
	 * @var boolean
	 */
	var $allow_msgform;

	var $nofollow;
	/**
	 * @var string
	 */
	var $secret;
	/**
	 * Have post processing notifications been handled for this comment?
	 * @var string
	 */
	var $notif_status;
	/**
	 * Which cron task is responsible for handling notifications for this comment?
	 * @var integer
	 */
	var $notif_ctsk_ID;
	/**
	 * What have been notified?
	 * Possible values, separated by comma: 'moderators_notified,members_notified,community_notified,pings_sent'
	 * @var string
	 */
	var $notif_flags;

	/**
	 * Is this comment a reply to another comment ?
	 *
	 * This can be used by plugins to display threaded comments.
	 *
	 * @var integer
	 */
	var $in_reply_to_cmt_ID;

	/**
	 * Parent Comment
	 *
	 * @var object
	 */
	var $parent_Comment;

	/**
	 * Voting result of all votes in system helpfulness
	 *
	 * @var integer
	 */
	var $helpful_addvotes;
	/**
	 * A count of all votes in system helpfulness
	 *
	 * @var integer
	 */
	var $helpful_countvotes;
	/**
	 * Voting result of all votes in system spam detecting
	 *
	 * @var integer
	 */
	var $spam_addvotes;
	/**
	 * A count of all votes in system spam detecting
	 *
	 * @var integer
	 */
	var $spam_countvotes;

	/**
	 * Date when comment was edited last time (timestamp)
	 * @var integer
	 */
	var $last_touched_ts;

	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( 'T_comments', 'comment_', 'comment_ID' );

		if( $db_row == NULL )
		{
			// echo 'null comment';
			$this->rating = NULL;
			$this->featured = 0;
			$this->nofollow = 1;
			$this->notif_status = 'noreq';
			$this->in_reply_to_cmt_ID = 0;
			$this->set_renderers( array( 'default' ) );
			$this->set( 'status', 'draft' );
		}
		else
		{
			$this->ID = $db_row->comment_ID;
			$this->item_ID = $db_row->comment_item_ID;
			if( ! empty( $db_row->comment_author_user_ID ) )
			{
				$this->author_user_ID = $db_row->comment_author_user_ID;
			}
			$this->type = $db_row->comment_type;
			$this->status = $db_row->comment_status;
			$this->author = $db_row->comment_author;
			$this->author_email = $db_row->comment_author_email;
			$url = trim( $db_row->comment_author_url );
			if( ! empty($url) && ! preg_match( '~^\w+://~', $url ) )
			{ // URL given and does not start with a protocol:
				$url = 'http://'.$url;
			}
			$this->author_url = $url;
			$this->author_IP = $db_row->comment_author_IP;
			$this->IP_ctry_ID = $db_row->comment_IP_ctry_ID;
			$this->date = $db_row->comment_date;
			$this->last_touched_ts = $db_row->comment_last_touched_ts;
			$this->content = $db_row->comment_content;
			$this->renderers = $db_row->comment_renderers;
			$this->rating = $db_row->comment_rating;
			$this->featured = $db_row->comment_featured;
			$this->nofollow = $db_row->comment_nofollow;
			$this->spam_karma = $db_row->comment_spam_karma;
			$this->allow_msgform = $db_row->comment_allow_msgform;
			$this->secret = $db_row->comment_secret;
			$this->notif_status = $db_row->comment_notif_status;
			$this->notif_ctsk_ID = $db_row->comment_notif_ctsk_ID;
			$this->notif_flags = $db_row->comment_notif_flags;
			$this->in_reply_to_cmt_ID = $db_row->comment_in_reply_to_cmt_ID;
			$this->helpful_addvotes = $db_row->comment_helpful_addvotes;
			$this->helpful_countvotes = $db_row->comment_helpful_countvotes;
			$this->spam_addvotes = $db_row->comment_spam_addvotes;
			$this->spam_countvotes = $db_row->comment_spam_countvotes;
		}
	}


	/**
	 * Get this class db table config params
	 *
	 * @return array
	 */
	static function get_class_db_config()
	{
		static $comment_db_config;

		if( !isset( $comment_db_config ) )
		{
			$comment_db_config = array_merge( parent::get_class_db_config(),
				array(
					'dbtablename'        => 'T_comments',
					'dbprefix'           => 'comment_',
					'dbIDname'           => 'comment_ID',
				)
			);
		}

		return $comment_db_config;
	}


	/**
	 * Get delete cascade settings
	 *
	 * @return array
	 */
	static function get_delete_cascades()
	{
		return array(
				array( 'table'=>'T_links', 'fk'=>'link_cmt_ID', 'msg'=>T_('%d links to destination comments'),
						'class'=>'Link', 'class_path'=>'links/model/_link.class.php' ),
				array( 'table'=>'T_comments__votes', 'fk'=>'cmvt_cmt_ID', 'msg'=>T_('%d votes on comment') ),
				array( 'table'=>'T_comments__prerendering', 'fk'=>'cmpr_cmt_ID', 'msg'=>T_('%d prerendered content') ),
			);
	}


	/**
	 * Delete those comments from the database which corresponds to the given condition or to the given ids array
	 * Note: the delete cascade arrays are handled!
	 *
	 * @param string the name of this class
	 *   Note: This is required until min phpversion will be 5.3. Since PHP 5.3 we can use static::function_name to achieve late static bindings
	 * @param string where condition
	 * @param array object ids
	 * @return mixed # of rows affected or false if error
	 */
	static function db_delete_where( $class_name, $sql_where, $object_ids = NULL, $params = NULL )
	{
		global $DB;

		$use_transaction = ( isset( $params['use_transaction'] ) ) ? $params['use_transaction'] : true;
		if( $use_transaction )
		{
			$DB->begin();
			$params['use_transaction'] = false;
		}

		if( ! empty( $sql_where ) )
		{
			$object_ids = $DB->get_col( 'SELECT comment_ID FROM T_comments WHERE '.$sql_where );
		}

		if( ! $object_ids )
		{ // There is no comment to delete
			if( $use_transaction )
			{ // Commit transaction if it was started
				$DB->commit();
			}
			return;
		}

		$query_get_attached_file_ids = 'SELECT link_file_ID FROM T_links
			WHERE link_cmt_ID IN ( '.implode( ', ', $object_ids ).' )';
		$attached_file_ids = $DB->get_col( $query_get_attached_file_ids );

		$result = parent::db_delete_where( $class_name, $sql_where, $object_ids );

		if( ( $result !== false ) && ( ! empty( $attached_file_ids ) ) )
		{ // Delete orphan attachments and empty comment attachment folders
			load_funcs( 'files/model/_file.funcs.php' );
			remove_orphan_files( $attached_file_ids, NULL, true );
		}

		if( $use_transaction )
		{ // Commit or rollback the transaction
			( $result !== false ) ? $DB->commit() : $DB->rollback();
		}

		return $result;
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
			$UserCache = & get_UserCache();
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
			$ItemCache = & get_ItemCache();
			$this->Item = & $ItemCache->get_by_ID( $this->item_ID, false, false );
		}

		return $this->Item;
	}


	/**
	 * Get the Item this comment relates to
	 *
	 * @return Item
	 */
	function & get_parent_Comment()
	{
		if( ! isset( $this->parent_Comment ) )
		{
			$CommentCache = & get_CommentCache();
			$this->parent_Comment = & $CommentCache->get_by_ID( $this->in_reply_to_cmt_ID, false, false );
		}

		return $this->parent_Comment;
	}


	/**
	 * Fix parent Comment to top possible Comment from the same Item/Post
	 */
	function set_correct_parent_comment()
	{
		if( empty( $this->in_reply_to_cmt_ID ) )
		{	// Nothing to fix because this comment has no parent Comment:
			return;
		}

		// Use NULL to set comment in root if no found a proper top parent comment:
		$correct_in_reply_to_cmt_ID = NULL;

		$parent_Comment = & $this->get_parent_Comment();
		while( $parent_Comment )
		{
			if( $parent_Comment->get( 'item_ID' ) == $this->get( 'item_ID' ) )
			{	// This comment is located in same new created Item then we should use this as parent:
				$correct_in_reply_to_cmt_ID = $parent_Comment->ID;
				break;
			}
			$parent_Comment = & $parent_Comment->get_parent_Comment();
		}

		$this->set( 'in_reply_to_cmt_ID', $correct_in_reply_to_cmt_ID, true );
	}


	/**
	 * Get a member param by its name
	 *
	 * @param mixed Name of parameter
	 * @return mixed Value of parameter
	 */
	function get( $parname )
	{
		switch( $parname )
		{
			case 't_status':
				// Text status:
				$visibility_statuses = get_visibility_statuses( '', array( 'redirected' ) );
				return $visibility_statuses[ $this->status ];

			case 'notif_flags':
				return empty( $this->notif_flags ) ? array() : explode( ',', $this->notif_flags );
		}

		return parent::get( $parname );
	}


	/**
	 * Set param value
	 *
	 * @param string parameter name
	 * @param mixed parameter value
	 * @param boolean true to set to NULL if empty value
	 * @return boolean true, if a value has been set; false if it has not changed
	 */
	function set( $parname, $parvalue, $make_null = false )
	{
		switch( $parname )
		{
			case 'rating':
				// Save star rating with checking correct values
				if( $parvalue < 1 )
				{ // cannot be less than 0
					$parvalue = NULL;
				}
				elseif( $parvalue > 5 )
				{ // cannot be more than 5
					$parvalue = 5;
				}
				return $this->set_param( $parname, 'number', $parvalue, true );

			case 'author_email':
				return $this->set_param( $parname, 'string', utf8_strtolower( $parvalue ), $make_null );

			case 'notif_flags':
				$notifications_flags = $this->get( 'notif_flags' );
				if( ! is_array( $parvalue ) )
				{	// Convert string to array:
					$parvalue = array( $parvalue );
				}
				$notifications_flags = array_merge( $notifications_flags, $parvalue );
				$notifications_flags = array_unique( $notifications_flags );
				return $this->set_param( 'notif_flags', 'string', implode( ',', $notifications_flags ), $make_null );

			case 'status':
				// We need to set a reminder here to later check if the new status is allowed at dbinsert or dbupdate time ( $this->restrict_status( true ) )
				// We cannot check immediately because we may be setting the status before having set a main cat_ID -> a collection ID to check the status possibilities
				// Save previous status temporarily to make some changes on dbinsert(), dbupdate() & dbdelete()
				if( ! isset( $this->previous_status ) )
				{	// Set once previous status to know what status was original on several rewriting per same page request:
					$this->previous_status = $this->get( 'status' );
				}
				return parent::set( 'status', $parvalue, $make_null );

			default:
				return $this->set_param( $parname, 'string', $parvalue, $make_null );
		}
	}


	/**
	 * Set Item this comment relates to
	 * @param Item
	 */
	function set_Item( & $Item )
	{
		// Save previous item ID temporarily to make some changes on dbupdate()
		$this->previous_item_ID = $this->item_ID;

		$this->Item = & $Item;
		parent::set_param( 'item_ID', 'number', $Item->ID );
	}


	/**
	 * Set author User of this comment
	 */
	function set_author_User( & $author_User )
	{
		$this->author_User = & $author_User;
		parent::set_param( 'author_user_ID', 'number', $author_User->ID );
	}


	/**
	 * Set the spam karma, as a number.
	 * @param integer Spam karma (-100 - 100)
	 * @access protected
	 */
	function set_spam_karma( $spam_karma )
	{
		return $this->set_param( 'spam_karma', 'number', $spam_karma );
	}


	/**
	 * Set the vote, as a number.
	 *
	 * @param string Vote type (spam, helpful)
	 * @param string Vote value (spam, notsure, ok, yes, no)
	 * @access protected
	 */
	function set_vote( $vote_type, $vote_value )
	{
		global $DB, $current_User;

		if( ! in_array( $vote_type, array( 'spam', 'helpful' ) ) )
		{ // Restrict access for bad requests
			return;
		}

		switch ( $vote_value )
		{ // Set a value for spam vote
			case 'spam':
			case 'yes':
				$vote = '1';
				break;
			case 'notsure':
			case 'noopinion':
				$vote = '0';
				break;
			case 'ok':
			case 'no':
				$vote = '-1';
				break;
			default:
				// $vote_value is not correct from ajax request
				return;
		}

		if( empty( $this->ID ) )
		{ // If comment doesn't exist
			return;
		}

		$DB->begin();

		$SQL = new SQL( 'Check if current user already voted on comment #'.$this->ID );
		$SQL->SELECT( 'cmvt_cmt_ID, cmvt_'.$vote_type.' AS value' );
		$SQL->FROM( 'T_comments__votes' );
		$SQL->WHERE( 'cmvt_cmt_ID = '.$DB->quote( $this->ID ) );
		$SQL->WHERE_and( 'cmvt_user_ID = '.$DB->quote( $current_User->ID ) );
		$existing_vote = $DB->get_row( $SQL );

		if( $existing_vote === NULL )
		{	// Add a new vote for first time:
			// Use a replace into to avoid duplicate key conflict in case when user clicks two times fast one after the other:
			$DB->query( 'INSERT INTO T_comments__votes
				       ( cmvt_cmt_ID, cmvt_user_ID, cmvt_'.$vote_type.' )
				VALUES ( '.$DB->quote( $this->ID ).', '.$DB->quote( $current_User->ID ).', '.$DB->quote( $vote ).' )',
				'Add new vote on comment #'.$this->ID );
		}
		else
		{ // Update a vote:
			if( $existing_vote->value == $vote )
			{	// Undo previous vote:
				$vote = NULL;
			}
			$DB->query( 'UPDATE T_comments__votes
				  SET cmvt_'.$vote_type.' = '.$DB->quote( $vote ).'
				WHERE cmvt_cmt_ID = '.$DB->quote( $this->ID ).'
				  AND cmvt_user_ID = '.$DB->quote( $current_User->ID ),
				'Update a vote on comment #'.$this->ID );
		}

		$vote_SQL = new SQL( 'Get voting results of comment #'.$this->ID );
		$vote_SQL->SELECT( 'COUNT( cmvt_'.$vote_type.' ) AS votes_count, SUM( cmvt_'.$vote_type.' ) AS votes_sum' );
		$vote_SQL->FROM( 'T_comments__votes' );
		$vote_SQL->WHERE( 'cmvt_cmt_ID = '.$DB->quote( $this->ID ) );
		$vote_SQL->WHERE_and( 'cmvt_'.$vote_type.' IS NOT NULL' );
		$vote = $DB->get_row( $vote_SQL->get() );

		// These values must be number and not NULL:
		$vote->votes_sum = intval( $vote->votes_sum );
		$vote->votes_count = intval( $vote->votes_count );

		// Update fields with vote counters for this comment
		$DB->query( 'UPDATE T_comments
			  SET comment_'.$vote_type.'_addvotes = '.$DB->quote( $vote->votes_sum ).',
			      comment_'.$vote_type.'_countvotes = '.$DB->quote( $vote->votes_count ).'
			WHERE comment_ID = '.$DB->quote( $this->ID ),
			'Update fields with vote counters for comment #'.$this->ID );
		$this->{$vote_type.'_addvotes'} = $vote->votes_sum;
		$this->{$vote_type.'_countvotes'} = $vote->votes_count;

		$DB->commit();

		if( $vote_type == 'spam' && $vote_value == 'spam' )
		{	// This is a voting about spam comment we should inform moderators:
			$this->send_vote_spam_emails();
		}

		return;
	}


	/**
	 * Get the vote statuses for current user
	 *
	 * @param string Vote type: 'spam', 'helpful'
	 * @return boolean
	 */
	function get_vote_status( $type = 'spam' )
	{
		global $current_User, $DB, $cache_comments_vote_statuses;

		if( ! is_logged_in() )
		{	// Current user must be logged in:
			return false;
		}

		if( ! is_array( $cache_comments_vote_statuses ) )
		{	// Initialize array first time:
			$cache_comments_vote_statuses = array();
		}

		if( ! isset( $cache_comments_vote_statuses[ $this->ID ] ) )
		{	// Get a vote status from DB and cache in global variable:
			$SQL = new SQL( 'Get the vote statuses for current user and comment #'.$this->ID );
			$SQL->SELECT( 'cmvt_spam AS spam, cmvt_helpful AS helpful' );
			$SQL->FROM( 'T_comments__votes' );
			$SQL->WHERE( 'cmvt_cmt_ID = '.$DB->quote( $this->ID ) );
			$SQL->WHERE_and( 'cmvt_user_ID = '.$DB->quote( $current_User->ID ) );
			$cache_comments_vote_statuses[ $this->ID ] = $DB->get_row( $SQL, ARRAY_A );
		}

		if( isset( $cache_comments_vote_statuses[ $this->ID ][ $type ] ) )
		{	// Return a vote status:
			return $cache_comments_vote_statuses[ $this->ID ][ $type ];
		}
		else
		{	// Current user didn't vote on this comment yet:
			return false;
		}
	}

	/**
	 * Get the vote spam type disabled, as array.
	 *
	 * @param int User ID
	 *
	 * @return array Result:
	 *               'is_voted' - TRUE if current user already voted on this comment
	 *               'icons_statuses': array( 'spam', 'notsure', 'ok' )
	 */
	function get_vote_spam_disabled()
	{
		global $DB, $current_User;

		$result = array(
				'is_voted' => false,
				'icons_statuses' => array(
					'ok' => 'disabled',
					'notsure' => 'disabled',
					'spam' => 'disabled',
			) );

		$vote = $this->get_vote_status( 'spam' );
		if( $vote !== false )
		{	// Get a spam vote for current comment and user:
			$result['is_voted'] = true;
			$class_disabled = 'disabled';
			$class_voted = 'voted';
			switch ( $vote )
			{
				case '1': // SPAM
					$result['icons_statuses']['spam'] = $class_voted;
					$result['icons_statuses']['notsure'] = $result['icons_statuses']['ok'] = $class_disabled;
					break;
				case '0': // NOT SURE
					$result['icons_statuses']['notsure'] = $class_voted;
					$result['icons_statuses']['spam'] = $result['icons_statuses']['ok'] = $class_disabled;
					break;
				case '-1': // OK
					$result['icons_statuses']['ok'] = $class_voted;
					$result['icons_statuses']['spam'] = $result['icons_statuses']['notsure'] = $class_disabled;
					break;
			}
		}

		return $result;
	}


	/**
	 * Get the vote helpful type disabled, as array.
	 *
	 * @return array Result:
	 *               'is_voted' - TRUE if current user already voted on this comment
	 *               'icons_statuses': array( 'yes', 'no' )
	 */
	function get_vote_helpful_disabled()
	{
		global $DB, $current_User;

		$result = array(
				'is_voted' => false,
				'icons_statuses' => array(
					'yes' => '',
					'no' => ''
			) );

		$vote = $this->get_vote_status( 'helpful' );
		if( $vote !== false )
		{	// Get a helpful vote for current comment and user:
			$result['is_voted'] = true;
			$class_disabled = 'disabled';
			$class_voted = 'voted';
			switch ( $vote )
			{
				case '1': // YES
					$result['icons_statuses']['yes'] = $class_voted;
					$result['icons_statuses']['no'] = $class_disabled;
					break;
				case '-1': // NO
					$result['icons_statuses']['no'] = $class_voted;
					$result['icons_statuses']['yes'] = $class_disabled;
					break;
			}
		}

		return $result;
	}


	/**
	 * Get the vote summary, as a string.
	 *
	 * @param type Vote type (spam, helpful)
	 * @param srray Params
	 * @return string
	 */
	function get_vote_summary( $type, $params = array() )
	{
		$params = array_merge( array(
				'result_title'           => '',
				'result_title_undecided' => '',
				'after_result'           => '',
			), $params );

		if( ! in_array( $type, array( 'spam', 'helpful' ) ) )
		{	// Bad request
			return '';
		}

		if( $this->{$type.'_countvotes'} == 0 )
		{	// No votes for current comment
			return '';
		}

		// Calculate a vote summary
		$summary = ceil( $this->{$type.'_addvotes'} / $this->{$type.'_countvotes'} * 100 );

		if( $summary < -20 )
		{	// Comment is OK
			$summary = abs($summary).'% '.( $type == 'spam' ? T_('OK') : T_('Negative') );
		}
		else if( $summary >= -20 && $summary <= 20 )
		{	// Comment is UNDECIDED
			$summary = T_('UNDECIDED');
			if( !empty( $params['result_title_undecided'] ) )
			{	// Display title before undecided results
				$summary = $params['result_title_undecided'].' '.$summary;
			}
		}
		else if( $summary > 20 )
		{	// Comment is SPAM
			$summary .= '% '.( $type == 'spam' ? T_('SPAM') : T_('Positive') );
		}

		if( !empty( $params['result_title'] ) )
		{	// Display title before results
			$summary = $params['result_title'].' '.$summary;
		}

		return $summary.$params['after_result'].' ';
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
			return $this->author_User->get_preferred_name();
		}
		else
		{
			return $this->author;
		}
	}


	/**
	 * Get the comment author's gender.
	 *
	 * @return string
	 */
	function get_author_gender()
	{
		if( $this->get_author_User() )
		{
			return $this->author_User->get( 'gender' );
		}
		else
		{
			return '';
		}
	}


	/**
	 * Get the comment anonymous author's name with gender class.
	 *
	 * @param string format to display author
	 * @param array Params
	 * @return string
	 */
	function get_author_name_anonymous( $format = 'htmlbody', $params = array() )
	{
		// Make sure we are not missing any param:
		$params = array_merge( array(
				'before' => '',
				'after'  => '',
				'rel'    => NULL,
			), $params );

		$gender_class = '';
		if( check_setting( 'gender_colored' ) )
		{ // Set a gender class if the setting is ON
			$gender_class = ' nogender';
		}

		$author_name = $this->dget( 'author', $format );

		if( is_null( $params['rel'] ) )
		{ // Set default rel:
			$params['rel'] = 'bubbletip_comment_'.$this->ID;
		}

		if( ! empty( $params['rel'] ) )
		{ // Initialize attribure "rel"
			$params['rel'] = ' rel="'.$params['rel'].'"';
		}

		$author_name = '<span class="user anonymous'.$gender_class.'"'.$params['rel'].'>'
			.$params['before']
			.$author_name
			.$params['after']
			.'</span>';

		return $author_name;
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
	 * Template function: display the avatar of the comment's author.
	 *
	 * @param string
	 * @param string class for the img tag
	 * @param array
	 */
	function avatar( $size = 'crop-top-64x64', $class = 'bCommentAvatar', $params = array() )
	{
		if( $r = $this->get_avatar( $size, $class, $params ) )
		{
			echo $r;
		}
	}


	/**
	 * Get the avatar of the comment's author.
	 *
	 * @param string Avatar thumb size
	 * @param string Class name of avatar img tag
	 * @param array Params
	 * @return string
	 */
	function get_avatar( $size = 'crop-top-64x64', $class = 'bCommentAvatar', $params = array() )
	{
		// Make sure we are not missing any param:
		$params = array_merge( array(
				'thumb_zoomable' => false,
			), $params );

		global $Settings, $Plugins;

		if( ! $Settings->get('allow_avatars') )
		{ // Avatars are not allowed generally, Exit here
			return;
		}

		$comment_Item = $this->get_Item();
		$comment_Item->load_Blog();
		if( !$this->Item->Blog->get_setting('comments_avatars') )
		{ // Avatars are not allowe for this Blog, Exit here
			return;
		}

		$author_link = get_user_identity_url( $this->author_user_ID );

		if( $comment_author_User = & $this->get_author_User() )
		{ // Author is a user
			if( $comment_author_User->has_avatar() )
			{ // Get an image
				$r = $comment_author_User->get_avatar_imgtag( $size, $class, '', $params['thumb_zoomable'] );
				if( $author_link != '' && !$params['thumb_zoomable'] )
				{ // Add author link
					$r = '<a href="'.$author_link.'">'.$r.'</a>';
				}
				return $r;
			}
		}

		// TODO> add new event
		// See if plugin supplies an image
		// $img_url = $Plugins->trigger_event( 'GetCommentAvatar', array( 'Comment' => & $this, 'size' => $size ) );

		// Get gravatar for anonymous users and for users without uploaded avatar
		return get_avatar_imgtag_default( $size, $class, '', array(
				'email'    => $this->get_author_email(),
				'username' => $this->get_author_name(),
				'gender'   => $this->get_author_gender(),
			) );
	}


	/**
	 * Template function: display author of comment
	 *
	 * @deprecated use Comment::author2() instead
	 * @param string String to display before author name if not a user
	 * @param string String to display after author name if not a user
	 * @param string String to display before author name if he's a user
	 * @param string String to display after author name if he's a user
	 * @param string Output format, see {@link format_to_output()}
	 * @param boolean true for link, false if you want NO html link
	 * @param string What show as user name: avatar_name | avatar_login | only_avatar | name | login | nickname | firstname | lastname | fullname | preferredname
	 */
	function author( $before = '', $after = '#', $before_user = '', $after_user = '#',
										$format = 'htmlbody', $makelink = false, $lint_text = 'name' )
	{
		echo $this->get_author( array(
					'before'      => $before,
					'after'       => $after,
					'before_user' => $before_user,
					'after_user'  => $after_user,
					'format'      => $format,
					'link_to'     => ( $makelink ? 'userurl>userpage' : '' ),
					'link_text'   => $lint_text,
				)
			);
	}


	/**
	 * Template function: display author of comment
	 *
	 * @param array
	 */
	function author2( $params = array()  )
	{
		echo $this->get_author( $params );
	}


	/**
	 * Get author of comment
	 *
	 * @param array
	 * @return string
	 */
	function get_author( $params = array() )
	{
		// Make sure we are not missing any param:
		$params = array_merge( array(
				'profile_tab'  => 'user',
				'before'       => ' ',
				'after'        => '#',	// After anonymous user
				'before_user'  => '',
				'after_user'   => '#',	// After Member user
				'format'       => 'htmlbody',
				'link_to'      => 'userurl>userpage', // 'userpage' or 'userurl' or 'userurl>userpage' 'userpage>userurl'
				'link_text'    => 'auto', // avatar_name | avatar_login | only_avatar | name | login | nickname | firstname | lastname | fullname | preferredname
				'link_rel'     => '',
				'link_class'   => '',
				'thumb_size'   => 'crop-top-32x32',
				'thumb_class'  => '',
			), $params );

		global $Plugins;

		global $Collection, $Blog;

		if( empty( $Blog ) )
		{ // Set Blog if it is still not defined
			$comment_Item = $this->get_Item();
			$Collection = $Blog = $comment_Item->get_Blog();
		}

		if( $Blog->get_setting( 'allow_comments' ) != 'any' && $params['after_user'] == '#' && $params['after'] == '#' )
		{ // The blog does not allow anonymous comments, Don't display a type of comment author
			$params['after_user'] = '';
			$params['after'] = '';
		}

		if( $params['after_user'] == '#' && $this->is_meta() )
		{	// Don't display a commenter type for meta comment, because only memebers can create them:
			$params['after_user'] = '';
		}

		if( !$Blog->get_setting('comments_avatars') && $params['link_text'] == 'avatar' )
		{ // If avatars are not allowed for this Blog
			$params['link_text'] = 'name';
		}

		if( $this->get_author_User() )
		{ // Author is a registered user:
			if( $params['after_user'] == '#' ) $params['after_user'] = ' <span class="bUser-member-tag">['.T_('Member').']</span>';

			$r = $this->author_User->get_identity_link( $params );

			$r = $params['before_user'].$r.$params['after_user'];
		}
		else
		{ // Not a registered user, display info recorded at edit time:
			if( $params['after'] == '#' ) $params['after'] = ' <span class="bUser-anonymous-tag">['.T_('Visitor').']</span>';

			if( utf8_strlen( $this->author_url ) <= 10 )
			{ // URL is too short anyways...
				$params['link_to'] = '';
			}

			$author_name_params = array();
			if( strpos( $params['link_text'], 'avatar' ) !== false )
			{ // Get avatar for anonymous user
				$author_name_params['before'] = $this->get_avatar( $params['thumb_size'], $params['thumb_class'] );
			}
			// Don't display avatar login name on mode 'only_avatar'
			$author_name = $params['link_text'] == 'only_avatar' ?
				$author_name_params['before'] :
				$this->get_author_name_anonymous( $params['format'], $author_name_params );

			switch( $params['link_to'] )
			{
				case 'userurl':
				case 'userurl>userpage':
				case 'userpage>userurl':
					// Make a link:
					$r = $this->get_author_url_link( $author_name, $params['before'], $params['after'], true, $params['link_class'] );
					break;

				default:
					// Display the name: (NOTE: get_author_url_link( with nolink option ) would NOT handle this correctly when url is empty
					$r = $params['before'].$author_name.$params['after'];
					break;
			}
		}

		$hook_params = array(
			'data' => & $r,
			'Comment' => & $this,
			'makelink' => ! empty($params['link_to']),
		);

		$Plugins->trigger_event( 'FilterCommentAuthor', $hook_params );

		return $r;
	}


	/**
	 * Template function: display comment's author's IP
	 *
	 * @param string String to display before IP, if IP exists
	 * @param string String to display after IP, if IP exists
	 * @param boolean|string Type of url
	 *                TRUE|'filter' - create an url to filter by IP
	 *                'antispam' - to antispam page with filtered by the IP
	 *                FALSE - display IP as plain text without link
	 * @param boolean TRUE to display a link to antispam ip page
	 */
	function author_ip( $before = '', $after = '', $IP_link_to = false, $display_antispam_link = false )
	{
		if( ! empty( $this->author_IP ) )
		{
			global $Plugins, $CommentList;

			$author_IP = $this->author_IP;
			if( $IP_link_to === 'antispam' )
			{ // Add link to antispam page
				$author_IP = implode( ', ', get_linked_ip_list( array( $author_IP ) ) );
			}
			elseif( $IP_link_to === 'filter' || $IP_link_to )
			{ // Add link to filter by IP
				$filter_IP_url = regenerate_url( 'filter,ctrl,comments,cmnt_fullview_comments', 'cmnt_fullview_author_IP='.$author_IP.'&amp;ctrl=comments' );
				$author_IP = '<a href="'.$filter_IP_url.'">'.$author_IP.'</a>';
			}

			echo $before;

			// Filter the IP by plugins for display, allowing e.g. the DNSBL plugin to add a link that displays info about the IP:
			echo $Plugins->get_trigger_event( 'FilterIpAddress', array(
					'format'=>'htmlbody',
					'data' => $author_IP ),
				'data' );

			if( $display_antispam_link )
			{ // Display a link to antispam ip page
				$antispam_icon = get_icon( 'lightning', 'imgtag', array( 'title' => T_( 'Go to edit this IP address in antispam control panel' ) ) );
				echo ' '.implode( ', ', get_linked_ip_list( array( $this->author_IP ), NULL, $antispam_icon ) );
			}

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
	 * Template function: display comment's country that was detected by IP address
	 *
	 * @param string String to display before country, if country_ID is defined
	 * @param string String to display after country, if country_ID is defined
	 */
	function ip_country( $before = '', $after = '' )
	{
		echo $this->get_ip_country( $before, $after );
	}


	/**
	 * Get comment's country that was detected by IP address
	 *
	 * @param string String to display before country, if country_ID is defined
	 * @param string String to display after country, if country_ID is defined
	 * @return string Country with flag
	 */
	function get_ip_country( $before = '', $after = '' )
	{
		$country = '';

		if( !empty( $this->IP_ctry_ID ) )
		{	// Country ID is defined
			load_funcs( 'regional/model/_regional.funcs.php' );
			load_class( 'regional/model/_country.class.php', 'Country' );

			$CountryCache = & get_CountryCache();
			if( $Country = $CountryCache->get_by_ID( $this->IP_ctry_ID, false ) )
			{
				$country .= $before;

				$country .= country_flag( $Country->get( 'code' ), $Country->get_name(), 'w16px', 'flag', '', false );
				$country .= ' '.$Country->get_name();

				$country .= $after;
			}
		}

		return $country;
	}


	/**
	 * Get link to comment author's provided URL
	 *
	 * @param string String to display for link: leave empty to display URL
	 * @param string String to display before link, if link exists
	 * @param string String to display after link, if link exists
	 * @param boolean false if you want NO html link
	 * @param string Link class
	 * @return boolean true if URL has been displayed
	 */
	function get_author_url_link( $linktext = '', $before = '', $after = '', $makelink = true, $link_class = '' )
	{
		global $Plugins;

		$url = $this->get_author_url();

		if( utf8_strlen( $url ) < 10 )
		{
			return false;
		}

		// If URL exists:
		$r = $before;
		if( $makelink )
		{
			$r .= '<a ';
			if( $this->nofollow )
			{
				$r .= 'rel="nofollow" ';
			}
			if( ! empty( $link_class ) )
			{
				$r .= 'class="'.$link_class.'" ';
			}
			$r .= 'href="'.$url.'">';
		}
		$r .= ( empty($linktext) ? $url : $linktext );
		if( $makelink ) $r .= '</a>';
		$r .= $after;

		$Plugins->trigger_event( 'FilterCommentAuthorUrl', array( 'data' => & $r, 'makelink' => $makelink, 'Comment' => $this ) );

		return $r;
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
		$r = $this->get_author_url_link( $linktext, $before, $after, $makelink );
		if( !empty( $r ) )
		{
			echo $r;
			return true;
		}
		return false;
	}


	/**
	 * Display author url, delete icon and ban icon if user has proper rights
	 *
	 * @param string Redirect url. NOTE: This param MUST NOT be encoded before sending to this func, because it is executed by this func inside.
	 * @param boolean true to use ajax button
	 * @param boolean true to check user permission to edit this comment and antispam screen
	 * @param boolean TRUE - to save context(memorized params), to allow append redirect_to param to url
	 */
	function author_url_with_actions( $redirect_to = NULL, $ajax_button = false, $check_perms = true, $save_context = true )
	{
		global $current_User;
		if( $this->author_url( '', ' <span &bull; Url: id="commenturl_'.$this->ID.'" <span class="bUrl" >', '' ) )
		{ // There is an URL
			if( ! $this->get_author_User() && $current_User->check_perm( 'comment!CURSTATUS', 'edit', false, $this ) )
			{ // Author is anonymous user and we have permission to edit this comment...
				if( $redirect_to == NULL )
				{
					$redirect_to = regenerate_url( '', 'filter=restore', '', '&' );
				}
				$this->deleteurl_link( $redirect_to, $ajax_button, false, '&amp;', $save_context );
				$this->banurl_link( $redirect_to, $ajax_button, true, '&amp;', $save_context );
			}
			echo '</span>';
		}
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
	 * @param string Glue string for url params
	 * @param boolean TRUE - to save context(memorized params), to allow append redirect_to param to url
	 * @param string Redirect url. NOTE: This param MUST NOT be encoded before sending to this func, because it is executed by this func inside.
	 * @return boolean
	 */
	function edit_link( $before = ' ', $after = ' ', $text = '#', $title = '#', $class = '', $glue = '&amp;', $save_context = true, $redirect_to = NULL )
	{
		global $current_User, $admin_url;

		if( ! is_logged_in( false ) ) return false;

		if( empty($this->ID) )
		{	// Happens in Preview
			return false;
		}

		if( ! $current_User->check_perm( 'comment!CURSTATUS', 'edit', false, $this ) )
		{ // If User has no permission to edit this comment:
			return false;
		}

		if( $text == '#' ) $text = get_icon( 'edit' ).' '.T_('Edit...');
		if( $title == '#' ) $title = T_('Edit this comment');

		$this->get_Item();
		$item_Blog = & $this->Item->get_Blog();
		echo $before;
		if( $item_Blog->get_setting( 'in_skin_editing' ) && !is_admin_page() )
		{
			echo '<a href="'.url_add_param( $item_Blog->gen_blogurl(), 'disp=edit_comment'.$glue.'c='.$this->ID );
		}
		else
		{
			echo '<a href="'.$admin_url.'?ctrl=comments'.$glue.'blog='.$item_Blog->ID.$glue.'action=edit'.$glue.'comment_ID='.$this->ID;
		}
		if( $save_context )
		{	// Use a param to redirect after action:
			if( $redirect_to === NULL )
			{	// Get current url for redirect:
				$redirect_to = regenerate_url( '', 'filter=restore', '', '&' );
			}
			echo $glue.'redirect_to='.rawurlencode( $redirect_to );
		}
		echo '" title="'.$title.'"';
		echo empty( $class ) ? '' : ' class="'.$class.'"';
		if( $this->is_meta() )
		{ // Edit meta comment by ajax
			echo ' onclick="return edit_comment( \'form\', '.$this->ID.' )"';
		}
		echo '>'.$text.'</a>';
		echo $after;

		return true;
	}


	/**
	 * Display delete icon for deleting author_url if user has proper rights
	 *
	 * @param string Redirect url. NOTE: This param MUST NOT be encoded before sending to this func, because it is executed by this func inside.
	 * @param boolean true if create ajax button
	 * @param boolean true if need permission check, because it wasn't checked before
	 * @param string glue between url params
	 * @param boolean TRUE - to save context(memorized params), to allow append redirect_to param to url
	 * @return link on success, false otherwise
	 */
	function deleteurl_link( $redirect_to, $ajax_button = false, $check_perm = true, $glue = '&amp;', $save_context = true )
	{
		global $current_User, $admin_url;

		if( ! is_logged_in( false ) ) return false;

		if( $check_perm && ! $current_User->check_perm( 'comment!CURSTATUS', 'delete', false, $this ) )
		{ // If current user has no permission to edit this comment
			return false;
		}

		if( $save_context )
		{	// Use a param to redirect after action:
			if( $redirect_to === NULL )
			{	// Get current url for redirect:
				$redirect_to = regenerate_url( '', 'filter=restore', '', '&' );
			}
			$redirect_to = $glue.'redirect_to='.rawurlencode( $redirect_to );
		}
		else
		{	// Don't allow a redirect after action:
			$redirect_to = '';
		}

		$delete_url = $admin_url.'?ctrl=comments'.$glue.'action=delete_url'.$glue.'comment_ID='.$this->ID.$glue.url_crumb( 'comment' ).$redirect_to;
		if( $ajax_button )
		{
			echo ' <a href="'.$delete_url.'" onclick="delete_comment_url('.$this->ID.'); return false;">'.get_icon( 'remove' ).'</a>';
		}
		else
		{
			echo ' <a href="'.$delete_url.'">'.get_icon( 'remove' ).'</a>';
		}
	}


	/**
	 * Display ban icon, which goes to the antispam screen with keyword=author_url
	 *
	 * @param string Redirect url. NOTE: This param MUST NOT be encoded before sending to this func, because it is executed by this func inside.
	 * @param boolean true if create ajax button
	 * @param boolean true if need permission check, because it wasn't check before
	 * @param string glue between url params
	 * @param boolean TRUE - to save context(memorized params), to allow append redirect_to param to url
	 * @return link on success, false otherwise
	 */
	function banurl_link( $redirect_to, $ajax_button = false, $check_perm = true, $glue = '&amp;', $save_context = true )
	{
		global $current_User, $admin_url;

		if( ! is_logged_in( false ) ) return false;

		//$Item = & $this->get_Item();

		if( $check_perm && ! $current_User->check_perm( 'spamblacklist', 'edit' ) )
		{ // if current user has no permission to edit spams
			return false;
		}

		if( $save_context )
		{	// Use a param to redirect after action:
			if( $redirect_to === NULL )
			{	// Get current url for redirect:
				$redirect_to = regenerate_url( '', 'filter=restore', '', '&' );
			}
			$redirect_to = $glue.'redirect_to='.rawurlencode( $redirect_to );
		}
		else
		{	// Don't allow a redirect after action:
			$redirect_to = '';
		}

		// TODO: really ban the base domain! - not by keyword
		$ban_domain = get_ban_domain( $this->get_author_url() );
		$ban_url = $admin_url.'?ctrl=antispam'.$glue.'action=ban'.$glue.'keyword='.rawurlencode( $ban_domain ).$redirect_to.$glue.url_crumb( 'antispam' );

		if( $ajax_button )
		{
			echo ' <a id="ban_url" href="'.$ban_url.'" onclick="ban_url(\''.$ban_domain.'\'); return false;">'.get_icon( 'lightning' ).'</a>';
		}
		else
		{
			echo ' <a href="'.$ban_url.'">'.get_icon( 'lightning' ).'</a> ';
		}
	}


	/**
	 * Displays button for deleting the Comment if user has proper rights
	 *
	 * @param string to display before link
	 * @param string to display after link
	 * @param string link text
	 * @param string link title
	 * @param string class name
	 * @param boolean true to make this a button instead of a link
	 * @param string glue between url params
	 * @param boolean TRUE - to save context(memorized params), to allow append redirect_to param to url
	 * @param boolean true if create AJAX button
	 * @param string confirmation text
	 * @param string Redirect url. NOTE: This param MUST NOT be encoded before sending to this func, because it is executed by this func inside.
	 */
	function delete_link( $before = ' ', $after = ' ', $text = '#', $title = '#', $class = '', $button = false, $glue = '&amp;', $save_context = true, $ajax_button = false, $confirm_text = '#', $redirect_to = NULL )
	{
		global $current_User, $admin_url;

		if( ! is_logged_in( false ) ) return false;

		if( empty($this->ID) )
		{	// Happens in Preview
			return false;
		}

		$this->get_Item();

		if( ! $current_User->check_perm( 'comment!CURSTATUS', 'delete', false, $this ) )
		{ // If User has no permission to delete a comments:
			return false;
		}

		if( $text == '#' )
		{ // Use icon+text as default, if not displayed as button (otherwise just the text)
			$text = ( $this->status == 'trash' || $this->is_meta() ) ? T_('Delete').'!' : T_('Recycle').'!';
			if( ! $button )
			{ // Append icon before text
				$text = ( $this->status == 'trash' || $this->is_meta() ? get_icon( 'delete' ) : get_icon( 'recycle' ) ).' '.$text;
			}
		}
		if( $title == '#' )
		{ // Set default title
			$title = ( $this->status == 'trash' || $this->is_meta() ) ? T_('Delete this comment') : T_('Recycle this comment');
		}

		$url = $admin_url.'?ctrl=comments'.$glue.'action=delete'.$glue.'comment_ID='.$this->ID.$glue.url_crumb('comment');
		if( $save_context )
		{	// Use a param to redirect after action:
			if( $redirect_to === NULL )
			{	// Get current url for redirect:
				$redirect_to = regenerate_url( '', 'filter=restore', '', '&' );
			}
			$url .= $glue.'redirect_to='.rawurlencode( $redirect_to );
		}

		echo $before;
		if( $ajax_button && ( $this->status != 'trash' ) )
		{
			$comment_type = $this->is_meta() ? 'meta' : 'feedback';
			echo '<a href="'.$url.'" onclick="deleteComment('.$this->ID.', \''.request_from().'\', \''.$comment_type.'\'); return false;" title="'.$title.'"';
			if( !empty( $class ) ) echo ' class="'.$class.'"';
			echo '>'.$text.'</a>';
		}
		else
		{
			// JS confirm is required only when the comment is not in the recycle bin yet
			$display_js_confirm = ( $this->status == 'trash' );
			if( $display_js_confirm && ( $confirm_text == '#' ) )
			{ // Set js confirm text on comment delete action
				$confirm_text = TS_('You are about to delete this comment!\\nThis cannot be undone!');
			}

			if( $button )
			{ // Display as button
				echo '<input type="button"';
				echo ' value="'.$text.'" title="'.$title.'"';
				if( $display_js_confirm )
				{
					echo ' onclick="if ( confirm(\''.$confirm_text.'\') ) { document.location.href=\''.$url.'\' }"';
				}
				if( !empty( $class ) ) echo ' class="'.$class.'"';
				echo '/>';
			}
			else
			{ // Display as link
				echo '<a href="'.$url.'" title="'.$title.'"';
				if( $display_js_confirm )
				{
					echo ' onclick="return confirm(\''.$confirm_text.'\')"';
				}
				if( !empty( $class ) ) echo ' class="'.$class.'"';
				echo '>'.$text.'</a>';
			}
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
	 * @param boolean TRUE - to save context(memorized params), to allow append redirect_to param to url
	 * @param boolean true if create AJAX button
	 * @param string Redirect url. NOTE: This param MUST NOT be encoded before sending to this func, because it is executed by this func inside.
	 * @return string A link to deprecate this comment
	 */
	function get_deprecate_link( $before = ' ', $after = ' ', $text = '#', $title = '#', $class = '', $glue = '&amp;', $save_context = true, $ajax_button = false, $redirect_to = NULL )
	{
		global $current_User, $admin_url;

		if( ! is_logged_in( false ) )
		{
			return false;
		}

		if( ( $this->status == 'deprecated' ) // Already deprecated!
		    || !$current_User->check_perm( 'comment!deprecated', 'moderate', false, $this ) )
		{ // User has no right to deprecated this comment:
			return false;
		}

		$status = 'deprecated';
		$status_order = get_visibility_statuses( 'ordered-array' );
		$status_index = get_visibility_statuses( 'ordered-index', array( 'redirected' ) );
		if( isset( $status_index[ $status ] ) &&
		    isset( $status_order[ $status_index[ $status ] ] ) &&
		    ! empty( $status_order[ $status_index[ $status ] ][3] ) )
		{ // Get color of button icon
			$status_icon_color = $status_order[ $status_index[ $status ] ][3];
		}
		else
		{ // Use grey arrow as default
			$status_icon_color = 'grey';
		}

		$params = array(
			'before' => $before,
			'after'  => $after,
			'text'   => ( ( $text == '#' ) ?  get_icon( 'move_down_'.$status_icon_color ).' '.T_('Deprecate').'!' : $text ),
			'title'  => $title,
			'class'  => $class,
			'glue'   => $glue,
			'save_context' => $save_context,
			'ajax_button'  => $ajax_button,
			'redirect_to'  => $redirect_to,
			'status' => 'deprecated',
			'action' => 'restrict'
		);

		return $this->get_moderation_link( $params );
	}


	/**
	 * Provide link to vote a comment if user has edit rights
	 *
	 * @param string a vote type
	 * @param string a vote value
	 * @param string class name
	 * @param string glue between url params
	 * @param boolean TRUE - to save context(memorized params), to allow append redirect_to param to url
	 * @param boolean true if create AJAX button
	 * @param array Params
	 */
	function get_vote_link( $vote_type, $vote_value, $class = '', $glue = '&amp;', $save_context = true, $ajax_button = false, $params = array() )
	{
		$params = array_merge( array(
				'title_spam'          => T_('Cast a spam vote!'),
				'title_spam_voted'    => T_('You sent a spam vote.'),
				'title_notsure'       => T_('Cast a "not sure" vote!'),
				'title_notsure_voted' => T_('You sent a "not sure" vote.'),
				'title_ok'            => T_('Cast an OK vote!'),
				'title_ok_voted'      => T_('You sent an OK vote.'),
				'title_yes'           => T_('Cast a helpful vote!'),
				'title_yes_voted'     => T_('You sent a "helpful" vote.'),
				'title_no'            => T_('Cast a "not helpful" vote!'),
				'title_no_voted'      => T_('You sent a "not helpful" vote.'),
			), $params );

		global $current_User, $admin_url;

		$this->get_Item();

		$is_voted = false;
		$icon_params = array();
		if( $class == 'voted' )
		{ // Current user already voted for this
			$class = '';
			$is_voted = true;
			$icon_params = array( 'class' => 'voted' );
		}

		switch( $vote_value )
		{
			case "spam":
				$title = $is_voted ? $params['title_spam_voted'] : $params['title_spam'];
				$icon_params['title'] = $title;
				$text = get_icon( 'vote_spam'.( $class != '' ? '_'.$class : '' ), 'imgtag', $icon_params );
				$class .= ' '.button_class();
			break;
			case "notsure":
				$title = $is_voted ? $params['title_notsure_voted'] : $params['title_notsure'];
				$icon_params['title'] = $title;
				$text = get_icon( 'vote_notsure'.( $class != '' ? '_'.$class : '' ), 'imgtag', $icon_params );
				$class .= ' '.button_class();
			break;
			case "ok":
				$title = $is_voted ? $params['title_ok_voted'] : $params['title_ok'];
				$icon_params['title'] = $title;
				$text = get_icon( 'vote_ok'.( $class != '' ? '_'.$class : '' ), 'imgtag', $icon_params );
				$class .= ' '.button_class();
			break;
			case "yes":
				$title = $is_voted ? $params['title_yes_voted'] : $params['title_yes'];
				$icon_params['title'] = $title;
				$text = get_icon( 'thumb_up'.( $class != '' ? '_'.$class : '' ), 'imgtag', $icon_params );
			break;
			case "no":
				$title = $is_voted ? $params['title_no_voted'] : $params['title_no'];
				$icon_params['title'] = $title;
				$text = get_icon( 'thumb_down'.( $class != '' ? '_'.$class : '' ), 'imgtag', $icon_params );
			break;
		}
		if( strpos( $class, 'disabled' ) !== false )
		{ // add rollover action for disabled buttons
			 $class .= ' rollover_sprite';
		}
		$class .= ' action_icon';
		// change classes for bootstrap styles
		if( $is_voted )
		{
			$class .= ' active';
		}
		$class = str_replace( 'disabled', '', $class );

		$r = '<a href="'.$admin_url.'?ctrl=comments'.$glue.'action='.$vote_type.$glue.'value='.$vote_value.$glue.'comment_ID='.$this->ID.'&amp;'.url_crumb('comment');
		if( $save_context )
		{	// Use a param to redirect after action:
			$r .= $glue.'redirect_to='.rawurlencode( regenerate_url( '', 'filter=restore', '', '&' ) );
		}
		$r .= '"';

		if( $ajax_button )
		{
			$r .= ' onclick="setCommentVote('.$this->ID.', \''.$vote_type.'\' , \''.$vote_value.'\' ); return false;"';
		}

		$r .= ' title="'.$title.'" class="'.$class.'">'.$text.'</a>';

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
	 * @param boolean TRUE - to save context(memorized params), to allow append redirect_to param to url
	 * @param boolean true if create AJAX button
	 * @param string Redirect url. NOTE: This param MUST NOT be encoded before sending to this func, because it is executed by this func inside.
	 */
	function deprecate_link( $before = ' ', $after = ' ', $text = '#', $title = '#', $class = '', $glue = '&amp;', $save_context = true, $ajax_button = false, $redirect_to = NULL )
	{
		$deprecate_link = $this->get_deprecate_link( $before, $after, $text, $title, $class, $glue, $save_context, $ajax_button, $redirect_to );
		if( $deprecate_link === false )
		{ // The deprecate link is unavailable for current user and for this comment
			return false;
		}

		// Display the deprecate link
		echo $deprecate_link;

		return true;
	}


	/**
	 * Display link to vote a comment as SPAM if user has edit rights
	 *
	 * @param string to display before link
	 * @param string to display after link
	 * @param string glue between url params
	 * @param boolean TRUE - to save context(memorized params), to allow append redirect_to param to url
	 * @param boolean true if create AJAX button
	 * @param array Params
	 */
	function vote_spam( $before = '', $after = '', $glue = '&amp;', $save_context = true, $ajax_button = false, $params = array())
	{
		$params = array_merge( array(
				'display'             => false, // TRUE - to show this tool on loading(Used to make it visible only when JS is enalbed)
				'title_spam'          => T_('Cast a spam vote!'),
				'title_spam_voted'    => T_('You sent a spam vote.'),
				'title_notsure'       => T_('Cast a "not sure" vote!'),
				'title_notsure_voted' => T_('You sent a "not sure" vote.'),
				'title_ok'            => T_('Cast an OK vote!'),
				'title_ok_voted'      => T_('You sent an OK vote.'),
				'title_empty'         => T_('No votes on spaminess yet.'),
				'button_group_class'  => button_class( 'group' ),
			), $params );

		if( $this->is_meta() )
		{	// Don't allow voting on meta comments:
			return;
		}

		global $current_User;

		$this->get_Item();

		if( !is_logged_in( false ) || !$current_User->check_perm( 'blog_vote_spam_comments', 'edit', false, $this->Item->get_blog_ID() ) )
		{ // If User has no permission to vote spam
			return false;
		}

		echo $before;

		$style = $params['display'] ? '' : ' style="display:none"';
		echo '<div id="vote_spam_'.$this->ID.'" class="vote_spam nowrap"'.$style.'>';

		$vote_result = $this->get_vote_spam_disabled();

		if( $current_User->ID == $this->author_user_ID )
		{ // Display only vote summary for users on their own comments
			$result_summary = $this->get_vote_summary( 'spam', array(
					'result_title' => T_('Spam consensus:'),
					'after_result' => '.',
				) );
			echo ( !empty( $result_summary ) ? $result_summary : $params['title_empty'] );
		}
		else
		{ // Display form to vote
			echo T_('Spam Vote:').' ';

			echo '<span class="'.$params['button_group_class'].'">';
			foreach( $vote_result['icons_statuses'] as $vote_type => $vote_class )
			{ // Print out 3 buttons for spam voting
				echo $this->get_vote_link( 'spam', $vote_type, $vote_class, $glue, $save_context, $ajax_button, $params );
			}
			echo '</span>';

			if( $vote_result['is_voted'] )
			{ // Display vote summary if user already voted on this comment
				echo ' '.$this->get_vote_summary( 'spam', array(
						'result_title' => T_('Consensus:'),
						'after_result' => '.',
					) );
			}
		}

		echo '</div>';

		echo $after;
	}


	/**
	 * Display links to vote a comment as HELPFUL if user is logged
	 *
	 * @param string to display before link
	 * @param string to display after link
	 * @param string glue between url params
	 * @param boolean TRUE - to save context(memorized params), to allow append redirect_to param to url
	 * @param boolean true if create AJAX button
	 * @param array Params
	 */
	function vote_helpful( $before = '', $after = '', $glue = '&amp;', $save_context = true, $ajax_button = false, $params = array() )
	{
		$params = array_merge( array(
				'before_title'          => ' &nbsp; ',
				'skin_ID'               => 0,
				'helpful_text'          => T_('Is this comment helpful?'),
				'title_yes'             => T_('Cast a helpful vote!'),
				'title_yes_voted'       => T_('You sent a "helpful" vote.'),
				'title_noopinion'       => T_('Cast a "no opinion" vote!'),
				'title_noopinion_voted' => T_('You sent a "no opinion" vote.'),
				'title_no'              => T_('Cast a "not helpful" vote!'),
				'title_no_voted'        => T_('You sent a "not helpful" vote.'),
				'title_empty'           => T_('No user votes yet.'),
				'class'                 => '',
				'display_wrapper'       => true, // Use FALSE when you update this from AJAX request
			), $params );

		if( $this->is_meta() )
		{	// Don't allow voting on meta comments:
			return;
		}

		global $current_User;

		$comment_Item = & $this->get_Item();
		$comment_Item->load_Blog();

		if( ! is_logged_in( false ) || ! $comment_Item->Blog->get_setting('allow_rating_comment_helpfulness') )
		{ // If User is not logged OR Users cannot vote
			return false;
		}

		echo $before;

		if( $params['display_wrapper'] )
		{	// Display wrapper:
			echo '<span id="vote_helpful_'.$this->ID.'" class="nowrap evo_voting_panel'.( empty( $params['class'] ) ? '' : ' '.$params['class'] ).'">';
		}

		echo $params['before_title'];

		if( $current_User->ID == $this->author_user_ID )
		{ // Display only vote summary for users on their own comments
			$params['result_title_undecided'] = T_('Helpfulness:');
			$params['after_result'] = '.';
			$result_summary = $this->get_vote_summary( 'helpful', $params );
			echo ( !empty( $result_summary ) ? $result_summary : $params['title_empty'] );
		}
		else
		{ // Display form to vote
			$vote_result = $this->get_vote_helpful_disabled();

			if( !$vote_result['is_voted'] )
			{ // Current user didn't vote on this comment
				$title_text = $params['helpful_text'];
			}
			else
			{ // Display vote summary if user already voted on this comment
				$title_text = $this->get_vote_summary( 'helpful', $params );
			}

			display_voting_form( array(
					'vote_type'             => 'comment',
					'vote_ID'               => $this->ID,
					'skin_ID'               => $params['skin_ID'],
					'display_inappropriate' => false,
					'display_spam'          => false,
					'title_text'            => $title_text.' ',
					'title_like'            => $params['title_yes'],
					'title_like_voted'      => $params['title_yes_voted'],
					'title_noopinion'       => $params['title_noopinion'],
					'title_noopinion_voted' => $params['title_noopinion_voted'],
					'title_dontlike'        => $params['title_no'],
					'title_dontlike_voted'  => $params['title_no_voted'],
				) );
		}

		if( $params['display_wrapper'] )
		{	// Display end of wrapper:
			echo '</span>';
		}

		echo $after;
	}


	/**
	 * Get next status to publish/restrict to this comment
	 *
	 * @param boolean true to get next publish status, and false to get next restrict status
	 * @param string Status that can be used instead of $this->status
	 * @return mixed false if user has no permission | array( status, status_text, icon_color ) otherwise
	 */
	function get_next_status( $publish, $current_status = NULL )
	{
		if( !is_logged_in() )
		{
			return false;
		}

		global $current_User, $blog;

		if( is_null( $current_status ) )
		{ // Use status of comment if param is NULL
			$current_status = $this->status;
		}

		$comment_Item = & $this->get_Item();
		// Comment status cannot be more than post status, restrict it:
		$restrict_max_allowed_status = ( $comment_Item ? $comment_Item->status : '' );
		// Get those statuses which are not allowed for the current User to edit comment in this blog:
		$restricted_statuses = get_restricted_statuses( $blog, 'blog_comment!', 'edit', $current_status, $restrict_max_allowed_status );

		$status_order = get_visibility_statuses( 'ordered-array' );
		$status_index = get_visibility_statuses( 'ordered-index', array( 'redirected' ) );

		$curr_index = $status_index[$current_status];
		if( ( !$publish ) && ( $curr_index == 0 ) && ( $current_status != 'trash' ) && ( $current_status != 'deprecated' ) )
		{ // Increase curr_index value to allow deprecated status for the other statuses from the same public level
			$curr_index = $curr_index + 1;
		}
		$has_perm = false;
		while( !$has_perm && ( $publish ? ( $curr_index < 4 ) : ( $curr_index > 0 ) ) )
		{ // Check until the user has permission or there is no more status to check
			$curr_index = $publish ? ( $curr_index + 1 ) : ( $curr_index - 1 );
			if( in_array( $status_order[$curr_index][0], $restricted_statuses ) )
			{	// The status is restricted for this comment by its item or collection settings:
				$has_perm = false;
			}
			else
			{	// Check if current user can moderate this comment to the next/prev status:
				$has_perm = $current_User->check_perm( 'comment!'.$status_order[$curr_index][0], 'moderate', false, $this );
			}
		}
		if( $has_perm )
		{ // An available status has been found
			$label_index = $publish ? 1 : 2;
			return array( $status_order[$curr_index][0], $status_order[$curr_index][$label_index], $status_order[$curr_index][3] );
		}
		return false;
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
	 * @param boolean TRUE - to save context(memorized params), to allow append redirect_to param to url
	 * @param boolean true if create AJAX button
	 * @param string Redirect url. NOTE: This param MUST NOT be encoded before sending to this func, because it is executed by this func inside.
	 */
	function get_publish_link( $before = ' ', $after = ' ', $text = '#', $title = '#', $class = '', $glue = '&amp;', $save_context = true, $ajax_button = false, $redirect_to = NULL )
	{
		global $current_User, $admin_url;

		if( ! is_logged_in( false ) ) return false;

		$next_status_in_row = $this->get_next_status( true );
		if( !$next_status_in_row )
		{
			return false;
		}

		$publish_status = $next_status_in_row[0];
		$publish_text = $next_status_in_row[1];

		if( $text == '#' ) $text = get_icon( 'publish', 'imgtag' ).' '.$publish_text;
		if( $title == '#' ) $title = T_('Publish this comment!');

		$r = $before;
		$r .= '<a href="'.$admin_url.'?ctrl=comments'.$glue.'action=publish'.$glue.'publish_status='.$publish_status.$glue.'comment_ID='.$this->ID.'&amp;'.url_crumb('comment');
		if( $save_context )
		{	// Use a param to redirect after action:
			if( $redirect_to === NULL )
			{	// Get current url for redirect:
				$redirect_to = regenerate_url( '', 'filter=restore', '', '&' );
			}
			$r .= $glue.'redirect_to='.rawurlencode( $redirect_to );
		}
		$r .= '"';

		if( $ajax_button )
		{	// This is AJAX button:
			$r .= ' onclick="setCommentStatus('.$this->ID.', \''.$publish_status.'\', \''.request_from().'\', \''.$redirect_to.'\'); return false;"';
		}

		$r .= ' title="'.$title.'"';
		if( !empty( $class ) ) $r .= ' class="'.$class.'"';
		$r .= '>'.$text.'</a>';
		$r .= $after;

		return $r;
	}


	/**
	 * Get any kind of moderation link where the user status will be changed.
	 * This function should be private!
	 * TODO: asimo>This function should be used instead the old get_publish_link and get_deprecate_link
	 *
	 * @param array params
	 * @return string the moderate link
	 */
	function get_moderation_link( $params )
	{
		global $admin_url;

		// Redirect url. NOTE: This param MUST NOT be encoded before sending to this func, because it is executed by this func inside:
		$redirect_to = $params['redirect_to'];
		$new_status = $params['status'];
		$action = $params['action'];
		$glue = $params['glue'];
		$status_param = ( $action == 'publish' ) ? 'publish_status' : 'comment_status';

		$r = $params['before'];
		$r .= '<a href="'.$admin_url.'?ctrl=comments'.$glue.'action='.$action.$glue.$status_param.'='.$new_status.$glue.'comment_ID='.$this->ID.'&amp;'.url_crumb('comment');
		if( $params['save_context'] )
		{	// Allow to redirect after action:
			if( $redirect_to === NULL )
			{	// Use current url to redirect ater action:
				$redirect_to = regenerate_url( '', 'filter=restore', '', '&' );
			}
			$r .= $glue.'redirect_to='.rawurlencode( $redirect_to );
		}
		$r .= '"';

		if( $params['ajax_button'] )
		{	// This is AJAX button:
			$comment_type = $this->is_meta() ? 'meta' : 'feedback';
			$r .= ' onclick="setCommentStatus('.$this->ID.', \''.$new_status.'\', \''.request_from().'\', \''.$redirect_to.'\' ); return false;"';
		}

		$status_title = get_visibility_statuses( 'moderation-titles' );
		$r .= ' title="'.$status_title[$new_status].'"';
		if( !empty( $params['class'] ) ) $r .= ' class="'.$params['class'].'"';
		$r .= '>'.$params['text'].'</a>';
		$r .= $params['after'];

		return $r;
	}


	/**
	 * Display link to publish a comment if user has edit rights
	 * TODO: asimo> Use params array instead of so many param
	 *
	 * @param string to display before link
	 * @param string to display after link
	 * @param string link text
	 * @param string link title
	 * @param string class name
	 * @param string glue between url params
	 * @param boolean TRUE - to save context(memorized params), to allow append redirect_to param to url
	 * @param boolean true if create AJAX button
	 * @param string Redirect url. NOTE: This param MUST NOT be encoded before sending to this func, because it is executed by this func inside.
	 * @return boolean TRUE - if the publish link is available
	 */
	function publish_link( $before = ' ', $after = ' ', $text = '#', $title = '#', $class = '', $glue = '&amp;', $save_context = true, $ajax_button = false, $redirect_to = NULL )
	{
		global $current_User;

		if( ! is_logged_in( false ) ) return false;

		if( !$current_User->check_perm( 'comment!CURSTATUS', 'edit', false, $this ) )
		{ // User has no permission to edit this comment
			return false;
		}

		$this->get_Item();
		$target_blog_ID = $this->Item->get_blog_ID();
		// get the current User highest publish status in this comment item blog
		list( $highest_status, $publish_text ) = get_highest_publish_status( 'comment', $target_blog_ID );
		if( compare_visibility_status( $highest_status, $this->status ) <= 0 )
		{ // Current User has no permission to change this comment status to a more public status
			return false;
		}

		$status_order = get_visibility_statuses( 'ordered-array' );
		$status_index = get_visibility_statuses( 'ordered-index', array( 'redirected' ) );
		if( isset( $status_index[ $highest_status ] ) &&
		    isset( $status_order[ $status_index[ $highest_status ] ] ) &&
		    ! empty( $status_order[ $status_index[ $highest_status ] ][3] ) )
		{ // Get color of button icon
			$status_icon_color = $status_order[ $status_index[ $highest_status ] ][3];
		}
		else
		{ // Use green arrow as default
			$status_icon_color = 'green';
		}

		$params = array(
			'before' => $before,
			'after'  => $after,
			'text'   => ( ( $text == '#' ) ? get_icon( 'move_up_'.$status_icon_color, 'imgtag' ).' '.$publish_text : $text ),
			'title'  => ( ( $title == '#' ) ? $publish_text : $title ),
			'class'  => $class,
			'glue'   => $glue,
			'save_context' => $save_context,
			'ajax_button'  => $ajax_button,
			'redirect_to'  => $redirect_to,
			'status' => $highest_status,
			'action' => 'publish'
		);

		// Display the publish link
		echo $this->get_moderation_link( $params );

		return true;
	}


	/**
	 * Display next available level raise/lower status link
	 *
	 * @param array params
	 * @param boolean set true to get raise link and false to get lower link
	 * @param string set any status what is required instead of $this->status
	 * @return boolean true if link is available, false otherwise
	 */
	function next_status_link( $params, $raise, $current_status = NULL )
	{
		global $current_User;

		if( ! is_logged_in( false ) ) return false;

		$next_status_in_row = $this->get_next_status( $raise, $current_status );
		if( !$next_status_in_row )
		{ // Next status is not allowed for current user
			return false;
		}

		$class = empty( $params['class'] ) ? '' : $params['class'].' ';
		unset( $params['class'] );

		$next_status = $next_status_in_row[0];
		$status_text = $next_status_in_row[1];
		if( $raise )
		{
			$action = 'publish';
			$action_icon = get_icon( 'move_up_'.$next_status_in_row[2], 'imgtag', array( 'title' => '' ) );
			$class .= 'btn_raise_status_'.$next_status;
		}
		else
		{
			$action = 'restrict';
			$action_icon = get_icon( 'move_down_'.$next_status_in_row[2], 'imgtag', array( 'title' => '' ) );
			$class .= 'btn_lower_status_'.$next_status;
		}

		$params = array_merge( array(
				'before'       => '',
				'after'        => '',
				'text'         => $action_icon.' '.$status_text,
				'title'        => $status_text,
				'action'       => $action,
				'status'       => $next_status,
				'class'        => $class,
				'glue'         => '&amp;',
				'save_context' => true,
				'ajax_button'  => false,
				'redirect_to'  => NULL,
			), $params
		);

		if( $params['text'] == '#' )
		{
			$params['text'] = $action_icon;
		}

		echo $this->get_moderation_link( $params );
		return true;
	}


	/**
	 * Display raise status link if it is available
	 *
	 * @param array params
	 * @return boolean true if link was displayed, false otherwise
	 */
	function raise_link( $params )
	{
		return $this->next_status_link( $params, true );
	}


	/**
	 * Display lower status link if it is available
	 *
	 * @param array params
	 * @return boolean true if link was displayed, false otherwise
	 */
	function lower_link( $params )
	{
		return $this->next_status_link( $params, false );
	}


	/**
	 * Display moderation status links if it is available
	 *
	 * @param array params
	 * @return boolean true if link was displayed, false otherwise
	 */
	function moderation_links( $params )
	{
		if( ! is_logged_in( false ) )
		{
			return false;
		}

		if( empty( $this->ID ) )
		{	// Happens in Preview
			return false;
		}

		$params = array_merge( array(
				'detect_last' => true, // TRUE if we should find what button is last and visible, FALSE if we have some other buttons after moderation buttons (e.g. button to delete a comment)
			), $params );

		$statuses = get_visibility_statuses( 'ordered-array' );
		$statuses = array_reverse( $statuses );

		$frontoffice_statuses = $this->get_frontoffice_statuses();

		// Get first and last statuses that will be visible buttons
		$first_status_in_row = $this->get_next_status( true, $this->status );
		$last_status_in_row = $this->get_next_status( false, $this->status );
		$first_status = $first_status_in_row ? $first_status_in_row[0] : '';
		$last_status = '';
		if( $params['detect_last'] )
		{ // We should detect what button is last
			$last_status = $last_status_in_row ? $last_status_in_row[0] : '';
		}

		$r = '';
		$prev_status = '';
		foreach( $statuses as $status )
		{ // Print the buttons to increase status
			$next_status_in_row = $this->get_next_status( true, $status[0] );
			if( $next_status_in_row && $prev_status != $next_status_in_row[0] )
			{
				$tmp_params = $params;
				if( $first_status == $next_status_in_row[0] )
				{ // Mark this button as first visible
					$tmp_params['class'] = ( isset( $tmp_params['class'] ) ? $tmp_params['class'] : '' ).' first-child';
					if( $params['detect_last'] && empty( $last_status ) )
					{ // This first button is also last button
						$tmp_params['class'] .= ' last-child';
					}
				}
				if( $next_status_in_row[0] == $first_status_in_row[0] )
				{
					$tmp_params['class'] .= ' btn_next_status';
				}
				if( ! in_array( $next_status_in_row[0], $frontoffice_statuses ) )
				{ // Don't make ajax button for those statuses which are not allowed in the front office
					$tmp_params = array_merge( $tmp_params, array( 'ajax_button' => false ) );
				}
				$r .= $this->next_status_link( $tmp_params, true, $status[0] );
			}
			$prev_status = $next_status_in_row[0];
		}

		$prev_status = '';
		foreach( $statuses as $status )
		{ // Print the buttons to decrease status
			$next_status_in_row = $this->get_next_status( false, $status[0] );

			if( $next_status_in_row && $prev_status != $next_status_in_row[0] )
			{
				$tmp_params = $params;
				$tmp_params['class'] = (isset( $tmp_params['class'] ) ? $tmp_params['class'] : '' );
				if( $params['detect_last'] && $last_status == $next_status_in_row[0] )
				{ // Mark this button as last visible
					$tmp_params['class'] .= ' last-child';
				}
				if( empty( $first_status ) )
				{ // This last button also is first button
					$tmp_params['class'] .= ' first-child';
				}
				if( $next_status_in_row[0] == $last_status_in_row[0] )
				{
					$tmp_params['class'] .= ' btn_next_status';
				}
				if( ! in_array( $next_status_in_row[0], $frontoffice_statuses ) )
				{ // Don't make ajax button for those statuses which are not allowed in the front office
					$tmp_params = array_merge( $tmp_params, array( 'ajax_button' => false ) );
				}
				$r .= $this->next_status_link( $tmp_params, false, $status[0] );
			}
			$prev_status = $next_status_in_row[0];
		}

		return $r;
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
			$msg_type = $this->author_User->get_msgform_possibility();
			if( empty( $msg_type ) )
			{ // message form is not allowed
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
			$msg_type = 'email';
			$form_url = url_add_param( $form_url, 'recipient_id=0' );
		}

		$form_url = url_add_param( $form_url, 'comment_id='.$this->ID.'&amp;post_id='.$this->item_ID
				.'&amp;redirect_to='.rawurlencode(url_rel_to_same_host(regenerate_url('','','','&'), $form_url)) );

		if( $title == '#' )
		{
			if( $msg_type == 'email' )
			{
				$title = T_('Send email to comment author');
			}
			else
			{
				$title = T_('Send message to comment author');
			}
		}
		if( $text == '#' ) $text = get_icon( 'email', 'imgtag', array( 'class' => 'middle', 'title' => $title ) );

		echo $before;
		echo '<a href="'.$form_url.'" title="'.$title.'"';
		if( !empty( $class ) ) echo ' class="'.$class.'"';
		// TODO: have an SEO setting for nofollow here, default to nofollow
		echo ' rel="nofollow"';
		echo '>'.$text.'</a>';
		echo $after;

		return true;
	}


	/**
	 * Generate permalink to this comment.
	 *
	 * Note: This actually only returns the URL, to get a real link, use Comment::get_permanent_link()
	 *
	 * @param string glue between url params
	 * @param string Anchor for meta comment
	 */
	function get_permanent_url( $glue = '&amp;', $meta_anchor = '#' )
	{
		$this->get_Item();
		return $this->Item->get_single_url( 'auto', '', $glue ).'#'.$this->get_anchor();
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
	 * @param string link text or special value: '#', '#icon#', '#text#', '#item#'
	 * @param string link title
	 * @param string class name
	 * @param boolean TRUE - to use attr rel="nofollow"
	 * @param boolean Restrict by inskin statuses
	 * @return string Link
	 */
	function get_permanent_link( $text = '#', $title = '#', $class = '', $nofollow = false, $restrict_status = true )
	{
		if( $restrict_status && ! $this->may_be_seen_in_frontoffice() )
		{
			return '';
		}

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

			case '#item#':
				$comment_Item = & $this->get_Item();
				$text = $comment_Item->get_title( array( 'link_type' => 'none' ) );
				break;
		}

		if( $title == '#' ) $title = T_('Permanent link to this comment');

		$url = $this->get_permanent_url();

		// Display as link
		$r = '<a href="'.$url.'" title="'.$title.'"';
		if( !empty( $class ) ) $r .= ' class="'.$class.'"';
		if( !empty( $nofollow ) ) $r .= ' rel="nofollow"';
		$r .= '>'.$text.'</a>';

		return $r;
	}


	/**
	 * Displays a permalink link to the Comment
	 *
	 * Note: If you only want the permalink URL, use Comment::permanent_url()
	 */
	function permanent_link( $params = array() )
	{
		// Make sure we are not missing any param:
		$params = array_merge( array(
				'before'      => ' ',
				'after'       => ' ',
				'text'        => '#',
				'title'       => '#',
				'class'       => '',
				'nofollow'    => false,
			), $params );

		echo $params['before'];
		echo $this->get_permanent_link( $params['text'], $params['title'], $params['class'], $params['nofollow'] );
		echo $params['after'];
	}


	function get_prerendered_content( $format  = 'htmlbody' )
	{
		global $CommentList, $Plugins, $DB;

		$use_cache = $this->ID && in_array( $format, array('htmlbody', 'entityencoded', 'xml', 'text') );
		if( $use_cache )
		{ // the format/comment can be cached:
			if( empty( $CommentList ) )
			{ // set comments Blog from comment Item
				$this->get_Item();
				$comments_Blog = & $this->Item->get_Blog();
			}
			else
			{ // set comments Blog from CommentList
				$comments_Blog = & $CommentList->Blog;
			}
			$comment_renderers = $this->get_renderers_validated();
			if( empty( $comment_renderers ) )
			{
				return format_to_output( $this->content, $format );
			}
			$comment_renderers = implode( '.', $comment_renderers );
			$cache_key = $format.'/'.$comment_renderers;

			$CommentPrerenderingCache = & get_CommentPrerenderingCache();

			if( isset($CommentPrerenderingCache[$format][$this->ID][$cache_key]) )
			{ // already in PHP cache.
				$r = $CommentPrerenderingCache[$format][$this->ID][$cache_key];
				// Save memory, typically only accessed once.
				unset($CommentPrerenderingCache[$format][$this->ID][$cache_key]);
			}
			else
			{ // try loading into Cache
				if( ! isset($CommentPrerenderingCache[$format]) )
				{ // only do the prefetch loading once.
					$CommentPrerenderingCache[$format] = array();

					$SQL = new SQL( 'Preload prerendered comments content ('.$format.')' );
					$SQL->SELECT( 'cmpr_cmt_ID, cmpr_format, cmpr_renderers, cmpr_content_prerendered' );
					$SQL->FROM( 'T_comments__prerendering' );
					if( empty( $CommentList ) )
					{	// Load prerendered cache for each comment which belongs to this comments Item:
						$SQL->FROM_add( 'INNER JOIN T_comments ON cmpr_cmt_ID = comment_ID' );
						$SQL->WHERE( 'comment_item_ID = '.$this->Item->ID );
					}
					else
					{	// Load prerendered cache for each comment from the CommentList:
						$comments_page_ID_array = $CommentList->get_page_ID_array();
						if( ! empty( $comments_page_ID_array ) )
						{	// If at least one comment is loaded in current comments list:
							$SQL->WHERE( 'cmpr_cmt_ID IN ( '.implode( ',', $comments_page_ID_array ).' )' );
						}
					}
					$SQL->WHERE_and( 'cmpr_format = '.$DB->quote( $format ) );
					$rows = $DB->get_results( $SQL );
					foreach($rows as $row)
					{
						$row_cache_key = $row->cmpr_format.'/'.$row->cmpr_renderers;

						if( ! isset($CommentPrerenderingCache[$format][$row->cmpr_cmt_ID]) )
						{ // init list
							$CommentPrerenderingCache[$format][$row->cmpr_cmt_ID] = array();
						}

						$CommentPrerenderingCache[$format][$row->cmpr_cmt_ID][$row_cache_key] = $row->cmpr_content_prerendered;
					}

					// Get the value for current Comment.
					if( isset($CommentPrerenderingCache[$format][$this->ID][$cache_key]) )
					{
						$r = $CommentPrerenderingCache[$format][$this->ID][$cache_key];
						// Save memory, typically only accessed once.
						unset($CommentPrerenderingCache[$format][$this->ID][$cache_key]);
					}
				}
			}
		}

		if( !isset( $r ) )
		{
			$data = $this->content;
			$Plugins->trigger_event( 'FilterCommentContent', array( 'data' => & $data, 'Comment' => $this ) );
			$r = format_to_output( $data, $format );

			if( $use_cache )
			{ // save into DB (using REPLACE INTO because it may have been pre-rendered by another thread since the SELECT above)
				global $servertimenow;
				$DB->query( 'REPLACE INTO T_comments__prerendering ( cmpr_cmt_ID, cmpr_format, cmpr_renderers, cmpr_content_prerendered, cmpr_datemodified )
					 VALUES ( '.$this->ID.', '.$DB->quote( $format ).', '.$DB->quote( $comment_renderers ).', '.$DB->quote( $r ).', '.$DB->quote( date2mysql( $servertimenow ) ).' )', 'Cache prerendered comment content' );
			}
		}

		// Trigger Display plugins FOR THE STUFF THAT WOULD NOT BE PRERENDERED:
		$r = $Plugins->render( $r, $this->get_renderers_validated(), $format, array( 'Item' => $this->get_Item() ), 'Display' );

		return $r;
	}


	/**
	 * Unset any prerendered content for this item (in PHP cache).
	 */
	function delete_prerendered_content()
	{
		global $DB;

		// Delete DB rows.
		$DB->query( 'DELETE FROM T_comments__prerendering WHERE cmpr_cmt_ID = '.$this->ID );

		// Delete cache.
		$CommentPrerenderingCache = & get_CommentPrerenderingCache();
		foreach( array_keys($CommentPrerenderingCache) as $format )
		{
			unset($CommentPrerenderingCache[$format][$this->ID]);
		}
	}


	/**
	 * Template function: get content of comment
	 *
	 * @param string Output format, see {@link format_to_output()}
	 * @return string
	 */
	function get_content( $format = 'htmlbody' )
	{
		if( $format == 'raw_text' )
		{
			return format_to_output( $this->content, 'text' );
		}
		return $this->get_prerendered_content( $format );
	}


	/**
	 * Template function: display content of comment
	 *
	 * @param string Output format, see {@link format_to_output()}
	 * @param boolean Add ban url action icon after each url or not
	 * @param boolean show comment attachments
	 * @param array attachment display params
	 */
	function content( $format = 'htmlbody', $ban_urls = false, $show_attachments = true, $params = array() )
	{
		global $current_User;
		global $Plugins;

		// Make sure we are not missing any param:
		$params = array_merge( array(
				'before_image'          => '<figure class="evo_image_block">',
				'before_image_legend'   => '<figcaption class="evo_image_legend">',
				'after_image_legend'    => '</figcaption>',
				'after_image'           => '</figure>',
				'image_size'            => 'fit-400x320',
				'image_class'           => '',
				'image_text'            => '', // Text below attached pictures
				'attachments_mode'      => 'read', // read | view
				'attachments_view_text' => '',
			), $params );

		$attachments = array();

		if( $show_attachments )
		{
			if( empty( $this->ID ) && isset( $this->checked_attachments ) )
			{ // PREVIEW
				$attachment_ids = explode( ',', $this->checked_attachments );
				$FileCache = & get_FileCache();
				foreach( $attachment_ids as $ID )
				{
					$File = $FileCache->get_by_ID( $ID, false, false );
					if( $File != NULL )
					{
						$attachments[] = $File;
					}
				}
			}
			else
			{ // Get all Links
				$LinkOwner = new LinkComment( $this );
				$attachments = & $LinkOwner->get_Links();
			}
		}

		$images_above_content = '';
		$images_below_content = '';
		foreach( $attachments as $index => $attachment )
		{
			if( ! empty( $this->ID ) )
			{ // Normal mode when comment exists in DB (NOT PREVIEW mode)
				$Link = $attachment;
				$link_position = $Link->get( 'position' );
				$params['Link'] = $Link;
				$attachment = $attachment->get_File();
			}
			else
			{ // Set default position for preview files
				$link_position = 'aftermore';
			}

			$File = $attachment;

			if( empty( $File ) )
			{ // File object doesn't exist in DB
				global $Debuglog;
				$Debuglog->add( sprintf( 'File object linked to comment #%d does not exist in DB!', $this->ID ), array( 'error', 'files' ) );
				continue;
			}

			if( ! $File->exists() )
			{ // File doesn't exist on the disk
				global $Debuglog;
				$Debuglog->add( sprintf( 'File linked to comment #%d does not exist (%s)!', $this->ID, $File->get_full_path() ), array( 'error', 'files' ) );
				continue;
			}

			$r = '';
			$params['File'] = $File;
			$params['Comment'] = $this;
			$params['data'] = & $r;

			$temp_params = $params;
			foreach( $params as $param_key => $param_value )
			{ // Pass all params by reference, in order to give possibility to modify them by plugin
				// So plugins can add some data before/after image tag (E.g. used by infodots plugin)
				$params[ $param_key ] = & $params[ $param_key ];
			}

			// Prepare params before rendering comment attachment:
			$Plugins->trigger_event_first_true_with_params( 'PrepareForRenderCommentAttachment', $params );

			if( count( $Plugins->trigger_event_first_true( 'RenderCommentAttachment', $params ) ) != 0 )
			{	// This attachment has been rendered by a plugin (to $params['data']), Skip this from core rendering:
				if( $link_position == 'teaser' )
				{ // Image should be displayed above content
					$images_above_content .= $r;
				}
				else
				{ // Image should be displayed below content
					$images_below_content .= $r;
				}
				unset( $attachments[ $index ] );
				continue;
			}

			if( $File->is_image() )
			{ // File is image
				if( $params['attachments_mode'] == 'view' )
				{ // Only preview attachments
					$image_link_rel = '';
					$image_link_to = '';
				}
				else// if( $params['attachments_mode'] == 'read' )
				{ // Read attachments
					$image_link_rel = 'lightbox[c'.$this->ID.']';
					$image_link_to = 'original';
				}

				if( empty( $this->ID ) )
				{ // PREVIEW mode
					$r = $File->get_tag( $params['before_image'], $params['before_image_legend'], $params['after_image_legend'], $params['after_image'], $params['image_size'], $image_link_to, T_('Posted by').' '.$this->get_author_name(), $image_link_rel, $params['image_class'], '', '', '#' );
				}
				else
				{
					$r = $Link->get_tag( array_merge( array(
						'image_link_to'    => $image_link_to,
						'image_link_title' => T_('Posted by').' '.$this->get_author_name(),
						'image_link_rel'   => $image_link_rel,
					), $params ) );
				}

				if( $link_position == 'teaser' )
				{ // Image should be displayed above content
					$images_above_content .= $r;
				}
				else
				{ // Image should be displayed below content
					$images_below_content .= $r;
				}

				unset( $attachments[ $index ] );
			}
			$params = $temp_params;
		}

		if( ! empty( $images_above_content ) )
		{ // Display images above content
			echo $images_above_content;
			if( $params['image_text'] != '' )
			{ // Display info text below pictures
				echo $params['image_text'];
			}
		}

		if( $ban_urls )
		{ // add ban icons if user has edit permission for this comment
			$ban_urls = $current_User->check_perm( 'comment!CURSTATUS', 'edit', false, $this );
		}

		if( $ban_urls )
		{ // ban urls and user has permission
			echo add_ban_icons( $this->get_content( $format ) );
		}
		else
		{ // don't ban urls
			echo $this->get_content( $format );
		}

		if( ! empty( $images_below_content ) )
		{ // Display images below content
			echo $images_below_content;
			if( empty( $images_above_content ) && $params['image_text'] != '' )
			{ // Display info text below pictures
				echo $params['image_text'];
			}
		}

		if( isset( $attachments ) )
		{ // show not image attachments
			$after_docs = '';
			if( count( $attachments ) > 0 )
			{
				echo '<br /><b>'.T_( 'Attachments' ).':</b>';
				echo '<ul class="bFiles">';
				$after_docs = '</ul>';
			}
			foreach( $attachments as $attachment )
			{
				// $attachment is a File in preview mode, but it is a Link in normal mode
				$doc_File = empty( $this->ID ) ? $attachment : $attachment->get_File();
				echo '<li>';
				if( empty( $doc_File ) )
				{ // Broken File object
					$attachment_download_link = '';
					$attachment_name = empty( $attachment ) ? '' : T_( 'Link ID' ).'#'.$attachment->ID;
				}
				elseif( ! $doc_File->exists() )
				{
					$attachment_download_link = '';
					$attachment_name = $doc_File->get_name();
				}
				elseif( $params['attachments_mode'] == 'view' )
				{ // Only preview attachments
					$attachment_download_link = '';
					$attachment_name = $doc_File->get_type();
				}
				else// if( $params['attachments_mode'] == 'read' )
				{ // Read attachments
					$attachment_download_link = action_icon( T_('Download file'), 'download', $doc_File->get_url(), '', 5 ).' ';
					$attachment_name = $doc_File->get_view_link( $doc_File->get_name() );
				}
				echo $attachment_download_link;
				echo $attachment_name;
				if( ! empty( $doc_File ) && $doc_File->exists() )
				{ // Display file size if it exists
					echo ' ('.bytesreadable( $doc_File->get_size() ).')';
				}
				else
				{ // Display warning if File is broken
					echo ' - <span class="red nowrap">'.get_icon( 'warning_yellow' ).' '.T_('Missing attachment!').'</span>';
				}
				if( !empty( $params['attachments_view_text'] ) )
				{
					echo $params['attachments_view_text'];
				}
				echo '</li>';
			}
			echo $after_docs;
		}
	}


	/**
	 * Template function: display checkable list of renderers
	 *
	 * @param array|NULL If given, assume these renderers to be checked.
	 * @params boolean display or not
	 */
	function renderer_checkboxes( $comment_renderers = NULL, $display = true )
	{
		global $Plugins;

		if( is_null($comment_renderers) )
		{
			$comment_renderers = $this->get_renderers();
		}
		$r = $Plugins->get_renderer_checkboxes( $comment_renderers, array( 'Comment' => & $this ) );

		if( $display )
		{
			echo $r;
		}

		return $r;
	}


	/**
	 * Get title of comment, e.g. "Comment from: Foo Bar"
	 *
	 * @param array Params
	 *   'author_format': Formatting of the author (%s gets replaced with
	 *                    the author string)
	 *   'link_text'    : 'avatar' - display author's login with avatar icon,
	 *                    'only_avatar' - display only author's avatar
	 *                    'login' - display only author's login
	 *   'thumb_size'   : Author's avatar size
	 * @return string
	 */
	function get_title( $params = array() )
	{
		// Make sure we are not missing any param:
		$params = array_merge( array(
				'author_format' => '%s',
				'link_text'     => 'name', // avatar_name | avatar_login | only_avatar | name | login | nickname | firstname | lastname | fullname | preferredname
				'thumb_size'    => 'crop-top-32x32', // author's avatar size
				'linked_type'   => false, // TRUE - to make comment type text as link to permament url
			), $params );

		$author = sprintf( $params['author_format'], $this->get_author( $params ) );

		switch( $this->get( 'type' ) )
		{
			case 'comment': // Display a comment:
				$s = T_('Comment from %s');
				break;

			case 'trackback': // Display a trackback:
				$s = T_('Trackback from %s');
				break;

			case 'pingback': // Display a pingback:
				$s = T_('Pingback from %s');
				break;

			case 'meta': // Display a meta comment:
				$href = '';
				if( $params['linked_type'] )
				{	// Make a comment type as link to permanent url:
					$href = 'href="'.$this->get_permanent_url().'"';
				}
				return sprintf( T_('<a %s>Meta comment</a> from %s'), $href, $author );
		}

		return sprintf( $s, $author );
	}


	/**
	 * Get the list of validated renderers for this Comment. This includes stealth plugins etc.
	 * @return array List of validated renderer codes
	 */
	function get_renderers_validated()
	{
		if( ! isset($this->renderers_validated) )
		{
			global $Plugins;
			$this->renderers_validated = $Plugins->validate_renderer_list( $this->get_renderers(), array( 'Comment' => & $this ) );
		}
		return $this->renderers_validated;
	}


	/**
	 * Get the list of renderers for this Comment.
	 * @return array
	 */
	function get_renderers()
	{
		return explode( '.', $this->renderers );
	}


	/**
	 * Set the renderers of the Comment.
	 *
	 * @param array List of renderer codes.
	 * @return boolean true, if it has been set; false if it has not changed
	 */
	function set_renderers( $renderers )
	{
		return $this->set_param( 'renderers', 'string', implode( '.', $renderers ) );
	}


	/**
	 * Template function: display date (datetime) of comment
	 *
	 * @param string date/time format: leave empty to use locale default date format
	 * @param boolean true if you want GMT
	 */
	function date( $format = '', $useGM = false )
	{
		if( empty( $format ) )
		{	// Get the current locale's default date format
			$format = locale_datefmt();
		}

		echo mysql2date( $format, $this->date, $useGM );
	}


	/**
	 * Template function: display time (datetime) of comment
	 *
	 * @param string date/time format: leave empty to use locale default time format
	 *                                 '#short_time' - to use locale default short time format
	 * @param boolean true if you want GMT
	 */
	function time( $format = '', $useGM = false )
	{
		if( empty( $format ) )
		{	// Get the current locale's default time format
			$format = locale_timefmt();
		}

		if( $format == '#short_time' )
		{	// Use short time format of current locale
			$format = locale_shorttimefmt();
		}

		echo mysql2date( $format, $this->date, $useGM );
	}


	/**
	 * Template tag:  display rating
	 */
	function rating( $params = array() )
	{
		if( empty( $this->rating ) )
		{
			return false;
		}

		// Make sure we are not missing any param:
		$params = array_merge( array(
				'before'      => '<div class="comment_rating">',
				'after'       => '</div>',
			), $params );

		echo $params['before'];

		star_rating( $this->rating );

		echo $params['after'];
	}

  /**
	 * Rating input
	 */
	function rating_input( $params = array() )
	{
		global $rsc_uri;

		$params = array_merge( array(
									'before'     => '',
									'after'      => '',
									'label_low'  => T_('Bad'),
									'label_2'    => T_('Poor'),
									'label_3'    => T_('Average'),
									'label_4'    => T_('Good'),
									'label_high' => T_('Excellent'),
									'reset'      => false,
									'item_ID'    => 0, // Set only for new comments without defined item ID
								), $params );

		echo $params['before'];

		if( empty( $this->item_ID ) && !empty( $params['item_ID'] ) )
		{	// Set item ID for form with new comment
			$this->item_ID = $params['item_ID'];
		}
		if( $comment_Item = & $this->get_Item() )
		{
			if( $item_Blog = & $comment_Item->get_Blog() )
			{
				if( $item_Blog->get_setting( 'rating_question' ) != '' )
				{	// Display star rating question
					echo '<div id="comment_rating_question">';
					echo nl2br( $item_Blog->get_setting( 'rating_question' ) );
					echo '</div>';
				}
			}
		}

		echo '<div id="comment_rating">';

		echo $params['label_low'];

		for( $i=1; $i<=5; $i++ )
		{
			echo '<input type="radio" class="radio" name="comment_rating" value="'.$i.'"';
			if( $this->rating == $i )
			{
				echo ' checked="checked"';
			}
			echo ' />';
		}

		echo $params['label_high'];

		$jquery_raty_param = '';
		if( $params['reset'] )
		{ // Init "reset" button
			$jquery_raty_param = 'cancel: true';
			$this->rating_none_input( array( 'before' => '<p>', 'after' => '</p>' ) );
		}

		echo '</div>';

		echo '<script type="text/javascript">
		/* <![CDATA[ */
		jQuery("#comment_rating").html("").raty({
			scoreName: "comment_rating",
			start: '.(int)$this->rating.',
			hintList: ["'.$params['label_low'].'", "'.$params['label_2'].'", "'.$params['label_3'].'", "'.$params['label_4'].'", "'.$params['label_high'].'"],
			width: 110,
			'.$jquery_raty_param.'
		});
		/* ]]> */
		</script>';

		echo $params['after'];
	}


  /**
	 * Rating reset input
	 */
	function rating_none_input( $params = array() )
	{
		$params = array_merge( array(
									'before'    => '',
									'after'     => '',
									'label'     => T_('No rating'),
								), $params );

		echo $params['before'];

		echo '<label><input type="radio" class="radio" name="comment_rating" value="0"';
		if( empty($this->rating) )
		{
			echo ' checked="checked"';
		}
		echo ' />';

		echo $params['label'].'</label>';

		echo $params['after'];
	}


	/**
	 * Get status of comment
	 *
	 * Statuses:
	 * - published
	 * - deprecated
	 * - protected
	 * - private
	 * - draft
	 *
	 * @param string Output format, see {@link format_to_output()}
	 * @param array Params
	 * @return string Status
	 */
	function get_status( $format = 'htmlbody', $params = array() )
	{
		// Make sure we are not missing any param:
		$params = array_merge( array(
				'before' => '',
				'after'  => '',
			), $params );

		$r = $params['before'];

		switch( $format )
		{
			case 'raw':
				$r .= $this->dget( 'status', 'raw' );
				break;

			case 'styled':
				// DEPRECATED: instead use something like: $Comment->format_status( array(	'template' => '<div class="evo_status__banner evo_status__$status$">$status_title$</div>' ) );
				if( $this->is_meta() )
				{
					$r .= get_styled_status( 'meta', T_('Meta') );
				}
				else
				{
					$r .= get_styled_status( $this->status, $this->get( 't_status' ) );
				}
				break;

			default:
				$r .= format_to_output( $this->get( 't_status' ), $format );
				break;
		}

		$r .= $params['after'];

		return $r;
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
	 * @param array Params
	 */
	function status( $format = 'htmlbody', $params = array() )
	{
		// Make sure we are not missing any param:
		$params = array_merge( array(
				'before' => '',
				'after'  => '',
			), $params );

		echo $this->get_status( $format, $params );
	}


	/**
	 * Display status of item in a formatted way, following a provided template
	 *
	 * There are 3 possible variables:
	 * - $status$ = the raw status
	 * - $status_title$ = the human readable text version of the status (translated to current language)
	 * - $tooltip_title$ = the human readable text version of the status for tooltip
	 *
	 * @param array Params
	 */
	function format_status( $params = array() )
	{
		$params = array_merge( array(
				'template'     => '<div class="evo_status evo_status_$status$" data-toggle="tooltip" data-placement="top" title="$tooltip_title$>$status_title$</div>',
				'format'       => 'htmlbody', // Output format, see {@link format_to_output()}
				'status'       => NULL,
				'status_title' => NULL,
			), $params );

		if( $this->is_meta() )
		{	// Don't display a status banner of meta comments:
			return;
		}

		if( is_null( $params['status'] ) )
		{ // Use current status of this comment
			$params['status'] = $this->status;
		}

		if( is_null( $params['status_title'] ) )
		{ // Use current status title of this comment
			$params['status_title'] = $this->get( 't_status' );
		}

		$r = str_replace( array( '$status$', '$status_title$', '$tooltip_title$' ),
			array( $params['status'], $params['status_title'], get_status_tooltip_title( $params['status'] ) ),
			$params['template'] );

		echo format_to_output( $r, $params['format'] );
	}


	/**
	 * Template function: display all statuses and only one is visible by css class name
	 *
	 * Statuses:
	 * - published
	 * - community
	 * - protected
	 * - review
	 * - private
	 * - draft
	 */
	function format_statuses( $params = array() )
	{
		$statuses = get_visibility_statuses( '', array( 'deprecated', 'redirected', 'trash' ) );

		foreach( $statuses as $status => $title )
		{
			$params['status'] = $status;
			$params['status_title'] = $title;
			$this->format_status( $params );
		}
	}


	/**
	 * Template function: display all statuses and only one is visible by css class name
	 *
	 * Statuses:
	 * - published
	 * - community
	 * - protected
	 * - review
	 * - private
	 * - draft
	 */
	function statuses()
	{
		$statuses = get_visibility_statuses( '', array( 'deprecated', 'redirected', 'trash' ) );

		foreach( $statuses as $status => $title )
		{
			echo get_styled_status( $status, $title );
		}
	}


	/**
	 * Execute or schedule various notifications:
	 * - notifications for moderators
	 * - notifications for subscribers
	 *
	 * Should be called only when a new comment was posted or when a comment status was changed to published
	 *
	 * @param integer User ID who executed the action which will be notified, or NULL if it was executed by an anonymous user or current logged in User
	 * @param boolean TRUE if it is notification about new comment, FALSE - for edited comment
	 * @param boolean|string Force sending notifications for members:
	 *                       false   - Auto mode depending on current item statuses
	 *                       'skip'  - Skip notifications
	 *                       'force' - Force notifications
	 *                       'mark'  - Change DB flag to "notified" but do NOT actually send notifications
	 * @param boolean|string Force sending notifications for community (use same values of third param)
	 */
	function handle_notifications( $executed_by_userid = NULL, $is_new_comment = false, $force_members = false, $force_community = false )
	{
		global $Settings, $Messages;

		// Immediate notifications? Asynchronous? Off?
		$notifications_mode = $Settings->get( 'outbound_notifications_mode' );

		if( $notifications_mode == 'off' )
		{	// Don't send notifications:
			return false;
		}

		if( $executed_by_userid === NULL && is_logged_in() )
		{	// Use current user by default:
			global $current_User;
			$executed_by_userid = $current_User->ID;
		}

		// FIRST: Moderators need to be notified immediately, even if the comment is a draft/review.
		// Send email notifications to users who can moderate this comment:
		$already_notified_user_IDs = $this->send_moderation_emails( $executed_by_userid, $is_new_comment );

		// SECOND: Subscribers may be notified asynchornously...

		$notified_flags = array();
		if( $force_members == 'mark' )
		{	// Only change DB flag to "members_notified" but do NOT actually send notifications:
			$force_members = false;
			$notified_flags[] = 'members_notified';
			$Messages->add_to_group( T_('Marking email notifications for members as sent.'), 'note', T_('Sending notifications:') );
		}
		if( $force_community == 'mark' )
		{	// Only change DB flag to "community_notified" but do NOT actually send notifications:
			$force_community = false;
			$notified_flags[] = 'community_notified';
			$Messages->add_to_group( T_('Marking email notifications for community as sent.'), 'note', T_('Sending notifications:') );
		}
		if( ! empty( $notified_flags ) )
		{	// Save the marked processing status to DB:
			$this->set( 'notif_flags', $notified_flags );
			$this->dbupdate();
		}

		// Instead of the above we now check the flags:
		if( ( $force_members != 'force' && $force_community != 'force' ) &&
		    $this->check_notifications_flags( array( 'members_notified', 'community_notified' ) ) )
		{	// All possible notifications have already been sent:
			$Messages->add_to_group( T_('All possible notifications have already been sent: skipping notifications...'), 'note', T_('Sending notifications:') );
			return false;
		}

		// IMMEDIATE vs ASYNCHRONOUS sending:

		if( $notifications_mode == 'immediate' )
		{	// Send email notifications now!:

			// Send email notifications to users who want to receive them for the collection of this comment: (will be different recipients depending on visibility)
			$notified_flags = $this->send_email_notifications( $executed_by_userid, $is_new_comment, $already_notified_user_IDs, $force_members, $force_community );

			// Record that we have just notified the members and/or community:
			$this->set( 'notif_flags', $notified_flags );

			// Record that processing has been done:
			$this->set( 'notif_status', 'finished' );
		}
		elseif( $this->get( 'notif_status' ) != 'todo' && $this->get( 'notif_status' ) != 'started' )
		{	// Create scheduled job to send notifications:

			// CREATE CRON JOB OBJECT:
			load_class( '/cron/model/_cronjob.class.php', 'Cronjob' );
			$comment_Cronjob = new Cronjob();

			// start datetime. We do not want to ping before the post is effectively published:
			$comment_Cronjob->set( 'start_datetime', $this->date );

			// key:
			$comment_Cronjob->set( 'key', 'send-comment-notifications' );

			// params: specify which post this job is supposed to send notifications for:
			$comment_Cronjob->set( 'params', array(
					'comment_ID'                => $this->ID,
					'executed_by_userid'        => $executed_by_userid,
					'is_new_comment'            => $is_new_comment,
					'already_notified_user_IDs' => $already_notified_user_IDs,
					'force_members'             => $force_members,
					'force_community'           => $force_community,
				) );

			// Save cronjob to DB:
			if( $comment_Cronjob->dbinsert() )
			{
				$Messages->add_to_group( T_('Scheduling email notifications for subscribers.'), 'note', T_('Sending notifications:') );

				// Memorize the cron job ID which is going to handle this post:
				$this->set( 'notif_ctsk_ID', $comment_Cronjob->ID );

				// Record that processing has been scheduled:
				$this->set( 'notif_status', 'todo' );
			}
		}

		// Update comment notification params:
		$this->dbupdate();
	}


	/**
	 * Send "comment may need moderation" notifications for those users who have permission to moderate this comment and would like to receive these notifications.
	 *
	 * @param integer User ID who executed the action which will be notified, or NULL if it was executed by an anonymous user or current logged in User
	 * @param boolean TRUE if it is notification about new comment, FALSE - for edited comment
	 * @return array The notified user IDs
	 */
	function send_moderation_emails( $executed_by_userid = NULL, $is_new_comment = false )
	{
		global $Settings, $UserSettings, $Messages;

		if( $executed_by_userid === NULL && is_logged_in() )
		{	// Use current user by default:
			global $current_User;
			$executed_by_userid = $current_User->ID;
		}

		$UserCache = & get_UserCache();

		$comment_Item = & $this->get_Item();
		$comment_item_Blog = & $comment_Item->get_Blog();
		$owner_User = $comment_item_Blog->get_owner_User();

		$notify_users = array();
		$moderators = array();

		if( ! $this->is_meta() )
		{	// Get the moderators which can be notified about this NORMAL comment:
			$moderators_to_notify = $comment_item_Blog->get_comment_moderator_user_data();
			$notify_moderation_setting_name = ( $is_new_comment ? 'notify_comment_moderation' : 'notify_edit_cmt_moderation' );

			foreach( $moderators_to_notify as $moderator )
			{
				$notify_moderator = ( is_null( $moderator->$notify_moderation_setting_name ) ) ? $Settings->get( 'def_'.$notify_moderation_setting_name ) : $moderator->$notify_moderation_setting_name;
				if( $notify_moderator )
				{	// add user to notify:
					$moderators[] = $moderator->user_ID;
				}
			}
			if( $UserSettings->get( $notify_moderation_setting_name, $owner_User->ID ) && is_email( $owner_User->get( 'email' ) ) )
			{	// add blog owner:
				$moderators[] = $owner_User->ID;
			}

			// Load all moderators, and check each edit permission on this comment:
			$UserCache->load_list( $moderators );
			foreach( $moderators as $index => $moderator_ID )
			{
				$moderator_User = $UserCache->get_by_ID( $moderator_ID, false );
				if( ( ! $moderator_User ) || ( ! $moderator_User->check_perm( 'comment!CURSTATUS', 'edit', false, $this ) ) )
				{	// User doesn't exists any more, or has no permission to edit this comment!
					unset( $moderators[$index] );
				}
				else
				{
					$notify_users[$moderator_ID] = 'moderator';
				}
			}
		}

		$notified_user_IDs = array_keys( $notify_users );

		if( $executed_by_userid !== NULL && isset( $notify_users[ $executed_by_userid ] ) )
		{	// Don't notify the user who just created/updated this comment:
			unset( $notify_users[ $executed_by_userid ] );
		}

		// Send emails to the moderators:
		$this->send_email_messages( $notify_users, $is_new_comment );

		// Record that we have notified the moderators (for info only):
		$this->set( 'notif_flags', 'moderators_notified' );
		// Update comment notification params:
		$this->dbupdate();

		$Messages->add_to_group( sprintf( T_('Sending %d email notifications to moderators.'), count( $notify_users ) ), 'note', T_('Sending notifications:') );

		return $notified_user_IDs;
	}


	/**
	 * Send email notifications to subscribed users
	 *
	 * @param integer User ID who executed the action which will be notified, or NULL if it was executed by an anonymous user or current logged in User
	 * @param boolean TRUE if it is notification about new comment, FALSE - for edited comment
	 * @param array The already notified user IDs
	 * @param boolean|string Force sending notifications for members:
	 *                       false - Auto mode depending on current item statuses
	 *                       'skip' - Skip notifications
	 *                       'force' - Force notifications
	 * @param boolean|string Force sending notifications for community (use same values of fourth param)
	 * @return array Notified flags: 'members_notified', 'community_notified'
	 */
	function send_email_notifications( $executed_by_userid = NULL, $is_new_comment = false, $already_notified_user_IDs = array(), $force_members = false, $force_community = false )
	{
		global $DB, $Settings, $UserSettings, $Messages;

		if( $executed_by_userid === NULL && is_logged_in() )
		{	// Use current user by default:
			global $current_User;
			$executed_by_userid = $current_User->ID;
		}

		$comment_Item = & $this->get_Item();
		$comment_item_Blog = & $comment_Item->get_Blog();

		if( ! $comment_item_Blog->get_setting( 'allow_item_subscriptions' ) )
		{	// Subscriptions not enabled!
			$Messages->add_to_group( T_('Skipping email notifications to subscribers because subscriptions are turned Off for this collection.'), 'note', T_('Sending notifications:') );
			return array();
		}

		if( ! in_array( $this->get( 'status' ), array( 'protected', 'community', 'published' ) ) )
		{	// Don't send notifications about comments with not allowed status:
			$status_titles = get_visibility_statuses( '', array() );
			$status_title = isset( $status_titles[ $this->get( 'status' ) ] ) ? $status_titles[ $this->get( 'status' ) ] : $this->get( 'status' );
			$Messages->add_to_group( sprintf( T_('Skipping email notifications to subscribers because status is still: %s.'), $status_title ), 'note', T_('Sending notifications:') );
			return array();
		}

		if( $force_members == 'skip' && $force_community == 'skip' )
		{	// Skip subscriber notifications because of it is forced by param:
			$Messages->add_to_group( T_('Skipping email notifications to subscribers.'), 'note', T_('Sending notifications:') );
			return array();
		}

		if( $force_members == 'force' && $force_community == 'force' )
		{	// Force to members and community:
			$Messages->add_to_group( T_('Force sending email notifications to subscribers...'), 'note', T_('Sending notifications:') );
		}
		elseif( $force_members == 'force' )
		{	// Force to members only:
			$Messages->add_to_group( T_('Force sending email notifications to subscribed members...'), 'note', T_('Sending notifications:') );
		}
		elseif( $force_community == 'force' )
		{	// Force to community only:
			$Messages->add_to_group( T_('Force sending email notifications to other subscribers...'), 'note', T_('Sending notifications:') );
		}

		$notify_members = false;
		$notify_community = false;

		if( $this->get( 'status' ) == 'protected' )
		{	// If the comment is visible for members only...
			if( $force_members == 'force' || ! $this->check_notifications_flags( 'members_notified' ) )
			{	// Members have not been notified yet, do so:
				$notify_members = true;
			}
		}
		elseif( $this->get( 'status' ) == 'community' || $this->get( 'status' ) == 'published' )
		{	// If the comment is visible to the community or is public...
			if( $force_members == 'force' || ! $this->check_notifications_flags( 'members_notified' ) )
			{	// Members have not been notified yet (which means the community has not been notified either), notify them all:
				$notify_members = true;
			}
			if( $force_community == 'force' || ! $this->check_notifications_flags( 'community_notified' ) )
			{	// Community have not been notified yet, do so:
				$notify_community = true;
			}
		}

		if( ! $notify_members && ! $notify_community )
		{	// Everyone has already been notified, nothing to do:
			$Messages->add_to_group( T_('Skipping email notifications to subscribers because they were already notified.'), 'note', T_('Sending notifications:') );
			return array();
		}

		if( $notify_members && $force_members == 'skip' )
		{	// Skip email notifications to members because it is forced by param:
			$Messages->add_to_group( T_('Skipping email notifications to subscribed members.'), 'note', T_('Sending notifications:') );
			$notify_members = false;
		}
		if( $notify_community && $force_community == 'skip' )
		{	// Skip email notifications to community because it is forced by param:
			$Messages->add_to_group( T_('Skipping email notifications to other subscribers.'), 'note', T_('Sending notifications:') );
			$notify_community = false;
		}

		// Set flags what really users will be notified below:
		$notified_flags = array();
		if( $notify_members )
		{	// If members should be notified:
			$notified_flags[] = 'members_notified';
		}
		if( $notify_community )
		{	// If community should be notified:
			$notified_flags[] = 'community_notified';
		}

		if( ! $notify_members && ! $notify_community )
		{	// All notifications are skipped by requested params:
			return $notified_flags;
		}

		$notify_users = array();

		if( ! $this->is_meta() )
		{	// Get the notify users for NORMAL comments:

			// Send only for active users:
			$active_users_condition = 'AND user_status IN ( "activated", "autoactivated" )';

			$except_condition = '';
			if( ! empty( $already_notified_user_IDs ) )
			{	// Set except moderators condition. Exclude moderators who already got a notification email:
				$except_condition .= ' AND user_ID NOT IN ( "'.implode( '", "', $already_notified_user_IDs ).'" )';
			}

			// Check if we need to include the item creator user:
			$creator_User = & $comment_Item->get_creator_User();
			if( $UserSettings->get( 'notify_published_comments', $creator_User->ID ) && ( ! empty( $creator_User->email ) )
				&& ( ! ( in_array( $creator_User->ID, $already_notified_user_IDs ) ) ) )
			{	// Comment creator wants to be notified, and comment author is not a moderator:
				$notify_users[$creator_User->ID] = 'creator';
			}

			// Get list of users who want to be notified about the this post comments:
			if( $comment_item_Blog->get_setting( 'allow_item_subscriptions' ) )
			{	// If item subscriptions is allowed:
				$sql = 'SELECT user_ID
						FROM (
							SELECT DISTINCT isub_user_ID AS user_ID
							FROM T_items__subscriptions
							INNER JOIN T_users ON ( user_ID = isub_user_ID '.$active_users_condition.' )
							WHERE isub_item_ID = '.$comment_Item->ID.'
							AND isub_comments <> 0

							UNION

							SELECT user_ID
							FROM T_coll_settings AS opt
							INNER JOIN T_coll_settings AS sub ON ( sub.cset_coll_ID = opt.cset_coll_ID AND sub.cset_name = "allow_item_subscriptions" AND sub.cset_value = 1 )
							LEFT JOIN T_coll_group_perms ON ( bloggroup_blog_ID = opt.cset_coll_ID AND bloggroup_ismember = 1 )
							INNER JOIN T_users ON ( user_grp_ID = bloggroup_group_ID '.$active_users_condition.' )
							LEFT JOIN T_items__subscriptions ON ( isub_item_ID = '.$comment_Item->ID.' AND isub_user_ID = user_ID )
							WHERE opt.cset_coll_ID = '.$comment_item_Blog->ID.'
								AND opt.cset_name = "opt_out_item_subscription"
								AND opt.cset_value = 1
								AND NOT user_ID IS NULL
								AND ( isub_comments IS NULL OR isub_comments = 1 )

							UNION

							SELECT sug_user_ID
							FROM T_coll_settings AS opt
							INNER JOIN T_coll_settings AS sub ON ( sub.cset_coll_ID = opt.cset_coll_ID AND sub.cset_name = "allow_item_subscriptions" AND sub.cset_value = 1 )
							LEFT JOIN T_coll_group_perms ON ( bloggroup_blog_ID = opt.cset_coll_ID AND bloggroup_ismember = 1 )
							LEFT JOIN T_users__secondary_user_groups ON ( sug_grp_ID = bloggroup_group_ID )
							LEFT JOIN T_items__subscriptions ON ( isub_item_ID = '.$comment_Item->ID.' AND isub_user_ID = sug_user_ID )
							INNER JOIN T_users ON ( user_ID = isub_user_ID '.$active_users_condition.' )
							WHERE opt.cset_coll_ID = '.$comment_item_Blog->ID.'
								AND opt.cset_name = "opt_out_item_subscription"
								AND opt.cset_value = 1
								AND NOT sug_user_ID IS NULL
								AND ( isub_comments IS NULL OR isub_comments = 1 )

							UNION

							SELECT bloguser_user_ID
							FROM T_coll_settings AS opt
							INNER JOIN T_coll_settings AS sub ON ( sub.cset_coll_ID = opt.cset_coll_ID AND sub.cset_name = "allow_item_subscriptions" AND sub.cset_value = 1 )
							LEFT JOIN T_coll_user_perms ON ( bloguser_blog_ID = opt.cset_coll_ID AND bloguser_ismember = 1 )
							LEFT JOIN T_items__subscriptions ON ( isub_item_ID = '.$comment_Item->ID.' AND isub_user_ID = bloguser_user_ID )
							INNER JOIN T_users ON ( user_ID = isub_user_ID '.$active_users_condition.' )
							WHERE opt.cset_coll_ID = '.$comment_item_Blog->ID.'
								AND opt.cset_name = "opt_out_item_subscription"
								AND opt.cset_value = 1
								AND NOT bloguser_user_ID IS NULL
								AND ( isub_comments IS NULL OR isub_comments = 1 )
						) AS users
						WHERE user_ID IS NOT NULL'.$except_condition;

				$notify_list = $DB->get_results( $sql, OBJECT, 'Get list of users who want to be notified about comments of the the post #'.$comment_Item->ID );

				// Preprocess list:
				foreach( $notify_list as $notification )
				{
					if( ! isset( $notify_users[ $notification->user_ID ] ) )
					{	// Don't rewrite a notify type if user already is notified by other type before:
						$notify_users[ $notification->user_ID ] = 'item_subscription';
					}
				}
			}

			// Get list of users who want to be notified about this blog comments:
			if( $comment_item_Blog->get_setting( 'allow_comment_subscriptions' ) )
			{	// If blog subscription is allowed:
				$sql = 'SELECT user_ID
								FROM (
									SELECT DISTINCT sub_user_ID AS user_ID
									FROM T_subscriptions
									INNER JOIN T_users ON ( user_ID = sub_user_ID '.$active_users_condition.' )
									WHERE sub_coll_ID = '.$comment_item_Blog->ID.'
									AND sub_comments <> 0

									UNION

									SELECT user_ID
									FROM T_coll_settings AS opt
									INNER JOIN T_blogs ON ( blog_ID = opt.cset_coll_ID AND blog_advanced_perms = 1 )
									INNER JOIN T_coll_settings AS sub ON ( sub.cset_coll_ID = opt.cset_coll_ID AND sub.cset_name = "allow_subscriptions" AND sub.cset_value = 1 )
									LEFT JOIN T_coll_group_perms ON ( bloggroup_blog_ID = opt.cset_coll_ID AND bloggroup_ismember = 1 )
									INNER JOIN T_users ON ( user_grp_ID = bloggroup_group_ID '.$active_users_condition.' )
									LEFT JOIN T_subscriptions ON ( sub_coll_ID = opt.cset_coll_ID AND sub_user_ID = user_ID )
									WHERE opt.cset_coll_ID = '.$comment_item_Blog->ID.'
										AND opt.cset_name = "opt_out_comment_subscription"
										AND opt.cset_value = 1
										AND NOT user_ID IS NULL
										AND ( ( sub_comments IS NULL OR sub_comments = 1 ) )

									UNION

									SELECT sug_user_ID
									FROM T_coll_settings AS opt
									INNER JOIN T_blogs ON ( blog_ID = opt.cset_coll_ID AND blog_advanced_perms = 1 )
									INNER JOIN T_coll_settings AS sub ON ( sub.cset_coll_ID = opt.cset_coll_ID AND sub.cset_name = "allow_subscriptions" AND sub.cset_value = 1 )
									LEFT JOIN T_coll_group_perms ON ( bloggroup_blog_ID = opt.cset_coll_ID AND bloggroup_ismember = 1 )
									LEFT JOIN T_users__secondary_user_groups ON ( sug_grp_ID = bloggroup_group_ID )
									LEFT JOIN T_subscriptions ON ( sub_coll_ID = opt.cset_coll_ID AND sub_user_ID = sug_user_ID )
									INNER JOIN T_users ON ( user_ID = sub_user_ID '.$active_users_condition.' )
									WHERE opt.cset_coll_ID = '.$comment_item_Blog->ID.'
										AND opt.cset_name = "opt_out_comment_subscription"
										AND opt.cset_value = 1
										AND NOT sug_user_ID IS NULL
										AND ( ( sub_comments IS NULL OR sub_comments = 1 ) )

									UNION

									SELECT bloguser_user_ID
									FROM T_coll_settings AS opt
									INNER JOIN T_blogs ON ( blog_ID = opt.cset_coll_ID AND blog_advanced_perms = 1 )
									INNER JOIN T_coll_settings AS sub ON ( sub.cset_coll_ID = opt.cset_coll_ID AND sub.cset_name = "allow_subscriptions" AND sub.cset_value = 1 )
									LEFT JOIN T_coll_user_perms ON ( bloguser_blog_ID = opt.cset_coll_ID AND bloguser_ismember = 1 )
									LEFT JOIN T_subscriptions ON ( sub_coll_ID = opt.cset_coll_ID AND sub_user_ID = bloguser_user_ID )
									INNER JOIN T_users ON ( user_ID = sub_user_ID '.$active_users_condition.' )
									WHERE opt.cset_coll_ID = '.$comment_item_Blog->ID.'
										AND opt.cset_name = "opt_out_comment_subscription"
										AND opt.cset_value = 1
										AND NOT bloguser_user_ID IS NULL
										AND ( ( sub_comments IS NULL OR sub_comments = 1 ) )
								) AS users
								WHERE NOT user_ID IS NULL'.$except_condition;

				$notify_list = $DB->get_results( $sql, OBJECT, 'Get list of users who want to be notified about comments of the collection #'.$comment_item_Blog->ID );

				// Preprocess list:
				foreach( $notify_list as $notification )
				{
					if( ! isset( $notify_users[ $notification->user_ID ] ) )
					{	// Don't rewrite a notify type if user already is notified by other type before:
						$notify_users[ $notification->user_ID ] = 'blog_subscription';
					}
				}
			}
		}
		else
		{	// Get the notify users for META comments:
			$meta_SQL = new SQL( 'Select users which have permission to the edited_Item #'.$comment_Item->ID.' meta comments and would like to recieve notifications' );
			$meta_SQL->SELECT( 'user_ID, "meta_comment"' );
			$meta_SQL->FROM( 'T_users' );
			$meta_SQL->FROM_add( 'INNER JOIN T_groups ON user_grp_ID = grp_ID' );
			$meta_SQL->FROM_add( 'LEFT JOIN T_groups__groupsettings ON user_grp_ID = gset_grp_ID AND gset_name = "perm_admin"' );
			$meta_SQL->FROM_add( 'LEFT JOIN T_users__usersettings ON user_ID = uset_user_ID AND uset_name = "notify_meta_comments"' );
			$meta_SQL->FROM_add( 'LEFT JOIN T_coll_user_perms ON bloguser_user_ID = user_ID AND bloguser_blog_ID = '.$comment_item_Blog->ID );
			$meta_SQL->FROM_add( 'LEFT JOIN T_coll_group_perms ON bloggroup_blog_ID = '.$comment_item_Blog->ID.'
				AND ( bloggroup_group_ID = user_grp_ID
				      OR bloggroup_group_ID IN ( SELECT sug_grp_ID FROM T_users__secondary_user_groups WHERE sug_user_ID = user_ID ) )' );
			// Check if users have access to the back-office:
			$meta_SQL->WHERE( '( gset_value = "normal" OR gset_value = "restricted" )' );
			// Check if the users would like to receive notifications about new meta comments:
			$meta_SQL->WHERE_and( 'uset_value = "1"'.( $Settings->get( 'def_notify_meta_comments' ) ? ' OR uset_value IS NULL' : '' ) );
			// Check if users are activated:
			$meta_SQL->WHERE_and( 'user_status IN ( "activated", "autoactivated" )' );
			// Check if the users have permission to edit this Item:
			$users_with_item_edit_perms = '( user_ID = '.$DB->quote( $comment_item_Blog->owner_user_ID ).' )';
			$users_with_item_edit_perms .= ' OR ( grp_perm_blogs = "editall" )';
			if( $comment_item_Blog->get( 'advanced_perms' ) )
			{
				$creator_User = & $comment_Item->get_creator_User();
				$creator_User->get_Group();
				$post_creator_user_level = $creator_User->get( 'level' );

				$users_with_item_edit_perms .= ' OR ( bloguser_perm_delpost = 1 ) OR ( bloggroup_perm_delpost = 1 ) OR (
					( ( bloguser_perm_poststatuses LIKE '.$DB->quote( '%'.$comment_Item->get( 'status' ).'%' ).' )
					OR ( bloggroup_perm_poststatuses LIKE '.$DB->quote( '%'.$comment_Item->get( 'status' ).'%' ).' ) )';
				$users_with_item_edit_perms .= ' AND (
						( bloguser_perm_edit = "all"
						OR ( bloguser_perm_edit = "le" AND '.$DB->quote( $post_creator_user_level ).' <= user_level )
						OR ( bloguser_perm_edit = "lt" AND '.$DB->quote( $post_creator_user_level ).' < user_level )
						OR ( bloguser_perm_edit = "own" AND '.$DB->quote( $creator_User->ID ).' = user_ID ) )';
				$users_with_item_edit_perms .= ' OR ( bloggroup_perm_edit = "all"
						OR ( bloggroup_perm_edit = "le" AND '.$DB->quote( $post_creator_user_level ).' <= user_level )
						OR ( bloggroup_perm_edit = "lt" AND '.$DB->quote( $post_creator_user_level ).' < user_level )
						OR ( bloggroup_perm_edit = "own" AND '.$DB->quote( $creator_User->ID ).' = user_ID ) ) )';
				$users_with_item_edit_perms .= ' )';
			}
			$meta_SQL->WHERE_and( $users_with_item_edit_perms );

			// Select users which have permission to the edited_Item meta comments and would like to recieve notifications:
			$notify_users = $DB->get_assoc( $meta_SQL );
		}

		if( $executed_by_userid !== NULL && isset( $notify_users[ $executed_by_userid ] ) )
		{	// Don't notify the user who just created/updated this comment:
			unset( $notify_users[ $executed_by_userid ] );
		}

		// Load all users who will be notified:
		$UserCache = & get_UserCache();
		$UserCache->load_list( array_keys( $notify_users ) );

		$members_count = 0;
		$community_count = 0;
		foreach( $notify_users as $user_ID => $notify_type )
		{	// Check for each subscribed User, if we can send a notification to him depending on current request and Item settings:

			if( ! ( $notify_User = & $UserCache->get_by_ID( $user_ID, false, false ) ) )
			{	// Invalid User, Skip it:
				unset( $notify_users[ $user_ID ] );
				continue;
			}
			// Check if the user is member of the collection:
			$is_member = $notify_User->check_perm( 'blog_ismember', 'view', false, $comment_item_Blog->ID );
			if( $notify_members && $notify_community )
			{	// We can notify all subscribed users:
				if( $is_member )
				{	// Count subscribed member:
					$members_count++;
				}
				else
				{	// Count other subscriber:
					$community_count++;
				}
			}
			elseif( $notify_members )
			{	// We should notify only members:
				if( $is_member )
				{	// Count subscribed member:
					$members_count++;
				}
				else
				{	// Skip not member:
					unset( $notify_users[ $user_ID ] );
				}
			}
			else
			{	// We should notify only community users:
				if( ! $is_member )
				{	// Count subscribed community user:
					$community_count++;
				}
				else
				{	// Skip member:
					unset( $notify_users[ $user_ID ] );
				}
			}
		}

		if( $notify_members )
		{	// Display a message to know how many members are notified:
			$Messages->add_to_group( sprintf( T_('Sending %d email notifications to subscribed members.'), $members_count ), 'note', T_('Sending notifications:') );
		}
		if( $notify_community )
		{	// Display a message to know how many community users are notified:
			$Messages->add_to_group( sprintf( T_('Sending %d email notifications to other subscribers.'), $community_count ), 'note', T_('Sending notifications:') );
		}

		if( empty( $notify_users ) )
		{	// No-one to notify:
			return $notified_flags;
		}

		$this->send_email_messages( $notify_users, $is_new_comment );

		return $notified_flags;
	}


	/**
	 * Send email notifications to users
	 *
	 * @param array Array of users which should be notified, where key is User ID and value is a notify type:
	 *              - 'moderator'
	 *              - 'creator'
	 *              - 'blog_subscription'
	 *              - 'item_subscription'
	 *              - 'meta_comment'
	 * @param boolean TRUE if it is notification about new comment, FALSE - for edited comment
	 */
	function send_email_messages( $notify_users, $is_new_comment = false )
	{
		global $debug, $Debuglog;

		$UserCache = & get_UserCache();

		$comment_Item = & $this->get_Item();
		$comment_item_Blog = & $comment_Item->get_Blog();

		if( ! count( $notify_users ) )
		{	// No-one to notify:
			return;
		}

		/*
		 * We have a list of user IDs to notify:
		 */

		// TODO: dh> this reveals the comments author's email address to all subscribers!!
		//           $notify_from should get used by default, unless the user has opted in to be the sender!
		// fp>If the subscriber has permission to moderate the comments, he SHOULD receive the email address.
		// Get author email address. It will be visible for moderators/blog/post owners only -- NOT for other subscribers
		if( $this->get_author_User() )
		{ // Comment from a registered user:
			$reply_to = $this->author_User->get( 'email' );
			$author_name = $this->author_User->get_username();
			$author_user_ID = $this->author_User->ID;
		}
		elseif( ! empty( $this->author_email ) )
		{ // non-member, but with email address:
			$reply_to = $this->author_email;
			$author_name = $this->dget( 'author' );
			$author_user_ID = NULL;
		}
		else
		{ // Fallback (we have no email address):  fp>TODO: or the subscriber is not allowed to view it.
			$reply_to = NULL;
			$author_name =  $this->dget( 'author' );
			$author_user_ID = NULL;
		}

		// Load all users who will be notified, becasuse another way the send_mail_to_User funtion would load them one by one:
		$UserCache->load_list( array_keys( $notify_users ) );

		// Load a list with the blocked emails  in cache
		load_blocked_emails( array_keys( $notify_users ) );

		// Send emails:
		foreach( $notify_users as $notify_user_ID => $notify_type )
		{
			// get data content
			$notify_User = $UserCache->get_by_ID( $notify_user_ID );
			$notify_email = $notify_User->get( 'email' );

			// init notification setting
			locale_temp_switch( $notify_User->get( 'locale' ) );
			$notify_user_Group = $notify_User->get_Group();
			$notify_full = ( ( $notify_type == 'moderator' ) && ( $notify_user_Group->check_perm( 'comment_moderation_notif', 'full' ) )
							|| ( $notify_user_Group->check_perm( 'comment_subscription_notif', 'full' ) ) );

			switch( $this->type )
			{
				case 'trackback':
					/* TRANS: Subject of the mail to send on new trackbacks. First %s is the blog's shortname, the second %s is the item's title. */
					$subject = sprintf( T_('[%s] New trackback on "%s"'), $comment_item_Blog->get('shortname'), $comment_Item->get('title') );
					break;

				case 'meta':
					/* TRANS: Subject of the mail to send on new meta comments. First %s is author login, the second %s is the item's title. */
					$subject = sprintf( T_( '%s posted a new meta comment on "%s"' ), $author_name, $comment_Item->get('title') );
					break;

				default:
					if( $notify_type == 'moderator' )
					{	// Subject for moderators:
						if( $this->status == 'draft' || $this->status == 'review' )
						{
							/* TRANS: Subject of the mail to send on new comments to moderators. First %s is blog name, the second %s is the item's title. */
							$subject = T_('[%s] New comment awaiting moderation on "%s"');
						}
						else
						{
							/* TRANS: Subject of the mail to send on new comments to moderators. First %s is blog name, the second %s is the item's title. */
							$subject = T_('[%s] New comment may need moderation on "%s"');
						}
					}
					else
					{	// Subject for subscribed users:
						/* TRANS: Subject of the mail to send on new comments to subscribed users. First %s is blog name, the second %s is the item's title. */
						$subject = T_('[%s] New comment on "%s"');
					}
					$subject = sprintf( $subject, $comment_item_Blog->get('shortname'), $comment_Item->get('title') );
			}

			switch( $notify_type )
			{
				case 'moderator': // moderation email
				case 'creator': // user is the creator of the post
					$user_reply_to = $reply_to;
					break;

				case 'blog_subscription': // blog subscription
				case 'item_subscription': // item subscription
				case 'meta_comment': // meta comment notification
					$user_reply_to = NULL;
					break;

				default: // Invalid notify type
					debug_die( 'Unknown user subscription type' );
			}

			$email_template_params = array(
					'notify_full'    => $notify_full,
					'Comment'        => $this,
					'Blog'           => $comment_item_Blog,
					'Item'           => $comment_Item,
					'author_name'    => $author_name,
					'author_ID'      => $author_user_ID,
					'notify_type'    => $notify_type,
					'recipient_User' => $notify_User,
					'is_new_comment' => $is_new_comment,
				);

			if( $debug )
			{
				$notify_message = mail_template( 'comment_new', 'text', $email_template_params );
				$mail_dump = "Sending notification to $notify_email:<pre>Subject: $subject\n$notify_message</pre>";

				if( $debug >= 2 )
				{ // output mail content - NOTE: this will kill sending of headers.
					echo "<p>$mail_dump</p>";
				}

				$Debuglog->add( $mail_dump, 'notification' );
			}

			// Send the email:
			// Note: Note activated users won't get notification email
			send_mail_to_User( $notify_user_ID, $subject, 'comment_new', $email_template_params, false, array( 'Reply-To' => $user_reply_to ) );

			blocked_emails_memorize( $notify_User->email );

			locale_restore_previous();
		}

		blocked_emails_display();
	}


	/**
	 * Send "comment spam" emails for those users who have permission to moderate this comment.
	 */
	function send_vote_spam_emails()
	{
		global $current_User, $Settings, $UserSettings;

		if( ! is_logged_in() )
		{	// Only loggen in users can vote on comments
			return;
		}

		if( $this->is_meta() )
		{	// Meta comments have no spam voting
			return;
		}

		$UserCache = & get_UserCache();

		$comment_Item = & $this->get_Item();
		$comment_item_Blog = & $comment_Item->get_Blog();
		$coll_owner_User = $comment_item_Blog->get_owner_User();

		$moderators = array();

		$moderators_to_notify = $comment_item_Blog->get_comment_moderator_user_data();

		foreach( $moderators_to_notify as $moderator )
		{
			$notify_moderator = is_null( $moderator->notify_spam_cmt_moderation ) ? $Settings->get( 'def_notify_spam_cmt_moderation' ) : $moderator->notify_spam_cmt_moderation;
			if( $notify_moderator )
			{	// Include user to notify because of enabled setting:
				$moderators[] = $moderator->user_ID;
			}
		}
		if( $UserSettings->get( 'notify_spam_cmt_moderation', $coll_owner_User->ID ) && is_email( $coll_owner_User->get( 'email' ) ) )
		{	// Include collection owner:
			$moderators[] = $coll_owner_User->ID;
		}

		$email_subject = sprintf( T_('[%s] Spam comment may need moderation on "%s"'), $comment_item_Blog->get( 'shortname' ), $comment_Item->get( 'title' ) );
		$email_template_params = array(
				'Comment'  => $this,
				'Blog'     => $comment_item_Blog,
				'Item'     => $comment_Item,
				'voter_ID' => $current_User->ID,
			);

		// Load all moderators, and check each edit permission on this comment:
		$UserCache->load_list( $moderators );
		foreach( $moderators as $moderator_ID )
		{
			if( $moderator_ID == $current_User->ID )
			{	// Don't send email to the voter:
				continue;
			}
			$moderator_User = $UserCache->get_by_ID( $moderator_ID, false );
			if( $moderator_User && $moderator_User->check_perm( 'comment!CURSTATUS', 'edit', false, $this ) )
			{	// If moderator has a permission to edit this comment:
				$moderator_Group = $moderator_User->get_Group();
				$email_template_params['notify_full'] = $moderator_Group->check_perm( 'comment_moderation_notif', 'full' );

				// Send email to the moderator:
				send_mail_to_User( $moderator_ID, $email_subject, 'comment_spam', $email_template_params );
			}
		}
	}


	/**
	 * Handle quick moderation secret param: checks if comment secret should expire after first comment moderation, and delete the secret if required
	 * This should be called after every kind of commment moderation
	 */
	function handle_qm_secret( $save_comment = false )
	{
		$comment_Item = & $this->get_Item();
		$comment_Item->load_Blog();
		if( $comment_Item->Blog->get_setting( 'comment_quick_moderation' ) == 'expire' )
		{ // comment secret expires after first comment moderation
			$this->set( 'secret', NULL );
		}
		if( $save_comment )
		{
			$this->dbupdate();
		}
	}


	/**
	* Get a list of those comment statuses which can be displayed in the front office
	*
	* @return array Front office statuses in the comment's collection
	*/
	function get_frontoffice_statuses()
	{
		if( ! ( $comment_Item = & $this->get_Item() ) ||
		    ! ( $comment_blog_ID = $comment_Item->get_blog_ID() ) )
		{	// Comment's collection ID must be detected to get front-office comment statuses:
			return array();
		}

		return get_inskin_statuses( $comment_blog_ID, 'comment' );
	}


	/**
	 * Check if this comment may be seen in front office
	 *
	 * @return boolean true if the comment status is used to display on front office, false otherwise
	 */
	function may_be_seen_in_frontoffice()
	{
		$current_status_permvalue = get_status_permvalue( $this->status );
		$frontoffice_statuses_permvalue = get_status_permvalue( $this->get_frontoffice_statuses() );

		return ( $current_status_permvalue & $frontoffice_statuses_permvalue ) ? true : false;
	}


	/**
	 * Trigger event AfterCommentUpdate after calling parent method.
	 *
	 * @return boolean true on success
	 */
	function dbupdate()
	{
		global $Plugins, $DB;

		if( isset( $this->previous_status ) )
		{	// Restrict comment status by parent item:
			// (ONLY if current request is updating comment status)
			$this->restrict_status( true );
		}

		$dbchanges = $this->dbchanges;

		if( count( $dbchanges ) )
		{
			$this->set_last_touched_date();
		}

		$DB->begin();

		// Check previous comment status was visible on front-office:
		$was_front_office_visible = ( isset( $this->previous_status ) &&
			$this->can_be_displayed( $this->previous_status ) );

		// Check we should refresh contents last updated date of the PARENT Item
		// if this Comment was the latest FRONT-OFFICE VISIBLE Comment of the parent Item:
		$refresh_parent_item_contents_last_updated_date = ( $was_front_office_visible && // This Comment was FRONT-OFFICE VISIBLE
			( ! $this->may_be_seen_in_frontoffice() ) && // This Comment is NOT FRONT-OFFICE VISIBLE currently
			( $comment_Item = & $this->get_Item() ) && // Get parent Item
			( $item_latest_Comment = & $comment_Item->get_latest_Comment() ) && // Get the latest Comment of the parent Item
			( $item_latest_Comment->ID == $this->ID ) ); // This Comment is the latest comment of the parent Item

		$ItemCache = & get_ItemCache();
		$ItemCache->clear();

		// Check we should refresh contents last updated date of the PREVIOUS Item
		// if this Comment was the latest FRONT-OFFICE VISIBLE Comment of the previous Item:
		$refresh_previous_item_contents_last_updated_date = ( ! empty( $this->previous_item_ID ) && // This Comment is moving to another Item
			( $was_front_office_visible ) && // This Comment was FRONT-OFFICE VISIBLE for previous Item
			( $previous_Item = & $ItemCache->get_by_ID( $this->previous_item_ID, false, false ) ) && // Get the previous Item
			( $previous_item_latest_Comment = & $previous_Item->get_latest_Comment() ) && // Get the latest Comment of the previous Item
			( $previous_item_latest_Comment->ID == $this->ID ) ); // This Comment was the latest comment of the previous Item

		if( ( $r = parent::dbupdate() ) !== false )
		{
			if( isset( $dbchanges['comment_content'] ) || isset( $dbchanges['comment_renderers'] ) )
			{	// Delete a prerendered content if content or text renderers have been updated:
				$this->delete_prerendered_content();
			}

			$update_item_contents_last_updated_date = false;
			if( $this->may_be_seen_in_frontoffice() )
			{	// Update contents last update date of the comment's post ONLY when the updated comment may be seen in frontoffice:
				if( isset( $dbchanges['comment_content'] ) ||
				    isset( $dbchanges['comment_rating'] ) ||
				    isset( $dbchanges['comment_item_ID'] ) ||
				    ( isset( $dbchanges['comment_status'] ) && isset( $this->previous_status ) && ! $this->can_be_displayed( $this->previous_status ) ) )
				{	// AND if content, rating or parent Item have been updated
					//     or status has been updated from NOT front-office status into some front-office status:
					$update_item_contents_last_updated_date = true;
				}
			}

			if( ! empty( $this->previous_item_ID ) )
			{	// If comment has been moved from another post:
				if( $previous_Item = & $ItemCache->get_by_ID( $this->previous_item_ID, false, false ) )
				{	// Update ONLY last touched date of previous item:
					$previous_Item->update_last_touched_date( false, true );
					if( $refresh_previous_item_contents_last_updated_date )
					{	// Refresh contents last updated ts of the previous parent Item if this Comment was the latest FRONT-OFFICE VISIBLE Comment of the previous parent Item:
						$previous_Item->refresh_contents_last_updated_ts();
					}
				}

				// Also move all child comments to new post
				$child_comment_IDs = $this->get_child_comment_IDs();
				if( count( $child_comment_IDs ) )
				{
					$DB->query( 'UPDATE T_comments
						  SET comment_item_ID = '.$DB->quote( $this->item_ID ).'
						WHERE comment_ID IN ( '.$DB->quote( $child_comment_IDs ).' )' );
				}
			}

			if( $refresh_parent_item_contents_last_updated_date )
			{	// Refresh contents last updated ts of the parent Item:
				$comment_Item->refresh_contents_last_updated_ts();
			}

			$this->update_last_touched_date( true, $update_item_contents_last_updated_date );

			$DB->commit();

			$Plugins->trigger_event( 'AfterCommentUpdate', $params = array( 'Comment' => & $this, 'dbchanges' => $dbchanges ) );
		}
		else
		{
			$DB->rollback();
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

		if( isset( $this->previous_status ) )
		{	// Restrict comment status by parent item:
			// (ONLY if current request is updating comment status)
			$this->restrict_status( true );
		}

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

		// set comment secret for quick moderation
		// fp> users have requested this for all comments
		$comment_Item = & $this->get_Item();
		$comment_Blog = & $comment_Item->get_Blog();
		if( $comment_Blog->get_setting( 'comment_quick_moderation' ) != 'never' )
		{ // quick moderation is permitted, set comment secret
			$this->set( 'secret', generate_random_key() );
		}

		$this->set_last_touched_date();

		$dbchanges = $this->dbchanges;

		if( $r = parent::dbinsert() )
		{
			// Update last touched date of item if comment is created with ANY status,
			// But update contents last updated date of item if comment is created ONLY in published status(Public, Community or Members):
			$this->update_last_touched_date( true, $this->may_be_seen_in_frontoffice() );
			// Plugin event to call after new comment insert:
			$Plugins->trigger_event( 'AfterCommentInsert', $params = array( 'Comment' => & $this, 'dbchanges' => $dbchanges ) );
		}

		return $r;
	}


	/**
	 * Trigger event AfterCommentDelete after calling parent method.
	 *
	 * @param boolean set true to force permanent delete, leave false for "move to trash/recylce"
	 * @param boolean TRUE to use transaction
	 * @return boolean true on success
	 */
	function dbdelete( $force_permanent_delete = false, $use_transaction = true )
	{
		global $Plugins, $DB;

		if( $use_transaction )
		{
			$DB->begin();
		}

		if( $this->status != 'trash' )
		{ // The comment was not recycled yet
			if( $this->has_replies() )
			{ // Move the replies to the one level up
				$new_parent_ID = !empty( $this->in_reply_to_cmt_ID ) ? $DB->quote( $this->in_reply_to_cmt_ID ) : 'NULL';
				$DB->query( 'UPDATE T_comments
				    SET comment_in_reply_to_cmt_ID = '.$new_parent_ID.'
				  WHERE comment_in_reply_to_cmt_ID = '.$this->ID );
			}
		}

		// Check we should refresh contents last updated date of the parent Item after
		// deleting of this Comment because it was the latest comment of the parent Item:
		$refresh_parent_item_contents_last_updated_date = ( ( $comment_Item = & $this->get_Item() ) &&
			( $item_latest_Comment = & $comment_Item->get_latest_Comment() ) &&
			( $item_latest_Comment->ID == $this->ID ) );

		if( $force_permanent_delete || ( $this->status == 'trash' ) || $this->is_meta() )
		{	// Permamently delete comment from DB:
			// remember ID, because parent method resets it to 0
			$old_ID = $this->ID;

			if( $r = parent::dbdelete() )
			{
				// re-set the ID for the Plugin event
				$this->ID = $old_ID;

				$Plugins->trigger_event( 'AfterCommentDelete', $params = array( 'Comment' => & $this ) );

				$this->ID = 0;
			}
		}
		else
		{ // don't delete, just move to the trash:
			$this->set( 'status', 'trash' );
			$r = $this->dbupdate();
		}

		if( $r )
		{
			if( $this->ID == 0 )
			{	// Update only last touched date of item if comment was deleted from DB,
				// Don't call this when comment was recycled because we already called this on dbupdate() above:
				$this->update_last_touched_date();
			}

			if( $use_transaction )
			{
				$DB->commit();
			}

			if( $refresh_parent_item_contents_last_updated_date )
			{	// Refresh contents last updated ts of parent Item if this Comment was the latest Comment of parent Item:
				$comment_Item->refresh_contents_last_updated_ts();
			}
		}
		else
		{
			if( $use_transaction )
			{
				$DB->rollback();
			}
		}

		return $r;
	}


	/**
	 * Displays link for replying to the Comment if blog's setting allows this action
	 *
	 * @param string to display before link
	 * @param string to display after link
	 * @param string link text
	 * @param string link title
	 * @param string class name
	 */
	function reply_link( $before = ' ', $after = ' ', $text = '#', $title = '#', $class = '' )
	{
		if( empty( $this->ID ) )
		{	// Happens in Preview
			return false;
		}

		$this->get_Item();
		$this->Item->load_Blog();

		if( ! $this->Item->Blog->get_setting( 'threaded_comments' ) )
		{	// A blog's setting is OFF for replying to the comment
			return false;
		}

		if( ! $this->Item->can_comment( NULL ) )
		{	// If current User cannot create a comment for the Item:
			return false;
		}

		// ID of a replying comment
		$comment_reply_ID = param( 'reply_ID', 'integer', 0 );

		if( $text == '#' )
		{	// Use default text
			$text = $this->ID == $comment_reply_ID ? T_('You are currently replying to this comment') : T_('Reply to this comment');
		}
		if( $title == '#' )
		{	// Use default title
			$title = T_('Reply to this comment');
		}

		$class .= ' comment_reply';
		if( $this->ID == $comment_reply_ID )
		{	// This comment is using for replying now
			$class .= ' active';
		}
		$class = ' class="'.trim( $class ).'"';

		// Initialize an url to reply on comment:
		if( is_admin_page() )
		{	// for back-office:
			global $admin_url;
			$url = $admin_url.'?ctrl=items&amp;blog='.$this->Item->Blog->ID.'&amp;p='.$this->Item->ID.( $this->is_meta() ? '&amp;comment_type=meta' : '' ).'&amp;reply_ID='.$this->ID.'#comment_checkchanges';
		}
		else
		{	// for front-office:
			$url = url_add_param( $this->Item->get_permanent_url(), 'reply_ID='.$this->ID.( $this->is_meta() ? '&amp;comment_type=meta' : '' ).'&amp;redir=no' ).'#'.( $this->is_meta() ? 'meta_' : '' ).'form_p'.$this->Item->ID;
		}

		echo $before;

		// Display a link
		echo '<a href="'.$url.'" title="'.$title.'"'.$class.' rel="'.$this->ID.'">'.$text.'</a>';

		echo $after;

		return true;
	}


	/**
	 * Check if comment has the replies
	 */
	function has_replies()
	{
		global $cache_comments_has_replies;

		if( ! isset( $cache_comments_has_replies ) )
		{ // Init an array to cache
			$cache_comments_has_replies = array();
		}

		if( ! isset( $cache_comments_has_replies[ $this->item_ID ] ) )
		{ // Get all comments that have the replies from DB (first time)
			global $DB;

			// Cache a result
			$SQL = new SQL();
			$SQL->SELECT( 'DISTINCT ( comment_in_reply_to_cmt_ID ), comment_ID' );
			$SQL->FROM( 'T_comments' );
			$SQL->WHERE( 'comment_in_reply_to_cmt_ID IS NOT NULL' );
			$SQL->WHERE_and( 'comment_item_ID = '.$this->item_ID );

			// Init an array to cache a result from current item
			$cache_comments_has_replies[ $this->item_ID ] = $DB->get_assoc( $SQL->get() );
		}

		// Get a result from cache
		return isset( $cache_comments_has_replies[ $this->item_ID ][ $this->ID ] );
	}


	/**
	 * Set field last_touched_ts
	 */
	function set_last_touched_date()
	{
		global $localtimenow;
		$this->set_param( 'last_touched_ts', 'date', date2mysql( $localtimenow ) );
	}


	/**
	 * Update field last_touched_ts
	 *
	 * @param boolean update comment's post last touched ts as well or not
	 * @param boolean Use TRUE to update field contents_last_updated_ts of the comment's item
	 */
	function update_last_touched_date( $update_item_last_touched_ts = true, $update_item_contents_last_updated_ts = false )
	{
		global $localtimenow, $current_User;

		if( $this->is_meta() )
		{ // Don't touch Item when this Comment is meta
			return;
		}

		$comment_Item = & $this->get_Item();

		if( empty( $comment_Item ) )
		{ // Don't execute the following code because this comment is broken
			return;
		}

		$timestamp = date2mysql( $localtimenow );

		if( $this->ID && ( $this->last_touched_ts !== $timestamp ) )
		{ // If the comment was not deleted then update last touched date
			$this->set_param( 'last_touched_ts', 'date', $timestamp );
			$this->dbupdate();
		}

		if( $update_item_last_touched_ts || $update_item_contents_last_updated_ts )
		{	// Update last touched timestamp or content last update timestamp of the Item:
			$comment_Item->update_last_touched_date( true, $update_item_last_touched_ts, $update_item_contents_last_updated_ts );
		}
	}


	/**
	 * Get a permalink link to the Item of this Comment
	 *
	 * @param array Params
	 * @return string Link to Item with anchor to Comment
	 */
	function get_permanent_item_link( $params = array() )
	{
		$params = array_merge( array(
				'text'            => '#item#',
				'title'           => '#',
				'class'           => '',
				'nofollow'        => false,
				'restrict_status' => false,
			), $params );

		return $this->get_permanent_link( $params['text'], $params['title'], $params['class'], $params['nofollow'], $params['restrict_status'] );
	}


	/**
	 * Check if this comment is meta
	 *
	 * @return boolean TRUE if this comment is meta
	 */
	function is_meta()
	{
		return $this->type == 'meta';
	}


	/**
	 * Get all child comment IDs
	 *
	 * @param integer Parent comment ID
	 * @return array Comment IDs
	 */
	function get_child_comment_IDs( $parent_comment_ID = NULL )
	{
		global $DB;

		if( $parent_comment_ID === NULL )
		{ // Use current comment ID as main parent ID
			$parent_comment_ID = $this->ID;
		}

		// Get child comment of level 1
		$comments_SQL = new SQL();
		$comments_SQL->SELECT( 'comment_ID' );
		$comments_SQL->FROM( 'T_comments' );
		$comments_SQL->WHERE( 'comment_in_reply_to_cmt_ID = '.$parent_comment_ID );
		$parent_comment_IDs = $DB->get_col( $comments_SQL->get() );

		$comment_IDs = array();
		foreach( $parent_comment_IDs as $comment_ID )
		{ // Get all children recursively
			$comment_IDs[] = $comment_ID;
			$child_comment_IDs = $this->get_child_comment_IDs( $comment_ID );
			foreach( $child_comment_IDs as $child_comment_ID )
			{
				$comment_IDs[] = $child_comment_ID;
			}
		}

		return $comment_IDs;
	}


	/*
	 * Get max allowed comment status depending on parent item status
	 *
	 * @param string Status key to check if it is allowed, NULL- to use current comment status
	 * @return string Status key
	 */
	function get_allowed_status( $current_status = NULL )
	{
		$comment_Item = & $this->get_Item();
		$item_Blog = & $comment_Item->get_Blog();

		if( $current_status === NULL )
		{	// Use current comment status:
			$current_status = $this->get( 'status' );
		}

		// Restrict status to max allowed for item collection:
		$item_restricted_status = $item_Blog->get_allowed_item_status( $comment_Item->get( 'status' ) );
		if( empty( $item_restricted_status ) )
		{	// If max allowed status is not detected because for example current User has no perm to item status,
			// then use current status of the Item in order to restrict max comment status below:
			$item_restricted_status = $comment_Item->get( 'status' );
		}

		// Comment status cannot be more than post status, restrict it:
		$restricted_statuses = get_restricted_statuses( $item_Blog->ID, 'blog_comment!', 'edit', '', $item_restricted_status, $this );

		// Get all visibility statuses:
		$visibility_statuses = get_visibility_statuses( '', $restricted_statuses );

		// Find what max comment status we can use depending on parent item:
		$status_order = 0;
		$comment_status_order = NULL;
		$item_status_order = NULL;
		$max_allowed_comment_status = '';
		foreach( $visibility_statuses as $visibility_status => $visibility_status_title )
		{
			if( $status_order == 0 )
			{	// Set max allowed comment status:
				$max_allowed_comment_status = $visibility_status;
			}
			if( $visibility_status == $current_status )
			{	// Set an order for current status of this comment:
				$comment_status_order = $status_order;
			}
			if( $visibility_status == $item_restricted_status )
			{	// Set an order for max allowed status of the comment's item:
				$item_status_order = $status_order;
			}
			$status_order++;
		}

		if( $comment_status_order === NULL )
		{	// Current comment status is higher than max allowed by parent item,
			// So restrict it by max allowed for comments:
			$comment_restricted_status = $max_allowed_comment_status;
		}
		elseif( $comment_status_order < $item_restricted_status )
		{	// Restrict comment status to max allowed by parent item:
			$comment_restricted_status = $item_restricted_status;
		}
		else
		{	// Don't restrict because current comment status is allowed:
			$comment_restricted_status = $current_status;
		}

		return $comment_restricted_status;
	}


	/**
	 * Restrict Comment status by parent Item status AND its Collection access restriction AND by CURRENT USER write perm
	 *
	 * @param boolean TRUE to update status
	 */
	function restrict_status( $update_status = false )
	{
		global $current_User;

		// Store current status to display a warning:
		$current_status = $this->get( 'status' );

		$commented_Item = & $this->get_Item();

		if( $this->is_meta() )
		{	// Meta comment:
			if( ! is_logged_in() || ( $commented_Item && ! $current_User->check_perm( 'meta_comment', 'view', false, $commented_Item->get_blog_ID() ) ) )
			{	// Change meta comment status to 'protected' if user has no perm to view them:
				$comment_allowed_status = 'protected';
			}
			else
			{	// Do not restrict if meta comment and user has the proper permission:
				$comment_allowed_status = $current_status;
			}
		}
		else
		{	// Restrict status of normal comment to max allowed by parent item:
			$comment_allowed_status = $this->get_allowed_status();
			if( empty( $comment_allowed_status ) && $commented_Item && ( $item_Blog = & $commented_Item->get_Blog() ) )
			{	// If min allowed status is not found then use what default status is allowed:
				$comment_allowed_status = get_highest_publish_status( 'comment', $item_Blog->ID, false );
			}
		}

		if( $update_status )
		{	// Update status to new restricted value:
			$this->set( 'status', $comment_allowed_status );
		}
		else
		{	// Only change status to update it on the edit forms and Display a warning:
			$this->status = $comment_allowed_status;

			if( $current_status != $this->get( 'status' ) && ! $this->is_meta() )
			{	// If current comment status cannot be used because it is restricted by parent item:
				global $Messages;

				// Get max allowed for item collection:
				$comment_Item = & $this->get_Item();
				$item_Blog = & $comment_Item->get_Blog();
				$item_restricted_status = $item_Blog->get_allowed_item_status( $comment_Item->status );

				// Get all visibility status titles:
				$visibility_statuses = get_visibility_statuses();

				// Display a warning:
				$Messages->add( sprintf( T_('Since the parent post of this comment have its visibility set to "%s", the visibility of this comment will be restricted to "%s".'),
						$visibility_statuses[ $item_restricted_status ], $visibility_statuses[ $this->status ] ), 'warning' );
			}
		}
	}


	/**
	 * Check what were already notified on this item
	 *
	 * @param array|string Flags, possible values: 'moderators_notified', 'members_notified', 'community_notified'
	 */
	function check_notifications_flags( $flags )
	{
		if( ! is_array( $flags ) )
		{	// Convert string to array:
			$flags = array( $flags );
		}

		// TRUE if all requested flags are in current item notifications flags:
		return ( count( array_diff( $flags, $this->get( 'notif_flags' ) ) ) == 0 );
	}


	/**
	 * Check if this comment can be displayed for current user on front-office
	 *
	 * @param string|NULL Status | NULL to use current status of this comment
	 * @return boolean
	 */
	function can_be_displayed( $status = NULL )
	{
		if( empty( $this->ID ) )
		{	// Comment is not created yet, so it cannot be displayed:
			return false;
		}

		// Load Item of this comment to get a collection ID:
		$Item = & $this->get_Item();

		if( $status === NULL )
		{	// Use current status of this comment:
			$status = $this->get( 'status' );
		}

		return can_be_displayed_with_status( $status, 'comment', $Item->get_blog_ID(), $this->author_user_ID );
	}


	/**
	 * Get comment order numbers for current filtered list (global $CommentList)
	 *
	 * @return integer|NULL
	 */
	function get_inlist_order()
	{
		if( empty( $this->ID ) )
		{	// This comment must exist in DB
			return NULL;
		}

		global $CommentList;

		if( empty( $CommentList ) )
		{	// Comment list must be initialized globally
			return NULL;
		}

		if( ! isset( $CommentList->inlist_orders[ $this->ID ] ) )
		{	// Order number is not found in list for this comment:
			return NULL;
		}

		$inlist_order = intval( $CommentList->inlist_orders[ $this->ID ] );

		return $inlist_order < 0 ? 0 : $inlist_order;
	}
}

?>