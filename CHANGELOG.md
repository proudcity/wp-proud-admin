# Changelog

## [Unreleased]

### Changed
- Migrated build system from legacy Grunt/Gulp to Vite 6
- Upgraded Sass from 1.56.x to 1.74.x+ to support modern deprecation tooling
- Added `"type": "module"` to package.json to resolve Vite CJS Node API deprecation warning

### Fixed
- Fixed Sass `slash-div` deprecation warnings across all proudcity-patterns SCSS files by replacing `/` division with `* 0.5` / `* 0.25` multiplication equivalents:
  - `pattern-scss/_local-variables.scss` (lines 497–498)
  - `pattern-scss/helpers/_utilities.scss` (6 instances)
  - `pattern-scss/helpers/_grid.scss` — replaced `percentage(((1 / $grid-columns) / 2))` with `calc(50% / #{$grid-columns})`
  - `pattern-scss/_navbar.scss` (lines 39, 348)
  - `pattern-scss/_social-wall.scss` (lines 164, 165, 197)
  - `pattern-scss/_proudbar.scss` — replaced `114px/22px*$proudbar-logo-height` with `$proudbar-logo-height * 5.18182` (lines 68, 69, 71)
  - `pattern-scss/_page-header.scss` (line 7)
  - `pattern-scss/vendor/_card.scss` (line 50)
  - `pattern-scss/vendor/_hamburger.scss` (line 90)
  - `pattern-scss/wp/_so-pagebuilder.scss` (lines 66–67, no-space division pattern)
- Fixed Sass `slash-div` deprecation in `@fortawesome/fontawesome-free` v5: `$fa-fw-width: (20em / 16)` → `1.25em` via patch-package

### Added
- `patch-package` dev dependency with `postinstall` hook to apply npm package patches on install
- `patches/@fortawesome+fontawesome-free+5.15.4.patch` — fixes slash-div in fontawesome v5 Sass variables
- All proudcity-patterns slash-div fixes added to `npm run patch-patterns` script so they apply automatically on fresh clone via `projectsetup` / `projectupdate`

- Replaced Bourbon v4 with a lightweight drop-in (`assets/styles/loaders/_bourbon-replacement.scss`) providing only the 8 mixins the project actually uses (`transition`, `transform`, `animation`, `keyframes`, `transition-property`, `transition-duration`, `transition-delay`, `clearfix`). Vendor prefixes removed — all properties have been universally supported unprefixed since 2015.
- Removed `bourbon` from `devDependencies` and from `vite.config.js` `includePaths`
- Fixed slash-div in project-owned `assets/styles/components/_wp-classes.scss` (4 instances of `$line-height-computed / 2` → `* 0.5`)
- Fixed additional no-space slash-div in `proudcity-patterns/wp/_so-pagebuilder.scss` lines 70–71 (missed in initial pass); patch-patterns script updated to use broader pattern catching all `$grid-gutter-width/2` variants

### Known Issues / Remaining Work
- **wp-proud-theme** also uses proudcity-patterns and will need the same slash-div fixes applied (same patch-patterns script approach)
- Bourbon v4 removed and replaced with lightweight drop-in (see above)
- `@import` is deprecated in Dart Sass 3.0 — full migration to `@use`/`@forward` required across all project SCSS
- Deprecated global color functions (`lighten`, `darken`, `ceil`, `floor`) in proudcity-patterns `_local-variables.scss` — needs fixing alongside `@use` migration
- Deprecated `if()` syntax in bourbon and bootstrap-sass — needs patch-package or package replacement
- FontAwesome v5 → v6 upgrade pending (would eliminate the patch-package fix above)
