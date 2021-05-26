const mix = require('laravel-mix');
const publicPath = "dist/";
const resourcesPath = "../";

// Set mix paths config
mix.setPublicPath(publicPath);
mix.setResourceRoot(resourcesPath);

// Remove extra moment local files
mix.webpackConfig(webpack => {
  return {
    plugins: [
      new webpack.ContextReplacementPlugin(
        /moment[\/\\]locale/,
        // A regular expression matching files that should be included
        /(en-gb)\.js/
      )
    ]
  };
});

// Add assets for mix to compile
mix.sass(
  'src/scss/theme-vendors.scss',
  'css'
).sass(
  'src/scss/theme-main.scss',
  'css'
).copy(
  'src/images',
  publicPath + 'images'
).extract();

if (mix.inProduction()) {
  mix.version();
} else {
  mix.sourceMaps().webpackConfig({
    devtool: 'source-map',
  });
}
