<?php

/**
 * Builds a custom WordPress Base Theme based on the user input variables
 *
 * @author Tyler Bailey
 * @version 1.0.0
 */

class Create_Theme {

	/**
	 * Source of the base theme files
	 */
	private $source;

	/**
	 * Destination of where to save the new theme files
	 */
	private $dest;

	/**
	 * Permissions of theme directory
	 */
	private $permissions;

	/**
	 * Array of strings to replace in the theme files
	 */
	private $theme_ids;

	/**
	 * Class initialization
	 */
	public function __construct() {

		$this->source = __DIR__ . '/base-theme';
		$this->dest = __DIR__ . '/zip';
		$this->permissions = 0755;

		$this->theme_ids = array(
			'theme_name' => "{%THEME_NAME%}",
			'theme_slug' => "{%THEME_SLUG%}",
			'theme_prefix' => "{%THEME_PREFIX%}",
			'theme_author' => "{%THEME_AUTHOR%}",
			'theme_const' => "{%THEME_CONST%}"
		);

		$this->clean_theme_dir();
	}

	/**
	 * Begin the form processing
	 *
	 * @param  array $data $_POST data from form submission
	 * @return null
	 */
	public function process_form_submission($data) {

		// If they haven't filled anything out, return an error.
		if(!isset($data) || !is_array($data))
		return "No data submitted.";

		// Validate the form submission data
		$valid_data = $this->validate_form_submission($data);

		// If there were errors during validation, return them
		if(isset($valid_data['error']) && $valid_data['error'])
		return $valid_data['message'];

		// Begin the theme build
		$theme_build = $this->build_theme($valid_data);

		// If there was an error during the build, return it
		if(isset($theme_build['error']) && $theme_build['error'])
		return $theme_build['message'];
	}

	/**
	 * Validate the submitted form data
	 *
	 * @param  array $data $_POST data from form submission
	 * @return array $data validated & sanitized $_POST data
	 */
	private function validate_form_submission(&$data) {

		// If the submitted data is not empty
		if(is_array($data) && !empty($data)) {

			// Loop through the submitted data
			foreach($data as $k => $v) {

				// If we are not on the theme_author input
				if($k !== 'theme_author') {

					// If they have not filled it out
					if(strlen($v) < 1) {
						// Get the input name
						$input_name = str_replace('_', ' ', $k);

						// Return an error
						$msg = "Invalid " . $input_name;
						$this->_return_error($msg);
					} else {

						// Spam Check!
						if($k === 'email' && strlen($v) > 0) {
							$msg = "We don't like spam...";
							$this->_return_error($msg);
						}

						// Strip all HTML tags
						$v = strip_tags($v);

						// If we're on the theme_slug or prefix, make lowercase and replace spaces with dashes
						if($k === 'theme_slug' || $k === 'theme_prefix') {
							$v = str_replace(' ', '-', strtolower($v));
						}

						// Reassign the validated data to the data array
						$data[$k] = $v;
					}
				} else {
					// If we ARE on the theme_author input and it is not filled out, put a default value
					if(strlen($v) < 1) {
						$data[$k] = 'Elexicon';
					}
				}
			}

			// Unset the submit button value
			unset($data['submit_theme']);

			// Set the theme constant for development purposes
			$data['theme_const'] = strtoupper($data['theme_prefix']);

		} else {
			return false;
		}

		return $data;
	}

	/**
	 * Execute the functions required to build the theme
	 *
	 * @param  array $data $_POST data from form submission
	 * @return null
	 */
	private function build_theme($data) {
		// Create the unique directory name for this theme
		$base_dest = $this->dest . DIRECTORY_SEPARATOR . md5($data['theme_slug']);

		// Create the theme directory and copy the base theme files over
		$zip_dir = $this->create_theme_dir($this->source, $base_dest, $this->permissions);

		// If the file copy was successful, swap out the file data
		if($zip_dir)
		$swap = $this->swap_theme_data($data, $base_dest);

		// If the swap was successful, create the zip file & download it
		if($swap)
		$zip_file = $this->create_theme_zip($base_dest, $data);

		// Return error if one is present
		if(isset($zip_file['error']))
		return $zip_file;
	}

	/**
	 * Create the theme directory and add files from base-theme directory
	 *
	 * Copies all files from the base-theme directory into a new directory for zipping
	 *
	 * @param string $source the base-theme directory
	 * @param string $base_dest the destination to copy the theme to
	 * @param $permissions constant directory permissions to set
	 * @return bool
	 */
	private function create_theme_dir($source, $base_dest, $permissions) {
		// Check for symlinks
	    if (is_link($source)) {
	        return symlink(readlink($source), $base_dest);
	    }

	    // Simple copy for a file
	    if (is_file($source)) {
	        return copy($source, $base_dest);
	    }

	    // Make destination directory
	    if (!is_dir($base_dest)) {
	        mkdir($base_dest, $permissions);
	    }

	    // Loop through the folder
	    $dir = dir($source);
	    while (false !== $entry = $dir->read()) {
	        // Skip pointers
	        if ($entry == '.' || $entry == '..') {
	            continue;
	        }

	        // Deep copy directories
	        $this->create_theme_dir("$source/$entry", "$base_dest/$entry", $permissions);
	    }

	    // Clean up
	    $dir->close();
	    return true;
	}

