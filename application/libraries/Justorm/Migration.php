<?php if (!defined('BASEPATH')) {exit('No direct script access allowed');}

/**
 * Justorm Migration Class
 *
 * @package		Justorm Codeigniter
 * @subpackage	Justorm Codeigniter
 * @category	Codeigniter Library
 * @author		Shmavon Gazanchyan <munhell@gmail.com>
 * @license		http://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * @link		http://codeblog.ru
 */
class JO_Migration
{
	/**
	 * Migration table
	 *
	 * @access	public
	 * @var		string
	 */
	public static $migration_table = 'migrations';

	/**
	 * Migration version
	 *
	 * @access	protected
	 * @var		string
	 */
	protected $version;

	/**
	 * Migration information
	 *
	 * @access	protected
	 * @var		array
	 */
	protected $migration;

	/**
	 * Model name
	 *
	 * @access	protected
	 * @var		string
	 */
	protected $model;

	/**
	 * Costructor
	 *
	 * @access		public
	 * @param		array
	 * @param		string
	 * @return		void
	 */
	public function __construct($data, $direction)
	{
		$this->load->dbforge();
		$this->version		= $data['version'];
		$this->migration	= $data[$direction];
		$this->model		= $data['model'];
	}

	/**
	 * Apply migration
	 *
	 * @access		public
	 * @return		void
	 */
	public function apply()
	{
		foreach ($this->migration as $action => $structure)
		{
			switch($action)
			{
				case 'create_table':
					foreach ($structure as $table_name => $table_data)
					{
						$columns = array();
						foreach ($table_data['columns'] as $column_name => $column_data)
						{
							$columns[$column_name] = $this->_field($column_name, $column_data);
						}
						$this->dbforge->add_field($columns);
						$this->dbforge->create_table($table_name);
						$this->_build_model($table_name);
					}
					break;
				case 'drop_table':
					if(is_array($structure))
					{
						foreach($structure as $table)
						{
							$this->dbforge->drop_table($table);
							$this->_delete_model();
						}
					}
					else
					{
						$this->dbforge->drop_table($structure);
						$this->_delete_model();
					}
					break;
				case 'rename_table':
					foreach ($structure as $original_table_name => $new_table_name)
					{
						$this->dbforge->rename_table($original_table_name, $new_table_name);
						$this->_build_model($new_table_name);
					}
					break;
				case 'add_field':
					foreach ($structure as $table_name => $table_data)
					{
						foreach ($table_data['columns'] as $column_name => $column_data)
						{
							$after = '';
							$column = $this->_field($column_name, $column_data);
							if(isset($column['after']))
							{
								$after = $column['after'];
							}
							$this->dbforge->add_column($table_name, array($column_name => $column), $after);
						}
						$this->_build_model($table_name);
					}
					break;
				case 'delete_field':
					foreach ($structure as $table_name => $table_data)
					{
						foreach ($table_data['columns'] as $column_name)
						{
							$this->dbforge->drop_column($table_name, $column_name);
						}
						$this->_build_model($table_name);
					}
					break;
				case 'modify_field':
					foreach ($structure as $table_name => $table_data)
					{
						$columns = array();
						foreach ($table_data['columns'] as $column_name => $column_data)
						{
							$columns[$column_name] = $this->_field($column_name, $column_data);
						}
						$this->dbforge->modify_column($table_name, $columns);
						$this->_build_model($table_name);
					}
					break;
				case 'rename_field':
					foreach ($structure as $table_name => $table_data)
					{
						$fields = $this->db->field_data($table_name);
						foreach ($table_data as $original_column_name => $new_column_name)
						{
							foreach($fields as $field)
							{
								if($field->name == $original_column_name)
								{
									$column = array(
										$original_column_name => array(
											'name' => $new_column_name,
											'type' => $field->type,
											'constraint' => $field->max_length
										)
									);
									$this->dbforge->modify_column($table_name, $column);
								}
							}
						}
						$this->_build_model($table_name);
					}
					break;
				case 'add_key':
					// TODO
					break;
				case 'delete_key':
					// TODO
					break;
				case 'add_index':
					// WAITING FOR CI DB DRIVER
					break;
				case 'delete_index':
					// WAITING FOR CI DB DRIVER
					break;
			}
		}
	}

