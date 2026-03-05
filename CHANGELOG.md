# Changelog

## [Unreleased]

### Changed
- Migrated build system from legacy Grunt/Gulp to Vite 6
- Switched Vite SCSS preprocessor from `api: 'legacy'` to `api: 'modern-compiler'`
- Converted all project SCSS from `@import` to `@use`/`@forward` (Dart Sass 3.0 readiness)
- Added `assets/styles` to Sass `loadPaths` so pattern files can reference project loaders
- Added `node_modules` to `loadPaths` so fontawesome SCSS is resolvable by package name
- Upgraded Sass from 1.56.x to 1.74.x+ to support modern deprecation tooling
- Added `"type": "module"` to package.json to resolve Vite CJS Node API deprecation warning
- Removed `bourbon` v4 from `devDependencies` and from `vite.config.js` `includePaths` — replaced with a lightweight project-owned drop-in

### Fixed

#### @import → @use/@forward migration (Dart Sass 3.0) — project files fully resolved
- `assets/styles/loaders/_load-variables.scss`: `@import` → `@forward` for both variable source files
- `assets/styles/loaders/_load-local.scss`: `@import` → `@forward` (bootstrap/mixins excluded to avoid mixin-name conflicts with bourbon-replacement)
- `assets/styles/proud-admin.scss`: all `@import` → `@use`; added namespace aliases for duplicate basenames (`layouts/gravity-forms`, `modules/gravity-forms`, `pattern-scss/wp/so-pagebuilder`)
- `assets/styles/components/_forms.scss`, `_grid.scss`, `_wp-classes.scss`: added `@use '../loaders/load-local' as *`
- `assets/styles/layouts/_toolbar.scss`, `_misc.scss`, `_dashboard.scss`: added `@use '../loaders/load-local' as *`
- `assets/styles/loaders/_bootstrap-mixins-bridge.scss` (new): legacy bridge that loads bootstrap/mixins with variables in scope — required because `bootstrap/mixins` uses `$grid-gutter-width` as a default parameter value, which is unavailable in the new module scope
- All proudcity-patterns mixin sub-files: added `@use 'pattern-scss/local-variables' as *` so variables are in scope when forwarded as modules; `_form.scss` also gets `@use './font-awesome' as *`; `_nav.scss` also gets `@use 'loaders/bourbon-replacement' as *` for `clearfix`
- `proudcity-patterns/_proudcity-mixins.scss`: all `@import "mixins/..."` → `@forward "mixins/..."`
- `proudcity-patterns/wp/_admin-menu.scss`, `_sidebar.scss`, `_so-pagebuilder.scss`, `_widget-proud-jumbotron-header.scss`: added `@use 'loaders/load-local' as *`
- `proudcity-patterns/mixins/_font-awesome.scss`: changed fontawesome `@import` paths from `node_modules/@fortawesome/...` to `@fortawesome/...` (works with `node_modules` in Sass `loadPaths`)
- All proudcity-patterns @use/@forward changes added to `npm run patch-patterns` so they apply on fresh clone

**Remaining `[import]` warnings** (require package upgrades, deferred):
- `bootstrap-sass` v3 internal files — Task #5
- `proudcity-patterns/mixins/_font-awesome.scss` 3 `@import` lines — Task #6 (FontAwesome v6 upgrade)

#### [global-builtin] / [color-functions] — proudcity-patterns fully resolved
- `proudcity-patterns/_local-variables.scss`: added `@use 'sass:color'`, `@use 'sass:math'`, `@use 'sass:string'` at top
- Replaced all `lighten()` → `color.adjust($color, $lightness: N%)` (5 instances)
- Replaced all `darken()` → `color.adjust($color, $lightness: -N%)` (19 instances, including 4 nested `darken(adjust-hue(...))`)
- Replaced `lighten(desaturate($brand-primary, 50%), 35%)` → `color.adjust(color.adjust(..., $saturation: -50%), $lightness: 35%)`
- Replaced nested `darken(adjust-hue($state-X-bg, -10), N%)` → `color.adjust(color.adjust(..., $hue: -10deg), $lightness: -N%)` for all 4 state colors
- Replaced `fade_in()` → `color.adjust($color, $alpha: N)`
- Replaced all `ceil()` → `math.ceil()` (4 instances)
- Replaced all `floor()` → `math.floor()` (3 instances)
- Replaced `unquote()` → `string.unquote()` (1 instance)
- `proudcity-patterns/mixins/_media-queries.scss`: added `@use 'sass:list'`; replaced `length()` → `list.length()` in `respond()` mixin
- All changes codified in `npm run patch-patterns` so they apply on fresh clone

