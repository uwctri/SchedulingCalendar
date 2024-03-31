const path = require("path")
const MiniCssExtractPlugin = require("mini-css-extract-plugin");

module.exports = {
    mode: "development",
    devtool: "inline-source-map",
    plugins: [new MiniCssExtractPlugin({ filename: 'style.css' })],
    entry: {
        "calendar": "./index.js",
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