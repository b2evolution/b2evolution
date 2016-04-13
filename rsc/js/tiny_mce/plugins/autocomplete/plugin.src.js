/**
 * editor_plugin_src.js
 * 
 * Copyright 2012, Mijura Pty. Ltd. Released under The MIT License.
 * 
 * About:
 * AutoComplete for TinyMCE provides inline autocomplete in a style similar to 
 * Twitter or Facebook.  The text you type in tinyMCE is checked 
 * against a list of specified options; if there is a match then 
 * you will see them appear in a list underneath the caret.  
 * 
 * Configuration:
 * Parameters that we can use in tinyMCE config:
 * 1\ autocomplete_delimiters - A CSV list of delimiters (ASCII codes) on which 
 * 		to split text entered into tinyMCE. In most cases you will want to 
 * 		split text by spaces, in which case you would specify '160,32'. 32 is 
 * 		a normal space and 160 is &nbsp; (which is commonly used by tinyMCE). 
 * 		Whichever delimiter you specify first will be inserted after you 
 * 		select an option.  The default is '160,32' for spaces. 
 * 2\ autocomplete_options - A CSV list of autocomplete options.  For 
 * 		example, 'john,jane,jwilliam'.    
 * 3\ autocomplete_trigger -  You can specify a trigger character that must 
 * 		be type immediately before searching for options.  The default 
 * 		trigger is '@'  
 * 4\ autocomplete_end_option - Any text that you want to be added after the 
 * 		option.  The caret will be placed between the option and this ending 
 * 		text.  For example, you could specify 'end', in which case selecting 
 * 		an autocomplete option would insert: '@jane  @end' with the caret 
 * 		placed in between (and including the trigger before the end option).
 * 5\ autocomplete_min_length - The minimum number of characters a word needs to have
 *              before the autocomplete activates.
 *              The default is 1.
 * 6\ autocomplete_min_length_ajax - The minimum number of characters a word needs to have
 *              before the autocomplete activates for AJAX request.
 *              The default is 4.
 * 7\ autocomplete_on_select - A function to call after an option is selected.
 *              The default is false.
 * 8\ autocomplete_on_match - A function to call when text entered match only one option.
 *              The default is false.
 *
 * Support: 
 * You are welcome to use this plugin at your own risk.  It is currently 
 * being maintained on GitHub where you can submit issues / feature requests. 
 *
 * Modified at 2014-02-06 to be compatible with TinyMCE 4 version
 */

