# PHP-Steam-OpenID
Simple Steam connection class using LightOpenID.

## Features
* Caches the JSON response from the Steam API
* Creates a session token and deletes old sessions on each login

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

See the `example.php` file for more info.



[Powered by Steam](http://steampowered.com/)
