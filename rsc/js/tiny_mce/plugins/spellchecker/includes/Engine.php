<?php
/**
 * Engine.php
 *
 * Copyright, Moxiecode Systems AB
 * Released under LGPL License.
 *
 * License: http://www.tinymce.com/license
 * Contributing: http://www.tinymce.com/contributing
 *
 * Base class for all spellcheckers this takes in the words to check
 * spelling on and returns the suggestions.
 */

class TinyMCE_SpellChecker_Engine {
	private static $engines = array();
	private $config;

	public function __constructor($config) {
		$this->config = $config;
	}

	/**
	 * Spellchecks an array of words.
	 *
	 * @param String $lang Selected language code (like en_US or de_DE). Shortcodes like "en" and "de" work with enchant >= 1.4.1
	 * @param Array $words Array of words to check.
	 * @return Name/value object with arrays of suggestions.
	 */
	public function getSuggestions($lang, $words) {
		return array();
	}

	/**
	 * Return true/false if the engine is supported by the server.
	 *
	 * @return boolean True/false if the engine is supported.
	 */
	public function isSupported() {
		return true;
	}

	/**
	 * Sets the config array used to create the instance.
	 *
	 * @param Array $config Name/value array with config options.
	 */
	public function setConfig($config) {
		$this->config = $config;
	}

	/**
	 * Returns the config array used to create the instance.
	 *
	 * @return Array Name/value array with config options.
	 */
	public function getConfig() {
		return $this->config;
	}

	// Static methods

	public static function processRequest($tinymceSpellcheckerConfig) {
		$engine = self::get($tinymceSpellcheckerConfig["engine"]);
		$engine = new $engine();
		$engine->setConfig($tinymceSpellcheckerConfig);

		header('Content-Type: application/json');
		header('Content-Encoding: UTF-8');
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: no-store, no-cache, must-revalidate");
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");

		$method = self::getParam("method", "spellcheck");
		$lang = self::getParam("lang", "en_US");
		$text = self::getParam("text");

		if ($method == "spellcheck") {
			try {
				if (!$text) {
					throw new Exception("Missing input parameter 'text'.");
				}

				if (!$engine->isSupported()) {
					throw new Exception("Current spellchecker isn't supported.");
				}

				$words = self::getWords($text);

				echo json_encode((object) array(
					"words" => (object) $engine->getSuggestions($lang, $words)
				));
			} catch (Exception $e) {
				echo json_encode((object) array(
					"error" => $e->getMessage()
				));
			}
		} else {
			echo json_encode((object) array(
				"error" => "Invalid JSON input"
			));
		}
	}

	/**
	 * Returns an request value by name without magic quoting.
	 *
	 * @param String $name Name of parameter to get.
	 * @param String $default_value Default value to return if value not found.
	 * @return String request value by name without magic quoting or default value.
	 */
	public static function getParam($name, $default_value = false) {
		if (isset($_POST[$name])) {
			$req = $_POST;
		} else if (isset($_GET[$name])) {
			$req = $_GET;
		} else {
			return $default_value;
		}

		// Handle magic quotes
		if (ini_get("magic_quotes_gpc")) {
			if (is_array($req[$name])) {
				$out = array();

				foreach ($req[$name] as $name => $value) {
					$out[stripslashes($name)] = stripslashes($value);
				}

				return $out;
			}

			return stripslashes($req[$name]);
		}

		return $req[$name];
	}

	public static function add($name, $className) {
		self::$engines[$name] = $className;
	}

	public static function get($name) {
		if (!isset(self::$engines[$name])) {
			return null;
		}

		return self::$engines[$name];
	}

	public static function getWords($text) {
		preg_match_all('(\w{3,})u', $text, $matches);
		$words = $matches[0];

		for ($i = count($words) - 1;  $i >= 0; $i--) {
			// Exclude words with numbers in them
			if (preg_match('/[0-9]+/', $words[$i])) {
				array_splice($words, $i, 1);
			}
		}

		return $words;
	}
}

?>