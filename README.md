# wp-proud-admin

Administration theme, permissions tweaks, and ProudCity settings pages. [ProudCity](http://proudcity.com) is a Wordpress platform for modern, standards-compliant municipal websites.

All bug reports, feature requests and other issues should be added to the [wp-proudcity Issue Queue](https://github.com/proudcity/wp-proudcity/issues).

## Development Setup

### Prerequisites

- Node.js (v18+)
- npm

### First-time setup

```bash
npm run projectsetup
```

This single command:
1. Runs `npm install` (installs Vite, sass, bootstrap-sass, FontAwesome, patch-package)
2. Applies `patch-package` patches automatically via the `postinstall` hook
3. Clones `proudcity-patterns` into `node_modules/proudcity-patterns`
4. Runs `patch-patterns` to apply required compatibility fixes to proudcity-patterns

### Build commands

```bash
# One-time production build
npm run build

# Watch mode — rebuilds automatically on file changes
npm run dev
```

Built CSS is output to `dist/styles/proud-admin.css`.

### Updating proudcity-patterns

```bash
npm run projectupdate
```

Re-clones proudcity-patterns and re-applies all patches.

---

## Architecture notes

### Build system

Vite v6 compiles `assets/styles/proud-admin.scss` → `dist/styles/proud-admin.css`.

SCSS is compiled with the modern Sass compiler API (`api: 'modern-compiler'`). Third-party
dependencies (bootstrap-sass, FontAwesome, proudcity-patterns) still use the legacy `@import`
syntax, which is silenced via `silenceDeprecations` in `vite.config.js`.

### proudcity-patterns

`proudcity-patterns` is a git clone, not an npm package. It lives at
`node_modules/proudcity-patterns` and must be cloned manually (or via `projectsetup`/`projectupdate`).

After cloning, `npm run patch-patterns` applies sed patches to make it compatible with
FontAwesome v6:
- Inserts `@import "functions.scss"` before `@import "variables.scss"` in `_font-awesome.scss`
  (FA v6 requires functions to load first)
- Strips the `node_modules/` prefix from FontAwesome paths in `_font-awesome-loader.scss`
- Updates the CDN font path from FA v5.13.0 to v6.7.0

### patch-package patches

Two patches in `patches/` are applied automatically on `npm install`:

- `patches/@fortawesome+fontawesome-free+6.7.2.patch` — fixes deprecated `unquote()` calls
  in FontAwesome's Sass files to use `string.unquote()` from the modern Sass module system
- `patches/bootstrap-sass+3.4.3.patch` — fixes deprecated `percentage()`, `ceil()`, and
  `floor()` calls in bootstrap-sass to use `math.*()` equivalents; also removes a legacy
  asset helper conditional in `_image.scss`

### bourbon

bourbon v4 has been removed. The mixins it provided are replaced by
`assets/styles/loaders/_bourbon-replacement.scss`, which covers the 8 mixins used in this
project: `transition`, `transform`, `animation`, `keyframes`, `transition-property`,
`transition-duration`, `transition-delay`, and `clearfix`.