	/**
	 * Build field data
	 *
	 * @access		private
	 * @param		string
	 * @param		array
	 * @return		array
	 */
	private function _field($column_name, $column_data)
	{
		$column = array();
		if (isset($column_data['type']))
		{
			// CONSTRAINT {{{
			if(isset($column_data['constraint']))
			{
				$column['constraint'] = $column_data['constraint'];
			}
			elseif (isset($column_data['length']))
			{
				$column['constraint'] = $column_data['length'];
			}
			else
			{
				$type = explode('(', $column_data['type']);
				if(count($type) === 2)
				{
					$column['constraint']	= trim($type[1], ')');
					$column_data['type']	= $type[0]; // For further check
				}
			}
			// }}}
			// TYPE {{{
			if($column_data['type'] == 'string' OR $column_data['type'] == 'varchar')
			{
				$column['type'] = 'varchar';
				if(!isset($column['constraint']))
				{
					$column['constraint'] = 255;
				}
			}
			elseif($column_data['type'] == 'int' OR $column_data['type'] == 'integer')
			{
				$column['type'] = 'int';
				if(!isset($column['constraint']))
				{
					$column['constraint'] = 16;
				}
			}
			elseif($column_data['type'] == 'id')
			{
				$column['type'] = 'int';
				$column['constraint'] = '16';
				$column['auto_increment'] = true;
				$this->dbforge->add_key($column_name, true);
			}
			else
			{
				$column['type'] = $column_data['type'];
			}
			// }}}
		}
		elseif($column_name == 'id')
		{
			$column['type'] = 'int';
			$column['constraint'] = '16';
			$column['auto_increment'] = true;
			$this->dbforge->add_key($column_name, true);
		}
		// AUTOINCREMENT {{{
		if(isset($column_data['auto_increment']))
		{
			if($column_data['auto_increment'] == 'true' OR $column_data['auto_increment'] == 'yes' OR $column_data['auto_increment'] == true)
			{
				$column['auto_increment'] = true;
			}
			else
			{
				$column['auto_increment'] = false;
			}
		}
		elseif(isset($column_data['autoincrement']))
		{
			if($column_data['autoincrement'] == 'true' OR $column_data['autoincrement'] == 'yes' OR $column_data['autoincrement'] == true)
			{
				$column['auto_increment'] = true;
			}
			else
			{
				$column['auto_increment'] = false;
			}
		}
		// }}}
		// PRIMARY {{{
		if(isset($column_data['primary']))
		{
			if($column_data['primary'] == 'true' OR $column_data['primary'] == 'yes' OR $column_data['primary'] == true)
			{
				$this->dbforge->add_key($column_name, true);
			}
		}
		// }}}
		// NULL {{{
		if(isset($column_data['null']))
		{
			if($column_data['null'] == 'true' OR $column_data['null'] == 'yes' OR $column_data['null'] == true)
			{
				$column['null'] = true;
			}
			else
			{
				$column['null'] = false;
			}
		}
		if(isset($column_data['notnull']))
		{
			if($column_data['notnull'] == 'true' OR $column_data['notnull'] == 'yes' OR $column_data['notnull'] == true)
			{
				$column['null'] = false;
			}
			else
			{
				$column['null'] = true;
			}
		}
		//}}}
		// UNSIGNED {{{
		if(isset($column_data['unsigned']))
		{
			if($column_data['unsigned'] == 'true' OR $column_data['unsigned'] == 'yes' OR $column_data['unsigned'] == true)
			{
				$column['unsigned'] = true;
			}
			else
			{
				$column['unsigned'] = false;
			}
		}
		// }}}
		// VALUES {{{
		if(isset($column_data['values']))
		{
			if($column_data['type'] == 'enum' OR $column_data['type'] == 'set')
			{
				$column['constraint'] = $column_data['values'];
			}
		}
		// }}}
		// AFTER (FOR ADDING FIELDS) {{{
		if(isset($column_data['after']))
		{
			$column['after'] = $column_data['after'];
		}
		// }}}
		return $column;
	}

