<?php
/**
 * This file implements the Video Plug plugin for b2evolution
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package plugins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Replaces Video Plug markup in HTML (not XML).
 *
 * @todo dh> Hook into AdminBeforeItemEditUpdate and validate provided video IDs
 *
 * @package plugins
 */
class videoplug_plugin extends Plugin
{
	var $code = 'evo_videoplug';
	var $name = 'Video Plug';
	var $priority = 65;
	var $group = 'rendering';
	var $short_desc;
	var $long_desc;
	var $version = '7.0.2';
	var $number_of_installs = 1;


	/**
	 * Init
	 */
	function PluginInit( & $params )
	{
		$this->short_desc = T_('Video plug for a few popular video sites.');
		$this->long_desc = T_('This plugin allows to quickly embed (plug) a video from a video hosting site such as YouTube, Vimeo, DailyMotion and Facebook. Use it through the toolbar or directly by entering a shortcode like [video:youtube:123xyz] or [video:vimeo:123xyz] into your post, where 123xyz is the ID of the video.');
	}


	/**
	 * Define here default custom settings that are to be made available
	 *     in the backoffice for collections, private messages and newsletters.
	 *
	 * @param array Associative array of parameters.
	 * @return array See {@link Plugin::GetDefaultSettings()}.
	 */
	function get_custom_setting_definitions( & $params )
	{
		return array(
				'width' => array(
					'label' => T_('Video width (px or %)'),
					'note' => T_('100% width if left empty or 0'),
					'valid_pattern' => '/^(\d+(\.\d+)?%?)?$/',
					'defaultvalue' => '100%',
				),
				'height' => array(
					'label' => T_('Video height (px or %)'),
					'defaultvalue' => '',
					'allow_empty' => true,
					'valid_pattern' => '/^(\d+(\.\d+)?%?)?$/',
					'note' => T_('Leave empty for a 16/9 aspect ratio').' (16/9=56.25%)',
					'defaultvalue' => '56.25%',
				),
			);
	}


	/**
	 * Define here default collection/blog settings that are to be made available in the backoffice.
	 *
	 * @param array Associative array of parameters.
	 * @return array See {@link Plugin::GetDefaultSettings()}.
	 */
	function get_coll_setting_definitions( & $params )
	{
		return array_merge( parent::get_coll_setting_definitions( $params ),
			array(
				'replace_url_post' => array(
						'label' => T_('Replace full video URLs found in posts' ),
						'type' => 'checkbox',
						'defaultvalue' => 1,
					),
				'replace_url_comment' => array(
						'label' => T_('Replace full video URLs found in comments' ),
						'type' => 'checkbox',
						'defaultvalue' => 1,
					),
			)
		);
	}


	/**
	 * Perform rendering
	 *
	 * @todo add more video sites, anyone...
	 *
	 * @see Plugin::RenderItemAsHtml()
	 */
	function RenderItemAsHtml( & $params )
	{
		$content = & $params['data'];

		if( $setting_Blog = & $this->get_Blog_from_params( $params ) )
		{	// We are rendering Item, Comment or Widget now, Get the settings depending on Collection:
			$this->video_width = $this->get_coll_setting( 'width', $setting_Blog );
			$this->video_height = $this->get_coll_setting( 'height', $setting_Blog );
		}
		elseif( ! empty( $params['Message'] ) )
		{	// We are rendering Message now:
			$this->video_width = $this->get_msg_setting( 'width' );
			$this->video_height = $this->get_msg_setting( 'height' );
		}
		elseif( ! empty( $params['EmailCampaign'] ) )
		{	// We are rendering EmailCampaign now:
			$this->video_width = $this->get_email_setting( 'width' );
			$this->video_height = $this->get_email_setting( 'height' );
		}
		else
		{	// Unknown call, Don't render this case:
			return;
		}

		if( ! empty( $setting_Blog ) && (
		      ( $this->get_coll_setting( 'replace_url_comment', $setting_Blog ) && ! empty( $params['Comment'] ) && $params['Comment'] instanceof Comment ) ||
		      ( $this->get_coll_setting( 'replace_url_post', $setting_Blog ) && ! empty( $params['Item'] ) && $params['Item'] instanceof Item )
		  ) )
		{	// Render full video URLs in post or comment content:
			$content = replace_content_outcode( '#(<a[^>]+href=")?(https?://(.+\.)?(youtube.com|youtu.be|dailymotion.com|vimeo.com|facebook.com|wistia.com)([^"\s\n\r<]+))("[^>]*>(.+?)</a>)?#i',
				array( $this, 'parse_video_url_callback' ), $content, 'replace_content', 'preg_callback' );
		}

		// Move short tag outside of paragraph:
		$content = move_short_tags( $content, '/\[video:(youtube|dailymotion|vimeo|facebook|wistia):?[^\[\]]*\]/i' );

		// Replace video tags with html code:
		$content = replace_content_outcode( '#\[video:(youtube|dailymotion|vimeo|facebook|wistia|google|livevideo|ifilm):([^:\[\]\\\/]*|https?:\/\/.*\.facebook\.com\/[^:]*):?(\d+%?)?:?(\d+%?)?:?([^:\[\]\\\/]*)\]#',
			array( $this, 'parse_video_tag_callback' ), $content, 'replace_content', 'preg_callback' );

		return true;
	}


