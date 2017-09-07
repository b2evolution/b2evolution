// ColorBox v1.3.17.2 - a full featured, light-weight, customizable lightbox based on jQuery 1.3+
// Copyright (c) 2011 Jack Moore - jack@colorpowered.com
// Licensed under the MIT license: http://www.opensource.org/licenses/mit-license.php

(function ($, document, window) {
	var
	// ColorBox Default Settings.
	// See http://colorpowered.com/colorbox for details.
	defaults = {
		transition: "elastic",
		speed: 300,
		width: false,
		initialWidth: "600",
		innerWidth: false,
		minWidth: false,
		maxWidth: false,
		height: false,
		initialHeight: "450",
		innerHeight: false,
		minHeight: false,
		maxHeight: false,
		scalePhotos: true,
		scrolling: true,
		inline: false,
		html: false,
		iframe: false,
		fastIframe: true,
		photo: false,
		href: false,
		title: false,
		rel: false,
		preloading: true,
		current: "image {current} of {total}",
		previous: "previous",
		next: "next",
		close: "close",
		openNewWindowText: "open in new window",
		open: false,
		returnFocus: true,
		loop: true,
		slideshow: false,
		slideshowAuto: true,
		slideshowSpeed: 2500,
		slideshowStart: "start slideshow",
		slideshowStop: "stop slideshow",
		onOpen: false,
		onLoad: false,
		onComplete: false,
		onCleanup: false,
		onClosed: false,
		overlayClose: true,
		escKey: true,
		arrowKey: true,
		top: false,
		bottom: false,
		left: false,
		right: false,
		fixed: false,
		data: false,
		displayVoting: false,
		votingUrl: '',
	},

	// Abstracting the HTML and event identifiers for easy rebranding
	colorbox = 'colorbox',
	prefix = 'cbox',
	boxElement = prefix + 'Element',

	// Events
	event_open = prefix + '_open',
	event_load = prefix + '_load',
	event_complete = prefix + '_complete',
	event_cleanup = prefix + '_cleanup',
	event_closed = prefix + '_closed',
	event_purge = prefix + '_purge',

	// Special Handling for IE
	isIE = !$.support.opacity, // Detects IE6,7,8.  IE9 supports opacity.  Feature detection alone gave a false positive on at least one phone browser and on some development versions of Chrome, hence the user-agent test.

	// Cached jQuery Object Variables
	$overlay,
	$box,
	$wrap,
	$content,
	$related,
	$window,
	$loaded,
	$loadingBay,
	$loadingOverlay,
	$title,
	$current,
	$slideshow,
	$next,
	$prev,
	$close,
	$groupControls,

	// Variables for cached values or use across multiple functions
	settings,
	prevSettings,
	interfaceHeight,
	interfaceWidth,
	defaultLoadedHeight,
	loadedHeight,
	loadedWidth,
	infobarHeight,
	element,
	index,
	photo,
	open,
	active,
	closing,
	handler,
	loadingTimer,
	publicMethod;

	// ****************
	// HELPER FUNCTIONS
	// ****************

	// jQuery object generator to reduce code size
	function $div(id, cssText, div) {
		div = document.createElement('div');
		if (id) {
			div.id = prefix + id;
		}
		div.style.cssText = cssText || '';
		return $(div);
	}

	// Convert '%' and 'px' values to integers
	function setSize(size, dimension) {
		return Math.round((/%/.test(size) ? ((dimension === 'x' ? $window.width() : $window.height()) / 100) : 1) * parseInt(size, 10));
	}

	// Checks an href to see if it is a photo.
	// There is a force photo option (photo: true) for hrefs that cannot be matched by this regex.
	function isImage(url) {
		return settings.photo || /\.(gif|png|jpg|jpeg|bmp)(?:\?([^#]*))?(?:#(\.*))?$/i.test(url);
	}

	// Assigns function results to their respective settings.  This allows functions to be used as values.
	function makeSettings(i) {
		settings = $.extend({}, $.data(element, colorbox));

		for (i in settings) {
			if ($.isFunction(settings[i]) && i.substring(0, 2) !== 'on') { // checks to make sure the function isn't one of the callbacks, they will be handled at the appropriate time.
				settings[i] = settings[i].call(element);
			}
		}

		settings.rel = settings.rel || element.rel || 'nofollow';
		settings.href = settings.href || $(element).attr('href');
		settings.title = settings.title || element.title;

		if (typeof settings.href === "string") {
			settings.href = $.trim(settings.href);
		}
	}

	function trigger(event, callback) {
		if (callback) {
			callback.call(element);
		}
		$.event.trigger(event);
	}

	// Slideshow functionality
	function slideshow() {
		var
		interval_ID,
		className = prefix + "Slideshow_",
		click = "click." + prefix,
		start,
		stop;

		if (settings.slideshow && $related[1]) {
			start = function () {
				$slideshow
					.text(settings.slideshowStop)
					.one(click, stop);
				$box.removeClass(className + "off").addClass(className + "on");
				interval_ID = setInterval( function() {
					if( ! open || ( ! settings.loop && index == $related.length - 1 ) ) {
						stop();
					}
					publicMethod.next();
				}, settings.slideshowSpeed);
			};

			stop = function () {
				clearInterval(interval_ID);
				$slideshow
					.text(settings.slideshowStart)
					.one(click, start);
				$box.removeClass(className + "on").addClass(className + "off");
			};

			if (settings.slideshowAuto) {
				start();
			} else {
				stop();
			}
		} else {
			$box.removeClass(className + "off " + className + "on");
		}
	}

	function launch(target) {
		if (!closing) {

			element = target;
			prevSettings = {};
			makeSettings();

			$related = $(element);

			index = 0;

			if (settings.rel !== 'nofollow') {
				$related = $('.' + boxElement).filter(function () {
					var relRelated = $.data(this, colorbox).rel || this.rel;
					return (relRelated === settings.rel);
				});
				index = $related.index(element);

				// Check direct calls to ColorBox.
				if (index === -1) {
					$related = $related.add(element);
					index = $related.length - 1;
				}
			}

			if (!open) {
				open = active = true; // Prevents the page-change action from queuing up if the visitor holds down the left or right keys.

				$box.show();

				if (settings.returnFocus) {
					try {
						element.blur();
						$(element).one(event_closed, function () {
							try {
								this.focus();
							} catch (e) {
								// do nothing
							}
						});
					} catch (e) {
						// do nothing
					}
				}

				$overlay.css({"cursor": settings.overlayClose ? "pointer" : "auto"}).show();

				// Opens inital empty ColorBox prior to content being loaded.
				settings.w = setSize(settings.initialWidth, 'x');
				settings.h = setSize(settings.initialHeight, 'y');
				publicMethod.position();

				trigger(event_open, settings.onOpen);

				$groupControls.add($title).hide();

				$close.html(settings.close).show();

				slideshow();
			}

			publicMethod.load(true);
		}
	}

	// ****************
	// PUBLIC FUNCTIONS
	// Usage format: $.fn.colorbox.close();
	// Usage from within an iframe: parent.$.fn.colorbox.close();
	// ****************

	publicMethod = $.fn[colorbox] = $[colorbox] = function (options, callback) {
		var $this = this;

		options = options || {};

		if (!$this[0]) {
			if ($this.selector) { // if a selector was given and it didn't match any elements, go ahead and exit.
				return $this;
			}
			// if no selector was given (ie. $.colorbox()), create a temporary element to work with
			$this = $('<a/>');
			options.open = true; // assume an immediate open
		}

		if (callback) {
			options.onComplete = callback;
		}

		$this.each(function () {
			$.data(this, colorbox, $.extend({}, $.data(this, colorbox) || defaults, options));
			$(this).addClass(boxElement);
		});

		if (($.isFunction(options.open) && options.open.call($this)) || options.open) {
			launch($this[0]);
		}

		return $this;
	};

	// Initialize ColorBox: store common calculations, preload the interface graphics, append the html.
	// This preps ColorBox for a speedy open when clicked, and minimizes the burdon on the browser by only
	// having to run once, instead of each time colorbox is opened.
	publicMethod.init = function () {
		// Create & Append jQuery Objects
		$window = $(window);
		$box = $div().attr({id: colorbox, 'class': isIE ? prefix + 'IE' : ''});
		$overlay = $div("Overlay").hide();

		$wrap = $div("Wrapper");
		$content = $div("Content").append(
			$loaded = $div("LoadedContent", 'width:0; height:0; overflow:hidden'),
			$loadingOverlay = $div("LoadingOverlay").add($div("LoadingGraphic")),
			$title = $div("Title"),
			$infoBar = $div("InfoBar").append(
				$nav = $div("Navigation").append(
					$prev = $div("Previous"),
					$current = $div("Current"),
					$next = $div("Next")
				),
				$voting = $div("Voting"),
				$slideshow = $div("Slideshow"),
				$close = $div("Close"),
				$open = $div("Open")
			)
		);
		$wrap.append( $content );

		$loadingBay = $div(false, 'position:absolute; width:9999px; visibility:hidden; display:none');

		$('body').prepend($overlay, $box.append($wrap, $loadingBay));

		$voting.data( 'voting_positions_done', 0 );
		previous_title = '';

		$content.children()
		.hover(function () {
			$(this).addClass('hover');
		}, function () {
			$(this).removeClass('hover');
		}).addClass('hover');

		// Cache values needed for size calculations
		interfaceHeight = $content.outerHeight(true) - $content.height();//Subtraction needed for IE6
		interfaceWidth = $content.outerWidth(true) - $content.width();
		loadedHeight = $loaded.outerHeight(true);
		loadedWidth = $loaded.outerWidth(true);

		defaultLoadedHeight = loadedHeight;
		infobarHeight = $close.height() + 4;

		// Setting padding to remove the need to do size conversions during the animation step.
		$box.css({"padding-bottom": interfaceHeight, "padding-right": interfaceWidth}).hide();

		// Setup button events.
		// Anonymous functions here keep the public method from being cached, thereby allowing them to be redefined on the fly.
		$next.click(function () {
			publicMethod.next();
		});
		$prev.click(function () {
			publicMethod.prev();
		});
		$close.click(function () {
			publicMethod.close();
		});
		$open.click(function () {
			publicMethod.close();
		});

		$groupControls = $next.add($prev).add($current).add($slideshow);

		// Adding the 'hover' class allowed the browser to load the hover-state
		// background graphics in case the images were not part of a sprite.  The class can now can be removed.
		$content.children().removeClass('hover');

		$overlay.click(function () {
			if (settings.overlayClose) {
				publicMethod.close();
			}
		});

		// Set Navigation Key Bindings
		$(document).bind('keydown.' + prefix, function (e) {
			var key = e.keyCode;
			if (open && settings.escKey && key === 27) {
				e.preventDefault();
				publicMethod.close();
			}
			if (open && settings.arrowKey && $related[1]) {
				if (key === 37) {
					e.preventDefault();
					$prev.click();
				} else if (key === 39) {
					e.preventDefault();
					$next.click();
				}
			}
		});
	};

	publicMethod.remove = function () {
		$box.add($overlay).remove();
		$('.' + boxElement).removeData(colorbox).removeClass(boxElement);
	};

	publicMethod.position = function (speed, loadedCallback) {
		var w = ( prevSettings.pw == undefined || settings.w > prevSettings.pw ) ? settings.w : prevSettings.pw;
		var h = ( prevSettings.ph == undefined || settings.h > prevSettings.ph ) ? settings.h : prevSettings.ph;

		var voting_wrapper = $('#colorbox .voting_wrapper');
		var bottomMargin = parseInt( $content.css( 'border-bottom' ) );

		$infoBar.css({ 'minHeight': infobarHeight + 'px' });

		if( w <= 700 && $voting.is(':visible') )
		{ // voting button, title and others will not fit in 1 line
			voting_wrapper.addClass( 'compact' );
			loadedHeight = ( defaultLoadedHeight * 2 ) - 3;
		}
		else
		{
			voting_wrapper.removeClass( 'compact' );
			loadedHeight = defaultLoadedHeight;
		}

		var top = 0, left = 0;

		$window.unbind('resize.' + prefix);

		// remove the modal so that it doesn't influence the document width/height
		$box.hide();

		if (settings.fixed) {
			$box.css({position: 'fixed'});
		} else {
			top = $window.scrollTop();
			left = $window.scrollLeft();
			$box.css({position: 'absolute'});
		}

		// keeps the top and left positions within the browser's viewport.
		if (settings.right !== false) {
			left += Math.max($window.width() - w - loadedWidth - interfaceWidth - setSize(settings.right, 'x'), 0);
		} else if (settings.left !== false) {
			left += setSize(settings.left, 'x');
		} else {
			left += Math.round(Math.max($window.width() - w - loadedWidth - interfaceWidth, 0) / 2);
		}

		if (settings.bottom !== false) {
			top += Math.max(document.documentElement.clientHeight - h - loadedHeight - interfaceHeight - setSize(settings.bottom, 'y'), 0);
		} else if (settings.top !== false) {
			top += setSize(settings.top, 'y');
		} else {
			top += Math.round(Math.max(document.documentElement.clientHeight - h - loadedHeight - interfaceHeight, 0) / 2);
		}

		$box.show();

		// setting the speed to 0 to reduce the delay between same-sized content.
		speed = ($box.width() === w + loadedWidth && $box.height() === h + loadedHeight) ? 0 : speed || 0;

		// this gives the wrapper plenty of breathing room so it's floated contents can move around smoothly,
		// but it has to be shrank down around the size of div#colorbox when it's done.  If not,
		// it can invoke an obscure IE bug when using iframes.
		$wrap[0].style.width = $wrap[0].style.height = "9999px";

		function modalDimensions(that) {
			// loading overlay height has to be explicitly set for IE6.
			$content[0].style.width = that.style.width;
			$loadingOverlay[0].style.height = $loadingOverlay[1].style.height = $content[0].style.height = that.style.height;
		}

		$box.dequeue().animate({width: w + loadedWidth, height: h + loadedHeight, top: top, left: left}, {
			duration: speed,
			complete: function () {
				modalDimensions(this);

				active = false;

				// shrink the wrapper down to exactly the size of colorbox to avoid a bug in IE's iframe implementation.
				$wrap[0].style.width = (w + loadedWidth + interfaceWidth) + "px";
				$wrap[0].style.height = (h + loadedHeight + interfaceHeight) + "px";

				if (loadedCallback) {
					loadedCallback();
				}

				setTimeout(function(){  // small delay before binding onresize due to an IE8 bug.
					$window.bind('resize.' + prefix, publicMethod.position);
				}, 1);

				publicMethod.resizeVoting();

				( $wrap.parent().width() < 380 ) ? $slideshow.hide() : $slideshow.show();
			},
			step: function () {
				modalDimensions(this);
			}
		});
	};

	publicMethod.resize = function (options) {
		if (open) {
			options = options || {};

			if (options.width) {
				settings.w = setSize(options.width, 'x') - loadedWidth - interfaceWidth;
			}
			if (options.innerWidth) {
				settings.w = setSize(options.innerWidth, 'x');
			}
			$loaded.css({width: settings.w});

			if (options.height) {
				settings.h = setSize(options.height, 'y') - loadedHeight - interfaceHeight;
			}
			if (options.innerHeight) {
				settings.h = setSize(options.innerHeight, 'y');
			}
			if (!options.innerHeight && !options.height) {
				var $child = $loaded.wrapInner("<div style='overflow:auto'></div>").children(); // temporary wrapper to get an accurate estimate of just how high the total content should be.
				settings.h = $child.outerHeight();
				$child.replaceWith($child.children()); // ditch the temporary wrapper div used in height calculation
			}
			$loaded.css({height: settings.h});

			prevSettings.pw = settings.w;
			prevSettings.ph = settings.h;

			publicMethod.position(settings.transition === "none" ? 0 : settings.speed);
		}
	};

	publicMethod.prep = function (object) {
		if (!open) {
			return;
		}

		var callback, speed = settings.transition === "none" ? 0 : settings.speed;


		$loaded.remove();
		$loaded = $div('LoadedContent').append(object);

		function getWidth() {
			settings.w = settings.w || $loaded.width();
			settings.w = settings.mw && settings.mw < settings.w ? settings.mw : settings.w;
			settings.w = settings.minWidth && settings.minWidth > settings.w ? settings.minWidth : settings.w;
			prevSettings.pw = ( prevSettings.pw == undefined || settings.w > prevSettings.pw ) ? settings.w : prevSettings.pw;
			return prevSettings.pw;
		}
		function getHeight() {
			settings.h = settings.h || $loaded.height();
			settings.h = settings.mh && settings.mh < settings.h ? settings.mh : settings.h;
			settings.h = settings.minHeight && settings.minHeight > settings.h ? settings.minHeight : settings.h;
			prevSettings.ph = (  prevSettings.ph == undefined || settings.h > prevSettings.ph )? settings.h : prevSettings.ph;
			return prevSettings.ph;
		}

		$loaded.hide()
		.appendTo($loadingBay.show())// content has to be appended to the DOM for accurate size calculations.
		.css({width: getWidth(), overflow: settings.scrolling ? 'auto' : 'hidden'})
		.css({height: getHeight()})// sets the height independently from the width in case the new width influences the value of height.
		.prependTo($content);

		$loadingBay.hide();

		// floating the IMG removes the bottom line-height and fixed a problem where IE miscalculates the width of the parent element as 100% of the document width.
		//$(photo).css({'float': 'none', marginLeft: 'auto', marginRight: 'auto'});

		$(photo).css({'float': 'none'});

		callback = function () {
			var prev, prevSrc, next, nextSrc, total = $related.length, iframe, complete;

			if (!open) {
				return;
			}

			function removeFilter() {
				if (isIE) {
					$box[0].style.removeAttribute('filter');
				}
			}

			complete = function () {
				clearTimeout(loadingTimer);
				$loadingOverlay.hide();
				trigger(event_complete, settings.onComplete);
			};

			if (isIE) {
				//This fadeIn helps the bicubic resampling to kick-in.
				if (photo) {
					$loaded.fadeIn(100);
				}
			}

			//$title.attr( 'title', settings.title );
			//$title.html(settings.title).add($loaded).show();
			$title.add($loaded).show();

			if (total > 1) { // handle grouping
				if (typeof settings.current === "string" && $loaded.width() > 380) {
					$current.html(settings.current.replace('{current}', index + 1).replace('{total}', total)).show();
				}

				$next[(settings.loop || index < total - 1) ? "show" : "hide"]().html(settings.next);
				$prev[(settings.loop || index) ? "show" : "hide"]().html(settings.previous);

				prev = index ? $related[index - 1] : $related[total - 1];
				next = index < total - 1 ? $related[index + 1] : $related[0];

				if (settings.slideshow && $loaded.width() > 380) {
					$slideshow.show();
				}

				// Preloads images within a rel group
				if (settings.preloading) {
					nextSrc = $.data(next, colorbox).href || next.href;
					prevSrc = $.data(prev, colorbox).href || prev.href;

					nextSrc = $.isFunction(nextSrc) ? nextSrc.call(next) : nextSrc;
					prevSrc = $.isFunction(prevSrc) ? prevSrc.call(prev) : prevSrc;

					if (isImage(nextSrc)) {
						$('<img/>')[0].src = nextSrc;
					}

					if (isImage(prevSrc)) {
						$('<img/>')[0].src = prevSrc;
					}
				}
			} else {
				$groupControls.hide();
			}

			if (settings.iframe) {
				iframe = $('<iframe/>').addClass(prefix + 'Iframe')[0];

				if (settings.fastIframe) {
					complete();
				} else {
					$(iframe).one('load', complete);
				}
				iframe.name = prefix + (+new Date());
				iframe.src = settings.href;

				if (!settings.scrolling) {
					iframe.scrolling = "no";
				}

				if (isIE) {
					iframe.frameBorder = 0;
					iframe.allowTransparency = "true";
				}

				$(iframe).appendTo($loaded).one(event_purge, function () {
					iframe.src = "//about:blank";
				});
			} else {
				complete();
			}

			if (settings.transition === 'fade') {
				$box.fadeTo(speed, 1, removeFilter);
			} else {
				removeFilter();
			}
		};

		if (settings.transition === 'fade') {
			$box.fadeTo(speed, 0, function () {
				publicMethod.position(0, callback);
			});
		} else {
			publicMethod.position(speed, callback);
		}
	};

	publicMethod.load = function (launched) {
		var href, setResize, prep = publicMethod.prep;

		active = true;

		photo = false;

		element = $related[index];

		if (!launched) {
			makeSettings();
		}

		trigger(event_purge);

		trigger(event_load, settings.onLoad);

		previous_title = settings.title;

		if( settings.displayVoting && settings.votingUrl != '' && element.id != '' )
		{ // Initialize the actions for the voting controls
			if( $voting.data( 'voting_positions_done' ) == 0 )
			{ // Fix positions of the control elements
				if( loadedHeight == 0 )
				{ // Fix height because sometimes it doesn't have a time for initialization
					loadedHeight = $loaded.outerHeight(true);
				}
				//loadedHeight += $voting.outerHeight();
				$voting.data( 'voting_positions_done', 1 );
			}
			$voting.show();

			// Initialize the voting events
			init_voting_bar( $voting, settings.votingUrl, element.id, true );
		}
		else if( $voting.html() != '' )
		{ // Clear the voting panel if previous image displayed this
			//loadedHeight -= $voting.outerHeight();
			$voting.html( '' ).hide();
			$voting.data( 'voting_positions_done', 0 );
		}

		settings.h = settings.height ?
				setSize(settings.height, 'y') - loadedHeight - interfaceHeight :
				settings.innerHeight && setSize(settings.innerHeight, 'y');

		settings.w = settings.width ?
				setSize(settings.width, 'x') - loadedWidth - interfaceWidth :
				settings.innerWidth && setSize(settings.innerWidth, 'x');

		// Sets the minimum dimensions for use in image scaling
		settings.mw = settings.w;
		settings.mh = settings.h;


		// Re-evaluate the minimum width and height based on maxWidth and maxHeight values.
		// If the width or height exceed the maxWidth or maxHeight, use the maximum values instead.
		if (settings.maxWidth) {
			settings.mw = setSize(settings.maxWidth, 'x') - loadedWidth - interfaceWidth;
			settings.mw = settings.w && settings.w < settings.mw ? settings.w : settings.mw;
		}
		if (settings.maxHeight) {
			settings.mh = setSize(settings.maxHeight, 'y') - loadedHeight - interfaceHeight;
			settings.mh = settings.h && settings.h < settings.mh ? settings.h : settings.mh;
		}

		href = settings.href;

		loadingTimer = setTimeout(function () {
			$loadingOverlay.show();
		}, 100);
		if (settings.inline) {
			// Inserts an empty placeholder where inline content is being pulled from.
			// An event is bound to put inline content back when ColorBox closes or loads new content.
			$div().hide().insertBefore($(href)[0]).one(event_purge, function () {
				$(this).replaceWith($loaded.children());
			});
			prep($(href));
		} else if (settings.iframe) {
			// IFrame element won't be added to the DOM until it is ready to be displayed,
			// to avoid problems with DOM-ready JS that might be trying to run in that iframe.
			prep(" ");
		} else if (settings.html) {
			prep(settings.html);
		} else if (isImage(href)) {
			$(photo = new Image())
			.addClass(prefix + 'Photo')
			.error(function () {
				settings.title = false;
				prep($div('Error').text('This image could not be loaded'));
			})
			.load(function () {
				var percent;
				photo.onload = null; //stops animated gifs from firing the onload repeatedly.

				if (settings.scalePhotos) {
					setResize = function () {
						photo.height -= photo.height * percent;
						photo.width -= photo.width * percent;
					};
					if (settings.mw && photo.width > settings.mw) {
						percent = (photo.width - settings.mw) / photo.width;
						setResize();
					}
					if (settings.mh && photo.height > settings.mh) {
						percent = (photo.height - settings.mh) / photo.height;
						setResize();
					}
				}

				if (settings.h) {
					photo.style.marginTop = Math.max(settings.h - photo.height, 0) / 2 + 'px';
				}

				// Clear classes from previous image
				jQuery( photo ).removeClass( 'zoomin zoomout' );

				colorbox_is_zoomed = false;
				var photo_width = 0;
				var photo_height = 0;
				var photo_is_big = photo.naturalWidth > photo.width * 1.1 || photo.naturalHeight > photo.height * 1.1;
				if( photo_is_big )
				{ // If photo is big - make a specific cursor over photo
					photo.className = photo.className + ' zoomin';
				}
				if( ! photo_is_big && $related[1] && ( index < $related.length - 1 || settings.loop ) )
				{	// Use a click event to display next photo only when Photo is small and we have at least two photos:
					photo.onclick = function( e )
					{
						publicMethod.next();
					}
				}
				if( photo_is_big )
				{ // Photo is big - Use a click event to zoom a photo
					jQuery( photo ).bind( 'click dblclick', function( event, touch_event )
					{
						if( colorbox_is_zoomed )
						{ // Zoom out a photo to window size
							photo.className = photo.className.replace( /zoomout/, '' );
							photo.width = photo_width;
							photo.height = photo_height;
							// Reset image position and scrolling to top/left corner:
							jQuery( this ).parent().scrollLeft( 0 ).scrollTop( 0 );
							jQuery( this ).css( { 'position': 'relative', 'top': '0', 'left': '0' } );
						}
						else
						{ // Zoom in a photo to real size
							publicMethod.resize({
								width: settings.mw,
								height: settings.mh + parseInt( $loaded.css( 'margin-bottom' ) )
							});
							var this_offset = jQuery( this ).offset();
							var pageX = typeof( event.pageX ) != 'undefined' ? event.pageX : touch_event.originalEvent.touches[0].pageX;
							var pageY = typeof( event.pageY ) != 'undefined' ? event.pageY : touch_event.originalEvent.touches[0].pageY;
							var pecentX = ( pageX - this_offset.left ) / jQuery( this ).width();
							var pecentY = ( pageY - this_offset.top ) / jQuery( this ).height();

							photo.className = photo.className + ' zoomout';
							$(photo).css({ 'position': 'static', 'top': 0, 'left': 0, 'transform': 'none' });
							photo_width = photo.width;
							photo_height = photo.height;
							photo.removeAttribute( 'width' );
							photo.removeAttribute( 'height' );

							// Scroll image to mouse pointer
							var this_parent = jQuery( this ).parent()[0];
							jQuery( this ).parent()
								.scrollLeft( pecentX * ( this_parent.scrollWidth - this_parent.clientWidth ) )
								.scrollTop( pecentY * ( this_parent.scrollHeight - this_parent.clientHeight ) );
						}
						colorbox_is_zoomed = colorbox_is_zoomed ? false : true;
					} );
				}

				if (isIE) {
					photo.style.msInterpolationMode = 'bicubic';
				}

				setTimeout(function () { // A pause because Chrome will sometimes report a 0 by 0 size otherwise.
					prep(photo);
				}, 1);
			});

			setTimeout(function () { // A pause because Opera 10.6+ will sometimes not run the onload function otherwise.
				photo.src = href;
			}, 1);

			//$open.html( '<a href="' + href + '" target="_blank">' + settings.openNewWindowText + '</a>' ).show();
		} else if (href) {
			$loadingBay.load(href, settings.data, function (data, status, xhr) {
				prep(status === 'error' ? $div('Error').text('Request unsuccessful: ' + xhr.statusText) : $(this).contents());
			});
		}
	};

	// Navigates to the next page/image in a set.
	publicMethod.next = function () {
		if (!active && $related[1] && (index < $related.length - 1 || settings.loop)) {
			index = index < $related.length - 1 ? index + 1 : 0;
			publicMethod.load();
		}
	};

	publicMethod.prev = function () {
		if (!active && $related[1] && (index || settings.loop)) {
			index = index ? index - 1 : $related.length - 1;
			publicMethod.load();
		}
	};

	// Note: to use this within an iframe use the following format: parent.$.fn.colorbox.close();
	publicMethod.close = function () {
		if (open && !closing) {

			closing = true;

			open = false;

			trigger(event_cleanup, settings.onCleanup);

			$window.unbind('.' + prefix);

			$overlay.fadeTo(200, 0);

			$box.stop().fadeTo(300, 0, function () {

				$box.add($overlay).css({'opacity': 1, cursor: 'auto'}).hide();

				trigger(event_purge);

				$loaded.remove();

				setTimeout(function () {
					closing = false;
					trigger(event_closed, settings.onClosed);
				}, 1);
			});
		}
	};

	publicMethod.resizeVoting = function() {

		var voting_wrapper = $('#colorbox .voting_wrapper');
		var w = $wrap.parent().width();
		if( w <= 480 )
		{
			$current.hide();
		}
		else
		{
			$current.show();
		}

		$infoBar.css({ 'minHeight': infobarHeight + 'px' });

		if( w <= 700 && $voting.is(':visible') )
		{ // voting button, title and others will not fit in 1 line
			voting_wrapper.addClass( 'compact' );
			loadedHeight = ( defaultLoadedHeight * 2 ) - 3;
		}
		else
		{
			voting_wrapper.removeClass( 'compact' );
			loadedHeight = defaultLoadedHeight;
		}
	};

	// A method for fetching the current element ColorBox is referencing.
	// returns a jQuery object.
	publicMethod.element = function () {
		return $(element);
	};

	publicMethod.settings = defaults;

	// Bind the live event before DOM-ready for maximum performance in IE6 & 7.
	handler = function (e) {
		// checks to see if it was a non-left mouse-click and for clicks modified with ctrl, shift, or alt.
		if (!((e.button !== 0 && typeof e.button !== 'undefined') || e.ctrlKey || e.shiftKey || e.altKey)) {
			e.preventDefault();
			launch(this);
		}
	};

	if ($.fn.delegate) {
		$(document).delegate('.' + boxElement, 'click', handler);
	} else {
		$(document).on('click', '.' + boxElement, handler);
	}

	// Initializes ColorBox when the DOM has loaded
	$(publicMethod.init);

}(jQuery, document, this));

// Rewrite double click event for double tap event on touch devices (in order to zoom big images)
jQuery.event.special.dblclick = {
	setup: function( data, namespaces )
	{
		var elem = this,
			$elem = jQuery( elem );
		$elem.bind( 'touchstart.dblclick', jQuery.event.special.dblclick.handler );
	},

	teardown: function( namespaces )
	{
		var elem = this,
			$elem = jQuery( elem );
		$elem.unbind( 'touchstart.dblclick' );
	},

	handler: function( event )
	{
		var elem = event.target,
			$elem = jQuery( elem ),
			lastTouch = $elem.data( 'lastTouch' ) || 0,
			now = new Date().getTime();

		var delta = now - lastTouch;
		if( delta > 20 && delta < 500 )
		{
			$elem.data( 'lastTouch', 0 );
			$elem.trigger( 'dblclick', event );
		} else
		{
			$elem.data( 'lastTouch', now );
		}
	}
};