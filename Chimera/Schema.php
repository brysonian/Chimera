<?php

namespace chimera;


class Schema
{
	const String   = "String";
	const Int      = "Int";
	const UInt      = "UInt";
	const BigInt      = "BigInt";
	const UBigInt      = "UBigInt";
	const Text     = "Text";
	const DateTime = "DateTime";
	const TimeStamp = "TimeStamp";
	const UNIXTimeStamp = "UNIXTimeStamp";
	const Date     = "Date";
	const JSON     = "JSON";

	protected $_virtuals = array();
	protected $_definition = array();
	protected $_strict_typecheck = false;

	function __construct($def=array()) {
		$this->_definition = $def;
		foreach ($this->_definition as $key => $val) {
			if (!is_array($val)) $this->_definition[$key] = array('type' => $val);
			if (!isset($val['type'])) $this->_definition[$key]['type'] = Schema::String;
			if (!isset($val['required'])) $this->_definition[$key]['required'] = false;
		}
	}

	public function defaults() {
		$out = array();
		// set props with default values
		foreach ($this->_definition as $key => $val) {
			if (!isset($val['default'])) continue;
			$this->validate($key, $val['default']);
			$out[$key] = $val['default'];
		}
		return $out;
	}

	public function validate($key, $val=false) {
		if ($val !== false || !is_object($key))
			return $this->validate_prop($key, $val);

		# here, key will be a document
		foreach ($this->_definition as $name => $info) {
			if ($key->$name === false) {
				if ($name != 'id' && $info['required'] == true) {
					throw new \Exception("Error: $name is required.");
					return false;
				}
			} else {
				$this->validate_prop($name, $key->$name);
			}
		}
	}

	public function validate_prop($key, $val) {
		if (!isset($this->_definition[$key])) return false;
		switch ($this->_definition[$key]['type']) {
			case static::Int:
			case static::UInt:
			case static::BigInt:
			case static::UBigInt:
				if (!is_numeric($val)) throw new \Exception("Validation Error: $key must be numeric.");
				return;

			case static::Date:
			case static::DateTime:
				if ($val == 'NOW()' || $val == 'DATE()') return;
				if (strtotime($val) === false) throw new \Exception("Validation Error: $key must be a valid date or time.");
				return;

			case static::TimeStamp:
			case static::UNIXTimeStamp:
				if ($val == 'NOW()' || $val == 'DATE()') return;
				if (date('U', (int) $val) === false) throw new \Exception("Validation Error: $key must be a valid timestamp value.");
				return;

			case static::Text:
			case static::String:
				if (!$this->_strict_typecheck) return true; // make sure everything can be cast to a string!!!
				if (!is_string($val)) throw new \Exception("Validation Error: $key must be a string.");
				return;

			case static::JSON:
				return;

			default:
				throw new \Exception('Cannot validate unknown attribute type '. $this->_definition[$key]['type'] .'.');
				return;
		}
	}

	public function virtual($prop, $virtual=false) {
		if (!isset($this->_virtuals[$prop])) {
			if ($virtual !== false) $this->_virtuals[$prop] = $virtual;
			else $this->_virtuals[$prop] = new VirtualAttribute();
		}
		return $this->_virtuals[$prop];
	}

	public function has_virtual($prop) { return isset($this->_virtuals[$prop]); }

	public function strict_typecheck($val) { $this->_strict_typecheck = $val; }
	public function definition() { return $this->_definition; }
}

class VirtualAttribute
{
	private $_get;
	private $_set;

	function get($method) {	$this->_get = $method; }
	function set($method) {	$this->_set = $method; }
	function execute_get($schema) { return call_user_func($this->_get, $schema); }
	function execute_set($schema, $val) { return call_user_func($this->_set, $schema, $val); }
}
