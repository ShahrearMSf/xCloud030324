/* eslint-disable */

var path              = require( 'path' );
var UglifyJSPlugin    = require( 'uglifyjs-webpack-plugin' );
var ExtractTextPlugin = require( 'extract-text-webpack-plugin' );
var SpritesmithPlugin = require( 'webpack-spritesmith' );

module.exports = {
  entry  : './src/index.js',
  output : {
    path    : path.resolve( __dirname , 'dist' ),
    filename : 'amazon.js'
  },
    module : {
        rules : [
            {
                test    : /\.js$/,
                exclude : '/node_modules/',
                use     : 'babel-loader'
            },
            {
                test    : /\.scss$/,
                exclude : '/node_modules/',
                use     : ExtractTextPlugin.extract( {
                    use : [ { loader : 'css-loader' , options : { minimize : true } } , 'sass-loader' ]
                } )
            },
            {
                test    : /\.png$/,
                exclude : '/node_modules/',
                loaders : [ 'file-loader?name=i/[hash].[ext]' ]
            }
        ]
    },
    resolve : {
        modules : [ "node_modules" , "spritesmith-generated" ]
    },
    plugins : [
        new UglifyJSPlugin(),
        new ExtractTextPlugin( 'amazon.css' ),
        new SpritesmithPlugin( {
            src : {
                cwd  : path.resolve( __dirname , 'src/assets/images' ),
                glob : '*.png'
            },
            target : {
                image : path.resolve( __dirname , 'src/assets/spritesmith-generated/sprite.png' ),
                css   : path.resolve( __dirname , 'src/assets/spritesmith-generated/sprite.scss' )
            },
            apiOptions : {
                cssImageRef : "~sprite.png"
            }
        } )
    ]
};
