# Changelog

## [Unreleased]

### Changed
- Migrated build system from legacy Grunt/Gulp to Vite 6
- Upgraded Sass from 1.56.x to 1.74.x+ to support modern deprecation tooling
- Added `"type": "module"` to package.json to resolve Vite CJS Node API deprecation warning
- Removed `bourbon` v4 from `devDependencies` and from `vite.config.js` `includePaths` — replaced with a lightweight project-owned drop-in

### Fixed

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
4. **`[global-builtin]` / `[color-functions]`** *(Sass 3.0)* — `lighten()`, `darken()`, `ceil()`, `unquote()` in proudcity-patterns `_local-variables.scss`. Requires `@use 'sass:color'` / `@use 'sass:math'` in scope; depends on `@import` migration above.
5. **FontAwesome v5 → v6 upgrade** — Would eliminate the patch-package fix; requires auditing icon class usage across the plugin for breaking changes.
6. **wp-proud-theme** — Also uses proudcity-patterns and needs the same slash-div fixes and bourbon drop-in applied.
