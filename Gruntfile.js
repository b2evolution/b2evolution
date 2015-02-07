// In order to use Grunt:
// - first install NodeJS on your system (http://nodejs.org), which includes the npm tool.
// - then install the Grunt CLI on your system: (sudo) npm install -g grunt-cli
// - then switch to the b2evolution folder (where this file resides) and install (locally in this dir) 
//   everything that is needed: just type "npm install" - this will use the package.json file to know what to install.
//   each of the required packages with "npm install xxx" for each of the Dependencies listed in package.json
// - then, in order to be able to invoke 'grunt' on your system, type "sudo npm install -g grunt-cli". This will make
//   grunt available from the Command Line Interface.
// In order to use Sass:
// - Make sur Ruby is installed on your system (should be preinstalled on MacOSX. On windows: http://rubyinstaller.org)
// - Type: "sudo gem install sass"
// Once this is done, you can:
// - type 'grunt' (in this dir) and run the default tasks
// - type 'grunt xxx' where xxx is a specific task name
// - type 'grunt watch' and grunt will start listening for file edits and run automatically 
// Note for devs: when adding new plugins, use for example "npm install grunt-contrib-less --save-dev" 
// to update the package.json file with the new plugin reference.

module.exports = function(grunt) {

	// Project configuration:
	grunt.initConfig({

		// Read project settings into the pkg property:
		// (Will allow to refer to the values of the properties below)
		pkg: grunt.file.readJSON('package.json'),

		// Configuration for the less->css compiling tasks:
		less: {
			development: {
				options: {
					compress: true,
					//yuicompress: true,
					//optimization: 2
				},
				files: {
					// target.css file: source.less file
					'blogs/rsc/build/testless.css': 'blogs/rsc/less/test.less',
					// Custom CSS for bootstrap
					'blogs/skins_adm/bootstrap/rsc/css/style.css': 'blogs/skins_adm/bootstrap/rsc/css/style.less',
					'blogs/rsc/css/bootstrap/b2evo.css': 'blogs/rsc/css/bootstrap/b2evo.less',
					'blogs/skins/bootstrap/style.css': 'blogs/skins/bootstrap/style.less',
				}
			}
		},

		// Configuration for the scss->css compiling tasks:
		sass: {
			development: {
				options: {
					style: 'expanded',
					sourcemap: 'none'
				},
				files: {
					// target.css file: source.scss file
					//'blogs/rsc/build/testscss.css': 'blogs/rsc/scss/test.scss',
					'blogs/skins/pureforums/pureforums_header.css': 'blogs/skins/pureforums/pureforums_header.scss',
					'blogs/skins/pureforums/pureforums_main.css': 'blogs/skins/pureforums/pureforums_main.scss',
					'blogs/skins/pureforums/pureforums_footer.css': 'blogs/skins/pureforums/pureforums_footer.scss',
				}
			}
		},

		// Configuration for the concatenate tasks:
		concat: {
			options: { 
				// The following will appear on top of the created files:
				banner: '/*! <%= pkg.name %> v<%= pkg.version %> */\n',
			},
			/*
			 * CSS
			 */
			b2evo_base: {
				options: {
					banner: '<%= concat.options.banner %>/* This includes: basic_styles.css, basic.css, blog_base.css, item_base.css */\n'
				},
				nonull: true, // Display missing files
				src: ['blogs/rsc/css/basic_styles.css', 'blogs/rsc/css/basic.css', 'blogs/rsc/css/blog_base.css', 'blogs/rsc/css/item_base.css'],
				dest: 'blogs/rsc/build/b2evo_base.bundle.css',
			},
			skin_evopress: {
				nonull: true, // Display missing files
				src: ['blogs/skins/evopress/style.css', 'blogs/skins/evopress/item.css'],
				dest: 'blogs/skins/evopress/evopress.bundle.css',
			},
			skin_pureforums: {
				nonull: true, // Display missing files
				src: ['blogs/skins/pureforums/pureforums_header.css', 'blogs/skins/pureforums/pureforums_main.css', 'blogs/skins/pureforums/pureforums_footer.css'],
				dest: 'blogs/skins/pureforums/pureforums.bundle.css',
			},
			/*
			 * JS:
			 */
			// Login screen:
			sha1_md5: {
				src: ['blogs/rsc/js/src/sha1.js', 'blogs/rsc/js/src/md5.js'],
				dest: 'blogs/rsc/js/build/sha1_md5.bundle.js',
			},
		},

		// CSS minification:
		cssmin: {
			options: { 
				// The following will appear on top of the created files:
				banner: '/*! <%= pkg.name %> v<%= pkg.version %> */\n',
			},
			b2evo_base: {
				nonull: true, // Display missing files
				src: 'blogs/rsc/build/b2evo_base.bundle.css',
				dest: 'blogs/rsc/build/b2evo_base.bmin.css',
			},
			skin_evopress: {
				src: 'blogs/skins/evopress/evopress.bundle.css',
				dest: 'blogs/skins/evopress/evopress.bmin.css',
			},
			skin_pureforums: {
				src: 'blogs/skins/pureforums/pureforums.bundle.css',
				dest: 'blogs/skins/pureforums/pureforums.bmin.css',
			},
		},

		// Configuration for the uglify minifying tasks:
		uglify: {
			// Login screen:
			sha1_md5: { 
				nonull: true, // Display missing files
				src: ['blogs/rsc/js/build/sha1_md5.bundle.js'],
				dest: 'blogs/rsc/js/build/sha1_md5.bmin.js'
			},
			// Another Target:
			/*  Early tests:
			functionsjs: {
				nonull: true, // Display missing files
				src: 'blogs/rsc/js/functions.js',
				dest: 'blogs/rsc/js/build/functions.min.js'
			},
			ajaxcomjs: {
				options: {
					// Extend default banner:
					banner: '<%= uglify.options.banner %>// This includes 2 files \n'
				},
				nonull: true, // Display missing files
				src: ['blogs/rsc/js/ajax.js', 'blogs/rsc/js/communication.js'],
				dest: 'blogs/rsc/js/build/ajaxcom.min.js',
			},
			*/
			// Colorbox + Voting + Touchswipe
			colorbox: {
				options: {
					banner: '/* This includes 4 files: jquery.colorbox.js, voting.js, jquery.touchswipe.js, colorbox.init.js */\n'
				},
				nonull: true, // Display missing files
				src: ['blogs/rsc/js/colorbox/jquery.colorbox.js', 'blogs/rsc/js/voting.js', 'blogs/rsc/js/jquery/jquery.touchswipe.js', 'blogs/rsc/js/colorbox/colorbox.init.js'],
				dest: 'blogs/rsc/js/build/colorbox.bmin.js'
			},
			// Bubbletip
			bubbletip: {
				options: {
					banner: '/* This includes 4 files: bubbletip.js, plugins.js, userfields.js, colorpicker.js */\n'
				},
				nonull: true, // Display missing files
				// fp>yura: why isn't jquery.bubbletip.js bundled into this?
				// if plugins.js is used only for editing we should probably move it to a textedit.bundle		
				src: ['blogs/rsc/js/bubbletip.js', 'blogs/rsc/js/plugins.js', 'blogs/rsc/js/userfields.js', 'blogs/rsc/js/colorpicker.js'],
				dest: 'blogs/rsc/js/build/bubbletip.bmin.js'
			},
			// Popover (Analog of bubbletip on bootstrap skins)
			popover: {
				options: {
					banner: '/* This includes 4 files: bootstrap/usernames.js, bootstrap/plugins.js, bootstrap/userfields.js, bootstrap/colorpicker.js */\n'
				},
				nonull: true, // Display missing files
				src: ['blogs/rsc/js/bootstrap/usernames.js', 'blogs/rsc/js/bootstrap/plugins.js', 'blogs/rsc/js/bootstrap/userfields.js', 'blogs/rsc/js/bootstrap/colorpicker.js'],
				dest: 'blogs/rsc/js/build/popover.bmin.js'
			},
			// Textcomplete plugin to suggest user names in textareas with '@username'
			textcomplete: {
				options: {
					banner: '/* This includes 2 files: jquery.textcomplete.js, textcomplete.init.js */\n'
				},
				nonull: true, // Display missing files
				src: ['blogs/rsc/js/jquery/jquery.textcomplete.js', 'blogs/rsc/js/textcomplete.init.js'],
				dest: 'blogs/rsc/js/build/textcomplete.bmin.js'
			},
		},

		// Configuration for the watch tasks:
		watch: {
			/* Early tests:
			functionsjs: {
				files: ['blogs/rsc/js/functions.js'],
				tasks: ['uglify:functionsjs'],
			},
			ajaxcomjs: {
				files: ['blogs/rsc/js/ajax.js', 'blogs/rsc/js/communication.js'],
				tasks: ['uglify:ajaxcomjs'],
			},
			*/
			less: {
				// Which files to watch (all .less files recursively in the whole blogs directory)
				files: ['blogs/**/*.less'],
				tasks: ['less'],
				options: {
					nospawn: true,
				}
			},
			sass: {
				// Which files to watch (all .scss files recursively in the scss directory)
				files: ['blogs/**/*.scss'],
				tasks: ['sass'],
				options: {
					nospawn: true,
				}
			},
			concat_cssmin: {
				// Which files to watch (all .css files recursively in the whole blogs directory)
				files: ['blogs/**/*.css'],
				tasks: ['concat','cssmin'],
				options: {
					nospawn: true,
				}
			},
		},

	});

	// Load the plugin that provides the tasks ( "uglify", "less", "sass", etc. ):
	grunt.loadNpmTasks('grunt-contrib-concat');
	grunt.loadNpmTasks('grunt-contrib-cssmin');
	grunt.loadNpmTasks('grunt-contrib-less');
	grunt.loadNpmTasks('grunt-contrib-sass');
	grunt.loadNpmTasks('grunt-contrib-uglify');
	grunt.loadNpmTasks('grunt-contrib-watch');

	// Default task(s):
	grunt.registerTask('default', ['less','sass','concat','cssmin','uglify']);

};