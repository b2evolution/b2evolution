/**
 * This file initializes the password indicator JS
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois PLANQUE - {@link http://fplanque.com/}
 * 
 * Depends on: jQuery
 */
jQuery( document ).ready( function()
{
	if( typeof( evo_init_password_indicator_config ) == 'undefined' )
	{	// Don't execute code below because no config var is found:
		return;
	}

	var config = evo_init_password_indicator_config;

	window.passcheck = function passcheck()
		{
			var pass1input = jQuery( 'input#' + config.pass1_id );
			if( pass1input.length == 0 )
			{
				return; // password field not found
			}

			if( ! config.field_width )
			{   // Use current field width:
				config.field_width = pass1input.outerWidth();
			}

			var pass2input = jQuery( 'input#' + config.pass2_id );
			if( pass2input.length != 0 )
			{
				pass2input.css( 'width', ( config.field_width - 2 ) + 'px' ); // Set fixed length
			}

			// Prepare password field
			pass1input.css( 'width', ( config.field_width - 2 ) + 'px' ); // Set fixed length
			pass1input.attr( 'onkeyup', 'return passinfo( this );' ); // Add onkeyup attribute
			pass1input.parent().append( '<div id="p-container"><div id="p-result"></div><div id="p-status"></div><div id="p-time"></div></div>' );

			jQuery( 'head' ).append( '<style>' +
						'#p-container { position: relative; margin-top: 4px; height:5px; border: 1px solid #CCC; font-size: 84%; line-height:normal; color: #999; background: #FFF } ' +
						'#p-result { height:5px } ' +
						'#p-status { position:absolute; width: 100px; top:-5px; left: ' + ( config.field_width + 8 ) + 'px } ' +
						'#p-time { position:absolute; width: 400px } ' +
					'</style>'
				);
			jQuery( '#p-container' ).css( 'width', pass1input.outerWidth() - 2 );
			var pass1input_marginleft = parseInt( pass1input.css( 'margin-left' ) );
			if( pass1input_marginleft > 0 )
			{
				jQuery( '#p-container' ).css( 'margin-left', pass1input_marginleft + 'px' );
			}
		};

	window.passinfo = function passinfo( el )
		{
			var presult = document.getElementById('p-result');
			var pstatus = document.getElementById('p-status');
			var ptime = document.getElementById('p-time');

			var vlogin = '';
			var login = document.getElementById( config.login_id );
			if( login != null && login.value != '' )
			{
				vlogin = login.value;
			}

			var vemail = '';
			var email = document.getElementById( config.email_id );
			if( email != null && email.value != '' )
			{
				vemail = email.value;
			}

			// Check the password
			var weak_pwds = [vlogin, vemail];
			var passcheck = zxcvbn( el.value, weak_pwds.concat( config.blacklist ) );

			var bar_color = 'red';
			var bar_status = config.msg_status_very_weak;

			if( el.value.length == 0 )
			{
				presult.style.display = 'none';
				pstatus.style.display = 'none';
				ptime.style.display   = 'none';
			}
			else
			{
				presult.style.display = 'block';
				pstatus.style.display = 'block';
				ptime.style.display   = 'block';
			}

			switch( passcheck.score )
			{
				case 1:
					bar_color  = '#F88158';
					bar_status = config.msg_status_weak;
					break;
				case 2:
					bar_color  = '#FBB917';
					bar_status = config.msg_status_soso;
					break;
				case 3:
					bar_color  = '#8BB381';
					bar_status = config.msg_status_good;
					break;
				case 4:
					bar_color  = '#59E817';
					bar_status = config.msg_status_great;
					break;
			}

			presult.style.width = ( passcheck.score * 20 + 20 )+'%';
			presult.style.background = bar_color;

			if( config.disp_status )
			{
				pstatus.innerHTML = bar_status;
			}
			if( config.disp_time )
			{
				document.getElementById( 'p-time' ).innerHTML = config.msg_est_crack_time + ': ' + passcheck.crack_time_display;
			}
		};

	// Load password strength estimation library
	( function() {
		var a;
		a = function() {
				var a,b;
				b = document.createElement( 'script' );
				b.src = config.rsc_url + 'js/zxcvbn.js';
				b.type = 'text/javascript';
				b.async = !0;
				a = document.getElementsByTagName( 'script' )[0];
				
				return a.parentNode.insertBefore( b,a )
			};
		null != window.attachEvent ? window.attachEvent( 'onload', a ) : window.addEventListener( 'load', a , !1 )
	} ).call( this) ;

	jQuery( 'input#' + config.pass1_id + ', input#' + config.pass2_id ).keyup( function()
		{	// Validate passwords
			var minLength = config.min_pwd_len;
			var pass1Field = jQuery( 'input#' + config.pass1_id );
			var pass2Field = jQuery( 'input#' + config.pass2_id );
			var passStatus = jQuery( '#pass2_status' );
			var errorMsg = '';
			var regex = /^[^\<\&\>]+$/g; // Password cannot contain the following characters: < > &

			if( ( pass1Field.val().length && ( pass1Field.val().match( regex ) == null ) ) ||
					( pass2Field.val().length && ( pass2Field.val().match( regex ) == null ) ) )
			{
				errorMsg = config.msg_illegal_char;
				pass1Field[0].setCustomValidity( pass1Field.val().match( regex ) ? '' : errorMsg );
				pass2Field[0].setCustomValidity( pass2Field.val().match( regex ) ? '' : errorMsg );
				passStatus.html( config.error_icon + ' ' + errorMsg );
			}
			else if( ( pass1Field.val().length > 0 && pass1Field.val().length < minLength ) || ( pass2Field.val().length > 0 && pass2Field.val().length < minLength ) )
			{ // Password does not meet minimum length
				errorMsg = config.msg_min_pwd_len;
				pass1Field[0].setCustomValidity( pass1Field.val().length < minLength ? errorMsg : '' );
				pass2Field[0].setCustomValidity( pass2Field.val().length < minLength ? errorMsg : '' );
				passStatus.html( config.error_icon + ' ' + errorMsg );
			}
			else if( pass2Field.val() != pass1Field.val() )
			{	// Passwords are different
				errorMsg = config.msg_pwd_not_matching;
				pass1Field[0].setCustomValidity( '' );
				pass2Field[0].setCustomValidity( errorMsg );
				passStatus.html( config.error_icon + ' ' + errorMsg );
			}
			else
			{
				pass1Field[0].setCustomValidity( errorMsg );
				pass2Field[0].setCustomValidity( errorMsg );
				passStatus.html( errorMsg );
			};
		} );

	// Call 'passcheck' function when document is loaded
	window.passcheck();

} );