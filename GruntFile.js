module.exports = function(grunt) {

    grunt.initConfig({
        jasmine: {
            test: {
                src: 'Resources/public/js/*.js',
                options: {
                    specs: 'Tests/Resources/*Test.js',
                    helpers: 'Tests/Resources/*Helper.js',
                    vendor:  [
                        'Resources/public/js/jquery-1.11.2.min.js',
                        'node_modules/jasmine-jquery/lib/jasmine-jquery.js',
                        'Resources/public/js/jstree.js'
                    ]
                }
            }
        }
    });

    grunt.loadNpmTasks('grunt-contrib-jasmine');
    grunt.registerTask('default', ['jasmine']);
};