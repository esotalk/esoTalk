<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * General functions. Contains utility functions that are used throughout the application, such as sanitation
 * functions, color functions, JSON encoding, URL construction, and array manipluation.
 *
 * @package esoTalk
 */

// Define E_USER_DEPRECATED for PHP < 5.3.
if (!defined("E_USER_DEPRECATED")) define('E_USER_DEPRECATED', E_USER_WARNING);


/**
 * Throw a deprecation error.
 *
 * @param string $oldFunction The name of the deprecated function.
 * @param string $newFunction The name of a function that should be used instead.
 * @return void
 *
 * @package esoTalk
 */
function deprecated($oldFunction, $newFunction = false)
{
	$message = "$oldFunction is deprecated.";
	if ($newFunction) $message .= " Use $newFunction instead.";
	trigger_error($message, E_USER_DEPRECATED);
}


/**
 * Shortcut function for ET::translate().
 *
 * @see ET::translate()
 *
 * @package esoTalk
 */
function T($string, $default = false)
{
	return ET::translate($string, $default);
}


/**
 * Translate a string to its normal form or its plurular form, depending on an amount.
 *
 * @param string $string The string to translate (singular).
 * @param string $pluralString The string to translate (plurular).
 * @param int $amount The amount.
 *
 * @package esoTalk
 */
function Ts($string, $pluralString, $amount)
{
	return sprintf(T($amount == 1 ? $string : $pluralString), $amount);
}


/**
 * Shortcut function for ET::config().
 *
 * @see ET::config()
 *
 * @package esoTalk
 */
function C($string, $default = false)
{
	return ET::config($string, $default);
}


/**
 * Get a request input value, falling back to a default value if it is not set. POST will be searched first,
 * then GET, and then the fallback will be used.
 *
 * @param string $key The request input key.
 * @param mixed $default The fallback value.
 * @return mixed
 *
 * @package esoTalk
 */
function R($key, $default = "")
{
	if (!empty($_POST[$key])) return $_POST[$key];
	elseif (isset($_GET[$key])) return $_GET[$key];
	else return $default;
}


/**
 * Remove a directory recursively.
 *
 * @param string $dir The path to the directory.
 * @return bool Whether or not the remove succeeded.
 *
 * @package esoTalk
 */
function rrmdir($dir)
{
	if (is_dir($dir)) {
		$objects = scandir($dir);
		foreach ($objects as $object) {
			if ($object != "." and $object != "..") {
				if (filetype($dir."/".$object) == "dir") rrmdir($dir."/".$object);
				else unlink($dir."/".$object);
			}
		}
		return rmdir($dir);
	}
	return false;
}


/**
 * Write contents to a file, attempting to create the directory that the file is in if it does not exist.
 *
 * @param string $file The filepath to write to.
 * @param string $contents The contents to write.
 * @return int
 *
 * @package esoTalk
 */
function file_force_contents($file, $contents){
	$parts = explode("/", $file);
	$file = array_pop($parts);
	$dir = "";
	foreach($parts as $part)
		if (!is_dir($dir .= "$part/")) mkdir($dir);
	return file_put_contents("$dir$file", $contents);
}


/**
 * Converts the php.ini notiation for numbers (like '2M') to an integer of bytes.
 *
 * @param string $value The value of the php.ini directive.
 * @return int The equivalent number of bytes.
 *
 * @package esoTalk
 */
function iniToBytes($value)
{
	$l = substr($value, -1);
	$ret = substr($value, 0, -1);
	switch(strtoupper($l)){
		case "P":
			$ret *= 1024;
		case "T":
			$ret *= 1024;
		case "G":
			$ret *= 1024;
		case "M":
			$ret *= 1024;
		case "K":
			$ret *= 1024;
		break;
	}
	return $ret;
}


/**
 * Minify a CSS string by removing comments and whitespace.
 *
 * @param string $css The CSS to minify.
 * @return string The minified result.
 *
 * @package esoTalk
 */
