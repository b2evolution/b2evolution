tinymce.PluginManager.add( 'b2evo_shorttags', function( editor ) {

	var win;
	var renderedTags = [];
	var selected, selectedType;
	var forRemoval = false;

	// Keys allowed when a renderedTag is selected
	var allowedKeys = [];
	allowedKeys.push( 16, 17, 18, 19 ); // Ctrl, Alt, Shift, Pause/Break
	allowedKeys.push( 20, 27, 45 ); // Capslock, Esc, Insert
	allowedKeys.push( 33, 34, 35, 36 ); // PgUp, PgDn, End, Home
	allowedKeys.push( 37, 38, 39, 40 ); // Arrow keys
	allowedKeys.push( 91, 92, 93 ); // Left and right Windows Keys, select key
	allowedKeys.push( 112, 113, 114, 115, 116, 117, 118, 119 ,120, 121, 122, 123 ); // function keys
	allowedKeys.push( 144, 145 ); // NumLock, ScrollLock

	var self = this;
	self.selected = function() {
		return selected;
	};


	/**
	 * Marks the selected ( renderedTag ) node as active
	 */
	function select( node )	{
		var dom = editor.dom;

		if ( node !== selected ) {
			// Make sure that the editor is focused.
			// It is possible that the editor is not focused when the mouse event fires
			// without focus, the selection will not work properly.
			editor.getBody().focus();

			deselect();
			selected = node;
			selectedType = node.getAttribute( 'data-evo-type' );

			// Do not allow cut and paste operation within the selected node
			dom.bind( selected, 'paste cut', _stop );
			dom.addClass( selected, 'evo_selected' );

			// Necessary to prevent manipulating the selection/focus
			dom.bind( selected, 'beforedeactivate focusin focusout', _stop );

			// Set cursor position
			if( dom.is( selected, 'img' ) ) {
				editor.selection.select( selected );
			} else {
				var img = dom.select( 'img:first', selected );
				if( img.length ) {
					editor.selection.select( img[0] );
				} else {
					editor.selection.setCursorLocation( selected );
				}
			}

			editor.nodeChanged();
		}
	}


	/**
	 * Deselects the current active node
	 */
	function deselect()	{
		var dom = editor.dom;

		if( selected ) {
			dom.unbind( selected, 'paste cut beforedeactive focusin focusout' );
			dom.removeClass( selected, 'evo_selected' );
			selected = null;
			selectedType = null;

			editor.selection.collapse();
		}
	}


	/**
	 * Removes the specified node
	 */
	function remove( node )	{
		if( selected == node ) {
			deselect();
		}
		editor.undoManager.transact( function() {
			editor.dom.remove( node );
		});
	}


	/**
	 * Add [image:] button
	 */
	editor.addButton( 'evo_image', {
		text: '[image:]',
		icon: false,
		tooltip: 'Edit image',
		onclick: function() {
			if( ! editor.getParam( 'postID' ) )	{
				alert( 'Please save post first to start uploading files.' );
				return false;
			}

			if( selected && ( selectedType == 'image' ) ) {
				var selectedData = getRenderedNodeData( selected );

				win = editor.windowManager.open( {
					title: 'Edit Image',
					body: [
						{
							type: 'textbox',
							name: 'caption',
							label: 'Caption',
							minWidth: 500,
							value: selectedData.caption && !selectedData.disableCaption ? selectedData.caption : null,
							disabled: selectedData.disableCaption
						},
						{
							type: 'checkbox',
							name: 'disableCaption',
							label: 'Disable caption',
							checked: selectedData.disableCaption,
						},
						{
							type: 'textbox',
							name: 'extraClass',
							label: 'Additional class:',
							minWidth: 500,
							value: selectedData.extraClass ? selectedData.extraClass : null
						},
					],
					buttons: [
						{
							text: 'Update',
							onclick: function() {
								var captionCtrl = win.find('#caption')[0];
								var disableCaptionCtrl = win.find('#disableCaption')[0];
								var classCtrl = win.find('#extraClass')[0];
								var tag = '[image:' + selectedData.linkId;

								if( disableCaptionCtrl.checked() || captionCtrl.value() == '-' ) {
									tag += ':-';
								} else {
									tag += ':' + captionCtrl.value();
								}
								tag += classCtrl.value() ? ':' + classCtrl.value() : '';
								tag += ']';

								// Get rendered tag and output directly
								var renderedTag = getRenderedTag( tag );

								if( renderedTag === false )
								{
									getRenderedTags( [ tag ], function( rTags ) {
											for( var i = 0; i < renderedTags.length; i++ ) {
												if( renderedTags[i].shortTag == tag )	{
													renderedTag = renderedTags[i];
													break;
												}
											}

											if( renderedTag )	{
												if( selected ) {
													editor.dom.replace( renderedTag.node, selected, false );
												} else {
													editor.insertContent( renderedTag.html );
												}

												editor.windowManager.close();
											}
										} );
								}
								else
								{
									if( selected ) {
										editor.dom.replace( renderedTag.node, selected, false );
									}	else {
										editor.insertContent( renderedTag.html );
									}

									editor.windowManager.close();
								}
							}
						},
						{
							text: 'Cancel',
							onclick: 'close'
						},
					]
				} );

				var disableCaptionCtrl = win.find( '#disableCaption' )[0];
				var captionCtrl = win.find( '#caption' )[0];

				disableCaptionCtrl.on( 'click', function( event ) {
					captionCtrl.disabled( disableCaptionCtrl.checked() );
				} );

			} else {
				var root, path, fm_highlight;
				openModalWindow( '<span class="loader_img loader_user_report absolute_center" title="Loading..."></span>',
						'80%', '', true, 'Select image to insert', '', true );
				jQuery.ajax(
				{
					type: 'POST',
					url: editor.getParam( 'modal_url' ),
					success: function(result)
					{
						openModalWindow( result, '90%', '80%', true, 'Select image', '' );
					}
				} );
				return false;
			}
		},
		onPostRender: function()
		{
			var imageButton = this;
			editor.on( 'NodeChange', function( event ) {
				imageButton.active( selectedType == 'image' );
			});
		}
	});


	/**
	 * Add [thumbnail:] button
	 */
	editor.addButton( 'evo_thumbnail', {
		text: '[thumbnail:]',
		icon: false,
		tooltip: 'Edit thumbnail',
		onclick: function() {
			if( ! editor.getParam( 'postID' ) )	{
				alert( 'Please save post first to start uploading files.' );
				return false;
			}

			if( selected && ( selectedType == 'thumbnail' ) ) {
				var selectedData = getRenderedNodeData( selected );

				win = editor.windowManager.open( {
					title: 'Edit Thumbnail',
					body: [
						{
							type: 'listbox',
							name: 'alignment',
							label: 'Alignment:',
							values: [
								{ text: 'left', value: 'left' },
								{ text: 'right', value: 'right' }
							],
							value: selectedData ? selectedData.alignment : 'left'
						},
						{
							type: 'listbox',
							name: 'size',
							label: 'Size',
							values: [
								{ text: 'small', value: 'small' },
								{ text: 'medium',	value: 'medium' },
								{ text: 'large', value: 'large' }
							],
							value: selectedData ? selectedData.size : 'medium'
						},
						{
							type: 'textbox',
							name: 'extraClass',
							label: 'Additional class:',
							minWidth: 500,
							value: selectedData ? selectedData.extraClass : null
						}
					],
					buttons: [
						{
							text: 'Update',
							onclick: function() {
								var sizeCtrl = win.find('#size')[0];
								var alignCtrl = win.find('#alignment')[0];
								var classCtrl = win.find('#extraClass')[0];
								var tag = '[thumbnail:' + selectedData.linkId;

								tag += ':' + sizeCtrl.value();
								tag += ':' + alignCtrl.value();
								tag += classCtrl.value() ? ':' + classCtrl.value() : '';
								tag += ']';

								// Get rendered tag and output directly
								var renderedTag = getRenderedTag( tag );

								if( renderedTag === false )	{
									getRenderedTags( [ tag ], function( rTags ) {
											for( var i = 0; i < renderedTags.length; i++ ) {
												if( renderedTags[i].shortTag == tag )	{
													renderedTag = renderedTags[i];
													break;
												}
											}

											if( renderedTag )	{
												if( selected ) {
													editor.dom.replace( renderedTag.node, selected, false );
												}	else {
													editor.insertContent( renderedTag.html );
												}

												editor.windowManager.close();
											}
										} );
								}	else {
									if( selected ) {
										editor.dom.replace( renderedTag.node, selected, false );
									}	else {
										editor.insertContent( renderedTag.html );
									}

									editor.windowManager.close();
								}
							}
						},
						{
							text: 'Cancel',
							onclick: 'close'
						},
					]
				});
			} else {
				openModalWindow( '<span class="loader_img loader_user_report absolute_center" title="Loading..."></span>',
						'80%', '', true, 'Select image to insert', '', true );
				jQuery.ajax(
				{
					type: 'POST',
					url: editor.getParam( 'modal_url' ),
					success: function(result)
					{
						openModalWindow( result, '90%', '80%', true, 'Select image', '' );
					}
				} );
				return false;
				//alert( 'To insert a thumbnail, attach a picture to the post in the Attachments panel below; then click the (+) icon in the Attachments panel.' );
			}
		},
		onPostRender: function()
		{
			var thumbnailButton = this;
			editor.on( 'NodeChange', function( event ) {
				thumbnailButton.active( selectedType == 'thumbnail' );
			});
		}
	});

	/**
	 * Add [inline:] button
	 */
	editor.addButton( 'evo_inline', {
		text: '[inline:]',
		icon: false,
		tooltip: 'Edit inline',
		onclick: function() {
			if( ! editor.getParam( 'postID' ) )	{
				alert( 'Please save post first to start uploading files.' );
				return false;
			}

			if( selected && ( selectedType == 'inline' ) ) {
				var selectedData = getRenderedNodeData( selected );

				win = editor.windowManager.open({
					title: 'Edit Inline',
					body: [
						{
							type: 'textbox',
							name: 'extraClass',
							label: 'Additional class:',
							minWidth: 500,
							value: selectedData ? selectedData.extraClass : null
						}
					],
					buttons: [
						{
							text: 'Update',
							onclick: function() {
								var classCtrl = win.find('#extraClass')[0];
								var tag = '[inline:' + selectedData.linkId;

								tag += classCtrl.value() ? ':' + classCtrl.value() : '';
								tag += ']';

								// Get rendered tag and output directly
								var renderedTag = getRenderedTag( tag );

								if( renderedTag === false )	{
									getRenderedTags( [ tag ], function( rTags ) {
											for( var i = 0; i < renderedTags.length; i++ ) {
												if( renderedTags[i].shortTag == tag )	{
													renderedTag = renderedTags[i];
													break;
												}
											}

											if( renderedTag )	{
												if( selected ) {
													editor.dom.replace( renderedTag.node, selected, false );
												}	else {
													editor.insertContent( renderedTag.html );
												}

												editor.windowManager.close();
											}
										} );
								}	else {
									if( selected ) {
										editor.dom.replace( renderedTag.node, selected, false );
									}	else {
										editor.insertContent( renderedTag.html );
									}

									editor.windowManager.close();
								}
							}
						},
						{
							text: 'Cancel',
							onclick: 'close'
						},
					]
				});
			} else {
				openModalWindow( '<span class="loader_img loader_user_report absolute_center" title="Loading..."></span>',
						'80%', '', true, 'Select image to insert', '', true );
				jQuery.ajax(
				{
					type: 'POST',
					url: editor.getParam( 'modal_url' ),
					success: function(result)
					{
						openModalWindow( result, '90%', '80%', true, 'Select image', '' );
					}
				} );
				return false;
				//alert( 'To insert an inline image, attach a picture to the post in the Attachments panel below; then click the (+) icon in the Attachments panel.' );
			}
		},
		onPostRender: function()
		{
			var inlineButton = this;
			editor.on( 'NodeChange', function( event ) {
				inlineButton.active( selectedType == 'inline' );
			});
		}
	});


	function _stop( event ) {
		event.stopPropagation();
		return false;
	}


	/**
	 * Fetches rendering data including HTML fragments of submitted inline tags
	 *
	 * @param array List of inline tags to render
	 * @param function Callback function after fetching the HTML fragments
	 */
	function getRenderedTags( inlineTags, callback  ) {
		var tagsParam = [];
		for( var i = 0; i < inlineTags.length; i++ ) {
			var renderedTag = getRenderedTag( inlineTags[i] );
			if( renderedTag === false ) {
				renderedTags.push({
					shortTag: inlineTags[i],
					html: null,
					node: null,
					type: null,
					rendered: false
				});
			} else {
				continue;
			}
			tagsParam.push( 'tags[]=' + encodeURI( inlineTags[i] ) );
		}

		if( tagsParam.length )
		{
			tagsParam = tagsParam.join( '&' );

			tinymce.util.XHR.send({
				url: editor.getParam( 'anon_async_url' ) + '?action=render_inlines&p=' + editor.getParam( 'postID' ),
				content_type : 'application/x-www-form-urlencoded',
				data: tagsParam,
				success: function( data ) {
					var returnedTags = tinymce.util.JSON.parse( data );

					for( tag in returnedTags ) {
						var wrapper = editor.dom.create( 'div' );
						var df = editor.dom.createFragment( returnedTags[tag] );
						var tagData = parseTag( tag );

						editor.dom.setAttrib( df.childNodes[0], 'data-evo-tag', window.encodeURIComponent( tag ) );
						editor.dom.setAttrib( df.childNodes[0], 'data-evo-type', tagData.type );
						wrapper.appendChild( df );

						var renderedTag = getRenderedTag( tag );
						if( renderedTag === false )
						{
							renderedTags.push({
								shortTag: tag,
								html: wrapper.innerHTML,
								node: wrapper.childNodes[0],
								type: tagdata.type,
								rendered: true
							});
						}
						else if( renderedTag.rendered === false )
						{
							renderedTag.html = wrapper.innerHTML;
							renderedTag.node = wrapper.childNodes[0];
							renderedTag.rendered = true;
						}
					}
					callback( returnedTags );
				}
			});
		}
		else
		{
			callback();
		}
	}


	/**
	 * Gets the relevant rendering of an inline tag
	 *
	 * @param string Inline tag
	 * @return mixed Array of rendering data, False otherwise
	 */
	function getRenderedTag( tag ) {
		var n = renderedTags.length;
		for( var i = 0; i < n; i++ ) {
			if( renderedTags[i].shortTag == tag ) {
				return renderedTags[i];
			}
		}

		return false;
	}


	/**
	 * Render the inline tags
	 *
	 * @param string Content of the post
	 */
	function renderInlineTags( content ) {
		var re = /(<span.*?data-evo-tag.*?>)?(\[(image|thumbnail|inline|video|audio):(\d+):?([^\[\]]*)\])(<\/span>)?/ig;
		var m;
		var matches = [];
		var inlineTags = [];

		while ( ( m = re.exec( content ) ) !== null ) {
			if ( m.index === re.lastIndex ) {
					re.lastIndex++;
			}

			matches.push( {
				shortTag: m[2],
				inlineType: m[3],
				linkId: parseInt( m[4] ),
				other: m[5],
				openTag: m[1],
				closeTag: m[6]
			});
			inlineTags.push( m[2] );
		}

		getRenderedTags( inlineTags, function( returnedTags ) {
			if( returnedTags ) {
				update();
			}
		} );

		var n = matches.length;
		for( var i = 0; i < n; i++ ) {
			if( matches[i] && !matches[i].openTag && !matches[i].closeTag )	{
				var tag = matches[i].shortTag;
				var renderedTag = getRenderedTag( tag );

				if( renderedTag !== false && renderedTag.rendered !== false )	{
					switch( matches[i].inlineType ) {
						case 'image':
						case 'thumbnail':
						case 'inline':
							content = content.replace( tag, renderedTag.html );
							break;

						default:
							content = content.replace( tag, '<span style="color: green;" data-evo-tag>' + tag + '</span>' );
					}
				}
			}
		}

		return content;
	}


	/**
	 * Restore rendering of inline tags to the original inline tag string
	 *
	 * @param string Content to cleanup
	 * @returen string Cleaned up content
	 */
	function restoreShortTags( content ) {
		// Cleanup errors
		content = content.replace( /(<span [^>]+data-evo-error[^>]+>(.*?)<\/span>)/ig,
			function( match, c, i )	{
				return i;
			});

		// Cleanup other shorttags
		var re = /(<span.*?data-evo-tag.*?>)?(\[(image|file|inline|video|audio|thumbnail):(\d+):?([^\[\]]*)\])(<\/span>)?/ig;
		while ( ( m = re.exec( content ) ) !== null ) {
			if ( m.index === re.lastIndex ) {
					re.lastIndex++;
			}
			if( m[1] && m[6] ) {
				content = content.replace( m[0], m[2] );
			}
		}

		// Cleanup rendered nodes
		var df = editor.dom.createFragment( content );
		var renderedNode;
		while( renderedNode = df.querySelector( '[data-evo-tag]' ) ) {
			var tag = window.decodeURIComponent( renderedNode.getAttributeNode( 'data-evo-tag' ).value );
			renderedNode.parentNode.replaceChild( document.createTextNode( tag ), renderedNode );
		}

		var tmpWrapper = editor.dom.create( 'div' );
		tmpWrapper.appendChild( df );

		return tmpWrapper.innerHTML;
	}


	/**
	 * Renders the inline tag and updates the post content
	 */
	function update() {
		var content = editor.getContent();
		editor.setContent( renderInlineTags( content ) );
	}


	/**
	 * Determines if a given node is part of a rendered node
	 *
	 * @param element Node to be determined
	 * @param string Element attribute used to identify root of rendered node
	 * @return mixed Root element of rendered node, False if give node is not part of a rendered node
	 */
	function getRenderedNode( node, nodeId ) {
		if( !nodeId ) nodeId = 'data-evo-tag';

		return editor.dom.getParent( node, '[' + nodeId + ']' );
	}


	/**
	 * Retrieves tag from rendered node
	 *
	 * @param Element rendered node
	 * @return String shorttag
	 */
	function getRenderedNodeData( node )
	{
		var tag = node.getAttribute( 'data-evo-tag' );

		if( tag )	{
			return parseTag( tag );
		} else {
			return false;
		}
	}


	/**
	 * Parses data from shorttag
	 *
	 * @string Shorttag
	 * @return Array tag information
	 */
	function parseTag( tag )
	{
		if( tag ) {
			tag = window.decodeURIComponent( tag );
			var re = /\[(image|file|inline|video|audio|thumbnail):(\d+):?([^\[\]]*)\]/i;
			var m = re.exec( tag );
			var data = {
					tag: m[0],
					type: m[1],
					linkId: parseInt( m[2] )
				};

			switch( data.type ) {
				case 'image':
					var options = m[3];
					if( options ) {
						options = options.split( ':' );

						if( options[0] ) {
							if( options[0] == '-' ) {
								data['caption'] = null;
								data['disableCaption'] = true;
							} else {
								data['caption'] = options[0];
								data['disableCaption'] = false;
 							}
						} else {
							data['caption'] = null;
							data['disableCaption'] = false;
						}

						if( options[1] ) {
							data['extraClass'] = options[1];
						} else {
							data['extraClass'] = null;
						}
					}
					else
					{
						data['caption'] = null;
						data['extraClass'] = null;
					}
					break;

				case 'thumbnail':
					var options = m[3];
					if( options ) {
						options = options.split( ':' );

						if( options[0] && ['small', 'medium', 'large'].indexOf( options[0] ) != -1 ) {
							data['size'] = options[0];
						} else {
							data['size'] = 'medium';
						}

						if( options[1] && ['left', 'right'].indexOf( options[1] ) != -1 ) {
							data['alignment'] = options[1];
						} else {
							data['alignment'] = 'left';
						}

						if( options[2] ) {
							data['extraClass'] = options[2];
						} else {
							data['extraClass'] = null;
						}

					} else {
						data['size'] = 'medium';
						data['alignment'] = 'left';
						data['extraClass'] = null;
					}
					break;

				case 'inline':
					var options = m[3];
					if( options ) {
						data['extraClass'] = options;
					} else {
						data['extraClass'] = null;
					}
					break;

				default:
					data['options'] = m[3];
			}

			return data;
		} else {
			return false;
		}
	};


	// Render shorttags into rendered nodes
	editor.on( 'BeforeSetContent', function( event ) {
		event.content = renderInlineTags( event.content );
	});


	// Restore rendered nodes into shorttags again
	editor.on( 'PostProcess', function( event )	{
		if( event.get )	{
			event.content = restoreShortTags( event.content );
		}
	});


	// Update content and render inline tags when attachments are reloaded
	editor.on( 'attachmentsLoaded', function( event ) {
		update();
	});


	// Check if selected node is part of rendered node

	editor.on( 'mousedown mouseup click touchend', function( event ) {
		var renderedNode = getRenderedNode( event.target );

		if( renderedNode ) {
			event.stopImmediatePropagation();
			event.preventDefault();
			select( renderedNode );
		}	else {
			deselect();
		}
	}, true );


	// Prevent editing if current selection part of rendered node
	editor.on( 'keydown', function( event ) {
		var dom = editor.dom,
				selection = editor.selection,
				node, renderedNode;

		node = selection.getNode();
		renderedNode = getRenderedNode( node );

		if( selected ) {
			if( event.which == 8 || event.which == 46 ) { // Backspace, Delete
				forRemoval = selected;
				event.preventDefault();
				return false;
			}	else if( allowedKeys.indexOf( event.which ) == -1 )	{
				event.preventDefault();
				return false;
			}

			switch( event.which )	{
				case 37: // Left
				case 38: // Up
					// No need to do anything as cursor position is already at the start of the rendered node
					if( renderedNode.previousSibling ) {
						// Move the cursor to the beginning of the selected node first
						selection.setCursorLocation( selected );
					}
					break;

				case 39: // Right
				case 40: // Down
					if( renderedNode.nextSibling ) {
						selection.setCursorLocation( renderedNode.nextSibling );
						event.preventDefault();
						return false;
					}
					break;
			}
		}	else {
			if( ! selection.isCollapsed() ) {
				range = selection.getRng();

				if( renderedNode = getRenderedNode( range.endContainer ) ) {
					clonedRange = range.cloneRange();
					selection.select( renderedNode.previousSibling, true );
					selection.collapse();
					tempRange = selection.getRng();
					clonedRange.setEnd( tempRange.endContainer, tempRange.endOffset );
					selection.setRng( clonedRange );
				} else if( renderedNode = getRenderedNode( range.startContainer ) ) {
					clonedRange = range.cloneRange();
					clonedRange.setStart( renderedNode.nextSibling, 0 );
					selection.setRng( clonedRange );
				}
			}
		}
	}, true );


	editor.on( 'keyup', function( event ) {
		var dom = editor.dom,
				selection = editor.selection,
				node, renderedNode;

		node = selection.getNode();
		renderedNode = getRenderedNode( node );

		if( renderedNode ) {
			select( renderedNode );
		}	else	{
			deselect();
		}

		if( forRemoval ) {
			remove( forRemoval );
			forRemoval = false;
		}
	} );


	editor.on( 'ResolveName', function( event ) {
		if ( editor.dom.getAttrib( event.target, 'data-evo-tag' ) ) {
			var tagType = editor.dom.getAttrib( event.target, 'data-evo-type' );
			if( tagType ) {
				event.name = '[' + tagType + ':]';
			} else {
				event.name = 'shorttag';
			}
			event.stopPropagation();
		} else if ( getRenderedNode( event.target ) ) {
			event.preventDefault();
			event.stopPropagation();
		}
	});


	// Set dragged element data
	editor.on( 'dragstart', function( event ) {
		var renderedNode = getRenderedNode( event.target );

		if( renderedNode )	{
			select( renderedNode );
			var tag = window.decodeURIComponent( renderedNode.getAttribute( 'data-evo-tag' ) );
			event.dataTransfer.setData( 'application/x-moz-node', event.target );
			event.dataTransfer.setData( 'text/plain', tag );
			event.dataTransfer.effectAllowed = 'move';
		}	else {
			deselect();
		}
	});


	// Drop handler
	editor.on( 'drop', function( event ) {
		var target = event.target,
			tag = event.dataTransfer.getData( 'text/plain' );

		var renderedNode = getRenderedNode( target );
		if( renderedNode ) {
			target = renderedNode;
		}

		// Dragged element dropped on body itself and we are unable to determine
		// where we can insert the element so let's cancel the drop
		if( target.tagName == 'BODY' )
		{
			event.preventDefault();
			return false;
		}

		// An element belonging to a rendered node was dragged and dropped
		if( renderedNode && tag && selected ) {
			event.preventDefault();
			target.insertAdjacentHTML( 'beforebegin', tag );
			editor.dom.remove( selected );
			update();
			return false;
		}
	});


	editor.on( 'init', function() {
		var scrolled = false,
			selection = editor.selection;

		// When a renderedNode is selected, ensure content that is being pasted
		// or inserted is added to a text node (instead of the renderedNode).
		editor.on( 'BeforeSetContent', function() {
			var walker, target,
				renderedNode = getRenderedNode( selection.getNode() );

			// If the selection is not within a renderedNode, bail.
			if ( !renderedNode ) {
				return;
			}

			if ( !renderedNode.nextSibling || getRenderedNode( renderedNode.nextSibling ) ) {
				// If there are no additional nodes or the next node is a
				// renderedNode, create a text node after the current renderedNode.
				target = editor.getDoc().createTextNode('');
				editor.dom.insertAfter( target, renderedNode );
			} else {
				// Otherwise, find the next text node.
				walker = new tinymce.dom.TreeWalker( renderedNode.nextSibling, renderedNode.nextSibling );
				target = walker.next();
			}

			// Select the `target` text node.
			selection.select( target );
			selection.collapse( true );
		});
	});

});