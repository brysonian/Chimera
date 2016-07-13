<?php

namespace chimera\Storage;


class MySQL
{

	protected static function type_for_schema_type($schema_type) {
		switch ($schema_type) {
			case \chimera\Schema::Int:
				return 'INT';

			case \chimera\Schema::String:
				return 'VARCHAR(255)';

			case \chimera\Schema::JSON:
			case \chimera\Schema::Text:
				return 'TEXT';

			case \chimera\Schema::DateTime:
				return 'DATETIME';

			case \chimera\Schema::TimeStamp:
				return 'DATETIME';

			case \chimera\Schema::UNIXTimeStamp:
				return 'INT';

			case \chimera\Schema::Date:
				return 'DATE';
		}
	}

	public static function create_table_query($table, $definition) {
		$sql = "CREATE TABLE IF NOT EXISTS $table (";
		$props = array(
			"id int NOT NULL AUTO_INCREMENT PRIMARY KEY",
			"_data TEXT"
		);

		foreach ($definition as $key => $val) {
			$props[] = "`$key` " . static::type_for_schema_type($val['type']);
		}
		return $sql . join(', ', $props) . ')';
	}

}
