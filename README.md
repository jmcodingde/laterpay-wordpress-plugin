laterpay-wordpress-plugin
=========================

This is the official LaterPay plugin for selling digital content with WordPress.

Feel free to fork the plugin and adapt it to your needs.

Please get involved in this project and contribute back changes other users would also benefit from.


## Installation

The plugin is available on http://wordpress.org/plugins/laterpay


## Contributing

1. Fork it ( https://github.com/laterpay/laterpay-wordpress-plugin/fork )
2. Create your feature branch (`git checkout -b feature/my_new_feature`)
3. Commit your changes (`git commit -am 'Added some feature'`)
4. Push to the branch (`git push origin feature/my_new_feature`)
5. Create a new Pull Request

This project uses Gulp to build its assets.
Gulp is a node.js module. If you have node.js running, you can install gulp with ```sudo npm install -g gulp```.
Then go to the repository root folder and install the required gulp plugins with ```npm install```.
Now you can run any of the tasks defined in the gulpfile from the repository root folder.
During development you can either watch the repo for changes and automatically recompile the modified assets using ```gulp```.
For exporting the assets for a release, you can also run ```gulp build```.


The plugin uses the CSS preprocessor [Stylus](http://learnboost.github.io/stylus/).
Stylus is a node.js module. If you have node.js running, you can install Stylus with ```sudo npm install -g stylus```.
To generate production CSS from the .styl sources, go to folder 'laterpay' and run ```stylus asset_sources/stylus --out built_assets/css --compress```.

Contributed PHP code must comply with the WordPress coding standards.
We recommend testing it with PHP_CodeSniffer + [standard 'WordPress'](https://github.com/WordPress-Coding-Standards/WordPress-Coding-Standards).

Contributed JS code must be linted with JSHint and the [.jshintrc](https://github.com/laterpay/laterpay-wordpress-plugin/blob/master/.jshintrc) included in this repository.


## Versioning

The LaterPay WordPress plugin uses [Semantic Versioning 2.0.0](http://semver.org)


## Copyright

Copyright 2014 LaterPay GmbH – Released under MIT License
