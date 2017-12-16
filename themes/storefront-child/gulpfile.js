'use strict';
 
var gulp    = require('gulp');
var sass    = require('gulp-sass');
const shell = require('gulp-shell')
 
gulp.task('sass', function () {
  return gulp.src('sass/style.scss')
    .pipe(sass().on('error', sass.logError))
    .pipe(gulp.dest('.'));
});
 
gulp.task('sass:watch', function () {
  gulp.watch('./**/*.scss', ['sass']);
});
