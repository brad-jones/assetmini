<?php
////////////////////////////////////////////////////////////////////////////////
//            _____                        __     _____   __        __          
//           /  _  \   ______ ______ _____/  |_  /     \ |__| ____ |__|         
//          /  /_\  \ /  ___//  ___// __ \   __\/  \ /  \|  |/    \|  |         
//         /    |    \\___ \ \___ \\  ___/|  | /    Y    \  |   |  \  |         
//         \____|__  /____  >____  >\___  >__| \____|__  /__|___|  /__|         
//                 \/     \/     \/     \/             \/        \/             
// -----------------------------------------------------------------------------
//          Designed and Developed by Brad Jones <brad @="bjc.id.au" />         
// -----------------------------------------------------------------------------
////////////////////////////////////////////////////////////////////////////////

namespace Gears\AssetMini;

class HtmlHelper
{
	// We keep some static data here.
	private static $debug = false;
	private static $baseurl = null;
	private static $basepath = null;
	private static $rewritebase = null;

	/**
	 * Method: setDebug
	 * =========================================================================
	 * This sets the debug mode. We default debug mode to false.
	 * When debug mode is off everything is concatenated together, minified
	 * and gzipped. Exactly how it should be for a productions site.
	 * 
	 * If you set debug mode to true. All style sheets and scripts are loaded
	 * individually. Referencing their respective source files. As we all know
	 * sometimes minification isn't 100% perfect and we might get some
	 * javascript errors or broken styling. If this is the case turn debug mode
	 * on which will hopefully help you fix up the code. Needless to say when
	 * developing javascript it is basically mandatory to turn debug mode on.
	 * 
	 * Parameters:
	 * -------------------------------------------------------------------------
	 * $value - simply true or false
	 * 
	 * Returns:
	 * -------------------------------------------------------------------------
	 * void
	 */
	public static function setDebug($value)
	{
		if (is_bool($value))
		{
			self::$debug = (bool) $value;
		}
	}

	/**
	 * Method: setBaseUrl
	 * =========================================================================
	 * We do our best to auto detect the base URL but sometimes we aren't
	 * clever enough. Simply define the full public base URL to your
	 * assets folder.
	 * 
	 * Eg: http://www.example.org/assets/
	 * 
	 * Parameters:
	 * -------------------------------------------------------------------------
	 * $value - A url to your assets folder.
	 * 
	 * Returns:
	 * -------------------------------------------------------------------------
	 * void
	 */
	public static function setBaseUrl($value)
	{
		self::$baseurl = $value;
	}

	/**
	 * Method: setBasePath
	 * =========================================================================
	 * Again we do our best to automatically determine what the file system
	 * path is to the assets folder. For some setups you may want to define
	 * this yourself.
	 * 
	 * Eg: /var/www/mycoolsite/some/odd/folder/assets
	 * 
	 * Parameters:
	 * -------------------------------------------------------------------------
	 * $value - The full absolute path to the assets folder.
	 * 
	 * Returns:
	 * -------------------------------------------------------------------------
	 * void
	 */
	public static function setBasePath($value)
	{
		self::$basepath = $value;
	}

	/**
	 * Method: css
	 * =========================================================================
	 * This is how you add style sheets to your HTML page.
	 * All you need to do is supply an array of css or less files.
	 * Note that we use dot notation for folders.
	 * 
	 * Usage might look like:
	 * 
	 * Gears\AssetMini\HtmlHelper::css(['bootstrap.bootstrap', 'mystyles']);
	 * 
	 * This will output something like:
	 * 
	 * <link rel="stylesheet" href="/assets/cache/123...-1409307541.min.css" />
	 * 
	 * Parameters:
	 * -------------------------------------------------------------------------
	 * $value - An array of css assets to combine and minify.
	 * 
	 * Returns:
	 * -------------------------------------------------------------------------
	 * void
	 */
	public static function css()
	{
		// Grab the arguments
		$args = func_get_args();

		// Do we have a name
		if (is_string($args[0]))
		{
			$name = $args[0];
			$files = $args[1];
		}
		else
		{
			$name = false;
			$files = $args[0];
		}

		// Call the general method
		self::general
		(
			'css',
			$name,
			$files,
			function($url)
			{
				return '<link rel="stylesheet" href="'.$url.'" />';
			}
		);
	}
	
