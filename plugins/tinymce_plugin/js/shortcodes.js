var evo = evo || {};

evo.shortcode = {
	types: {
		image: {
			regexp: /(<span.*?data-evo-tag.*?>)?(\[(image):(\d+):?([^\[\]]*)\])(<\/span>)?/g
		},
		thumbnail: {
			regexp: /(<span.*?data-evo-tag.*?>)?(\[(thumbnail):(\d+):?([^\[\]]*)\])(<\/span>)?/g
		},
		inline: {
			regexp: /(<span.*?data-evo-tag.*?>)?(\[(inline):(\d+):?([^\[\]]*)\])(<\/span>)?/g
		},
		button: {
			regexp: /(<span.*?data-evo-tag.*?>)?(\[(button):image#(\d+)([^\[\]]*)\][^\[]*\[\/button\])(<\/span>)?/g
		},
		cta: {
			regexp: /(<span.*?data-evo-tag.*?>)?(\[(cta):?\d*:image#(\d+)([^\[\]]*)\][^\[]*\[\/cta\])(<\/span>)?/g
		},
		like: {
			regexp: /(<span.*?data-evo-tag.*?>)?(\[(like):image#(\d+)([^\[\]]*)\][^\[]*\[\/like\])(<\/span>)?/g
		},
		dislike: {
			regexp: /(<span.*?data-evo-tag.*?>)?(\[(dislike):image#(\d+)([^\[\]]*)\][^\[]*\[\/dislike\])(<\/span>)?/g
		},
		activate: {
			regexp: /(<span.*?data-evo-tag.*?>)?(\[(activate):image#(\d+)([^\[\]]*)\][^\[]*\[\/activate\])(<\/span>)?/g
		},
		unsubscribe: {
			regexp: /(<span.*?data-evo-tag.*?>)?(\[(unsubscribe):image#(\d+)([^\[\]]*)\][^\[]*\[\/unsubscribe\])(<\/span>)?/g
		}
	},
	next: function( tag, text, index ) {
		var re = evo.shortcode.regexp( tag ),
			match, result;

		re.lastIndex = index || 0;
		match = re.exec( text );

		if ( ! match ) {
			return;
		}

		result = {
			index:     match.index,
			content:   match[0],
			shortcode: evo.shortcode.fromMatch( match ),
		};

		return result;
	},
	regexp: function( tag ) {
		return evo.shortcode.types[tag].regexp;
	},
	fromMatch: function( match ) {
		return new evo.shortcode( {
			type: match[3],
			link_ID: match[4],
			content: match[5] } );
	}
};

evo.shortcode = $.extend( function( options ) {
	//options = { content: options.content };
	//$.extend( this, options );

	this.type = options.type;
	this.link_ID = options.link_ID;
	this.content = options.content;
}, evo.shortcode );