<?php

namespace chimera;

class Model
{
	protected $_source;
	protected $_schema;
	protected $_owner;

	public function __construct($source, $schema, $owner) {
		$this->_schema 	= $schema;
		$this->_source 	= $source;
		$this->_owner 	= $owner;
	}

	public function create($props=false) {
		# add some props
		if (is_array($props)) {
			$d = new Document($this);
			$d->data($props);
			return $d;
		}

		# otherwise, return a blank one
		return new Document($this);
	}

	public function get($id=false) {
		if (is_numeric($id)) {
			$result = $this->select()->where(array('id'=>$id))->first();
			if ($result !== false)
				return $this->process_row($result);
		}
		return false;
	}

	public function find($params=array()) {
		if (!($params instanceof Query)) {
			$query = $this->select();
			$where = isset($params['where']) ? $params['where'] : array();
			if (!empty($where)) $query->where($where);
			if (isset($params['limit'])) $query->limit($params['limit']);
			if (isset($params['offset'])) $query->offset($params['offset']);
			if (isset($params['order'])) $query->order($params['order']);
		}
		// die(var_export($query->sql()));
		return $this->query($query);
	}

	public function query($query=false, $raw=false) {
		if ($query === false)
			return $this->select();
		$output = array();
		$s = $query->execute();
		if ($s !== false) {
	    while ($row = $s->fetch(\PDO::FETCH_ASSOC)) {
	    	$output[] = $raw ? $row : $this->process_row($row);
			}
		}
		return $output;
	}

	protected function process_row($row) {
		$d = new Document($this);
		$d->data(json_decode($row['_data'], true));
		$def = $this->_schema->definition();
		foreach ($def as $source => $info) {
			$d->$source = ($info['type'] == \chimera\Schema::JSON) ? json_decode($row[$source]) : $row[$source];
		}
		$d->id = $row['id'];
		return $d;
	}

	public function select() {
		return $this->_owner->query()->from($this->_source)->select();
	}

	public function schema() { return $this->_schema; }
	public function source() { return $this->_source; }
	public function owner() { return $this->_owner; }
}