function minifyCSS($css)
{
	// Compress whitespace.
	$css = preg_replace('/\s+/', ' ', $css);

	// Remove comments.
	$css = preg_replace('/\/\*.*?\*\//', '', $css);

	return trim($css);
}


/**
 * Minify a JavaScript string using JSMin.
 *
 * @param string $js The JavaScript to minify.
 * @return string The minified result.
 *
 * @package esoTalk
 */
function minifyJS($js)
{
	require_once PATH_LIBRARY."/vendor/jsmin.php";
	return JSMin::minify($js);
}


/**
 * Send an email with proper headers.
 *
 * @param string $to The address to send the email to.
 * @param string $subject The subject of the email.
 * @param string $body The body of the email.
 * @return bool Whether or not the mailing succeeded.
 *
 * @package esoTalk
 */
function sendEmail($to, $subject, $body)
{
	$phpmailer = PATH_LIBRARY.'/vendor/class.phpmailer.php';
	require_once($phpmailer);
	$mail = new PHPMailer(true);

	if (($return = ET::trigger("sendEmailBefore", array($mail, &$to, &$subject, &$body))) and !empty($return))
		return reset($return);

	$mail->IsHTML(true);
	$mail->AddAddress($to);
	$mail->SetFrom(C("esoTalk.emailFrom"), sanitizeForHTTP(C("esoTalk.forumTitle")));
	$mail->Subject = sanitizeForHTTP($subject);
	$mail->Body = $body;

	return $mail->Send();
}


/**
 * Parse an array of request parts (eg. $_GET["p"] exploded by "/"), work out what controller to set up,
 * instantiate it, and work out the method + arguments to dispatch to it.
 *
 * @param array $parts An array of parts of the request.
 * @param array $controllers An array of available controllers, with the keys as the controller names and the
 * 		values as the factory names.
 * @return array An array of information about the response:
 * 		0 => the controller name
 * 		1 => the controller instance
 * 		2 => the method to dispatch
 * 		3 => the arguments to pass when dispatching
 * 		4 => the response type to use
 *
 * @package esoTalk
 */
function parseRequest($parts, $controllers)
{
	$c = strtolower(@$parts[0]);
	$method = "index";
	$type = RESPONSE_TYPE_DEFAULT;

	// If the specified controller doesn't exist, 404.
	if (!isset($controllers[$c])) ET::notFound();

	// Make an instance of the controller.
	$controller = ETFactory::make($controllers[$c]);

	// Determine the controller method and response type to use. Default to index.
	$arguments = array_slice($parts, 2);
	if (!empty($parts[1])) {
		$method = strtolower($parts[1]);

		// If there's a period in the method string, use the first half as the method and the second half as the response type.
		if (strpos($method, ".") !== false) {
			list($method, $suffix) = explode(".", $method, 2);
			if (in_array($suffix, array(RESPONSE_TYPE_VIEW, RESPONSE_TYPE_JSON, RESPONSE_TYPE_AJAX, RESPONSE_TYPE_ATOM))) $type = $suffix;
		}

		// Get all of the immediately public methods in the controller class.
		$methods = get_class_methods($controller);
		$parentMethods = get_class_methods(get_parent_class($controller));
		$methods = array_diff($methods, $parentMethods);
		foreach ($methods as $k => $v) $methods[$k] = strtolower($v);

		// If the method we want to use doesn't exist in the controller...
		if (!$method or !in_array($method, $methods)) {

			// Search for a plugin with this method. If found, use that.
			$found = false;
			foreach (ET::$plugins as $plugin) {
				if (method_exists($plugin, $c."Controller_".$method)) {
					$found = true;
					break;
				}
			}

			// If one wasn't found, default to the "index" method.
			if (!$found) {
				$method = "index";
				$arguments = array_slice($parts, 1);
			}
		}
	}

	return array($c, $controller, $method, $arguments, $type);
}


