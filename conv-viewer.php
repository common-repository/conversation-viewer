<?php
/*
Plugin Name: Conversation Viewer
Plugin URI: http://super-cooper.com/2005/05/26/wordpress-plugin-conversation-viewer/
Description: Pulls a conversation from an external file and displays the conversation in a defined list. 
Version: 1.1
Author: Chad Cooper, core code from Aaron Schaefer
Author URI: http://super-cooper.com/
*/

/*
It should be noted for the record that the bulk of this code is from Aaron Shaefer's Code Viewer plugin,
so go visit him at http://elasticdog.com.

USAGE

This plugin takes an external .txt file of a conversation and pulls it into a post, thus eliminating the need to markup the 
conversation by hand in a defined or ordered list. A sample conversation could look like so:

Chad,Why did the chicken cross the road?

The Wife,I don't really care.

Chad,To get to the other side.

Chad,Really.

#The Wife,Really?

Chad,Really.

The Wife,You're crazy.

Here's basically how it works. It looks at the conversation text file line by line and takes whatever is before the comma and assigns that to the 
$person variable. It then takes everything to the right of the comma and assigns it to the $said variable. Next, it tosses $person into the <dt> and 
$said into the <dd>, line by line. Note the pound (#) sign used above in the conversation. This allows you to add emphasis, in the form of italic text,
to what someone says. Putting the # sign at the beginning of the line will put what that person said (not their name) on that particular line in italics. 
So the output of the above conversation from the plugin would look like this:

<dl class="conversation">

	<dt>Chad:</dt>

	<dd>Why did the chicken cross the road?</dd>

	<dt>The Wife:</dt>

	<dd>I don&#8217;t really care.</dd>

	<dt>Chad:</dt>

	<dd>To get to the other side.</dd>

	<dt>Chad:</dt>

	<dd>Really.</dd>

	<dt>The Wife:</dt>

	<dd class="emphasis">Really?</dd>

	<dt>Chad:</dt>

	<dd>Really.</dd>

	<dt>The Wife:</dt>

	<dd>You&#8217;re crazy.</dd>

	</dl>

That's it.
*/

/* Configuration Settings */
$default_conv_path = "http://www.super-cooper.com/conversations/";  // the absolute path of your conversations folder

/* --- STOP EDITING ---  */

function conv_viewer($conv_text) {
	global $default_conv_path;

	$count = preg_match_all('/<conversation src="([^"]+)"?\s?\/>/', $conv_text, $matches);

	for ($i = 0; $i < $count; $i++) {
		// Determine if the specified path is absolute, or relative to the root path
		// If it's neither, assume it's relative to the default path set on line 12
		if (strpos(($matches[1][$i]), 'http://') !== false) {
			$file_path = $matches[1][$i];
		} else if (substr(($matches[1][$i]), 0, 1) == '/') {
			$file_path = $_SERVER['DOCUMENT_ROOT'] . $matches[1][$i];
		} else {
			$file_path = $default_conv_path . $matches[1][$i];
		}

		// Open the file
		// If the file can't be found, print an error message
		if ($lines = @file($file_path)) {
			$conv_list = '<dl class="conversation">' . "\n";

			foreach ($lines as $line_num => $line) {
				
				// If the line is blank, insert a space to prevent collapsing
				if (ltrim($line) == "") {
					$conv_list .= "\t" . '<dt>&nbsp;</dt>' . "\n";
					// Otherwise insert the line
				} else {
					
					$line = trim($line);   // Trim leading/trailing whitespace
						if (substr($line, 0, 1) == "#") { // If it's an emphasized quote, then account for the # sign so it doesn't get included in the person's name
							$person = substr ($line, 1, (strpos($line, ",") - 1)); // Get the person's name from the line string
							} else {
							$person = substr($line, 0, strpos($line, ","));    
							}
					$said = substr($line, (strpos($line, ",") + 1));    // Get what the person said from the line string
						// Next, put the person and their quote together
						if (substr($line, 0, 1) == "#") { // If the quote is emphasized, then apply the emphasis class to the <dd>
							$conv_list .= '<dt>' . htmlspecialchars($person) . ':</dt><dd class="emphasis">' . htmlspecialchars($said) . '</dd>' . "\n";
							} else {
							$conv_list .= '<dt>' . htmlspecialchars($person) . ':</dt><dd>' . htmlspecialchars($said) . '</dd>' . "\n";
							}
				}
			}

			$conv_list .= "</dl>";
		} else {
			$conv_list = '<p class="warning">[The requested file <kbd>' . $file_path . '</kbd> could not be found]</p>';
		}

		$conv_text = str_replace(($matches[0][$i]), $conv_list, $conv_text);
	}

	return $conv_text;
}

function fix_bad_p_conv($conv_text) {
	$text = str_replace('<p><dl class="conversation">', '<dl class="conversation">', $conv_text);
	$text = str_replace('</dl></p>', '</dl>', $conv_text);

	return $conv_text;
}

add_filter('the_content', 'conv_viewer', 9);
add_filter('the_content', 'fix_bad_p_conv');
?>