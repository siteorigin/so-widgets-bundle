var gulp = require('gulp');
var del = require('del');
var rename = require('gulp-rename');
var replace = require('gulp-replace');
var less = require('gulp-less');
var uglify = require('gulp-uglify');

var args = {};
args.env = 'dev';
if(process.argv.length > 2) {
    var arr = process.argv.slice(2);
    args.target = arr[0];
    for (var i = 0; i < arr.length; i++) {
        var argName = arr[i];
        if(argName.match(/-\w+/i)) {
            args[argName.slice(1)] = arr[i + 1];
        }
    }
}

var outDir = 'dist';

gulp.task('clean', function () {
    del([outDir]);
});

gulp.task('version', ['clean'], function() {
    if(typeof args.v == "undefined") {
        console.log("version task requires version number argument.");
        console.log("E.g. gulp release 1.2.3");
        return;
    }
    return gulp.src('so-widgets-bundle.php')
        .pipe(replace(/(Version: ).*/, '$1'+args.v))
        .pipe(replace(/(define\('SOW_BUNDLE_VERSION', ').*('\);)/, '$1'+args.v+'$2'))
        .pipe(replace(/(define\('SOW_BUNDLE_JS_SUFFIX', ').*('\);)/, '$1.min$2'))
        .pipe(gulp.dest(outDir));
});

gulp.task('less', ['clean'], function() {
    return gulp.src(
        [
            '**/admin/**/*.less',
            '**/base/**/*.less',
            '**/widgets/**/*.less',
            '!base/less/',
            '!widgets/**/styles/*.less'
        ])
        .pipe(less({paths: ['base/less'], compress: true}))
        .pipe(gulp.dest(outDir));
});

gulp.task('concat', ['clean'], function () {

});

gulp.task('minify', ['concat'], function () {
    return gulp.src(
        [
            '**/admin/**/*.js',
            '**/base/**/*.js',
            '**/js/**/*.js',
            '**/widgets/**/*.js',
            '!{node_modules,node_modules/**}',  // Ignore node_modules/ and contents
            '!{tests,tests/**}',                 // Ignore tests/ and contents
            '!{dist,dist/**}'                 // Ignore tests/ and contents
        ])
        .pipe(rename({ suffix: '.min' }))
        .pipe(uglify())
        .pipe(gulp.dest(outDir));
});

gulp.task('build', ['version', 'less', 'minify'], function () {
    //Just copy remaining files.
    return gulp.src(
        [
            '**/!(*.js|*.less)',                // Everything except .js and .less files
            '!phpunit.xml',                     // Not the unit tests configuration file.
            '!so-widgets-bundle.php',           // Not the base plugin file, it is copied by the 'version' task.
            '**/widgets/**/styles/*.less',      // All the widgets' runtime .less files
            '!**/widgets/**/styles/*.css',      // Don't copy any .css files compiled from runtime .less files
            '!{node_modules,node_modules/**}',  // Ignore node_modules/ and contents
            '!{tests,tests/**}',                 // Ignore tests/ and contents
            '!{dist,dist/**}'                 // Ignore tests/ and contents
        ])
        .pipe(gulp.dest(outDir));
});