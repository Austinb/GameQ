# Contributing

Contributions are **welcome** and will be fully **credited**.

## Pull Requests

- **Document any change in behavior** - Make sure the `README.md` and any other relevant documentation are kept up-to-date.

- **Create feature branches** - Don't ask us to pull from your master branch.

- **One pull request per feature** - If you want to do more than one thing, send multiple pull requests.

## Coding Standard

- The **[PSR-2 Coding Standard](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md)** is to be used for all code. 
- **[PHPMD](http://phpmd.org/)** is used to help keep the code clean but exceptions can exist. 

- Use the following commands to check your code before committing it:

```sh
$ vendor/bin/phpcs src tests --extensions=php --ignore=bootstrap --report=checkstyle --report-file=build/logs/checkstyle.xml --standard=build/config/phpcs.xml -v
$ vendor/bin/phpmd src,tests xml build/config/phpmd.xml
```


## Tests

- **Add tests!** - Your patch won't be accepted if it doesn't have tests.

- Run tests by calling `phpunit`
```sh
$ vendor/bin/phpunit
```

The coding standard, mess detection and tests are validated by [Travis CI](.travis.yml).

# Can't Contribute?
If you do not feel comfortable writing your own changes feel free open up a [[new issue|https://github.com/Austinb/GameQ/issues/new]] for to add a game or feature.