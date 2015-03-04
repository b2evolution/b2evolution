// In order to use Grunt:
// - first install NodeJS on your system (http://nodejs.org), which includes the npm tool.
// - then install the Grunt CLI on your system: (sudo) npm install -g grunt-cli
// - then switch to the b2evolution folder (where this file resides) and install (locally in this dir) 
//   everything that is needed: just type "npm install" - this will use the package.json file to know what to install.
//   each of the required packages with "npm install xxx" for each of the Dependencies listed in package.json
// - then, in order to be able to invoke 'grunt' on your system, type "sudo npm install -g grunt-cli". This will make
//   grunt available from the Command Line Interface.
// - ONCE IN A WHILE: run "npm update" to update all youe packages
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
			// Note! The output css files are not compressed on this task
				options: {
					compress: false,
					//yuicompress: true,
					//optimization: 2
				},
				files: {
					// target.css file: source.less file
					'rsc/build/testless.css': 'rsc/less/test.less',
					// Basic styles:
					'rsc/css/basic_styles.css': 'rsc/less/basic_styles.less',
					'rsc/css/basic.css':        'rsc/less/basic.less',
					'rsc/css/blog_base.css':    'rsc/less/blog_base.less',
					'rsc/css/item_base.css':    'rsc/less/item_base.less',
					// Bootstrap frontoffice styles:
					'rsc/build/bootstrap-b2evo_base.bundle.css': [
							// Basic styles for all bootstrap skins
							'rsc/less/bootstrap-basic_styles.less',
							'rsc/less/bootstrap-basic.less',
							'rsc/less/bootstrap-blog_base.less',
							'rsc/less/bootstrap-item_base.less',
							// Common styles for all bootstrap skins
							'rsc/less/bootstrap-evoskins.less'
						],
					// Bootstrap backoffice styles:
					'rsc/build/bootstrap-backoffice-b2evo_base.bundle.css': [
							// Basic styles for all bootstrap skins
							'rsc/less/bootstrap-basic_styles.less',
							'rsc/less/bootstrap-basic.less',
							// Common styles for all bootstrap skins
							'rsc/less/bootstrap-evoskins.less'
						],
				}
			},
			compress: {
			// This is a separate task special to compress the less files right here
				options: {
					compress: true,
				},
				files: {
					// Bootstrap skins
					'skins_adm/bootstrap/rsc/css/style.css': 'skins_adm/bootstrap/rsc/css/style.less',
					'skins/bootstrap/style.css':             'skins/bootstrap/style.less',
					'skins/bootstrap_main/style.css':        'skins/bootstrap_main/style.less',
					'skins/bootstrap_manual/style.css':      'skins/bootstrap_manual/style.less',
				}
			}
		},

		// Configuration for the scss->css compiling tasks:
		sass: {
			development: {
				options: {
					style: 'expanded',
				},
				files: {
					// target.css file: source.scss file
					//'rsc/build/testscss.css': 'rsc/scss/test.scss',
					'skins/pureforums/pureforums_header.css': 'skins/pureforums/pureforums_header.scss',
					'skins/pureforums/pureforums_main.css': 'skins/pureforums/pureforums_main.scss',
					'skins/pureforums/pureforums_footer.css': 'skins/pureforums/pureforums_footer.scss',
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
				src: ['rsc/css/basic_styles.css', 'rsc/css/basic.css', 'rsc/css/blog_base.css', 'rsc/css/item_base.css'],
				dest: 'rsc/build/b2evo_base.bundle.css',
			},
			skin_evopress: {
				nonull: true, // Display missing files
				src: ['skins/evopress/style.css', 'skins/evopress/item.css'],
				dest: 'skins/evopress/evopress.bundle.css',
			},
			skin_pureforums: {
				nonull: true, // Display missing files
				src: ['skins/pureforums/pureforums_header.css', 'skins/pureforums/pureforums_main.css', 'skins/pureforums/pureforums_footer.css'],
				dest: 'skins/pureforums/pureforums.bundle.css',
			},
			/*
			 * JS:
			 */
			// Login screen:
			sha1_md5: {
				src: ['rsc/js/src/sha1.js', 'rsc/js/src/md5.js'],
				dest: 'rsc/js/build/sha1_md5.bundle.js',
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
				src: 'rsc/build/b2evo_base.bundle.css',
				dest: 'rsc/build/b2evo_base.bmin.css',
			},
			bootstrap_b2evo_base: {
				nonull: true, // Display missing files
				src: 'rsc/build/bootstrap-b2evo_base.bundle.css',
				dest: 'rsc/build/bootstrap-b2evo_base.bmin.css',
			},
			bootstrap_backoffice_b2evo_base: {
				nonull: true, // Display missing files
				src: 'rsc/build/bootstrap-backoffice-b2evo_base.bundle.css',
				dest: 'rsc/build/bootstrap-backoffice-b2evo_base.bmin.css',
			},
			skin_evopress: {
				src: 'skins/evopress/evopress.bundle.css',
				dest: 'skins/evopress/evopress.bmin.css',
			},
			skin_pureforums: {
				src: 'skins/pureforums/pureforums.bundle.css',
				dest: 'skins/pureforums/pureforums.bmin.css',
			},
		},

		// Configuration for the uglify minifying tasks:
		uglify: {
			// Login screen:
			sha1_md5: { 
				nonull: true, // Display missing files
				src: ['rsc/js/build/sha1_md5.bundle.js'],
				dest: 'rsc/js/build/sha1_md5.bmin.js'
			},
			// Another Target:
			/*  Early tests:
			functionsjs: {
				nonull: true, // Display missing files
				src: 'rsc/js/functions.js',
				dest: 'rsc/js/build/functions.min.js'
			},
			ajaxcomjs: {
				options: {
					// Extend default banner:
					banner: '<%= uglify.options.banner %>// This includes 2 files \n'
				},
				nonull: true, // Display missing files
				src: ['rsc/js/ajax.js', 'rsc/js/communication.js'],
				dest: 'rsc/js/build/ajaxcom.min.js',
			},
			*/
			// Colorbox + Voting + Touchswipe
			colorbox: {
				options: {
					banner: '/* This includes 4 files: jquery.colorbox.js, voting.js, jquery.touchswipe.js, colorbox.init.js */\n'
				},
				nonull: true, // Display missing files
				src: ['rsc/js/colorbox/jquery.colorbox.js', 'rsc/js/voting.js', 'rsc/js/jquery/jquery.touchswipe.js', 'rsc/js/colorbox/colorbox.init.js'],
				dest: 'rsc/js/build/colorbox.bmin.js'
			},
			// Bubbletip
			bubbletip: {
				options: {
					banner: '/* This includes 4 files: bubbletip.js, plugins.js, userfields.js, colorpicker.js */\n'
				},
				nonull: true, // Display missing files
				// fp>yura: why isn't jquery.bubbletip.js bundled into this?
				// if plugins.js is used only for editing we should probably move it to a textedit.bundle		
				src: ['rsc/js/bubbletip.js', 'rsc/js/plugins.js', 'rsc/js/userfields.js', 'rsc/js/colorpicker.js'],
				dest: 'rsc/js/build/bubbletip.bmin.js'
			},
			// Popover (Analog of bubbletip on bootstrap skins)
			popover: {
				options: {
					banner: '/* This includes 4 files: bootstrap/usernames.js, bootstrap/plugins.js, bootstrap/userfields.js, bootstrap/colorpicker.js */\n'
				},
				nonull: true, // Display missing files
				src: ['rsc/js/bootstrap/usernames.js', 'rsc/js/bootstrap/plugins.js', 'rsc/js/bootstrap/userfields.js', 'rsc/js/bootstrap/colorpicker.js'],
				dest: 'rsc/js/build/popover.bmin.js'
			},
			// Textcomplete plugin to suggest user names in textareas with '@username'
			textcomplete: {
				options: {
					banner: '/* This includes 2 files: jquery.textcomplete.js, textcomplete.init.js */\n'
				},
				nonull: true, // Display missing files
				src: ['rsc/js/jquery/jquery.textcomplete.js', 'rsc/js/textcomplete.init.js'],
				dest: 'rsc/js/build/textcomplete.bmin.js'
			},
		},

		// Markdown to HTML
		markdown: {
			options: {
				template: 'readme.template.html'
			},
			files: {
				expand: true,
				src: 'readme.md',
				dest: '',
				ext: '.html'
			}
		},

		// Configuration for the watch tasks:
		watch: {
			/* Early tests:
			functionsjs: {
				files: ['rsc/js/functions.js'],
				tasks: ['uglify:functionsjs'],
			},
			ajaxcomjs: {
				files: ['rsc/js/ajax.js', 'rsc/js/communication.js'],
				tasks: ['uglify:ajaxcomjs'],
			},
			*/
			less: {
				// Which files to watch (all .less files recursively in the whole blogs directory)
				files: ['**/*.less'],
				tasks: ['less'],
				options: {
					nospawn: true,
				}
			},
			sass: {
				// Which files to watch (all .scss files recursively in the scss directory)
				files: ['**/*.scss'],
				tasks: ['sass'],
				options: {
					nospawn: true,
				}
			},
			concat_cssmin: {
				// Which files to watch (all .css files recursively in the whole blogs directory)
				files: ['**/*.css'],
				tasks: ['concat','cssmin'],
				options: {
					nospawn: true,
				}
			},
			markdown: {
				files: ['readme.md','readme.template.html'],
				tasks: ['markdown']
			}
		}


	});

	// Load the plugin that provides the tasks ( "uglify", "less", "sass", etc. ):
	grunt.loadNpmTasks('grunt-contrib-concat');
	grunt.loadNpmTasks('grunt-contrib-cssmin');
	grunt.loadNpmTasks('grunt-contrib-less');
	grunt.loadNpmTasks('grunt-contrib-sass');
	grunt.loadNpmTasks('grunt-contrib-uglify');
	grunt.loadNpmTasks('grunt-contrib-watch');
	grunt.loadNpmTasks('grunt-markdown');

	// Default task(s):
	grunt.registerTask('default', ['less','sass','concat','cssmin','uglify']);

};