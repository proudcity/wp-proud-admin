# wp-proud-admin
Administration theme, permissions tweaks, and ProudCity settings pages. [ProudCity](http://proudcity.com) is a Wordpress platform for modern, standards-compliant municipal websites.

All bug reports, feature requests and other issues should be added to the [wp-proudcity Issue Queue](https://github.com/proudcity/wp-proudcity/issues).

### Building notes

This project uses Vite to compile SCSS assets.

#### Initial Setup

```bash
# Install dependencies
npm install --no-audit

# Clone proudcity-patterns into node_modules
git clone git@github.com:proudcity/proudcity-patterns.git node_modules/proudcity-patterns

# Build the CSS
npm run build
```

Or use the combined setup script (note: you must clone patterns separately after):

```bash
npm run projectsetup
```

#### Build Commands

```bash
# Production build
npm run build

# Watch mode (rebuilds on file changes)
npm run dev
```

#### Updating ProudCity Patterns

To update the patterns library to the latest version:

```bash
npm run projectupdate
```

#### Output

Built CSS is output to `dist/styles/proud-admin.css`.