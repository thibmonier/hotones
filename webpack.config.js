// webpack.config.js
const Encore = require('@symfony/webpack-encore');
const RtlCssPlugin = require('rtlcss-webpack-plugin');

Encore
  .setOutputPath('public/assets/')
  .setPublicPath('/assets/')
  .addEntry('app', './assets/app.js')
  .addEntry('bootstrap', './assets/scss/bootstrap.scss')
  .addEntry('icons', './assets/scss/icons.scss')
  .addEntry('toast', './assets/js/toast.js')
  .addEntry('form-validation', './assets/js/form-validation.js')
  .addEntry('dependent-fields', './assets/js/dependent-fields.js')
  .addEntry('form-wizard', './assets/js/form-wizard.js')
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
   * Using rtlcss-webpack-plugin for automatic RTL CSS generation
   */
  .addPlugin(new RtlCssPlugin({
    filename: 'css/[name]-rtl.min.css',
  }))

  /*
   * FEATURE CONFIG
   */
  .cleanupOutputBeforeBuild()
  .enableBuildNotifications()

  // Configure Stimulus controllers.json alias
  .addAliases({
    '@symfony/stimulus-bridge/controllers.json': require('path').resolve(__dirname, 'assets/controllers.json')
  });

module.exports = Encore.getWebpackConfig();
