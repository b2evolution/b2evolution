/* prevent execution of jQuery if included more then once */
if(typeof window.jQuery == "undefined") {
/*
 * jQuery @VERSION - New Wave Javascript
 *
 * Copyright (c) 2006 John Resig (jquery.com)
 * Dual licensed under the MIT (MIT-LICENSE.txt)
 * and GPL (GPL-LICENSE.txt) licenses.
 *
 * $Date$
 * $Rev: 557 $
 */

// Global undefined variable
window.undefined = window.undefined;

/**
 * Create a new jQuery Object
 *
 * @test ok( Array.prototype.push, "Array.push()" );
 * ok( Function.prototype.apply, "Function.apply()" );
 * ok( document.getElementById, "getElementById" );
 * ok( document.getElementsByTagName, "getElementsByTagName" );
 * ok( RegExp, "RegExp" );
 * ok( jQuery, "jQuery" );
 * ok( $, "$()" );
 *
 * @constructor
 * @private
 * @name jQuery
 * @cat Core
 */
var jQuery = function(a,c) {

	// Shortcut for document ready (because $(document).each() is silly)
	if ( a && typeof a == "function" && jQuery.fn.ready && !a.nodeType && a[0] == undefined ) // Safari reports typeof on DOM NodeLists as a function
		return jQuery(document).ready(a);

	// Make sure that a selection was provided
	a = a || jQuery.context || document;

	// Watch for when a jQuery object is passed as the selector
	if ( a.jquery )
		return jQuery( jQuery.merge( a, [] ) );

	// Watch for when a jQuery object is passed at the context
	if ( c && c.jquery )
		return jQuery( c ).find(a);

	// If the context is global, return a new object
	if ( window == this )
		return new jQuery(a,c);

	// Handle HTML strings
	if ( a.constructor == String ) {
		var m = /^[^<]*(<.+>)[^>]*$/.exec(a);
		if ( m ) a = jQuery.clean( [ m[1] ] );
	}

	// Watch for when an array is passed in
	this.get( a.constructor == Array || a.length && a != window && !a.nodeType && a[0] != undefined && a[0].nodeType ?
		// Assume that it is an array of DOM Elements
		jQuery.merge( a, [] ) :

		// Find the matching elements and save them for later
		jQuery.find( a, c ) );

	// See if an extra function was provided
	var fn = arguments[ arguments.length - 1 ];

	// If so, execute it in context
	if ( fn && typeof fn == "function" )
		this.each(fn);

	return this;
};

// Map over the $ in case of overwrite
if ( typeof $ != "undefined" )
	jQuery._$ = $;

/**
 * This function accepts a string containing a CSS selector,
 * basic XPath, or raw HTML, which is then used to match a set of elements.
 * The HTML string is different from the traditional selectors in that
 * it creates the DOM elements representing that HTML string, on the fly,
 * to be (assumedly) inserted into the document later.
 *
 * The core functionality of jQuery centers around this function.
 * Everything in jQuery is based upon this, or uses this in some way.
 * The most basic use of this function is to pass in an expression
 * (usually consisting of CSS or XPath), which then finds all matching
 * elements and remembers them for later use.
 *
 * By default, $() looks for DOM elements within the context of the
 * current HTML document.
 *
 * @example $("div > p")
 * @desc This finds all p elements that are children of a div element.
 * @before <p>one</p> <div><p>two</p></div> <p>three</p>
 * @result [ <p>two</p> ]
 *
 * @example $("<div><p>Hello</p></div>").appendTo("#body")
 * @desc Creates a div element (and all of its contents) dynamically, 
 * and appends it to the element with the ID of body. Internally, an
 * element is created and it's innerHTML property set to the given markup.
 * It is therefore both quite flexible and limited. 
 *
 * @name $
 * @param String expr An expression to search with, or a string of HTML to create on the fly.
 * @cat Core
 * @type jQuery
 */

/**
 * This function accepts a string containing a CSS selector, or
 * basic XPath, which is then used to match a set of elements with the
 * context of the specified DOM element, or document
 *
 * @example $("div", xml.responseXML)
 * @desc This finds all div elements within the specified XML document.
 *
 * @name $
 * @param String expr An expression to search with.
 * @param Element context A DOM Element, or Document, representing the base context.
 * @cat Core
 * @type jQuery
 */

/**
 * Wrap jQuery functionality around a specific DOM Element.
 * This function also accepts XML Documents and Window objects
 * as valid arguments (even though they are not DOM Elements).
 *
 * @example $(document).find("div > p")
 * @before <p>one</p> <div><p>two</p></div> <p>three</p>
 * @result [ <p>two</p> ]
 *
 * @example $(document.body).background( "black" );
 * @desc Sets the background color of the page to black.
 *
 * @name $
 * @param Element elem A DOM element to be encapsulated by a jQuery object.
 * @cat Core
 * @type jQuery
 */

/**
 * Wrap jQuery functionality around a set of DOM Elements.
 *
 * @example $( myForm.elements ).hide()
 * @desc Hides all the input elements within a form
 *
 * @name $
 * @param Array<Element> elems An array of DOM elements to be encapsulated by a jQuery object.
 * @cat Core
 * @type jQuery
 */

/**
 * A shorthand for $(document).ready(), allowing you to bind a function
 * to be executed when the DOM document has finished loading. This function
 * behaves just like $(document).ready(), in that it should be used to wrap
 * all of the other $() operations on your page. While this function is,
 * technically, chainable - there really isn't much use for chaining against it.
 * You can have as many $(document).ready events on your page as you like.
 *
 * @example $(function(){
 *   // Document is ready
 * });
 * @desc Executes the function when the DOM is ready to be used.
 *
 * @name $
 * @param Function fn The function to execute when the DOM is ready.
 * @cat Core
 * @type jQuery
 */

/**
 * A means of creating a cloned copy of a jQuery object. This function
 * copies the set of matched elements from one jQuery object and creates
 * another, new, jQuery object containing the same elements.
 *
 * @example var div = $("div");
 * $( div ).find("p");
 * @desc Locates all p elements with all div elements, without disrupting the original jQuery object contained in 'div' (as would normally be the case if a simple div.find("p") was done).
 *
 * @name $
 * @param jQuery obj The jQuery object to be cloned.
 * @cat Core
 * @type jQuery
 */

// Map the jQuery namespace to the '$' one
var $ = jQuery;

jQuery.fn = jQuery.prototype = {
	/**
	 * The current version of jQuery.
	 *
	 * @private
	 * @property
	 * @name jquery
	 * @type String
	 * @cat Core
	 */
	jquery: "@VERSION",

	/**
	 * The number of elements currently matched.
	 *
	 * @example $("img").length;
	 * @before <img src="test1.jpg"/> <img src="test2.jpg"/>
	 * @result 2
	 *
	 * @test ok( $("div").length == 2, "Get Number of Elements Found" );
	 *
	 * @property
	 * @name length
	 * @type Number
	 * @cat Core
	 */

	/**
	 * The number of elements currently matched.
	 *
	 * @example $("img").size();
	 * @before <img src="test1.jpg"/> <img src="test2.jpg"/>
	 * @result 2
	 *
	 * @test ok( $("div").size() == 2, "Get Number of Elements Found" );
	 *
	 * @name size
	 * @type Number
	 * @cat Core
	 */
	size: function() {
		return this.length;
	},

	/**
	 * Access all matched elements. This serves as a backwards-compatible
	 * way of accessing all matched elements (other than the jQuery object
	 * itself, which is, in fact, an array of elements).
	 *
	 * @example $("img").get();
	 * @before <img src="test1.jpg"/> <img src="test2.jpg"/>
	 * @result [ <img src="test1.jpg"/> <img src="test2.jpg"/> ]
	 *
	 * @test isSet( $("div").get(), q("main","foo"), "Get All Elements" );
	 *
	 * @name get
	 * @type Array<Element>
	 * @cat Core
	 */

	/**
	 * Access a single matched element. num is used to access the
	 * Nth element matched.
	 *
	 * @example $("img").get(1);
	 * @before <img src="test1.jpg"/> <img src="test2.jpg"/>
	 * @result [ <img src="test1.jpg"/> ]
	 *
	 * @test ok( $("div").get(0) == document.getElementById("main"), "Get A Single Element" );
	 *
	 * @name get
	 * @type Element
	 * @param Number num Access the element in the Nth position.
	 * @cat Core
	 */

	/**
	 * Set the jQuery object to an array of elements.
	 *
	 * @example $("img").get([ document.body ]);
	 * @result $("img").get() == [ document.body ]
	 *
	 * @private
	 * @name get
	 * @type jQuery
	 * @param Elements elems An array of elements
	 * @cat Core
	 */
	get: function( num ) {
		// Watch for when an array (of elements) is passed in
		if ( num && num.constructor == Array ) {

			// Use a tricky hack to make the jQuery object
			// look and feel like an array
			this.length = 0;
			[].push.apply( this, num );

			return this;
		} else
			return num == undefined ?

				// Return a 'clean' array
				jQuery.merge( this, [] ) :

				// Return just the object
				this[num];
	},

	/**
	 * Execute a function within the context of every matched element.
	 * This means that every time the passed-in function is executed
	 * (which is once for every element matched) the 'this' keyword
	 * points to the specific element.
	 *
	 * Additionally, the function, when executed, is passed a single
	 * argument representing the position of the element in the matched
	 * set.
	 *
	 * @example $("img").each(function(){
	 *   this.src = "test.jpg";
	 * });
	 * @before <img/> <img/>
	 * @result <img src="test.jpg"/> <img src="test.jpg"/>
	 *
	 * @example $("img").each(function(i){
	 *   alert( "Image #" + i + " is " + this );
	 * });
	 * @before <img/> <img/>
	 * @result <img src="test.jpg"/> <img src="test.jpg"/>
	 *
	 * @test var div = $("div");
	 * div.each(function(){this.foo = 'zoo';});
	 * var pass = true;
	 * for ( var i = 0; i < div.size(); i++ ) {
	 *   if ( div.get(i).foo != "zoo" ) pass = false;
	 * }
	 * ok( pass, "Execute a function, Relative" );
	 *
	 * @name each
	 * @type jQuery
	 * @param Function fn A function to execute
	 * @cat Core
	 */
	each: function( fn, args ) {
		return jQuery.each( this, fn, args );
	},

	/**
	 * Searches every matched element for the object and returns
	 * the index of the element, if found, starting with zero. 
	 * Returns -1 if the object wasn't found.
	 *
	 * @example $("*").index(document.getElementById('foobar')) 
	 * @before <div id="foobar"></div><b></b><span id="foo"></span>
	 * @result 0
	 *
	 * @example $("*").index(document.getElementById('foo')) 
	 * @before <div id="foobar"></div><b></b><span id="foo"></span>
	 * @result 2
	 *
	 * @example $("*").index(document.getElementById('bar')) 
	 * @before <div id="foobar"></div><b></b><span id="foo"></span>
	 * @result -1
	 *
	 * @test ok( $([window, document]).index(window) == 0, "Check for index of elements" );
	 * ok( $([window, document]).index(document) == 1, "Check for index of elements" );
	 * var inputElements = $('#radio1,#radio2,#check1,#check2');
	 * ok( inputElements.index(document.getElementById('radio1')) == 0, "Check for index of elements" );
	 * ok( inputElements.index(document.getElementById('radio2')) == 1, "Check for index of elements" );
	 * ok( inputElements.index(document.getElementById('check1')) == 2, "Check for index of elements" );
	 * ok( inputElements.index(document.getElementById('check2')) == 3, "Check for index of elements" );
	 * ok( inputElements.index(window) == -1, "Check for not found index" );
	 * ok( inputElements.index(document) == -1, "Check for not found index" );
	 * 
	 * @name index
	 * @type Number
	 * @param Object obj Object to search for
	 * @cat Core
	 */
	index: function( obj ) {
		var pos = -1;
		this.each(function(i){
			if ( this == obj ) pos = i;
		});
		return pos;
	},

	/**
	 * Access a property on the first matched element.
	 * This method makes it easy to retrieve a property value
	 * from the first matched element.
	 *
	 * @example $("img").attr("src");
	 * @before <img src="test.jpg"/>
	 * @result test.jpg
	 *
	 * @test ok( $('#text1').attr('value') == "Test", 'Check for value attribute' );
	 * ok( $('#text1').attr('type') == "text", 'Check for type attribute' );
	 * ok( $('#radio1').attr('type') == "radio", 'Check for type attribute' );
	 * ok( $('#check1').attr('type') == "checkbox", 'Check for type attribute' );
	 * ok( $('#simon1').attr('rel') == "bookmark", 'Check for rel attribute' );
	 * ok( $('#google').attr('title') == "Google!", 'Check for title attribute' );
	 * ok( $('#mark').attr('hreflang') == "en", 'Check for hreflang attribute' );
	 * ok( $('#en').attr('lang') == "en", 'Check for lang attribute' );
	 * ok( $('#simon').attr('class') == "blog link", 'Check for class attribute' );
	 * ok( $('#name').attr('name') == "name", 'Check for name attribute' );
	 * ok( $('#text1').attr('name') == "action", 'Check for name attribute' );
	 * ok( $('#form').attr('action').indexOf("formaction") >= 0, 'Check for action attribute' );
	 *
	 * @name attr
	 * @type Object
	 * @param String name The name of the property to access.
	 * @cat DOM
	 */

	/**
	 * Set a hash of key/value object properties to all matched elements.
	 * This serves as the best way to set a large number of properties
	 * on all matched elements.
	 *
	 * @example $("img").attr({ src: "test.jpg", alt: "Test Image" });
	 * @before <img/>
	 * @result <img src="test.jpg" alt="Test Image"/>
	 *
	 * @test var pass = true;
	 * $("div").attr({foo: 'baz', zoo: 'ping'}).each(function(){
	 *   if ( this.getAttribute('foo') != "baz" && this.getAttribute('zoo') != "ping" ) pass = false;
	 * });
	 * ok( pass, "Set Multiple Attributes" );
	 *
	 * @name attr
	 * @type jQuery
	 * @param Hash prop A set of key/value pairs to set as object properties.
	 * @cat DOM
	 */

	/**
	 * Set a single property to a value, on all matched elements.
	 *
	 * @example $("img").attr("src","test.jpg");
	 * @before <img/>
	 * @result <img src="test.jpg"/>
	 *
	 * @test var div = $("div");
	 * div.attr("foo", "bar");
	 * var pass = true;
	 * for ( var i = 0; i < div.size(); i++ ) {
	 *   if ( div.get(i).getAttribute('foo') != "bar" ) pass = false;
	 * }
	 * ok( pass, "Set Attribute" );
	 *
	 * $("#name").attr('name', 'something');
	 * ok( $("#name").name() == 'something', 'Set name attribute' );
	 * $("#check2").attr('checked', true);
	 * ok( document.getElementById('check2').checked == true, 'Set checked attribute' );
	 * $("#check2").attr('checked', false);
	 * ok( document.getElementById('check2').checked == false, 'Set checked attribute' );
	 * $("#text1").attr('readonly', true);
	 * ok( document.getElementById('text1').readOnly == true, 'Set readonly attribute' );
	 * $("#text1").attr('readonly', false);
	 * ok( document.getElementById('text1').readOnly == false, 'Set readonly attribute' );
	 *
	 * @test stop();
	 * $.get('data/dashboard.xml', function(xml) { 
	 *   var titles = [];
	 *   $('tab', xml).each(function() {
	 *     titles.push($(this).attr('title'));
	 *   });
	 *   ok( titles[0] == 'Location', 'attr() in XML context: Check first title' );
	 *   ok( titles[1] == 'Users', 'attr() in XML context: Check second title' );
	 *   start();
	 * });
	 *
	 * @name attr
	 * @type jQuery
	 * @param String key The name of the property to set.
	 * @param Object value The value to set the property to.
	 * @cat DOM
	 */
	attr: function( key, value, type ) {
		// Check to see if we're setting style values
		return key.constructor != String || value != undefined ?
			this.each(function(){
				// See if we're setting a hash of styles
				if ( value == undefined )
					// Set all the styles
					for ( var prop in key )
						jQuery.attr(
							type ? this.style : this,
							prop, key[prop]
						);

				// See if we're setting a single key/value style
				else
					jQuery.attr(
						type ? this.style : this,
						key, value
					);
			}) :

			// Look for the case where we're accessing a style value
			jQuery[ type || "attr" ]( this[0], key );
	},

	/**
	 * Access a style property on the first matched element.
	 * This method makes it easy to retrieve a style property value
	 * from the first matched element.
	 *
	 * @example $("p").css("color");
	 * @before <p style="color:red;">Test Paragraph.</p>
	 * @result red
	 * @desc Retrieves the color style of the first paragraph
	 *
	 * @example $("p").css("fontWeight");
	 * @before <p style="font-weight: bold;">Test Paragraph.</p>
	 * @result bold
	 * @desc Retrieves the font-weight style of the first paragraph.
	 * Note that for all style properties with a dash (like 'font-weight'), you have to
	 * write it in camelCase. In other words: Every time you have a '-' in a 
	 * property, remove it and replace the next character with an uppercase 
	 * representation of itself. Eg. fontWeight, fontSize, fontFamily, borderWidth,
	 * borderStyle, borderBottomWidth etc.
	 *
	 * @test ok( $('#main').css("display") == 'none', 'Check for css property "display"');
	 *
	 * @name css
	 * @type Object
	 * @param String name The name of the property to access.
	 * @cat CSS
	 */

	/**
	 * Set a hash of key/value style properties to all matched elements.
	 * This serves as the best way to set a large number of style properties
	 * on all matched elements.
	 *
	 * @example $("p").css({ color: "red", background: "blue" });
	 * @before <p>Test Paragraph.</p>
	 * @result <p style="color:red; background:blue;">Test Paragraph.</p>
	 *
	 * @test ok( $('#foo').is(':visible'), 'Modifying CSS display: Assert element is visible');
	 * $('#foo').css({display: 'none'});
	 * ok( !$('#foo').is(':visible'), 'Modified CSS display: Assert element is hidden');
	 * $('#foo').css({display: 'block'});
	 * ok( $('#foo').is(':visible'), 'Modified CSS display: Assert element is visible');
	 * $('#floatTest').css({styleFloat: 'right'});
	 * ok( $('#floatTest').css('styleFloat') == 'right', 'Modified CSS float using "styleFloat": Assert float is right');
	 * $('#floatTest').css({cssFloat: 'left'});
	 * ok( $('#floatTest').css('cssFloat') == 'left', 'Modified CSS float using "cssFloat": Assert float is left');
	 * $('#floatTest').css({'float': 'right'});
	 * ok( $('#floatTest').css('float') == 'right', 'Modified CSS float using "float": Assert float is right');
	 * $('#floatTest').css({'font-size': '30px'});
	 * ok( $('#floatTest').css('font-size') == '30px', 'Modified CSS font-size: Assert font-size is 30px');
	 * 
	 * @name css
	 * @type jQuery
	 * @param Hash prop A set of key/value pairs to set as style properties.
	 * @cat CSS
	 */

	/**
	 * Set a single style property to a value, on all matched elements.
	 *
	 * @example $("p").css("color","red");
	 * @before <p>Test Paragraph.</p>
	 * @result <p style="color:red;">Test Paragraph.</p>
	 * @desc Changes the color of all paragraphs to red
	 *
	 *
	 * @test ok( $('#foo').is(':visible'), 'Modifying CSS display: Assert element is visible');
	 * $('#foo').css('display', 'none');
	 * ok( !$('#foo').is(':visible'), 'Modified CSS display: Assert element is hidden');
	 * $('#foo').css('display', 'block');
	 * ok( $('#foo').is(':visible'), 'Modified CSS display: Assert element is visible');
	 * $('#floatTest').css('styleFloat', 'left');
	 * ok( $('#floatTest').css('styleFloat') == 'left', 'Modified CSS float using "styleFloat": Assert float is left');
	 * $('#floatTest').css('cssFloat', 'right');
	 * ok( $('#floatTest').css('cssFloat') == 'right', 'Modified CSS float using "cssFloat": Assert float is right');
	 * $('#floatTest').css('float', 'left');
	 * ok( $('#floatTest').css('float') == 'left', 'Modified CSS float using "float": Assert float is left');
	 * $('#floatTest').css('font-size', '20px');
	 * ok( $('#floatTest').css('font-size') == '20px', 'Modified CSS font-size: Assert font-size is 20px');
	 *
	 * @name css
	 * @type jQuery
	 * @param String key The name of the property to set.
	 * @param Object value The value to set the property to.
	 * @cat CSS
	 */
	css: function( key, value ) {
		return this.attr( key, value, "curCSS" );
	},

	/**
	 * Retrieve the text contents of all matched elements. The result is
	 * a string that contains the combined text contents of all matched
	 * elements. This method works on both HTML and XML documents.
	 *
	 * @example $("p").text();
	 * @before <p>Test Paragraph.</p>
	 * @result Test Paragraph.
	 *
	 * @test var expected = "This link has class=\"blog\": Simon Willison's Weblog";
	 * ok( $('#sap').text() == expected, 'Check for merged text of more then one element.' );
	 *
	 * @name text
	 * @type String
	 * @cat DOM
	 */
	text: function(e) {
		e = e || this;
		var t = "";
		for ( var j = 0; j < e.length; j++ ) {
			var r = e[j].childNodes;
			for ( var i = 0; i < r.length; i++ )
				if ( r[i].nodeType != 8 )
					t += r[i].nodeType != 1 ?
						r[i].nodeValue : jQuery.fn.text([ r[i] ]);
		}
		return t;
	},

	/**
	 * Wrap all matched elements with a structure of other elements.
	 * This wrapping process is most useful for injecting additional
	 * stucture into a document, without ruining the original semantic
	 * qualities of a document.
	 *
	 * This works by going through the first element
	 * provided (which is generated, on the fly, from the provided HTML)
	 * and finds the deepest ancestor element within its
	 * structure - it is that element that will en-wrap everything else.
	 *
	 * This does not work with elements that contain text. Any necessary text
	 * must be added after the wrapping is done.
	 *
	 * @example $("p").wrap("<div class='wrap'></div>");
	 * @before <p>Test Paragraph.</p>
	 * @result <div class='wrap'><p>Test Paragraph.</p></div>
	 * 
	 * @test var defaultText = 'Try them out:'
	 * var result = $('#first').wrap('<div class="red"><span></span></div>').text();
	 * ok( defaultText == result, 'Check for wrapping of on-the-fly html' );
	 * ok( $('#first').parent().parent().is('.red'), 'Check if wrapper has class "red"' );
	 *
	 * @name wrap
	 * @type jQuery
	 * @param String html A string of HTML, that will be created on the fly and wrapped around the target.
	 * @cat DOM/Manipulation
	 */

	/**
	 * Wrap all matched elements with a structure of other elements.
	 * This wrapping process is most useful for injecting additional
	 * stucture into a document, without ruining the original semantic
	 * qualities of a document.
	 *
	 * This works by going through the first element
	 * provided and finding the deepest ancestor element within its
	 * structure - it is that element that will en-wrap everything else.
	 *
 	 * This does not work with elements that contain text. Any necessary text
	 * must be added after the wrapping is done.
	 *
	 * @example $("p").wrap( document.getElementById('content') );
	 * @before <p>Test Paragraph.</p><div id="content"></div>
	 * @result <div id="content"><p>Test Paragraph.</p></div>
	 *
	 * @test var defaultText = 'Try them out:'
	 * var result = $('#first').wrap(document.getElementById('empty')).parent();
	 * ok( result.is('ol'), 'Check for element wrapping' );
	 * ok( result.text() == defaultText, 'Check for element wrapping' );
	 *
	 * @name wrap
	 * @type jQuery
	 * @param Element elem A DOM element that will be wrapped.
	 * @cat DOM/Manipulation
	 */
	wrap: function() {
		// The elements to wrap the target around
		var a = jQuery.clean(arguments);

		// Wrap each of the matched elements individually
		return this.each(function(){
			// Clone the structure that we're using to wrap
			var b = a[0].cloneNode(true);

			// Insert it before the element to be wrapped
			this.parentNode.insertBefore( b, this );

			// Find the deepest point in the wrap structure
			while ( b.firstChild )
				b = b.firstChild;

			// Move the matched element to within the wrap structure
			b.appendChild( this );
		});
	},

	/**
	 * Append any number of elements to the inside of every matched elements,
	 * generated from the provided HTML.
	 * This operation is similar to doing an appendChild to all the
	 * specified elements, adding them into the document.
	 *
	 * @example $("p").append("<b>Hello</b>");
	 * @before <p>I would like to say: </p>
	 * @result <p>I would like to say: <b>Hello</b></p>
	 *
	 * @test var defaultText = 'Try them out:'
	 * var result = $('#first').append('<b>buga</b>');
	 * ok( result.text() == defaultText + 'buga', 'Check if text appending works' );
	 * ok( $('#select3').append('<option value="appendTest">Append Test</option>').find('option:last-child').attr('value') == 'appendTest', 'Appending html options to select element');
	 *
	 * @name append
	 * @type jQuery
	 * @param String html A string of HTML, that will be created on the fly and appended to the target.
	 * @cat DOM/Manipulation
	 */

	/**
	 * Append an element to the inside of all matched elements.
	 * This operation is similar to doing an appendChild to all the
	 * specified elements, adding them into the document.
	 *
	 * @example $("p").append( $("#foo")[0] );
	 * @before <p>I would like to say: </p><b id="foo">Hello</b>
	 * @result <p>I would like to say: <b id="foo">Hello</b></p>
	 *
	 * @test var expected = "This link has class=\"blog\": Simon Willison's WeblogTry them out:";
	 * $('#sap').append(document.getElementById('first'));
	 * ok( expected == $('#sap').text(), "Check for appending of element" );
	 *
	 * @name append
	 * @type jQuery
	 * @param Element elem A DOM element that will be appended.
	 * @cat DOM/Manipulation
	 */

	/**
	 * Append any number of elements to the inside of all matched elements.
	 * This operation is similar to doing an appendChild to all the
	 * specified elements, adding them into the document.
	 *
	 * @example $("p").append( $("b") );
	 * @before <p>I would like to say: </p><b>Hello</b>
	 * @result <p>I would like to say: <b>Hello</b></p>
	 *
	 * @test var expected = "This link has class=\"blog\": Simon Willison's WeblogTry them out:Yahoo";
	 * $('#sap').append([document.getElementById('first'), document.getElementById('yahoo')]);
	 * ok( expected == $('#sap').text(), "Check for appending of array of elements" );
	 *
	 * @name append
	 * @type jQuery
	 * @param Array<Element> elems An array of elements, all of which will be appended.
	 * @cat DOM/Manipulation
	 */
	append: function() {
		return this.domManip(arguments, true, 1, function(a){
			this.appendChild( a );
		});
	},

	/**
	 * Prepend any number of elements to the inside of every matched elements,
	 * generated from the provided HTML.
	 * This operation is the best way to insert dynamically created elements
	 * inside, at the beginning, of all the matched element.
	 *
	 * @example $("p").prepend("<b>Hello</b>");
	 * @before <p>I would like to say: </p>
	 * @result <p><b>Hello</b>I would like to say: </p>
	 *
 	 * @test var defaultText = 'Try them out:'
	 * var result = $('#first').prepend('<b>buga</b>');
	 * ok( result.text() == 'buga' + defaultText, 'Check if text prepending works' );
	 * ok( $('#select3').prepend('<option value="prependTest">Prepend Test</option>').find('option:first-child').attr('value') == 'prependTest', 'Prepending html options to select element');
	 *
	 * @name prepend
	 * @type jQuery
	 * @param String html A string of HTML, that will be created on the fly and appended to the target.
	 * @cat DOM/Manipulation
	 */

	/**
	 * Prepend an element to the inside of all matched elements.
	 * This operation is the best way to insert an element inside, at the
	 * beginning, of all the matched element.
	 *
	 * @example $("p").prepend( $("#foo")[0] );
	 * @before <p>I would like to say: </p><b id="foo">Hello</b>
	 * @result <p><b id="foo">Hello</b>I would like to say: </p>
	 *	 
	 * @test var expected = "Try them out:This link has class=\"blog\": Simon Willison's Weblog";
	 * $('#sap').prepend(document.getElementById('first'));
	 * ok( expected == $('#sap').text(), "Check for prepending of element" );
	 *
	 * @name prepend
	 * @type jQuery
	 * @param Element elem A DOM element that will be appended.
	 * @cat DOM/Manipulation
	 */

	/**
	 * Prepend any number of elements to the inside of all matched elements.
	 * This operation is the best way to insert a set of elements inside, at the
	 * beginning, of all the matched element.
	 *
	 * @example $("p").prepend( $("b") );
	 * @before <p>I would like to say: </p><b>Hello</b>
	 * @result <p><b>Hello</b>I would like to say: </p>
	 *
	 * @test var expected = "Try them out:YahooThis link has class=\"blog\": Simon Willison's Weblog";
	 * $('#sap').prepend([document.getElementById('first'), document.getElementById('yahoo')]);
	 * ok( expected == $('#sap').text(), "Check for prepending of array of elements" );
	 *
	 * @name prepend
	 * @type jQuery
	 * @param Array<Element> elems An array of elements, all of which will be appended.
	 * @cat DOM/Manipulation
	 */
	prepend: function() {
		return this.domManip(arguments, true, -1, function(a){
			this.insertBefore( a, this.firstChild );
		});
	},

	/**
	 * Insert any number of dynamically generated elements before each of the
	 * matched elements.
	 *
	 * @example $("p").before("<b>Hello</b>");
	 * @before <p>I would like to say: </p>
	 * @result <b>Hello</b><p>I would like to say: </p>
	 *
	 * @test var expected = 'This is a normal link: bugaYahoo';
	 * $('#yahoo').before('<b>buga</b>');
	 * ok( expected == $('#en').text(), 'Insert String before' );
	 *
	 * @name before
	 * @type jQuery
	 * @param String html A string of HTML, that will be created on the fly and appended to the target.
	 * @cat DOM/Manipulation
	 */

	/**
	 * Insert an element before each of the matched elements.
	 *
	 * @example $("p").before( $("#foo")[0] );
	 * @before <p>I would like to say: </p><b id="foo">Hello</b>
	 * @result <b id="foo">Hello</b><p>I would like to say: </p>
	 *
	 * @test var expected = "This is a normal link: Try them out:Yahoo";
	 * $('#yahoo').before(document.getElementById('first'));
	 * ok( expected == $('#en').text(), "Insert element before" );
	 *
	 * @name before
	 * @type jQuery
	 * @param Element elem A DOM element that will be appended.
	 * @cat DOM/Manipulation
	 */

	/**
	 * Insert any number of elements before each of the matched elements.
	 *
	 * @example $("p").before( $("b") );
	 * @before <p>I would like to say: </p><b>Hello</b>
	 * @result <b>Hello</b><p>I would like to say: </p>
	 *
	 * @test var expected = "This is a normal link: Try them out:diveintomarkYahoo";
	 * $('#yahoo').before([document.getElementById('first'), document.getElementById('mark')]);
	 * ok( expected == $('#en').text(), "Insert array of elements before" );
	 *
	 * @name before
	 * @type jQuery
	 * @param Array<Element> elems An array of elements, all of which will be appended.
	 * @cat DOM/Manipulation
	 */
	before: function() {
		return this.domManip(arguments, false, 1, function(a){
			this.parentNode.insertBefore( a, this );
		});
	},

	/**
	 * Insert any number of dynamically generated elements after each of the
	 * matched elements.
	 *
	 * @example $("p").after("<b>Hello</b>");
	 * @before <p>I would like to say: </p>
	 * @result <p>I would like to say: </p><b>Hello</b>
	 *
	 * @test var expected = 'This is a normal link: Yahoobuga';
	 * $('#yahoo').after('<b>buga</b>');
	 * ok( expected == $('#en').text(), 'Insert String after' );
	 *
	 * @name after
	 * @type jQuery
	 * @param String html A string of HTML, that will be created on the fly and appended to the target.
	 * @cat DOM/Manipulation
	 */

	/**
	 * Insert an element after each of the matched elements.
	 *
	 * @example $("p").after( $("#foo")[0] );
	 * @before <b id="foo">Hello</b><p>I would like to say: </p>
	 * @result <p>I would like to say: </p><b id="foo">Hello</b>
	 *
	 * @test var expected = "This is a normal link: YahooTry them out:";
	 * $('#yahoo').after(document.getElementById('first'));
	 * ok( expected == $('#en').text(), "Insert element after" );
	 *
	 * @name after
	 * @type jQuery
	 * @param Element elem A DOM element that will be appended.
	 * @cat DOM/Manipulation
	 */

	/**
	 * Insert any number of elements after each of the matched elements.
	 *
	 * @example $("p").after( $("b") );
	 * @before <b>Hello</b><p>I would like to say: </p>
	 * @result <p>I would like to say: </p><b>Hello</b>
	 *
	 * @test var expected = "This is a normal link: YahooTry them out:diveintomark";
	 * $('#yahoo').after([document.getElementById('first'), document.getElementById('mark')]);
	 * ok( expected == $('#en').text(), "Insert array of elements after" );
	 *
	 * @name after
	 * @type jQuery
	 * @param Array<Element> elems An array of elements, all of which will be appended.
	 * @cat DOM/Manipulation
	 */
	after: function() {
		return this.domManip(arguments, false, -1, function(a){
			this.parentNode.insertBefore( a, this.nextSibling );
		});
	},

	/**
	 * End the most recent 'destructive' operation, reverting the list of matched elements
	 * back to its previous state. After an end operation, the list of matched elements will
	 * revert to the last state of matched elements.
	 *
	 * @example $("p").find("span").end();
	 * @before <p><span>Hello</span>, how are you?</p>
	 * @result $("p").find("span").end() == [ <p>...</p> ]
	 *
	 * @test ok( 'Yahoo' == $('#yahoo').parent().end().text(), 'Check for end' );
	 * ok( $('#yahoo').end(), 'Check for end with nothing to end' );
	 *
	 * @name end
	 * @type jQuery
	 * @cat DOM/Traversing
	 */
	end: function() {
		if( !(this.stack && this.stack.length) )
			return this;
		return this.get( this.stack.pop() );
	},

	/**
	 * Searches for all elements that match the specified expression.
	 * This method is the optimal way of finding additional descendant
	 * elements with which to process.
	 *
	 * All searching is done using a jQuery expression. The expression can be
	 * written using CSS 1-3 Selector syntax, or basic XPath.
	 *
	 * @example $("p").find("span");
	 * @before <p><span>Hello</span>, how are you?</p>
	 * @result $("p").find("span") == [ <span>Hello</span> ]
	 *
	 * @test ok( 'Yahoo' == $('#foo').find('.blogTest').text(), 'Check for find' );
	 *
	 * @name find
	 * @type jQuery
	 * @param String expr An expression to search with.
	 * @cat DOM/Traversing
	 */
	find: function(t) {
		return this.pushStack( jQuery.map( this, function(a){
			return jQuery.find(t,a);
		}), arguments );
	},

	/**
	 * Create cloned copies of all matched DOM Elements. This does
	 * not create a cloned copy of this particular jQuery object,
	 * instead it creates duplicate copies of all DOM Elements.
	 * This is useful for moving copies of the elements to another
	 * location in the DOM.
	 *
	 * @example $("b").clone().prependTo("p");
	 * @before <b>Hello</b><p>, how are you?</p>
	 * @result <b>Hello</b><p><b>Hello</b>, how are you?</p>
	 *
	 * @test ok( 'This is a normal link: Yahoo' == $('#en').text(), 'Assert text for #en' );
	 * var clone = $('#yahoo').clone();
	 * ok( 'Try them out:Yahoo' == $('#first').append(clone).text(), 'Check for clone' );
	 * ok( 'This is a normal link: Yahoo' == $('#en').text(), 'Reassert text for #en' );
	 *
	 * @name clone
	 * @type jQuery
	 * @cat DOM/Manipulation
	 */
	clone: function(deep) {
		return this.pushStack( jQuery.map( this, function(a){
			return a.cloneNode( deep != undefined ? deep : true );
		}), arguments );
	},

	/**
	 * Removes all elements from the set of matched elements that do not
	 * match the specified expression. This method is used to narrow down
	 * the results of a search.
	 *
	 * All searching is done using a jQuery expression. The expression
	 * can be written using CSS 1-3 Selector syntax, or basic XPath.
	 *
	 * @example $("p").filter(".selected")
	 * @before <p class="selected">Hello</p><p>How are you?</p>
	 * @result $("p").filter(".selected") == [ <p class="selected">Hello</p> ]
	 *
	 * @test isSet( $("input").filter(":checked").get(), q("radio2", "check1"), "Filter elements" );
	 * @test $("input").filter(":checked",function(i){ 
	 *   ok( this == q("radio2", "check1")[i], "Filter elements, context" );
	 * });
	 * @test $("#main > p#ap > a").filter("#foobar",function(){},function(i){
	 *   ok( this == q("google","groups", "mark")[i], "Filter elements, else context" );
	 * });
	 *
	 * @name filter
	 * @type jQuery
	 * @param String expr An expression to search with.
	 * @cat DOM/Traversing
	 */

	/**
	 * Removes all elements from the set of matched elements that do not
	 * match at least one of the expressions passed to the function. This
	 * method is used when you want to filter the set of matched elements
	 * through more than one expression.
	 *
	 * Elements will be retained in the jQuery object if they match at
	 * least one of the expressions passed.
	 *
	 * @example $("p").filter([".selected", ":first"])
	 * @before <p>Hello</p><p>Hello Again</p><p class="selected">And Again</p>
	 * @result $("p").filter([".selected", ":first"]) == [ <p>Hello</p>, <p class="selected">And Again</p> ]
	 *
	 * @name filter
	 * @type jQuery
	 * @param Array<String> exprs A set of expressions to evaluate against
	 * @cat DOM/Traversing
	 */
	filter: function(t) {
		return this.pushStack(
			t.constructor == Array &&
			jQuery.map(this,function(a){
				for ( var i = 0; i < t.length; i++ )
					if ( jQuery.filter(t[i],[a]).r.length )
						return a;
				return false;
			}) ||

			t.constructor == Boolean &&
			( t ? this.get() : [] ) ||

			typeof t == "function" &&
			jQuery.grep( this, t ) ||

			jQuery.filter(t,this).r, arguments );
	},

	/**
	 * Removes the specified Element from the set of matched elements. This
	 * method is used to remove a single Element from a jQuery object.
	 *
	 * @example $("p").not( document.getElementById("selected") )
	 * @before <p>Hello</p><p id="selected">Hello Again</p>
	 * @result [ <p>Hello</p> ]
	 *
	 * @name not
	 * @type jQuery
	 * @param Element el An element to remove from the set
	 * @cat DOM/Traversing
	 */

	/**
	 * Removes elements matching the specified expression from the set
	 * of matched elements. This method is used to remove one or more
	 * elements from a jQuery object.
	 *
	 * @example $("p").not("#selected")
	 * @before <p>Hello</p><p id="selected">Hello Again</p>
	 * @result [ <p>Hello</p> ]
	 *
	 * @test ok($("#main > p#ap > a").not("#google").length == 2, ".not")
	 *
	 * @name not
	 * @type jQuery
	 * @param String expr An expression with which to remove matching elements
	 * @cat DOM/Traversing
	 */
	not: function(t) {
		return this.pushStack( t.constructor == String ?
			jQuery.filter(t,this,false).r :
			jQuery.grep(this,function(a){ return a != t; }), arguments );
	},

	/**
	 * Adds the elements matched by the expression to the jQuery object. This
	 * can be used to concatenate the result sets of two expressions.
	 *
	 * @example $("p").add("span")
	 * @before <p>Hello</p><p><span>Hello Again</span></p>
	 * @result [ <p>Hello</p>, <span>Hello Again</span> ]
	 *
	 * @name add
	 * @type jQuery
	 * @param String expr An expression whose matched elements are added
	 * @cat DOM/Traversing
	 */

	/**
	 * Adds each of the Elements in the array to the set of matched elements.
	 * This is used to add a set of Elements to a jQuery object.
	 *
	 * @example $("p").add([document.getElementById("a"), document.getElementById("b")])
	 * @before <p>Hello</p><p><span id="a">Hello Again</span><span id="b">And Again</span></p>
	 * @result [ <p>Hello</p>, <span id="a">Hello Again</span>, <span id="b">And Again</span> ]
	 *
	 * @name add
	 * @type jQuery
	 * @param Array<Element> els An array of Elements to add
	 * @cat DOM/Traversing
	 */

	/**
	 * Adds a single Element to the set of matched elements. This is used to
	 * add a single Element to a jQuery object.
	 *
	 * @example $("p").add( document.getElementById("a") )
	 * @before <p>Hello</p><p><span id="a">Hello Again</span></p>
	 * @result [ <p>Hello</p>, <span id="a">Hello Again</span> ]
	 *
	 * @name add
	 * @type jQuery
	 * @param Element el An Element to add
	 * @cat DOM/Traversing
	 */
	add: function(t) {
		return this.pushStack( jQuery.merge( this, t.constructor == String ?
			jQuery.find(t) : t.constructor == Array ? t : [t] ), arguments );
	},

	/**
	 * Checks the current selection against an expression and returns true,
	 * if the selection fits the given expression. Does return false, if the
	 * selection does not fit or the expression is not valid.
	 *
	 * @example $("input[@type='checkbox']").parent().is("form")
	 * @before <form><input type="checkbox" /></form>
	 * @result true
	 * @desc Returns true, because the parent of the input is a form element
	 * 
	 * @example $("input[@type='checkbox']").parent().is("form")
	 * @before <form><p><input type="checkbox" /></p></form>
	 * @result false
	 * @desc Returns false, because the parent of the input is a p element
	 *
	 * @example $("form").is(null)
	 * @before <form></form>
	 * @result false
	 * @desc An invalid expression always returns false.
	 *
	 * @test ok( $('#form').is('form'), 'Check for element: A form must be a form' );
	 * ok( !$('#form').is('div'), 'Check for element: A form is not a div' );
	 * ok( $('#mark').is('.blog'), 'Check for class: Expected class "blog"' );
	 * ok( !$('#mark').is('.link'), 'Check for class: Did not expect class "link"' );
	 * ok( $('#simon').is('.blog.link'), 'Check for multiple classes: Expected classes "blog" and "link"' );
	 * ok( !$('#simon').is('.blogTest'), 'Check for multiple classes: Expected classes "blog" and "link", but not "blogTest"' );
	 * ok( $('#en').is('[@lang="en"]'), 'Check for attribute: Expected attribute lang to be "en"' );
	 * ok( !$('#en').is('[@lang="de"]'), 'Check for attribute: Expected attribute lang to be "en", not "de"' );
	 * ok( $('#text1').is('[@type="text"]'), 'Check for attribute: Expected attribute type to be "text"' );
	 * ok( !$('#text1').is('[@type="radio"]'), 'Check for attribute: Expected attribute type to be "text", not "radio"' );
	 * ok( $('#text2').is(':disabled'), 'Check for pseudoclass: Expected to be disabled' );
	 * ok( !$('#text1').is(':disabled'), 'Check for pseudoclass: Expected not disabled' );
	 * ok( $('#radio2').is(':checked'), 'Check for pseudoclass: Expected to be checked' );
	 * ok( !$('#radio1').is(':checked'), 'Check for pseudoclass: Expected not checked' );
	 * ok( $('#foo').is('[p]'), 'Check for child: Expected a child "p" element' );
	 * ok( !$('#foo').is('[ul]'), 'Check for child: Did not expect "ul" element' );
	 * ok( $('#foo').is('[p][a][code]'), 'Check for childs: Expected "p", "a" and "code" child elements' );
	 * ok( !$('#foo').is('[p][a][code][ol]'), 'Check for childs: Expected "p", "a" and "code" child elements, but no "ol"' );
	 * ok( !$('#foo').is(0), 'Expected false for an invalid expression - 0' );
	 * ok( !$('#foo').is(null), 'Expected false for an invalid expression - null' );
	 * ok( !$('#foo').is(''), 'Expected false for an invalid expression - ""' );
	 * ok( !$('#foo').is(undefined), 'Expected false for an invalid expression - undefined' );
	 *
	 * @name is
	 * @type Boolean
	 * @param String expr The expression with which to filter
	 * @cat DOM/Traversing
	 */
	is: function(expr) {
		return expr ? jQuery.filter(expr,this).r.length > 0 : false;
	},
	
	/**
	 *
	 *
	 * @private
	 * @name domManip
	 * @param Array args
	 * @param Boolean table
	 * @param Number int
	 * @param Function fn The function doing the DOM manipulation.
	 * @type jQuery
	 * @cat Core
	 */
	domManip: function(args, table, dir, fn){
		var clone = this.size() > 1;
		var a = jQuery.clean(args);

		return this.each(function(){
			var obj = this;

			if ( table && this.nodeName.toUpperCase() == "TABLE" && a[0].nodeName.toUpperCase() != "THEAD" ) {
				var tbody = this.getElementsByTagName("tbody");

				if ( !tbody.length ) {
					obj = document.createElement("tbody");
					this.appendChild( obj );
				} else
					obj = tbody[0];
			}

			for ( var i = ( dir < 0 ? a.length - 1 : 0 );
				i != ( dir < 0 ? dir : a.length ); i += dir ) {
					fn.apply( obj, [ clone ? a[i].cloneNode(true) : a[i] ] );
			}
		});
	},

	/**
	 *
	 *
	 * @private
	 * @name pushStack
	 * @param Array a
	 * @param Array args
	 * @type jQuery
	 * @cat Core
	 */
	pushStack: function(a,args) {
		var fn = args && args[args.length-1];
		var fn2 = args && args[args.length-2];
		
		if ( fn && fn.constructor != Function ) fn = null;
		if ( fn2 && fn2.constructor != Function ) fn2 = null;

		if ( !fn ) {
			if ( !this.stack ) this.stack = [];
			this.stack.push( this.get() );
			this.get( a );
		} else {
			var old = this.get();
			this.get( a );

			if ( fn2 && a.length || !fn2 )
				this.each( fn2 || fn ).get( old );
			else
				this.get( old ).each( fn );
		}

		return this;
	}
};

/**
 * Extends the jQuery object itself. Can be used to add both static
 * functions and plugin methods.
 * 
 * @example $.fn.extend({
 *   check: function() {
 *     this.each(function() { this.checked = true; });
 *   ),
 *   uncheck: function() {
 *     this.each(function() { this.checked = false; });
 *   }
 * });
 * $("input[@type=checkbox]").check();
 * $("input[@type=radio]").uncheck();
 * @desc Adds two plugin methods.
 *
 * @private
 * @name extend
 * @param Object obj
 * @type Object
 * @cat Core
 */

/**
 * Extend one object with another, returning the original,
 * modified, object. This is a great utility for simple inheritance.
 * 
 * @example var settings = { validate: false, limit: 5, name: "foo" };
 * var options = { validate: true, name: "bar" };
 * jQuery.extend(settings, options);
 * @result settings == { validate: true, limit: 5, name: "bar" }
 *
 * @test var settings = { xnumber1: 5, xnumber2: 7, xstring1: "peter", xstring2: "pan" };
 * var options =     { xnumber2: 1, xstring2: "x", xxx: "newstring" };
 * var optionsCopy = { xnumber2: 1, xstring2: "x", xxx: "newstring" };
 * var merged = { xnumber1: 5, xnumber2: 1, xstring1: "peter", xstring2: "x", xxx: "newstring" };
 * jQuery.extend(settings, options);
 * isSet( settings, merged, "Check if extended: settings must be extended" );
 * isSet ( options, optionsCopy, "Check if not modified: options must not be modified" );
 *
 * @name $.extend
 * @param Object obj The object to extend
 * @param Object prop The object that will be merged into the first.
 * @type Object
 * @cat Javascript
 */
jQuery.extend = jQuery.fn.extend = function(obj,prop) {
	// Watch for the case where null or undefined gets passed in by accident
	if ( arguments.length > 1 && (prop === null || prop == undefined) )
		return obj;

	// If no property object was provided, then we're extending jQuery
	if ( !prop ) { prop = obj; obj = this; }

	// Extend the base object
	for ( var i in prop ) obj[i] = prop[i];

	// Return the modified object
	return obj;
};

jQuery.extend({
	/**
	 * @private
	 * @name init
	 * @type undefined
	 * @cat Core
	 */
	init: function(){
		jQuery.initDone = true;

		jQuery.each( jQuery.macros.axis, function(i,n){
			jQuery.fn[ i ] = function(a) {
				var ret = jQuery.map(this,n);
				if ( a && a.constructor == String )
					ret = jQuery.filter(a,ret).r;
				return this.pushStack( ret, arguments );
			};
		});

		jQuery.each( jQuery.macros.to, function(i,n){
			jQuery.fn[ i ] = function(){
				var a = arguments;
				return this.each(function(){
					for ( var j = 0; j < a.length; j++ )
						jQuery(a[j])[n]( this );
				});
			};
		});

		jQuery.each( jQuery.macros.each, function(i,n){
			jQuery.fn[ i ] = function() {
				return this.each( n, arguments );
			};
		});

		jQuery.each( jQuery.macros.filter, function(i,n){
			jQuery.fn[ n ] = function(num,fn) {
				return this.filter( ":" + n + "(" + num + ")", fn );
			};
		});

		jQuery.each( jQuery.macros.attr, function(i,n){
			n = n || i;
			jQuery.fn[ i ] = function(h) {
				return h == undefined ?
					this.length ? this[0][n] : null :
					this.attr( n, h );
			};
		});

		jQuery.each( jQuery.macros.css, function(i,n){
			jQuery.fn[ n ] = function(h) {
				return h == undefined ?
					( this.length ? jQuery.css( this[0], n ) : null ) :
					this.css( n, h );
			};
		});

	},

	/**
	 * A generic iterator function, which can be used to seemlessly
	 * iterate over both objects and arrays. This function is not the same
	 * as $().each() - which is used to iterate, exclusively, over a jQuery
	 * object. This function can be used to iterate over anything.
	 *
	 * @example $.each( [0,1,2], function(i){
	 *   alert( "Item #" + i + ": " + this );
	 * });
	 * @desc This is an example of iterating over the items in an array, accessing both the current item and its index.
	 *
	 * @example $.each( { name: "John", lang: "JS" }, function(i){
	 *   alert( "Name: " + i + ", Value: " + this );
	 * });
	 * @desc This is an example of iterating over the properties in an Object, accessing both the current item and its key.
	 *
	 * @name $.each
	 * @param Object obj The object, or array, to iterate over.
	 * @param Function fn The function that will be executed on every object.
	 * @type Object
	 * @cat Javascript
	 */
	each: function( obj, fn, args ) {
		if ( obj.length == undefined )
			for ( var i in obj )
				fn.apply( obj[i], args || [i, obj[i]] );
		else
			for ( var i = 0; i < obj.length; i++ )
				if ( fn.apply( obj[i], args || [i, obj[i]] ) === false ) break;
		return obj;
	},

	className: {
		add: function(o,c){
			if (jQuery.className.has(o,c)) return;
			o.className += ( o.className ? " " : "" ) + c;
		},
		remove: function(o,c){
			if( !c ) {
				o.className = "";
			} else {
				var classes = o.className.split(" ");
				for(var i=0; i<classes.length; i++) {
					if(classes[i] == c) {
						classes.splice(i, 1);
						break;
					}
				}
				o.className = classes.join(' ');
			}
		},
		has: function(e,a) {
			if ( e.className != undefined )
				e = e.className;
			return new RegExp("(^|\\s)" + a + "(\\s|$)").test(e);
		}
	},

	/**
	 * Swap in/out style options.
	 * @private
	 */
	swap: function(e,o,f) {
		for ( var i in o ) {
			e.style["old"+i] = e.style[i];
			e.style[i] = o[i];
		}
		f.apply( e, [] );
		for ( var i in o )
			e.style[i] = e.style["old"+i];
	},

	css: function(e,p) {
		if ( p == "height" || p == "width" ) {
			var old = {}, oHeight, oWidth, d = ["Top","Bottom","Right","Left"];

			for ( var i in d ) {
				old["padding" + d[i]] = 0;
				old["border" + d[i] + "Width"] = 0;
			}

			jQuery.swap( e, old, function() {
				if (jQuery.css(e,"display") != "none") {
					oHeight = e.offsetHeight;
					oWidth = e.offsetWidth;
				} else {
					e = jQuery(e.cloneNode(true))
						.find(":radio").removeAttr("checked").end()
						.css({
							visibility: "hidden", position: "absolute", display: "block", right: "0", left: "0"
						}).appendTo(e.parentNode)[0];

					var parPos = jQuery.css(e.parentNode,"position");
					if ( parPos == "" || parPos == "static" )
						e.parentNode.style.position = "relative";

					oHeight = e.clientHeight;
					oWidth = e.clientWidth;

					if ( parPos == "" || parPos == "static" )
						e.parentNode.style.position = "static";

					e.parentNode.removeChild(e);
				}
			});

			return p == "height" ? oHeight : oWidth;
		}

		return jQuery.curCSS( e, p );
	},

	curCSS: function(elem, prop, force) {
		var ret;
		
		if (prop == 'opacity' && jQuery.browser.msie)
			return jQuery.attr(elem.style, 'opacity');
			
		if (prop == "float" || prop == "cssFloat")
		    prop = jQuery.browser.msie ? "styleFloat" : "cssFloat";

		if (!force && elem.style[prop]) {

			ret = elem.style[prop];

		} else if (elem.currentStyle) {

			var newProp = prop.replace(/\-(\w)/g,function(m,c){return c.toUpperCase();});
			ret = elem.currentStyle[prop] || elem.currentStyle[newProp];

		} else if (document.defaultView && document.defaultView.getComputedStyle) {

			if (prop == "cssFloat" || prop == "styleFloat")
				prop = "float";

			prop = prop.replace(/([A-Z])/g,"-$1").toLowerCase();
			var cur = document.defaultView.getComputedStyle(elem, null);

			if ( cur )
				ret = cur.getPropertyValue(prop);
			else if ( prop == 'display' )
				ret = 'none';
			else
				jQuery.swap(elem, { display: 'block' }, function() {
					ret = document.defaultView.getComputedStyle(this,null).getPropertyValue(prop);
				});

		}

		return ret;
	},
	
	clean: function(a) {
		var r = [];
		for ( var i = 0; i < a.length; i++ ) {
			var arg = a[i];
			if ( arg.constructor == String ) { // Convert html string into DOM nodes
				// Trim whitespace, otherwise indexOf won't work as expected
				var s = jQuery.trim(arg), div = document.createElement("div"), wrap = [0,"",""];

				if ( !s.indexOf("<opt") ) // option or optgroup
					wrap = [1, "<select>", "</select>"];
				else if ( !s.indexOf("<thead") || !s.indexOf("<tbody") )
					wrap = [1, "<table>", "</table>"];
				else if ( !s.indexOf("<tr") )
					wrap = [2, "<table>", "</table>"];	// tbody auto-inserted
				else if ( !s.indexOf("<td") || !s.indexOf("<th") )
					wrap = [3, "<table><tbody><tr>", "</tr></tbody></table>"];

				// Go to html and back, then peel off extra wrappers
				div.innerHTML = wrap[1] + s + wrap[2];
				while ( wrap[0]-- ) div = div.firstChild;
				arg = div.childNodes;
			} 
			
			
			if ( arg.length != undefined && ( (jQuery.browser.safari && typeof arg == 'function') || !arg.nodeType ) ) // Safari reports typeof on a DOM NodeList to be a function
				for ( var n = 0; n < arg.length; n++ ) // Handles Array, jQuery, DOM NodeList collections
					r.push(arg[n]);
			else
				r.push(	arg.nodeType ? arg : document.createTextNode(arg.toString()) );
		}

		return r;
	},

	expr: {
		"": "m[2]== '*'||a.nodeName.toUpperCase()==m[2].toUpperCase()",
		"#": "a.getAttribute('id')&&a.getAttribute('id')==m[2]",
		":": {
			// Position Checks
			lt: "i<m[3]-0",
			gt: "i>m[3]-0",
			nth: "m[3]-0==i",
			eq: "m[3]-0==i",
			first: "i==0",
			last: "i==r.length-1",
			even: "i%2==0",
			odd: "i%2",

			// Child Checks
			"nth-child": "jQuery.sibling(a,m[3]).cur",
			"first-child": "jQuery.sibling(a,0).cur",
			"last-child": "jQuery.sibling(a,0).last",
			"only-child": "jQuery.sibling(a).length==1",

			// Parent Checks
			parent: "a.childNodes.length",
			empty: "!a.childNodes.length",

			// Text Check
			contains: "jQuery.fn.text.apply([a]).indexOf(m[3])>=0",

			// Visibility
			visible: "a.type!='hidden'&&jQuery.css(a,'display')!='none'&&jQuery.css(a,'visibility')!='hidden'",
			hidden: "a.type=='hidden'||jQuery.css(a,'display')=='none'||jQuery.css(a,'visibility')=='hidden'",

			// Form attributes
			enabled: "!a.disabled",
			disabled: "a.disabled",
			checked: "a.checked",
			selected: "a.selected || jQuery.attr(a, 'selected')",

			// Form elements
			text: "a.type=='text'",
			radio: "a.type=='radio'",
			checkbox: "a.type=='checkbox'",
			file: "a.type=='file'",
			password: "a.type=='password'",
			submit: "a.type=='submit'",
			image: "a.type=='image'",
			reset: "a.type=='reset'",
			button: "a.type=='button'",
			input: "a.nodeName.toLowerCase().match(/input|select|textarea|button/)"
		},
		".": "jQuery.className.has(a,m[2])",
		"@": {
			"=": "z==m[4]",
			"!=": "z!=m[4]",
			"^=": "z && !z.indexOf(m[4])",
			"$=": "z && z.substr(z.length - m[4].length,m[4].length)==m[4]",
			"*=": "z && z.indexOf(m[4])>=0",
			"": "z"
		},
		"[": "jQuery.find(m[2],a).length"
	},

	token: [
		"\\.\\.|/\\.\\.", "a.parentNode",
		">|/", "jQuery.sibling(a.firstChild)",
		"\\+", "jQuery.sibling(a).next",
		"~", function(a){
			var r = [];
			var s = jQuery.sibling(a);
			if ( s.n > 0 )
				for ( var i = s.n; i < s.length; i++ )
					r.push( s[i] );
			return r;
		}
	],

	/**
	 *
	 * @test t( "Element Selector", "div", ["main","foo"] );
	 * t( "Element Selector", "body", ["body"] );
	 * t( "Element Selector", "html", ["html"] );
	 * ok( $("*").size() >= 30, "Element Selector" );
	 * t( "Parent Element", "div div", ["foo"] );
	 *
	 * t( "ID Selector", "#body", ["body"] );
	 * t( "ID Selector w/ Element", "body#body", ["body"] );
	 * t( "ID Selector w/ Element", "ul#first", [] );
	 *
	 * t( "Class Selector", ".blog", ["mark","simon"] );
	 * t( "Class Selector", ".blog.link", ["simon"] );
	 * t( "Class Selector w/ Element", "a.blog", ["mark","simon"] );
	 * t( "Parent Class Selector", "p .blog", ["mark","simon"] );
	 *
	 * t( "Comma Support", "a.blog, div", ["mark","simon","main","foo"] );
	 * t( "Comma Support", "a.blog , div", ["mark","simon","main","foo"] );
	 * t( "Comma Support", "a.blog ,div", ["mark","simon","main","foo"] );
	 * t( "Comma Support", "a.blog,div", ["mark","simon","main","foo"] );
	 *
	 * t( "Child", "p > a", ["simon1","google","groups","mark","yahoo","simon"] );
	 * t( "Child", "p> a", ["simon1","google","groups","mark","yahoo","simon"] );
	 * t( "Child", "p >a", ["simon1","google","groups","mark","yahoo","simon"] );
	 * t( "Child", "p>a", ["simon1","google","groups","mark","yahoo","simon"] );
	 * t( "Child w/ Class", "p > a.blog", ["mark","simon"] );
	 * t( "All Children", "code > *", ["anchor1","anchor2"] );
	 * t( "All Grandchildren", "p > * > *", ["anchor1","anchor2"] );
	 * t( "Adjacent", "a + a", ["groups"] );
	 * t( "Adjacent", "a +a", ["groups"] );
	 * t( "Adjacent", "a+ a", ["groups"] );
	 * t( "Adjacent", "a+a", ["groups"] );
	 * t( "Adjacent", "p + p", ["ap","en","sap"] );
	 * t( "Comma, Child, and Adjacent", "a + a, code > a", ["groups","anchor1","anchor2"] );
	 * t( "First Child", "p:first-child", ["firstp","sndp"] );
	 * t( "Attribute Exists", "a[@title]", ["google"] );
	 * t( "Attribute Exists", "*[@title]", ["google"] );
	 * t( "Attribute Exists", "[@title]", ["google"] );
	 *
	 * t( "Attribute Equals", "a[@rel='bookmark']", ["simon1"] );
	 * t( "Attribute Equals", 'a[@rel="bookmark"]', ["simon1"] );
	 * t( "Attribute Equals", "a[@rel=bookmark]", ["simon1"] );
	 * t( "Multiple Attribute Equals", "input[@type='hidden'],input[@type='radio']", ["hidden1","radio1","radio2"] );
	 * t( "Multiple Attribute Equals", "input[@type=\"hidden\"],input[@type='radio']", ["hidden1","radio1","radio2"] );
	 * t( "Multiple Attribute Equals", "input[@type=hidden],input[@type=radio]", ["hidden1","radio1","radio2"] );
	 *
	 * t( "Attribute Begins With", "a[@href ^= 'http://www']", ["google","yahoo"] );
	 * t( "Attribute Ends With", "a[@href $= 'org/']", ["mark"] );
	 * t( "Attribute Contains", "a[@href *= 'google']", ["google","groups"] );
	 * t( "First Child", "p:first-child", ["firstp","sndp"] );
	 * t( "Last Child", "p:last-child", ["sap"] );
	 * t( "Only Child", "a:only-child", ["simon1","anchor1","yahoo","anchor2"] );
	 * t( "Empty", "ul:empty", ["firstUL"] );
	 * t( "Enabled UI Element", "input:enabled", ["text1","radio1","radio2","check1","check2","hidden1","hidden2","name"] );
	 * t( "Disabled UI Element", "input:disabled", ["text2"] );
	 * t( "Checked UI Element", "input:checked", ["radio2","check1"] );
	 * t( "Selected Option Element", "option:selected", ["option1a","option2d","option3b","option3c"] );
	 * t( "Text Contains", "a:contains('Google')", ["google","groups"] );
	 * t( "Text Contains", "a:contains('Google Groups')", ["groups"] );
	 * t( "Element Preceded By", "p ~ div", ["foo"] );
	 * t( "Not", "a.blog:not(.link)", ["mark"] );
	 *
	 * ok( jQuery.find("//*").length >= 30, "All Elements (//*)" );
	 * t( "All Div Elements", "//div", ["main","foo"] );
	 * t( "Absolute Path", "/html/body", ["body"] );
	 * t( "Absolute Path w/ *", "/* /body", ["body"] );
	 * t( "Long Absolute Path", "/html/body/dl/div/div/p", ["sndp","en","sap"] );
	 * t( "Absolute and Relative Paths", "/html//div", ["main","foo"] );
	 * t( "All Children, Explicit", "//code/*", ["anchor1","anchor2"] );
	 * t( "All Children, Implicit", "//code/", ["anchor1","anchor2"] );
	 * t( "Attribute Exists", "//a[@title]", ["google"] );
	 * t( "Attribute Equals", "//a[@rel='bookmark']", ["simon1"] );
	 * t( "Parent Axis", "//p/..", ["main","foo"] );
	 * t( "Sibling Axis", "//p/../", ["firstp","ap","foo","first","firstUL","empty","form","floatTest","sndp","en","sap"] );
	 * t( "Sibling Axis", "//p/../*", ["firstp","ap","foo","first","firstUL","empty","form","floatTest","sndp","en","sap"] );
	 * t( "Has Children", "//p[a]", ["firstp","ap","en","sap"] );
	 *
	 * t( "nth Element", "p:nth(1)", ["ap"] );
	 * t( "First Element", "p:first", ["firstp"] );
	 * t( "Last Element", "p:last", ["first"] );
	 * t( "Even Elements", "p:even", ["firstp","sndp","sap"] );
	 * t( "Odd Elements", "p:odd", ["ap","en","first"] );
	 * t( "Position Equals", "p:eq(1)", ["ap"] );
	 * t( "Position Greater Than", "p:gt(0)", ["ap","sndp","en","sap","first"] );
	 * t( "Position Less Than", "p:lt(3)", ["firstp","ap","sndp"] );
	 * t( "Is A Parent", "p:parent", ["firstp","ap","sndp","en","sap","first"] );
	 * t( "Is Visible", "input:visible", ["text1","text2","radio1","radio2","check1","check2","name"] );
	 * t( "Is Hidden", "input:hidden", ["hidden1","hidden2"] );
	 *
	 * t( "Grouped Form Elements", "input[@name='foo[bar]']", ["hidden2"] );
	 *
	 * t( "All Children of ID", "#foo/*", ["sndp", "en", "sap"]  );
	 * t( "All Children of ID with no children", "#firstUL/*", []  );
	 *
	 * t( "Form element :input", ":input", ["text1", "text2", "radio1", "radio2", "check1", "check2", "hidden1", "hidden2", "name", "button", "area1", "select1", "select2", "select3"] );
	 * t( "Form element :radio", ":radio", ["radio1", "radio2"] );
	 * t( "Form element :checkbox", ":checkbox", ["check1", "check2"] );
	 * t( "Form element :text", ":text", ["text1", "text2", "hidden2", "name"] );
	 * t( "Form element :radio:checked", ":radio:checked", ["radio2"] );
	 * t( "Form element :checkbox:checked", ":checkbox:checked", ["check1"] );
	 * t( "Form element :checkbox:checked, :radio:checked", ":checkbox:checked, :radio:checked", ["check1", "radio2"] );
	 *
	 * t( ":not() Existing attribute", "select:not([@multiple])", ["select1", "select2"]);
	 * t( ":not() Equals attribute", "select:not([@name=select1])", ["select2", "select3"]);
	 * t( ":not() Equals quoted attribute", "select:not([@name='select1'])", ["select2", "select3"]);
	 *
	 * @name $.find
	 * @type Array<Element>
	 * @private
	 * @cat Core
	 */
	find: function( t, context ) {
		// Make sure that the context is a DOM Element
		if ( context && context.nodeType == undefined )
			context = null;

		// Set the correct context (if none is provided)
		context = context || jQuery.context || document;

		if ( t.constructor != String ) return [t];

		if ( !t.indexOf("//") ) {
			context = context.documentElement;
			t = t.substr(2,t.length);
		} else if ( !t.indexOf("/") ) {
			context = context.documentElement;
			t = t.substr(1,t.length);
			// FIX Assume the root element is right :(
			if ( t.indexOf("/") >= 1 )
				t = t.substr(t.indexOf("/"),t.length);
		}

		var ret = [context];
		var done = [];
		var last = null;

		while ( t.length > 0 && last != t ) {
			var r = [];
			last = t;

			t = jQuery.trim(t).replace( /^\/\//i, "" );

			var foundToken = false;

			for ( var i = 0; i < jQuery.token.length; i += 2 ) {
				if ( foundToken ) continue;

				var re = new RegExp("^(" + jQuery.token[i] + ")");
				var m = re.exec(t);

				if ( m ) {
					r = ret = jQuery.map( ret, jQuery.token[i+1] );
					t = jQuery.trim( t.replace( re, "" ) );
					foundToken = true;
				}
			}

			if ( !foundToken ) {
				if ( !t.indexOf(",") || !t.indexOf("|") ) {
					if ( ret[0] == context ) ret.shift();
					done = jQuery.merge( done, ret );
					r = ret = [context];
					t = " " + t.substr(1,t.length);
				} else {
					var re2 = /^([#.]?)([a-z0-9\\*_-]*)/i;
					var m = re2.exec(t);

					if ( m[1] == "#" ) {
						// Ummm, should make this work in all XML docs
						var oid = document.getElementById(m[2]);
						r = ret = oid ? [oid] : [];
						t = t.replace( re2, "" );
					} else {
						if ( !m[2] || m[1] == "." ) m[2] = "*";

						for ( var i = 0; i < ret.length; i++ )
							r = jQuery.merge( r,
								m[2] == "*" ?
									jQuery.getAll(ret[i]) :
									ret[i].getElementsByTagName(m[2])
							);
					}
				}

			}

			if ( t ) {
				var val = jQuery.filter(t,r);
				ret = r = val.r;
				t = jQuery.trim(val.t);
			}
		}

		if ( ret && ret[0] == context ) ret.shift();
		done = jQuery.merge( done, ret );

		return done;
	},

	getAll: function(o,r) {
		r = r || [];
		var s = o.childNodes;
		for ( var i = 0; i < s.length; i++ )
			if ( s[i].nodeType == 1 ) {
				r.push( s[i] );
				jQuery.getAll( s[i], r );
			}
		return r;
	},

	attr: function(elem, name, value){
		var fix = {
			"for": "htmlFor",
			"class": "className",
			"float": jQuery.browser.msie ? "styleFloat" : "cssFloat",
			cssFloat: jQuery.browser.msie ? "styleFloat" : "cssFloat",
			innerHTML: "innerHTML",
			className: "className",
			value: "value",
			disabled: "disabled",
			checked: "checked",
			readonly: "readOnly"
		};
		
		// IE actually uses filters for opacity ... elem is actually elem.style
		if (name == "opacity" && jQuery.browser.msie && value != undefined) {
			// IE has trouble with opacity if it does not have layout
			// Would prefer to check element.hasLayout first but don't have access to the element here
			elem['zoom'] = 1; 
			if (value == 1) // Remove filter to avoid more IE weirdness
				return elem["filter"] = elem["filter"].replace(/alpha\([^\)]*\)/gi,"");
			else
				return elem["filter"] = elem["filter"].replace(/alpha\([^\)]*\)/gi,"") + "alpha(opacity=" + value * 100 + ")";
		} else if (name == "opacity" && jQuery.browser.msie) {
			return elem["filter"] ? parseFloat( elem["filter"].match(/alpha\(opacity=(.*)\)/)[1] )/100 : 1;
		}
		
		// Mozilla doesn't play well with opacity 1
		if (name == "opacity" && jQuery.browser.mozilla && value == 1) value = 0.9999;

		if ( fix[name] ) {
			if ( value != undefined ) elem[fix[name]] = value;
			return elem[fix[name]];
		} else if( value == undefined && jQuery.browser.msie && elem.nodeName && elem.nodeName.toUpperCase() == 'FORM' && (name == 'action' || name == 'method') ) {
			return elem.getAttributeNode(name).nodeValue;
		} else if ( elem.tagName ) { // IE elem.getAttribute passes even for style
			if ( value != undefined ) elem.setAttribute( name, value );
			return elem.getAttribute( name );
		} else {
			name = name.replace(/-([a-z])/ig,function(z,b){return b.toUpperCase();});
			if ( value != undefined ) elem[name] = value;
			return elem[name];
		}
	},

	// The regular expressions that power the parsing engine
	parse: [
		// Match: [@value='test'], [@foo]
		"\\[ *(@)S *([!*$^=]*) *('?\"?)(.*?)\\4 *\\]",

		// Match: [div], [div p]
		"(\\[)\s*(.*?)\s*\\]",

		// Match: :contains('foo')
		"(:)S\\(\"?'?([^\\)]*?)\"?'?\\)",

		// Match: :even, :last-chlid
		"([:.#]*)S"
	],

	filter: function(t,r,not) {
		// Figure out if we're doing regular, or inverse, filtering
		var g = not !== false ? jQuery.grep :
			function(a,f) {return jQuery.grep(a,f,true);};

		while ( t && /^[a-z[({<*:.#]/i.test(t) ) {

			var p = jQuery.parse;

			for ( var i = 0; i < p.length; i++ ) {
		
				// Look for, and replace, string-like sequences
				// and finally build a regexp out of it
				var re = new RegExp(
					"^" + p[i].replace("S", "([a-z*_-][a-z0-9_-]*)"), "i" );

				var m = re.exec( t );

				if ( m ) {
					// Re-organize the first match
					if ( !i )
						m = ["",m[1], m[3], m[2], m[5]];

					// Remove what we just matched
					t = t.replace( re, "" );

					break;
				}
			}

			// :not() is a special case that can be optimized by
			// keeping it out of the expression list
			if ( m[1] == ":" && m[2] == "not" )
				r = jQuery.filter(m[3],r,false).r;

			// Otherwise, find the expression to execute
			else {
				var f = jQuery.expr[m[1]];
				if ( f.constructor != String )
					f = jQuery.expr[m[1]][m[2]];

				// Build a custom macro to enclose it
				eval("f = function(a,i){" +
					( m[1] == "@" ? "z=jQuery.attr(a,m[3]);" : "" ) +
					"return " + f + "}");

				// Execute it against the current filter
				r = g( r, f );
			}
		}

		// Return an array of filtered elements (r)
		// and the modified expression string (t)
		return { r: r, t: t };
	},

	/**
	 * Remove the whitespace from the beginning and end of a string.
	 *
	 * @example $.trim("  hello, how are you?  ");
	 * @result "hello, how are you?"
	 *
	 * @name $.trim
	 * @type String
	 * @param String str The string to trim.
	 * @cat Javascript
	 */
	trim: function(t){
		return t.replace(/^\s+|\s+$/g, "");
	},

	/**
	 * All ancestors of a given element.
	 *
	 * @private
	 * @name $.parents
	 * @type Array<Element>
	 * @param Element elem The element to find the ancestors of.
	 * @cat DOM/Traversing
	 */
	parents: function( elem ){
		var matched = [];
		var cur = elem.parentNode;
		while ( cur && cur != document ) {
			matched.push( cur );
			cur = cur.parentNode;
		}
		return matched;
	},

	/**
	 * All elements on a specified axis.
	 *
	 * @private
	 * @name $.sibling
	 * @type Array
	 * @param Element elem The element to find all the siblings of (including itself).
	 * @cat DOM/Traversing
	 */
	sibling: function(elem, pos, not) {
		var elems = [];
		
		if(elem) {
			var siblings = elem.parentNode.childNodes;
			for ( var i = 0; i < siblings.length; i++ ) {
				if ( not === true && siblings[i] == elem ) continue;
	
				if ( siblings[i].nodeType == 1 )
					elems.push( siblings[i] );
				if ( siblings[i] == elem )
					elems.n = elems.length - 1;
			}
		}

		return jQuery.extend( elems, {
			last: elems.n == elems.length - 1,
			cur: pos == "even" && elems.n % 2 == 0 || pos == "odd" && elems.n % 2 || elems[pos] == elem,
			prev: elems[elems.n - 1],
			next: elems[elems.n + 1]
		});
	},

	/**
	 * Merge two arrays together, removing all duplicates. The final order
	 * or the new array is: All the results from the first array, followed
	 * by the unique results from the second array.
	 *
	 * @example $.merge( [0,1,2], [2,3,4] )
	 * @result [0,1,2,3,4]
	 *
	 * @example $.merge( [3,2,1], [4,3,2] )
	 * @result [3,2,1,4]
	 *
	 * @name $.merge
	 * @type Array
	 * @param Array first The first array to merge.
	 * @param Array second The second array to merge.
	 * @cat Javascript
	 */
	merge: function(first, second) {
		var result = [];

		// Move b over to the new array (this helps to avoid
		// StaticNodeList instances)
		for ( var k = 0; k < first.length; k++ )
			result[k] = first[k];

		// Now check for duplicates between a and b and only
		// add the unique items
		for ( var i = 0; i < second.length; i++ ) {
			var noCollision = true;

			// The collision-checking process
			for ( var j = 0; j < first.length; j++ )
				if ( second[i] == first[j] )
					noCollision = false;

			// If the item is unique, add it
			if ( noCollision )
				result.push( second[i] );
		}

		return result;
	},

	/**
	 * Filter items out of an array, by using a filter function.
	 * The specified function will be passed two arguments: The
	 * current array item and the index of the item in the array. The
	 * function should return 'true' if you wish to keep the item in
	 * the array, false if it should be removed.
	 *
	 * @example $.grep( [0,1,2], function(i){
	 *   return i > 0;
	 * });
	 * @result [1, 2]
	 *
	 * @name $.grep
	 * @type Array
	 * @param Array array The Array to find items in.
	 * @param Function fn The function to process each item against.
	 * @param Boolean inv Invert the selection - select the opposite of the function.
	 * @cat Javascript
	 */
	grep: function(elems, fn, inv) {
		// If a string is passed in for the function, make a function
		// for it (a handy shortcut)
		if ( fn.constructor == String )
			fn = new Function("a","i","return " + fn);

		var result = [];

		// Go through the array, only saving the items
		// that pass the validator function
		for ( var i = 0; i < elems.length; i++ )
			if ( !inv && fn(elems[i],i) || inv && !fn(elems[i],i) )
				result.push( elems[i] );

		return result;
	},

	/**
	 * Translate all items in an array to another array of items. 
	 * The translation function that is provided to this method is 
	 * called for each item in the array and is passed one argument: 
	 * The item to be translated. The function can then return:
	 * The translated value, 'null' (to remove the item), or 
	 * an array of values - which will be flattened into the full array.
	 *
	 * @example $.map( [0,1,2], function(i){
	 *   return i + 4;
	 * });
	 * @result [4, 5, 6]
	 *
	 * @example $.map( [0,1,2], function(i){
	 *   return i > 0 ? i + 1 : null;
	 * });
	 * @result [2, 3]
	 * 
	 * @example $.map( [0,1,2], function(i){
	 *   return [ i, i + 1 ];
	 * });
	 * @result [0, 1, 1, 2, 2, 3]
	 *
	 * @name $.map
	 * @type Array
	 * @param Array array The Array to translate.
	 * @param Function fn The function to process each item against.
	 * @cat Javascript
	 */
	map: function(elems, fn) {
		// If a string is passed in for the function, make a function
		// for it (a handy shortcut)
		if ( fn.constructor == String )
			fn = new Function("a","return " + fn);

		var result = [];

		// Go through the array, translating each of the items to their
		// new value (or values).
		for ( var i = 0; i < elems.length; i++ ) {
			var val = fn(elems[i],i);

			if ( val !== null && val != undefined ) {
				if ( val.constructor != Array ) val = [val];
				result = jQuery.merge( result, val );
			}
		}

		return result;
	},

	/*
	 * A number of helper functions used for managing events.
	 * Many of the ideas behind this code orignated from Dean Edwards' addEvent library.
	 */
	event: {

		// Bind an event to an element
		// Original by Dean Edwards
		add: function(element, type, handler) {
			// For whatever reason, IE has trouble passing the window object
			// around, causing it to be cloned in the process
			if ( jQuery.browser.msie && element.setInterval != undefined )
				element = window;

			// Make sure that the function being executed has a unique ID
			if ( !handler.guid )
				handler.guid = this.guid++;

			// Init the element's event structure
			if (!element.events)
				element.events = {};

			// Get the current list of functions bound to this event
			var handlers = element.events[type];

			// If it hasn't been initialized yet
			if (!handlers) {
				// Init the event handler queue
				handlers = element.events[type] = {};

				// Remember an existing handler, if it's already there
				if (element["on" + type])
					handlers[0] = element["on" + type];
			}

			// Add the function to the element's handler list
			handlers[handler.guid] = handler;

			// And bind the global event handler to the element
			element["on" + type] = this.handle;

			// Remember the function in a global list (for triggering)
			if (!this.global[type])
				this.global[type] = [];
			this.global[type].push( element );
		},

		guid: 1,
		global: {},

		// Detach an event or set of events from an element
		remove: function(element, type, handler) {
			if (element.events)
				if (type && element.events[type])
					if ( handler )
						delete element.events[type][handler.guid];
					else
						for ( var i in element.events[type] )
							delete element.events[type][i];
				else
					for ( var j in element.events )
						this.remove( element, j );
		},

		trigger: function(type,data,element) {
			// Clone the incoming data, if any
			data = $.merge([], data || []);

			// Handle a global trigger
			if ( !element ) {
				var g = this.global[type];
				if ( g )
					for ( var i = 0; i < g.length; i++ )
						this.trigger( type, data, g[i] );

			// Handle triggering a single element
			} else if ( element["on" + type] ) {
				// Pass along a fake event
				data.unshift( this.fix({ type: type, target: element }) );

				// Trigger the event
				element["on" + type].apply( element, data );
			}
		},

		handle: function(event) {
			if ( typeof jQuery == "undefined" ) return false;

			event = jQuery.event.fix( event || window.event || {} ); // Empty object is for triggered events with no data

			// If no correct event was found, fail
			if ( !event ) return false;

			var returnValue = true;

			var c = this.events[event.type];

			var args = [].slice.call( arguments, 1 );
			args.unshift( event );

			for ( var j in c ) {
				if ( c[j].apply( this, args ) === false ) {
					event.preventDefault();
					event.stopPropagation();
					returnValue = false;
				}
			}

			// Clean up added properties in IE to prevent memory leak
			if (jQuery.browser.msie) event.target = event.preventDefault = event.stopPropagation = null;

			return returnValue;
		},

		fix: function(event) {
			// check IE
			if(jQuery.browser.msie) {
				// fix target property
				event.target = event.srcElement;
				
			// check safari and if target is a textnode
			} else if(jQuery.browser.safari && event.target.nodeType == 3) {
				// target is readonly, clone the event object
				event = jQuery.extend({}, event);
				// get parentnode from textnode
				event.target = event.target.parentNode;
			}
			
			// fix preventDefault and stopPropagation
			if (!event.preventDefault)
				event.preventDefault = function() {
					this.returnValue = false;
				};
				
			if (!event.stopPropagation)
				event.stopPropagation = function() {
					this.cancelBubble = true;
				};
			
			return event;
		}
	}
});

/**
 * Contains flags for the useragent, read from navigator.userAgent.
 * Available flags are: safari, opera, msie, mozilla
 * This property is available before the DOM is ready, therefore you can
 * use it to add ready events only for certain browsers.
 *
 * See <a href="http://davecardwell.co.uk/geekery/javascript/jquery/jqbrowser/">
 * jQBrowser plugin</a> for advanced browser detection:
 *
 * @example $.browser.msie
 * @desc returns true if the current useragent is some version of microsoft's internet explorer
 *
 * @example if($.browser.safari) { $( function() { alert("this is safari!"); } ); }
 * @desc Alerts "this is safari!" only for safari browsers
 *
 * @name $.browser
 * @type Boolean
 * @cat Javascript
 */
new function() {
	var b = navigator.userAgent.toLowerCase();

	// Figure out what browser is being used
	jQuery.browser = {
		safari: /webkit/.test(b),
		opera: /opera/.test(b),
		msie: /msie/.test(b) && !/opera/.test(b),
		mozilla: /mozilla/.test(b) && !/(compatible|webkit)/.test(b)
	};

	// Check to see if the W3C box model is being used
	jQuery.boxModel = !jQuery.browser.msie || document.compatMode == "CSS1Compat";
};

jQuery.macros = {
	to: {
		/**
		 * Append all of the matched elements to another, specified, set of elements.
		 * This operation is, essentially, the reverse of doing a regular
		 * $(A).append(B), in that instead of appending B to A, you're appending
		 * A to B.
		 *
		 * @example $("p").appendTo("#foo");
		 * @before <p>I would like to say: </p><div id="foo"></div>
		 * @result <div id="foo"><p>I would like to say: </p></div>
		 *
		 * @name appendTo
		 * @type jQuery
		 * @param String expr A jQuery expression of elements to match.
		 * @cat DOM/Manipulation
		 */
		appendTo: "append",

		/**
		 * Prepend all of the matched elements to another, specified, set of elements.
		 * This operation is, essentially, the reverse of doing a regular
		 * $(A).prepend(B), in that instead of prepending B to A, you're prepending
		 * A to B.
		 *
		 * @example $("p").prependTo("#foo");
		 * @before <p>I would like to say: </p><div id="foo"><b>Hello</b></div>
		 * @result <div id="foo"><p>I would like to say: </p><b>Hello</b></div>
		 *
		 * @name prependTo
		 * @type jQuery
		 * @param String expr A jQuery expression of elements to match.
		 * @cat DOM/Manipulation
		 */
		prependTo: "prepend",

		/**
		 * Insert all of the matched elements before another, specified, set of elements.
		 * This operation is, essentially, the reverse of doing a regular
		 * $(A).before(B), in that instead of inserting B before A, you're inserting
		 * A before B.
		 *
		 * @example $("p").insertBefore("#foo");
		 * @before <div id="foo">Hello</div><p>I would like to say: </p>
		 * @result <p>I would like to say: </p><div id="foo">Hello</div>
		 *
		 * @name insertBefore
		 * @type jQuery
		 * @param String expr A jQuery expression of elements to match.
		 * @cat DOM/Manipulation
		 */
		insertBefore: "before",

		/**
		 * Insert all of the matched elements after another, specified, set of elements.
		 * This operation is, essentially, the reverse of doing a regular
		 * $(A).after(B), in that instead of inserting B after A, you're inserting
		 * A after B.
		 *
		 * @example $("p").insertAfter("#foo");
		 * @before <p>I would like to say: </p><div id="foo">Hello</div>
		 * @result <div id="foo">Hello</div><p>I would like to say: </p>
		 *
		 * @name insertAfter
		 * @type jQuery
		 * @param String expr A jQuery expression of elements to match.
		 * @cat DOM/Manipulation
		 */
		insertAfter: "after"
	},

	/**
	 * Get the current CSS width of the first matched element.
	 *
	 * @example $("p").width();
	 * @before <p>This is just a test.</p>
	 * @result "300px"
	 *
	 * @name width
	 * @type String
	 * @cat CSS
	 */

	/**
	 * Set the CSS width of every matched element. Be sure to include
	 * the "px" (or other unit of measurement) after the number that you
	 * specify, otherwise you might get strange results.
	 *
	 * @example $("p").width("20px");
	 * @before <p>This is just a test.</p>
	 * @result <p style="width:20px;">This is just a test.</p>
	 *
	 * @name width
	 * @type jQuery
	 * @param String val Set the CSS property to the specified value.
	 * @cat CSS
	 */

	/**
	 * Get the current CSS height of the first matched element.
	 *
	 * @example $("p").height();
	 * @before <p>This is just a test.</p>
	 * @result "14px"
	 *
	 * @name height
	 * @type String
	 * @cat CSS
	 */

	/**
	 * Set the CSS height of every matched element. Be sure to include
	 * the "px" (or other unit of measurement) after the number that you
	 * specify, otherwise you might get strange results.
	 *
	 * @example $("p").height("20px");
	 * @before <p>This is just a test.</p>
	 * @result <p style="height:20px;">This is just a test.</p>
	 *
	 * @name height
	 * @type jQuery
	 * @param String val Set the CSS property to the specified value.
	 * @cat CSS
	 */

	/**
	 * Get the current CSS top of the first matched element.
	 *
	 * @example $("p").top();
	 * @before <p>This is just a test.</p>
	 * @result "0px"
	 *
	 * @name top
	 * @type String
	 * @cat CSS
	 */

	/**
	 * Set the CSS top of every matched element. Be sure to include
	 * the "px" (or other unit of measurement) after the number that you
	 * specify, otherwise you might get strange results.
	 *
	 * @example $("p").top("20px");
	 * @before <p>This is just a test.</p>
	 * @result <p style="top:20px;">This is just a test.</p>
	 *
	 * @name top
	 * @type jQuery
	 * @param String val Set the CSS property to the specified value.
	 * @cat CSS
	 */

	/**
	 * Get the current CSS left of the first matched element.
	 *
	 * @example $("p").left();
	 * @before <p>This is just a test.</p>
	 * @result "0px"
	 *
	 * @name left
	 * @type String
	 * @cat CSS
	 */

	/**
	 * Set the CSS left of every matched element. Be sure to include
	 * the "px" (or other unit of measurement) after the number that you
	 * specify, otherwise you might get strange results.
	 *
	 * @example $("p").left("20px");
	 * @before <p>This is just a test.</p>
	 * @result <p style="left:20px;">This is just a test.</p>
	 *
	 * @name left
	 * @type jQuery
	 * @param String val Set the CSS property to the specified value.
	 * @cat CSS
	 */

	/**
	 * Get the current CSS position of the first matched element.
	 *
	 * @example $("p").position();
	 * @before <p>This is just a test.</p>
	 * @result "static"
	 *
	 * @name position
	 * @type String
	 * @cat CSS
	 */

	/**
	 * Set the CSS position of every matched element.
	 *
	 * @example $("p").position("relative");
	 * @before <p>This is just a test.</p>
	 * @result <p style="position:relative;">This is just a test.</p>
	 *
	 * @name position
	 * @type jQuery
	 * @param String val Set the CSS property to the specified value.
	 * @cat CSS
	 */

	/**
	 * Get the current CSS float of the first matched element.
	 *
	 * @example $("p").float();
	 * @before <p>This is just a test.</p>
	 * @result "none"
	 *
	 * @name float
	 * @type String
	 * @cat CSS
	 */

	/**
	 * Set the CSS float of every matched element.
	 *
	 * @example $("p").float("left");
	 * @before <p>This is just a test.</p>
	 * @result <p style="float:left;">This is just a test.</p>
	 *
	 * @name float
	 * @type jQuery
	 * @param String val Set the CSS property to the specified value.
	 * @cat CSS
	 */

	/**
	 * Get the current CSS overflow of the first matched element.
	 *
	 * @example $("p").overflow();
	 * @before <p>This is just a test.</p>
	 * @result "none"
	 *
	 * @name overflow
	 * @type String
	 * @cat CSS
	 */

	/**
	 * Set the CSS overflow of every matched element.
	 *
	 * @example $("p").overflow("auto");
	 * @before <p>This is just a test.</p>
	 * @result <p style="overflow:auto;">This is just a test.</p>
	 *
	 * @name overflow
	 * @type jQuery
	 * @param String val Set the CSS property to the specified value.
	 * @cat CSS
	 */

	/**
	 * Get the current CSS color of the first matched element.
	 *
	 * @example $("p").color();
	 * @before <p>This is just a test.</p>
	 * @result "black"
	 *
	 * @name color
	 * @type String
	 * @cat CSS
	 */

	/**
	 * Set the CSS color of every matched element.
	 *
	 * @example $("p").color("blue");
	 * @before <p>This is just a test.</p>
	 * @result <p style="color:blue;">This is just a test.</p>
	 *
	 * @name color
	 * @type jQuery
	 * @param String val Set the CSS property to the specified value.
	 * @cat CSS
	 */

	/**
	 * Get the current CSS background of the first matched element.
	 *
	 * @example $("p").background();
	 * @before <p style="background:blue;">This is just a test.</p>
	 * @result "blue"
	 *
	 * @name background
	 * @type String
	 * @cat CSS
	 */

	/**
	 * Set the CSS background of every matched element.
	 *
	 * @example $("p").background("blue");
	 * @before <p>This is just a test.</p>
	 * @result <p style="background:blue;">This is just a test.</p>
	 *
	 * @name background
	 * @type jQuery
	 * @param String val Set the CSS property to the specified value.
	 * @cat CSS
	 */

	css: "width,height,top,left,position,float,overflow,color,background".split(","),

	/**
	 * Reduce the set of matched elements to a single element.
	 * The position of the element in the set of matched elements
	 * starts at 0 and goes to length - 1.
	 *
	 * @example $("p").eq(1)
	 * @before <p>This is just a test.</p><p>So is this</p>
	 * @result [ <p>So is this</p> ]
	 *
	 * @name eq
	 * @type jQuery
	 * @param Number pos The index of the element that you wish to limit to.
	 * @cat Core
	 */

	/**
	 * Reduce the set of matched elements to all elements before a given position.
	 * The position of the element in the set of matched elements
	 * starts at 0 and goes to length - 1.
	 *
	 * @example $("p").lt(1)
	 * @before <p>This is just a test.</p><p>So is this</p>
	 * @result [ <p>This is just a test.</p> ]
	 *
	 * @name lt
	 * @type jQuery
	 * @param Number pos Reduce the set to all elements below this position.
	 * @cat Core
	 */

	/**
	 * Reduce the set of matched elements to all elements after a given position.
	 * The position of the element in the set of matched elements
	 * starts at 0 and goes to length - 1.
	 *
	 * @example $("p").gt(0)
	 * @before <p>This is just a test.</p><p>So is this</p>
	 * @result [ <p>So is this</p> ]
	 *
	 * @name gt
	 * @type jQuery
	 * @param Number pos Reduce the set to all elements after this position.
	 * @cat Core
	 */

	/**
	 * Filter the set of elements to those that contain the specified text.
	 *
	 * @example $("p").contains("test")
	 * @before <p>This is just a test.</p><p>So is this</p>
	 * @result [ <p>This is just a test.</p> ]
	 *
	 * @name contains
	 * @type jQuery
	 * @param String str The string that will be contained within the text of an element.
	 * @cat DOM/Traversing
	 */

	filter: [ "eq", "lt", "gt", "contains" ],

	attr: {
		/**
		 * Get the current value of the first matched element.
		 *
		 * @example $("input").val();
		 * @before <input type="text" value="some text"/>
		 * @result "some text"
		 *
 		 * @test ok( $("#text1").val() == "Test", "Check for value of input element" );
		 * ok( !$("#text1").val() == "", "Check for value of input element" );
		 *
		 * @name val
		 * @type String
		 * @cat DOM/Attributes
		 */

		/**
		 * Set the value of every matched element.
		 *
		 * @example $("input").val("test");
		 * @before <input type="text" value="some text"/>
		 * @result <input type="text" value="test"/>
		 *
		 * @test document.getElementById('text1').value = "bla";
		 * ok( $("#text1").val() == "bla", "Check for modified value of input element" );
		 * $("#text1").val('test');
		 * ok ( document.getElementById('text1').value == "test", "Check for modified (via val(String)) value of input element" );
		 *
		 * @name val
		 * @type jQuery
		 * @param String val Set the property to the specified value.
		 * @cat DOM/Attributes
		 */
		val: "value",

		/**
		 * Get the html contents of the first matched element.
		 *
		 * @example $("div").html();
		 * @before <div><input/></div>
		 * @result <input/>
		 *
		 * @name html
		 * @type String
		 * @cat DOM/Attributes
		 */

		/**
		 * Set the html contents of every matched element.
		 *
		 * @example $("div").html("<b>new stuff</b>");
		 * @before <div><input/></div>
		 * @result <div><b>new stuff</b></div>
		 *
		 * @test var div = $("div");
		 * div.html("<b>test</b>");
		 * var pass = true;
		 * for ( var i = 0; i < div.size(); i++ ) {
		 *   if ( div.get(i).childNodes.length == 0 ) pass = false;
		 * }
		 * ok( pass, "Set HTML" );
		 *
		 * @name html
		 * @type jQuery
		 * @param String val Set the html contents to the specified value.
		 * @cat DOM/Attributes
		 */
		html: "innerHTML",

		/**
		 * Get the current id of the first matched element.
		 *
		 * @example $("input").id();
		 * @before <input type="text" id="test" value="some text"/>
		 * @result "test"
		 *
 		 * @test ok( $(document.getElementById('main')).id() == "main", "Check for id" );
		 * ok( $("#foo").id() == "foo", "Check for id" );
		 * ok( !$("head").id(), "Check for id" );
		 *
		 * @name id
		 * @type String
		 * @cat DOM/Attributes
		 */

		/**
		 * Set the id of every matched element.
		 *
		 * @example $("input").id("newid");
		 * @before <input type="text" id="test" value="some text"/>
		 * @result <input type="text" id="newid" value="some text"/>
		 *
		 * @name id
		 * @type jQuery
		 * @param String val Set the property to the specified value.
		 * @cat DOM/Attributes
		 */
		id: null,

		/**
		 * Get the current title of the first matched element.
		 *
		 * @example $("img").title();
		 * @before <img src="test.jpg" title="my image"/>
		 * @result "my image"
		 *
 		 * @test ok( $(document.getElementById('google')).title() == "Google!", "Check for title" );
		 * ok( !$("#yahoo").title(), "Check for title" );
		 *
		 * @name title
		 * @type String
		 * @cat DOM/Attributes
		 */

		/**
		 * Set the title of every matched element.
		 *
		 * @example $("img").title("new title");
		 * @before <img src="test.jpg" title="my image"/>
		 * @result <img src="test.jpg" title="new image"/>
		 *
		 * @name title
		 * @type jQuery
		 * @param String val Set the property to the specified value.
		 * @cat DOM/Attributes
		 */
		title: null,

		/**
		 * Get the current name of the first matched element.
		 *
		 * @example $("input").name();
		 * @before <input type="text" name="username"/>
		 * @result "username"
		 *
 		 * @test ok( $(document.getElementById('text1')).name() == "action", "Check for name" );
		 * ok( $("#hidden1").name() == "hidden", "Check for name" );
		 * ok( !$("#area1").name(), "Check for name" );
		 *
		 * @name name
		 * @type String
		 * @cat DOM/Attributes
		 */

		/**
		 * Set the name of every matched element.
		 *
		 * @example $("input").name("user");
		 * @before <input type="text" name="username"/>
		 * @result <input type="text" name="user"/>
		 *
		 * @name name
		 * @type jQuery
		 * @param String val Set the property to the specified value.
		 * @cat DOM/Attributes
		 */
		name: null,

		/**
		 * Get the current href of the first matched element.
		 *
		 * @example $("a").href();
		 * @before <a href="test.html">my link</a>
		 * @result "test.html"
		 *
		 * @name href
		 * @type String
		 * @cat DOM/Attributes
		 */

		/**
		 * Set the href of every matched element.
		 *
		 * @example $("a").href("test2.html");
		 * @before <a href="test.html">my link</a>
		 * @result <a href="test2.html">my link</a>
		 *
		 * @name href
		 * @type jQuery
		 * @param String val Set the property to the specified value.
		 * @cat DOM/Attributes
		 */
		href: null,

		/**
		 * Get the current src of the first matched element.
		 *
		 * @example $("img").src();
		 * @before <img src="test.jpg" title="my image"/>
		 * @result "test.jpg"
		 *
		 * @name src
		 * @type String
		 * @cat DOM/Attributes
		 */

		/**
		 * Set the src of every matched element.
		 *
		 * @example $("img").src("test2.jpg");
		 * @before <img src="test.jpg" title="my image"/>
		 * @result <img src="test2.jpg" title="my image"/>
		 *
		 * @name src
		 * @type jQuery
		 * @param String val Set the property to the specified value.
		 * @cat DOM/Attributes
		 */
		src: null,

		/**
		 * Get the current rel of the first matched element.
		 *
		 * @example $("a").rel();
		 * @before <a href="test.html" rel="nofollow">my link</a>
		 * @result "nofollow"
		 *
		 * @name rel
		 * @type String
		 * @cat DOM/Attributes
		 */

		/**
		 * Set the rel of every matched element.
		 *
		 * @example $("a").rel("nofollow");
		 * @before <a href="test.html">my link</a>
		 * @result <a href="test.html" rel="nofollow">my link</a>
		 *
		 * @name rel
		 * @type jQuery
		 * @param String val Set the property to the specified value.
		 * @cat DOM/Attributes
		 */
		rel: null
	},

	axis: {
		/**
		 * Get a set of elements containing the unique parents of the matched
		 * set of elements.
		 *
		 * @example $("p").parent()
		 * @before <div><p>Hello</p><p>Hello</p></div>
		 * @result [ <div><p>Hello</p><p>Hello</p></div> ]
		 *
		 * @name parent
		 * @type jQuery
		 * @cat DOM/Traversing
		 */

		/**
		 * Get a set of elements containing the unique parents of the matched
		 * set of elements, and filtered by an expression.
		 *
		 * @example $("p").parent(".selected")
		 * @before <div><p>Hello</p></div><div class="selected"><p>Hello Again</p></div>
		 * @result [ <div class="selected"><p>Hello Again</p></div> ]
		 *
		 * @name parent
		 * @type jQuery
		 * @param String expr An expression to filter the parents with
		 * @cat DOM/Traversing
		 */
		parent: "a.parentNode",

		/**
		 * Get a set of elements containing the unique ancestors of the matched
		 * set of elements (except for the root element).
		 *
		 * @example $("span").ancestors()
		 * @before <html><body><div><p><span>Hello</span></p><span>Hello Again</span></div></body></html>
		 * @result [ <body>...</body>, <div>...</div>, <p><span>Hello</span></p> ]
		 *
		 * @name ancestors
		 * @type jQuery
		 * @cat DOM/Traversing
		 */

		/**
		 * Get a set of elements containing the unique ancestors of the matched
		 * set of elements, and filtered by an expression.
		 *
		 * @example $("span").ancestors("p")
		 * @before <html><body><div><p><span>Hello</span></p><span>Hello Again</span></div></body></html>
		 * @result [ <p><span>Hello</span></p> ]
		 *
		 * @name ancestors
		 * @type jQuery
		 * @param String expr An expression to filter the ancestors with
		 * @cat DOM/Traversing
		 */
		ancestors: jQuery.parents,

		/**
		 * Get a set of elements containing the unique ancestors of the matched
		 * set of elements (except for the root element).
		 *
		 * @example $("span").ancestors()
		 * @before <html><body><div><p><span>Hello</span></p><span>Hello Again</span></div></body></html>
		 * @result [ <body>...</body>, <div>...</div>, <p><span>Hello</span></p> ]
		 *
		 * @name parents
		 * @type jQuery
		 * @cat DOM/Traversing
		 */

		/**
		 * Get a set of elements containing the unique ancestors of the matched
		 * set of elements, and filtered by an expression.
		 *
		 * @example $("span").ancestors("p")
		 * @before <html><body><div><p><span>Hello</span></p><span>Hello Again</span></div></body></html>
		 * @result [ <p><span>Hello</span></p> ]
		 *
		 * @name parents
		 * @type jQuery
		 * @param String expr An expression to filter the ancestors with
		 * @cat DOM/Traversing
		 */
		parents: jQuery.parents,

		/**
		 * Get a set of elements containing the unique next siblings of each of the
		 * matched set of elements.
		 *
		 * It only returns the very next sibling, not all next siblings.
		 *
		 * @example $("p").next()
		 * @before <p>Hello</p><p>Hello Again</p><div><span>And Again</span></div>
		 * @result [ <p>Hello Again</p>, <div><span>And Again</span></div> ]
		 *
		 * @name next
		 * @type jQuery
		 * @cat DOM/Traversing
		 */

		/**
		 * Get a set of elements containing the unique next siblings of each of the
		 * matched set of elements, and filtered by an expression.
		 *
		 * It only returns the very next sibling, not all next siblings.
		 *
		 * @example $("p").next(".selected")
		 * @before <p>Hello</p><p class="selected">Hello Again</p><div><span>And Again</span></div>
		 * @result [ <p class="selected">Hello Again</p> ]
		 *
		 * @name next
		 * @type jQuery
		 * @param String expr An expression to filter the next Elements with
		 * @cat DOM/Traversing
		 */
		next: "jQuery.sibling(a).next",

		/**
		 * Get a set of elements containing the unique previous siblings of each of the
		 * matched set of elements.
		 *
		 * It only returns the immediately previous sibling, not all previous siblings.
		 *
		 * @example $("p").prev()
		 * @before <p>Hello</p><div><span>Hello Again</span></div><p>And Again</p>
		 * @result [ <div><span>Hello Again</span></div> ]
		 *
		 * @name prev
		 * @type jQuery
		 * @cat DOM/Traversing
		 */

		/**
		 * Get a set of elements containing the unique previous siblings of each of the
		 * matched set of elements, and filtered by an expression.
		 *
		 * It only returns the immediately previous sibling, not all previous siblings.
		 *
		 * @example $("p").prev(".selected")
		 * @before <div><span>Hello</span></div><p class="selected">Hello Again</p><p>And Again</p>
		 * @result [ <div><span>Hello</span></div> ]
		 *
		 * @name prev
		 * @type jQuery
		 * @param String expr An expression to filter the previous Elements with
		 * @cat DOM/Traversing
		 */
		prev: "jQuery.sibling(a).prev",

		/**
		 * Get a set of elements containing all of the unique siblings of each of the
		 * matched set of elements.
		 *
		 * @example $("div").siblings()
		 * @before <p>Hello</p><div><span>Hello Again</span></div><p>And Again</p>
		 * @result [ <p>Hello</p>, <p>And Again</p> ]
		 *
		 * @test isSet( $("#en").siblings().get(), q("sndp", "sap"), "Check for siblings" ); 
		 *
		 * @name siblings
		 * @type jQuery
		 * @cat DOM/Traversing
		 */

		/**
		 * Get a set of elements containing all of the unique siblings of each of the
		 * matched set of elements, and filtered by an expression.
		 *
		 * @example $("div").siblings(".selected")
		 * @before <div><span>Hello</span></div><p class="selected">Hello Again</p><p>And Again</p>
		 * @result [ <p class="selected">Hello Again</p> ]
		 *
		 * @test isSet( $("#sndp").siblings("[code]").get(), q("sap"), "Check for filtered siblings (has code child element)" ); 
		 * isSet( $("#sndp").siblings("[a]").get(), q("en", "sap"), "Check for filtered siblings (has anchor child element)" );
		 *
		 * @name siblings
		 * @type jQuery
		 * @param String expr An expression to filter the sibling Elements with
		 * @cat DOM/Traversing
		 */
		siblings: "jQuery.sibling(a, null, true)",


		/**
		 * Get a set of elements containing all of the unique children of each of the
		 * matched set of elements.
		 *
		 * @example $("div").children()
		 * @before <p>Hello</p><div><span>Hello Again</span></div><p>And Again</p>
		 * @result [ <span>Hello Again</span> ]
		 *
		 * @test isSet( $("#foo").children().get(), q("sndp", "en", "sap"), "Check for children" );
		 *
		 * @name children
		 * @type jQuery
		 * @cat DOM/Traversing
		 */

		/**
		 * Get a set of elements containing all of the unique children of each of the
		 * matched set of elements, and filtered by an expression.
		 *
		 * @example $("div").children(".selected")
		 * @before <div><span>Hello</span><p class="selected">Hello Again</p><p>And Again</p></div>
		 * @result [ <p class="selected">Hello Again</p> ]
		 *
		 * @test isSet( $("#foo").children("[code]").get(), q("sndp", "sap"), "Check for filtered children" ); 
		 *
		 * @name children
		 * @type jQuery
		 * @param String expr An expression to filter the child Elements with
		 * @cat DOM/Traversing
		 */
		children: "jQuery.sibling(a.firstChild)"
	},

	each: {

		/**
		 * Remove an attribute from each of the matched elements.
		 *
		 * @example $("input").removeAttr("disabled")
		 * @before <input disabled="disabled"/>
		 * @result <input/>
		 *
		 * @name removeAttr
		 * @type jQuery
		 * @param String name The name of the attribute to remove.
		 * @cat DOM
		 */
		removeAttr: function( key ) {
			this.removeAttribute( key );
		},

		/**
		 * Displays each of the set of matched elements if they are hidden.
		 *
		 * @example $("p").show()
		 * @before <p style="display: none">Hello</p>
		 * @result [ <p style="display: block">Hello</p> ]
		 *
		 * @test var pass = true, div = $("div");
		 * div.show().each(function(){
		 *   if ( this.style.display == "none" ) pass = false;
		 * });
		 * ok( pass, "Show" );
		 *
		 * @name show
		 * @type jQuery
		 * @cat Effects
		 */
		show: function(){
			this.style.display = this.oldblock ? this.oldblock : "";
			if ( jQuery.css(this,"display") == "none" )
				this.style.display = "block";
		},

		/**
		 * Hides each of the set of matched elements if they are shown.
		 *
		 * @example $("p").hide()
		 * @before <p>Hello</p>
		 * @result [ <p style="display: none">Hello</p> ]
		 *
		 * var pass = true, div = $("div");
		 * div.hide().each(function(){
		 *   if ( this.style.display != "none" ) pass = false;
		 * });
		 * ok( pass, "Hide" );
		 *
		 * @name hide
		 * @type jQuery
		 * @cat Effects
		 */
		hide: function(){
			this.oldblock = this.oldblock || jQuery.css(this,"display");
			if ( this.oldblock == "none" )
				this.oldblock = "block";
			this.style.display = "none";
		},

		/**
		 * Toggles each of the set of matched elements. If they are shown,
		 * toggle makes them hidden. If they are hidden, toggle
		 * makes them shown.
		 *
		 * @example $("p").toggle()
		 * @before <p>Hello</p><p style="display: none">Hello Again</p>
		 * @result [ <p style="display: none">Hello</p>, <p style="display: block">Hello Again</p> ]
		 *
		 * @name toggle
		 * @type jQuery
		 * @cat Effects
		 */
		toggle: function(){
			jQuery(this)[ jQuery(this).is(":hidden") ? "show" : "hide" ].apply( jQuery(this), arguments );
		},

		/**
		 * Adds the specified class to each of the set of matched elements.
		 *
		 * @example $("p").addClass("selected")
		 * @before <p>Hello</p>
		 * @result [ <p class="selected">Hello</p> ]
		 *
		 * @test var div = $("div");
		 * div.addClass("test");
		 * var pass = true;
		 * for ( var i = 0; i < div.size(); i++ ) {
		 *  if ( div.get(i).className.indexOf("test") == -1 ) pass = false;
		 * }
		 * ok( pass, "Add Class" );
		 *
		 * @name addClass
		 * @type jQuery
		 * @param String class A CSS class to add to the elements
		 * @cat DOM
		 */
		addClass: function(c){
			jQuery.className.add(this,c);
		},

		/**
		 * Removes the specified class from the set of matched elements.
		 *
		 * @example $("p").removeClass("selected")
		 * @before <p class="selected">Hello</p>
		 * @result [ <p>Hello</p> ]
		 *
		 * @test var div = $("div").addClass("test");
		 * div.removeClass("test");
		 * var pass = true;
		 * for ( var i = 0; i < div.size(); i++ ) {
		 *  if ( div.get(i).className.indexOf("test") != -1 ) pass = false;
		 * }
		 * ok( pass, "Remove Class" );
		 * 
		 * reset();
		 *
		 * var div = $("div").addClass("test").addClass("foo").addClass("bar");
		 * div.removeClass("test").removeClass("bar").removeClass("foo");
		 * var pass = true;
		 * for ( var i = 0; i < div.size(); i++ ) {
		 *  if ( div.get(i).className.match(/test|bar|foo/) ) pass = false;
		 * }
		 * ok( pass, "Remove multiple classes" );
		 *
		 * @name removeClass
		 * @type jQuery
		 * @param String class A CSS class to remove from the elements
		 * @cat DOM
		 */
		removeClass: function(c){
			jQuery.className.remove(this,c);
		},

		/**
		 * Adds the specified class if it is present, removes it if it is
		 * not present.
		 *
		 * @example $("p").toggleClass("selected")
		 * @before <p>Hello</p><p class="selected">Hello Again</p>
		 * @result [ <p class="selected">Hello</p>, <p>Hello Again</p> ]
		 *
		 * @name toggleClass
		 * @type jQuery
		 * @param String class A CSS class with which to toggle the elements
		 * @cat DOM
		 */
		toggleClass: function( c ){
			jQuery.className[ jQuery.className.has(this,c) ? "remove" : "add" ](this,c);
		},

		/**
		 * Removes all matched elements from the DOM. This does NOT remove them from the
		 * jQuery object, allowing you to use the matched elements further.
		 *
		 * @example $("p").remove();
		 * @before <p>Hello</p> how are <p>you?</p>
		 * @result how are
		 *
		 * @name remove
		 * @type jQuery
		 * @cat DOM/Manipulation
		 */

		/**
		 * Removes only elements (out of the list of matched elements) that match
		 * the specified jQuery expression. This does NOT remove them from the
		 * jQuery object, allowing you to use the matched elements further.
		 *
		 * @example $("p").remove(".hello");
		 * @before <p class="hello">Hello</p> how are <p>you?</p>
		 * @result how are <p>you?</p>
		 *
		 * @name remove
		 * @type jQuery
		 * @param String expr A jQuery expression to filter elements by.
		 * @cat DOM/Manipulation
		 */
		remove: function(a){
			if ( !a || jQuery.filter( a, [this] ).r )
				this.parentNode.removeChild( this );
		},

		/**
		 * Removes all child nodes from the set of matched elements.
		 *
		 * @example $("p").empty()
		 * @before <p>Hello, <span>Person</span> <a href="#">and person</a></p>
		 * @result [ <p></p> ]
		 *
		 * @name empty
		 * @type jQuery
		 * @cat DOM/Manipulation
		 */
		empty: function(){
			while ( this.firstChild )
				this.removeChild( this.firstChild );
		},

		/**
		 * Binds a handler to a particular event (like click) for each matched element.
		 * The event handler is passed an event object that you can use to prevent
		 * default behaviour. To stop both default action and event bubbling, your handler
		 * has to return false.
		 *
		 * @example $("p").bind( "click", function() {
		 *   alert( $(this).text() );
		 * } )
		 * @before <p>Hello</p>
		 * @result alert("Hello")
		 *
		 * @example $("form").bind( "submit", function() { return false; } )
		 * @desc Cancel a default action and prevent it from bubbling by returning false
		 * from your function.
		 *
		 * @example $("form").bind( "submit", function(event) {
		 *   event.preventDefault();
		 * } );
		 * @desc Cancel only the default action by using the preventDefault method.
		 *
		 *
		 * @example $("form").bind( "submit", function(event) {
		 *   event.stopPropagation();
		 * } )
		 * @desc Stop only an event from bubbling by using the stopPropagation method.
		 *
		 * @name bind
		 * @type jQuery
		 * @param String type An event type
		 * @param Function fn A function to bind to the event on each of the set of matched elements
		 * @cat Events
		 */
		bind: function( type, fn ) {
			if ( fn.constructor == String )
				fn = new Function("e", ( !fn.indexOf(".") ? "jQuery(this)" : "return " ) + fn);
			jQuery.event.add( this, type, fn );
		},

		/**
		 * The opposite of bind, removes a bound event from each of the matched
		 * elements. You must pass the identical function that was used in the original
		 * bind method.
		 *
		 * @example $("p").unbind( "click", function() { alert("Hello"); } )
		 * @before <p onclick="alert('Hello');">Hello</p>
		 * @result [ <p>Hello</p> ]
		 *
		 * @name unbind
		 * @type jQuery
		 * @param String type An event type
		 * @param Function fn A function to unbind from the event on each of the set of matched elements
		 * @cat Events
		 */

		/**
		 * Removes all bound events of a particular type from each of the matched
		 * elements.
		 *
		 * @example $("p").unbind( "click" )
		 * @before <p onclick="alert('Hello');">Hello</p>
		 * @result [ <p>Hello</p> ]
		 *
		 * @name unbind
		 * @type jQuery
		 * @param String type An event type
		 * @cat Events
		 */

		/**
		 * Removes all bound events from each of the matched elements.
		 *
		 * @example $("p").unbind()
		 * @before <p onclick="alert('Hello');">Hello</p>
		 * @result [ <p>Hello</p> ]
		 *
		 * @name unbind
		 * @type jQuery
		 * @cat Events
		 */
		unbind: function( type, fn ) {
			jQuery.event.remove( this, type, fn );
		},

		/**
		 * Trigger a type of event on every matched element.
		 *
		 * @example $("p").trigger("click")
		 * @before <p click="alert('hello')">Hello</p>
		 * @result alert('hello')
		 *
		 * @name trigger
		 * @type jQuery
		 * @param String type An event type to trigger.
		 * @cat Events
		 */
		trigger: function( type, data ) {
			jQuery.event.trigger( type, data, this );
		}
	}
};

jQuery.init();
jQuery.fn.extend({

	// We're overriding the old toggle function, so
	// remember it for later
	_toggle: jQuery.fn.toggle,
	
	/**
	 * Toggle between two function calls every other click.
	 * Whenever a matched element is clicked, the first specified function 
	 * is fired, when clicked again, the second is fired. All subsequent 
	 * clicks continue to rotate through the two functions.
	 *
	 * @example $("p").toggle(function(){
	 *   $(this).addClass("selected");
	 * },function(){
	 *   $(this).removeClass("selected");
	 * });
	 * 
	 * @test var count = 0;
	 * var fn1 = function() { count++; }
	 * var fn2 = function() { count--; }
	 * var link = $('#mark');
	 * link.click().toggle(fn1, fn2).click().click().click().click().click();
	 * ok( count == 1, "Check for toggle(fn, fn)" );
	 *
	 * @name toggle
	 * @type jQuery
	 * @param Function even The function to execute on every even click.
	 * @param Function odd The function to execute on every odd click.
	 * @cat Events
	 */
	toggle: function(a,b) {
		// If two functions are passed in, we're
		// toggling on a click
		return a && b && a.constructor == Function && b.constructor == Function ? this.click(function(e){
			// Figure out which function to execute
			this.last = this.last == a ? b : a;
			
			// Make sure that clicks stop
			e.preventDefault();
			
			// and execute the function
			return this.last.apply( this, [e] ) || false;
		}) :
		
		// Otherwise, execute the old toggle function
		this._toggle.apply( this, arguments );
	},
	
	/**
	 * A method for simulating hovering (moving the mouse on, and off,
	 * an object). This is a custom method which provides an 'in' to a 
	 * frequent task.
	 *
	 * Whenever the mouse cursor is moved over a matched 
	 * element, the first specified function is fired. Whenever the mouse 
	 * moves off of the element, the second specified function fires. 
	 * Additionally, checks are in place to see if the mouse is still within 
	 * the specified element itself (for example, an image inside of a div), 
	 * and if it is, it will continue to 'hover', and not move out 
	 * (a common error in using a mouseout event handler).
	 *
	 * @example $("p").hover(function(){
	 *   $(this).addClass("over");
	 * },function(){
	 *   $(this).addClass("out");
	 * });
	 *
	 * @name hover
	 * @type jQuery
	 * @param Function over The function to fire whenever the mouse is moved over a matched element.
	 * @param Function out The function to fire whenever the mouse is moved off of a matched element.
	 * @cat Events
	 */
	hover: function(f,g) {
		
		// A private function for haandling mouse 'hovering'
		function handleHover(e) {
			// Check if mouse(over|out) are still within the same parent element
			var p = (e.type == "mouseover" ? e.fromElement : e.toElement) || e.relatedTarget;
	
			// Traverse up the tree
			while ( p && p != this ) try { p = p.parentNode } catch(e) { p = this; };
			
			// If we actually just moused on to a sub-element, ignore it
			if ( p == this ) return false;
			
			// Execute the right function
			return (e.type == "mouseover" ? f : g).apply(this, [e]);
		}
		
		// Bind the function to the two event listeners
		return this.mouseover(handleHover).mouseout(handleHover);
	},
	
	/**
	 * Bind a function to be executed whenever the DOM is ready to be
	 * traversed and manipulated. This is probably the most important 
	 * function included in the event module, as it can greatly improve
	 * the response times of your web applications.
	 *
	 * In a nutshell, this is a solid replacement for using window.onload, 
	 * and attaching a function to that. By using this method, your bound Function 
	 * will be called the instant the DOM is ready to be read and manipulated, 
	 * which is exactly what 99.99% of all Javascript code needs to run.
	 * 
	 * Please ensure you have no code in your &lt;body&gt; onload event handler, 
	 * otherwise $(document).ready() may not fire.
	 *
	 * You can have as many $(document).ready events on your page as you like.
	 *
	 * @example $(document).ready(function(){ Your code here... });
	 *
	 * @name ready
	 * @type jQuery
	 * @param Function fn The function to be executed when the DOM is ready.
	 * @cat Events
	 */
	ready: function(f) {
		// If the DOM is already ready
		if ( jQuery.isReady )
			// Execute the function immediately
			f.apply( document );
			
		// Otherwise, remember the function for later
		else {
			// Add the function to the wait list
			jQuery.readyList.push( f );
		}
	
		return this;
	}
});

jQuery.extend({
	/*
	 * All the code that makes DOM Ready work nicely.
	 */
	isReady: false,
	readyList: [],
	
	// Handle when the DOM is ready
	ready: function() {
		// Make sure that the DOM is not already loaded
		if ( !jQuery.isReady ) {
			// Remember that the DOM is ready
			jQuery.isReady = true;
			
			// If there are functions bound, to execute
			if ( jQuery.readyList ) {
				// Execute all of them
				for ( var i = 0; i < jQuery.readyList.length; i++ )
					jQuery.readyList[i].apply( document );
				
				// Reset the list of functions
				jQuery.readyList = null;
			}
			// Remove event lisenter to avoid memory leak
			if ( jQuery.browser.mozilla || jQuery.browser.opera )
				document.removeEventListener( "DOMContentLoaded", jQuery.ready, false );
		}
	}
});

new function(){

		/**
		 * Bind a function to the scroll event of each matched element.
		 *
		 * @example $("p").scroll( function() { alert("Hello"); } );
		 * @before <p>Hello</p>
		 * @result <p onscroll="alert('Hello');">Hello</p>
		 *
		 * @name scroll
		 * @type jQuery
		 * @param Function fn A function to bind to the scroll event on each of the matched elements.
		 * @cat Events/Browser
		 */

		/**
		 * Trigger the scroll event of each matched element. This causes all of the functions
		 * that have been bound to thet scroll event to be executed.
		 *
		 * @example $("p").scroll();
		 * @before <p onscroll="alert('Hello');">Hello</p>
		 * @result alert('Hello');
		 *
		 * @name scroll
		 * @type jQuery
		 * @cat Events/Browser
		 */

		/**
		 * Bind a function to the scroll event of each matched element, which will only be executed once.
		 * Unlike a call to the normal .scroll() method, calling .onescroll() causes the bound function to be
		 * only executed the first time it is triggered, and never again (unless it is re-bound).
		 *
		 * @example $("p").onescroll( function() { alert("Hello"); } );
		 * @before <p onscroll="alert('Hello');">Hello</p>
		 * @result alert('Hello'); // Only executed for the first scroll
		 *
		 * @name onescroll
		 * @type jQuery
		 * @param Function fn A function to bind to the scroll event on each of the matched elements.
		 * @cat Events/Browser
		 */

		/**
		 * Removes a bound scroll event from each of the matched
		 * elements. You must pass the identical function that was used in the original 
		 * bind method.
		 *
		 * @example $("p").unscroll( myFunction );
		 * @before <p onscroll="myFunction">Hello</p>
		 * @result <p>Hello</p>
		 *
		 * @name unscroll
		 * @type jQuery
		 * @param Function fn A function to unbind from the scroll event on each of the matched elements.
		 * @cat Events/Browser
		 */

		/**
		 * Removes all bound scroll events from each of the matched elements.
		 *
		 * @example $("p").unscroll();
		 * @before <p onscroll="alert('Hello');">Hello</p>
		 * @result <p>Hello</p>
		 *
		 * @name unscroll
		 * @type jQuery
		 * @cat Events/Browser
		 */

		/**
		 * Bind a function to the submit event of each matched element.
		 *
		 * @example $("p").submit( function() { alert("Hello"); } );
		 * @before <p>Hello</p>
		 * @result <p onsubmit="alert('Hello');">Hello</p>
		 *
		 * @name submit
		 * @type jQuery
		 * @param Function fn A function to bind to the submit event on each of the matched elements.
		 * @cat Events/Form
		 */

		/**
		 * Trigger the submit event of each matched element. This causes all of the functions
		 * that have been bound to thet submit event to be executed.
		 *
		 * @example $("p").submit();
		 * @before <p onsubmit="alert('Hello');">Hello</p>
		 * @result alert('Hello');
		 *
		 * @name submit
		 * @type jQuery
		 * @cat Events/Form
		 */

		/**
		 * Bind a function to the submit event of each matched element, which will only be executed once.
		 * Unlike a call to the normal .submit() method, calling .onesubmit() causes the bound function to be
		 * only executed the first time it is triggered, and never again (unless it is re-bound).
		 *
		 * @example $("p").onesubmit( function() { alert("Hello"); } );
		 * @before <p onsubmit="alert('Hello');">Hello</p>
		 * @result alert('Hello'); // Only executed for the first submit
		 *
		 * @name onesubmit
		 * @type jQuery
		 * @param Function fn A function to bind to the submit event on each of the matched elements.
		 * @cat Events/Form
		 */

		/**
		 * Removes a bound submit event from each of the matched
		 * elements. You must pass the identical function that was used in the original 
		 * bind method.
		 *
		 * @example $("p").unsubmit( myFunction );
		 * @before <p onsubmit="myFunction">Hello</p>
		 * @result <p>Hello</p>
		 *
		 * @name unsubmit
		 * @type jQuery
		 * @param Function fn A function to unbind from the submit event on each of the matched elements.
		 * @cat Events/Form
		 */

		/**
		 * Removes all bound submit events from each of the matched elements.
		 *
		 * @example $("p").unsubmit();
		 * @before <p onsubmit="alert('Hello');">Hello</p>
		 * @result <p>Hello</p>
		 *
		 * @name unsubmit
		 * @type jQuery
		 * @cat Events/Form
		 */

		/**
		 * Bind a function to the focus event of each matched element.
		 *
		 * @example $("p").focus( function() { alert("Hello"); } );
		 * @before <p>Hello</p>
		 * @result <p onfocus="alert('Hello');">Hello</p>
		 *
		 * @name focus
		 * @type jQuery
		 * @param Function fn A function to bind to the focus event on each of the matched elements.
		 * @cat Events/UI
		 */

		/**
		 * Trigger the focus event of each matched element. This causes all of the functions
		 * that have been bound to thet focus event to be executed.
		 *
		 * @example $("p").focus();
		 * @before <p onfocus="alert('Hello');">Hello</p>
		 * @result alert('Hello');
		 *
		 * @name focus
		 * @type jQuery
		 * @cat Events/UI
		 */

		/**
		 * Bind a function to the focus event of each matched element, which will only be executed once.
		 * Unlike a call to the normal .focus() method, calling .onefocus() causes the bound function to be
		 * only executed the first time it is triggered, and never again (unless it is re-bound).
		 *
		 * @example $("p").onefocus( function() { alert("Hello"); } );
		 * @before <p onfocus="alert('Hello');">Hello</p>
		 * @result alert('Hello'); // Only executed for the first focus
		 *
		 * @name onefocus
		 * @type jQuery
		 * @param Function fn A function to bind to the focus event on each of the matched elements.
		 * @cat Events/UI
		 */

		/**
		 * Removes a bound focus event from each of the matched
		 * elements. You must pass the identical function that was used in the original 
		 * bind method.
		 *
		 * @example $("p").unfocus( myFunction );
		 * @before <p onfocus="myFunction">Hello</p>
		 * @result <p>Hello</p>
		 *
		 * @name unfocus
		 * @type jQuery
		 * @param Function fn A function to unbind from the focus event on each of the matched elements.
		 * @cat Events/UI
		 */

		/**
		 * Removes all bound focus events from each of the matched elements.
		 *
		 * @example $("p").unfocus();
		 * @before <p onfocus="alert('Hello');">Hello</p>
		 * @result <p>Hello</p>
		 *
		 * @name unfocus
		 * @type jQuery
		 * @cat Events/UI
		 */

		/**
		 * Bind a function to the keydown event of each matched element.
		 *
		 * @example $("p").keydown( function() { alert("Hello"); } );
		 * @before <p>Hello</p>
		 * @result <p onkeydown="alert('Hello');">Hello</p>
		 *
		 * @name keydown
		 * @type jQuery
		 * @param Function fn A function to bind to the keydown event on each of the matched elements.
		 * @cat Events/Keyboard
		 */

		/**
		 * Trigger the keydown event of each matched element. This causes all of the functions
		 * that have been bound to thet keydown event to be executed.
		 *
		 * @example $("p").keydown();
		 * @before <p onkeydown="alert('Hello');">Hello</p>
		 * @result alert('Hello');
		 *
		 * @name keydown
		 * @type jQuery
		 * @cat Events/Keyboard
		 */

		/**
		 * Bind a function to the keydown event of each matched element, which will only be executed once.
		 * Unlike a call to the normal .keydown() method, calling .onekeydown() causes the bound function to be
		 * only executed the first time it is triggered, and never again (unless it is re-bound).
		 *
		 * @example $("p").onekeydown( function() { alert("Hello"); } );
		 * @before <p onkeydown="alert('Hello');">Hello</p>
		 * @result alert('Hello'); // Only executed for the first keydown
		 *
		 * @name onekeydown
		 * @type jQuery
		 * @param Function fn A function to bind to the keydown event on each of the matched elements.
		 * @cat Events/Keyboard
		 */

		/**
		 * Removes a bound keydown event from each of the matched
		 * elements. You must pass the identical function that was used in the original 
		 * bind method.
		 *
		 * @example $("p").unkeydown( myFunction );
		 * @before <p onkeydown="myFunction">Hello</p>
		 * @result <p>Hello</p>
		 *
		 * @name unkeydown
		 * @type jQuery
		 * @param Function fn A function to unbind from the keydown event on each of the matched elements.
		 * @cat Events/Keyboard
		 */

		/**
		 * Removes all bound keydown events from each of the matched elements.
		 *
		 * @example $("p").unkeydown();
		 * @before <p onkeydown="alert('Hello');">Hello</p>
		 * @result <p>Hello</p>
		 *
		 * @name unkeydown
		 * @type jQuery
		 * @cat Events/Keyboard
		 */

		/**
		 * Bind a function to the dblclick event of each matched element.
		 *
		 * @example $("p").dblclick( function() { alert("Hello"); } );
		 * @before <p>Hello</p>
		 * @result <p ondblclick="alert('Hello');">Hello</p>
		 *
		 * @name dblclick
		 * @type jQuery
		 * @param Function fn A function to bind to the dblclick event on each of the matched elements.
		 * @cat Events/Mouse
		 */

		/**
		 * Trigger the dblclick event of each matched element. This causes all of the functions
		 * that have been bound to thet dblclick event to be executed.
		 *
		 * @example $("p").dblclick();
		 * @before <p ondblclick="alert('Hello');">Hello</p>
		 * @result alert('Hello');
		 *
		 * @name dblclick
		 * @type jQuery
		 * @cat Events/Mouse
		 */

		/**
		 * Bind a function to the dblclick event of each matched element, which will only be executed once.
		 * Unlike a call to the normal .dblclick() method, calling .onedblclick() causes the bound function to be
		 * only executed the first time it is triggered, and never again (unless it is re-bound).
		 *
		 * @example $("p").onedblclick( function() { alert("Hello"); } );
		 * @before <p ondblclick="alert('Hello');">Hello</p>
		 * @result alert('Hello'); // Only executed for the first dblclick
		 *
		 * @name onedblclick
		 * @type jQuery
		 * @param Function fn A function to bind to the dblclick event on each of the matched elements.
		 * @cat Events/Mouse
		 */

		/**
		 * Removes a bound dblclick event from each of the matched
		 * elements. You must pass the identical function that was used in the original 
		 * bind method.
		 *
		 * @example $("p").undblclick( myFunction );
		 * @before <p ondblclick="myFunction">Hello</p>
		 * @result <p>Hello</p>
		 *
		 * @name undblclick
		 * @type jQuery
		 * @param Function fn A function to unbind from the dblclick event on each of the matched elements.
		 * @cat Events/Mouse
		 */

		/**
		 * Removes all bound dblclick events from each of the matched elements.
		 *
		 * @example $("p").undblclick();
		 * @before <p ondblclick="alert('Hello');">Hello</p>
		 * @result <p>Hello</p>
		 *
		 * @name undblclick
		 * @type jQuery
		 * @cat Events/Mouse
		 */

		/**
		 * Bind a function to the keypress event of each matched element.
		 *
		 * @example $("p").keypress( function() { alert("Hello"); } );
		 * @before <p>Hello</p>
		 * @result <p onkeypress="alert('Hello');">Hello</p>
		 *
		 * @name keypress
		 * @type jQuery
		 * @param Function fn A function to bind to the keypress event on each of the matched elements.
		 * @cat Events/Keyboard
		 */

		/**
		 * Trigger the keypress event of each matched element. This causes all of the functions
		 * that have been bound to thet keypress event to be executed.
		 *
		 * @example $("p").keypress();
		 * @before <p onkeypress="alert('Hello');">Hello</p>
		 * @result alert('Hello');
		 *
		 * @name keypress
		 * @type jQuery
		 * @cat Events/Keyboard
		 */

		/**
		 * Bind a function to the keypress event of each matched element, which will only be executed once.
		 * Unlike a call to the normal .keypress() method, calling .onekeypress() causes the bound function to be
		 * only executed the first time it is triggered, and never again (unless it is re-bound).
		 *
		 * @example $("p").onekeypress( function() { alert("Hello"); } );
		 * @before <p onkeypress="alert('Hello');">Hello</p>
		 * @result alert('Hello'); // Only executed for the first keypress
		 *
		 * @name onekeypress
		 * @type jQuery
		 * @param Function fn A function to bind to the keypress event on each of the matched elements.
		 * @cat Events/Keyboard
		 */

		/**
		 * Removes a bound keypress event from each of the matched
		 * elements. You must pass the identical function that was used in the original 
		 * bind method.
		 *
		 * @example $("p").unkeypress( myFunction );
		 * @before <p onkeypress="myFunction">Hello</p>
		 * @result <p>Hello</p>
		 *
		 * @name unkeypress
		 * @type jQuery
		 * @param Function fn A function to unbind from the keypress event on each of the matched elements.
		 * @cat Events/Keyboard
		 */

		/**
		 * Removes all bound keypress events from each of the matched elements.
		 *
		 * @example $("p").unkeypress();
		 * @before <p onkeypress="alert('Hello');">Hello</p>
		 * @result <p>Hello</p>
		 *
		 * @name unkeypress
		 * @type jQuery
		 * @cat Events/Keyboard
		 */

		/**
		 * Bind a function to the error event of each matched element.
		 *
		 * @example $("p").error( function() { alert("Hello"); } );
		 * @before <p>Hello</p>
		 * @result <p onerror="alert('Hello');">Hello</p>
		 *
		 * @name error
		 * @type jQuery
		 * @param Function fn A function to bind to the error event on each of the matched elements.
		 * @cat Events/Browser
		 */

		/**
		 * Trigger the error event of each matched element. This causes all of the functions
		 * that have been bound to thet error event to be executed.
		 *
		 * @example $("p").error();
		 * @before <p onerror="alert('Hello');">Hello</p>
		 * @result alert('Hello');
		 *
		 * @name error
		 * @type jQuery
		 * @cat Events/Browser
		 */

		/**
		 * Bind a function to the error event of each matched element, which will only be executed once.
		 * Unlike a call to the normal .error() method, calling .oneerror() causes the bound function to be
		 * only executed the first time it is triggered, and never again (unless it is re-bound).
		 *
		 * @example $("p").oneerror( function() { alert("Hello"); } );
		 * @before <p onerror="alert('Hello');">Hello</p>
		 * @result alert('Hello'); // Only executed for the first error
		 *
		 * @name oneerror
		 * @type jQuery
		 * @param Function fn A function to bind to the error event on each of the matched elements.
		 * @cat Events/Browser
		 */

		/**
		 * Removes a bound error event from each of the matched
		 * elements. You must pass the identical function that was used in the original 
		 * bind method.
		 *
		 * @example $("p").unerror( myFunction );
		 * @before <p onerror="myFunction">Hello</p>
		 * @result <p>Hello</p>
		 *
		 * @name unerror
		 * @type jQuery
		 * @param Function fn A function to unbind from the error event on each of the matched elements.
		 * @cat Events/Browser
		 */

		/**
		 * Removes all bound error events from each of the matched elements.
		 *
		 * @example $("p").unerror();
		 * @before <p onerror="alert('Hello');">Hello</p>
		 * @result <p>Hello</p>
		 *
		 * @name unerror
		 * @type jQuery
		 * @cat Events/Browser
		 */

		/**
		 * Bind a function to the blur event of each matched element.
		 *
		 * @example $("p").blur( function() { alert("Hello"); } );
		 * @before <p>Hello</p>
		 * @result <p onblur="alert('Hello');">Hello</p>
		 *
		 * @name blur
		 * @type jQuery
		 * @param Function fn A function to bind to the blur event on each of the matched elements.
		 * @cat Events/UI
		 */

		/**
		 * Trigger the blur event of each matched element. This causes all of the functions
		 * that have been bound to thet blur event to be executed.
		 *
		 * @example $("p").blur();
		 * @before <p onblur="alert('Hello');">Hello</p>
		 * @result alert('Hello');
		 *
		 * @name blur
		 * @type jQuery
		 * @cat Events/UI
		 */

		/**
		 * Bind a function to the blur event of each matched element, which will only be executed once.
		 * Unlike a call to the normal .blur() method, calling .oneblur() causes the bound function to be
		 * only executed the first time it is triggered, and never again (unless it is re-bound).
		 *
		 * @example $("p").oneblur( function() { alert("Hello"); } );
		 * @before <p onblur="alert('Hello');">Hello</p>
		 * @result alert('Hello'); // Only executed for the first blur
		 *
		 * @name oneblur
		 * @type jQuery
		 * @param Function fn A function to bind to the blur event on each of the matched elements.
		 * @cat Events/UI
		 */

		/**
		 * Removes a bound blur event from each of the matched
		 * elements. You must pass the identical function that was used in the original 
		 * bind method.
		 *
		 * @example $("p").unblur( myFunction );
		 * @before <p onblur="myFunction">Hello</p>
		 * @result <p>Hello</p>
		 *
		 * @name unblur
		 * @type jQuery
		 * @param Function fn A function to unbind from the blur event on each of the matched elements.
		 * @cat Events/UI
		 */

		/**
		 * Removes all bound blur events from each of the matched elements.
		 *
		 * @example $("p").unblur();
		 * @before <p onblur="alert('Hello');">Hello</p>
		 * @result <p>Hello</p>
		 *
		 * @name unblur
		 * @type jQuery
		 * @cat Events/UI
		 */

		/**
		 * Bind a function to the load event of each matched element.
		 *
		 * @example $("p").load( function() { alert("Hello"); } );
		 * @before <p>Hello</p>
		 * @result <p onload="alert('Hello');">Hello</p>
		 *
		 * @name load
		 * @type jQuery
		 * @param Function fn A function to bind to the load event on each of the matched elements.
		 * @cat Events/Browser
		 */

		/**
		 * Trigger the load event of each matched element. This causes all of the functions
		 * that have been bound to thet load event to be executed.
		 *
		 * Marked as private: Calling load() without arguments throws exception because the ajax load
		 * does not handle it.
		 *
		 * @example $("p").load();
		 * @before <p onload="alert('Hello');">Hello</p>
		 * @result alert('Hello');
		 *
		 * @name load
		 * @private
		 * @type jQuery
		 * @cat Events/Browser
		 */

		/**
		 * Bind a function to the load event of each matched element, which will only be executed once.
		 * Unlike a call to the normal .load() method, calling .oneload() causes the bound function to be
		 * only executed the first time it is triggered, and never again (unless it is re-bound).
		 *
		 * @example $("p").oneload( function() { alert("Hello"); } );
		 * @before <p onload="alert('Hello');">Hello</p>
		 * @result alert('Hello'); // Only executed for the first load
		 *
		 * @name oneload
		 * @type jQuery
		 * @param Function fn A function to bind to the load event on each of the matched elements.
		 * @cat Events/Browser
		 */

		/**
		 * Removes a bound load event from each of the matched
		 * elements. You must pass the identical function that was used in the original 
		 * bind method.
		 *
		 * @example $("p").unload( myFunction );
		 * @before <p onload="myFunction">Hello</p>
		 * @result <p>Hello</p>
		 *
		 * @name unload
		 * @type jQuery
		 * @param Function fn A function to unbind from the load event on each of the matched elements.
		 * @cat Events/Browser
		 */

		/**
		 * Removes all bound load events from each of the matched elements.
		 *
		 * @example $("p").unload();
		 * @before <p onload="alert('Hello');">Hello</p>
		 * @result <p>Hello</p>
		 *
		 * @name unload
		 * @type jQuery
		 * @cat Events/Browser
		 */

		/**
		 * Bind a function to the select event of each matched element.
		 *
		 * @example $("p").select( function() { alert("Hello"); } );
		 * @before <p>Hello</p>
		 * @result <p onselect="alert('Hello');">Hello</p>
		 *
		 * @name select
		 * @type jQuery
		 * @param Function fn A function to bind to the select event on each of the matched elements.
		 * @cat Events/Form
		 */

		/**
		 * Trigger the select event of each matched element. This causes all of the functions
		 * that have been bound to thet select event to be executed.
		 *
		 * @example $("p").select();
		 * @before <p onselect="alert('Hello');">Hello</p>
		 * @result alert('Hello');
		 *
		 * @name select
		 * @type jQuery
		 * @cat Events/Form
		 */

		/**
		 * Bind a function to the select event of each matched element, which will only be executed once.
		 * Unlike a call to the normal .select() method, calling .oneselect() causes the bound function to be
		 * only executed the first time it is triggered, and never again (unless it is re-bound).
		 *
		 * @example $("p").oneselect( function() { alert("Hello"); } );
		 * @before <p onselect="alert('Hello');">Hello</p>
		 * @result alert('Hello'); // Only executed for the first select
		 *
		 * @name oneselect
		 * @type jQuery
		 * @param Function fn A function to bind to the select event on each of the matched elements.
		 * @cat Events/Form
		 */

		/**
		 * Removes a bound select event from each of the matched
		 * elements. You must pass the identical function that was used in the original 
		 * bind method.
		 *
		 * @example $("p").unselect( myFunction );
		 * @before <p onselect="myFunction">Hello</p>
		 * @result <p>Hello</p>
		 *
		 * @name unselect
		 * @type jQuery
		 * @param Function fn A function to unbind from the select event on each of the matched elements.
		 * @cat Events/Form
		 */

		/**
		 * Removes all bound select events from each of the matched elements.
		 *
		 * @example $("p").unselect();
		 * @before <p onselect="alert('Hello');">Hello</p>
		 * @result <p>Hello</p>
		 *
		 * @name unselect
		 * @type jQuery
		 * @cat Events/Form
		 */

		/**
		 * Bind a function to the mouseup event of each matched element.
		 *
		 * @example $("p").mouseup( function() { alert("Hello"); } );
		 * @before <p>Hello</p>
		 * @result <p onmouseup="alert('Hello');">Hello</p>
		 *
		 * @name mouseup
		 * @type jQuery
		 * @param Function fn A function to bind to the mouseup event on each of the matched elements.
		 * @cat Events/Mouse
		 */

		/**
		 * Trigger the mouseup event of each matched element. This causes all of the functions
		 * that have been bound to thet mouseup event to be executed.
		 *
		 * @example $("p").mouseup();
		 * @before <p onmouseup="alert('Hello');">Hello</p>
		 * @result alert('Hello');
		 *
		 * @name mouseup
		 * @type jQuery
		 * @cat Events/Mouse
		 */

		/**
		 * Bind a function to the mouseup event of each matched element, which will only be executed once.
		 * Unlike a call to the normal .mouseup() method, calling .onemouseup() causes the bound function to be
		 * only executed the first time it is triggered, and never again (unless it is re-bound).
		 *
		 * @example $("p").onemouseup( function() { alert("Hello"); } );
		 * @before <p onmouseup="alert('Hello');">Hello</p>
		 * @result alert('Hello'); // Only executed for the first mouseup
		 *
		 * @name onemouseup
		 * @type jQuery
		 * @param Function fn A function to bind to the mouseup event on each of the matched elements.
		 * @cat Events/Mouse
		 */

		/**
		 * Removes a bound mouseup event from each of the matched
		 * elements. You must pass the identical function that was used in the original 
		 * bind method.
		 *
		 * @example $("p").unmouseup( myFunction );
		 * @before <p onmouseup="myFunction">Hello</p>
		 * @result <p>Hello</p>
		 *
		 * @name unmouseup
		 * @type jQuery
		 * @param Function fn A function to unbind from the mouseup event on each of the matched elements.
		 * @cat Events/Mouse
		 */

		/**
		 * Removes all bound mouseup events from each of the matched elements.
		 *
		 * @example $("p").unmouseup();
		 * @before <p onmouseup="alert('Hello');">Hello</p>
		 * @result <p>Hello</p>
		 *
		 * @name unmouseup
		 * @type jQuery
		 * @cat Events/Mouse
		 */

		/**
		 * Bind a function to the unload event of each matched element.
		 *
		 * @example $("p").unload( function() { alert("Hello"); } );
		 * @before <p>Hello</p>
		 * @result <p onunload="alert('Hello');">Hello</p>
		 *
		 * @name unload
		 * @type jQuery
		 * @param Function fn A function to bind to the unload event on each of the matched elements.
		 * @cat Events/Browser
		 */

		/**
		 * Trigger the unload event of each matched element. This causes all of the functions
		 * that have been bound to thet unload event to be executed.
		 *
		 * @example $("p").unload();
		 * @before <p onunload="alert('Hello');">Hello</p>
		 * @result alert('Hello');
		 *
		 * @name unload
		 * @type jQuery
		 * @cat Events/Browser
		 */

		/**
		 * Bind a function to the unload event of each matched element, which will only be executed once.
		 * Unlike a call to the normal .unload() method, calling .oneunload() causes the bound function to be
		 * only executed the first time it is triggered, and never again (unless it is re-bound).
		 *
		 * @example $("p").oneunload( function() { alert("Hello"); } );
		 * @before <p onunload="alert('Hello');">Hello</p>
		 * @result alert('Hello'); // Only executed for the first unload
		 *
		 * @name oneunload
		 * @type jQuery
		 * @param Function fn A function to bind to the unload event on each of the matched elements.
		 * @cat Events/Browser
		 */

		/**
		 * Removes a bound unload event from each of the matched
		 * elements. You must pass the identical function that was used in the original 
		 * bind method.
		 *
		 * @example $("p").ununload( myFunction );
		 * @before <p onunload="myFunction">Hello</p>
		 * @result <p>Hello</p>
		 *
		 * @name ununload
		 * @type jQuery
		 * @param Function fn A function to unbind from the unload event on each of the matched elements.
		 * @cat Events/Browser
		 */

		/**
		 * Removes all bound unload events from each of the matched elements.
		 *
		 * @example $("p").ununload();
		 * @before <p onunload="alert('Hello');">Hello</p>
		 * @result <p>Hello</p>
		 *
		 * @name ununload
		 * @type jQuery
		 * @cat Events/Browser
		 */

		/**
		 * Bind a function to the change event of each matched element.
		 *
		 * @example $("p").change( function() { alert("Hello"); } );
		 * @before <p>Hello</p>
		 * @result <p onchange="alert('Hello');">Hello</p>
		 *
		 * @name change
		 * @type jQuery
		 * @param Function fn A function to bind to the change event on each of the matched elements.
		 * @cat Events/Form
		 */

		/**
		 * Trigger the change event of each matched element. This causes all of the functions
		 * that have been bound to thet change event to be executed.
		 *
		 * @example $("p").change();
		 * @before <p onchange="alert('Hello');">Hello</p>
		 * @result alert('Hello');
		 *
		 * @name change
		 * @type jQuery
		 * @cat Events/Form
		 */

		/**
		 * Bind a function to the change event of each matched element, which will only be executed once.
		 * Unlike a call to the normal .change() method, calling .onechange() causes the bound function to be
		 * only executed the first time it is triggered, and never again (unless it is re-bound).
		 *
		 * @example $("p").onechange( function() { alert("Hello"); } );
		 * @before <p onchange="alert('Hello');">Hello</p>
		 * @result alert('Hello'); // Only executed for the first change
		 *
		 * @name onechange
		 * @type jQuery
		 * @param Function fn A function to bind to the change event on each of the matched elements.
		 * @cat Events/Form
		 */

		/**
		 * Removes a bound change event from each of the matched
		 * elements. You must pass the identical function that was used in the original 
		 * bind method.
		 *
		 * @example $("p").unchange( myFunction );
		 * @before <p onchange="myFunction">Hello</p>
		 * @result <p>Hello</p>
		 *
		 * @name unchange
		 * @type jQuery
		 * @param Function fn A function to unbind from the change event on each of the matched elements.
		 * @cat Events/Form
		 */

		/**
		 * Removes all bound change events from each of the matched elements.
		 *
		 * @example $("p").unchange();
		 * @before <p onchange="alert('Hello');">Hello</p>
		 * @result <p>Hello</p>
		 *
		 * @name unchange
		 * @type jQuery
		 * @cat Events/Form
		 */

		/**
		 * Bind a function to the mouseout event of each matched element.
		 *
		 * @example $("p").mouseout( function() { alert("Hello"); } );
		 * @before <p>Hello</p>
		 * @result <p onmouseout="alert('Hello');">Hello</p>
		 *
		 * @name mouseout
		 * @type jQuery
		 * @param Function fn A function to bind to the mouseout event on each of the matched elements.
		 * @cat Events/Mouse
		 */

		/**
		 * Trigger the mouseout event of each matched element. This causes all of the functions
		 * that have been bound to thet mouseout event to be executed.
		 *
		 * @example $("p").mouseout();
		 * @before <p onmouseout="alert('Hello');">Hello</p>
		 * @result alert('Hello');
		 *
		 * @name mouseout
		 * @type jQuery
		 * @cat Events/Mouse
		 */

		/**
		 * Bind a function to the mouseout event of each matched element, which will only be executed once.
		 * Unlike a call to the normal .mouseout() method, calling .onemouseout() causes the bound function to be
		 * only executed the first time it is triggered, and never again (unless it is re-bound).
		 *
		 * @example $("p").onemouseout( function() { alert("Hello"); } );
		 * @before <p onmouseout="alert('Hello');">Hello</p>
		 * @result alert('Hello'); // Only executed for the first mouseout
		 *
		 * @name onemouseout
		 * @type jQuery
		 * @param Function fn A function to bind to the mouseout event on each of the matched elements.
		 * @cat Events/Mouse
		 */

		/**
		 * Removes a bound mouseout event from each of the matched
		 * elements. You must pass the identical function that was used in the original 
		 * bind method.
		 *
		 * @example $("p").unmouseout( myFunction );
		 * @before <p onmouseout="myFunction">Hello</p>
		 * @result <p>Hello</p>
		 *
		 * @name unmouseout
		 * @type jQuery
		 * @param Function fn A function to unbind from the mouseout event on each of the matched elements.
		 * @cat Events/Mouse
		 */

		/**
		 * Removes all bound mouseout events from each of the matched elements.
		 *
		 * @example $("p").unmouseout();
		 * @before <p onmouseout="alert('Hello');">Hello</p>
		 * @result <p>Hello</p>
		 *
		 * @name unmouseout
		 * @type jQuery
		 * @cat Events/Mouse
		 */

		/**
		 * Bind a function to the keyup event of each matched element.
		 *
		 * @example $("p").keyup( function() { alert("Hello"); } );
		 * @before <p>Hello</p>
		 * @result <p onkeyup="alert('Hello');">Hello</p>
		 *
		 * @name keyup
		 * @type jQuery
		 * @param Function fn A function to bind to the keyup event on each of the matched elements.
		 * @cat Events/Keyboard
		 */

		/**
		 * Trigger the keyup event of each matched element. This causes all of the functions
		 * that have been bound to thet keyup event to be executed.
		 *
		 * @example $("p").keyup();
		 * @before <p onkeyup="alert('Hello');">Hello</p>
		 * @result alert('Hello');
		 *
		 * @name keyup
		 * @type jQuery
		 * @cat Events/Keyboard
		 */

		/**
		 * Bind a function to the keyup event of each matched element, which will only be executed once.
		 * Unlike a call to the normal .keyup() method, calling .onekeyup() causes the bound function to be
		 * only executed the first time it is triggered, and never again (unless it is re-bound).
		 *
		 * @example $("p").onekeyup( function() { alert("Hello"); } );
		 * @before <p onkeyup="alert('Hello');">Hello</p>
		 * @result alert('Hello'); // Only executed for the first keyup
		 *
		 * @name onekeyup
		 * @type jQuery
		 * @param Function fn A function to bind to the keyup event on each of the matched elements.
		 * @cat Events/Keyboard
		 */

		/**
		 * Removes a bound keyup event from each of the matched
		 * elements. You must pass the identical function that was used in the original 
		 * bind method.
		 *
		 * @example $("p").unkeyup( myFunction );
		 * @before <p onkeyup="myFunction">Hello</p>
		 * @result <p>Hello</p>
		 *
		 * @name unkeyup
		 * @type jQuery
		 * @param Function fn A function to unbind from the keyup event on each of the matched elements.
		 * @cat Events/Keyboard
		 */

		/**
		 * Removes all bound keyup events from each of the matched elements.
		 *
		 * @example $("p").unkeyup();
		 * @before <p onkeyup="alert('Hello');">Hello</p>
		 * @result <p>Hello</p>
		 *
		 * @name unkeyup
		 * @type jQuery
		 * @cat Events/Keyboard
		 */

		/**
		 * Bind a function to the click event of each matched element.
		 *
		 * @example $("p").click( function() { alert("Hello"); } );
		 * @before <p>Hello</p>
		 * @result <p onclick="alert('Hello');">Hello</p>
		 *
		 * @name click
		 * @type jQuery
		 * @param Function fn A function to bind to the click event on each of the matched elements.
		 * @cat Events/Mouse
		 */

		/**
		 * Trigger the click event of each matched element. This causes all of the functions
		 * that have been bound to thet click event to be executed.
		 *
		 * @example $("p").click();
		 * @before <p onclick="alert('Hello');">Hello</p>
		 * @result alert('Hello');
		 *
		 * @name click
		 * @type jQuery
		 * @cat Events/Mouse
		 */

		/**
		 * Bind a function to the click event of each matched element, which will only be executed once.
		 * Unlike a call to the normal .click() method, calling .oneclick() causes the bound function to be
		 * only executed the first time it is triggered, and never again (unless it is re-bound).
		 *
		 * @example $("p").oneclick( function() { alert("Hello"); } );
		 * @before <p onclick="alert('Hello');">Hello</p>
		 * @result alert('Hello'); // Only executed for the first click
		 *
		 * @name oneclick
		 * @type jQuery
		 * @param Function fn A function to bind to the click event on each of the matched elements.
		 * @cat Events/Mouse
		 */

		/**
		 * Removes a bound click event from each of the matched
		 * elements. You must pass the identical function that was used in the original 
		 * bind method.
		 *
		 * @example $("p").unclick( myFunction );
		 * @before <p onclick="myFunction">Hello</p>
		 * @result <p>Hello</p>
		 *
		 * @name unclick
		 * @type jQuery
		 * @param Function fn A function to unbind from the click event on each of the matched elements.
		 * @cat Events/Mouse
		 */

		/**
		 * Removes all bound click events from each of the matched elements.
		 *
		 * @example $("p").unclick();
		 * @before <p onclick="alert('Hello');">Hello</p>
		 * @result <p>Hello</p>
		 *
		 * @name unclick
		 * @type jQuery
		 * @cat Events/Mouse
		 */

		/**
		 * Bind a function to the resize event of each matched element.
		 *
		 * @example $("p").resize( function() { alert("Hello"); } );
		 * @before <p>Hello</p>
		 * @result <p onresize="alert('Hello');">Hello</p>
		 *
		 * @name resize
		 * @type jQuery
		 * @param Function fn A function to bind to the resize event on each of the matched elements.
		 * @cat Events/Browser
		 */

		/**
		 * Trigger the resize event of each matched element. This causes all of the functions
		 * that have been bound to thet resize event to be executed.
		 *
		 * @example $("p").resize();
		 * @before <p onresize="alert('Hello');">Hello</p>
		 * @result alert('Hello');
		 *
		 * @name resize
		 * @type jQuery
		 * @cat Events/Browser
		 */

		/**
		 * Bind a function to the resize event of each matched element, which will only be executed once.
		 * Unlike a call to the normal .resize() method, calling .oneresize() causes the bound function to be
		 * only executed the first time it is triggered, and never again (unless it is re-bound).
		 *
		 * @example $("p").oneresize( function() { alert("Hello"); } );
		 * @before <p onresize="alert('Hello');">Hello</p>
		 * @result alert('Hello'); // Only executed for the first resize
		 *
		 * @name oneresize
		 * @type jQuery
		 * @param Function fn A function to bind to the resize event on each of the matched elements.
		 * @cat Events/Browser
		 */

		/**
		 * Removes a bound resize event from each of the matched
		 * elements. You must pass the identical function that was used in the original 
		 * bind method.
		 *
		 * @example $("p").unresize( myFunction );
		 * @before <p onresize="myFunction">Hello</p>
		 * @result <p>Hello</p>
		 *
		 * @name unresize
		 * @type jQuery
		 * @param Function fn A function to unbind from the resize event on each of the matched elements.
		 * @cat Events/Browser
		 */

		/**
		 * Removes all bound resize events from each of the matched elements.
		 *
		 * @example $("p").unresize();
		 * @before <p onresize="alert('Hello');">Hello</p>
		 * @result <p>Hello</p>
		 *
		 * @name unresize
		 * @type jQuery
		 * @cat Events/Browser
		 */

		/**
		 * Bind a function to the mousemove event of each matched element.
		 *
		 * @example $("p").mousemove( function() { alert("Hello"); } );
		 * @before <p>Hello</p>
		 * @result <p onmousemove="alert('Hello');">Hello</p>
		 *
		 * @name mousemove
		 * @type jQuery
		 * @param Function fn A function to bind to the mousemove event on each of the matched elements.
		 * @cat Events/Mouse
		 */

		/**
		 * Trigger the mousemove event of each matched element. This causes all of the functions
		 * that have been bound to thet mousemove event to be executed.
		 *
		 * @example $("p").mousemove();
		 * @before <p onmousemove="alert('Hello');">Hello</p>
		 * @result alert('Hello');
		 *
		 * @name mousemove
		 * @type jQuery
		 * @cat Events/Mouse
		 */

		/**
		 * Bind a function to the mousemove event of each matched element, which will only be executed once.
		 * Unlike a call to the normal .mousemove() method, calling .onemousemove() causes the bound function to be
		 * only executed the first time it is triggered, and never again (unless it is re-bound).
		 *
		 * @example $("p").onemousemove( function() { alert("Hello"); } );
		 * @before <p onmousemove="alert('Hello');">Hello</p>
		 * @result alert('Hello'); // Only executed for the first mousemove
		 *
		 * @name onemousemove
		 * @type jQuery
		 * @param Function fn A function to bind to the mousemove event on each of the matched elements.
		 * @cat Events/Mouse
		 */

		/**
		 * Removes a bound mousemove event from each of the matched
		 * elements. You must pass the identical function that was used in the original 
		 * bind method.
		 *
		 * @example $("p").unmousemove( myFunction );
		 * @before <p onmousemove="myFunction">Hello</p>
		 * @result <p>Hello</p>
		 *
		 * @name unmousemove
		 * @type jQuery
		 * @param Function fn A function to unbind from the mousemove event on each of the matched elements.
		 * @cat Events/Mouse
		 */

		/**
		 * Removes all bound mousemove events from each of the matched elements.
		 *
		 * @example $("p").unmousemove();
		 * @before <p onmousemove="alert('Hello');">Hello</p>
		 * @result <p>Hello</p>
		 *
		 * @name unmousemove
		 * @type jQuery
		 * @cat Events/Mouse
		 */

		/**
		 * Bind a function to the mousedown event of each matched element.
		 *
		 * @example $("p").mousedown( function() { alert("Hello"); } );
		 * @before <p>Hello</p>
		 * @result <p onmousedown="alert('Hello');">Hello</p>
		 *
		 * @name mousedown
		 * @type jQuery
		 * @param Function fn A function to bind to the mousedown event on each of the matched elements.
		 * @cat Events/Mouse
		 */

		/**
		 * Trigger the mousedown event of each matched element. This causes all of the functions
		 * that have been bound to thet mousedown event to be executed.
		 *
		 * @example $("p").mousedown();
		 * @before <p onmousedown="alert('Hello');">Hello</p>
		 * @result alert('Hello');
		 *
		 * @name mousedown
		 * @type jQuery
		 * @cat Events/Mouse
		 */

		/**
		 * Bind a function to the mousedown event of each matched element, which will only be executed once.
		 * Unlike a call to the normal .mousedown() method, calling .onemousedown() causes the bound function to be
		 * only executed the first time it is triggered, and never again (unless it is re-bound).
		 *
		 * @example $("p").onemousedown( function() { alert("Hello"); } );
		 * @before <p onmousedown="alert('Hello');">Hello</p>
		 * @result alert('Hello'); // Only executed for the first mousedown
		 *
		 * @name onemousedown
		 * @type jQuery
		 * @param Function fn A function to bind to the mousedown event on each of the matched elements.
		 * @cat Events/Mouse
		 */

		/**
		 * Removes a bound mousedown event from each of the matched
		 * elements. You must pass the identical function that was used in the original 
		 * bind method.
		 *
		 * @example $("p").unmousedown( myFunction );
		 * @before <p onmousedown="myFunction">Hello</p>
		 * @result <p>Hello</p>
		 *
		 * @name unmousedown
		 * @type jQuery
		 * @param Function fn A function to unbind from the mousedown event on each of the matched elements.
		 * @cat Events/Mouse
		 */

		/**
		 * Removes all bound mousedown events from each of the matched elements.
		 *
		 * @example $("p").unmousedown();
		 * @before <p onmousedown="alert('Hello');">Hello</p>
		 * @result <p>Hello</p>
		 *
		 * @name unmousedown
		 * @type jQuery
		 * @cat Events/Mouse
		 */
		 
		/**
		 * Bind a function to the mouseover event of each matched element.
		 *
		 * @example $("p").mouseover( function() { alert("Hello"); } );
		 * @before <p>Hello</p>
		 * @result <p onmouseover="alert('Hello');">Hello</p>
		 *
		 * @name mouseover
		 * @type jQuery
		 * @param Function fn A function to bind to the mousedown event on each of the matched elements.
		 * @cat Events/Mouse
		 */

		/**
		 * Trigger the mouseover event of each matched element. This causes all of the functions
		 * that have been bound to thet mousedown event to be executed.
		 *
		 * @example $("p").mouseover();
		 * @before <p onmouseover="alert('Hello');">Hello</p>
		 * @result alert('Hello');
		 *
		 * @name mouseover
		 * @type jQuery
		 * @cat Events/Mouse
		 */

		/**
		 * Bind a function to the mouseover event of each matched element, which will only be executed once.
		 * Unlike a call to the normal .mouseover() method, calling .onemouseover() causes the bound function to be
		 * only executed the first time it is triggered, and never again (unless it is re-bound).
		 *
		 * @example $("p").onemouseover( function() { alert("Hello"); } );
		 * @before <p onmouseover="alert('Hello');">Hello</p>
		 * @result alert('Hello'); // Only executed for the first mouseover
		 *
		 * @name onemouseover
		 * @type jQuery
		 * @param Function fn A function to bind to the mouseover event on each of the matched elements.
		 * @cat Events/Mouse
		 */

		/**
		 * Removes a bound mouseover event from each of the matched
		 * elements. You must pass the identical function that was used in the original 
		 * bind method.
		 *
		 * @example $("p").unmouseover( myFunction );
		 * @before <p onmouseover="myFunction">Hello</p>
		 * @result <p>Hello</p>
		 *
		 * @name unmouseover
		 * @type jQuery
		 * @param Function fn A function to unbind from the mouseover event on each of the matched elements.
		 * @cat Events/Mouse
		 */

		/**
		 * Removes all bound mouseover events from each of the matched elements.
		 *
		 * @example $("p").unmouseover();
		 * @before <p onmouseover="alert('Hello');">Hello</p>
		 * @result <p>Hello</p>
		 *
		 * @name unmouseover
		 * @type jQuery
		 * @cat Events/Mouse
		 */
		 
		 /**
		  * @test var count;
		  * // ignore load
		  * var e = ("blur,focus,resize,scroll,unload,click,dblclick," +
		  * 		"mousedown,mouseup,mousemove,mouseover,mouseout,change,reset,select," + 
		  * 		"submit,keydown,keypress,keyup,error").split(",");
		  * var handler1 = function(event) {
		  * 	count++;
		  * };
		  * var handler2 = function(event) {
		  * 	count++;
		  * };
		  * for( var i=0; i < e.length; i++) {
		  * 	var event = e[i];
		  * 	count = 0;
		  * 	// bind handler
		  * 	$(document)[event](handler1);
		  *		$(document)[event](handler2);
		  * 	$(document)["one"+event](handler1);
		  * 	
		  * 	// call event two times
		  * 	$(document)[event]();
		  * 	$(document)[event]();
		  * 	
		  * 	// unbind events
		  * 	$(document)["un"+event](handler1);
		  * 	// call once more
		  * 	$(document)[event]();
		  *
		  * 	// remove all handlers
		  *		$(document)["un"+event]();
		  *
		  * 	// call once more
		  * 	$(document)[event]();
		  * 	
		  * 	// assert count
		  *     ok( count == 6, 'Checking event ' + event);
		  * }
		  *
		  * @private
		  * @name eventTesting
		  * @cat Events
		  */

	var e = ("blur,focus,load,resize,scroll,unload,click,dblclick," +
		"mousedown,mouseup,mousemove,mouseover,mouseout,change,reset,select," + 
		"submit,keydown,keypress,keyup,error").split(",");

	// Go through all the event names, but make sure that
	// it is enclosed properly
	for ( var i = 0; i < e.length; i++ ) new function(){
			
		var o = e[i];
		
		// Handle event binding
		jQuery.fn[o] = function(f){
			return f ? this.bind(o, f) : this.trigger(o);
		};
		
		// Handle event unbinding
		jQuery.fn["un"+o] = function(f){ return this.unbind(o, f); };
		
		// Finally, handle events that only fire once
		jQuery.fn["one"+o] = function(f){
			// save cloned reference to this
			var element = jQuery(this);
			var handler = function() {
				// unbind itself when executed
				element.unbind(o, handler);
				element = null;
				// apply original handler with the same arguments
				f.apply(this, arguments);
			};
			return this.bind(o, handler);
		};
			
	};
	
	// If Mozilla is used
	if ( jQuery.browser.mozilla || jQuery.browser.opera ) {
		// Use the handy event callback
		document.addEventListener( "DOMContentLoaded", jQuery.ready, false );
	
	// If IE is used, use the excellent hack by Matthias Miller
	// http://www.outofhanwell.com/blog/index.php?title=the_window_onload_problem_revisited
	} else if ( jQuery.browser.msie ) {
	
		// Only works if you document.write() it
		document.write("<scr" + "ipt id=__ie_init defer=true " + 
			"src=//:><\/script>");
	
		// Use the defer script hack
		var script = document.getElementById("__ie_init");
		script.onreadystatechange = function() {
			if ( this.readyState != "complete" ) return;
			this.parentNode.removeChild( this );
			jQuery.ready();
		};
	
		// Clear from memory
		script = null;
	
	// If Safari  is used
	} else if ( jQuery.browser.safari ) {
		// Continually check to see if the document.readyState is valid
		jQuery.safariTimer = setInterval(function(){
			// loaded and complete are both valid states
			if ( document.readyState == "loaded" || 
				document.readyState == "complete" ) {
	
				// If either one are found, remove the timer
				clearInterval( jQuery.safariTimer );
				jQuery.safariTimer = null;
	
				// and execute any waiting functions
				jQuery.ready();
			}
		}, 10);
	} 

	// A fallback to window.onload, that will always work
	jQuery.event.add( window, "load", jQuery.ready );
	
};

// Clean up after IE to avoid memory leaks
if (jQuery.browser.msie) jQuery(window).unload(function() {
	var event = jQuery.event, global = event.global;
	for (var type in global) {
 		var els = global[type], i = els.length;
		if (i>0) do if (type != 'unload') event.remove(els[i-1], type); while (--i);
	}
});
jQuery.fn.extend({

	// overwrite the old show method
	_show: jQuery.fn.show,
	
	/**
	 * Show all matched elements using a graceful animation.
	 * The height, width, and opacity of each of the matched elements 
	 * are changed dynamically according to the specified speed.
	 *
	 * @example $("p").show("slow");
	 *
	 * @name show
	 * @type jQuery
	 * @param Object speed A string representing one of the three predefined speeds ("slow", "normal", or "fast") or the number of milliseconds to run the animation (e.g. 1000).
	 * @cat Effects/Animations
	 */
	 
	/**
	 * Show all matched elements using a graceful animation and firing a callback
	 * function after completion.
	 * The height, width, and opacity of each of the matched elements 
	 * are changed dynamically according to the specified speed.
	 *
	 * @example $("p").show("slow",function(){
	 *   alert("Animation Done.");
	 * });
	 *
	 * @name show
	 * @type jQuery
	 * @param Object speed A string representing one of the three predefined speeds ("slow", "normal", or "fast") or the number of milliseconds to run the animation (e.g. 1000).
	 * @param Function callback A function to be executed whenever the animation completes.
	 * @cat Effects/Animations
	 */
	show: function(speed,callback){
		return speed ? this.animate({
			height: "show", width: "show", opacity: "show"
		}, speed, callback) : this._show();
	},
	
	// Overwrite the old hide method
	_hide: jQuery.fn.hide,
	
	/**
	 * Hide all matched elements using a graceful animation.
	 * The height, width, and opacity of each of the matched elements 
	 * are changed dynamically according to the specified speed.
	 *
	 * @example $("p").hide("slow");
	 *
	 * @name hide
	 * @type jQuery
	 * @param Object speed A string representing one of the three predefined speeds ("slow", "normal", or "fast") or the number of milliseconds to run the animation (e.g. 1000).
	 * @cat Effects/Animations
	 */
	 
	/**
	 * Hide all matched elements using a graceful animation and firing a callback
	 * function after completion.
	 * The height, width, and opacity of each of the matched elements 
	 * are changed dynamically according to the specified speed.
	 *
	 * @example $("p").hide("slow",function(){
	 *   alert("Animation Done.");
	 * });
	 *
	 * @name hide
	 * @type jQuery
	 * @param Object speed A string representing one of the three predefined speeds ("slow", "normal", or "fast") or the number of milliseconds to run the animation (e.g. 1000).
	 * @param Function callback A function to be executed whenever the animation completes.
	 * @cat Effects/Animations
	 */
	hide: function(speed,callback){
		return speed ? this.animate({
			height: "hide", width: "hide", opacity: "hide"
		}, speed, callback) : this._hide();
	},
	
	/**
	 * Reveal all matched elements by adjusting their height.
	 * Only the height is adjusted for this animation, causing all matched
	 * elements to be revealed in a "sliding" manner.
	 *
	 * @example $("p").slideDown("slow");
	 *
	 * @name slideDown
	 * @type jQuery
	 * @param Object speed A string representing one of the three predefined speeds ("slow", "normal", or "fast") or the number of milliseconds to run the animation (e.g. 1000).
	 * @cat Effects/Animations
	 */
	 
	/**
	 * Reveal all matched elements by adjusting their height and firing a callback
	 * function after completion.
	 * Only the height is adjusted for this animation, causing all matched
	 * elements to be revealed in a "sliding" manner.
	 *
	 * @example $("p").slideDown("slow",function(){
	 *   alert("Animation Done.");
	 * });
	 *
	 * @name slideDown
	 * @type jQuery
	 * @param Object speed A string representing one of the three predefined speeds ("slow", "normal", or "fast") or the number of milliseconds to run the animation (e.g. 1000).
	 * @param Function callback A function to be executed whenever the animation completes.
	 * @cat Effects/Animations
	 */
	slideDown: function(speed,callback){
		return this.animate({height: "show"}, speed, callback);
	},
	
	/**
	 * Hide all matched elements by adjusting their height.
	 * Only the height is adjusted for this animation, causing all matched
	 * elements to be hidden in a "sliding" manner.
	 *
	 * @example $("p").slideUp("slow");
	 *
	 * @name slideUp
	 * @type jQuery
	 * @param Object speed A string representing one of the three predefined speeds ("slow", "normal", or "fast") or the number of milliseconds to run the animation (e.g. 1000).
	 * @cat Effects/Animations
	 */
	 
	/**
	 * Hide all matched elements by adjusting their height and firing a callback
	 * function after completion.
	 * Only the height is adjusted for this animation, causing all matched
	 * elements to be hidden in a "sliding" manner.
	 *
	 * @example $("p").slideUp("slow",function(){
	 *   alert("Animation Done.");
	 * });
	 *
	 * @name slideUp
	 * @type jQuery
	 * @param Object speed A string representing one of the three predefined speeds ("slow", "normal", or "fast") or the number of milliseconds to run the animation (e.g. 1000).
	 * @param Function callback A function to be executed whenever the animation completes.
	 * @cat Effects/Animations
	 */
	slideUp: function(speed,callback){
		return this.animate({height: "hide"}, speed, callback);
	},

	/**
	 * Toggle the visibility of all matched elements by adjusting their height.
	 * Only the height is adjusted for this animation, causing all matched
	 * elements to be hidden in a "sliding" manner.
	 *
	 * @example $("p").slideToggle("slow");
	 *
	 * @name slideToggle
	 * @type jQuery
	 * @param Object speed A string representing one of the three predefined speeds ("slow", "normal", or "fast") or the number of milliseconds to run the animation (e.g. 1000).
	 * @cat Effects/Animations
	 */
	 
	/**
	 * Toggle the visibility of all matched elements by adjusting their height
	 * and firing a callback function after completion.
	 * Only the height is adjusted for this animation, causing all matched
	 * elements to be hidden in a "sliding" manner.
	 *
	 * @example $("p").slideToggle("slow",function(){
	 *   alert("Animation Done.");
	 * });
	 *
	 * @name slideToggle
	 * @type jQuery
	 * @param Object speed A string representing one of the three predefined speeds ("slow", "normal", or "fast") or the number of milliseconds to run the animation (e.g. 1000).
	 * @param Function callback A function to be executed whenever the animation completes.
	 * @cat Effects/Animations
	 */
	slideToggle: function(speed,callback){
		return this.each(function(){
			var state = jQuery(this).is(":hidden") ? "show" : "hide";
			jQuery(this).animate({height: state}, speed, callback);
		});
	},
	
	/**
	 * Fade in all matched elements by adjusting their opacity.
	 * Only the opacity is adjusted for this animation, meaning that
	 * all of the matched elements should already have some form of height
	 * and width associated with them.
	 *
	 * @example $("p").fadeIn("slow");
	 *
	 * @name fadeIn
	 * @type jQuery
	 * @param Object speed A string representing one of the three predefined speeds ("slow", "normal", or "fast") or the number of milliseconds to run the animation (e.g. 1000).
	 * @cat Effects/Animations
	 */
	 
	/**
	 * Fade in all matched elements by adjusting their opacity and firing a 
	 * callback function after completion.
	 * Only the opacity is adjusted for this animation, meaning that
	 * all of the matched elements should already have some form of height
	 * and width associated with them.
	 *
	 * @example $("p").fadeIn("slow",function(){
	 *   alert("Animation Done.");
	 * });
	 *
	 * @name fadeIn
	 * @type jQuery
	 * @param Object speed A string representing one of the three predefined speeds ("slow", "normal", or "fast") or the number of milliseconds to run the animation (e.g. 1000).
	 * @param Function callback A function to be executed whenever the animation completes.
	 * @cat Effects/Animations
	 */
	fadeIn: function(speed,callback){
		return this.animate({opacity: "show"}, speed, callback);
	},
	
	/**
	 * Fade out all matched elements by adjusting their opacity.
	 * Only the opacity is adjusted for this animation, meaning that
	 * all of the matched elements should already have some form of height
	 * and width associated with them.
	 *
	 * @example $("p").fadeOut("slow");
	 *
	 * @name fadeOut
	 * @type jQuery
	 * @param Object speed A string representing one of the three predefined speeds ("slow", "normal", or "fast") or the number of milliseconds to run the animation (e.g. 1000).
	 * @cat Effects/Animations
	 */
	 
	/**
	 * Fade out all matched elements by adjusting their opacity and firing a 
	 * callback function after completion.
	 * Only the opacity is adjusted for this animation, meaning that
	 * all of the matched elements should already have some form of height
	 * and width associated with them.
	 *
	 * @example $("p").fadeOut("slow",function(){
	 *   alert("Animation Done.");
	 * });
	 *
	 * @name fadeOut
	 * @type jQuery
	 * @param Object speed A string representing one of the three predefined speeds ("slow", "normal", or "fast") or the number of milliseconds to run the animation (e.g. 1000).
	 * @param Function callback A function to be executed whenever the animation completes.
	 * @cat Effects/Animations
	 */
	fadeOut: function(speed,callback){
		return this.animate({opacity: "hide"}, speed, callback);
	},
	
	/**
	 * Fade the opacity of all matched elements to a specified opacity.
	 * Only the opacity is adjusted for this animation, meaning that
	 * all of the matched elements should already have some form of height
	 * and width associated with them.
	 *
	 * @example $("p").fadeTo("slow", 0.5);
	 *
	 * @name fadeTo
	 * @type jQuery
	 * @param Object speed A string representing one of the three predefined speeds ("slow", "normal", or "fast") or the number of milliseconds to run the animation (e.g. 1000).
	 * @param Number opacity The opacity to fade to (a number from 0 to 1).
	 * @cat Effects/Animations
	 */
	 
	/**
	 * Fade the opacity of all matched elements to a specified opacity and 
	 * firing a callback function after completion.
	 * Only the opacity is adjusted for this animation, meaning that
	 * all of the matched elements should already have some form of height
	 * and width associated with them.
	 *
	 * @example $("p").fadeTo("slow", 0.5, function(){
	 *   alert("Animation Done.");
	 * });
	 *
	 * @name fadeTo
	 * @type jQuery
	 * @param Object speed A string representing one of the three predefined speeds ("slow", "normal", or "fast") or the number of milliseconds to run the animation (e.g. 1000).
	 * @param Number opacity The opacity to fade to (a number from 0 to 1).
	 * @param Function callback A function to be executed whenever the animation completes.
	 * @cat Effects/Animations
	 */
	fadeTo: function(speed,to,callback){
		return this.animate({opacity: to}, speed, callback);
	},
	
	/**
	 * A function for making your own, custom, animations. The key aspect of
	 * this function is the object of style properties that will be animated,
	 * and to what end. Each key within the object represents a style property
	 * that will also be animated (for example: "height", "top", or "opacity").
	 *
	 * The value associated with the key represents to what end the property
	 * will be animated. If a number is provided as the value, then the style
	 * property will be transitioned from its current state to that new number.
	 * Oterwise if the string "hide", "show", or "toggle" is provided, a default
	 * animation will be constructed for that property.
	 *
	 * @example $("p").animate({
	 *   height: 'toggle', opacity: 'toggle'
	 * }, "slow");
	 *
	 * @example $("p").animate({
	 *   left: 50, opacity: 'show'
	 * }, 500);
	 *
	 * @test stop();
	 * var hash = {opacity: 'show'};
	 * var hashCopy = $.extend({}, hash);
	 * $('#foo').animate(hash, 'fast', function() {
	 *  ok( hash.opacity == hashCopy.opacity, 'Check if animate changed the hash parameter' );
	 *  start();
	 * });
	 *
	 * @name animate
	 * @type jQuery
	 * @param Hash params A set of style attributes that you wish to animate, and to what end.
	 * @param Object speed A string representing one of the three predefined speeds ("slow", "normal", or "fast") or the number of milliseconds to run the animation (e.g. 1000).
	 * @param Function callback A function to be executed whenever the animation completes.
	 * @cat Effects/Animations
	 */
	animate: function(prop,speed,callback) {
		return this.queue(function(){
		
			this.curAnim = jQuery.extend({}, prop);
			
			for ( var p in prop ) {
				var e = new jQuery.fx( this, jQuery.speed(speed,callback), p );
				if ( prop[p].constructor == Number )
					e.custom( e.cur(), prop[p] );
				else
					e[ prop[p] ]( prop );
			}
			
		});
	},
	
	/**
	 *
	 * @private
	 */
	queue: function(type,fn){
		if ( !fn ) {
			fn = type;
			type = "fx";
		}
	
		return this.each(function(){
			if ( !this.queue )
				this.queue = {};
	
			if ( !this.queue[type] )
				this.queue[type] = [];
	
			this.queue[type].push( fn );
		
			if ( this.queue[type].length == 1 )
				fn.apply(this);
		});
	}

});

jQuery.extend({

	setAuto: function(e,p) {
		if ( e.notAuto ) return;

		if ( p == "height" && e.scrollHeight != parseInt(jQuery.curCSS(e,p)) ) return;
		if ( p == "width" && e.scrollWidth != parseInt(jQuery.curCSS(e,p)) ) return;

		// Remember the original height
		var a = e.style[p];

		// Figure out the size of the height right now
		var o = jQuery.curCSS(e,p,1);

		if ( p == "height" && e.scrollHeight != o ||
			p == "width" && e.scrollWidth != o ) return;

		// Set the height to auto
		e.style[p] = e.currentStyle ? "" : "auto";

		// See what the size of "auto" is
		var n = jQuery.curCSS(e,p,1);

		// Revert back to the original size
		if ( o != n && n != "auto" ) {
			e.style[p] = a;
			e.notAuto = true;
		}
	},
	
	speed: function(s,o) {
		o = o || {};
		
		if ( o.constructor == Function )
			o = { complete: o };
		
		var ss = { slow: 600, fast: 200 };
		o.duration = (s && s.constructor == Number ? s : ss[s]) || 400;
	
		// Queueing
		o.oldComplete = o.complete;
		o.complete = function(){
			jQuery.dequeue(this, "fx");
			if ( o.oldComplete && o.oldComplete.constructor == Function )
				o.oldComplete.apply( this );
		};
	
		return o;
	},
	
	queue: {},
	
	dequeue: function(elem,type){
		type = type || "fx";
	
		if ( elem.queue && elem.queue[type] ) {
			// Remove self
			elem.queue[type].shift();
	
			// Get next function
			var f = elem.queue[type][0];
		
			if ( f ) f.apply( elem );
		}
	},

	/*
	 * I originally wrote fx() as a clone of moo.fx and in the process
	 * of making it small in size the code became illegible to sane
	 * people. You've been warned.
	 */
	
	fx: function( elem, options, prop ){
	
		var z = this;
	
		// The users options
		z.o = {
			duration: options.duration || 400,
			complete: options.complete,
			step: options.step
		};
	
		// The element
		z.el = elem;
	
		// The styles
		var y = z.el.style;
	
		// Simple function for setting a style value
		z.a = function(){
			if ( options.step )
				options.step.apply( elem, [ z.now ] );
 
			if ( prop == "opacity" )
				jQuery.attr(y, "opacity", z.now); // Let attr handle opacity
			else if ( parseInt(z.now) ) // My hate for IE will never die
				y[prop] = parseInt(z.now) + "px";
				
			y.display = "block";
		};
	
		// Figure out the maximum number to run to
		z.max = function(){
			return parseFloat( jQuery.css(z.el,prop) );
		};
	
		// Get the current size
		z.cur = function(){
			var r = parseFloat( jQuery.curCSS(z.el, prop) );
			return r && r > -10000 ? r : z.max();
		};
	
		// Start an animation from one number to another
		z.custom = function(from,to){
			z.startTime = (new Date()).getTime();
			z.now = from;
			z.a();
	
			z.timer = setInterval(function(){
				z.step(from, to);
			}, 13);
		};
	
		// Simple 'show' function
		z.show = function(){
			if ( !z.el.orig ) z.el.orig = {};

			// Remember where we started, so that we can go back to it later
			z.el.orig[prop] = this.cur();
			
			// Begin the animation
			z.custom(0, z.el.orig[prop]);

			// Stupid IE, look what you made me do
			if ( prop != "opacity" )
				y[prop] = "1px";
		};
	
		// Simple 'hide' function
		z.hide = function(){
			if ( !z.el.orig ) z.el.orig = {};

			// Remember where we started, so that we can go back to it later
			z.el.orig[prop] = this.cur();

			z.o.hide = true;

			// Begin the animation
			z.custom(z.el.orig[prop], 0);
		};
	
		// Remember  the overflow of the element
		if ( !z.el.oldOverflow )
			z.el.oldOverflow = jQuery.css( z.el, "overflow" );
	
		// Make sure that nothing sneaks out
		y.overflow = "hidden";
	
		// Each step of an animation
		z.step = function(firstNum, lastNum){
			var t = (new Date()).getTime();
	
			if (t > z.o.duration + z.startTime) {
				// Stop the timer
				clearInterval(z.timer);
				z.timer = null;

				z.now = lastNum;
				z.a();

				z.el.curAnim[ prop ] = true;
				
				var done = true;
				for ( var i in z.el.curAnim )
					if ( z.el.curAnim[i] !== true )
						done = false;
						
				if ( done ) {
					// Reset the overflow
					y.overflow = z.el.oldOverflow;
				
					// Hide the element if the "hide" operation was done
					if ( z.o.hide ) 
						y.display = 'none';
					
					// Reset the property, if the item has been hidden
					if ( z.o.hide ) {
						for ( var p in z.el.curAnim ) {
							if (p == "opacity")
								jQuery.attr(y, p, z.el.orig[p]);
							else
								y[ p ] = z.el.orig[p] + "px";
	
							// set its height and/or width to auto
							if ( p == 'height' || p == 'width' )
								jQuery.setAuto( z.el, p );
						}
					}
				}

				// If a callback was provided, execute it
				if( done && z.o.complete && z.o.complete.constructor == Function )
					// Execute the complete function
					z.o.complete.apply( z.el );
			} else {
				// Figure out where in the animation we are and set the number
				var p = (t - this.startTime) / z.o.duration;
				z.now = ((-Math.cos(p*Math.PI)/2) + 0.5) * (lastNum-firstNum) + firstNum;
	
				// Perform the next step of the animation
				z.a();
			}
		};
	
	}

});
jQuery.fn.extend({

	/**
	 * Load HTML from a remote file and inject it into the DOM, only if it's
	 * been modified by the server.
	 *
	 * @example $("#feeds").loadIfModified("feeds.html")
	 * @before <div id="feeds"></div>
	 * @result <div id="feeds"><b>45</b> feeds found.</div>
	 *
	 * @name loadIfModified
	 * @type jQuery
	 * @param String url The URL of the HTML file to load.
	 * @param Hash params A set of key/value pairs that will be sent to the server.
	 * @param Function callback A function to be executed whenever the data is loaded.
	 * @cat AJAX
	 */
	loadIfModified: function( url, params, callback ) {
		this.load( url, params, callback, 1 );
	},

	/**
	 * Load HTML from a remote file and inject it into the DOM.
	 *
	 * @example $("#feeds").load("feeds.html")
	 * @before <div id="feeds"></div>
	 * @result <div id="feeds"><b>45</b> feeds found.</div>
	 *
 	 * @example $("#feeds").load("feeds.html",
 	 *   {test: true},
 	 *   function() { alert("load is done"); }
 	 * );
	 * @desc Same as above, but with an additional parameter
	 * and a callback that is executed when the data was loaded.
	 *
	 * @test stop();
	 * $('#first').load("data/name.php", function() {
	 * 	ok( $('#first').text() == 'ERROR', 'Check if content was injected into the DOM' );
	 * 	start();
	 * });
	 *
	 * @test stop(); // check if load can be called with only url
	 * $('#first').load("data/name.php");
	 * $.get("data/name.php", function() {
	 *   ok( $('#first').text() == 'ERROR', 'Check if load works without callback');
	 *   start();
	 * });
	 *
	 * @test stop();
	 * window.foobar = undefined;
	 * window.foo = undefined;
	 * var verifyEvaluation = function() {
	 *   ok( foobar == "bar", 'Check if script src was evaluated after load' );
	 *   ok( $('#foo').html() == 'foo', 'Check if script evaluation has modified DOM');
	 *   ok( $('#ap').html() == 'bar', 'Check if script evaluation has modified DOM');
	 *   start();
	 * };
	 * $('#first').load('data/test.html', function() {
	 *   ok( $('#first').html().match(/^html text/), 'Check content after loading html' );
	 *   ok( foo == "foo", 'Check if script was evaluated after load' );
	 *   setTimeout(verifyEvaluation, 600);
	 * });
	 *
	 * @name load
	 * @type jQuery
	 * @param String url The URL of the HTML file to load.
	 * @param Object params A set of key/value pairs that will be sent to the server.
	 * @param Function callback A function to be executed whenever the data is loaded.
	 * @cat AJAX
	 */
	load: function( url, params, callback, ifModified ) {
		if ( url.constructor == Function )
			return this.bind("load", url);

		callback = callback || function(){};

		// Default to a GET request
		var type = "GET";

		// If the second parameter was provided
		if ( params ) {
			// If it's a function
			if ( params.constructor == Function ) {
				// We assume that it's the callback
				callback = params;
				params = null;

			// Otherwise, build a param string
			} else {
				params = jQuery.param( params );
				type = "POST";
			}
		}

		var self = this;

		// Request the remote document
		jQuery.ajax({
			url: url,
			type: type,
			data: params,
			ifModified: ifModified,
			complete: function(res, status){
				if ( status == "success" || !ifModified && status == "notmodified" ) {
					// Inject the HTML into all the matched elements
					self.html(res.responseText)
					  // Execute all the scripts inside of the newly-injected HTML
					  .evalScripts()
					  // Execute callback
					  .each( callback, [res.responseText, status] );
				} else
					callback.apply( self, [res.responseText, status] );
			}
		});
		return this;
	},

	/**
	 * Serializes a set of input elements into a string of data.
	 * This will serialize all given elements. If you need
	 * serialization similar to the form submit of a browser,
	 * you should use the form plugin. This is also true for
	 * selects with multiple attribute set, only a single option
	 * is serialized.
	 *
	 * @example $("input[@type=text]").serialize();
	 * @before <input type='text' name='name' value='John'/>
	 * <input type='text' name='location' value='Boston'/>
	 * @after name=John&location=Boston
	 * @desc Serialize a selection of input elements to a string
	 *
	 * @test var data = $(':input').not('button').serialize();
	 * // ignore button, IE takes text content as value, not relevant for this test
	 * ok( data == 'action=Test&text2=Test&radio1=on&radio2=on&check=on&=on&hidden=&foo[bar]=&name=name&=foobar&select1=&select2=3&select3=1', 'Check form serialization as query string' );
	 *
	 * @name serialize
	 * @type String
	 * @cat AJAX
	 */
	serialize: function() {
		return jQuery.param( this );
	},

	evalScripts: function() {
		return this.find('script').each(function(){
			if ( this.src )
				// for some weird reason, it doesn't work if the callback is ommited
				jQuery.getScript( this.src, function() {} );
			else
				eval.call( window, this.text || this.textContent || this.innerHTML || "" );
		}).end();
	}

});

// If IE is used, create a wrapper for the XMLHttpRequest object
if ( jQuery.browser.msie && typeof XMLHttpRequest == "undefined" )
	XMLHttpRequest = function(){
		return new ActiveXObject(
			navigator.userAgent.indexOf("MSIE 5") >= 0 ?
			"Microsoft.XMLHTTP" : "Msxml2.XMLHTTP"
		);
	};

// Attach a bunch of functions for handling common AJAX events

/**
 * Attach a function to be executed whenever an AJAX request begins.
 *
 * @example $("#loading").ajaxStart(function(){
 *   $(this).show();
 * });
 * @desc Show a loading message whenever an AJAX request starts.
 *
 * @name ajaxStart
 * @type jQuery
 * @param Function callback The function to execute.
 * @cat AJAX
 */

/**
 * Attach a function to be executed whenever all AJAX requests have ended.
 *
 * @example $("#loading").ajaxStop(function(){
 *   $(this).hide();
 * });
 * @desc Hide a loading message after all the AJAX requests have stopped.
 *
 * @name ajaxStop
 * @type jQuery
 * @param Function callback The function to execute.
 * @cat AJAX
 */

/**
 * Attach a function to be executed whenever an AJAX request completes.
 *
 * @example $("#msg").ajaxComplete(function(){
 *   $(this).append("<li>Request Complete.</li>");
 * });
 * @desc Show a message when an AJAX request completes.
 *
 * @name ajaxComplete
 * @type jQuery
 * @param Function callback The function to execute.
 * @cat AJAX
 */

/**
 * Attach a function to be executed whenever an AJAX request completes
 * successfully.
 *
 * @example $("#msg").ajaxSuccess(function(){
 *   $(this).append("<li>Successful Request!</li>");
 * });
 * @desc Show a message when an AJAX request completes successfully.
 *
 * @name ajaxSuccess
 * @type jQuery
 * @param Function callback The function to execute.
 * @cat AJAX
 */

/**
 * Attach a function to be executed whenever an AJAX request fails.
 *
 * @example $("#msg").ajaxError(function(){
 *   $(this).append("<li>Error requesting page.</li>");
 * });
 * @desc Show a message when an AJAX request fails.
 *
 * @name ajaxError
 * @type jQuery
 * @param Function callback The function to execute.
 * @cat AJAX
 */

/**
 * @test stop(); var counter = { complete: 0, success: 0, error: 0 };
 * var success = function() { counter.success++ };
 * var error = function() { counter.error++ };
 * var complete = function() { counter.complete++ };
 * $('#foo').ajaxStart(complete).ajaxStop(complete).ajaxComplete(complete).ajaxError(error).ajaxSuccess(success);
 * // start with successful test
 * $.ajax({url: "data/name.php", success: success, error: error, complete: function() {
 *   ok( counter.error == 0, 'Check succesful request' );
 *   ok( counter.success == 2, 'Check succesful request' );
 *   ok( counter.complete == 3, 'Check succesful request' );
 *   counter.error = 0; counter.success = 0; counter.complete = 0;
 *   $.ajaxTimeout(500);
 *   $.ajax({url: "data/name.php?wait=5", success: success, error: error, complete: function() {
 *     ok( counter.error == 2, 'Check failed request' );
 *     ok( counter.success == 0, 'Check failed request' );
 *     ok( counter.complete == 3, 'Check failed request' );
 *     start();
 *   }});
 * }});

 * @test stop(); var counter = { complete: 0, success: 0, error: 0 };
 * counter.error = 0; counter.success = 0; counter.complete = 0;
 * var success = function() { counter.success++ };
 * var error = function() { counter.error++ };
 * $.ajaxTimeout(0);
 * $.ajax({url: "data/name.php", global: false, success: success, error: error, complete: function() {
 *   ok( counter.error == 0, 'Check sucesful request without globals' );
 *   ok( counter.success == 1, 'Check sucesful request without globals' );
 *   ok( counter.complete == 0, 'Check sucesful request without globals' );
 *   counter.error = 0; counter.success = 0; counter.complete = 0;
 *   $.ajaxTimeout(500);
 *   $.ajax({url: "data/name.php?wait=5", global: false, success: success, error: error, complete: function() {
 *      ok( counter.error == 1, 'Check failed request without globals' );
 *      ok( counter.success == 0, 'Check failed request without globals' );
 *      ok( counter.complete == 0, 'Check failed request without globals' );
 *      start();
 *   }});
 * }});
 *
 * @name ajaxHandlersTesting
 * @private
 */


new function(){
	var e = "ajaxStart,ajaxStop,ajaxComplete,ajaxError,ajaxSuccess".split(",");

	for ( var i = 0; i < e.length; i++ ) new function(){
		var o = e[i];
		jQuery.fn[o] = function(f){
			return this.bind(o, f);
		};
	};
};

jQuery.extend({

	/**
	 * Load a remote page using an HTTP GET request. All of the arguments to
	 * the method (except URL) are optional.
	 *
	 * @example $.get("test.cgi")
	 *
	 * @example $.get("test.cgi", { name: "John", time: "2pm" } )
	 *
	 * @example $.get("test.cgi", function(data){
	 *   alert("Data Loaded: " + data);
	 * })
	 *
	 * @example $.get("test.cgi",
	 *   { name: "John", time: "2pm" },
	 *   function(data){
	 *     alert("Data Loaded: " + data);
	 *   }
	 * )
	 *
	 * @test stop();
	 * $.get('data/dashboard.xml', function(xml) {
	 * 	var content = [];
	 * 	$('tab', xml).each(function() {
	 * 		content.push($(this).text());
	 * 	});
	 * 	ok( content[0] == 'blabla', 'Check first tab');
	 * 	ok( content[1] == 'blublu', 'Check second tab');
	 * 	start();
	 * });
	 *
	 * @name $.get
	 * @type undefined
	 * @param String url The URL of the page to load.
	 * @param Hash params A set of key/value pairs that will be sent to the server.
	 * @param Function callback A function to be executed whenever the data is loaded.
	 * @cat AJAX
	 */
	get: function( url, data, callback, type, ifModified ) {
		if ( data && data.constructor == Function ) {
			type = callback;
			callback = data;
			data = null;
		}

		// append ? + data or & + data, in case there are already params
		if ( data ) url += ((url.indexOf("?") > -1) ? "&" : "?") + jQuery.param(data);

		// Build and start the HTTP Request
		jQuery.ajax({
			url: url,
			ifModified: ifModified,
			complete: function(r, status) {
				if ( callback ) callback( jQuery.httpData(r,type), status );
			}
		});
	},

	/**
	 * Load a remote page using an HTTP GET request, only if it hasn't
	 * been modified since it was last retrieved. All of the arguments to
	 * the method (except URL) are optional.
	 *
	 * @example $.getIfModified("test.html")
	 *
	 * @example $.getIfModified("test.html", { name: "John", time: "2pm" } )
	 *
	 * @example $.getIfModified("test.cgi", function(data){
	 *   alert("Data Loaded: " + data);
	 * })
	 *
	 * @example $.getifModified("test.cgi",
	 *   { name: "John", time: "2pm" },
	 *   function(data){
	 *     alert("Data Loaded: " + data);
	 *   }
	 * )
	 *
	 * @test stop();
	 * $.getIfModified("data/name.php", function(msg) {
	 *     ok( msg == 'ERROR', 'Check ifModified' );
	 *     start();
	 * });
	 *
	 * @name $.getIfModified
	 * @type undefined
	 * @param String url The URL of the page to load.
	 * @param Hash params A set of key/value pairs that will be sent to the server.
	 * @param Function callback A function to be executed whenever the data is loaded.
	 * @cat AJAX
	 */
	getIfModified: function( url, data, callback, type ) {
		jQuery.get(url, data, callback, type, 1);
	},

	/**
	 * Loads, and executes, a remote JavaScript file using an HTTP GET request.
	 * All of the arguments to the method (except URL) are optional.
	 *
	 * @example $.getScript("test.js")
	 *
	 * @example $.getScript("test.js", function(){
	 *   alert("Script loaded and executed.");
	 * })
	 *
	 * @test stop();
	 * $.getScript("data/test.js", function() {
	 * 	ok( foobar == "bar", 'Check if script was evaluated' );
	 * 	start();
	 * });
	 *
	 * @test
	 * $.getScript("data/test.js");
	 * ok( true, "Check with single argument, can't verify" );
	 *
	 * @name $.getScript
	 * @type undefined
	 * @param String url The URL of the page to load.
	 * @param Function callback A function to be executed whenever the data is loaded.
	 * @cat AJAX
	 */
	getScript: function( url, callback ) {
		if(callback)
			jQuery.get(url, null, callback, "script");
		else {
			jQuery.get(url, null, null, "script");
		}
	},

	/**
	 * Load a remote JSON object using an HTTP GET request.
	 * All of the arguments to the method (except URL) are optional.
	 *
	 * @example $.getJSON("test.js", function(json){
	 *   alert("JSON Data: " + json.users[3].name);
	 * })
	 *
	 * @example $.getJSON("test.js",
	 *   { name: "John", time: "2pm" },
	 *   function(json){
	 *     alert("JSON Data: " + json.users[3].name);
	 *   }
	 * )
	 *
	 * @test stop();
	 * $.getJSON("data/json.php", {json: "array"}, function(json) {
	 *   ok( json[0].name == 'John', 'Check JSON: first, name' );
	 *   ok( json[0].age == 21, 'Check JSON: first, age' );
	 *   ok( json[1].name == 'Peter', 'Check JSON: second, name' );
	 *   ok( json[1].age == 25, 'Check JSON: second, age' );
	 *   start();
	 * });
	 * @test stop();
	 * $.getJSON("data/json.php", function(json) {
	 *   ok( json.data.lang == 'en', 'Check JSON: lang' );
	 *   ok( json.data.length == 25, 'Check JSON: length' );
	 *   start();
	 * });
	 *
	 * @name $.getJSON
	 * @type undefined
	 * @param String url The URL of the page to load.
	 * @param Hash params A set of key/value pairs that will be sent to the server.
	 * @param Function callback A function to be executed whenever the data is loaded.
	 * @cat AJAX
	 */
	getJSON: function( url, data, callback ) {
		if(callback)
			jQuery.get(url, data, callback, "json");
		else {
			jQuery.get(url, data, "json");
		}
	},

	/**
	 * Load a remote page using an HTTP POST request. All of the arguments to
	 * the method (except URL) are optional.
	 *
	 * @example $.post("test.cgi")
	 *
	 * @example $.post("test.cgi", { name: "John", time: "2pm" } )
	 *
	 * @example $.post("test.cgi", function(data){
	 *   alert("Data Loaded: " + data);
	 * })
	 *
	 * @example $.post("test.cgi",
	 *   { name: "John", time: "2pm" },
	 *   function(data){
	 *     alert("Data Loaded: " + data);
	 *   }
	 * )
	 *
	 * @test stop();
	 * $.post("data/name.php", {xml: "5-2"}, function(xml){
	 *   $('math', xml).each(function() {
	 * 	    ok( $('calculation', this).text() == '5-2', 'Check for XML' );
	 * 	    ok( $('result', this).text() == '3', 'Check for XML' );
	 * 	 });
	 *   start();
	 * });
	 *
	 * @name $.post
	 * @type undefined
	 * @param String url The URL of the page to load.
	 * @param Hash params A set of key/value pairs that will be sent to the server.
	 * @param Function callback A function to be executed whenever the data is loaded.
	 * @cat AJAX
	 */
	post: function( url, data, callback, type ) {
		// Build and start the HTTP Request
		jQuery.ajax({
			type: "POST",
			url: url,
			data: jQuery.param(data),
			complete: function(r, status) {
				if ( callback ) callback( jQuery.httpData(r,type), status );
			}
		});
	},

	// timeout (ms)
	timeout: 0,

	/**
	 * Set the timeout of all AJAX requests to a specific amount of time.
	 * This will make all future AJAX requests timeout after a specified amount
	 * of time (the default is no timeout).
	 *
	 * @example $.ajaxTimeout( 5000 );
	 * @desc Make all AJAX requests timeout after 5 seconds.
	 *
	 * @test stop();
	 * var passed = 0;
	 * var timeout;
	 * $.ajaxTimeout(1000);
	 * var pass = function() {
	 * 	passed++;
	 * 	if(passed == 2) {
	 * 		ok( true, 'Check local and global callbacks after timeout' );
	 * 		clearTimeout(timeout);
	 *      $('#main').unbind("ajaxError");
	 * 		start();
	 * 	}
	 * };
	 * var fail = function() {
	 * 	ok( false, 'Check for timeout failed' );
	 * 	start();
	 * };
	 * timeout = setTimeout(fail, 1500);
	 * $('#main').ajaxError(pass);
	 * $.ajax({
	 *   type: "GET",
	 *   url: "data/name.php?wait=5",
	 *   error: pass,
	 *   success: fail
	 * });
	 *
	 * @test stop(); $.ajaxTimeout(50);
	 * $.ajax({
	 *   type: "GET",
	 *   timeout: 5000,
	 *   url: "data/name.php?wait=1",
	 *   error: function() {
	 * 	   ok( false, 'Check for local timeout failed' );
	 * 	   start();
	 *   },
	 *   success: function() {
	 *     ok( true, 'Check for local timeout' );
	 *     start();
	 *   }
	 * });
	 * // reset timeout
	 * $.ajaxTimeout(0);
	 *
	 *
	 * @name $.ajaxTimeout
	 * @type undefined
	 * @param Number time How long before an AJAX request times out.
	 * @cat AJAX
	 */
	ajaxTimeout: function(timeout) {
		jQuery.timeout = timeout;
	},

	// Last-Modified header cache for next request
	lastModified: {},

	/**
	 * Load a remote page using an HTTP request. This function is the primary
	 * means of making AJAX requests using jQuery. $.ajax() takes one property,
	 * an object of key/value pairs, that're are used to initalize the request.
	 *
	 * These are all the key/values that can be passed in to 'prop':
	 *
	 * (String) type - The type of request to make (e.g. "POST" or "GET").
	 *
	 * (String) url - The URL of the page to request.
	 *
	 * (String) data - A string of data to be sent to the server (POST only).
	 *
	 * (String) dataType - The type of data that you're expecting back from
	 * the server (e.g. "xml", "html", "script", or "json").
	 *
	 * (Boolean) ifModified - Allow the request to be successful only if the
	 * response has changed since the last request, default is false, ignoring
	 * the Last-Modified header
	 *
	 * (Number) timeout - Local timeout to override global timeout, eg. to give a
	 * single request a longer timeout while all others timeout after 1 seconds,
	 * see $.ajaxTimeout
	 *
	 * (Boolean) global - Wheather to trigger global AJAX event handlers for
	 * this request, default is true. Set to true to prevent that global handlers
	 * like ajaxStart or ajaxStop are triggered.
	 *
	 * (Function) error - A function to be called if the request fails. The
	 * function gets passed two arguments: The XMLHttpRequest object and a
	 * string describing the type of error that occurred.
	 *
	 * (Function) success - A function to be called if the request succeeds. The
	 * function gets passed one argument: The data returned from the server,
	 * formatted according to the 'dataType' parameter.
	 *
	 * (Function) complete - A function to be called when the request finishes. The
	 * function gets passed two arguments: The XMLHttpRequest object and a
	 * string describing the type the success of the request.
	 *
	 * @example $.ajax({
	 *   type: "GET",
	 *   url: "test.js",
	 *   dataType: "script"
	 * })
	 * @desc Load and execute a JavaScript file.
	 *
	 * @example $.ajax({
	 *   type: "POST",
	 *   url: "some.php",
	 *   data: "name=John&location=Boston",
	 *   success: function(msg){
	 *     alert( "Data Saved: " + msg );
	 *   }
	 * });
	 * @desc Save some data to the server and notify the user once its complete.
	 *
	 * @test stop();
	 * $.ajax({
	 *   type: "GET",
	 *   url: "data/name.php?name=foo",
	 *   success: function(msg){
	 *     ok( msg == 'bar', 'Check for GET' );
	 *     start();
	 *   }
	 * });
	 *
	 * @test stop();
	 * $.ajax({
	 *   type: "POST",
	 *   url: "data/name.php",
	 *   data: "name=peter",
	 *   success: function(msg){
	 *     ok( msg == 'pan', 'Check for POST' );
	 *     start();
	 *   }
	 * });
	 *
	 * @test stop();
	 * window.foobar = undefined;
	 * window.foo = undefined;
	 * var verifyEvaluation = function() {
	 *   ok( foobar == "bar", 'Check if script src was evaluated for datatype html' );
	 *   start();
	 * };
	 * $.ajax({
	 *   dataType: "html",
	 *   url: "data/test.html",
	 *   success: function(data) {
	 *     ok( data.match(/^html text/), 'Check content for datatype html' );
	 *     ok( foo == "foo", 'Check if script was evaluated for datatype html' );
	 *     setTimeout(verifyEvaluation, 600);
	 *   }
	 * });
	 *
	 * @test stop();
	 * $.ajax({
	 *   url: "data/with_fries.xml", dataType: "xml", type: "GET", data: "", success: function(resp) {
	 *     ok( $("properties", resp).length == 1, 'properties in responseXML' );
	 *     ok( $("jsconf", resp).length == 1, 'jsconf in responseXML' );
	 *     ok( $("thing", resp).length == 2, 'things in responseXML' );
	 *     start();
	 *   }
	 * });
	 *
	 * @name $.ajax
	 * @type undefined
	 * @param Hash prop A set of properties to initialize the request with.
	 * @cat AJAX
	 */
	//ajax: function( type, url, data, ret, ifModified ) {
	ajax: function( s ) {

		var fvoid = function() {};
		s = jQuery.extend({
			global: true,
			ifModified: false,
			type: "GET",
			timeout: jQuery.timeout,
			complete: fvoid,
			success: fvoid,
			error: fvoid,
			dataType: null,
			data: null,
			url: null
		}, s);

		/*
		// If only a single argument was passed in,
		// assume that it is a object of key/value pairs
		if ( !url ) {
			ret = type.complete;
			var success = type.success;
			var error = type.error;
			var dataType = type.dataType;
			var global = typeof type.global == "boolean" ? type.global : true;
			var timeout = typeof type.timeout == "number" ? type.timeout : jQuery.timeout;
			ifModified = type.ifModified || false;
			data = type.data;
			url = type.url;
			type = type.type;
		}
		*/

		// Watch for a new set of requests
		if ( s.global && ! jQuery.active++ )
			jQuery.event.trigger( "ajaxStart" );

		var requestDone = false;

		// Create the request object
		var xml = new XMLHttpRequest();

		// Open the socket
		xml.open(s.type, s.url, true);

		// Set the correct header, if data is being sent
		if ( s.data )
			xml.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

		// Set the If-Modified-Since header, if ifModified mode.
		if ( s.ifModified )
			xml.setRequestHeader("If-Modified-Since",
				jQuery.lastModified[s.url] || "Thu, 01 Jan 1970 00:00:00 GMT" );

		// Set header so the called script knows that it's an XMLHttpRequest
		xml.setRequestHeader("X-Requested-With", "XMLHttpRequest");

		// Make sure the browser sends the right content length
		if ( xml.overrideMimeType )
			xml.setRequestHeader("Connection", "close");

		// Wait for a response to come back
		var onreadystatechange = function(isTimeout){
			// The transfer is complete and the data is available, or the request timed out
			if ( xml && (xml.readyState == 4 || isTimeout == "timeout") ) {
				requestDone = true;

				var status = jQuery.httpSuccess( xml ) && isTimeout != "timeout" ?
					s.ifModified && jQuery.httpNotModified( xml, s.url ) ? "notmodified" : "success" : "error";

				// Make sure that the request was successful or notmodified
				if ( status != "error" ) {
					// Cache Last-Modified header, if ifModified mode.
					var modRes;
					try {
						modRes = xml.getResponseHeader("Last-Modified");
					} catch(e) {} // swallow exception thrown by FF if header is not available

					if ( s.ifModified && modRes )
						jQuery.lastModified[s.url] = modRes;

					// If a local callback was specified, fire it
					if ( s.success )
						s.success( jQuery.httpData( xml, s.dataType ), status );

					// Fire the global callback
					if( s.global )
						jQuery.event.trigger( "ajaxSuccess" );

				// Otherwise, the request was not successful
				} else {
					// If a local callback was specified, fire it
					if ( s.error ) s.error( xml, status );

					// Fire the global callback
					if( s.global )
						jQuery.event.trigger( "ajaxError" );
				}

				// The request was completed
				if( s.global )
					jQuery.event.trigger( "ajaxComplete" );

				// Handle the global AJAX counter
				if ( s.global && ! --jQuery.active )
					jQuery.event.trigger( "ajaxStop" );

				// Process result
				if ( s.complete ) s.complete(xml, status);

				// Stop memory leaks
				xml.onreadystatechange = function(){};
				xml = null;

			}
		};
		xml.onreadystatechange = onreadystatechange;

		// Timeout checker
		if(s.timeout > 0)
			setTimeout(function(){
				// Check to see if the request is still happening
				if (xml) {
					// Cancel the request
					xml.abort();

					if ( !requestDone ) onreadystatechange( "timeout" );

					// Clear from memory
					xml = null;
				}
			}, s.timeout);

		// Send the data
		xml.send(s.data);
	},

	// Counter for holding the number of active queries
	active: 0,

	// Determines if an XMLHttpRequest was successful or not
	httpSuccess: function(r) {
		try {
			return !r.status && location.protocol == "file:" ||
				( r.status >= 200 && r.status < 300 ) || r.status == 304 ||
				jQuery.browser.safari && r.status == undefined;
		} catch(e){}

		return false;
	},

	// Determines if an XMLHttpRequest returns NotModified
	httpNotModified: function(xml, url) {
		try {
			var xmlRes = xml.getResponseHeader("Last-Modified");

			// Firefox always returns 200. check Last-Modified date
			return xml.status == 304 || xmlRes == jQuery.lastModified[url] ||
				jQuery.browser.safari && xml.status == undefined;
		} catch(e){}

		return false;
	},

	/* Get the data out of an XMLHttpRequest.
	 * Return parsed XML if content-type header is "xml" and type is "xml" or omitted,
	 * otherwise return plain text.
	 * (String) data - The type of data that you're expecting back,
	 * (e.g. "xml", "html", "script")
	 */
	httpData: function(r,type) {
		var ct = r.getResponseHeader("content-type");
		var data = !type && ct && ct.indexOf("xml") >= 0;
		data = type == "xml" || data ? r.responseXML : r.responseText;

		// If the type is "script", eval it
		if ( type == "script" ) eval.call( window, data );

		// Get the JavaScript object, if JSON is used.
		if ( type == "json" ) eval( "data = " + data );

		// evaluate scripts within html
		if ( type == "html" ) jQuery("<div>").html(data).evalScripts();

		return data;
	},

	// Serialize an array of form elements or a set of
	// key/values into a query string
	param: function(a) {
		var s = [];

		// If an array was passed in, assume that it is an array
		// of form elements
		if ( a.constructor == Array || a.jquery ) {
			// Serialize the form elements
			for ( var i = 0; i < a.length; i++ )
				s.push( a[i].name + "=" + encodeURIComponent( a[i].value ) );

		// Otherwise, assume that it's an object of key/value pairs
		} else {
			// Serialize the key/values
			for ( var j in a )
				s.push( j + "=" + encodeURIComponent( a[j] ) );
		}

		// Return the resulting serialization
		return s.join("&");
	}

});
} // close: if(typeof window.jQuery == "undefined") {
