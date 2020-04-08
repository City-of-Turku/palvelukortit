All style changes are done in the src/scss folder and generated into css automatically using gulp.

Note!
None of the dist files are included in git, which means less conflicts of compiled css files.
The dist files are compiled when deploying to test/production.

For everything to work correctly you need to install required modules. Run the following:

    npm i

After this you can run the command that compiles and copies SCSS, JS and fonts from ./src to ./dist.
The css and js files will be minified.

    npm run gulp

To update icons and generate SVG sprite, you can run:

    npm run generate-svg-sprite

