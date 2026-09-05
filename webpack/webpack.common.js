const path = require("path")
const webpack = require("webpack")
const MiniCssExtractPlugin = require("mini-css-extract-plugin")

module.exports = {
    plugins: [
        new MiniCssExtractPlugin({
            filename: (pathData) => {
                return pathData.chunk.name === "actionTag" ? "actiontag.css" : "style.css"
            }
        }),
        new webpack.ProvidePlugin({ $: ['./dollar', 'default'] })
    ],
    entry: {
        "index": "./index.js",
        "actionTag": "./actionTag.js",
    },
    performance: {
        maxEntrypointSize: 1000000,
        maxAssetSize: 1000000
    },
    module: {
        rules: [
            {
                test: /\.less$/i,
                use: [
                    MiniCssExtractPlugin.loader,
                    "css-loader",
                    "less-loader",
                ],
            },
            {
                test: /\.html$/i,
                loader: "html-loader",
            },
        ]
    },
    output: {
        filename: "[name].js",
        path: path.resolve(__dirname, "../"),
    },
}