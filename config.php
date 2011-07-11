<?php

// this function tries to autoload classes whose definition is unknown to the interpreter at runtime
function autoload_plista_contest($className) {
	// possibly use scandir() and iterate over all directory entries

	if (is_readable(dirname(__FILE__) . '/classes/' . $className . '.php')) {
		require_once dirname(__FILE__) . '/classes/' . $className . '.php';
	}
}
spl_autoload_register('autoload_plista_contest');

// this function is a simple wrapper around json_encode and tries to call __toJSON() on any object it gets passed first
function plista_json_encode($elem) {
	if (is_object($elem)) {
		if (is_callable(array($elem, '__toJSON'))) {
			return $elem->__toJSON();
		}
	}

	$json = json_encode($elem);

	// remove all line breaks from $data
	// this behaviour needs to be documented somewhere!!
	return str_replace(array("\r", "\n"), "", $json);
}

define('PLISTA_CONTEST_TIMEOUT', 1.0);