Zxcvbn-PHP is a password strength estimator using pattern matching and minimum entropy calculation. Zxcvbn-PHP is based on the Javascript zxcvbn project from [Dropbox and @lowe](https://tech.dropbox.com/2012/04/zxcvbn-realistic-password-strength-estimation/).

[![Build Status](https://travis-ci.org/bjeavons/zxcvbn-php.png?branch=master)](https://travis-ci.org/bjeavons/zxcvbn-php)
[![Coverage Status](https://coveralls.io/repos/bjeavons/zxcvbn-php/badge.png?branch=master)](https://coveralls.io/r/bjeavons/zxcvbn-php?branch=master)
[![Latest Stable Version](https://poser.pugx.org/bjeavons/zxcvbn-php/v/stable.png)](https://packagist.org/packages/bjeavons/zxcvbn-php)

## Installation

The library can be installed with [Composer](http://getcomposer.org) by adding it as a dependency to your composer.json file.

```json
{
    "require": {
        "bjeavons/zxcvbn-php": "*"
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
use ZxcvbnPhp\Zxcvbn;

$zxcvbn = new Zxcvbn();
$strength = $zxcvbn->passwordStrength('password);
echo $strength['score'];
```

### Acknowledgements
Thanks to @lowe for the original [Javascript Zxcvbn](https://github.com/lowe/zxcvbn)
and [@Dreyer's port](https://github.com/Dreyer/php-zxcvbn) for reference.