	/**
	 * Callback function to build HTML video code from video URL or link tag <a> with video URL
	 *
	 * @param array Matches:
	 *              0 - Full video URL or full link tag <a>
	 *              1 - Start part of <a> tag, or empty string when simple URL without <a> tag
	 *              2 - Full URL
	 *              3 - domain prefix like "www." or "www.subdomain." or empty
	 *              4 - Domain: youtube.com, youtu.be, dailymotion.com, vimeo.com, facebook.com, wistia.com
	 *              5 - Part of video URL after domain
	 *              6 - End part of <a> tag, or not defined when simple URL without <a> tag
	 *              7 - Text of <a> tag, or not defined when simple URL without <a> tag
	 * @return string HTML video code
	 */
	function parse_video_url_callback( $m )
	{
		if( isset( $m[7] ) && $m[7] != $m[2] )
		{	// Skip if link text is different than URL(e.g. <a href="https://youtu.be/abc123">Click to see Video!</a>):
			return $m[0];
		}

		// Try to exctract video code from URL depending on video server:
		switch( $m[4] )
		{
			case 'youtube.com':
			case 'youtu.be':
				if( preg_match( '#(^/|[\?&]v=)([^&=\?]+)(&|$)#', $m[5], $code ) )
				{
					$video_block = '<iframe id="ytplayer" type="text/html" src="//www.youtube.com/embed/'.$code[2].'" allowfullscreen="allowfullscreen" frameborder="0"></iframe>';
				}
				break;

			case 'dailymotion.com':
				if( preg_match( '#^/video/(.+)$#', $m[5], $code ) )
				{
					$video_block = '<iframe src="//www.dailymotion.com/embed/video/'.$code[1].'" frameborder="0" allowfullscreen></iframe>';
				}
				break;

			case 'vimeo.com':
				if( preg_match( '#^/(.+)$#', $m[5], $code ) )
				{
					$video_block = '<iframe src="//player.vimeo.com/video/'.$code[1].'" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>';
				}
				break;

			case 'facebook.com':
				if( preg_match( '#(/videos/|[\?&]v=)(\d+)(/|$)#', $m[5], $code ) )
				{
					$video_block = '<iframe src="https://www.facebook.com/plugins/video.php?href='.urlencode( $m[2] ).'" scrolling="no" frameborder="0" allowTransparency="true" allowFullScreen="true"></iframe>';
				}
				break;

			case 'wistia.com':
				if( preg_match( '#/medias/([a-z0-9]+)$#', $m[5], $code ) )
				{
					$video_block = '<script src="https://fast.wistia.com/embed/medias/'.$code[1].'.jsonp" async></script>'
						.'<script src="https://fast.wistia.com/assets/external/E-v1.js" async></script>'
						.'<div class="wistia_responsive_padding">'
							.'<div class="wistia_responsive_wrapper" style="height:100%;left:0;position:absolute;top:0;width:100%;">'
								.'<span class="wistia_embed wistia_async_'.$code[1].' popover=true popoverAnimateThumbnail=true videoFoam=true" style="display:inline-block;height:100%;position:relative;width:100%"> </span>'
							.'</div>'
						.'</div>';
				}
				break;
		}

		if( ! isset( $video_block ) )
		{	// No found correct video code in the URL:
			return $m[0];
		}

		$style = '';

		if( ! empty( $this->video_width ) )
		{	// Set width depending on what units are used:
			$style .= 'width:'.( strpos( $this->video_width, '%' ) === false ? $this->video_width.'px' : $this->video_width ).';';
		}

		if( ! empty( $this->video_height ) )
		{	// Set height depending on what units are used:
			$style .= 'padding-bottom:'.( strpos( $this->video_height, '%' ) === false ? '0;height:'.$this->video_height.'px' : $this->video_height );
		}

		return '<div class="videoblock"'.( $style == '' ? '' : ' style="'.$style.'"' ).'>'.$video_block.'</div>';
	}


