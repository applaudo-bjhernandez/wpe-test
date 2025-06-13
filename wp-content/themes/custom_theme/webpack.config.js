const path = require('path');
const webpack = require('webpack');
const CopyPlugin = require('copy-webpack-plugin');

var config = {
  entry: {
    'app': './src/js/main.js',
  },
  output: {
    filename: 'js/main.js',
    path: path.resolve(__dirname, '../src-wordpress/themes/custom_theme/dist')
  },
  module: {
    rules: []
  },
  externals: {
    jquery: 'jQuery'
  },
  plugins: [
    new CopyPlugin({
      patterns: [
        { from: "./*.php", to: "../" },
        { from: "./*.css", to: "../" }
      ],
    })
  ]
};

module.exports = (env, argv) => {
  if (argv.mode !== 'production') {
    config.devtool = 'source-map';
  }

  return config;
};
