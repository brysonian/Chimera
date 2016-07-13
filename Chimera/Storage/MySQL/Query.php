<?php

namespace chimera\Storage\MySQL;

class Query
{

	protected $_fields       = array();
	protected $_conditions   = array();
	protected $_order        = array();
	protected $_group        = array();
	protected $_limit        = '';
	protected $_offset = '';
	protected $_source       = '';
	protected $_adapter;
	protected $_verb				 = 'SELECT';

	protected $_user_sql		 = false;
	protected $_user_where 	 = false;

	function __construct($adapter) {
		$this->_adapter = $adapter;
	}

	public function from($source) {
		$this->_source = $source;
		return $this;
	}

	public function fields($fields) {
		$this->_fields = $fields;
		return $this;
	}

	public function limit($limit) {
		$this->_limit = $limit;
		return $this;
	}

	public function offset($offset) {
		$this->_offset = $offset;
		return $this;
	}

	public function order($order) {
		$this->_order = $order;
		return $this;
	}

	public function select() {
		$this->_verb = 'SELECT';
		return $this;
	}

	public function where($conditions=array(), $type='AND', $force=false) {
		$this->_conditions[] = array('conditions' => $conditions, 'type'=>$type);
		if ($force === true) {
			$this->_user_where = $conditions;
			$this->_conditions = [];
		}
		return $this;
	}

	public function first()
	{
		$result = $this->limit(1)->execute();
		return ($result !== false) ? $result->fetch() : false;
	}

	public function execute() {
		$stmt = $this->_adapter->prepare($this->sql());
		if($stmt && $stmt->execute($this->bound_conditions())) return $stmt;
		return false;
	}

	protected function bound_conditions() {
		$bound = array();
		if (!empty($this->_conditions)) {
			foreach ($this->_conditions as $set) {
				foreach ($set['conditions'] as $key => $value) {
					$bound[] = $value;
				}
			}
		}
		return $bound;
	}

	public function sql($SET_SQL=false) {
		if ($SET_SQL !== false) {
			$this->_user_sql = $SET_SQL;
			return;
		}

		if ($this->_user_sql !== false)
			return $this->_user_sql;

		$fields = empty($this->_fields) ? '*' : join(',', $this->_fields);
		$where = '';
		if ($this->_user_where) {
			$where = $this->_user_where;
		} else if (!empty($this->_conditions)) {
			$where = 'WHERE ';
			foreach ($this->_conditions as $k => $set) {
				$placeholders = array();
				foreach ($set['conditions'] as $key => $value) {
					$op = '=';
					if (is_array($value)) {
						$op = $value[0];
						$this->_conditions[$k]['conditions'][$key] = $value[1];
					}
					$placeholders[] = " {$key}{$op}? ";
				}
				$where .= '(' . join($set['type'], $placeholders) . ')';
			}
		}
		$limit = '';
		if (!empty($this->_limit)) {
			$limit = 'LIMIT '
							.	(empty($this->_offset) ? '' : $this->_offset . ', ')
							.	(empty($this->_limit) ? '' : $this->_limit);
		}
		$order = empty($this->_order) ? '' : 'ORDER BY ' . $this->_order;
		$sql = sprintf('%s %s FROM %s %s %s %s', $this->_verb, $fields, $this->_source, $where, $order, $limit);
		return $sql;
	}

}
