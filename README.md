# wp-proud-admin
Administration theme, permissions tweaks, and ProudCity settings pages. [ProudCity](http://proudcity.com) is a Wordpress platform for modern, standards-compliant municipal websites.

All bug reports, feature requests and other issues should be added to the [wp-proudcity Issue Queue](https://github.com/proudcity/wp-proudcity/issues).

### Building notes
You should install [Node Version Manager](https://github.com/nvm-sh/nvm) to run
the commands below and work on Node v12 for this build.

```
nvm use 12
# clones our proudcity-patterns repository and sets it up as the theme expects
npm run-script projectsetup
# build project
npx mix
```

To update ProudCity Patterns run the following NPM command to delete the old repository and download the latest master branch.

```
npm run-script projectupdate
```