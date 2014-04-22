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

// Define our base directory
$base_dir = realpath(dirname(__FILE__));

// Include composer
$composer_loaded = false;
$dir = $base_dir;
do
{
	if(file_exists($dir.'/vendor/autoload.php'))
	{
		require($dir.'/vendor/autoload.php');
		$composer_loaded = true;
		break;
	}
}
while($dir = realpath("$dir/.."));

// Did we manage to include composer okay
if (!$composer_loaded)
{
	header("HTTP/1.1 500 Internal Server Error");
	echo 'Missing Composer: we cant find composer, please check your install';
	exit;
}

// Is our cache dir writeable
if (!is_writable($base_dir.'/cache'))
{
	header("HTTP/1.1 500 Internal Server Error");
	echo 'Invalid Query: cant write to cache dir';
	exit;
}

// Grab the query string
$query = urldecode($_SERVER['QUERY_STRING']);

/*
 * This is a slightly special case. When the view helpers are in debug mode
 * they will output the script or stylesheet tags for each individual file.
 * However with Less stylehsheets they need to be turned into CSS first.
 * Thus if we get a request for /less/FILE_NAME.less?stopcache=1234567890
 * We will compile the less on the fly, not cache it, and not minify it.
 * But note that the request must go to /less/, if it were to go to /css/
 * the file would exist and the web server would serve it as is.
 */
if (Gears\String\Contains($query, '.less'))
{
	// Extract the filename portion
	$less_file = $base_dir.'/css/'.Gears\String\Between($query, 'less/', '&');
	
	// Work out the basedir that the actual less file is in.
	$less_base = pathinfo($less_file);
	$less_base = $less_base['dirname'];
	
	// Does it exist
	if (file_exists($less_file))
	{
		// Read in the file
		$data = file_get_contents($less_file);
		
		// Compile the less first
		$less = Gears\AssetMini\LessCompile($data, $less_base);
		
		// Output the less
		header('Content-type: text/css;');
		echo $less['css'];
	}
	else
	{
		// It doesn't so error out
		header("HTTP/1.1 500 Internal Server Error");
		echo 'Asset Does Not Exist: '.$less_file;
	}
	exit;
}

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
		// Clean up any old builds
		foreach(scandir($base_dir.'/cache') as $file)
		{
			if (strpos($file, substr($hash_name, 0, -1)) !== false)
			{
				unlink($base_dir.'/cache/'.$file);
			}
		}
		
		// This will contain a list of hashes for each file we minify.
		$hashes = array();
		
		// Loop through the files that make up this group
		foreach ($files as $file)
		{
			// Replace any dots in the file name with directory separators
			$file = str_replace('.', DIRECTORY_SEPARATOR, $file);
			
			// Create the full asset file name
			$assetfilename = $base_dir.'/'.$type.'/'.$file.'.'.$type;
			
			// It's possible the file exists as a LESS file
			$assetfilename_less = str_replace('.css', '.less', $assetfilename);
			
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
			elseif(file_exists($assetfilename_less))
			{
				// Read the file
				$data = file_get_contents($assetfilename_less);
				
				// Grab the hash
				$hashes[$assetfilename_less] = md5($data);
				
				// Work out the basedir that the actual less file is in.
				$less_base = pathinfo($assetfilename_less);
				$less_base = $less_base['dirname'];
				
				// Compile the less first
				$less = Gears\AssetMini\LessCompile($data, $less_base);
				
				// Loop through the imported files and add them to our hashes
				foreach ($less['imported-files'] as $imported)
				{
					$hashes[$imported] = md5(file_get_contents($imported));
				}
				
				// Minify it
				$output .= $mini($less['css']);
			}
			else
			{
				// It doesn't so error out
				header("HTTP/1.1 500 Internal Server Error");
				echo 'Asset Does Not Exist: '.$assetfilename;
				exit;
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