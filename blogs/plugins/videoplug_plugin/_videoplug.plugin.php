<?php
/**
 * This file implements the Video Plug plugin for b2evolution
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
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
	var $version = '5.0.0';
	var $number_of_installs = 1;


	/**
	 * Init
	 */
	function PluginInit( & $params )
	{
		$this->short_desc = T_('Video plug for a few popular video sites.');
		$this->long_desc = T_('This is a basic video plug pluigin. Use it by entering [video:youtube:123xyz] or [video:dailymotion:123xyz] into your post, where 123xyz is the ID of the video.');
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

		// fp> removed some embeds to make it xhtml compliant, using only object. (Hari style ;)
		// anyone, feel free to clean up the ones that have no object tag at all.

		$search_list = array(
				'#\[video:youtube:(.+?)]#',     // Youtube
				'#\[video:dailymotion:(.+?)]#', // Dailymotion
				'#\[video:google:(.+?)]#',      // Google video
				'#\[video:livevideo:(.+?)]#',   // LiveVideo
				'#\[video:ifilm:(.+?)]#',       // iFilm
				'#\[video:vimeo:(.+?)]#',       // vimeo // blueyed> TODO: might want to use oEmbed (to get title etc separately and display it below video): http://vimeo.com/api/docs/oembed
			);
		$replace_list = array(
				'<div class="videoblock"><object data="http://www.youtube.com/v/\\1" type="application/x-shockwave-flash" wmode="transparent" width="425" height="350"><param name="movie" value="http://www.youtube.com/v/\\1"></param><param name="wmode" value="transparent"></param></object></div>',
				'<div class="videoblock"><object data="http://www.dailymotion.com/swf/\\1" type="application/x-shockwave-flash" width="425" height="335" allowfullscreen="true"><param name="movie" value="http://www.dailymotion.com/swf/\\1"></param><param name="allowfullscreen" value="true"></param></object></div>',
				'<div class="videoblock"><embed style="width:400px; height:326px;" id="VideoPlayback" type="application/x-shockwave-flash" src="http://video.google.com/googleplayer.swf?docId=\\1&hl=en" flashvars=""></embed></div>',
				'<div class="videoblock"><object src="http://www.livevideo.com/flvplayer/embed/\\1" type="application/x-shockwave-flash" wmode="transparent" width="425" height="350"><param name="movie" value="http://www.livevideo.com/flvplayer/embed/\\1"></param><param name="wmode" value="transparent"></param></object></div>',
				'<div class="videoblock"><embed width="425" height="350" src="http://www.ifilm.com/efp" quality="high" bgcolor="000000" name="efp" align="middle" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" flashvars="flvbaseclip=\\1"> </embed></div>',
				'<div class="videoblock"><object data="http://vimeo.com/moogaloop.swf?clip_id=$1&amp;server=vimeo.com&amp;show_title=1&amp;show_byline=1&amp;show_portrait=0&amp;color=&amp;fullscreen=1" width="400" height="225" type="application/x-shockwave-flash">	<param name="allowfullscreen" value="true" />	<param name="allowscriptaccess" value="always" />	<param name="movie" value="http://vimeo.com/moogaloop.swf?clip_id=$1&amp;server=vimeo.com&amp;show_title=1&amp;show_byline=1&amp;show_portrait=0&amp;color=&amp;fullscreen=1" /></object></div>',
			);

		$content = replace_content_outcode( $search_list, $replace_list, $content );

		return true;
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
	 * Display a toolbar in admin.
	 *
	 * @param array Associative array of parameters
	 * @return boolean did we display a toolbar?
	 */
	function AdminDisplayToolbar( & $params )
	{
		if( !empty( $params['Item'] ) )
		{	// Item is set, get Blog from post
			$edited_Item = & $params['Item'];
			$Blog = & $edited_Item->get_Blog();
		}

		if( empty( $Blog ) )
		{	// Item is not set, try global Blog
			global $Blog;
			if( empty( $Blog ) )
			{	// We can't get a Blog, this way "apply_rendering" plugin collection setting is not available
				return false;
			}
		}

		$coll_setting_name = ( $params['target_type'] == 'Comment' ) ? 'coll_apply_comment_rendering' : 'coll_apply_rendering';
		$apply_rendering = $this->get_coll_setting( $coll_setting_name, $Blog );
		if( empty( $apply_rendering ) || $apply_rendering == 'never' ||
		    $params['edit_layout'] == 'simple' || $params['edit_layout'] == 'inskin' )
		{	// This is too complex for simple mode, don't display it:
			return false;
		}

		echo '<div class="edit_toolbar" id="video_toolbar">';
		echo T_('Video').': ';
		echo '<input type="button" id="video_youtube" title="'.T_('Insert Youtube video').'" class="quicktags" data-func="videotag|youtube" value="YouTube" />';
		echo '<input type="button" id="video_google" title="'.T_('Insert Google video').'" class="quicktags" data-func="videotag|google" value="Google video" />';
		echo '<input type="button" id="video_dailymotion" title="'.T_('Insert DailyMotion video').'" class="quicktags" data-func="videotag|dailymotion" value="DailyMotion" />';
		echo '<input type="button" id="video_livevideo" title="'.T_('Insert LiveVideo video').'" class="quicktags" data-func="videotag|livevideo" value="LiveVideo" />';
		echo '<input type="button" id="video_ifilm" title="'.T_('Insert iFilm video').'" class="quicktags" data-func="videotag|ifilm" value="iFilm" />';
		echo '<input type="button" id="video_vimeo" title="'.T_('Insert vimeo video').'" class="quicktags" data-func="videotag|vimeo" value="vimeo" />';

		echo '</div>';

		// Load js to work with textarea
		require_js( 'functions.js', 'blog', true, true );

		?>
		<script type="text/javascript">
			//<![CDATA[
			function videotag( tag )
			{
				while( 1 )
				{
					var valid_video_ID = false;
					var p = '<?php echo TS_('Enter video ID from %s:') ?>';
					var video_ID = prompt( p.replace( /%s/, tag ), '' );
					if( ! video_ID )
					{
						return;
					}

					// Validate Video ID:
					// TODO: verify validation / add for others..
					switch( tag )
					{
						case 'google':
							if( video_ID.match( /^[0-9-]+$/ ) )
							{ // valid
								valid_video_ID = true;
							}
							break;

						case 'youtube':
							// Allow HD video code with ?hd=1 at the end
							if( video_ID.match( /^[a-z0-9_?=-]+$/i ) )
							{ // valid
								valid_video_ID = true;
							}
							break;

						case 'vimeo':
							if( video_ID.match( /^\d+$/ ) )
							{ // valid
								valid_video_ID = true;
							}
							break;

						default:
							valid_video_ID = true;
							break;
					}

					if( valid_video_ID )
					{
						break;
					}
					alert( '<?php echo TS_('The video ID is invalid.'); ?>' );
				}

				tag = '[video:'+tag+':'+video_ID+']';

				textarea_wrap_selection( b2evoCanvas, tag, '', 1 );
			}
			//]]>
		</script>
		<?php

		return true;
	}
}

?>