	/**
	 * Callback function to build HTML video code from video tag
	 *
	 * @param array Matches:
	 *              0 - Full video tag
	 *              1 - Video type: youtube, dailymotion, vimeo, facebook, wistia, google, livevideo, ifilm
	 *              2 - Video code/key
	 *              3 - Width
	 *              4 - Height
	 *              5 - Extra params
	 * @return string HTML video code
	 */
	function parse_video_tag_callback( $m )
	{
		switch( $m[1] )
		{
			case 'youtube':
				$video_block = '<iframe id="ytplayer" type="text/html" src="//www.youtube.com/embed/'.$m[2].( ! empty( $m[5] )? '?'.$m[5] : '' ).'" allowfullscreen="allowfullscreen" frameborder="0"></iframe>';
				break;

			case 'dailymotion':
				$video_block = '<iframe src="//www.dailymotion.com/embed/video/'.$m[2].'" frameborder="0" allowfullscreen></iframe>';
				break;

			case 'vimeo':
				$video_block = '<iframe src="//player.vimeo.com/video/'.$m[2].'" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>';
				break;

			case 'facebook':
				$video_block = '<iframe src="https://www.facebook.com/plugins/video.php?href='.urlencode( $m[2] ).'" scrolling="no" frameborder="0" allowTransparency="true" allowFullScreen="true"></iframe>';
				break;

			case 'wistia':
				$video_block = '<script src="https://fast.wistia.com/embed/medias/'.$m[2].'.jsonp" async></script>'
					.'<script src="https://fast.wistia.com/assets/external/E-v1.js" async></script>'
					.'<div class="wistia_responsive_padding">'
						.'<div class="wistia_responsive_wrapper" style="height:100%;left:0;position:absolute;top:0;width:100%;">'
							.'<span class="wistia_embed wistia_async_'.$m[2].' popover=true popoverAnimateThumbnail=true videoFoam=true" style="display:inline-block;height:100%;position:relative;width:100%"> </span>'
						.'</div>'
					.'</div>';
				break;

			default: // google, livevideo, ifilm:
				// Unavailable services. Keep them for backwards compatibility:
				$video_block = 'The '.$m[1].' video service is not available anymore.';
				break;
		}

		$style = '';

		// Get width from video tag or from settings:
		$width = empty( $m[3] ) ? $this->video_width : $m[3];
		if( ! empty( $width ) )
		{	// Set width depending on what units are used:
			$style .= 'width:'.( strpos( $width, '%' ) === false ? $width.'px' : $width ).';';
		}

		// Get height from video tag or from settings:
		$height = empty( $m[4] ) ? $this->video_height : $m[4];
		if( ! empty( $height ) )
		{	// Set height depending on what units are used:
			$style .= 'padding-bottom:'.( strpos( $height, '%' ) === false ? '0;height:'.$height.'px' : $height );
		}

		return '<div class="videoblock"'.( $style == '' ? '' : ' style="'.$style.'"' ).'>'.$video_block.'</div>';
	}


	/**
	 * Perform rendering for XML feeds
	 *
	 * @see Plugin::RenderItemAsXml()
	 */
	function RenderItemAsXml( & $params )
	{
		return $this->RenderItemAsHtml( $params );
	}


