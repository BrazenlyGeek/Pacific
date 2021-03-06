'use strict';
module.exports = function(grunt) {
	require('load-grunt-tasks')(grunt);
	require('time-grunt')(grunt);
	grunt.loadNpmTasks('grunt-contrib-copy');

	var jsFileList = [
		'dev/vendor/bootstrap/js/transition.js',
	    'dev/vendor/bootstrap/js/alert.js',
	    'dev/vendor/bootstrap/js/button.js',
	    'dev/vendor/bootstrap/js/carousel.js',
	    'dev/vendor/bootstrap/js/collapse.js',
	    'dev/vendor/bootstrap/js/dropdown.js',
	    'dev/vendor/bootstrap/js/modal.js',
	    'dev/vendor/bootstrap/js/tooltip.js',
	    'dev/vendor/bootstrap/js/popover.js',
	    'dev/vendor/bootstrap/js/scrollspy.js',
	    'dev/vendor/bootstrap/js/tab.js',
	    'dev/vendor/bootstrap/js/affix.js',
		'dev/js/*.js'
	];

	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),

		compress: {
			main: {
				options: {
					archive: 'release/<%= pkg.name %>.zip'
				},
				files: [
					{
						expand: true,
						cwd: 'pacific/',
						src: ['**'],
						dest: '<%= pkg.name %>/'
					}
				]
			}
		},
		jshint: {
			options: {
				jshintrc: '.jshintrc'
			},
			all: [
				'Gruntfile.js',
				'dev/js/*.js'
			]
		},
		less: {
			dev: {
				files: {
					'pacific/assets/css/<%= pkg.name %>.css': [
						'dev/less/<%= pkg.name %>.less'
					]
				},
				options: {
					compress: false,
					// LESS source map
					// To enable, set sourceMap to true and update sourceMapRootpath based on your install
					sourceMap: true,
					sourceMapFilename: 'pacific/assets/css/<%= pkg.name %>.css.map'
				}
			},
			build: {
				files: {
					'pacific/assets/css/<%= pkg.name %>.min.css': [
						'dev/less/<%= pkg.name %>.less'
					]
				},
				options: {
					compress: true
				}
			}
		},
		concat: {
			options: {
				separator: ';',
			},
			dist: {
				src: [
					jsFileList
				],
				dest: 'pacific/assets/js/<%= pkg.name %>.js',
			},
		},
		uglify: {
			dist: {
				files: {
					'pacific/assets/js/<%= pkg.name %>.min.js': [
						jsFileList
					]
				}
			}
		},
		autoprefixer: {
			options: {
				browsers: [
					'last 2 versions',
					'ie 8',
					'ie 9',
					'android > 4',
					'> 5%',
					'> 1% in US'
				]
			},
			dev: {
				options: {
					map: {
						prev: 'pacific/assets/css/'
					}
				},
				src: 'pacific/assets/css/<%= pkg.name %>.css'
			},
			build: {
				src: 'pacific/assets/css/<%= pkg.name %>.min.css'
			}
		},
		watch: {
			less: {
				files: [
					'dev/less/*.less',
					'dev/less/**/*.less'
				],
				tasks: [
					'less:dev',
					'autoprefixer:dev'
				]
			},
			js: {
				files: [
					jsFileList,
					'<%= jshint.all %>'
				],
				tasks: [
					'jshint',
					'concat'
				]
			},
			livereload: {
				options: {
					livereload: true
				},
				files: [
					'dev/**',
					'pacific/*.php',
					'pacific/**/*.php'
				]
			}
		},
		copy: {
			main: {
				files: [
					{
						expand: true,
						flattin: true,
						isFile: true,
						cwd: 'dev/vendor/fontawesome/fonts/',
						src: [
							'*'
						],
						dest: 'pacific/assets/fonts/'
					},
					{
						expand: true,
						flattin: true,
						isFile: true,
						cwd: 'dev/vendor/html5shiv/dist/',
						src: [
							'*'
						],
						dest: 'pacific/assets/js/'
					}
				]
			}
		}
	});

	// Register tasks
	grunt.registerTask('default', [
		'dev'
	]);
	grunt.registerTask('dev', [
		'jshint',
		'less:build',
		'autoprefixer:build',
		'uglify',
		'copy'
	]);
	grunt.registerTask('build', [
		'jshint',
		'less',
		'autoprefixer',
		'uglify',
		'concat',
		'compress',
		'copy'
	]);
};