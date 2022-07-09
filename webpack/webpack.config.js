const path = require('path')

module.exports = {
    mode: 'development',
    entry: './index.js',
    resolve: {
        extensions: ['.js']
    },
    module: {
        rules: [
            {
                test: /\.css$/,
                use: [
                    { loader: 'css-loader', options: { importLoaders: 1 } }
                ]
            }
        ]
    },
    output: {
        filename: 'bundle.js',
        path: path.resolve(__dirname, '../'),
    },
}