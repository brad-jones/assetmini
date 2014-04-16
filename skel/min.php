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

// Include composer
require('../vendor/autoload.php');

// Define our base directory
$base_dir = realpath(dirname(__FILE__));

// Is our cache dir writeable
if (!is_writable($base_dir.'/cache'))
{
	header("HTTP/1.1 500 Internal Server Error");
	echo 'Invalid Query: cant write to cache dir';
	exit;
}

// Grab the query string
$query = urldecode($_SERVER['QUERY_STRING']);

// Extract the json portion
$json_string = Gears\String\Between($query, 'cache/', '.min');

// Attempt to parse it
if (($files = json_decode($json_string, true)) !== null)
{
	// This is what we will output
	$output = '';
	
	// What is the type of file we are generating?
	$type = pathinfo($query);
	$type = $type['extension'];
	
	// Get the time - this helps us invalidate the cache
	$time = array_pop($files);
	
	// Create the hash name
	$hash_name = '[';
	foreach ($files as $file) $hash_name .= '"'.$file.'",';
	$hash_name = substr($hash_name, 0, -1).']';
	
	// Create some file names
	$group_hash = $base_dir.'/cache/'.$hash_name.'.hash';
	$group_min = $base_dir.'/cache/'.$json_string.'.min.'.$type;
	$group_gz = $group_min.'.gz';
	
	// What is the function name we will use to minify this asset
	$mini = 'Gears\AssetMini\\'.ucfirst($type).'Min';
	
	/*
	 * Check to see if the group file already exists
	 * I have noticed that from time to time nginx takes a few requests
	 * before it recongnises that the cached files exist, my guess at this
	 * point in file locking. ie: the php process still has the file pointer
	 * open. Anyway if the cached files don't exist lets create them.
	 */
	if (!file_exists($group_min))
	{
		// This will contain a list of hashes for each file we minify.
		$hashes = array();
		
		// Loop through the files that make up this group
		foreach ($files as $file)
		{
			// Replace any dots in the file name with directory separators
			$file = str_replace('.', DIRECTORY_SEPARATOR, $file);
			
			// Create the full asset file name
			$assetfilename = $base_dir.'/'.$type.'/'.$file.'.'.$type;
			
			// Does it exist
			if (file_exists($assetfilename))
			{
				// Read the file
				$data = file_get_contents($assetfilename);
				
				// Grab the hash
				$hashes[$assetfilename] = md5($data);
				
				// Minify it
				$output .= $mini($data);
			}
		}
		
		// Compress the minfied data
		$output_gz = gzencode($output);
		
		// Cache the minfied version
		file_put_contents($group_min, $output);
		
		// Cache a gzipped version as well
		file_put_contents($group_gz, $output_gz);
		
		// Create a hash file so we can easily detect
		// when the cache is no longer valid
		file_put_contents($group_hash, json_encode($hashes));
		
		// Make sure all files have the same time
		touch($group_hash, $time);
		touch($group_min, $time);
		touch($group_gz, $time);
	}
	else
	{
		// Just read in what we already have
		$output = file_get_contents($group_min);
		$output_gz = file_get_contents($group_gz);
	}
	
	// What content type is it?
	if ($type == 'css') header('Content-type: text/css;');
	if ($type == 'js') header('Content-type: text/javascript;');
	
	// Does the browser support gzip?
	if (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false)
	{
		// We may as well return the gzipped data we just created.
		header('Vary: Accept-Encoding');
		header('Content-Encoding: gzip');
		$content = $output_gz;
	}
	else
	{
		$content = $output;
	}
	
	// How long is the content
	header('Content-Length: '.strlen($content));
	
	// Output the minfied asset
	echo $content;
}
else
{
	header("HTTP/1.1 500 Internal Server Error");
	echo 'Invalid Query: bad json';
}