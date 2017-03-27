( function( window, evo, shortcode, $ ) {
	'use strict';

	var views = {},
		instances = {};

	/**
	 * evo_views
	 *
	 * A set of utilities that simplifies adding custom UI within a TinyMCE editor.
	 * At its core, it serves as a series of converters, transforming text to a
	 * custom UI, and back again.
	 */
	evo.views = {

		/**
		 * Registers a new view type.
		 *
		 * @param {String} type   The view type.
		 * @param {Object} extend An object to extend wp.mce.View.prototype with.
		 */
		register: function( type, options ) {
			//views[ type ] = wp.mce.View.extend( _.extend( extend, { type: type } ) );
			views[ type ] = evo.View.extend( $.extend( options, {	type: type } ) );
		},

		/**
		 * Unregisters a view type.
		 *
		 * @param {String} type The view type.
		 */
		unregister: function( type ) {
			delete views[ type ];
		},

		/**
		 * Returns the settings of a view type.
		 *
		 * @param {String} type The view type.
		 *
		 * @return {Function} The view constructor.
		 */
		get: function( type ) {
			return views[ type ];
		},

		/**
		 * Unbinds all view nodes.
		 * Runs before removing all view nodes from the DOM.
		 */
		unbind: function() {
			$.each( instances, function( index, instance ) {
				instance.unbind();
			} );
		},

		/**
		 * Scans a given string for each view's pattern,
		 * replacing any matches with markers,
		 * and creates a new instance for every match.
		 *
		 * @param {String} content The string to scan.
		 *
		 * @return {String} The string with markers.
		 */
		setMarkers: function( content ) {
			var pieces = [ { content: content } ],
				self = this,
				instance, current;

			$.each( views, function( type, view ) {
				current = pieces.slice();
				pieces  = [];

				$.each( current, function( index, piece ) {
					var remaining = piece.content,
						result, text;

					// Ignore processed pieces, but retain their location.
					if ( piece.processed ) {
						pieces.push( piece );
						return;
					}

					// Iterate through the string progressively matching views
					// and slicing the string as we go.
					while ( remaining && ( result = view.prototype.match( remaining ) ) ) {
						// Any text before the match becomes an unprocessed piece.
						if ( result.index ) {
							pieces.push( { content: remaining.substring( 0, result.index ) } );
						}

						instance = self.createInstance( type, result.content, result.options );
						text = instance.loader ? '.' : instance.text;

						// Add the processed piece for the match.
						pieces.push( {
							content: instance.ignore ? text : '<span data-evo-view-marker="' + instance.encodedText + '">' + text + '</span>',
							processed: true
						} );

						// Update the remaining content.
						remaining = remaining.slice( result.index + result.content.length );
					}

					// There are no additional matches.
					// If any content remains, add it as an unprocessed piece.
					if ( remaining ) {
						pieces.push( { content: remaining } );
					}
				} );
			} );

			var arr = [];
			$.each( pieces, function( index, piece ) {
					return arr.push( piece.content );
				});
			content = arr.join( '' );

			return content;
		},

		/**
		 * Create a view instance.
		 *
		 * @param {String}  type    The view type.
		 * @param {String}  text    The textual representation of the view.
		 * @param {Object}  options Options.
		 * @param {Boolean} force   Recreate the instance. Optional.
		 *
		 * @return {wp.mce.View} The view instance.
		 */
		createInstance: function( type, text, options, force ) {
			var View = this.get( type ),
				encodedText,
				instance;

			text = tinymce.DOM.decode( text );

			if ( ! force ) {
				instance = this.getInstance( text );

				if ( instance ) {
					return instance;
				}
			}

			encodedText = encodeURIComponent( text );

			options = $.extend( options || {}, {
				text: text,
				encodedText: encodedText,
				renderedHTML: null,
			} );

			return instances[ encodedText ] = new View( options );
		},

		/**
		 * Get a view instance.
		 *
		 * @param {(String|HTMLElement)} object The textual representation of the view or the view node.
		 *
		 * @return {wp.mce.View} The view instance or undefined.
		 */
		getInstance: function( object ) {
			if ( typeof object === 'string' ) {
				return instances[ encodeURIComponent( object ) ];
			}

			return instances[ $( object ).attr( 'data-evo-view-text' ) ];
		},

		/**
		 * Given a view node, get the view's text.
		 *
		 * @param {HTMLElement} node The view node.
		 *
		 * @return {String} The textual representation of the view.
		 */
		getText: function( node ) {
			return decodeURIComponent( $( node ).attr( 'data-evo-view-text' ) || '' );
		},

		/**
		 * Renders all view nodes that are not yet rendered.
		 *
		 * @param {Boolean} force Rerender all view nodes.
		 */
		render: function( force ) {
			$.each( instances, function( index, instance ) {
				instance.render( null, force );
			} );
		},

		/**
		 * Update the text of a given view node.
		 *
		 * @param {String}         text   The new text.
		 * @param {tinymce.Editor} editor The TinyMCE editor instance the view node is in.
		 * @param {HTMLElement}    node   The view node to update.
		 * @param {Boolean}        force  Recreate the instance. Optional.
		 */
		update: function( text, editor, node, force ) {
			var instance = this.getInstance( node );

			if ( instance ) {
				instance.update( text, editor, node, force );
			}
		},

		/**
		 * Renders any editing interface based on the view type.
		 *
		 * @param {tinymce.Editor} editor The TinyMCE editor instance the view node is in.
		 * @param {HTMLElement}    node   The view node to edit.
		 */
		edit: function( editor, node ) {
			var instance = this.getInstance( node );

			if ( instance && instance.edit ) {
				instance.edit( instance.text, function( text, force ) {
					instance.update( text, editor, node, force );
				} );
			}
		},

		/**
		 * Remove a given view node from the DOM.
		 *
		 * @param {tinymce.Editor} editor The TinyMCE editor instance the view node is in.
		 * @param {HTMLElement}    node   The view node to remove.
		 */
		remove: function( editor, node ) {
			var instance = this.getInstance( node );

			if ( instance ) {
				instance.remove( editor, node );
			}
		}
	};

	//evo.View.extend = Backbone.View.extend;


	/**
	 * A Backbone-like View constructor intended for use when rendering a TinyMCE View.
	 * The main difference is that the TinyMCE View is not tied to a particular DOM node.
	 *
	 * @param {Object} options Options.
	 */
	evo.View = function( options ) {
		$.extend( this, options );
		this.initialize();
	};

	evo.View.extend = function( options ) {
		var subView = function( options ) {
			evo.View.call( this, options );
		}

		subView.prototype = Object.create( evo.View.prototype );
		subView.prototype.constructor = subView;
		for( name in options ) {
			subView.prototype[name] = options[name];
		}

		return subView;
	};

	$.extend( evo.View.prototype, {
		/**
		 * The content.
		 *
		 * @type {*}
		 */
		content: null,

		/**
		 * Whether or not to display a loader.
		 *
		 * @type {Boolean}
		 */
		loader: true,

		/**
		 * Runs after the view instance is created.
		 */
		initialize: function() {},

		/**
		 * Retuns the content to render in the view node.
		 *
		 * @return {*}
		 */
		getContent: function( editor ) {
			return this.content;
		},

		/**
		 * Renders all view nodes tied to this view instance that are not yet rendered.
		 *
		 * @param {String}  content The content to render. Optional.
		 * @param {Boolean} force   Rerender all view nodes tied to this view instance. Optional.
		 */
		render: function( content, force ) {
			var self = this;

			if ( content != null ) {
				this.content = content;
			}

			content = this.getContent();

			// If there's nothing to render and no loader needs to be shown, stop.
			if ( ! this.loader && ! content ) {
				return;
			}

			// We're about to rerender all views of this instance, so unbind rendered views.
			force && this.unbind();

			// Replace any left over markers.
			this.replaceMarkers();

			if ( content ) {
				this.setContent( content, function( editor, node, contentNode ) {
					$( node ).data( 'rendered', true );
					self.bindNode.call( self, editor, node, contentNode );
				}, force ? null : false );
			} else {
				this.setLoader();
			}
		},

		/**
		 * Binds a given node after its content is added to the DOM.
		 */
		bindNode: function() {},

		/**
		 * Unbinds a given node before its content is removed from the DOM.
		 */
		unbindNode: function() {},

		/**
		 * Unbinds all view nodes tied to this view instance.
		 * Runs before their content is removed from the DOM.
		 */
		unbind: function() {
			var self = this;
			this.getNodes( function( editor, node, contentNode ) {
				self.unbindNode.call( self, editor, node, contentNode );
				$( node ).trigger( 'evo-view-unbind' );
			}, true );
		},

		/**
		 * Gets all the TinyMCE editor instances that support views.
		 *
		 * @param {Function} callback A callback.
		 */
		getEditors: function( callback ) {
			$.each( tinymce.editors, function( index, editor ) {
				if ( editor.plugins.evo_view ) {
					callback.call( this, editor );
				}
			}, this );
		},

		/**
		 * Gets all view nodes tied to this view instance.
		 *
		 * @param {Function} callback A callback.
		 * @param {Boolean}  rendered Get (un)rendered view nodes. Optional.
		 */
		getNodes: function( callback, rendered ) {
			var self = this;
			this.getEditors( function( editor ) {

				$( editor.getBody() )
					.find( '[data-evo-view-text="' + self.encodedText + '"]' )
					.filter( function() {
						var data;

						if ( rendered == null ) {
							return true;
						}

						data = $( this ).data( 'rendered' ) === true;

						return rendered ? data : ! data;
					} )
					.each( function() {
						callback.call( self, editor, this, $( this ).find( '.evo-view-content' ).get( 0 ) );
					} );
			} );
		},

		/**
		 * Gets all marker nodes tied to this view instance.
		 *
		 * @param {Function} callback A callback.
		 */
		getMarkers: function( callback ) {
			var encodedText = this.encodedText;
			this.getEditors( function( editor ) {
				var self = this;

				$( editor.getBody() )
					.find( '[data-evo-view-marker="' + encodedText + '"]' )
					.each( function() {
						callback.call( self, editor, this );
					} );
			} );
		},

		/**
		 * Marker text
		 */
		markerText: '<div class="evo-view-wrap" data-evo-view-text="%encodedText%" data-evo-view-type="%viewType%">' +
									'<p class="evo-view-selection-before">\u00a0</p>' +
									'<div class="evo-view-body" contenteditable="false">' +
										'<div class="evo-view-content evo-view-type-%viewType%"></div>' +
									'</div>' +
									'<p class="evo-view-selection-after">\u00a0</p>' +
								'</div>',

		/**
		 * Replaces all marker nodes tied to this view instance.
		 */
		replaceMarkers: function() {
			var self = this;
			this.getMarkers( function( editor, node ) {
				var selected = node === editor.selection.getNode(),
					$viewNode;

				if ( ! self.loader && $( node ).text() !== self.text ) {
					editor.dom.setAttrib( node, 'data-evo-view-marker', null );
					return;
				}

				var markerText = self.markerText.replace( /%encodedText%/g, self.encodedText ).replace( /%viewType%/g, self.type );
				$viewNode = editor.$( markerText );

				editor.$( node ).replaceWith( $viewNode );

				if ( selected ) {
					editor.evo.setViewCursor( false, $viewNode[0] );
				}
			} );
		},

		/**
		 * Removes all marker nodes tied to this view instance.
		 */
		removeMarkers: function() {
			this.getMarkers( function( editor, node ) {
				editor.dom.setAttrib( node, 'data-evo-view-marker', null );
			} );
		},

		/**
		 * Sets the content for all view nodes tied to this view instance.
		 *
		 * @param {*}        content  The content to set.
		 * @param {Function} callback A callback. Optional.
		 * @param {Boolean}  rendered Only set for (un)rendered nodes. Optional.
		 */
		setContent: function( content, callback, rendered ) {
			if ( $.type( content ) === 'object' && content.body.indexOf( '<script' ) !== -1 ) {
				this.setIframes( content.head || '', content.body, callback, rendered );
			} else if ( $.type( content ) === 'string' && content.indexOf( '<script' ) !== -1 ) {
				this.setIframes( '', content, callback, rendered );
			} else {
				this.getNodes( function( editor, node, contentNode ) {
					content = content.body || content;

					if ( content.indexOf( '<iframe' ) !== -1 ) {
						content += '<div class="evo-view-overlay"></div>';
					}

					contentNode.innerHTML = '';
					contentNode.appendChild( typeof content  === 'string' ? editor.dom.createFragment( content ) : content );

					callback && callback.call( this, editor, node, contentNode );
				}, rendered );
			}
		},

		/**
		 * Sets the content in an iframe for all view nodes tied to this view instance.
		 *
		 * @param {String}   head     HTML string to be added to the head of the document.
		 * @param {String}   body     HTML string to be added to the body of the document.
		 * @param {Function} callback A callback. Optional.
		 * @param {Boolean}  rendered Only set for (un)rendered nodes. Optional.
		 */
		setIframes: function( head, body, callback, rendered ) {
			var MutationObserver = window.MutationObserver || window.WebKitMutationObserver || window.MozMutationObserver,
				self = this;

			this.getNodes( function( editor, node, contentNode ) {
				var dom = editor.dom,
					styles = '',
					bodyClasses = editor.getBody().className || '',
					editorHead = editor.getDoc().getElementsByTagName( 'head' )[0];

				tinymce.each( dom.$( 'link[rel="stylesheet"]', editorHead ), function( index, link ) {
					if ( link.href && link.href.indexOf( 'skins/lightgray/content.min.css' ) === -1 &&
						link.href.indexOf( 'skins/wordpress/wp-content.css' ) === -1 ) {

						styles += dom.getOuterHTML( link );
					}
				} );

				if ( self.iframeHeight ) {
					dom.add( contentNode, 'div', { style: {
						width: '100%',
						height: self.iframeHeight
					} } );
				}

				// Seems the browsers need a bit of time to insert/set the view nodes,
				// or the iframe will fail especially when switching Text => Visual.
				setTimeout( function() {
					var iframe, iframeDoc, observer, i, block;

					contentNode.innerHTML = '';

					iframe = dom.add( contentNode, 'iframe', {
						/* jshint scripturl: true */
						src: tinymce.Env.ie ? 'javascript:""' : '',
						frameBorder: '0',
						allowTransparency: 'true',
						scrolling: 'no',
						'class': 'evo-view-sandbox',
						style: {
							width: '100%',
							display: 'block'
						},
						height: self.iframeHeight
					} );

					dom.add( contentNode, 'div', { 'class': 'evo-view-overlay' } );

					iframeDoc = iframe.contentWindow.document;

					iframeDoc.open();

					iframeDoc.write(
						'<!DOCTYPE html>' +
						'<html>' +
							'<head>' +
								'<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />' +
								head +
								styles +
								'<style>' +
									'html {' +
										'background: transparent;' +
										'padding: 0;' +
										'margin: 0;' +
									'}' +
									'body#evo-view-iframe-sandbox {' +
										'background: transparent;' +
										'padding: 1px 0 !important;' +
										'margin: -1px 0 0 !important;' +
									'}' +
									'body#evo-view-iframe-sandbox:before,' +
									'body#evo-view-iframe-sandbox:after {' +
										'display: none;' +
										'content: "";' +
									'}' +
								'</style>' +
							'</head>' +
							'<body id="evo-view-iframe-sandbox" class="' + bodyClasses + '">' +
								body +
							'</body>' +
						'</html>'
					);

					iframeDoc.close();

					function resize() {
						var $iframe;

						if ( block ) {
							return;
						}

						// Make sure the iframe still exists.
						if ( iframe.contentWindow ) {
							$iframe = $( iframe );
							self.iframeHeight = $( iframeDoc.body ).height();

							if ( $iframe.height() !== self.iframeHeight ) {
								$iframe.height( self.iframeHeight );
								editor.nodeChanged();
							}
						}
					}

					if ( self.iframeHeight ) {
						block = true;

						setTimeout( function() {
							block = false;
							resize();
						}, 3000 );
					}

					$( iframe.contentWindow ).on( 'load', resize );

					if ( MutationObserver ) {
						observer = new MutationObserver( _.debounce( resize, 100 ) );

						observer.observe( iframeDoc.body, {
							attributes: true,
							childList: true,
							subtree: true
						} );

						$( node ).one( 'evo-view-unbind', function() {
							observer.disconnect();
						} );
					} else {
						for ( i = 1; i < 6; i++ ) {
							setTimeout( resize, i * 700 );
						}
					}

					function classChange() {
						iframeDoc.body.className = editor.getBody().className;
					}

					editor.on( 'evo-body-class-change', classChange );

					$( node ).one( 'evo-view-unbind', function() {
						editor.off( 'evo-body-class-change', classChange );
					} );

					callback && callback.call( self, editor, node, contentNode );
				}, 50 );
			}, rendered );
		},

		/**
		 * Sets a loader for all view nodes tied to this view instance.
		 */
		setLoader: function() {
			this.setContent(
				'<div class="loading-placeholder">' +
					'<div class="dashicons dashicons-admin-media"></div>' +
					'<div class="evo-view-loading"><ins></ins></div>' +
				'</div>'
			);
		},

		/**
		 * Sets an error for all view nodes tied to this view instance.
		 *
		 * @param {String} message  The error message to set.
		 * @param {String} dashicon A dashicon ID. Optional. {@link https://developer.wordpress.org/resource/dashicons/}
		 */
		setError: function( message, dashicon ) {
			this.setContent(
				'<div class="evo-view-error">' +
					'<div class="dashicons dashicons-' + ( dashicon || 'no' ) + '"></div>' +
					'<p>' + message + '</p>' +
				'</div>'
			);
		},

		/**
		 * Tries to find a text match in a given string.
		 *
		 * @param {String} content The string to scan.
		 *
		 * @return {Object}
		 */
		match: function( content ) {

			var match = shortcode.next( this.type, content );
			if ( match ) {
				return {
					index: match.index,
					content: match.content,
					options: {
						shortcode: match.shortcode
					}
				};
			}
		},

		/**
		 * Update the text of a given view node.
		 *
		 * @param {String}         text   The new text.
		 * @param {tinymce.Editor} editor The TinyMCE editor instance the view node is in.
		 * @param {HTMLElement}    node   The view node to update.
		 * @param {Boolean}        force  Recreate the instance. Optional.
		 */
		update: function( text, editor, node, force ) {
			$.each( views, function( key, view ) {
					var match = view.prototype.match( text );

					if( match ) {
						$( node ).data( 'rendered', false );
						editor.dom.setAttrib( node, 'data-evo-view-text', encodeURIComponent( text ) );
						views.createInstance( type, text, match.options, force ).render();
						editor.focus();

						return false;
					}
				} );
		},

		/**
		 * Remove a given view node from the DOM.
		 *
		 * @param {tinymce.Editor} editor The TinyMCE editor instance the view node is in.
		 * @param {HTMLElement}    node   The view node to remove.
		 */
		remove: function( editor, node ) {
			this.unbindNode.call( this, editor, node, $( node ).find( '.evo-view-content' ).get( 0 ) );
			$( node ).trigger( 'evo-view-unbind' );
			editor.dom.remove( node );
			editor.focus();
		}
	} );

//} )( window, window.wp, window.wp.shortcode, window.jQuery );
} )( window, window.evo, window.evo.shortcode, window.jQuery );

