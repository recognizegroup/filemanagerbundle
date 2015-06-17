var gulp = require('gulp');
var sass = require('gulp-sass');
var uglify = require('gulp-uglify');
var concat = require('gulp-concat');


var filemanager_theme_dir = "./src/Recognize/FilemanagerBundle/Resources/theme";

module.exports = function( gulp ){
    gulp.task("filemanager-img", function(){
        return gulp.src([ filemanager_theme_dir + '/*.png',
            filemanager_theme_dir + '/*.gif'])
            .pipe( gulp.dest("./web/filemanager") );
    });

    gulp.task("filemanager-sass", function(){
        return gulp.src(filemanager_theme_dir + "/base.bootstrap.scss")
            .pipe( sass() )
            .pipe( concat('filemanager-theme.css') )
            .pipe( gulp.dest("./web/filemanager") );
    });

    gulp.task("filemanager-theme", ["filemanager-img", "filemanager-sass"]);
};