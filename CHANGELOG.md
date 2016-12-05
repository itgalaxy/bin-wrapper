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
