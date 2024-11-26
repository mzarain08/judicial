const path = require('path');
const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const ExtractTextPlugin = require("extract-text-webpack-plugin");
const themePath ='./wp-content/themes/judicial-watch/assets/';
const VueLoaderPlugin = require('vue-loader/lib/plugin')

/**
 * Compile Sass
 *
 * Compiles Sass for frontend theme.
 */
const sassCompilation = {
    entry: {
        'judicial-watch': './build/styles/judicial-watch.scss',
        'judicial-watch-admin': './build/styles/judicial-watch-admin.scss',
    },
    output: {
        path: path.resolve(__dirname, themePath + 'styles/'),
        filename: '[name].css'
    },
    module: {
        rules: [
            {
                test: /\.scss$/,
                use: ExtractTextPlugin.extract({
                    fallback: 'style-loader',
                    use: [
                        'css-loader',
                        'sass-loader'
                    ]
                })
            }
        ]
    },
    plugins: [
        new ExtractTextPlugin('[name].css')
    ]
};

/**
 * Frontend Javascript
 *
 * Compiles Javascript assets for frontend theme.
 */

const javascriptCompilation = {
    entry: {
        'judicial-watch': './build/scripts/judicial-watch.js',
        'vendor': './build/scripts/vendor.js',
    },
    output: {
        path: path.resolve(__dirname, themePath + 'scripts/'),
        filename: '[name].js'
    },
    module: {
        rules: [
            {
                test: /\.js$/,
                exclude: /node_modules/,
                loader: "babel-loader"
            }
        ]
    }
};


/**
 * Donate Form Component
 *
 * A donate form component explicitly for the /donate page.
 */

const donateFormComponent = {
    entry: {
        'vue-donate-component': './build/scripts/components/Donate/vue-donate-component.js'
    },
    output: {
        path: path.resolve(__dirname, themePath + 'scripts/'),
        filename: '[name].js'
    },
    module: {
        rules: [
            {
                test: /\.js$/,
                exclude: /node_modules/,
                loader: "babel-loader"
            },
            {
                test: /\.vue$/,
                loader: 'vue-loader'
            }
        ]
    },
    plugins: [
        new VueLoaderPlugin()
    ]
};

/**
 * Export Configuration
 */
module.exports = [
    sassCompilation, javascriptCompilation,
    donateFormComponent
];