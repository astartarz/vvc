'use strict';

var gulp = require('gulp'),
  sass = require('gulp-sass'),
  sourcemaps = require('gulp-sourcemaps'),
  postcss = require('gulp-postcss'),
  autoprefixer = require('autoprefixer');

gulp.task('sass', function () {
  return gulp.src('./scss/[^_]*')
    .pipe(sourcemaps.init())
    .pipe(sass({
      outputStyle: 'expanded',
      precision: 10
    }).on('error', sass.logError))
    .pipe(sourcemaps.write('../sourcemaps'))
    .pipe(gulp.dest('./css'));
});

gulp.task('postcss', function () {
  return gulp.src('./*.css')
    .pipe(sourcemaps.init())
    .pipe(postcss([autoprefixer({
      browsers: ['last 3 versions']
    })]))
    .pipe(sourcemaps.write('../sourcemaps'))
    .pipe(gulp.dest('./'));
});

gulp.task('watch', function () {
  gulp.watch(['./scss/*.scss'], ['sass', 'postcss']);
});
