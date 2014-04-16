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

function JsMin($input)
{
	return \JShrink\Minifier::minify($input);
}

function CssMin($input)
{
	return \CssMin::minify($input);
}

function LessCompile($input, $import_dir)
{
	$parser = new \Less_Parser();
	$parser->SetImportDirs(array($import_dir => ''));
	$parser->parse($input);
	return $parser->getCss();
}