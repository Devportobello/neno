<?php

/**
 * @package     Neno
 * @subpackage  ContentElement
 *
 * @copyright   Copyright (c) 2014 Jensen Technologies S.L. All rights reserved
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('JPATH_NENO') or die;

/**
 * Class NenoContentElement
 *
 * @since  1.0
 */
abstract class NenoContentElement
{
	/**
	 * @var string
	 */
	protected static $databaseTableNames = array ();

	/**
	 * @var integer
	 */
	protected $id;

	/**
	 * @var boolean
	 */
	protected $hasChanged;

	/**
	 * Constructor
	 *
	 * @param   mixed $data Content element data
	 */
	public function __construct($data)
	{
		// Create a JObject object to unify the way to assign the properties
		$data = $this->sanitizeConstructorData($data);

		// Create a reflection class to use it to dynamic properties loading
		$classReflection = $this->getClassReflectionObject();

		// Getting all the properties marked as 'protected'
		$properties = $classReflection->getProperties(ReflectionProperty::IS_PROTECTED);

		// Go through them and assign a value to them if they exist in the argument passed as parameter.
		foreach ($properties as $property)
		{
			if ($data->get($property->getName()) !== null)
			{
				$this->{$property->getName()} = $data->get($property->getName());
			}
		}

		$this->hasChanged;
	}

	/**
	 * Make sure that the data contains CamelCase properties
	 *
	 * @param   mixed $data Data to sanitize
	 *
	 * @return JObject
	 */
	protected function sanitizeConstructorData($data)
	{
		$data         = new JObject($data);
		$properties   = $data->getProperties();
		$sanitizeData = new JObject;

		foreach ($properties as $property => $value)
		{
			$sanitizeData->set(NenoHelper::convertDatabaseColumnNameToPropertyName($property), $value);
		}

		return $sanitizeData;
	}

	/**
	 * Get a ReflectionObject to work with it.
	 *
	 * @return ReflectionClass
	 */
	public function getClassReflectionObject()
	{
		$className       = get_called_class();
		$classReflection = new ReflectionClass($className);

		return $classReflection;
	}

	/**
	 * Loads all the elements using its parent id and the parent Id value
	 *
	 * @param   string  $elementsTableName    Table Name
	 * @param   string  $parentColumnName     Parent column name
	 * @param   string  $parentId             Parent Id
	 * @param   boolean $transformProperties  If the properties should be transform to CamelCase
	 * @param   array   $extraWhereStatements Extra where statements
	 *
	 * @return array
	 */
	public static function getElementsByParentId(
		$elementsTableName,
		$parentColumnName,
		$parentId,
		$transformProperties = false,
		$extraWhereStatements = array ())
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
			->select('*')
			->from($elementsTableName)
			->where($parentColumnName . ' = ' . intval($parentId));

		if (!empty($extraWhereStatements))
		{
			foreach ($extraWhereStatements as $extraWhereStatement)
			{
				$query->where($extraWhereStatement);
			}
		}

		$db->setQuery($query);

		$elements = $db->loadObjectList();

		if ($transformProperties)
		{
			for ($i = 0; $i < count($elements); $i++)
			{
				$data = new stdClass;

				$elementArray = get_object_vars($elements[$i]);

				foreach ($elementArray as $property => $value)
				{
					$data->{NenoHelper::convertDatabaseColumnNameToPropertyName($property)} = $value;
				}

				$elements[$i] = $data;
			}
		}

		return $elements;
	}

	/**
	 * Load element from the database
	 *
	 * @param   mixed $pk it could be the ID of the element or an array of clauses
	 *
	 * @return stdClass
	 */
	public static function load($pk)
	{
		if (!is_array($pk))
		{
			$pk = array ('id' => $pk);
		}

		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
			->select('*')
			->from(self::getDbTable());

		foreach ($pk as $field => $value)
		{
			$query->where($db->quoteName($field) . ' = ' . $db->quote($value));
		}

		$db->setQuery($query);

		$data = $db->loadAssoc();

		$objectData = new stdClass;

		foreach ($data as $key => $value)
		{
			$objectData->{NenoHelper::convertDatabaseColumnNameToPropertyName($key)} = $value;
		}

		return $objectData;
	}

	/**
	 * Get the name of the database to persist the object
	 *
	 * @return string
	 */
	public static function getDbTable()
	{
		$className = get_called_class();

		if (empty(self::$databaseTableNames[$className]))
		{
			$classNameComponents = NenoHelper::splitCamelCaseString($className);
			$classNameComponents[count($classNameComponents) - 1] .= 's';

			self::$databaseTableNames[$className] = '#__' . implode('_', $classNameComponents);
		}

		return self::$databaseTableNames[$className];

	}

	public static function __callStatic($name, $arguments)
	{
		Kint::dump(func_get_args());
		exit;
	}

	public function __call($name, $arguments)
	{
		Kint::dump(func_get_args());
		exit;
	}

	/**
	 * Method to persist object in the database
	 *
	 * @return boolean
	 */
	public function persist()
	{
		$result = false;

		if ($this->hasChanged || $this->isNew())
		{
			$db   = JFactory::getDbo();
			$data = $this->toObject();

			if ($this->isNew())
			{
				$result   = $db->insertObject(self::getDbTable(), $data, 'id');
				$this->id = $db->insertid();
			}
			else
			{
				$result = $db->updateObject(self::getDbTable(), $data, 'id');
			}
		}

		return $result;
	}

	/**
	 * Check if the object is new
	 *
	 * @return bool
	 */
	public function isNew()
	{
		return empty($this->id);
	}

	/**
	 * Create a JObject using the properties of the class.
	 *
	 * @return JObject
	 */
	public function toObject()
	{
		$data = new JObject;

		// Create a reflection class to use it to dynamic properties loading
		$classReflection = $this->getClassReflectionObject();

		// Getting all the properties marked as 'protected'
		$properties = array_diff(
			$classReflection->getProperties(ReflectionProperty::IS_PROTECTED),
			$classReflection->getProperties(ReflectionProperty::IS_STATIC)
		);

		// Go through them and assign a value to them if they exist in the argument passed as parameter.
		foreach ($properties as $property)
		{
			$data->set(NenoHelper::convertPropertyNameToDatabaseColumnName($property->getName()), $this->{$property->getName()});
		}

		return $data;
	}

	/**
	 * Remove the object from the database
	 *
	 * @return bool
	 */
	public function remove()
	{
		// Only perform this task if the ID is not null or 0.
		if (!empty($this->id))
		{
			/* @var $db NenoDatabaseDriverMysqlx */
			$db = JFactory::getDbo();

			return $db->deleteObject(self::getDbTable(), $this->id);
		}

		return false;
	}

	/**
	 * Id getter
	 *
	 * @return integer
	 */
	public function getId()
	{
		return $this->id;
	}
}
