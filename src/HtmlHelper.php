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
	public static $debug = false;
	
	private static $baseurl = null;
	
	private static $basepath = null;
	
	private static $rewritebase = null;
	
	private static function general($type, $files, $link_builder)
	{
		// Work out the full url to our assets.
		if (self::$baseurl == null)
		{
			$info = pathinfo($_SERVER['SCRIPT_NAME']);
			self::$baseurl = 'http'.(isset($_SERVER['HTTPS'])?'s':'').'://'.$_SERVER['HTTP_HOST'].$info['dirname'].'/assets';
			
			// Also set the rewrite base
			self::$rewritebase = $info['dirname'].'/assets/';
		}
		
		// Work out the basepath to our assets
		if (self::$basepath == null)
		{
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
		
		// Are we in debug mode?
		if (self::$debug)
		{
			// Just output the individual files
			foreach ($files as $file)
			{
				// Replace the dots in the filename
				$file = str_replace('.', '/', $file);
				
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
			// Create the hash name
			$hash_name = '[';
			foreach ($files as $file) $hash_name .= '"'.$file.'",';
			$hash_name = substr($hash_name, 0, -1).']';
			
			// Create the hash file name
			$hashfile = self::$basepath.'/cache/'.$hash_name.'.hash';
			
			// Has the group already been built?
			if (file_exists($hashfile))
			{
				// Do the hashes match the current files
				$hashes = json_decode(file_get_contents($hashfile));
				foreach ($hashes as $src => $hash)
				{
					if (md5(file_get_contents($src)) != $hash)
					{
						// Something changed lets invalidate the
						// client side and server side cache.
						$time = time(); break;
					}
				}
				
				// Nothing changed so lets use the time from the current cache.
				if (!isset($time)) $time = filemtime($hashfile);
			}
			else
			{
				// The group hasn't been built yet.
				$time = time();
			}
			
			// Add the time to the files array
			$files[] = $time;
			
			// Build the url to the minified assets
			$url = self::$baseurl.'/cache/'.json_encode($files).'.min.'.$type;
			
			// Output the minified link for the group
			echo $link_builder($url);
		}
	}
	
	public static function css($files)
	{
		self::general
		(
			'css',
			$files,
			function($url)
			{
				return '<link rel="stylesheet" href=\''.$url.'\' />';
			}
		);
	}
	
	public static function js($files)
	{
		self::general
		(
			'js',
			$files,
			function($url)
			{
				return '<script src=\''.$url.'\'></script>';
			}
		);
	}
}