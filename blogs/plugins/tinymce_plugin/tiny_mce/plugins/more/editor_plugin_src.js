/**
 * $Id: editor_plugin_src.js 6650 2014-05-09 09:22:38Z yura $
 *
 * @author Moxiecode
 * @author Francois Planque
 * @copyright Copyright © 2004-2009, Moxiecode Systems AB, All rights reserved.
 * @copyright Copyright © 2009, Francois Planque -- http://fplanque.com/
 */

(function() {
	tinymce.create('tinymce.plugins.MorePlugin',
	{
		/**
		 * Initializes the plugin, this will be executed after the plugin has been created.
		 * This call is done before the editor instance has finished it's initialization so use the onInit event
		 * of the editor instance to intercept that event.
		 *
		 * @param {tinymce.Editor} ed Editor instance that the plugin is initialized in.
		 * @param {string} url Absolute URL to where the plugin is located.
		 */
		init : function(ed, url)
		{
			var pb = '<img src="' + url + '/img/trans.gif" class="mceMore mceItemNoResize" />',
			cls = 'mceMore',
			sep = ed.getParam('more_separator', '<!--more-->'),
			pbRE;

			pbRE = new RegExp(sep.replace(/[\?\.\*\[\]\(\)\{\}\+\^\$\:]/g, function(a) {return '\\' + a;}), 'g');

			// Register commands
			ed.addCommand('mceMore', function() {
				ed.execCommand('mceInsertContent', 0, pb);
			});

			// Register button
			ed.addButton('morebtn', {
				title : 'moreseparator.desc',
				cmd : cls,
				image : url+'/img/morebtn.gif'
			});

			ed.onInit.add(function() {
				if (ed.settings.content_css !== false)
					ed.dom.loadCSS(url + "/css/content.css");

				if (ed.theme.onResolveName) {
					ed.theme.onResolveName.add(function(th, o) {
						if (o.node.nodeName == 'IMG' && ed.dom.hasClass(o.node, cls))
							o.name = 'more';
					});
				}
			});

			ed.onClick.add(function(ed, e) {
				e = e.target;

				if (e.nodeName === 'IMG' && ed.dom.hasClass(e, cls))
					ed.selection.select(e);
			});

			ed.onNodeChange.add(function(ed, cm, n) {
				cm.setActive('more', n.nodeName === 'IMG' && ed.dom.hasClass(n, cls));
			});

			ed.onBeforeSetContent.add(function(ed, o) {
				o.content = o.content.replace(pbRE, pb);
			});

			ed.onPostProcess.add(function(ed, o) {
				if (o.get)
					o.content = o.content.replace(/<img[^>]+>/g, function(im) {
						if (im.indexOf('class="mceMore') !== -1)
							im = sep;

						return im;
					});
			});
		},

		/**
		 * Creates control instances based in the incomming name. This method is normally not
		 * needed since the addButton method of the tinymce.Editor class is a more easy way of adding buttons
		 * but you sometimes need to create more complex controls like listboxes, split buttons etc then this
		 * method can be used to create those.
		 *
		 * @param {String} n Name of the control to create.
		 * @param {tinymce.ControlManager} cm Control manager to use inorder to create new control.
		 * @return {tinymce.ui.Control} New control instance or null if no control was created.
		 */
		createControl : function(n, cm) {
			return null;
		},

		getInfo : function()
		{
			return {
				longname : 'More',
				author : 'Francois Planque',
				authorurl : 'http://fplanque.com',
				infourl : 'http://b2evolution.net',
				version : '0.1'
			};
		}
	});

	// Register plugin
	tinymce.PluginManager.add('more', tinymce.plugins.MorePlugin);
})();