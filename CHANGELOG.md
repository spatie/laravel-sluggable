# Changelog

All notable changes to `laravel-sluggable` will be documented in this file

## 3.4.1 - 2022-12-07

### What's Changed

- Update README.md by @furiouskj in https://github.com/spatie/laravel-sluggable/pull/240
- composer allow-plugins config by @hotsaucejake in https://github.com/spatie/laravel-sluggable/pull/241
- Normalize composer.json by @patinthehat in https://github.com/spatie/laravel-sluggable/pull/247
- Add Dependabot Automation by @patinthehat in https://github.com/spatie/laravel-sluggable/pull/246
- Add PHP 8.2 Support by @patinthehat in https://github.com/spatie/laravel-sluggable/pull/245
- Bump actions/checkout from 2 to 3 by @dependabot in https://github.com/spatie/laravel-sluggable/pull/248
- Allow set slug suffix starting number by @Vediovis in https://github.com/spatie/laravel-sluggable/pull/249

### New Contributors

- @furiouskj made their first contribution in https://github.com/spatie/laravel-sluggable/pull/240
- @hotsaucejake made their first contribution in https://github.com/spatie/laravel-sluggable/pull/241
- @dependabot made their first contribution in https://github.com/spatie/laravel-sluggable/pull/248
- @Vediovis made their first contribution in https://github.com/spatie/laravel-sluggable/pull/249

**Full Changelog**: https://github.com/spatie/laravel-sluggable/compare/3.4.0...3.4.1

## 3.4.0 - 2022-03-28

## What's Changed

- Converts Test cases to Pest tests by @marvin-wtt in https://github.com/spatie/laravel-sluggable/pull/223
- Add ability to skip the slug generation by a condition by @masterix21 in https://github.com/spatie/laravel-sluggable/pull/227

## New Contributors

- @masterix21 made their first contribution in https://github.com/spatie/laravel-sluggable/pull/227

**Full Changelog**: https://github.com/spatie/laravel-sluggable/compare/3.3.1...3.4.0

## 3.3.1 - 2022-03-09

## What's Changed

- Add support for spatie/laravel-translatable:^6.0 by @mziraki in https://github.com/spatie/laravel-sluggable/pull/224

## New Contributors

- @mziraki made their first contribution in https://github.com/spatie/laravel-sluggable/pull/224

**Full Changelog**: https://github.com/spatie/laravel-sluggable/compare/3.3.0...3.3.1

## 3.3.0 - 2022-01-13

- support Laravel 9

## 3.2.0 - 2021-12-15

## What's Changed

- Adds support for implicit route model binding with translated slugs by @marvin-wtt in https://github.com/spatie/laravel-sluggable/pull/213

## New Contributors

- @marvin-wtt made their first contribution in https://github.com/spatie/laravel-sluggable/pull/213

**Full Changelog**: https://github.com/spatie/laravel-sluggable/compare/3.1.1...3.2.0

## 3.1.1 - 2021-12-13

## What's Changed

- Migrate to PHP-CS-Fixer 3.x by @shuvroroy in https://github.com/spatie/laravel-sluggable/pull/203
- Adds test case for replicate method by @eduarguz in https://github.com/spatie/laravel-sluggable/pull/212
- Fix Deprecation: currentSlug is null by @phh in https://github.com/spatie/laravel-sluggable/pull/218

## New Contributors

- @shuvroroy made their first contribution in https://github.com/spatie/laravel-sluggable/pull/203
- @eduarguz made their first contribution in https://github.com/spatie/laravel-sluggable/pull/212
- @phh made their first contribution in https://github.com/spatie/laravel-sluggable/pull/218

**Full Changelog**: https://github.com/spatie/laravel-sluggable/compare/3.1.0...3.1.1

## 3.1.0 - 2021-06-04

- add extra scope callback option (#201)

## 3.0.2 - 2021-05-07

- bugfix for updating slugs generated from a callback (#200)

## 3.0.1 - 2021-04-22

- update slug on non unique names (#195)

## 3.0.0 - 2021-03-01

- require PHP 8+
- drop support for PHP 7.x
- convert syntax to PHP 8
- move Exceptions to `Exceptions` folder to match structure of other packages

## 2.6.2 - 2021-03-20

- Added translatable slug overriding (#190)

## 2.6.1 - 2020-01-31

- fix Eloquent model checking (#186)

## 2.6.0 - 2020-10-28

- add `preventOverwrite`
- add support for PHP 8

## 2.5.2 - 2020-10-01

- fixed an incompatibility bug with postgresql uuid column (#173)

## 2.5.1 - 2020-09-07

- add support for Laravel 8

## 2.5.0 - 2020-06-15

- add helper trait to integrate with `laravel-translatable` #155

## 2.4.2 - 2020-04-20

- fix bug that causes empty slugs when dealing with multi-bytes chars (#152)

## 2.4.1 - 2020-04-09

- use method for retrieving incrementing status of the model (#151)

## 2.4.0 - 2020-03-03

- add support for Laravel 7, drop support for Laravel 6

## 2.3.0 - 2019-12-06

- drop support for anything below PHP 7.4 and Laravel 6

## 2.2.1 - 2019-09-16

- Changed: Updated Laravel 6 compatibility for future versions

## 2.2.0 - 2019-09-04

- Drop support for PHP 7.1
- Add support for Laravel 6.0

## 2.1.8 - 2019-06-08

- ensure slugs are unique when using soft deletes

## 2.1.7 - 2019-02-26

- Add support for Laravel 5.8

## 2.1.6 - 2018-02-14

- performance improvements

## 2.1.5 - 2018-01-10

- improve compatibility with json fields

## 2.1.4 - 2018-08-28

- add support for Laravel 5.7

## 2.1.3 - 2018-02-15

- fix for models with non incrementing primary keys

## 2.1.2 - 2018-02-08

- Support Laravel 5.6

## 2.1.1 - 2017-01-28

- improve compatibility with Lumen

## 2.1.0 - 2017-09-13

- add `usingLanguage`

## 2.0.0 - 2017-08-31

- add support for Laravel 5.5, drop support for all older versions of the framework

## 1.5.2 - 2018-05-08

- make compatible with PHP 7.2

## 1.5.1 - 2017-08-19

- fix bugs when using a custom separator

## 1.5.0 - 2017-04-13

- add `usingSeparator()`

## 1.4.1 - 2017-04-11

- ignore global scopes when determining a unique slug

## 1.4.0 - 2017-01-24

- add support for Laravel 5.4

## 1.3.0 - 2016-11-14

- add `doNotGenerateSlugsOnCreate` and `doNotGenerateSlugsOnUpdate`

## 1.2.0 - 2016-06-13

- Added the ability to generate slugs from a callable

## 1.1.0 - 2016-01-24

- Allow custom slugs

## 1.0.2 - 2016-01-12

- Fix bug when creating slugs from null values

## 1.0.1 - 2015-12-27

- Fix Postgres bug

## 1.0.0 - 2015-12-24

- Initial release
