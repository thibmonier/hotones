// webpack.config.js
const Encore = require('@symfony/webpack-encore');
const RtlCssPlugin = require('rtlcss-webpack-plugin');

Encore
  .setOutputPath('public/assets/')
  .setPublicPath('/assets/')
  .addEntry('app', './assets/scss/app.scss')
  .addEntry('bootstrap', './assets/scss/bootstrap.scss')
  .addEntry('icons', './assets/scss/icons.scss')
  // .addEntry('custom', './assets/scss/custom.scss')
  .enableSingleRuntimeChunk()
  .enableSassLoader()
  .configureFilenames({
    css: 'css/[name].min.css',
  })

  .copyFiles({
    from: './assets/fonts',
    to: 'fonts/[name].[ext]',
  })

  .copyFiles({
    from: './assets/images',
    to: 'images/[path][name].[ext]',
  })

  .copyFiles({
    from: './assets/js',
    to: 'js/[path][name].[ext]',
  })

  .copyFiles({
    from: './assets/json',
    to: 'json/[name].[ext]',
  })

  .copyFiles({
    from: './assets/lang',
    to: 'lang/[name].[ext]',
  })

  .copyFiles({
    from: './assets/libs',
    to: 'libs/[path][name].[ext]',
  })

  /*
   * RTL CSS GENERATION
   */
  // .addLoader({
  //   test: /\.scss$/,
  //   use: [
  //     'css-loader',
  //     'sass-loader',
  //     {
  //       loader: 'sass-loader',
  //       options: {
  //         // rtlcss options, if needed
  //       },
  //     },
  //   ],
  // })

  // .webpackConfig({
  //   plugins: [
  //       new RtlCssPlugin()
  //   ],
  //   stats: {
  //       children: true,
  //   },
  // })

  .addPlugin(new RtlCssPlugin({
    filename: 'css/[name]-rtl.min.css',
  }))

  /*
   * FEATURE CONFIG
   */
  .cleanupOutputBeforeBuild()
  .enableBuildNotifications();

module.exports = Encore.getWebpackConfig();
