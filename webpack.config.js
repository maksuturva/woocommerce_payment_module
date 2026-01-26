const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');
const DependencyExtractionWebpackPlugin = require('@wordpress/dependency-extraction-webpack-plugin');

module.exports = {
	...defaultConfig,
	entry: {
		'svea-payments-blocks': path.resolve(process.cwd(), 'src', 'index.js'),
	},
	output: {
		path: path.resolve(process.cwd(), 'assets/js'),
		filename: '[name].js',
	},
	externals: {
		...defaultConfig.externals,
		'@woocommerce/blocks-registry': ['wc', 'wcBlocksRegistry'],
		'@woocommerce/settings': ['wc', 'wcSettings'],
	},
};