/**
 * Sanitize a string for outputting in a HTML context.
 *
 * @param string $string The string to sanitize.
 * @return string The sanitized string.
 *
 * @package esoTalk
 */
function sanitizeHTML($value)
{
	return htmlentities($value, ENT_QUOTES, "UTF-8");
}


/**
 * Sanitize HTTP header-sensitive characters (CR and LF.)
 *
 * @param string $string The string to sanitize.
 * @return string The sanitized string.
 *
 * @package esoTalk
 */
function sanitizeForHTTP($value)
{
	return str_replace(array("\r", "\n", "%0a", "%0d", "%0A", "%0D"), "", $value);
}


/**
 * Sanitize file-system sensitive characters.
 *
 * @param string $string The string to sanitize.
 * @return string The sanitized string.
 *
 * @package esoTalk
 */
function sanitizeFileName($value)
{
	return preg_replace("/(?:[\/:\\\]|\.{2,}|\\x00)/", "", $value);
}


/**
 * Sort an array by values in its second dimension.
 *
 * @param array $array The array to sort.
 * @param mixed $index The key of the second dimension to sort the array by.
 * @param string $order The direction (asc or desc).
 * @param bool $natSort Whether or not to use the natsort function.
 * @param bool $caseSensitive Whether or not to use case-sensitive sort functions.
 * @return array The sorted array.
 *
 * @package esoTalk
 */
function sort2d($array, $index, $order = "asc", $natSort = false, $caseSensitive = false)
{
	if (is_array($array) and count($array) > 0) {
		$temp = array();
		foreach (array_keys($array) as $key) {
			$temp[$key] = $array[$key][$index];
			if (!$natSort) ($order == "asc") ? asort($temp) : arsort($temp);
			else {
				($caseSensitive) ? natsort($temp) : natcasesort($temp);
				if ($order != "asc") $temp = array_reverse($temp, true);
			}
		}
		foreach (array_keys($temp) as $key) $sorted[$key] = $array[$key];
		return $sorted;
	}
	return $array;
}


/**
 * Returns whether or not the user is using a mobile device.
 *
 * @return bool
 *
 * @package esoTalk
 */
function isMobileBrowser()
{
	static $isMobileBrowser = null;

	if (is_null($isMobileBrowser)) {

		// This code is from http://detectmobilebrowser.com/ by Chad Smith. Thanks Chad!
		$userAgent = $_SERVER["HTTP_USER_AGENT"];
		$isMobileBrowser = (preg_match("/android|avantgo|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i", $userAgent) || preg_match("/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|e\-|e\/|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(di|rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|xda(\-|2|g)|yas\-|your|zeto|zte\-/i", substr($userAgent, 0, 4)));

	}

	return $isMobileBrowser;
}


/**
 * Create a slug for use in URLs from a given string. Any non-alphanumeric characters will be converted to "-".
 *
 * @param string $string The string to convert.
 * @return string The slug.
 *
 * @package esoTalk
 */
function slug($string)
{
	// Convert special latin letters and other characters to HTML entities.
	$slug = htmlentities($string, ENT_NOQUOTES, "UTF-8");

	// With those HTML entities, either convert them back to a normal letter, or remove them.
	$slug = preg_replace(array("/&([a-z]{1,2})(acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);/i", "/&[^;]{2,6};/"), array("$1", " "), $slug);

	// Now replace non-alphanumeric characters with a hyphen, and remove multiple hyphens.
	$slug = strtolower(trim(preg_replace(array("/[^0-9a-z]/i", "/-+/"), "-", $slug), "-"));

	return substr($slug, 0, 63);
}


/**
 * Generate a salt of $numOfChars characters long containing random letters, numbers, and symbols.
 *
 * @param int $numOfChars The length of the random string.
 * @return string The random string.
 *
 * @package esoTalk
 */