**Remaining `[global-builtin]` warnings** (third-party packages, deferred):
- `@fortawesome/fontawesome-free/scss/_variables.scss`: `unquote()` — Task #6 (FontAwesome v6 upgrade)
- `bootstrap-sass/mixins/_grid.scss`: `percentage()` — Task #5 (bootstrap-sass patches)

#### slash-div (Dart Sass 2.0) — fully resolved
- Fixed `slash-div` deprecation in project-owned `assets/styles/components/_wp-classes.scss` (4 instances of `$line-height-computed / 2` → `* 0.5`)
- Fixed `slash-div` across all proudcity-patterns SCSS files by replacing `/` division with `* 0.5` / `* 0.25` multiplication equivalents:
  - `pattern-scss/_local-variables.scss` (lines 497–498)
  - `pattern-scss/helpers/_utilities.scss` (6 instances)
  - `pattern-scss/helpers/_grid.scss` — replaced `percentage(((1 / $grid-columns) / 2))` with `calc(50% / #{$grid-columns})`
  - `pattern-scss/_navbar.scss` (lines 39, 348)
  - `pattern-scss/_social-wall.scss` (lines 164, 165, 197)
  - `pattern-scss/_proudbar.scss` — replaced `114px/22px*$proudbar-logo-height` with `$proudbar-logo-height * 5.18182` (lines 68, 69, 71)
  - `pattern-scss/_page-header.scss` (line 7)
  - `pattern-scss/vendor/_card.scss` (line 50)
  - `pattern-scss/vendor/_hamburger.scss` (line 90)
  - `pattern-scss/wp/_so-pagebuilder.scss` (lines 66–71, including no-space division pattern)
- Fixed `slash-div` in `@fortawesome/fontawesome-free` v5: `$fa-fw-width: (20em / 16)` → `1.25em` via patch-package

### Added
- `assets/styles/loaders/_bourbon-replacement.scss` — lightweight Bourbon v4 drop-in providing only the 8 mixins the project actually uses (`transition`, `transform`, `animation`, `keyframes`, `transition-property`, `transition-duration`, `transition-delay`, `clearfix`). Vendor prefixes omitted — all are universally supported unprefixed since 2015.
- `patch-package` dev dependency with `postinstall` hook — applies npm package patches automatically on `npm install`
- `patches/@fortawesome+fontawesome-free+5.15.4.patch` — fixes slash-div in fontawesome v5 Sass variables
- All proudcity-patterns slash-div fixes codified in `npm run patch-patterns` so they apply automatically on fresh clone via `projectsetup` / `projectupdate`

### Known Issues / Remaining Work
Remaining deprecations are all Dart Sass 3.0 concerns (not 2.0) except where noted.
Work is tracked in the project task list and should be addressed in the following order:

1. **`[legacy-js-api]`** *(Sass 2.0)* — Switch `api: 'legacy'` to `api: 'modern-compiler'` in `vite.config.js`. One-line fix, no dependencies.
2. **`[if-function]` in bootstrap-sass** *(Sass 3.0)* — `bootstrap-sass` is abandoned. Needs patch-package or replacement. Self-contained, no dependencies.
3. **`[import]` — `@import` migration** *(Sass 3.0)* — Full migration of all project SCSS from `@import` to `@use`/`@forward`. Large effort; blocks the item below.
4. ~~**`[global-builtin]` / `[color-functions]`**~~ — ✅ Fixed in all proudcity-patterns project files.
5. **`[if-function]` in bootstrap-sass** *(Sass 3.0)* — `bootstrap-sass` is abandoned. Needs patch-package or replacement. Self-contained, no dependencies.
6. **FontAwesome v5 → v6 upgrade** — Would eliminate the patch-package fix and `[import]` warnings from `_font-awesome.scss`; requires auditing icon class usage across the plugin for breaking changes.
7. **wp-proud-theme** — Also uses proudcity-patterns and needs the same slash-div fixes and bourbon drop-in applied.
