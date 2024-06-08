var gulp = require('gulp');
var gulpif = require('gulp-if');
var uglify = require('gulp-uglify');
var concat = require('gulp-concat');
var ngAnnotate = require('gulp-ng-annotate');
var util = require('gulp-util');

var fs = require('fs');
var _ = require('lodash');

var scripts = require('./scripts.json');

var compress = false;

gulp.task('controllers', function (done) {

    compress = util.env.compress !== undefined ? util.env.compress : compress;

    //console.log('env compress::', util.env.compress);

    _.forIn(scripts.modules, function (chunkScripts, chunkName) {
        var paths = [];

        //console.log("chunkName: " + chunkName);
        //console.log("chunkScripts: " + chunkScripts);

        chunkScripts.forEach(function (script) {
            if (!fs.existsSync(__dirname + '/' + script)) {
                throw console.error('Required path doesn\'t exist: ' + __dirname + '/' + script)
            }
            paths.push(script);
        });

        gulp.src(paths)
            .pipe(ngAnnotate())
            .pipe(gulpif(compress, uglify().on('error', function(uglify) {
                console.error(uglify.message);
                this.emit('end');
            })))            
            .pipe(concat(chunkName + '.min.js'))            
            .pipe(gulp.dest('assets/dist/js'));

        done();
    });

});


gulp.task('directives', function(done) {
    gulp.src('assets/js/directives/**/*.js')
    .pipe(gulpif(compress, uglify()))
    .pipe(concat('directives.min.js'))
    .pipe(gulp.dest('assets/dist/js'))

    done();    
});

gulp.task('vendor', function(done) {
    gulp.src('assets/modules/**/*.js')
    .pipe(uglify())
    .pipe(concat('script.js'))
    .pipe(gulp.dest('assets/dist/js'))

    done();    
});

gulp.task('app', function(done) {
    gulp.src('assets/modules/**/*.js')
    .pipe(uglify())
    .pipe(concat('script.js'))
    .pipe(gulp.dest('assets/dist/js'))

    done();    
});

gulp.task(
    'prod', 
    gulp.series('controllers', 'directives')
);

gulp.task(
    'dev', 
    gulp.series('controllers', 'directives')
);

gulp.task(
    'default',
    gulp.series('prod')
);

gulp.task('watch', function(){
    gulp.watch('assets/js/directives/**/*.js', gulp.series('controllers', 'directives') );
    gulp.watch('assets/modules/**/*.js', gulp.series('controllers', 'directives') );
    gulp.watch('assets/js/controllers/**/*.js', gulp.series('controllers', 'directives') );
});