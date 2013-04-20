<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * The ETUpload class provides a way to easily validate and manage uploaded files. Typically, an upload should
 * be validated using the getUploadedFile() function, which will return the temporary filename of the uploaded
 * file. This can then be saved with saveAs() or saveAsImage().
 *
 * @package esoTalk
 */
class ETUpload extends ETPluggable {


/**
 * Get the maximum file upload size in bytes.
 *
 * @return int
 */
public function maxUploadSize()
{
	return min(iniToBytes(ini_get("post_max_size")), iniToBytes(ini_get("upload_max_filesize")));
}


/**
 * Validate an uploaded file and return its temporary file name.
 *
 * @param string $key The name of the key in the $_FILES array.
 * @param array $allowedTypes An array of allowed mime-types. If empty, any mime-type is allowed.
 * @return string The temporary filepath of the uploaded file.
 */
public function getUploadedFile($key, $allowedTypes = array())
{
	$error = false;

	// If the uploaded file doesn't exist, then we have to fail.
	if (!isset($_FILES[$key]) or !is_uploaded_file($_FILES[$key]["tmp_name"]))
		$error = T("message.fileUploadFailed");

	// Otherwise, check for an error.
	else {
		$file = $_FILES[$key];
		switch ($file["error"]) {
			case 1:
			case 2:
				$error = sprintf(T("message.fileUploadTooBig"), ini_get("upload_max_filesize"));
				break;
			case 3:
			case 4:
			case 6:
			case 7:
			case 8:
				$error = T("message.fileUploadFailed");
		}
	}

	// If there was an error, throw it as an exception.
	if ($error) throw new Exception($error);

	// Otherwise, return the path to the uploaded file.
	else return $file["tmp_name"];
}


/**
 * Save an uploaded file to the specified destination.
 *
 * @param string $source The source file (typically what is returned by getUploadedFile())
 * @param string $destination The destination file to save as.
 * @return string The destination file that was saved.
 */
public function saveAs($source, $destination)
{
	// Attempt to move the uploaded file to the destination. If we can't, throw an exception.
	if (!move_uploaded_file($source, $destination))
		throw new Exception(sprintf(T("message.fileUploadFailedMove"), $destination));

	return $destination;
}


/**
 * Save an uploaded file to the specified destination, and convert it to a safe image with size restraints.
 *
 * @param string $source The source file (typically what is returned by getUploadedFile())
 * @param string $destination The destination file to save as. If this includes an extension (eg. .jpg) then
 * 		the image will be saved as that type. Otherwise, the same type as the source file will be used and
 * 		the extension will be appended.
 * @param int $width The width to resize the image to.
 * @param int $height The height to resize the image to.
 * @param string $sizeMode How to handle resizing of the image and the size of the output image:
 * 		crop: the output image will be $width by $height regardless of the input image.
 * 		min: the output image will be at least $width by $height, but can be more if the input image is larger.
 * 		max: the output image will be at most $width by $height, but can be less if the input image is smaller.
 * @return string The destination file that was saved, including the image type extension.
 */
public function saveAsImage($source, $destination, $width, $height, $sizeMode = "max")
{
	// Get information about the source image and make sure it actually is an image.
	$size = getimagesize($source);
	if ($size === false) throw new Exception(T("message.fileUploadNotImage"));
	list($sourceWidth, $sourceHeight, $type) = $size;

	// Depending on the type of image, create a GD image object of it.
	switch ($type) {
		case 1:
			$image = @imagecreatefromgif($source);
			break;
		case 2:
			$image = @imagecreatefromjpeg($source);
			break;
		case 3:
			$image = @imagecreatefrompng($source);
	}

	// If we weren't able to create a GD image from the source, throw an exception.
	if (!$image) throw new Exception(T("message.fileUploadNotImage"));

	// Work out the image type which we will output the image as.
	$outputType = pathinfo($destination, PATHINFO_EXTENSION);
	$types = array(1 => "gif", 2 => "jpg", 3 => "png");

	// If an extension was specified in the destination, we'll use that and strip the it off of the
	// destination; otherwise, use the type from getimagesize().
	if (!$outputType or !in_array($outputType, $types)) {
		$outputType = $types[$type];

		// We don't support gif output, as it makes transparency difficult.
		if ($outputType == "gif") $outputType = "png";
	}
	else $destination = substr($destination, 0, -strlen($outputType) - 1);

	// Work out the ratios needed to get the image to fit into the specified width or height.
	$widthRatio = $width / $sourceWidth;
	$heightRatio = $height / $sourceHeight;

	// If we're cropping, use the larger of the two ratios so we fill the whole image area.
	if ($sizeMode == "crop") $ratio = max($widthRatio, $heightRatio);

	// If not, use the smaller of the two so we get the whole image in the area.
	else $ratio = min($widthRatio, $heightRatio);

	// If the provided width x height is a minimum, the ratio must be no smaller than one.
	if ($sizeMode == "min") $ratio = max(1, $ratio);

	// If the provided width x height is a maximum, the ratio must be no greater than one.
	elseif ($sizeMode == "max") $ratio = min(1, $ratio);

	// Work out the new width and height of the image depending on the selected ratio.
	$newWidth = ceil($ratio * $sourceWidth);
	$newHeight = ceil($ratio * $sourceHeight);

	// Work out the dimensions of the image we are creating.
	if ($sizeMode == "max" or $sizeMode == "min") {
		$width = $newWidth;
		$height = $newHeight;
	}

	// Create a new GD image of the specified width and height, and make sure we can handle transparency.
	$newImage = imagecreatetruecolor($width, $height);
	if ($outputType == "png") {
		imagecolortransparent($newImage, imagecolorallocate($newImage, 0, 0, 0));
		imagealphablending($newImage, false);
		imagesavealpha($newImage, true);
	}

	// Work out how much to offset the image by in order to center it.
	$x = $newWidth / 2 - $width / 2;
	$y = $newHeight / 2 - $height / 2;

	// Copy the source image onto our new canvas.
	imagecopyresampled($newImage, $image, -$x, -$y, 0, 0, $newWidth, $newHeight, $sourceWidth, $sourceHeight);

	// Save the image to the correct destination and format.
	switch ($outputType) {
		case "png":
			imagepng($newImage, $outputFile = "$destination.png");
			break;

		case "gif":
			imagegif($newImage, $outputFile = "$destination.gif");
			break;

		default:
			imagejpeg($newImage, $outputFile = "$destination.jpg");
	}

	// Clean up.
	imagedestroy($newImage);
	imagedestroy($image);

	return $outputFile;
}

}