function generateRandomString($numOfChars, $possibleChars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890~!@#%^&*()_+=-{}[]:;<,>.?/`")
{
	$salt = "";
	for ($i = 0; $i < $numOfChars; $i++) $salt .= $possibleChars[rand(0, strlen($possibleChars) - 1)];
	return $salt;
}


/**
 * For bad server configs. Pretty much just performing stripslashes on an array here.
 *
 * @param mixed $value The value to undo magic quotes on.
 * @return mixed
 *
 * @package esoTalk
 */
function undoMagicQuotes($value)
{

	if (!is_array($value)) return stripslashes($value);
	else array_map("undoMagicQuotes", $value);
	return $value;
}


/**
 * For bad server configs as well. Unset all input variables added to the global namespace if register_globals
 * is on.
 *
 * @return void
 *
 * @package esoTalk
 */
function undoRegisterGlobals()
{
	if (ini_get("register_globals")) {
		$array = array("_REQUEST", "_SESSION", "_SERVER", "_ENV", "_FILES");
		foreach ($array as $value) {
			foreach ((array)$GLOBALS[$value] as $key => $var) {
				if (isset($GLOBALS[$key]) and $var === $GLOBALS[$key]) unset($GLOBALS[$key]);
			}
		}
	}
}


/**
 * Convert an RGB triplet to an HSL triplet.
 *
 * @param array $rgb The RGB triplet.
 * @return array The HSL triplet.
 *
 * @package esoTalk
 */
function rgb2hsl($rgb)
{
	$r = $rgb[0];
	$g = $rgb[1];
	$b = $rgb[2];
	$min = min($r, min($g, $b));
	$max = max($r, max($g, $b));
	$delta = $max - $min;
	$l = ($min + $max) / 2;
	$s = 0;
	if ($l > 0 && $l < 1) {
		$s = $delta / ($l < 0.5 ? (2 * $l) : (2 - 2 * $l));
	}
	$h = 0;
	if ($delta > 0) {
		if ($max == $r && $max != $g) {
			$h += ($g - $b) / $delta;
		}
		if ($max == $g && $max != $b) {
			$h += (2 + ($b - $r) / $delta);
		}
		if ($max == $b && $max != $r) {
			$h += (4 + ($r - $g) / $delta);
		}
		$h /= 6;
	}
	return array($h, $s, $l);
}


/**
 * Convert an HSL triplet to an RGB triplet.
 *
 * @param array $hsl The HSL triplet.
 * @return array The RGB triplet.
 *
 * @package esoTalk
 */
function hsl2rgb($hsl)
{
	$h = $hsl[0];
	$s = $hsl[1];
	$l = $hsl[2];
	$m2 = ($l <= 0.5) ? $l * ($s + 1) : $l + $s - $l*$s;
	$m1 = $l * 2 - $m2;
	return array(hue2rgb($m1, $m2, $h + 0.33333),
		   hue2rgb($m1, $m2, $h),
		   hue2rgb($m1, $m2, $h - 0.33333));
}


/**
 * Helper function for hsl2rgb().
 *
 * @package esoTalk
 */
function hue2rgb($m1, $m2, $h)
{
	$h = ($h < 0) ? $h + 1 : (($h > 1) ? $h - 1 : $h);
	if ($h * 6 < 1) return $m1 + ($m2 - $m1) * $h * 6;
	if ($h * 2 < 1) return $m2;
	if ($h * 3 < 2) return $m1 + ($m2 - $m1) * (0.66666 - $h) * 6;
	return $m1;
}


/**
 * Convert a hex color into an RGB triplet.
 *
 * @param string $hex The hex color value (with a leading #).
 * @param bool $normalize Whether or not the values of the RGB triplet should be 0-255 or 0-1.
 * @return array The RGB triplet.
 *
 * @package esoTalk
 */
function colorUnpack($hex, $normalize = false)
{
	if (strlen($hex) == 4) {
		$hex = $hex[1] . $hex[1] . $hex[2] . $hex[2] . $hex[3] . $hex[3];
	}
	$c = hexdec($hex);
	for ($i = 16; $i >= 0; $i -= 8) {
		$out[] = (($c >> $i) & 0xFF) / ($normalize ? 255 : 1);
	}
	return $out;
}


/**
 * Convert an RGB triplet to a hex color.
 *
 * @param array $rgb The RGB triplet.
 * @param bool $normalize Whether or not the values of the RGB triplet are 0-255 or 0-1.
 * @return The hex color, with a leading #.
 *
 * @package esoTalk
 */
function colorPack($rgb, $normalize = false)
{
	$out = null;
	foreach ($rgb as $k => $v) {
		$out |= (($v * ($normalize ? 255 : 1)) << (16 - $k * 8));
	}
	return "#".str_pad(dechex($out), 6, 0, STR_PAD_LEFT);
}


// json_encode for PHP < 5.2.0.
if (!function_exists("json_encode")) {

function json_encode($a = false)
{
	if (is_null($a)) return "null";
	if ($a === false) return "false";
	if ($a === true) return "true";
	if (is_scalar($a)) {
		if (is_float($a)) return floatval(str_replace(",", ".", strval($a)));
		if (is_string($a)) {
			static $jsonReplaces = array(array("\\", "/", "\n", "\t", "\r", "\b", "\f", '"'), array('\\\\', '\\/', '\\n', '\\t', '\\r', '\\b', '\\f', '\"'));
			return '"'.str_replace($jsonReplaces[0], $jsonReplaces[1], $a).'"';
		} else return $a;
	}

	$isList = true;
	for ($i = 0, reset($a), $count = count($a); $i < $count; $i++, next($a)) {
		if (key($a) !== $i) {
			$isList = false;
			break;
		}
	}
	$result = array();
	if ($isList) {
	  foreach ($a as $v) $result[] = json_encode($v);
	  return '['.implode(',', $result).']';
	} else {
	  foreach ($a as $k => $v) $result[] = '"'.($k).'":'.json_encode($v);
	  return '{'.implode(',', $result).'}';
	}
}

}


// json_decode for PHP < 5.2.0
if (!function_exists("json_decode")) {

function json_decode($json)
{
	$json = str_replace(array("\\\\", "\\\""), array("&#92;", "&#34;"), $json);
	$parts = preg_split("@(\"[^\"]*\")|([\[\]\{\},:])|\s@is", $json, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
	foreach ($parts as $index => $part) {
		if (strlen($part) == 1) {
			switch ($part) {
				case "[":
				case "{":
					$parts[$index] = "array(";
					break;
				case "]":
				case "}":
					$parts[$index] = ")";
					break;
				case ":":
				  $parts[$index] = "=>";
				  break;
				case ",":
				  break;
				default:
					return null;
			}
		}
		else {
			if ((substr($part, 0, 1) != "\"") || (substr($part, -1, 1) != "\""))
				return null;
		}
	}
	$json = str_replace(array("&#92;", "&#34;", "$"), array("\\\\", "\\\"", "\\$"), implode("", $parts));
	return eval("return $json;");
}

}


/**
 * Construct a URL, given a request path.
 *
 * Constructs a relative or absolute URL which can be used to link
 * to a page in esoTalk, according to the format specified by C("esoTalk.urls").
 *
 * @param string $url The request path (eg. conversations/all). May include a query string/hash.
 * @param bool $absolute Whether or not to return an absolute URL.
 * @return string
 *
 * @package esoTalk
 */
function URL($url = "", $absolute = false)
{
	if (strpos($url, "http://") === 0) return $url;
	
	// Strip off the hash.
	$hash = strstr($url, "#");
	if ($hash) $url = substr($url, 0, -strlen($hash));

	// Strip off the query string.
	$query = strstr($url, "?");
	if ($query) $url = substr($url, 0, -strlen($query));

	// If we don't have nice urls, use ?p=controller/method/argument instead.
	if (!C("esoTalk.urls.friendly") and $url) {
		$link = "?p=".$url;
		if ($query) $query[0] = "&";
	}
	else $link = $url;

	// Re-add the query string and has to the URL.
	$link .= $query . $hash;

	// If we're not using mod_rewrite, we need to prepend "index.php/" to the link.
	if (C("esoTalk.urls.friendly") and !C("esoTalk.urls.rewrite")) $link = "index.php/$link";
	return $absolute ? rtrim(C("esoTalk.baseURL"), "/")."/".$link : getWebPath($link);
}


/**
 * Remove the absolute path to the root esoTalk directory from a file path.
 *
 * @param string $path The file path.
 * @return string The path relative to the esoTalk root directory.
 *
 * @package esoTalk
 */
function getRelativePath($path)
{
	if (strpos($path, PATH_ROOT) === 0) $path = substr($path, strlen(PATH_ROOT) + 1);
	return $path;
}


/**
 * Get the "web path" (the path relative to the domain root) for a file.
 *
 * @param string $path The path to convert.
 * @return string The web path for the specified path.
 *
 * @package esoTalk
 */
function getWebPath($path)
{
	if (strpos($path, "://") === false) {

		// Remove the absolute path to the root esoTalk directory.
		$path = getRelativePath($path);

		// Prepend the web path.
		$path = ET::$webPath."/".ltrim($path, "/");

	}
	return $path;
}


/**
 * Get the relative path or URL to a resource (a web-accessible file stored in plugins, skins, languages, or js)
 * depending on the state of the resourceURL config setting.
 *
 * @param string $path The absolute path to the resource.
 * @return string The relative path or URL to the given resource.
 *
 * @package esoTalk
 */
function getResource($path, $absolute = false)
{
	if (strpos($path, "://") === false) {

		// Remove the absolute path to the root esoTalk directory.
		$path = getRelativePath($path);

		// Prepend the web path.
		$path = ltrim($path, "/");
		if ($c = C("esoTalk.resourceURL")) $path = $c.$path;
		else $path = $absolute ? rtrim(C("esoTalk.baseURL"), "/")."/".$path : ET::$webPath."/".$path;

	}
	return $path;
}


/**
 * Construct a URL to a conversation, given its ID and title.
 *
 * @param int $conversationId The ID of the conversation.
 * @param string $title The title of the conversation.
 * @return string The URL to the conversation (to be used in the URL function.)
 *
 * @package esoTalk
 */
function conversationURL($conversationId, $title = "")
{
	return $conversationId.(($title = slug($title)) ? "-$title" : "");
}


/**
 * Construct a URL to a member, given their ID and username, and the profile pane to go to.
 *
 * @param int $memberId The ID of the member.
 * @param string $username The member's username.
 * @param string $pane The profile pane to go to.
 * @return string The URL to the member's profile (to be used in the URL function.)
 *
 * @package esoTalk
 */
function memberURL($memberId, $username = "", $pane = "")
{
	return "member/".($pane ? "$pane/" : "").$memberId.(($username = slug($username)) ? "-$username" : "");
}


/**
 * Construct a URL to a post, given its ID.
 *
 * @param int $postId The ID of the post.
 * @return string The URL to the post (to be used in the URL function.)
 *
 * @package esoTalk
 */
function postURL($postId)
{
	return "conversation/post/".$postId;
}


/**
 * Construct a URL to a search results page, given a search string.
 *
 * @param string $search The search string.
 * @param string $channel The channel slug ('all' if not specified.)
 * @return string The URL to the search page (to be used in the URL function.)
 *
 * @package esoTalk
 */
function searchURL($search, $channel = "all")
{
	return "conversations/$channel/".($search ? "?search=".urlencode($search) : "");
}


/**
 * Send a HTTP Location header to redirect to a specific page.
 *
 * @param string $destination The location to redirect to.
 * @param int $code The HTTP code to send with the redirection.
 * @return void
 *
 * @package esoTalk
 */
function redirect($destination, $code = 302)
{
	// Close the database connection.
	if (ET::$database) ET::$database->close();

	// Clear the output buffer, and send the location header.
	@ob_end_clean();
	header("Location: ".sanitizeForHTTP($destination), true, $code);
	exit;
}


/**
 * Get a human-friendly string (eg. 1 hour ago) for how much time has passed since a given time.
 *
 * @param int $then UNIX timestamp of the time to work out how much time has passed since.
 * @param bool $precise Whether or not to return "x minutes/seconds", or just "a few minutes".
 * @return string A human-friendly time string.
 *
 * @package esoTalk
 */
function relativeTime($then, $precise = false)
{
	// If there is no $then, we can only assume that whatever it is never happened...
	if (!$then) return T("never");

	// Work out how many seconds it has been since $then.
	$ago = time() - $then;

	// If $then happened less than 1 second ago (or is yet to happen,) say "Just now".
	if ($ago < 1) return T("just now");

	// If this happened over a year ago, return "x years ago".
	if ($ago >= ($period = 60 * 60 * 24 * 365.25)) {
		$years = floor($ago / $period);
		return Ts("%d year ago", "%d years ago", $years);
	}

	// If this happened over two months ago, return "x months ago".
	elseif ($ago >= ($period = 60 * 60 * 24 * (365.25 / 12)) * 2) {
		$months = floor($ago / $period);
		return Ts("%d month ago", "%d months ago", $months);
	}

	// If this happend over a week ago, return "x weeks ago".
	elseif ($ago >= ($period = 60 * 60 * 24 * 7)) {
		$weeks = floor($ago / $period);
		return Ts("%d week ago", "%d weeks ago", $weeks);
	}

	// If this happened over a day ago, return "x days ago".
	elseif ($ago >= ($period = 60 * 60 * 24)) {
		$days = floor($ago / $period);
		return Ts("%d day ago", "%d days ago", $days);
	}

	// If this happened over an hour ago, return "x hours ago".
	elseif ($ago >= ($period = 60 * 60)) {
		$hours = floor($ago / $period);
		return Ts("%d hour ago", "%d hours ago", $hours);
	}

	// If we're going for a precise value, go on to test at the minute/second level.
	if ($precise) {

		// If this happened over a minute ago, return "x minutes ago".
		if ($ago >= ($period = 60)) {
			$minutes = floor($ago / $period);
			return Ts("%d minute ago", "%d minutes ago", $minutes);
		}

		// Return "x seconds ago".
		elseif ($ago >= 1) return Ts("%d second ago", "%d seconds ago", $ago);

	}

	// Otherwise, just return "Just now".
	return T("just now");
}


/**
 * Extract the contents of a ZIP file, and return a list of files it contains and their contents.
 *
 * @param string $filename The filepath to the ZIP file.
 * @return array An array of files and their details/contents.
 *
 * @package esoTalk
 */
function unzip($filename)
{
	$files = array();
	$handle = fopen($filename, "rb");

	// Seek to the end of central directory record.
	$size = filesize($filename);
	@fseek($handle, $size - 22);

	// Error checking.
	if (ftell($handle) != $size - 22) return false; // Can't seek to end of central directory?
	// Check end of central directory signature.
	$data = unpack("Vid", fread($handle, 4));
	if ($data["id"] != 0x06054b50) return false;

	// Extract the central directory information.
	$centralDir = unpack("vdisk/vdiskStart/vdiskEntries/ventries/Vsize/Voffset/vcommentSize", fread($handle, 18));
	$pos = $centralDir["offset"];

	// Loop through each entry in the zip file.
	for ($i = 0; $i < $centralDir["entries"]; $i++) {

		// Read next central directory structure header.
		@rewind($handle);
		@fseek($handle, $pos + 4);
		$header = unpack("vversion/vversionExtracted/vflag/vcompression/vmtime/vmdate/Vcrc/VcompressedSize/Vsize/vfilenameLen/vextraLen/vcommentLen/vdisk/vinternal/Vexternal/Voffset", fread($handle, 42));

		// Get the filename.
		$header["filename"] = $header["filenameLen"] ? fread($handle, $header["filenameLen"]) : "";

		// Save the position.
		$pos = ftell($handle) + $header["extraLen"] + $header["commentLen"];

		// Go to the position of the file.
		@rewind($handle);
		@fseek($handle, $header["offset"] + 4);

		// Read the local file header to get the filename length.
		$localHeader = unpack("vversion/vflag/vcompression/vmtime/vmdate/Vcrc/VcompressedSize/Vsize/vfilenameLen/vextraLen", fread($handle, 26));

		// Get the filename.
		$localHeader["filename"] = fread($handle, $localHeader["filenameLen"]);
		// Skip the extra bit.
		if ($localHeader["extraLen"] > 0) fread($handle, $localHeader["extraLen"]);

		// Extract the file (if it's not a folder.)
		$directory = substr($header["filename"], -1) == "/";
		if (!$directory and $header["compressedSize"] > 0) {
			if ($header["compression"] == 0) $content = fread($handle, $header["compressedSize"]);
			else $content = gzinflate(fread($handle, $header["compressedSize"]));
		} else $content = "";

		// Add to the files array.
		$files[] = array(
			"name" => $header["filename"],
			"size" => $header["size"],
			"directory" => $directory,
			"content" => !$directory ? $content : false
		);

	}

	fclose($handle);

	// Return an array of files that were extracted.
	return $files;
}


/**
 * Add an element to an indexed array after a specified position.
 *
 * @param array $array The array to add to.
 * @param mixed $add The element to add.
 * @param int $position The index to add the element at.
 * @return void
 *
 * @package esoTalk
 */
function addToArray(&$array, $add, $position = false)
{
	// If no position is specified, add it to the end and return the key
	if ($position === false) {
		$array[] = $add;
		end($array);
		ksort($array);
		return key($array);
	}
	// Else, until we can get ahold of a position (starting from the specified one), keep on going!
	// Consider replacing this with array_splice, and adding the element AT the specified position (not after?)
	do {
		if (isset($array[$position])) {
			$position++;
			continue;
		}
		$array[$position] = $add;
		ksort($array);
		return $position;
	} while (true);
}



/**
 * Add an element to an keyed array after/before a certain key, or at a certain index.
 *
 * @param array $array The array to add to.
 * @param string $key The key to add to the array.
 * @param mixed $value The value to add to the array.
 * @param mixed $position The position to add the element at. If this is an integer, the element will be added
 * 		at that index. If this is an array with the first key as "before" or "after", the element will be added
 * 		before or after the specified key.
 * @return void
 *
 * @package esoTalk
 */
function addToArrayString(&$array, $key, $value, $position = false)
{
	// If we're intending to add it to the end of the array, that's easy.
	if ($position === false) {
		$array[$key] = $value;
		return;
	}

	// Otherwise, split the array into keys and values.
	$keys = array_keys($array);
	$values = array_values($array);

	// If the position is "before" or "after" a certain key, find that key in the array and record the index.
	// If the key doesn't exist, then we'll add the element to the end of the array.
	if (is_array($position)) {
		$index = array_search(reset($position), $keys, true);
		if ($index === false) $index = count($array);
		if (key($position) == "after") $index++;
	}
	// If the position is just an integer, then we already have the index.
	else $index = (int)$position;

	// Add the key/value to their respective arrays at the appropriate index.
	array_splice($keys, $index, 0, $key);
	array_splice($values, $index, 0, array($value));

	// Combine the new keys/values!
	$array = array_combine($keys, $values);
}



if (function_exists("lcfirst") === false) {
 
/**
 * Make a string's first character lowercase.
 * 
 * NOTE: Is included in PHP 5 >= 5.3.0
 * 
 * @param string $str The input string.
 * @return string 
 *
 * @package esoTalk
 */
function lcfirst($str)
{
	$str[0] = strtolower($str[0]);
	return $str;
}

}