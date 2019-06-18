Zxcvbn-PHP is a password strength estimator using pattern matching and minimum entropy calculation. Zxcvbn-PHP is based on the [the Javascript zxcvbn project](https://github.com/dropbox/zxcvbn) from [Dropbox and @lowe](https://tech.dropbox.com/2012/04/zxcvbn-realistic-password-strength-estimation/). "zxcvbn" is bad password, just like "qwerty" and "123456".

>zxcvbn attempts to give sound password advice through pattern matching and conservative entropy calculations. It finds 10k common passwords, common American names and surnames, common English words, and common patterns like dates, repeats (aaa), sequences (abcd), and QWERTY patterns.


## Installation

The library can be installed with [Composer](http://getcomposer.org) by adding it as a dependency to your composer.json file.

```json
{
    "require": {
        "vojtechbuba/zxcvbn-php-cz-sk": "^0.5.1"
    }
}
```

After running `php composer.phar update` on the command line, include the
autoloader in your PHP scripts so that the ZxcvbnPhp class is available.

```php
require_once 'vendor/autoload.php';
```

## Usage

```php
TODO
```

### Acknowledgements
Thanks to @lowe for the original [Javascript Zxcvbn](https://github.com/lowe/zxcvbn)
and [@Dreyer's port](https://github.com/Dreyer/php-zxcvbn) for reference.
