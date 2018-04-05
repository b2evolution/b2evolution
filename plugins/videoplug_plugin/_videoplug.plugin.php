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
	var $version = '6.10.1';
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

		// Move short tag outside of paragraph:
		$content = move_short_tags( $content, '/\[video:(youtube|dailymotion|vimeo|facebook):?[^\[\]]*\]/i' );

		// Replace video tags with html code:
		$content = replace_content_outcode( '#\[video:(youtube|dailymotion|vimeo|facebook|google|livevideo|ifilm):([^:\[\]\\\/]*|https?:\/\/.*\.facebook\.com\/[^:]*):?(\d+%?)?:?(\d+%?)?:?([^:\[\]\\\/]*)\]#',
			array( $this, 'parse_video_tag_callback' ), $content, 'replace_content', 'preg_callback' );

		return true;
	}


	/**
	 * Callback function to build HTML video code from video tag
	 *
	 * @param array Matches:
	 *              0 - Full video tag
	 *              1 - Video type: youtube, dailymotion, vimeo, facebook, google, livevideo, ifilm
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
		echo $this->get_template( 'toolbar_group_after' );

		echo $this->get_template( 'toolbar_after' );

		// Load js to work with textarea
		require_js( 'functions.js', 'blog', true, true );

		?><script type="text/javascript">
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