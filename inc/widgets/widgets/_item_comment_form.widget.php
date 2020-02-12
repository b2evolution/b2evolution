<?php
/**
 * This file implements the item_comment_form Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2013 by Francois Planque - {@link http://fplanque.com/}
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
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author erhsatingin: Erwin Rommel Satingin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'widgets/model/_widget.class.php', 'ComponentWidget' );

/**
 * ComponentWidget Class
 *
 * A ComponentWidget is a displayable entity that can be placed into a Container on a web page.
 *
 * @package evocore
 */
class item_comment_form_Widget extends ComponentWidget
{
	var $icon = 'file-text';

	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'item_comment_form' );
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'item-comment-form-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Comment Form');
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 */
	function get_short_desc()
	{
		return format_to_output( T_('Item Comment Form') );
	}


	/**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Display item comment form.');
	}


	/**
	 * Get definitions for editable params
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_param_definitions( $params )
	{
		$r = array_merge( array(
				'title' => array(
					'label' => T_( 'Title' ),
					'size' => 40,
					'note' => T_( 'This is the title to display' ),
					'defaultvalue' => '',
				)
			), parent::get_param_definitions( $params ) );

		if( isset( $r['allow_blockcache'] ) )
		{	// Disable "allow blockcache" because item content may includes other items by inline tags like [inline:item-slug]:
			$r['allow_blockcache']['defaultvalue'] = false;
			$r['allow_blockcache']['disabled'] = 'disabled';
			$r['allow_blockcache']['note'] = T_('This widget cannot be cached in the block cache.');
		}

		return $r;
	}


	/**
	 * Prepare display params
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function init_display( $params )
	{
		global $preview;

		parent::init_display( $params );

		if( $preview )
		{	// Disable block caching for this widget when item is previewed currently:
			$this->disp_params['allow_blockcache'] = 0;
		}
	}


	/**
	 * Display the widget!
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function display( $params )
	{
		global $Item;
		global $cookie_name, $cookie_email, $cookie_url, $disp;
		global $comment_cookies, $comment_allow_msgform, $comment_anon_notify;
		global $Session, $Plugins;
		global $Collection, $Blog, $dummy_fields;

		if( empty( $Item ) )
		{	// Don't display this widget when no Item object:
			$this->display_error_message( 'Widget "'.$this->get_name().'" is hidden because there is no Item.' );
			return false;
		}

		// Default renderers:
		$comment_renderers = array( 'default' );

		$this->init_display( $params );

		$params = array_merge( array(
				'widget_item_comment_form_params' => array(),
			), $params );

		$widget_params = array_merge( array(
				'disp_comment_form'    => true,
				'form_title_start'     => '<div class="panel '.( $Session->get( 'core.preview_Comment'.( isset( $params['comment_type'] ) && $params['comment_type'] == 'meta' ? 'meta' : '' ) ) ? 'panel-danger' : 'panel-default' ).'">'
																		.'<div class="panel-heading"><h4 class="panel-title">',
				'form_title_end'       => '</h4></div><div class="panel-body">',
				'form_title_text'      => T_('Leave a comment'),
				'form_comment_text'    => T_('Comment text'),
				'form_submit_text'     => T_('Send comment'),
				'form_params'          => array( // Use to change structure of form, i.e. fieldstart, fieldend and etc.
					'comments_disabled_before' => '<p class="alert alert-warning">',
					'comments_disabled_after' => '</p>',
					),
				'policy_text'          => '',
				'author_link_text'     => 'auto',
				'textarea_lines'       => 10,
				'default_text'         => '',
				'preview_block_start'  => '',
				'preview_start'        => '<article class="evo_comment evo_comment__preview panel panel-warning" id="comment_preview">',
				'comment_template'     => '_item_comment.inc.php',	// The template used for displaying individual comments (including preview)
				'preview_end'          => '</article>',
				'preview_block_end'    => '',
				'before_comment_error' => '<p><em>',
				'comment_closed_text'  => '#',
				'after_comment_error'  => '</em></p>',
				'before_comment_form'  => '',
				'after_comment_form'   => '</div></div>',
				'form_comment_redirect_to' => $Item->get_feedback_url( $disp == 'feedback-popup', '&' ),
				'comment_image_size'       => 'fit-1280x720',
				'comment_attach_info'      => get_icon( 'help', 'imgtag', array(
						'data-toggle'    => 'tooltip',
						'data-placement' => 'bottom',
						'data-html'      => 'true',
						'title'          => htmlspecialchars( get_upload_restriction( array(
								'block_after'     => '',
								'block_separator' => '<br /><br />' ) ) )
					) ),
				'comment_type'         => 'comment',
				'comment_title_before'  => '<div class="panel-heading"><h4 class="evo_comment_title panel-title">',
				'comment_title_after'   => '</h4></div><div class="panel-body">',
				'comment_rating_before' => '<div class="evo_comment_rating">',
				'comment_rating_after'  => '</div>',
				'comment_text_before'   => '<div class="evo_comment_text">',
				'comment_text_after'    => '</div>',
				'comment_info_before'   => '<footer class="evo_comment_footer clear text-muted"><small>',
				'comment_info_after'    => '</small></footer></div>',
			), $params['widget_item_comment_form_params'] );

		$email_is_detected = false; // Used when comment contains an email strings

		echo $this->disp_params['block_start'];
		$this->disp_title();
		echo $this->disp_params['block_body_start'];

		// ---------------------- POST CONTENT INCLUDED HERE ----------------------
		$section_title = $widget_params['form_title_start'].$widget_params['form_title_text'].$widget_params['form_title_end'];
		if( $widget_params['disp_comment_form'] && ( $widget_params['comment_type'] == 'meta' && $Item->can_meta_comment() ||
				$Item->can_comment( $widget_params['before_comment_error'], $widget_params['after_comment_error'], '#', $widget_params['comment_closed_text'], $section_title, $widget_params ) ) )
		{ // We want to display the comments form and the item can be commented on:

			echo '<a name="'.( $widget_params['comment_type'] == 'meta' ? 'meta-comment-form' : 'comment-form' ).'"></a>';

			echo $widget_params['before_comment_form'];

			// INIT/PREVIEW:
			if( $Comment = get_comment_from_session( 'preview', $widget_params['comment_type'] ) )
			{	// We have a comment to preview
				if( $Comment->item_ID == $Item->ID )
				{ // display PREVIEW:

					// We do not want the current rendered page to be cached!!
					if( !empty( $PageCache ) )
					{
						$PageCache->abort_collect();
					}

					if( $Comment->email_is_detected )
					{	// We set it to define a some styles below
						$email_is_detected = true;
					}

					if( empty( $Comment->in_reply_to_cmt_ID ) )
					{ // Display the comment preview here only if this comment is not a reply, otherwise it was already displayed
						// ------------------ PREVIEW COMMENT INCLUDED HERE ------------------
						skin_include( $widget_params['comment_template'], array(
								'Comment'               => & $Comment,
								'comment_block_start'   => $Comment->email_is_detected ? '' : $widget_params['preview_block_start'],
								'comment_start'         => $Comment->email_is_detected ? $widget_params['comment_error_start'] : $widget_params['preview_start'],
								'comment_end'           => $Comment->email_is_detected ? $widget_params['comment_error_end'] : $widget_params['preview_end'],
								'comment_block_end'     => $Comment->email_is_detected ? '' : $widget_params['preview_block_end'],
								'comment_title_before'  => $widget_params['comment_title_before'],
								'comment_title_after'   => $widget_params['comment_title_after'],
								'comment_rating_before' => $widget_params['comment_rating_before'],
								'comment_rating_after'  => $widget_params['comment_rating_after'],
								'comment_text_before'   => $widget_params['comment_text_before'],
								'comment_text_after'    => $widget_params['comment_text_after'],
								'comment_info_before'   => $widget_params['comment_info_before'],
								'comment_info_after'    => $widget_params['comment_info_after'],
								'author_link_text'      => $widget_params['author_link_text'],
								'image_size'            => $widget_params['comment_image_size'],
							) );
						// Note: You can customize the default item comment by copying the generic
						// /skins/_item_comment.inc.php file into the current skin folder.
						// ---------------------- END OF PREVIEW COMMENT ---------------------
					}

					// Form fields:
					$comment_content = $Comment->original_content;
					// comment_attachments contains all file IDs that have been attached
					$comment_attachments = $Comment->preview_attachments;
					// checked_attachments contains all attachment file IDs which checkbox was checked in
					$checked_attachments = $Comment->checked_attachments;
					// for visitors:
					$comment_author = $Comment->author;
					$comment_author_email = $Comment->author_email;
					$comment_author_url = $Comment->author_url;
					$comment_allow_msgform = $Comment->allow_msgform;
					$comment_anon_notify = $Comment->anon_notify;
					$comment_user_notify = isset( $Comment->user_notify ) ? $Comment->user_notify : NULL;
					// Get what renderer checkboxes were selected on form:
					$comment_renderers = explode( '.', $Comment->get( 'renderers' ) );

					// Display error messages again after preview of comment
					global $Messages;
					$Messages->display();
				}
			}
			else
			{ // New comment:
				if( ( $Comment = get_comment_from_session( 'unsaved', $widget_params['comment_type'] ) ) == NULL )
				{ // there is no saved Comment in Session
					$Comment = new Comment();
					$Comment->set( 'type', $widget_params['comment_type'] );
					$Comment->set( 'item_ID', $Item->ID );
					if( ( !empty( $PageCache ) ) && ( $PageCache->is_collecting ) )
					{	// This page is going into the cache, we don't want personal data cached!!!
						// fp> These fields should be filled out locally with Javascript tapping directly into the cookies. Anyone JS savvy enough to do that?
						$comment_author = '';
						$comment_author_email = '';
						$comment_author_url = '';
					}
					else
					{ // Get params from $_COOKIE
						$comment_author = param_cookie( $cookie_name, 'string', '' );
						$comment_author_email = utf8_strtolower( param_cookie( $cookie_email, 'string', '' ) );
						$comment_author_url = param_cookie( $cookie_url, 'string', '' );
					}
					if( empty($comment_author_url) )
					{	// Even if we have a blank cookie, let's reset this to remind the bozos what it's for
						$comment_author_url = 'http://';
					}

					$comment_content =  $widget_params['default_text'];
				}
				else
				{ // set saved Comment attributes from Session
					$comment_content = $Comment->content;
					$comment_author = $Comment->author;
					$comment_author_email = $Comment->author_email;
					$comment_author_url = $Comment->author_url;
					$comment_allow_msgform = $Comment->allow_msgform;
					$comment_anon_notify = $Comment->anon_notify;
					$comment_user_notify = isset( $Comment->user_notify ) ? $Comment->user_notify : NULL;
					// comment_attachments contains all file IDs that have been attached
					$comment_attachments = $Comment->preview_attachments;
					// checked_attachments contains all attachment file IDs which checkbox was checked in
					$checked_attachments = $Comment->checked_attachments;
				}

				$quoted_comment_ID = param( 'quote_comment', 'integer', 0 );
				$quoted_post_ID = param( 'quote_post', 'integer', 0 );
				if( $quoted_comment_ID || $quoted_post_ID )
				{ // Quote for comment/post
					$comment_content = param( $dummy_fields[ 'content' ], 'html' );
					if( ! empty( $quoted_comment_ID ) &&
							( $CommentCache = & get_CommentCache() ) &&
							( $quoted_Comment = & $CommentCache->get_by_ID( $quoted_comment_ID, false ) ) &&
							$widget_params['comment_type'] == $quoted_Comment->get( 'type' ) )
					{	// Allow comment quoting only for the same comment type form:
						$quoted_Item = $quoted_Comment->get_Item();
						if( $quoted_User = $quoted_Comment->get_author_User() )
						{ // User is registered
							$quoted_login = $quoted_User->login;
						}
						else
						{ // Anonymous user
							$quoted_login = $quoted_Comment->get_author_name();
						}
						$quoted_content = $quoted_Comment->get( 'content' );
						$quoted_ID = 'c'.$quoted_Comment->ID;
					}
					elseif( ! empty( $quoted_post_ID ) && $widget_params['comment_type'] != 'meta' )
					{	// Allow item quoting only for normal(not meta) comment type form:
						$ItemCache = & get_ItemCache();
						$quoted_Item = & $ItemCache->get_by_ID( $quoted_post_ID, false );
						$quoted_login = $quoted_Item->get_creator_login();
						$quoted_content = $quoted_Item->get( 'content' );
						$quoted_ID = 'p'.$quoted_Item->ID;
					}

					if( !empty( $quoted_Item ) )
					{	// Format content for editing, if we were not already in editing...
						$comment_title = '';
						$comment_content .= '[quote=@'.$quoted_login.'#'.$quoted_ID.']'.strip_tags($quoted_content).'[/quote]';

						$Plugins_admin = & get_Plugins_admin();
						$quoted_Item->load_Blog();
						$plugins_params = array( 'object_type' => 'Comment', 'object_Blog' => & $quoted_Item->Blog );
						$Plugins_admin->unfilter_contents( $comment_title /* by ref */, $comment_content /* by ref */, $quoted_Item->get_renderers_validated(), $plugins_params );
					}
				}
			}

			if( ( !empty( $PageCache ) ) && ( $PageCache->is_collecting ) )
			{	// This page is going into the cache, we don't want personal data cached!!!
				// fp> These fields should be filled out locally with Javascript tapping directly into the cookies. Anyone JS savvy enough to do that?
			}
			else
			{
				// Get values that may have been passed through after a preview
				param( 'comment_cookies', 'integer', NULL );
				param( 'comment_allow_msgform', 'integer', NULL ); // checkbox

				if( is_null($comment_cookies) )
				{ // "Remember me" checked, if remembered before:
					$comment_cookies = isset($_COOKIE[$cookie_name]) || isset($_COOKIE[$cookie_email]) || isset($_COOKIE[$cookie_url]);
				}
			}

			echo $widget_params['form_title_start'];
			echo $widget_params['form_title_text'];
			echo $widget_params['form_title_end'];

			if( $widget_params['comment_type'] != 'meta' )
			{	// Display a message before comment form:
				$Item->display_comment_form_msg();
			}

		/*
			echo '<script type="text/javascript">
		/* <![CDATA[ *
		function validateCommentForm(form)
		{
			if( form.'.$dummy_fields['content'].'.value.replace(/^\s+|\s+$/g,"").length == 0 )
			{
				alert("'.TS_('Please do not send empty comments.').'");
				return false;
			}
		}
		/* ]]> *
		</script>';*/
			$Form = new Form( get_htsrv_url().'comment_post.php', 'evo_comment_form_id_'.$Item->ID, 'post', NULL, 'multipart/form-data' );

			$Form->switch_template_parts( $widget_params['form_params'] );

			$Form->begin_form( 'evo_form evo_form__comment', '', array( 'target' => '_self'/*, 'onsubmit' => 'return validateCommentForm(this);'*/ ) );

			// TODO: dh> a plugin hook would be useful here to add something to the top of the Form.
			//           Actually, the best would be, if the $Form object could be changed by a plugin
			//           before display!

			$Form->add_crumb( 'comment' );
			$Form->hidden( 'comment_type', $widget_params['comment_type'] );
			$Form->hidden( 'comment_item_ID', $Item->ID );

			$comment_reply_ID = param( 'reply_ID', 'integer', 0 );
			$comment_type = param( 'comment_type', 'string', 'comment' );
			if( ! empty( $comment_reply_ID ) && $comment_type == $widget_params['comment_type'] )
			{
				$Form->hidden( 'reply_ID', $comment_reply_ID );

				// Link to scroll back up to replying comment
				echo '<a href="'.url_add_param( $Item->get_permanent_url(), 'reply_ID='.$comment_reply_ID.'&amp;redir=no' ).'#c'.$comment_reply_ID.'" class="comment_reply_current" rel="'.$comment_reply_ID.'">'.T_('You are currently replying to a specific comment').'</a>';
			}
			$Form->hidden( 'redirect_to',
					// Make sure we get back to the right page (on the right domain)
					// fp> TODO: check if we can use the permalink instead but we must check that application wide,
					// that is to say: check with the comments in a pop-up etc...
					// url_rel_to_same_host(regenerate_url( '', '', $Blog->get('blogurl'), '&' ), get_htsrv_url())
					// fp> what we need is a regenerate_url that will work in permalinks
					// fp> below is a simpler approach:
					$widget_params['form_comment_redirect_to']
				);

			if( ! is_logged_in( false ) )
			{	// User is not logged in or not activated:
				if( is_logged_in() && empty( $comment_author ) && empty( $comment_author_email ) )
				{
					$comment_author = $current_User->login;
					$comment_author_email = $current_User->email;
				}
				// Note: we use funky field names to defeat the most basic guestbook spam bots
				$Form->text( $dummy_fields[ 'name' ], $comment_author, 40, T_('Name'), '<br />'.sprintf( T_('<a %s>Click here to log in</a> if you already have an account on this site.'), 'href="'.get_login_url( 'comment form', $Item->get_permanent_url() ).'" style="font-weight:bold"' ), 100, 'evo_comment_field' );

				$Form->email_input( $dummy_fields[ 'email' ], $comment_author_email, 40, T_('Email'), array(
					'bottom_note' => T_('Your email address will <strong>not</strong> be revealed on this site.'),
					'maxlength'   => 255,
					'class'       => 'evo_comment_field' ) );

				$Item->load_Blog();
				if( $Item->Blog->get_setting( 'allow_anon_url' ) )
				{
					$Form->text( $dummy_fields[ 'url' ], $comment_author_url, 40, T_('Website'), '<br />'.T_('Your URL will be displayed.'), 255, 'evo_comment_field' );
				}
			}

			if( ! $Comment->is_meta() && $Item->can_rate() )
			{ // Comment rating:
				ob_start();
				$Comment->rating_input( array( 'item_ID' => $Item->ID ) );
				$comment_rating = ob_get_clean();
				$Form->info_field( T_('Your vote'), $comment_rating );
			}

			if( !empty($widget_params['policy_text']) )
			{	// We have a policy text to display
				$Form->info_field( '', $widget_params['policy_text'] );
			}

			// Workflow properties:
			if( $Comment->is_meta() )
			{	// Display workflow properties if current user can edit at least one workflow property:
				skin_include( '_item_comment_workflow.inc.php', array_merge( $params, array(
					'Form'    => & $Form,
					'Comment' => & $Comment,
				) ) );
			}

			// Set prefix for js code in plugins:
			$plugin_js_prefix = ( $widget_params['comment_type'] == 'meta' ? 'meta_' : '' );

			// Display plugin captcha for comment form before textarea:
			$Plugins->display_captcha( array(
					'Form'          => & $Form,
					'form_type'     => 'comment',
					'form_position' => 'before_textarea',
				) );

			ob_start();
			echo '<div class="comment_toolbars">';
			// CALL PLUGINS NOW:
			$Plugins->trigger_event( 'DisplayCommentToolbar', array(
					'Comment'     => & $Comment,
					'Item'        => & $Item,
					'js_prefix'   => $plugin_js_prefix,
				) );
			echo '</div>';
			$comment_toolbar = ob_get_clean();

			// Message field:
			$content_id = $dummy_fields['content'].'_'.$widget_params['comment_type'];
			$form_inputstart = $Form->inputstart;
			$Form->inputstart .= $comment_toolbar;
			$note = '';
			// $note = T_('Allowed XHTML tags').': '.htmlspecialchars(str_replace( '><',', ', $comment_allowed_tags));
			$Form->textarea_input( $dummy_fields['content'], $comment_content, $widget_params['textarea_lines'], $widget_params['form_comment_text'], array(
					'note'  => $note,
					'cols'  => 38,
					'class' => 'autocomplete_usernames link_attachment_dropzone',
					'id'    => $content_id,
					'maxlength' => $Blog->get_setting( 'comment_maxlen' ),
				) );
			$Form->inputstart = $form_inputstart;

			// Set canvas object for plugins:
			echo '<script type="text/javascript">var '.$plugin_js_prefix.'b2evoCanvas = document.getElementById( "'.$content_id.'" );</script>';

			// CALL PLUGINS NOW:
			ob_start();
			$Plugins->trigger_event( 'AdminDisplayEditorButton', array(
				'target_type'   => 'Comment',
				'target_object' => $Comment,
				'content_id'    => $content_id,
				'edit_layout'   => 'inskin',
			) );
			$quick_setting_switch = ob_get_flush();

			// Additional options:
			$comment_options = array();
			if( ! is_logged_in( false ) )
			{	// For anonymous or not activated user:
				// TODO: If we got info from cookies, Add a link called "Forget me now!" (without posting a comment).
				$comment_options[] = array( 'comment_cookies', 1, T_('Remember me'), $comment_cookies, false, '('.T_('Set cookies so I don\'t need to fill out my details next time').')' );
				// TODO: If we have an email in a cookie, Add links called "Add a contact icon to all my previous comments" and "Remove contact icon from all my previous comments".
				$comment_options[] = array( 'comment_allow_msgform', 1, T_('Allow message form'), $comment_allow_msgform, false, '('.T_('Allow users to contact me through a message form -- Your email will <strong>not</strong> be revealed!').')', ( $email_is_detected ? 'comment_recommended_option' : '' ) );
				if( $Blog->get_setting( 'allow_anon_subscriptions' ) )
				{	// If item anonymous subscriptions are allowed for current collection:
					$comment_options[] = array( 'comment_anon_notify', 1, T_('Notify me of replies'), isset( $comment_anon_notify ) ? $comment_anon_notify : $Blog->get_setting( 'default_anon_comment_notify' ) );
				}
			}
			elseif( $widget_params['comment_type'] != 'meta' && $Blog->get_setting( 'allow_item_subscriptions' ) )
			{	// For registered user and normal(not meta) comment and if item subscriptions are allowed for current collection:
				$comment_options[] = array( 'comment_user_notify', 1, T_('Notify me of replies'), ( isset( $comment_user_notify ) ? $comment_user_notify : 1 ) );
			}
			if( count( $comment_options ) > 0 )
			{	// Display additional options:
				$Form->checklist( $comment_options, 'comment_options', T_('Options') );
			}

			// Display renderers
			$comment_renderer_checkboxes = $Plugins->get_renderer_checkboxes( $comment_renderers, array(
					'Blog'         => & $Blog,
					'setting_name' => 'coll_apply_comment_rendering',
					'js_prefix'    => $plugin_js_prefix,
				) );
			if( !empty( $comment_renderer_checkboxes ) )
			{
				$Form->info( T_('Text Renderers'), $comment_renderer_checkboxes );
			}

			// Attach files:
			if( !empty( $comment_attachments ) )
			{	// display already attached files checkboxes
				$FileCache = & get_FileCache();
				$attachments = explode( ',', $comment_attachments );
				$final_attachments = explode( ',', $checked_attachments );
				// create attachments checklist
				$list_options = array();
				foreach( $attachments as $attachment_ID )
				{
					$attachment_File = $FileCache->get_by_ID( $attachment_ID, false );
					if( $attachment_File )
					{
						// checkbox should be checked only if the corresponding file id is in the final attachments array
						$checked = in_array( $attachment_ID, $final_attachments );
						$list_options[] = array( 'preview_attachment'.$attachment_ID, 1, '', $checked, false, $attachment_File->get( 'name' ) );
					}
				}
				if( !empty( $list_options ) )
				{	// display list
					$Form->checklist( $list_options, 'comment_attachments', T_( 'Attached files' ) );
				}
				// memorize all attachments ids
				$Form->hidden( 'preview_attachments', $comment_attachments );
			}
			if( $Item->can_attach() )
			{	// Display attach file input field when JavaScript is disabled:
				echo '<noscript>';
				$Form->input_field( array( 'label' => T_('Attach files'), 'note' => $widget_params['comment_attach_info'], 'name' => 'uploadfile[]', 'type' => 'file' ) );
				echo '<p>'.T_('Please enable JavaScript to use file uploader.').'</p>';
				echo '</noscript>';
			}
			// Display attachments fieldset:
			$Form->attachments_fieldset( $Comment, false, $Comment->is_meta() ? 'meta_' : '' );

			if( ! $Comment->is_meta() )
			{	// Display workflow properties for normal comment form here:
				skin_include( '_item_comment_workflow.inc.php', array_merge( $params, array(
						'Form'    => & $Form,
						'Comment' => & $Comment,
					) ) );
			}

			if( $Item->can_edit_workflow() )
			{	// Prepend info for the form submit button title to inform user about additional action when workflow properties are on the form:
				$widget_params['form_submit_text'] = T_('Update Status').' / '.$widget_params['form_submit_text'];
			}

			$Plugins->trigger_event( 'DisplayCommentFormFieldset', array( 'Form' => & $Form, 'Item' => & $Item ) );

			// Display plugin captcha for comment form before submit button:
			$Plugins->display_captcha( array(
					'Form'          => & $Form,
					'form_type'     => 'comment',
					'form_position' => 'before_submit_button',
				) );

			$Form->begin_fieldset();
				echo $Form->buttonsstart;

				$preview_text = ( $Item->can_attach() ) ? T_('Preview/Add file') : T_('Preview');
				$Form->button_input( array( 'name' => 'submit_comment_post_'.$Item->ID.'[preview]', 'class' => 'preview btn-info', 'value' => $preview_text, 'tabindex' => 9 ) );
				$Form->button_input( array( 'name' => 'submit_comment_post_'.$Item->ID.'[save]', 'class' => 'submit SaveButton', 'value' => $widget_params['form_submit_text'], 'tabindex' => 10 ) );

				if( $Item->can_attach() )
				{	// Don't display "/Add file" on the preview button if JS is enabled:
					echo '<script type="text/javascript">jQuery( "input[type=submit].preview.btn-info" ).val( "'.TS_('Preview').'" )</script>';
				}

				$Plugins->trigger_event( 'DisplayCommentFormButton', array( 'Form' => & $Form, 'Item' => & $Item ) );

				echo $Form->buttonsend;
			$Form->end_fieldset();
			?>
			<script type="text/javascript">
			jQuery( document ).ready( function() {
				// Align TinyMCE toggle buttons:
				jQuery( '.evo_tinymce_toggle_buttons' ).addClass( 'col-sm-offset-3' );
			} );
			</script>
			<div class="clear"></div>

			<?php
			$Form->end_form();

			echo $widget_params['after_comment_form'];

			echo_comment_reply_js( $Item );
		}
		// -------------------------- END OF POST CONTENT -------------------------

		echo $this->disp_params['block_body_end'];
		echo $this->disp_params['block_end'];

		return true;
	}
}

?>