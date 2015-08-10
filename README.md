# PHP-Steam-OpenID
Simple Steam connection class using LightOpenID.

## Features
* Easily customizable and configurable
* Automatically detects logged in status in `__construct`
* Caches the JSON response from the Steam API
* Creates a session token and supports deleting and limiting sessions
* Error logging with specifiable method (using error_log())
* Lightweight and quick retrieval of Steam API info with cURL
* Support for IP-locking sessions for extra security
* Easily modifiable to allow for individual user security settings

## Included Libraries
* [LightOpenID](https://github.com/iignatov/LightOpenID)
* [simplecacher](https://github.com/whocodes/php-simplecacher)

These are already included in the php folder, however feel free to grab a more recent version from the above links.

## Requirements
* PHP with cURL extension
* MySQL server

## Setup
1. Edit the `$Config` and `$Database` variables in `php/steamopenid.php`
2. Import `sql/init.sql` into your chosen SQL database.
3. Include `php/steamopenid.php` into your own PHP script.

See the `an_example.php` file for more info.



[Powered by Steam](http://steampowered.com/)