	/**
	 * Loop through the theme files and swap the replacement strings throughout
	 *
	 * @param string $data submitted theme data
	 * @param string $dir directory of copied theme
	 * @return bool
	 */
	private function swap_theme_data($data, $dir) {

		// Use the Recursive*Iterator object to loop through files in theme directory
		foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir)) as $filename) {
			// For each submitted form input
			foreach($data as $k => $v) {
				// If it is a file (not a directory)
				if(is_file($filename)) {
					// Get the contents
					$file_contents = file_get_contents($filename);
					// Replace the contents
					$file_contents = str_replace($this->theme_ids[$k], $v, $file_contents);
					// Put the contents back
					file_put_contents($filename, $file_contents);
				}
			}
		}

		// Change JS file names
		$this->change_file_names($data, $dir);

		return true;
	}

	/**
	 * Change the theme file names
	 *
	 * @param string $dest the destination of the folder to zip
	 * @param string $slug the theme slug to name the zipped theme folder
	 * @return null
	 */
	private function change_file_names($data, $dir) {
		$js_dir = $dir . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR;

		// Change development JS file name
		if(file_exists($js_dir . 'theme-slug.js')) {
			rename($js_dir . 'theme-slug.js', $js_dir . $data['theme_slug'] . '.js');
		}

		// Change production (minified) JS file name
		if(file_exists($js_dir . 'theme-slug.min.js')) {
			rename($js_dir . 'theme-slug.min.js', $js_dir . $data['theme_slug'] . '.min.js');
		}
	}

	/**
	 * Zip the newly created theme folder up
	 *
	 * Calls the Zip_Extend object
	 *
	 * @param string $dest the destination of the folder to zip
	 * @param string $slug the theme slug to name the zipped theme folder
	 * @return string
	 */
	private function create_theme_zip($dest, $data) {
		// Include/init the Zip_Extend object
		include_once('Zip_Extend.php');
		$zip = new Zip_Extend();

		// Create a file-friendly theme name
		$theme_name = strtolower(str_replace(" ", "-", $data['theme_name']));
		// Remove all characters except a hyphen
		$theme_name = preg_replace("/[^a-z-]/i", "", $theme_name);

		// Open the zip archive folder
		$res = $zip->open($dest . DIRECTORY_SEPARATOR . $theme_name . '.zip', ZipArchive::CREATE);

		if($res === TRUE) {
			// Create the zip archive file
			$zip->add_dir($dest, $theme_name);
			$zip->close();

			// Download the file
			$this->set_download_headers($dest, $theme_name);
		} else {
			$msg = 'Failed to create zip file. Please try again.';
			$this->_return_error($msg);
		}
	}

	/**
	 * Set download headers
	 *
	 * Prompts the user to download the newly created file
	 *
	 * @param string $dest where the zip file is located
	 * @param string $slug the slug of the theme
	 * @return null
	 */
	private function set_download_headers($dest, $slug) {
		header('Content-disposition: attachment; filename=' . $slug . '.zip');
        header('Content-type: application/zip');
        readfile($dest . DIRECTORY_SEPARATOR . $slug . '.zip');
	}

	/**
	 * Clean the theme directory
	 *
	 * Loops through created theme directory and deletes themes older than 10 minutes
	 *
	 * @return null
	 */
	private function clean_theme_dir() {

		// Get all the theme directories in the zip folder
		$themes = array_filter(glob($this->dest . DIRECTORY_SEPARATOR . '*'), 'is_dir');

		// Loop through themes
		foreach($themes as $theme) {
			// Get the theme creation time
			$theme_time = filemtime($theme);

			// If the theme was created more than 1 minute ago, delete it
			if((time() - $theme_time) > 3600/60) {
				self::delete_theme($theme);
			}
		}

	}

	/**
	 * Loops through the supplied directory and deletes all files and folders within
	 *
	 * Custom folder recursion required
	 *
	 * @param string $dir_path Path to the directory to delete
	 * @return null
	 */
	private static function delete_theme($dir_path) {

		// Get all subdirectories and files within supplied directory and get paths
		$paths = new RecursiveIteratorIterator( new RecursiveDirectoryIterator($dir_path, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST );

		// Loop through paths returned from above
		foreach ($paths as $path) {
			// Determine if path is a file or directory and set the correct action to take
		    $action = ($path->isDir() ? 'rmdir' : 'unlink');
			// Execute action
		    $action($path->getRealPath());
		}

		// Remove the parent directory
		rmdir($dir_path);
	}

	/**
	 * Returns the error response
	 *
	 * @param string $msg the error response to be returned
	 * @return string
	 */
	public function _return_error($msg) {
		return $msg;
	}

}