	/**
	 * Method: js
	 * =========================================================================
	 * This is how you add scripts to your HTML page.
	 * All you need to do is supply an array of js files.
	 * Note that we use dot notation for folders.
	 * 
	 * Usage might look like:
	 * 
	 * Gears\AssetMini\HtmlHelper::js(['utils.modernizr', 'jquery']);
	 * 
	 * This will output something like:
	 * 
	 * <script src="/assets/cache/123...-1409306431.min.js"></script>
	 * 
	 * Parameters:
	 * -------------------------------------------------------------------------
	 * $value - An array of js assets to combine and minify.
	 * 
	 * Returns:
	 * -------------------------------------------------------------------------
	 * void
	 */
	public static function js()
	{
		// Grab the arguments
		$args = func_get_args();

		// Do we have a name
		if (is_string($args[0]))
		{
			$name = $args[0];
			$files = $args[1];
		}
		else
		{
			$name = false;
			$files = $args[0];
		}

		// Call the general method
		self::general
		(
			'js',
			$name,
			$files,
			function($url)
			{
				return '<script src="'.$url.'"></script>';
			}
		);
	}

	/**
	 * Method: general
	 * =========================================================================
	 * This is not part of the public API and is used by the css and js methods.
	 * 
	 * Parameters:
	 * -------------------------------------------------------------------------
	 * $type - css or js
	 * $group_name - An optional group name to use instead of using an md5 hash
	 * $files - An array of files that will make up this asset
	 * $link_builder - A closure that returns the html for the asset type.
	 * 
	 * Returns:
	 * -------------------------------------------------------------------------
	 * void
	 */
	private static function general($type, $group_name, $files, $link_builder)
	{
		// If the baseurl has not been set lets attempt to work it out
		if (self::$baseurl == null)
		{
			// Work out the full url to our assets.
			$info = pathinfo($_SERVER['SCRIPT_NAME']);
			if (substr($info['dirname'], -1) == '/') $info['dirname'] = substr($info['dirname'], 0, -1);
			self::$baseurl = 'http'.(isset($_SERVER['HTTPS'])?'s':'').'://'.$_SERVER['HTTP_HOST'].$info['dirname'].'/assets';
			
			// Also set the rewrite base
			self::$rewritebase = $info['dirname'].'/assets/';
		}
		
		// If the basepath has not been set lets attempt to work it out
		if (self::$basepath == null)
		{
			// Work out the basepath to our assets
			$info = pathinfo($_SERVER['SCRIPT_FILENAME']);
			self::$basepath = $info['dirname'].'/assets';
			
			// Now update the .htaccess file
			$htaccess = self::$basepath.'/.htaccess';
			if (file_exists($htaccess) && is_writable($htaccess))
			{
				// Grab the content of the file
				$lines = file($htaccess);
				
				// The new file
				$new_file = '';
				
				// Loop through the lines of the file
				foreach ($lines as $line)
				{
					if (\Gears\String\Contains($line, 'RewriteBase'))
					{
						$new_file .= "\tRewriteBase ".self::$rewritebase."\n";
					}
					else
					{
						$new_file .= $line;
					}
				}
				
				// Save the new file
				file_put_contents($htaccess, $new_file);
			}
		}
		
		// Replace dot notation with directory seperators
		$tmp = $files; $files = [];
		foreach ($tmp as $file)
		{
			$files[] = str_replace('.', DIRECTORY_SEPARATOR, $file);
		}

		// Are we in debug mode?
		if (self::$debug)
		{
			// Just output the individual files
			foreach ($files as $file)
			{
				$url = null;

				// Special case for less and sass
				if ($type == 'css')
				{
					// Check for any less assets
					if (file_exists(self::$basepath.'/css/'.$file.'.less'))
					{
						$url = self::$baseurl.'/less/'.$file.'.less?stopcache='.time();
					}

					// Check for any sass assets
					elseif (file_exists(self::$basepath.'/css/'.$file.'.scss'))
					{
						$url = self::$baseurl.'/sass/'.$file.'.scss?stopcache='.time();
					}
				}

				if (empty($url))
				{
					// Check for any pre minified assets
					if (file_exists(self::$basepath.'/'.$type.'/'.$file.'.min.'.$type))
					{
						$url = self::$baseurl.'/'.$type.'/'.$file.'.min.'.$type.'?stopcache='.time();
					}

					// The normal case
					else
					{
						$url = self::$baseurl.'/'.$type.'/'.$file.'.'.$type.'?stopcache='.time();
					}
				}

				// Output the HTML
				echo $link_builder($url);
			}
		}
		else
		{
			// Create the group hash
			$group_hash = md5(json_encode($files));

			// Set the group name
			if (!$group_name)
			{
				$group_name = $group_hash;
			}
			else
			{
				$group_name = str_replace('-','_', $group_name);
			}
			
			// Create the group hash filename
			$hashfile = self::$basepath.'/cache/'.$group_name.'.json';

			// Has the group already been built?
			if (file_exists($hashfile))
			{
				// Read in the current set of hashes
				$current_hashes = json_decode(file_get_contents($hashfile), true);

				// Remove the time from the hashes array
				$hash_time = array_pop($current_hashes);

				// Remove the group hash from the hashes array
				$current_group_hash = array_pop($current_hashes);

				// Have our assets changed since last time
				if ($group_hash != $current_group_hash)
				{
					// Something changed lets invalidate the
					// client side and server side cache.
					$time = time();
				}
				else
				{
					// The group hasn't changed but the members might have
					foreach ($current_hashes as $src => $hash)
					{
						// Build the full path to the asset
						$filepath = self::$basepath.'/'.$src;

						// Compare the 2 hashes
						if (md5(file_get_contents($filepath)) != $hash)
						{
							// Something changed lets invalidate the
							// client side and server side cache.
							$time = time(); break;
						}
					}
				}
			}
			else
			{
				// Its a brand new asset
				$time = time();
			}

			// Check for the time variable
			if (isset($time))
			{
				// Lets build a new hashes array
				$new_hashes = [];
				foreach ($files as $file)
				{
					$relative_path = null;

					if ($type == 'css')
					{
						// Check for any less assets
						if (file_exists(self::$basepath.'/css/'.$file.'.less'))
						{
							$relative_path = 'css/'.$file.'.less';
						}

						// Check for any sass assets
						elseif (file_exists(self::$basepath.'/css/'.$file.'.scss'))
						{
							$relative_path = 'css/'.$file.'.scss';
						}
					}

					if (empty($relative_path))
					{
						// Check for any pre minified assets
						if (file_exists(self::$basepath.'/'.$type.'/'.$file.'.min.'.$type))
						{
							$relative_path = $type.'/'.$file.'.min.'.$type;
						}

						// The normal case
						else
						{
							$relative_path = $type.'/'.$file.'.'.$type;
						}
					}

					// Create the full filepath
					$filepath = self::$basepath.'/'.$relative_path;

					// Create the hash entry
					if (file_exists($filepath))
					{
						$new_hashes[$relative_path] = md5(file_get_contents($filepath));
					}
					else
					{
						$new_hashes[$relative_path] = null;
					}
				}

				// Add the group hash
				$new_hashes[] = $group_hash;
				
				// Add the build time to the hashes array
				$new_hashes[] = $time;

				// Save the new hash file
				file_put_contents($hashfile, json_encode($new_hashes));
			}
			else
			{
				// Nothing changed so lets use the time from the current cache.
				$time = $hash_time;
			}
			
			// Create the asset name
			$asset_name = $group_name.'-'.$time;
			
			// Build the url to the minified assets
			$url = self::$baseurl.'/cache/'.$asset_name.'.min.'.$type;
			
			// Output the minified link for the group
			echo $link_builder($url);
		}
	}
}