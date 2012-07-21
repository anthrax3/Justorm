<?php if (!defined('BASEPATH')) {exit('No direct script access allowed');}

/**
 * Justorm Model Class
 *
 * @package		Justorm Codeigniter
 * @subpackage	Justorm Codeigniter
 * @category	Codeigniter Library
 * @author		Shmavon Gazanchyan <munhell@gmail.com>
 * @license		http://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * @link		http://codeblog.ru
 */
class JO_Model
{
	/**
	 * Model name
	 *
	 * @access	protected
	 * @var		string
	 */
	protected $model_name = '';

	/**
	 * Table name
	 *
	 * @access	protected
	 * @var		string
	 */
	protected $table_name = '';

	/**
	 * Primary key
	 *
	 * @access	protected
	 * @var		string
	 */
	protected $primary = 'id';

	/**
	 * Primary key value
	 *
	 * @access	protected
	 * @var		string
	 */
	protected $primary_value = null;

	/**
	 * Model fields
	 *
	 * @access	protected
	 * @var		array
	 */
	protected $fields = array();

	/**
	 * Model fields' keys
	 *
	 * @access	protected
	 * @var		array
	 */
	protected $fields_keys = array();

	/**
	 * Costructor
	 *
	 * @access		public
	 * @return		void
	 */
	public function __construct()
	{
		log_message('debug', 'Justorm Model Class Initialized');
		$this->fields_keys = array_keys($this->fields);
	}

	/**
	 * Find an object by ID
	 *
	 * @access		public
	 * @param		string
	 * @return		object
	 */
	public function find($id)
	{
		$this->fields = $this->db->select(implode(', ', $this->fields))->from($this->table_name)->where(array("$this->primary" => $id))->get()->row_array();
		$this->primary_value = $this->fields[$this->primary];
		return $this;
	}

	/**
	 * Find an object by custom parameters
	 *
	 * @access		public
	 * @param		array
	 * @return		object|array
	 */
	public function where($where)
	{
		$entities = $this->db->select(implode(', ', $this->fields))->from($this->table_name)->where($where)->get();
		if($entities->num_rows() == 1)
		{
			$this->fields = $entities->row_array();
			$this->primary_value = $this->fields[$this->primary];
			return $this;
		}
		elseif($entities->num_rows() > 1)
		{
			$objects = array();
			$objectsData = $entities->result_array();
			foreach($objectsData as $objectData)
			{
				$object = new $this;
				$object->setObject($objectData[$this->primary], $objectData);
				$objects[] = $object;
			}
			return $objects;
		}
		else
		{
			return null;
		}
	}

	/**
	 * Count objects in database
	 *
	 * @access		public
	 * @param		array
	 * @return		integer
	 */
	public function count($where = null)
	{
		$num = $this->db->from($this->table_name);
		if(is_array($where))
		{
			$num->where($where);
		}
		return $num->get()->num_rows();
	}

	/**
	 * Save an object
	 *
	 * @access		public
	 * @return		bool
	 */
	public function save()
	{
		if($this->primary_value == null)
		{
			// New object
			if(array_key_exists('created_at', $this->fields))
			{
				$this->fields['created_at'] = date('Y-m-d H:i:s');
			}
			if(array_key_exists($this->model_name.'_created_at', $this->fields))
			{
				$this->fields[$this->model_name.'_created_at'] = date('Y-m-d H:i:s');
			}
			if(array_key_exists('updated_at', $this->fields))
			{
				$this->fields['updated_at'] = date('Y-m-d H:i:s');
			}
			if(array_key_exists($this->model_name.'_updated_at', $this->fields))
			{
				$this->fields[$this->model_name.'_updated_at'] = date('Y-m-d H:i:s');
			}
			$fields = array();
			foreach($this->fields as $field_name => $field_value)
			{
				if(!is_null($field_value))
				{
					$fields[$field_name] = $field_value;
				}
			}
			return $this->db->insert($this->table_name, $fields);
		}
		else
		{
			// Existing object
			if(array_key_exists('updated_at', $this->fields))
			{
				$this->fields['updated_at'] = date('Y-m-d H:i:s');
			}
			if(array_key_exists($this->model_name.'_updated_at', $this->fields))
			{
				$this->fields[$this->model_name.'_updated_at'] = date('Y-m-d H:i:s');
			}
			return $this->db->update($this->table_name, $this->fields, array($this->primary => $this->primary_value));
		}
	}

	/**
	 * Delete an object
	 *
	 * @access		public
	 * @return		bool
	 */
	public function delete()
	{
		if($this->primary_value !== null)
		{
			$return = $this->db->delete($this->table_name, array($this->primary => $this->primary_value));
		}
		else
		{
			$this->reset();
			$return = true;
		}
		return $return;
	}

	/**
	 * Get a property
	 *
	 * @access		public
	 * @param		string
	 * @return		mixed
	 */
	public function get($property)
	{
		if(isset($this->fields[$property]))
		{
			return $this->fields[$property];
		}
		else
		{
			throw new Exception('Property "' . $property . '" in model "' . $this->table_name . '" is not accessible.');
		}
	}

	/**
	 * Set a property
	 *
	 * @access		public
	 * @param		string
	 * @param		mixed
	 * @return		object
	 */
	public function set($property, $value)
	{
		if(isset($this->fields[$property]))
		{
			$this->fields[$property] = $value;
		}
		else
		{
			throw new Exception('Property "' . $property . '" in model "' . $this->model_name . '" is not accessible.');
		}
		return $this;
	}

	/**
	 * Set properties
	 *
	 * @access		public
	 * @param		array
	 * @return		object
	 */
	public function setArray($fields)
	{
		foreach($fields as $field_name => $field_value)
		{
			if($this->fields[$field_name] === null OR isset($this->fields[$field_name]))
			{
				$this->fields[$field_name] = $field_value;
			}
		}
		return $this;
	}

	/**
	 * Get properties
	 *
	 * @access		public
	 * @return		array
	 */
	public function getArray()
	{
		return $this->fields;
	}

	/**
	 * Set primary key and properties
	 *
	 * @access		public
	 * @param		mixed
	 * @param		array
	 * @return		object
	 */
	public function setObject($primary_value, $fields)
	{
		$this->primary_value = $primary_value;
		$this->setArray($fields);
		return $this;
	}

	/**
	 * Reset all current object data
	 *
	 * @access		public
	 * @return		object
	 */
	public function reset()
	{
		$this->primary_value = null;
		foreach($this->fields as $field_name => $field_value)
		{
			$this->fields[$field_name] = null;
		}
		return $this;
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