	/**
	 * Build a model
	 *
	 * @access		private
	 * @param		string
	 * @return		void
	 */
	private function _build_model($table)
	{
		$model = ucfirst($this->model);
		$base_output = "<?php if (!defined('BASEPATH')) {exit('No direct script access allowed');}\n\n";
		$base_output .= "/**\n * $model model base class\n";
		$base_output .= " * @generator\tJustorm Codeigniter\n";
		$base_output .= " * @date\t".date('d.m.Y - H:i:s')."\n";
		$base_output .= " * @version\t".$this->version."\n";
		$base_output .= " */\n";
		$base_output .= "class JO_".$model."_model extends JO_Model\n{\n";
		$base_output .= "\t/**\n\t * Constructor function\n\t * Describes model structure.\n\t * Should not be edited manually.\n\t */\n";
		$base_output .= "\tpublic function __construct()\n\t{\n";
		$base_output .= "\t\t\$this->model_name = '".strtolower($model)."';\n";
		$base_output .= "\t\t\$this->table_name = '$table';\n";

		$primary = '';
		$fields = $this->db->field_data($table);

		foreach($fields as $field)
		{
			if($field->primary_key == 1)
			{
				$primary = $field->name;
			}
		}
		$base_output .= "\t\t\$this->primary = '".$primary."';\n";
		$base_output .= "\t\t\$this->fields = array(\n";

		$last = end($fields);
		foreach($fields as $field)
		{
			$base_output .= "\t\t\t'$field->name' => null";
			if($field->name == $last->name)
			{
				$base_output .= "\n";
			}
			else
			{
				$base_output .= ",\n";
			}
		}
		$base_output .= "\t\t);\n";
		$base_output .= "\t\tparent::__construct();\n\t}\n}";

		if(!file_exists(APPPATH.'models/'.strtolower($model).'_model.php'))
		{
			$output = "<?php if (!defined('BASEPATH')) {exit('No direct script access allowed');}\n\n";
			$output .= "require_once(APPPATH.'models/Justorm/JO_".strtolower($model)."_model.php');\n\n";
			$output .= "/**\n * $model model\n";
			$output .= " * @generator\tJustorm Codeigniter\n";
			$output .= " * @date\t".date('d.m.Y - H:i:s')."\n";
			$output .= " * @version\t#".$this->version."\n";
			$output .= " */\n";
			$output .= "class ".$model."_model extends JO_".$model."_model\n{\n}";
			$f = fopen(APPPATH.'models/'.strtolower($model).'_model.php', 'w');
			fwrite($f, $output);
			fclose($f);
		}

		if(!is_dir(APPPATH.'models/Justorm'))
		{
			mkdir(APPPATH.'models/Justorm');
		}
		$f = fopen(APPPATH.'models/Justorm/JO_'.strtolower($model).'_model.php', 'w');
		fwrite($f, $base_output);
		fclose($f);
	}

	/**
	 * Remove model
	 *
	 * @access		private
	 * @return		void
	 */
	private function _delete_model()
	{
		unlink(APPPATH.'models/'.strtolower($this->model).'_model.php');
	}

	/**
	 * Get/Set current migration version
	 *
	 * @access		public
	 * @param		integer
	 * @return		integer|bool
	 */
	public static function current($version = false)
	{
		$ci = &get_instance();
		$ci->load->dbforge();

		// If the migrations table is missing, make it
		if (!$ci->db->table_exists(self::$migration_table) AND $version !== false)
		{
			$ci->dbforge->add_field(array(
				'version' => array('type' => 'int', 'constraint' => 4),
			));
			$ci->dbforge->create_table(self::$migration_table, true);
			$ci->db->insert(self::$migration_table, array('version' => 0));
		}
		if(!$ci->db->table_exists(self::$migration_table) AND $version === false)
		{
			return 0;
		}

		if($version !== false)
		{
			return $ci->db->update(self::$migration_table, array(
				'version' => $version
			));
		}
		else
		{
			$row = $ci->db->select('version')->get(self::$migration_table)->row();
			return $row ? $row->version : 0;
		}
	}

	/**
	 * Allows models to access CI's loaded classes using the same
	 * syntax as controllers.
	 *
	 * @access		public
	 * @param		string
	 * @return		mixed
	 */
	public function __get($var)
	{
		return get_instance()->$var;
	}
}
