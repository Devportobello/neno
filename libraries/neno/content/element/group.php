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
 * Class NenoContentElementGroup
 *
 * @since  1.0
 */
class NenoContentElementGroup extends NenoContentElement
{
	/**
	 * @var string
	 */
	protected $groupName;

	/**
	 * @var integer|null
	 */
	protected $extensionId;

	/**
	 * @var array
	 */
	protected $tables;

	/**
	 * {@inheritdoc}
	 *
	 * @param mixed $data
	 */
	public function __construct($data)
	{
		parent::__construct($data);

		$this->tables = array();
	}

	/**
	 * Get a group object
	 *
	 * @param integer $groupId Group Id
	 *
	 * @return NenoContentElementGroup
	 */
	public static function getGroup($groupId)
	{
		$group = new NenoContentElementGroup(static::getElementDataFromDb($groupId));

		$tablesInfo = self::getElementsByParentId(NenoContentElementTable::getDbTable(), 'group_id', $group->id, true);

		$tables = array();

		foreach ($tablesInfo as $tableInfo)
		{
			$table    = NenoContentElementTable::getTable($tableInfo);
			$tables[] = $table;
		}

		$group->setTables($tables);

		return $group;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return string
	 */
	public static function getDbTable()
	{
		return '#__neno_content_elements_groups';
	}

	/**
	 * @param string $groupName           Group name
	 * @param string $contentElementFiles Content element file path
	 * @param string $prefixPath
	 *
	 * @return bool True on success
	 * @throws Exception
	 */
	public static function parseContentElementFiles($groupName, $contentElementFiles, $prefixPath = '')
	{
		// Create an array of group data
		$groupData = array(
			'groupName' => $groupName
		);

		$group = new NenoContentElementGroup($groupData);

		foreach ($contentElementFiles as $contentElementFile)
		{
			$xmlDoc = new DOMDocument;

			if ($xmlDoc->load($contentElementFile) === false)
			{
				throw new Exception(JText::_('Error reading content element file'));
			}

			$tables = $xmlDoc->getElementsByTagName('table');

			/* @var $tableData DOMElement */
			foreach ($tables as $tableData)
			{
				$table = new NenoContentElementTable(
					array(
						'tableName' => $tableData->getAttribute('name')
					)
				);

				$fields = $tableData->getElementsByTagName('field');

				/* @var $fieldData DOMElement */
				foreach ($fields as $fieldData)
				{
					$fieldData = array(
						'fieldName' => $fieldData->getAttribute('name'),
						'translate' => intval($fieldData->getAttribute('translate'))
					);
					$field     = new NenoContentElementField($fieldData);

					$table->addField($field);
				}

				$group->addTable($table);
			}
		}

		$group->persist();

		return true;
	}

	/**
	 * Add a table to the list
	 *
	 * @param NenoContentElementTable $table
	 *
	 * @return $this
	 */
	public function addTable(NenoContentElementTable $table)
	{
		$this->tables[] = $table;

		return $this;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return boolean
	 */
	public function persist()
	{
		if (parent::persist())
		{
			/* @var $table NenoContentElementTable */
			foreach ($this->tables as $table)
			{
				$table->setGroup($this);
				$table->persist();
			}
		}
	}

	/**
	 * Get group name
	 *
	 * @return string
	 */
	public function getGroupName()
	{
		return $this->groupName;
	}

	/**
	 * Set the group name
	 *
	 * @param   string $groupName Group name
	 *
	 * @return NenoContentElementGroup
	 */
	public function setGroupName($groupName)
	{
		$this->groupName = $groupName;

		return $this;
	}

	/**
	 * Get Extension Id
	 *
	 * @return int|null
	 */
	public function getExtensionId()
	{
		return $this->extensionId;
	}

	/**
	 * Set Extension Id
	 *
	 * @param   integer $extensionId Extension Id
	 *
	 * @return NenoContentElementGroup
	 */
	public function setExtensionId($extensionId)
	{
		$this->extensionId = $extensionId;

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
	 * Get all the tables related to this group
	 *
	 * @return array
	 */
	public function getTables()
	{
		return $this->tables;
	}

	/**
	 * Set all the tables related to this group
	 *
	 * @param array $tables
	 *
	 * @return $this
	 */
	public function setTables(array $tables)
	{
		$this->tables = $tables;

		return $this;
	}
}
