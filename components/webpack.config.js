let path = require("path");
let webpack = require("webpack");
const { VueLoaderPlugin } = require("vue-loader");

module.exports = {
  entry: "./accordion-view-mode.js",
  mode: "development",
  devtool: "eval-source-map",
  output: {
    path: path.resolve(__dirname, ""),
    filename: "accordion-view-mode.bundle.js",
    publicPath: process.env.WEBPACK_DEV_SERVER === "true" ? "http://127.0.0.1:8080/" : "",
  },
  devServer: {
    static: {
      directory: path.resolve(__dirname, ""),
    },
    hot: true,
    host: "127.0.0.1",
    port: 8080,
    headers: {
      "Access-Control-Allow-Origin": "*",
    },
    allowedHosts: "all",
    client: {
      overlay: true,
    },
    liveReload: false,
  },
  module: {
    rules: [
      {
        test: /\.vue$/,
        loader: "vue-loader"
      },
      {
        test: /\.js$/,
        loader: "babel-loader",
      }
    ],
  },
  plugins: [new VueLoaderPlugin()]
};
