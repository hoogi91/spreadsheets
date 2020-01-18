const TerserPlugin = require('terser-webpack-plugin');
const path = require('path');
const webpack = require('webpack');

module.exports = (env, argv) => ({
    optimization: {
        minimizer: [
            new TerserPlugin({
                cache: true,
                parallel: true,
                sourceMap: false,
                terserOptions: {
                    output: {
                        comments: false,
                    },
                },
                extractComments: false,
            }),
        ],
    },
    entry: {
        "SpreadsheetDataInput": path.join(__dirname, "/Resources/Private/Assets/JavaScript/main.js"),
    },
    module: {
        rules: [
            {
                test: /\.(js)$/,
                exclude: /node_modules/,
                use: [
                    "babel-loader",
                    "eslint-loader",
                ],
            }
        ]
    },
    output: {
        filename: "[name].js",
        libraryTarget: "amd",
        path: path.join(__dirname, "/Resources/Public/JavaScript"),
        publicPath: argv.mode !== "production" ? "/" : "../dist/",
        umdNamedDefine: true
    },
    externals: {
        // require("jquery") is external and available on the global var jQuery
        "jquery": "jquery",
        // require("jquery") is external and available on the global var jQuery
        "handsontable": "Handsontable"
    }
});
