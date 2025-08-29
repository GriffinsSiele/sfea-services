const path = require('path');
const TerserPlugin = require('terser-webpack-plugin');
const nodeExternals = require('webpack-node-externals');
const { dependencies } = require('./package.json')
const Dotenv = require('dotenv-webpack');
const webpack = require("webpack");


module.exports = {
  mode: 'development',
  entry: {
    // dist: './src/logic.ts',
    dist: './_bundles/dist.js'
  },
  output: {
    path: path.resolve(__dirname, '_bundles'),
    // filename: 'dist.js',
    filename: '1.js',

  },
  resolve: {
    extensions: [".ts", ".tsx", ".js"],
    fallback: {
      "fs": false,
      "net": require.resolve("net-socket/"),
      "url": require.resolve("url/"),
      "path": require.resolve('path/'),
    }
  },
  devtool: "inline-source-map",
  optimization: {
    // minimize: true,1
    // minimizer: [new TerserPlugin()]
  },
  module: {
    rules: [
      {
        test: /\.(ts|js)$/,
        exclude: /node_modules/,
        use: {
          loader: "babel-loader",
          options: {
            presets: [
              [
                "@babel/preset-env",
                {
                  include: [
                    "@babel/plugin-proposal-optional-chaining", // parsing fails on optional operator without this
                  ],
                },
              ],
              "@babel/preset-typescript",
            ],
          },
        },
      },
    ],
  },
  plugins: [
    new webpack.ProvidePlugin({
      Buffer: ['buffer', 'Buffer'],
    }),
    new Dotenv(),
    new webpack.ProvidePlugin({
      process: 'process/browser',
    }),
  ],
    externals: [ {
    'webpage':  'commonjs webpage'
  }]
};