	/**
	 * Event handler: Called when displaying editor toolbars on post/item form.
	 *
	 * This is for post/item edit forms only. Comments, PMs and emails use different events.
	 *
	 * @param array Associative array of parameters
	 * @return boolean did we display a toolbar?
	 */
	function AdminDisplayToolbar( & $params )
	{
		if( !empty( $params['Item'] ) )
		{	// Item is set, get Blog from post:
			$edited_Item = & $params['Item'];
			$Collection = $Blog = & $edited_Item->get_Blog();
		}

		if( empty( $Blog ) )
		{	// Item is not set, try global Blog:
			global $Collection, $Blog;
			if( empty( $Blog ) )
			{	// We can't get a Blog, this way "apply_rendering" plugin collection setting is not available:
				return false;
			}
		}

		$apply_rendering = $this->get_coll_setting( 'coll_apply_rendering', $Blog );
		if( empty( $apply_rendering ) || $apply_rendering == 'never' )
		{	// Plugin is not enabled for current case, so don't display a toolbar:
			return false;
		}

		return $this->DisplayCodeToolbar( $params );
	}


	/**
	 * Event handler: Called when displaying editor toolbars for message.
	 *
	 * @param array Associative array of parameters
	 * @return boolean did we display a toolbar?
	 */
	function DisplayMessageToolbar( & $params )
	{
		$apply_rendering = $this->get_msg_setting( 'msg_apply_rendering' );
		if( ! empty( $apply_rendering ) && $apply_rendering != 'never' )
		{
			return $this->DisplayCodeToolbar( $params );
		}

		return false;
	}


	/**
	 * Event handler: Called when displaying editor toolbars for email.
	 *
	 * @param array Associative array of parameters
	 * @return boolean did we display a toolbar?
	 */
	function DisplayEmailToolbar( & $params )
	{
		$apply_rendering = $this->get_email_setting( 'email_apply_rendering' );
		if( ! empty( $apply_rendering ) && $apply_rendering != 'never' )
		{
			return $this->DisplayCodeToolbar( $params );
		}

		return false;
	}


	/**
	 * Event handler: Called when displaying editor toolbars on comment form.
	 *
	 * @param array Associative array of parameters
	 * @return boolean did we display a toolbar?
	 */
	function DisplayCommentToolbar( & $params )
	{
		$Comment = & $params['Comment'];
		if( $Comment )
		{	// Get a post of the comment:
			if( $comment_Item = & $Comment->get_Item() )
			{
				$Collection = $Blog = & $comment_Item->get_Blog();
			}
		}

		if( empty( $Blog ) )
		{	// Item is not set, try global Blog
			global $Collection, $Blog;
			if( empty( $Blog ) )
			{	// We can't get a Blog, this way "apply_rendering" plugin collection setting is not available
				return false;
			}
		}

		$apply_rendering = $this->get_coll_setting( 'coll_apply_comment_rendering', $Blog );
		if( empty( $apply_rendering ) || $apply_rendering == 'never' )
		{	// Plugin is not enabled for current case, so don't display a toolbar:
			return false;
		}

		return $this->DisplayCodeToolbar( $params );
	}


