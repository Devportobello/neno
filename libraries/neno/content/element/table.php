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
 * Class NenoContentElementTable
 *
 * @since  1.0
 */
class NenoContentElementTable extends NenoContentElement
{
	/**
	 * @var NenoContentElementGroup
	 */
	protected $group;

	/**
	 * @var string
	 */
	protected $tableName;

	/**
	 * @var string
	 */
	protected $primaryKey;

	/**
	 * @var boolean
	 */
	protected $translate;

	/**
	 * @var array
	 */
	protected $fields;

	/**
	 * {@inheritdoc}
	 *
	 * @param mixed $data
	 */
	public function __construct($data)
	{
		parent::__construct($data);

		$this->fields = array();
	}


	/**
	 * Get the group that contains this table
	 *
	 * @return NenoContentElementGroup
	 */
	public function getGroup()
	{
		return $this->group;
	}

	/**
	 * Set the group that contains this table
	 *
	 * @param NenoContentElementGroup $group
	 *
	 * @return $this
	 */
	public function setGroup(NenoContentElementGroup $group)
	{
		$this->group = $group;

		return $this;
	}

	/**
	 * Get Table name
	 *
	 * @return string
	 */
	public function getTableName()
	{
		return $this->tableName;
	}

	/**
	 * Set Table name
	 *
	 * @param string $tableName
	 *
	 * @return $this
	 */
	public function setTableName($tableName)
	{
		$this->tableName = $tableName;

		return $this;
	}

	/**
	 * Get Primary key
	 *
	 * @return string
	 */
	public function getPrimaryKey()
	{
		return $this->primaryKey;
	}

	/**
	 * Set Primary key
	 *
	 * @param string $primaryKey
	 *
	 * @return $this
	 */
	public function setPrimaryKey($primaryKey)
	{
		$this->primaryKey = $primaryKey;

		return $this;
	}

	/**
	 * Check if a table has been marked as translatable
	 *
	 * @return boolean
	 */
	public function hasBeenMarkedAsTranslatable()
	{
		return $this->translate;
	}

	/**
	 * Mark a table as translatable or not.
	 *
	 * @param boolean $translate
	 *
	 * @return $this
	 */
	public function markAsTranslatable($translate)
	{
		$this->translate = $translate;

		return $this;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return ReflectionClass
	 */
	public function getClassReflectionObject()
	{
		// Create a reflection class to use it to dynamic properties loading
		$classReflection = new ReflectionClass(__CLASS__);

		return $classReflection;
	}

	/**
	 * Get the fields related to this table
	 *
	 * @return array
	 */
	public function getFields()
	{
		return $this->fields;
	}

	/**
	 * Set the fields related to this table
	 *
	 * @param array $fields
	 *
	 * @return $this
	 */
	public function setFields(array $fields)
	{
		$this->fields = $fields;

		return $this;
	}

	/**
	 * Add a field to the field list.
	 *
	 * @param NenoContentElementField $field
	 *
	 * @return NenoContentElementTable
	 */
	public function addField(NenoContentElementField $field)
	{
		$this->fields[] = $field;

		return $this;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return string
	 */
	public function getDbTable()
	{
		return '#__neno_content_elements_tables';
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return boolean
	 */
	public function persist()
	{
		parent::persist();

		/* @var $field NenoContentElementField */
		foreach ($this->fields as $field)
		{
			$field->persist();
		}
	}
}
