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

			if ($result !== false) {
				$d = new Document($this);
				$d->data(json_decode($result['_data'], true));
				$def = $this->_schema->definition();
				foreach ($def as $source => $info) {
					$d->$source = $result[$source];
				}
				$d->id = $result['id'];
				return $d;
			}
		}
		return false;
	}

	public function find($params=array()) {
		$query = $this->select();
		$where = isset($params['where']) ? $params['where'] : array();
		if (!empty($where)) $query->where($where);
		if (isset($params['limit'])) $query->limit($params['limit']);
		if (isset($params['offset'])) $query->offset($params['offset']);
		if (isset($params['order'])) $query->order($params['order']);

		$output = array();
		$s = $query->execute();
		if ($s !== false) {
	    while ($row = $s->fetch(\PDO::FETCH_ASSOC)) {
				$d = new Document($this);
				$d->data(json_decode($row['_data'], true));
				$def = $this->_schema->definition();
				foreach ($def as $source => $info) {
					$d->$source = $row[$source];
				}
				$d->id = $row['id'];
	    	$output[] = $d;
			}
		}
		/****

			When you iterate over the results here it should be document objects that you get,
			not raw pdo results.
			so...
			result (colleciton) object
				iterator interface


		****/


		/*
		if (is_numeric($id)) {
			$result = $this->_owner->adapter()->query("SELECT * FROM " . $this->_source . " WHERE id=$id");
			if ($result !== false) {
				$fields = $result->fetch();
				$d = new Document($this);
				$d->id = $fields['id'];
				$d->data(json_decode($fields['_data'], true));
				$def = $this->_schema->definition();
				foreach ($def as $source => $info) {
					$d->$source = $fields[$source];
				}
				$d->id = $fields['id'];
				return $d;
			}
		}
		*/
		return $output;
	}

	public function select() {
		return $this->_owner->query()->from($this->_source)->select();
	}

	public function schema() { return $this->_schema; }
	public function source() { return $this->_source; }
	public function owner() { return $this->_owner; }
}



