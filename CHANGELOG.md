# Change Log

All notable changes to this project will be documented in this file.

This project adheres to [Semantic Versioning](http://semver.org/).

# 1.0.4 - 2017-03-29

- Chore: minimum required `itgalaxy/os-filter` version is now `^3.0.0`.

# 1.0.3 - 2017-03-28

- Fixed: avoid creating file when `guzzle` could not download file.

# 1.0.2 - 2017-03-28

- Fixed: canonicalize all paths.

# 1.0.1 - 2017-03-28

- Fixed: ignore directory exist when search binary.

# 1.0.0 - 2017-03-28

- Changed: used full php namespace.
- Chore: used not interactive mode for `composer install` in `CI`.
- Chore: changed `getTempDirectory` function to `getTempDir` in `tests`.

# 0.0.6 - 2016-12-05

- Added: used `itgalaxy/bin-version-check` package for semver range validation.

# 0.0.5 - 2016-12-05

- Chore: loaded `autoload` in tests relatively `bootstrap.php` file.
- Chore: fixed test for `windows`.
- Chore: minimum required `phpunit/phpunit` version is now `~5.7.0`.
- Chore: used `itgalaxy/bin-check` package instead own implementation.
- Chore: used `PHP_OS` constant instead `php-uname` function.

# 0.0.4 - 2016-12-03

- Chore: improved `README.md`.
- Chore: used `itgalaxy/os-filter` package instead own implementation.
- Fixed: moved `symfony/filesystem` package from `require-dev` to `require`.

# 0.0.3 - 2016-12-02

- Fixed: compat with `windows`.

# 0.0.2 - 2016-12-02

- Fixed: used `Path::canonicalize` for normalize bin path.
- Fixed: used `Path::hasExtension` for determine whether you need to uncompress the file.

# 0.0.1 - 2016-12-01

- Chore: initial public release.