/*
 * The b2evolution core TinyMCE views.
 * Views for the shortags
 */
( function( window, views, $ ) {
	var base, gallery, av, embed, image,
		schema, parser, serializer;

	function verifyHTML( string ) {
		var settings = {};

		if ( ! window.tinymce ) {
			return string.replace( /<[^>]+>/g, '' );
		}

		if ( ! string || ( string.indexOf( '<' ) === -1 && string.indexOf( '>' ) === -1 ) ) {
			return string;
		}

		schema = schema || new window.tinymce.html.Schema( settings );
		parser = parser || new window.tinymce.html.DomParser( settings, schema );
		serializer = serializer || new window.tinymce.html.Serializer( settings, schema );

		return serializer.serialize( parser.parse( string, { forced_root_block: false } ) );
	}

	base = {
		loader: true,
	};

	image = $.extend( {}, base, {
		initialize: function() {
			var self = this;
			var	params = [];
			params.push( 'tags[]=' + encodeURI( this.text ) );
			//params.push( 'image_size=fit-256x256' );

			if( params.length )
			{
				params = params.join( '&' );
			}
			this.getEditors( function( editor ) {
				tinymce.util.XHR.send({
					url: editor.getParam( 'anon_async_url' ) + '?action=render_inlines&p=' + editor.getParam( 'postID' ),
					content_type : 'application/x-www-form-urlencoded',
					data: params,
					success: function( data ) {
						var returnedTags = tinymce.util.JSON.parse( data );
						var wrapper = editor.dom.create( 'div' );
						var df = editor.dom.createFragment( returnedTags[self.text] );
						wrapper.appendChild( df );
						self.render( wrapper.innerHTML );
					}
				});
			});
		}
	});

	views.register( 'image', image );

	thumbnail = $.extend( {}, image );
	views.register( 'thumbnail', thumbnail );

	inline = $.extend( {}, image, {
		markerText: '<span class="evo-view-wrap" data-evo-view-text="%encodedText%" data-evo-view-type="%viewType%">' +
				'<p class="evo-view-selection-before">\u00a0</p>' +
				'<span class="evo-view-body" contenteditable="false">' +
					'<span class="evo-view-content evo-view-type-%viewType%"></span>' +
				'</span>' +
				'<p class="evo-view-selection-after">\u00a0</p>' +
			'</span>',
	} );
	views.register( 'inline', inline );

} )( window, window.evo.views, window.jQuery );