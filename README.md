AssetMini
================================================================================
**PHP based js and css asset minification.**

This project is all about making your JS and CSS assets mini.
Yes there are plenty others out there that do a similar sort of thing.
How do we differ, what features do we offer:

  - Normally it will concatenate all css or js files together
    and then minifiy and gzip the output resulting in the
    final payload.
    
  - It will then cache this output so that NGINX or Apache can then serve
    the output directly without having to ever touch PHP.
    
  - The view helper class will automatically invalidate the cache if need be.

  - It can also parse LESS files, so you can use
    variables and other cool stuff in your css.
    **IT EVEN COMPILES TWITTER BOOTSTRAP ON THE FLY FOR YOU**
    
  - When in debug mode it will load standard css and js files
    individually, unminified and uncompressed.
    
  - When in debug mode LESS files will still be parsed
    but again will not be minified or compressed.

How to Install
--------------------------------------------------------------------------------
Installation via composer is easy:

	composer require gears/assetmini:x

How do I use it?
--------------------------------------------------------------------------------
First up when you install assetmini it will attempt to create an assets folder
in your project root dir, if the folder doesn't already exist. Don't worry
we won't overwrite anything.

If you would like to place the assets folder else where please add the
following to your ```composer.json``` file. Note this is just an example.

```json
"extra":
{
	"assetmini-dir": "./public/assets"
}
```

Apache should work out of the box. This is done by the inclusion of a .htaccess
file. If using the view helper this will even update the .htaccess file to have
the correct RewriteBase path.

To configure for Nginx, you could use the following:

```
location ~* \.(css|js)$
{
	try_files $uri /assets/min.php?$uri;
	expires max;
	gzip_static on;
	gzip_http_version 1.1;
	gzip_proxied expired no-cache no-store private auth;
	gzip_disable "MSIE [1-6]\.";
	gzip_vary on;
	add_header Pragma public;
	add_header Cache-Control "public, must-revalidate, proxy-revalidate";
	log_not_found off;
	access_log off;
}
```

This block will catch any minified css or js requests. We use the try_files to
test for an already minified version, if this does not exist we will get php to
make it for us. **Make sure you point nginx to correct location of min.php**

Once your PHP server is setup and working with the
appropriate re-writes then you can use the view helper like so:

```
require('vendor/autoload.php');
use Gears\AssetMini\HtmlHelper as AssetMini;
AssetMini::setDebug(true);
AssetMini::css(['file1','file2','file3','etc']);
AssetMini::js(['file1','file2','file3','etc']);
```

**Also note that the cache folders will need to be writable by PHP.**

Laravel Integration
--------------------------------------------------------------------------------
I have now included a ServiceProvider and Facade for Laravel.
All you need to do in your Laravel project is require assetmini as above.
And then add the following to your main ```config/app.php``` file.

```php
// Add to the providers array 
'Gears\AssetMini\Laravel\ServiceProvider'
```

```php
// Add to the aliases array
'AssetMini' => 'Gears\AssetMini\Laravel\Facade'
```

Making Contributions
--------------------------------------------------------------------------------
This project is first and foremost a tool to help me create awesome websites.
Thus naturally I am going to tailor for my use. I am just one of those really
kind people that have decided to share my code so I feel warm and fuzzy inside.
Thats what Open Source is all about, right :)

If you feel like you have some awesome new feature, or have found a bug I have
overlooked I would be more than happy to hear from you. Simply create a new
issue on the github project and optionally send me a pull request.

--------------------------------------------------------------------------------
Developed by Brad Jones - brad@bjc.id.au