(function () {
	var autocomplete_data = {};
	var DOWN_ARROW_KEY = 40;
	var UP_ARROW_KEY = 38;
	var ESC_KEY = 27;
	var ENTER_KEY = 13;
	var END_WORD_KEYS = [32, 59, 186, 188, 190];

	function parseOptions(param) {
		return param.options == null && typeof param != "boolean" ? param.split(",") : param.options;
	}

	tinymce.create('tinymce.plugins.AutoCompletePlugin', {

		setOptions: function (param) {
			autocomplete_data.options = parseOptions(param);
		},

		getOptions: function () {
			return autocomplete_data.options;
		},

		init: function (ed, url) {

			autocomplete_data = {
				list: createOptionList(),
				visible: false,
				cancelEnter: false,
				delimiter: ed.getParam('autocomplete_delimiters', '160,32').split(","),
				options: parseOptions(ed.getParam('autocomplete_options', '')),
				optionsUrl: parseOptions(ed.getParam('autocomplete_options_url', false)),
				trigger: ed.getParam('autocomplete_trigger', '@'),
				enclosing: ed.getParam('autocomplete_end_option', ''),
				minLength: ed.getParam('autocomplete_min_length', '1'),
				minLengthAjax: ed.getParam('autocomplete_min_length_ajax', '4'),
				onSelect: ed.getParam('autocomplete_on_select', false),
				onMatch: ed.getParam('autocomplete_on_match', false)
			};

			var t = this;

			// Setup plugin event
			if (autocomplete_data.onSelect) {
				t.onSelect = new tinymce.util.Dispatcher(t);
				t.onSelect.add(function (ed, selected) {
					ed.execCallback('autocomplete_on_select', ed, selected);
				});
			}
			if (autocomplete_data.onMatch) {
				t.onMatch = new tinymce.util.Dispatcher(t);
				t.onMatch.add(function (ed, match) {
					ed.execCallback('autocomplete_on_match', ed, match);
				});
			}

			/**
			 * Search for autocomplete options after text is entered and display the 
			 * option list if any matches are found. 
			 */
			function keyUpEvent(e) {
				if ((!autocomplete_data.visible && e.keyCode != ESC_KEY && e.keyCode != ENTER_KEY) || (e.keyCode != DOWN_ARROW_KEY && e.keyCode != UP_ARROW_KEY && e.keyCode != ENTER_KEY && e.keyCode != ESC_KEY)) {
					var currentWord = getCurrentWord(ed);
					currentWord = currentWord.replace(autocomplete_data.trigger, "");
					var matches = matchingOptions(currentWord);
					if (currentWord.length > 0) {
						populateList(currentWord);
					}
					if (currentWord.length == 0 || matches.length == 0) {
						hideOptionList();
					}
				}
			}


			/**
			 * Populates autocomplete list with matched words.
			 *
			 */
			function populateList(currentWord) {
				var wordLessTrigger = currentWord.replace(autocomplete_data.trigger, "");

				if( currentWord.length >= autocomplete_data.minLength &&
				    ( currentWord.length < autocomplete_data.minLengthAjax || ! autocomplete_data.optionsUrl ) )
				{ // Get matches from static array
					matches = matchingOptions(wordLessTrigger);

					if (matches.length > 0) {
						displayOptionList(matches, wordLessTrigger, ed);
						highlightNextOption();
					}
				}
				else
				{ // Get matches by AJAX
					if (wordLessTrigger.length <= 1)
						return false;

					var static_options = autocomplete_data.options;
					jQuery.ajax({
						type: "GET",
						dataType: "JSON",
						url: autocomplete_data.optionsUrl,
						cache: false,
						data: "q=" + wordLessTrigger,
						success: function (data) {
							if( data && typeof( data.users ) != 'undefined' ) {
								var options = [];
								for( var i in data.users ) {
									options.push(data.users[i].login);
								}
								autocomplete_data.options = options;

								matches = matchingOptions(wordLessTrigger);

								if (matches.length > 0) {
									displayOptionList(matches, wordLessTrigger, ed);
									highlightNextOption();
								}
								autocomplete_data.options = static_options;
							}
						}
					});
					autocomplete_data.options = static_options;
				}
			} // populateList


			/**
			 * Prevent return from adding a new line after selecting an option.  
			 */
			function keyPressEvent(e) {
				if (e.keyCode == ENTER_KEY && autocomplete_data.cancelEnter) {
					autocomplete_data.cancelEnter = false;
					return tinymce.dom.Event.cancel(e);
				}
			}

			/**
			 * Handle navigation inside the option list when it is visible.  
			 * These events should not propagate to the editor. 
			 */
			function keyDownEvent(e) {
				if (autocomplete_data.visible) {
					if (e.keyCode == DOWN_ARROW_KEY) {
						highlightNextOption();
						return tinymce.dom.Event.cancel(e);
					}
					if (e.keyCode == UP_ARROW_KEY) {
						highlightPreviousOption();
						return tinymce.dom.Event.cancel(e);
					}
					if (e.keyCode == ENTER_KEY) {
						selectOption(ed, getCurrentWord(ed));
						autocomplete_data.cancelEnter = true;
						return false; // the enter evet needs to be cancelled on keypress so 
						// it doesn't register a carriage return
					}
					if (e.keyCode == ESC_KEY) {
						hideOptionList();
						return tinymce.dom.Event.cancel(e);
					}
					// onMatch callback
					if (autocomplete_data.onMatch && END_WORD_KEYS.indexOf(e.keyCode)) {
						var word = getCurrentWord(ed);
						var matches = matchingOptions(word);
						var completeMatch = new RegExp("^" + matches[0] + "$", "i");
						if (matches.length == 1 && word.match(completeMatch)) {
							t.onMatch.dispatch(ed, matches[0]);
						}
					}
				}
			}

			function clickEvent(e) {
				hideOptionList();
			}

			/**
			 * Add all the options to the option list and display it right beneath 
			 * the caret where the user is entering text. There didn't appear to be 
			 * an easy way to retrieve the exact pixel position of the caret inside 
			 * tinyMCE so the difficult method had to suffice. 
			 */
			function displayOptionList(matches, matchedText, ed) {
				var matchesList = "";
				var highlightRegex = new RegExp("(" + matchedText + ")");


				for (var i in matches) {
					if (matches[i].key != null) {
						matchesList += "<li data-value='" + matches[i].key + "'>" + matches[i].key.replace(highlightRegex, "<mark>$1</mark>") + " " + matches[i].description + "</li>";
					}
					else if( typeof( matches[i] ) == 'string' ) {
						matchesList += "<li data-value='" + matches[i] + "'>" + matches[i].replace(highlightRegex, "<mark>$1</mark>") + "</li>";
					}
				}
				jQuery(autocomplete_data.list).html(matchesList);

				// work out the position of the caret
				var iframe_offset = jQuery( ed.getContainer() ).find( 'iframe' ).offset();
				var textareaTop = 0;
				var textareaLeft = 0;
				if (ed.selection.getRng().getClientRects().length > 0) {
					textareaTop = ed.selection.getRng().getClientRects()[0].top + ed.selection.getRng().getClientRects()[0].height;
					textareaLeft = ed.selection.getRng().getClientRects()[0].left;
				} else {
					textareaTop = parseInt(jQuery(ed.selection.getNode()).css("font-size")) * 1.3 + nodePosition.top;
					textareaLeft = nodePosition.left;
				}

				jQuery(autocomplete_data.list).css("position", "absolute");
				jQuery(autocomplete_data.list).css("top", iframe_offset.top + textareaTop );
				jQuery(autocomplete_data.list).css("left", iframe_offset.left + textareaLeft );
				jQuery(autocomplete_data.list).css("display", "block");
				autocomplete_data.visible = true;
				optionListEventHandlers(ed);
			}

			/**
			 * Allow a user to select an option by clicking with the mouse and 
			 * highlighting the options on hover. 
			 */
			function optionListEventHandlers(ed) {
				jQuery(autocomplete_data.list).find("li").hover(function () {
					jQuery(autocomplete_data.list).find("[data-selected=true]").attr("data-selected", "false").removeClass( 'active' );
					jQuery(this).attr("data-selected", "true").addClass( 'active' );
				});
				jQuery(autocomplete_data.list).find("li").click(function () {
					selectOption(ed, getCurrentWord(ed));
				});
			}

			function createOptionList() {
				var ulContainer = document.createElement("ul");
				jQuery(ulContainer).addClass("textcomplete-list");
				document.body.appendChild(ulContainer);
				return ulContainer;
			}

			function hideOptionList() {
				jQuery(autocomplete_data.list).css("display", "none");
				autocomplete_data.visible = false;
			}

			function highlightNextOption() {
				var current = jQuery(autocomplete_data.list).find("[data-selected=true]");
				if (current.size() == 0 || current.next().size() == 0) {
					jQuery(autocomplete_data.list).find("li:first-child").attr("data-selected", "true").addClass( 'active' );
				} else {
					current.next().attr("data-selected", "true").addClass( 'active' );
				}
				current.attr("data-selected", "false").removeClass( 'active' );
			}

			function highlightPreviousOption() {
				var current = jQuery(autocomplete_data.list).find("[data-selected=true]");
				if (current.size() == 0 || current.prev().size() == 0) {
					jQuery(autocomplete_data.list).find("li:last-child").attr("data-selected", "true").addClass( 'active' );
				} else {
					current.prev().attr("data-selected", "true").addClass( 'active' );
				}
				current.attr("data-selected", "false").removeClass( 'active' );
			}

			/**
			 * Select/insert the currently selected option.  The option will be inserted at the 
			 * caret position with a delimiter at the end and the option enclosing text.  If the 
			 * enclosing text has already been inserted (this would happen when you are editing 
			 * an autocompleted option), then it won't be inserted again. 
			 */
			function selectOption(ed, matchedText) {
				var current = jQuery(autocomplete_data.list).find("[data-selected=true]").attr("data-value");
				if (current == null) {
					current = jQuery(autocomplete_data.list).find("li:first-child").attr("data-value");
				}

				var content = restOfContent(ed.selection.getSel().anchorNode, "");
				var currentNode = ed.selection.getSel().anchorNode.textContent;

				// modify the range to replace overwrite the option text that has already been entered
				var range = ed.selection.getRng();
				range.setStart(range.startContainer, range.startOffset - matchedText.length);
				ed.selection.setRng(range);

				// insert the trigger, selected option and following delimiter 
				var delim = "";
				if (autocomplete_data.delimiter.length > 0) {
					delim = String.fromCharCode(autocomplete_data.delimiter[0]);
				}
				ed.selection.setContent(autocomplete_data.trigger + current.toString() + delim);

				// insert the enclosing text if it has not already been added
				if (autocomplete_data.enclosing.length > 0 && !closingTextExists(content, currentNode)) {
					var middleBookmark = ed.selection.getBookmark();
					ed.selection.setContent(delim + autocomplete_data.trigger + autocomplete_data.enclosing);
					ed.selection.moveToBookmark(middleBookmark);
				}
				hideOptionList();

				// onSelect callback
				if (autocomplete_data.onSelect) {
					t.onSelect.dispatch(ed, current);
				}
				hideOptionList();
			}

			/**
			 * Check if the enclosing string has already been placed past the current node.  
			 */
			function closingTextExists(content, currentNode) {
				var enclosed = autocomplete_data.trigger + autocomplete_data.enclosing;
				content = content.substr(currentNode.length);
				var matches = new RegExp(autocomplete_data.trigger + ".{" + autocomplete_data.enclosing.length + "}", "g").exec(content);
				if (matches != null && matches.length > 0 && matches[0] == enclosed) {
					return true;
				}
				return false;
			}

			/**
			 * Recursively find all of the content past (and including) the caret node. 
			 * This doesn't appear to be available any other way.  
			 */
			function restOfContent(anchorNode, content) {
				content += anchorNode.textContent;
				if (anchorNode.nextSibling != null) {
					return restOfContent(anchorNode.nextSibling, content);
				}
				return content;
			}

			/**
			 * Find all options whose beginning matches the currently entered text. 
			 */
			function matchingOptions(currentWord) {
				var options = autocomplete_data.options;
				var matches = [];
				for (var i in options) {
					if (options[i].key == null && (currentWord.length == 0 || beginningOfWordMatches(currentWord, options[i]))) {
						matches.push(options[i]);
					}
					else if (options[i].key != null && (currentWord.length == 0 || beginningOfWordMatches(currentWord, options[i].key))) {
						matches.push(options[i]);
					}
				}
				return matches;
			}

			function beginningOfWordMatches(beginning, option) {
				var test = new RegExp("^" + beginning, "i");
				return (typeof( option ) == 'string' ? option.match(test) : '');
			}

			/**
			 * Retrieves the 'word' as specified by the first occurrence of a
			 * delimiter prior to the caret position.
			 */
			function getCurrentWord(ed) {
				var nodeText = ed.selection.getSel().focusNode == null ? "" : ed.selection.getSel().focusNode.nodeValue;
				var positionInNode = ed.selection.getSel().focusOffset;
				if (nodeText == null || nodeText.length == 0) {
					return "";
				}
				var lastDelimiter = 0;
				for (var i = 0; i < positionInNode; i++) {
					if (autocomplete_data.delimiter.indexOf(nodeText.charCodeAt(i).toString()) != -1) {
						lastDelimiter = i + 1;
					}
				}
				var word = nodeText.substr(lastDelimiter, positionInNode - lastDelimiter);
				var retWord = "";
				if (autocomplete_data.trigger == '') {
					if (word.length >= autocomplete_data.minLength) {
						retWord = word;
					}
				} else {
					if (word.length > autocomplete_data.minLength && word.charAt(0).toString() == autocomplete_data.trigger) {
						retWord = word;
					}
				}
				return retWord;
			}

			ed.on( 'keyup', keyUpEvent );
			ed.on( 'keydown' , keyDownEvent );
			ed.on( 'keypress', keyPressEvent );
			ed.on( 'click', clickEvent );
		},

		getInfo: function () {
			return {
				longname: 'AutoComplete',
				author: 'Mijura Pty Ltd',
				authorurl: 'http://mijura.com',
				infourl: 'http://blog.mijura.com',
				version: tinymce.majorVersion + "." + tinymce.minorVersion
			};
		}
	});

	tinymce.PluginManager.add('autocomplete',
			tinymce.plugins.AutoCompletePlugin);
})();