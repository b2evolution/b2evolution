/**
 * This file initialize HTML 5 MediaElement player
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois PLANQUE - {@link http://fplanque.com/}
 */

jQuery( document ).ready( function()
{
	if( evo_html5_mediaelementjs_player_width != '100%' )
	{	// Check to make player width <= window width
		evo_html5_mediaelementjs_player_width = parseInt( evo_html5_mediaelementjs_player_width );
		if( jQuery( window ).width() < evo_html5_mediaelementjs_player_width )
		{
			evo_html5_mediaelementjs_player_width = jQuery( window ).width();
		}
	}

	jQuery( '.html5_mediaelementjs_player' ).mediaelementplayer(
	{
		defaultVideoWidth: evo_html5_mediaelementjs_player_width,
		defaultVideoHeight: evo_html5_mediaelementjs_player_height,
		videoWidth: evo_html5_mediaelementjs_player_width,
		videoHeight: evo_html5_mediaelementjs_player_height,
		audioWidth: evo_html5_mediaelementjs_player_width,
	} );
} );

/**
		 * Plugin options:

			// if the <video width> is not specified, this is the default
			defaultVideoWidth: 480,
			// if the <video height> is not specified, this is the default
			defaultVideoHeight: 270,
			// if set, overrides <video width>
			videoWidth: -1,
			// if set, overrides <video height>
			videoHeight: -1,
			// width of audio player
			audioWidth: 400,
			// height of audio player
			audioHeight: 30,
			// initial volume when the player starts
			startVolume: 0.8,
			// useful for <audio> player loops
			loop: false,
			// enables Flash and Silverlight to resize to content size
			enableAutosize: true,
			// the order of controls you want on the control bar (and other plugins below)
			features: ['playpause','progress','current','duration','tracks','volume','fullscreen'],
			// Hide controls when playing and mouse is not over the video
			alwaysShowControls: false,
			// force iPad's native controls
			iPadUseNativeControls: false,
			// force iPhone's native controls
			iPhoneUseNativeControls: false,
			// force Android's native controls
			AndroidUseNativeControls: false,
			// forces the hour marker (##:00:00)
			alwaysShowHours: false,
			// show framecount in timecode (##:00:00:00)
			showTimecodeFrameCount: false,
			// used when showTimecodeFrameCount is set to true
			framesPerSecond: 25,
			// turns keyboard support on and off for this instance
			enableKeyboard: true,
			// when this player starts, it will pause other players
			pauseOtherPlayers: true,
			// array of keyboard commands
			keyActions: []

		 */