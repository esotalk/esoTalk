<?php
// Copyright 2011 Toby Zerner, Simon Zerner
// This file is part of esoTalk. Please see the included license file for usage information.

if (!defined("IN_ESOTALK")) exit;

/**
 * The ETFormat class provides various formatting methods which can be performed on a string. It also includes
 * a way for plugins to hook in and add their own formatting methods.
 *
 * @package esoTalk
 */
class ETFormat extends ETPluggable {


/**
 * The content string to perform all formatting operations on.
 * @var string
 */
public $content = "";


/**
 * Whether or not to do inline-only formatting, i.e. don't embed YouTube videos, images, etc.
 * @var bool
 */
public $inline = false;


/**
 * Initialize the formatter with a content string on which all subsequent operations will be performed.
 *
 * @param string $content The content string.
 * @param bool $sanitize Whether or not to sanitize HTML in the content.
 * @return ETFormat
 */
public function init($content, $sanitize = true)
{
	// Clean up newline characters - make sure the only ones we are using are \n!
	$content = strtr($content, array("\r\n" => "\n", "\r" => "\n")) . "\n";

	// Set the content, and sanitize if necessary.
	$this->content = $sanitize ? sanitizeHTML($content) : $content;

	return $this;
}


/**
 * Turn "inline", inline-only formatting on or off.
 *
 * @param bool $inline Whether or not inline formatting should be on.
 * @return ETFormat
 */
public function inline($inline)
{
	$this->inline = $inline;
	return $this;
}


/**
 * Format the content string using a standard procedure and plugin hooks.
 *
 * @return ETFormat
 */
public function format()
{
	// Trigger the "before format" event, which can be used to strip out code blocks.
	$this->trigger("beforeFormat");

	// Format links, mentions, and quotes.
	if (C("esoTalk.format.mentions")) $this->mentions();
	if (!$this->inline) $this->quotes();
	$this->links();

	// Format bullet and numbered lists.
	if (!$this->inline) $this->lists();

	// Trigger the "format" event, where all regular formatting can be applied (bold, italic, etc.)
	$this->trigger("format");

	// Format whitespace, adding in <br/> and <p> tags.
	if (!$this->inline) $this->whitespace();

	// Trigger the "after format" event, where code blocks can be put back in.
	$this->trigger("afterFormat");

	return $this;
}


/**
 * Get the content string in its current state.
 *
 * @return string
 */
public function get()
{
	return trim($this->content);
}


public function firstLine()
{
	$this->content = substr($this->content, 0, strpos($this->content, "\n"));
	
	return $this;
}


/**
 * Clip the content string to a certain number of characters, appending "..." if necessary.
 *
 * @param int $characters The number of characters to clip to.
 * @return ETFormat
 */
public function clip($characters)
{
	// If the content string is already shorter than this, do nothing.
	if (strlen($this->content) <= $characters) return $this;

	// Cut the content down to the last full word that fits in this number of characters.
	$this->content = substr($this->content, 0, $characters);
	$this->content = substr($this->content, 0, strrpos($this->content, " "));

	// Append "...", and close all opened HTML tags.
	$this->closeTags();
	$this->content .= " ...";

	return $this;
}


/**
 * Close all unclosed HTML tags in the content string.
 *
 * @return ETFormat
 */
public function closeTags()
{
	// Remove any half-opened HTML tags at the end.
	$this->content = preg_replace('#<[^>]*$#i', "", $this->content);
	
	// Put all opened tags into an array.
    preg_match_all('#<(?!meta|img|br|hr|input\b)\b([a-z]+)(?: .*)?(?<![/|/ ])>#iU', $this->content, $result);
	$openedTags = $result[1];

	// Put all closed tags into an array.
    preg_match_all('#</([a-z]+)>#iU', $this->content, $result);
	$closedTags = $result[1];

	$numOpened = count($openedTags);

	// Go through the opened tags backwards, and close them one-by-one until we have no unclosed tags left.
	$openedTags = array_reverse($openedTags);
	for ($i = 0; $i < $numOpened; $i++) {

		// If there's no closing tag for this opening tag, append it.
		if (!in_array($openedTags[$i], $closedTags))
			$this->content .= "</".$openedTags[$i].">";

		// Otherwise, remove it from the closed tags array.
		else
			unset($closedTags[array_search($openedTags[$i], $closedTags)]);
	}

	return $this;
}


/**
 * Convert whitespace into appropriate HTML tags (<br/> and <p>).
 *
 * @return ETFormat
 */
public function whitespace()
{
	// Trim the edges of whitespace.
	$this->content = trim($this->content);

	// Add paragraphs and breakspaces.
	$this->content = "<p>".str_replace(array("\n\n", "\n"), array("</p><p>", "<br/>"), $this->content)."</p>";

	// Strip empty paragraphs.
	$this->content = preg_replace(array("/<p>\s*<\/p>/i", "/(?<=<p>)\s*(?:<br\/>)*/i", "/\s*(?:<br\/>)*\s*(?=<\/p>)/i"), "", $this->content);
	$this->content = str_replace("<p></p>", "", $this->content);

	return $this;
}


/**
 * Convert inline URLs and email addresses into HTML anchor tags.
 *
 * @return ETFormat
 */
public function links()
{
	// Convert normal links - http://www.example.com, www.example.com - using a callback function.
	$this->content = preg_replace_callback(
		"/(?<=\s|^|>|\()(\w+:\/\/)?((?:\w[\w\-]*\w\.)+(?:[a-z][a-z]+)(?:[\/#][^\s<]*?)?)(?=\)\s|[\s\.,?!>\)]*(?:\s|>|$))/i",
		array($this, "linksCallback"), $this->content);

	// Convert email links.
	$this->content = preg_replace("/[\w-\.]+@([\w-]+\.)+[\w-]{2,4}/i", "<a href='mailto:$0' class='link-email'>$0</a>", $this->content);

	return $this;
}


/**
 * The callback function used to replace inline URLs with HTML anchor tags.
 *
 * @param array $matches An array of matches from the regular expression.
 * @return string The replacement HTML anchor tag.
 */
public function linksCallback($matches)
{
	// If we're not doing inline formatting, YouTube embedding is enabled, and this is a YouTube video link,
	// then return an embed tag.
	if (!$this->inline and C("esoTalk.format.youtube") and preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*(?:\?|&amp;)v=)|youtu\.be/)([^"&?/ ]{11})(?:(?:\?|&amp;)(.*))?%i', $matches[2], $youtube)) {
		$id = $youtube[1];
		$options = $youtube[2];
		$width = 400;
		$height = 225;
		return "<iframe class='video' type='text/html' width='$width' height='$height' src='https://www.youtube.com/embed/$id?$options' allowfullscreen frameborder='0'></iframe>";
	}

	return $this->formatLink($matches[1].$matches[2], $matches[0]);
}


public function formatLink($url, $text = null)
{
	if ($text === null) $text = $url;
	if (!preg_match("/^(\w+:\/\/)/", $url)) $url = "http://".$url;

	// If this is an internal link...
	$baseURL = C("esoTalk.baseURL");
	if (substr($url, 0, strlen($baseURL)) == $baseURL) {
		return "<a href='".$url."' target='_blank' class='link-internal'>".$text."</a>";
	}

	// Otherwise, return an external HTML anchor tag.
	return "<a href='".$url."' rel='nofollow external' target='_blank' class='link-external'>".$text." <i class='icon-external-link'></i></a>";
}


/**
 * Convert simple bullet and numbered lists (eg. - list item\n - another list item) into their HTML equivalent.
 *
 * @return ETFormat
 */
public function lists()
{
	// Convert ordered lists - 1. list item\n 2. list item.
	// We do this by matching against 2 or more lines which begin with a number, passing them together to a
	// callback function, and then wrapping each line with <li> tags.
	$this->content = preg_replace_callback("/(?:^[0-9]+[.)]\s+([^\n]*)(?:\n|$)){2,}/m", function ($matches) {
		return '</p><ol>'.preg_replace("/^[0-9]+[.)]\s+([^\n]*)(?:\n|$)/m", "<li>$1</li>", trim($matches[0])).'</ol><p>';
	}, $this->content);

	// Same goes for unordered lists, but with a - or a * instead of a number.
	$this->content = preg_replace_callback("/(?:^ *[-*]\s*([^\n]*)(?:\n|$)){2,}/m", function ($matches) {
		return '</p><ul>'.preg_replace("/^ *[-*]\s*([^\n]*)(?:\n|$)/m", "<li>$1</li>", trim($matches[0])).'</ul><p>';
	}, $this->content);

	return $this;
}


/**
 * Convert [quote] tags into their HTML equivalent.
 *
 * @return ETFormat
 */
public function quotes()
{
	$self = $this;

	// Starting from the innermost quote, work our way to the outermost, replacing them one-by-one using a
	// callback function. This is the only simple way to do nested quotes without a lexer.
	$regexp = "/(.*?)\n?\[quote(?:=(.*?)(]?))?\]\n?(.*?)\n?\[\/quote\]\n{0,2}/is";
	while (preg_match($regexp, $this->content)) {
		$this->content = preg_replace_callback($regexp, function ($matches) use ($self) {
			return $matches[1].'</p>'.$self->makeQuote($matches[4], $matches[2].$matches[3]).'<p>';
		}, $this->content);
	}

	return $this;
}


/**
 * The callback function to get quote HTML, given the quote text and its citation.
 *
 * @param string $text The quoted text.
 * @param string $citation The citation text.
 * @return string The quote HTML.
 */
public function makeQuote($text, $citation = "")
{
	// If there is a citation and it has a : in it, split it into a post ID and the rest.
	if ($citation and strpos($citation, ":") !== false)
		list($postId, $citation) = explode(":", $citation, 2);

	// Construct the quote.
	$quote = "<blockquote><p>";

	// If we extracted a post ID from the citation, add a "find this post" link.
	if (!empty($postId)) $quote .= "<a href='".URL(postURL($postId), true)."' rel='post' data-id='$postId' class='control-search postRef'><i class='icon-search'></i></a> ";

	// If there is a citation, add it.
	if (!empty($citation)) $quote .= "<cite>$citation</cite> ";

	// Finish constructing and return the quote.
	$quote .= "$text\n</p></blockquote>";
	return $quote;
}


/**
 * Remove all quotes from the content string. This can be used to prevent nested quotes when quoting a post.
 *
 * @return ETFormat
 */
public function removeQuotes()
{
	while (preg_match("`(.*)\[quote(\=[^\]]+)?\].*?\[/quote\]`si", $this->content))
		$this->content = preg_replace("`(.*)\[quote(\=[^\]]+)?\].*?\[/quote\]`si", "$1", $this->content);

	return $this;
}


/**
 * Convert all @mentions into links to member profiles.
 *
 * @return ETFormat
 */
public function mentions()
{
	$this->content = preg_replace_callback(
		'/(^|[\s,\.:\]])@([^\s[\]]{2,20})\b/iu',
		function ($matches) {
			return $matches[1]."<a href='".URL('member/name/'.urlencode(str_replace('&nbsp;', ' ', $matches[2])), true)."' class='link-member'>@".$matches[2]."</a>";
		},
		$this->content
	);

	return $this;
}


/**
 * Get all of the @mentions present in a content string, and return the member names in an array.
 *
 * @param string $content The content string to get mentions from.
 * @return array
 */
public function getMentions($content)
{
	preg_match_all('/(^|[\s,\.:\]])@([^\s[\]]{2,20})\b/iu', $content, $matches, PREG_SET_ORDER);
	$names = array();
	foreach ($matches as $k => $v) $names[] = str_replace("&nbsp;", " ", $v[2]);

	return $names;
}


/**
 * Highlight a list of words in the content string.
 *
 * @return ETFormat
 */
public function highlight($words)
{
	$highlight = array_unique((array)$words);
	if (!empty($highlight)) $this->content = highlight($this->content, $highlight);

	return $this;
}

}
