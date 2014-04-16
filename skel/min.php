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

// Do we have a request
if (isset($_GET['request']))
{
	// This is what we will output
	$output = '';
	
	// Define our base directory
	$base_dir = realpath(dirname(__FILE__));

	// Parse the request
	$info = pathinfo($_GET['request']);
	$type = $info['extension'];
	$files = explode(',', str_replace('.min', '', $info['filename']));
	$time = array_pop($files);
	$classname = $type.'min';
	
	// Create the group name
	$group = '';
	foreach ($files as $file) $group .= $file.',';
	$group = substr($group, 0, -1);
	
	// Create some file names
	$group_hash = $base_dir.'/'.$type.'/'.$group.'.hash';
	$group_min = $base_dir.'/'.$type.'/'.$info['filename'].'.'.$type;
	$group_gz = $group_min.'.gz';
	
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
		foreach(scandir($base_dir.'/'.$type) as $file)
		{
			if (strpos($file, '.min'))
			{
				unlink($base_dir.'/'.$type.'/'.$file);
			}
		}
		
		// This will contain a list of hashes for each file we minify.
		$hashes = array();
		
		// We might need this later on
		$less = new lessc(); $less->setImportDir([$base_dir.'/css']);
		
		// Loop through the files that make up this group
		foreach ($files as $file)
		{
			// Create the full asset file name
			$assetfilename = $base_dir.'/'.$type.'/'.$file.'.'.$type;
			$assetfilename_less = $base_dir.'/'.$type.'/'.$file.'.less';
			
			// Does it exist
			if (file_exists($assetfilename))
			{
				// Read the file
				$data = file_get_contents($assetfilename);
				
				// Grab the hash
				$hashes[$assetfilename] = md5($data);
				
				// Minify it
				if ($time != 'debug') $output .= $classname::mini($data);
				else $output .= $data;
			}
			elseif (file_exists($assetfilename_less))
			{
				// Read the file
				$data = file_get_contents($assetfilename_less);
				
				// Grab the hash
				$hashes[$assetfilename_less] = md5($data);
				
				// Compile the css
				$data = $less->compile($data);
				
				// Minify it
				if ($time != 'debug') $output .= $classname::mini($data);
				else $output .= $data;
			}
		}
		
		if ($time != 'debug')
		{
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
	if (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false && $time != 'debug')
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