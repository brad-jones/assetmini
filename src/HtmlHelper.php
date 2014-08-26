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
	private static $debug = false;
	
	private static $baseurl = null;
	
	private static $basepath = null;
	
	private static $rewritebase = null;

	public static function setDebug($value)
	{
		if (is_bool($value))
		{
			self::$debug = (bool) $value;
		}
	}

	public static function setBaseUrl($value)
	{
		self::$baseurl = $value;
	}

	public static function setBasePath($value)
	{
		self::$basepath = $value;
	}

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
				return '<link rel="stylesheet" href=\''.$url.'\' />';
			}
		);
	}
	
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
				return '<script src=\''.$url.'\'></script>';
			}
		);
	}

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
				// Check for any less assets
				if (file_exists(self::$basepath.'/css/'.$file.'.less'))
				{
					$url = self::$baseurl.'/less/'.$file.'.less?stopcache='.time();
				}
				else
				{
					$url = self::$baseurl.'/'.$type.'/'.$file.'.'.$type.'?stopcache='.time();
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
						if (md5(file_get_contents($src)) != $hash)
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
					// Create the full file path
					$filepath = self::$basepath.'/'.$type.'/'.$file.'.'.$type;

					// Check for any less assets
					$lesspath = str_replace('.css', '.less', $filepath);
					if (!file_exists($filepath) && file_exists($lesspath))
					{
						$filepath = $lesspath;
					}

					// Create the hash entry
					$new_hashes[$filepath] = md5(file_get_contents($filepath));
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