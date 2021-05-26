'use strict';

var gulp = require('gulp'),
    imagemin = require('gulp-imagemin'),
    spritesmith = require('gulp.spritesmith'),
    sass = require('gulp-sass'),
    sourcemaps = require('gulp-sourcemaps'),
    postcss = require('gulp-postcss'),
    autoprefixer = require('autoprefixer');

gulp.task('sprite', function () {
  var spriteData = gulp.src('images/sprite-src/*.png').pipe(spritesmith({
    imgName: 'sprite.png',
    imgPath: '../images/sprite.png',
    cssName: '_sprite.scss',
    padding: 2
  }));
  spriteData.img.pipe(gulp.dest('images'));
  spriteData.css.pipe(gulp.dest('scss'));
});

gulp.task('imagemin', function () {
  return gulp.src('images/*')
      .pipe(imagemin())
      .pipe(gulp.dest('images'))
});

gulp.task('sass', function () {
  gulp.src('./scss/*')
      .pipe(sourcemaps.init())
      .pipe(sass({
        outputStyle: 'expanded',
        precision: 10
      }).on('error', sass.logError))
      .pipe(postcss([autoprefixer({
        browsers: ['last 2 versions']
      })]))
      .pipe(sourcemaps.write('./'))
      .pipe(gulp.dest('./css'));
});

gulp.task('imagemin', function () {
  gulp.watch(['images/**'], ['imagemin']);
});

gulp.task('watch', function () {
  gulp.watch(['images/sprite-src/*.png', './scss/**/*.scss'], ['sprite', 'sass']);
});
