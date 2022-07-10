const path = require("path")

module.exports = {
    mode: "development",
    devtool: "inline-source-map",
    entry: "./index.js",
    resolve: {
        extensions: [".js"]
    },
    module: {
        rules: [
            {
                test: /\.css$/,
                use: [
                    "style-loader",
                    "css-loader"
                ]
            },
            {
                test: /\.less$/i,
                use: [
                    "style-loader",
                    "css-loader",
                    "less-loader",
                ],
            },
        ]
    },
    output: {
        filename: "bundle.js",
        path: path.resolve(__dirname, "../"),
    },
}