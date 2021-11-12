const config = {
	presets: [ '@automattic/jetpack-webpack-config/babel/preset' ],
	plugins: [ '@babel/plugin-proposal-nullish-coalescing-operator' ],
	overrides: [
		{
			test: './extensions/',
			presets: [],
			plugins: [
				[
					require.resolve( '@wordpress/babel-plugin-import-jsx-pragma' ),
					{
						scopeVariable: 'createElement',
						scopeVariableFrag: 'Fragment',
						source: '@wordpress/element',
						isDefault: false,
					},
				],
				[
					require.resolve( '@babel/plugin-transform-react-jsx' ),
					{
						pragma: 'createElement',
						pragmaFrag: 'Fragment',
					},
				],
			],
		},
		{
			// Transpile ES Modules syntax (`import`) in config files (but not elsewhere)
			test: [ './gulpfile.babel.js', './tools/webpack.config.js', './tools/builder/' ],
			presets: [
				[
					'@automattic/jetpack-webpack-config/babel/preset',
					{ presetEnv: { modules: 'commonjs' } },
				],
			],
		},
		{
			test: './modules/search/instant-search',
			presets: [ './modules/search/instant-search/babel.config.js' ],
		},
		{
			test: './modules/search/customberg',
			presets: [ './modules/search/customberg/babel.config.js' ],
		},
	],
	env: {
		test: {
			presets: [ [ require.resolve( '@babel/preset-env' ), { targets: { node: 'current' } } ] ],
			plugins: [
				[ require.resolve( '@babel/plugin-transform-runtime' ), { absoluteRuntime: true } ],
			],
		},
	},
};

module.exports = config;
