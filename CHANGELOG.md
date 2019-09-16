# Changelog

All notable changes to `laravel-sluggable` will be documented in this file

## 2.2.1 - 2019-09-16
- Changed: Updated Laravel 6 compatibility for future versions

## 2.2.0 - 2019-09-04
- Drop support for PHP 7.2
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