	/**
	 * Display a code toolbar
	 *
	 * @param array Params
	 * @return boolean did we display a toolbar?
	 */
	function DisplayCodeToolbar( $params = array() )
	{
		$params = array_merge( array(
				'js_prefix' => '', // Use different prefix if you use several toolbars on one page
			), $params );

		echo $this->get_template( 'toolbar_before', array( '$toolbar_class$' => $params['js_prefix'].$this->code.'_toolbar' ) );

		echo $this->get_template( 'toolbar_title_before' ).T_('Video').': '.$this->get_template( 'toolbar_title_after' );
		echo $this->get_template( 'toolbar_group_before' );
		echo '<input type="button" id="video_youtube" title="'.T_('Insert Youtube video').'" class="'.$this->get_template( 'toolbar_button_class' ).'" data-func="videotag|youtube|'.$params['js_prefix'].'" value="YouTube" />';
		echo '<input type="button" id="video_vimeo" title="'.T_('Insert vimeo video').'" class="'.$this->get_template( 'toolbar_button_class' ).'" data-func="videotag|vimeo|'.$params['js_prefix'].'" value="Vimeo" />';
		echo '<input type="button" id="video_dailymotion" title="'.T_('Insert DailyMotion video').'" class="'.$this->get_template( 'toolbar_button_class' ).'" data-func="videotag|dailymotion|'.$params['js_prefix'].'" value="DailyMotion" />';
		echo '<input type="button" id="video_facebook" title="'.T_('Insert Facebook video').'" class="'.$this->get_template( 'toolbar_button_class' ).'" data-func="videotag|facebook|'.$params['js_prefix'].'" value="Facebook" />';
		echo '<input type="button" id="video_wistia" title="'.T_('Insert Wistia video').'" class="'.$this->get_template( 'toolbar_button_class' ).'" data-func="videotag|wistia|'.$params['js_prefix'].'" value="Wistia" />';
		echo $this->get_template( 'toolbar_group_after' );

		echo $this->get_template( 'toolbar_after' );

		// Load js to work with textarea
		require_js( 'functions.js', 'blog', true, true );

		?><script>
			//<![CDATA[
			function videotag( tag, prefix )
			{
				while( 1 )
				{
					var p = '<?php echo TS_('Copy/paste the URL or the ID of your video from %s:') ?>';
					var video_ID = prompt( p.replace( /%s/, tag ), '' );
					if( ! video_ID )
					{
						return;
					}

					// Validate Video ID or URL:
					var regexp_ID = false;
					var regexp_URL = false;
					switch( tag )
					{
						case 'youtube':
							// Allow HD video code with ?hd=1 at the end
							regexp_ID = /^[a-z0-9_-]+$/i;
							regexp_URL = /^(.+youtube(?:-nocookie)?\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|\S*?[?&]v=)|.+youtu\.be\/)([a-z0-9_-]{11})((?:\?|&).*)?/i;
							break;

						case 'dailymotion':
							regexp_ID = /^[a-z0-9]+$/i;
							regexp_URL = /^(.+\/video\/)?([a-z0-9]+)(_[a-z0-9_-]+)?(\?.+)?$/i;
							break;

						case 'vimeo':
							regexp_ID = /^\d+$/;
							regexp_URL = /^(.+\/)?(\d+)(\?.+)?$/;
							break;

						case 'facebook':
							regexp_ID = /^https:\/\/.+\.facebook\.com\/.+/i;
							regexp_URL = /^((https:\/\/.+\.facebook\.com\/.+))$/i;
							break;

						case 'wistia':
							regexp_ID = /^[a-z0-9]+$/i;
							regexp_URL = /^(.+\/medias\/)?([a-z0-9]+)$/i;
							break;

						default:
							// Don't allow unknown video:
							break;
					}

					if( regexp_ID && regexp_URL )
					{	// Check the entered data by regexp:
						if( video_ID.match( regexp_ID ) )
						{	// Valid video ID
							break;
						}
						else if( video_ID.match( /^https?:\/\// ) )
						{	// If this is URL, Check to correct format:
							if( video_ID.match( regexp_URL ) )
							{	// Valid video URL
								// Extract ID from URL:
								if( tag == 'youtube' )
								{
									var params = video_ID.replace( regexp_URL, '$3' ).trim();
									params = params.replace( /^[&\?]/, '' );
									params = params.replace( /&$/, '' );
									params = params.replace( /rel=\d+&?/, '' ); // remove rel=
									video_ID = video_ID.replace( regexp_URL, '$2' );
									if( params.length )
									{
										video_ID = video_ID + ':::' + params;
									}
								}
								else
								{
									video_ID = video_ID.replace( regexp_URL, '$2' );
								}
								break;
							}
							else
							{	// Display error when URL doesn't match:
								alert( '<?php echo TS_('The URL you provided could not be recognized.'); ?>' );
								continue;
							}
						}
					}

					// Display error of wrong entered data:
					alert( '<?php echo TS_('The URL or video ID is invalid.'); ?>' );
				}

				tag = '[video:'+tag+':'+video_ID+']';

				textarea_wrap_selection( window[ ( prefix ? prefix : '' ) + 'b2evoCanvas' ], tag, '', 1 );
			}
			//]]>
		</script><?php

		return true;
	}
}

?>