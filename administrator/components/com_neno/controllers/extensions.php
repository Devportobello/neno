<?php
/**
 * @package     Neno
 * @subpackage  Controllers
 *
 * @author      Jensen Technologies S.L. <info@notwebdesign.com>
 * @copyright   Copyright (C) 2014 Jensen Technologies S.L. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

jimport('joomla.application.component.controlleradmin');

/**
 * Manifest Tables controller class
 *
 * @since  1.0
 */
class NenoControllerExtensions extends JControllerAdmin
{
	/**
	 * @var array
	 */
	private static $extensionTypeAllowed = array(
		'component',
		'module',
		'plugin',
		'template'
	);

	/**
	 * Escape a string
	 *
	 * @param   mixed $value Value
	 *
	 * @return string
	 */
	private static function escapeString($value)
	{
		return JFactory::getDbo()->quote($value);
	}

	/**
	 * Method to import tables that need to be translated
	 *
	 * @return void
	 */
	public function discoverExtensions()
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
			->select(
				array(
					'e.extension_id',
					'e.name',
					'e.type',
					'e.folder',
					'e.enabled'
				)
			)
			->from('`#__extensions` AS e')
			->where(
				array(
					'e.type IN (' . implode(',', array_map(array('NenoControllerExtensions', 'escapeString'), self::$extensionTypeAllowed)) . ')',
					'e.name NOT LIKE \'com_neno\'',
					'NOT EXISTS (SELECT 1 FROM `#__neno_content_elements_groups` AS ceg WHERE e.extension_id = ceg.extension_id)'
				)
			)
			->order('name');

		$db->setQuery($query);
		$extensions = $db->loadObjectList();

		for ($i = 0; $i < count($extensions); $i++)
		{
			$groupData = array(
				'groupName'   => $extensions[$i]->name,
				'extensionId' => $extensions[$i]->extension_id
			);

			$group  = new NenoContentElementGroup($groupData);
			$tables = $this->getComponentTables($group);

			if (!empty($tables))
			{
				$group->setTables($tables);
				$group->persist();
			}
		}

		$this
			->setRedirect('index.php?option=com_neno&view=extensions')
			->redirect();
	}

	/**
	 * Get all the tables of the component that matches with the Joomla naming convention.
	 *
	 * @param   NenoContentElementGroup $componentName Component name
	 *
	 * @return array
	 */
	public function getComponentTables(NenoContentElementGroup $componentData)
	{
		/* @var $db NenoDatabaseDriverMysqlx */
		$db     = JFactory::getDbo();
		$tables = $db->getComponentTables($componentData->getGroupName());

		$result = array();

		for ($i = 0; $i < count($tables); $i++)
		{
			// Get Table name
			$tableName = NenoHelper::unifyTableName($tables[$i]);

			// Create an array with the table information
			$tableData = array(
				'tableName'  => $tableName,
				'primaryKey' => $db->getPrimaryKey($tableName)
			);

			// Create ContentElement object
			$table = new NenoContentElementTable($tableData);

			// Get all the columns a table contains
			$fields = $db->getTableColumns($table->getTableName(), false);

			foreach ($fields as $fieldInfo)
			{
				$fieldData = array(
					'fieldName' => $fieldInfo->Field,
				);

				$field = new NenoContentElementField($fieldData);

				$table->addField($field);
			}

			$result[] = $table;
		}

		return $result;
	}
}
