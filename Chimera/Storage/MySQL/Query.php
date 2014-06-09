<?

namespace chimera\Storage\MySQL
{

class Query
{

	protected $_fields       = array();
	protected $_conditions   = array();
	protected $_order        = array();
	protected $_group        = array();
	protected $_limit        = '';
	protected $_limit_offset = '';
	protected $_source       = '';
	protected $_adapter;
	protected $_verb				 = 'SELECT';

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

	public function order($order) {
		$this->_order = $order;
		return $this;
	}

	public function select() {
		$this->_verb = 'SELECT';
		return $this;
	}

	public function where($conditions=array(), $type='AND', $setType='AND') {
		$this->_conditions[] = array('conditions' => $conditions, 'type'=>$type, 'setType'=>$setType);
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

	public function sql() {
		$fields = empty($this->_fields) ? '*' : join(',', $this->_fields);
		$where = '';
		if (!empty($this->_conditions)) {
			$where = 'WHERE ';
			foreach ($this->_conditions as $set) {
				$placeholders = array();
				foreach ($set['conditions'] as $key => $value) {
					$placeholders[] = " $key=? ";
				}
				$where .= '(' . join($set['type'], $placeholders) . ')';
			}
		}
		$limit = empty($this->_limit) ? '' : 'LIMIT ' . $this->_limit;
		$order = empty($this->_order) ? '' : 'ORDER BY ' . $this->_order;
		$sql = sprintf('%s %s FROM %s %s %s %s', $this->_verb, $fields, $this->_source, $where, $limit, $order);
		return $sql;
	}

}

}

?>