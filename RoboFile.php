<?php

include '.tk/RoboFileBase.php';

class RoboFile extends RoboFileBase {
	public function directoriesStructure() {
		return array( 'assets', 'classes', 'languages' );
	}

	public function fileStructure() {
		return array( 'buddyforms-geo-my-wp.php', 'composer.json', 'license.txt', 'readme.txt' );
	}

	public function cleanPhpDirectories() {
		return array( 'classes/resources/tgm' );
	}

	public function pluginMainFile() {
		return 'buddyforms-geo-my-wp';
	}

	public function pluginFreemiusId() {
		return 3376;
	}

	public function minifyAssetsDirectories() {
		return array( 'assets' );
	}

	public function minifyImagesDirectories() {
		return array();
	}

	/**
	 * @return array Pair list of sass source directory and css target directory
	 */
	public function sassSourceTarget() {
		return array( array( 'scss/source' => 'assets/css' ) );
	}

	/**
	 * @return string Relative paths from the root folder of the plugin
	 */
	public function sassLibraryDirectory() {
		return 'scss/library';
	}
}