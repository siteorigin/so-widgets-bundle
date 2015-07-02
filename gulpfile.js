var gulp = require('gulp');
var rename = require('gulp-rename');
var replace = require('gulp-replace');
var less = require('gulp-less');
var uglify = require('gulp-uglify');
//var closureCompiler = require('gulp-closure-compiler');

gulp.task('version', function(version) {
    var args = {};
    if(process.argv.length > 3) {
        var arr = process.argv.slice(3);
        for (var i = 0; i < arr.length; i++) {
            var argName = arr[i];
            if(argName.match(/-\w+/i)) {
                args[argName.slice(1)] = arr[i + 1];
            }
        }
    }
    if(typeof args.v == "undefined") {
        console.log("version task requires version number argument.");
        console.log("E.g. gulp release 1.2.3");
        return;
    }
    return gulp.src('so-widgets-bundle.php')
        .pipe(replace(/(Version: ).*/, '$1'+args.v))
        .pipe(replace(/(define\('SOW_BUNDLE_VERSION', ').*('\);)/, '$1'+args.v+'$2'))
        .pipe(replace(/(define\('SOW_BUNDLE_JS_SUFFIX', ').*('\);)/, '$1.min$2'))
        .pipe(gulp.dest('tmp'));
});

gulp.task('compileLess', function() {
    return gulp.src(['**/*.less', '!base/less/*.less', '!bower_components/**', '!node_modules/**', '!tmp/**', '!widgets/**'])
        .pipe(less({paths: ['base/less'], compress: true}))
        .pipe(gulp.dest('tmp'));
});

gulp.task('concatScripts', function () {

});

gulp.task('minifyScripts', function () {
    return gulp.src(['**/*.js', '!bower_components/**', '!node_modules/**', '!tests/**', '!tmp/**', '!gulpfile.js'])
        .pipe(rename({ suffix: '.min' }))
        .pipe(uglify())
        .pipe(gulp.dest('tmp'));

    //return gulp.src('admin/admin.js')
    //    .pipe(closureCompiler({
    //        compilerPath: 'bower_components/closure-compiler/compiler.jar',
    //        fileName: 'admin.js'
    //    }))
    //    .pipe(gulp.dest('tmp'));
});

gulp.task('compileJS', ['minifyScripts']);

gulp.task('release', ['version', 'compileLess', 'compileJS'], function () {
    return gulp.src(
        [
            '**/*',
            '!so-widgets-bundle.php',
            '!{bower_components,bower_components/**}',
            '!{node_modules,node_modules/**}',
            '!{tests,tests/**}',
            '!{tmp,tmp/**}',
            '!.gitignore',
            '!bower.json',
            '!gulpfile.js',
            '!package.json'
        ])
        .pipe(gulp.dest('tmp'))
});

