'use strict';

var gulp = require('gulp'),
  imagemin = require('gulp-imagemin'),
  spritesmith = require('gulp.spritesmith'),
  sass = require('gulp-sass'),
  sourcemaps = require('gulp-sourcemaps'),
  postcss = require('gulp-postcss'),
  autoprefixer = require('autoprefixer');

gulp.task('sprite', function () {
  var spriteData = gulp.src('./assets/images/sprite-src/*.png').pipe(spritesmith({
    imgName: 'sprite.png',
    imgPath: './assets/images/sprite.png',
    cssName: '_sprite.scss',
    padding: 2
  }));
  spriteData.img.pipe(gulp.dest('./assets/images'));
  spriteData.css.pipe(gulp.dest('./assets/scss'));
});

gulp.task('imagemin', function () {
  return gulp.src('./assets/images/*')
    .pipe(imagemin())
    .pipe(gulp.dest('images'))
});

gulp.task('sass', function () {
  gulp.src('./assets/scss/*')
    .pipe(sourcemaps.init())
    .pipe(sass({
      outputStyle: 'expanded',
      precision: 10
    }).on('error', sass.logError))
    .pipe(postcss([autoprefixer({
      browsers: ['last 3 versions']
    })]))
    .pipe(sourcemaps.write('./'))
    .pipe(gulp.dest('./assets/css'));
});

gulp.task('imagemin', function () {
  gulp.watch(['./assets/images/**'], ['imagemin']);
});

gulp.task('watch', function () {
  gulp.watch(['./assets/images/sprite-src/*.png', './assets/scss/**/*.scss'], ['sprite', 'sass']);
});
