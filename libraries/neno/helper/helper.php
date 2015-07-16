<?php

/**
 * @package     Neno
 * @subpackage  Helper
 *
 * @author      Jensen Technologies S.L. <info@notwebdesign.com>
 * @copyright   Copyright (C) 2014 Jensen Technologies S.L. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;

/**
 * Neno helper.
 *
 * @since  1.0
 */
class NenoHelper
{
	/**
	 * Set the working language on the currently logged in user
	 *
	 * @param   string $lang 'en-GB' or 'de-DE'
	 *
	 * @return boolean
	 */
	public static function setWorkingLanguage($lang)
	{
		$userId = JFactory::getUser()->id;

		$db = JFactory::getDbo();

		/* @var $query NenoDatabaseQueryMysqli */
		$query = $db->getQuery(true);

		$query
			->replace('#__user_profiles')
			->set(
				array (
					'profile_value = ' . $db->quote($lang),
					'profile_key = ' . $db->quote('neno_working_language'),
					'user_id = ' . intval($userId)
				)
			);
		$db->setQuery($query);

		$db->execute();

		JFactory::getApplication()->setUserState('com_neno.working_language', $lang);

		return true;
	}

	/**
	 * Transform an array of stdClass to
	 *
	 * @param   array $objectList List of objects
	 *
	 * @return array
	 */
	public static function convertStdClassArrayToJObjectArray(array $objectList)
	{
		$jObjectList = array ();

		foreach ($objectList as $object)
		{
			$jObjectList[] = new JObject($object);
		}

		return $jObjectList;
	}

	/**
	 * Transform an array of neno Objects to
	 *
	 * @param   array $objectList List of objects
	 *
	 * @return array
	 */
	public static function convertNenoObjectListToJObjectList(array $objectList)
	{
		$jObjectList = array ();

		/* @var $object NenoObject */
		foreach ($objectList as $object)
		{
			$jObjectList[] = $object->prepareDataForView();
		}

		return $jObjectList;
	}

	/**
	 * Check if a string ends with a particular string
	 *
	 * @param   string $string String to be checked
	 * @param   string $suffix Suffix of the string
	 *
	 * @return bool
	 */
	public static function endsWith($string, $suffix)
	{
		return $suffix === "" || mb_strpos($string, $suffix, mb_strlen($string) - mb_strlen($suffix)) !== false;
	}

	/**
	 * Get the standard pattern
	 *
	 * @param   string $componentName Component name
	 *
	 * @return string
	 */
	public static function getTableNamePatternBasedOnComponentName($componentName)
	{
		$prefix = JFactory::getDbo()->getPrefix();

		return $prefix . str_replace(array ('com_'), '', strtolower($componentName));
	}

	/**
	 * Convert an array of objects to an simple array. If property is not specified, the property selected will be the first one.
	 *
	 * @param   array       $objectList   Object list
	 * @param   string|null $propertyName Property name
	 *
	 * @return array
	 */
	public static function convertOnePropertyObjectListToArray($objectList, $propertyName = null)
	{
		$arrayResult = array ();

		if (!empty($objectList))
		{
			// If a property wasn't passed as argument, we will get the first one.
			if ($propertyName === null)
			{
				$properties   = array_keys((array) $objectList[0]);
				$propertyName = $properties[0];
			}

			foreach ($objectList as $object)
			{
				$arrayResult[] = $object->{$propertyName};
			}
		}

		return $arrayResult;
	}

	/**
	 * Convert an array of objects to an simple array. If property is not specified, the property selected will be the first one.
	 *
	 * @param   array       $objectList   Object list
	 * @param   string|null $propertyName Property name
	 *
	 * @return array
	 */
	public static function convertOnePropertyArrayToSingleArray($objectList, $propertyName = null)
	{
		$arrayResult = array ();

		if (!empty($objectList))
		{
			// If a property wasn't passed as argument, we will get the first one.
			if ($propertyName === null)
			{
				$properties   = array_keys($objectList[0]);
				$propertyName = $properties[0];
			}

			foreach ($objectList as $object)
			{
				$arrayResult[] = $object[$propertyName];
			}
		}

		return $arrayResult;
	}

	/**
	 * Convert a camelcase property name to a underscore case database column name
	 *
	 * @param   string $propertyName Property name
	 *
	 * @return string
	 */
	public static function convertPropertyNameToDatabaseColumnName($propertyName)
	{
		return implode('_', self::splitCamelCaseString($propertyName));
	}

	/**
	 * Split a camel case string
	 *
	 * @param   string $string Camel case string
	 *
	 * @return array
	 */
	public static function splitCamelCaseString($string)
	{
		preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $string, $matches);
		$ret = $matches[0];

		foreach ($ret as &$match)
		{
			$match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
		}

		return $ret;
	}

	/**
	 * Convert an array fetched from the database to an array that the indexes match with a Class property names
	 *
	 * @param   array $databaseArray Database assoc array: [property_name] = value
	 *
	 * @return array
	 */
	public static function convertDatabaseArrayToClassArray(array $databaseArray)
	{
		$objectData = array ();

		foreach ($databaseArray as $fieldName => $fieldValue)
		{
			$objectData[self::convertDatabaseColumnNameToPropertyName($fieldName)] = $fieldValue;
		}

		return $objectData;
	}

	/**
	 * Convert a underscore case column name to a camelcase property name
	 *
	 * @param   string $columnName Database column name
	 *
	 * @return string
	 */
	public static function convertDatabaseColumnNameToPropertyName($columnName)
	{
		$nameParts = explode('_', $columnName);
		$firstWord = array_shift($nameParts);

		// If there are word left, let's capitalize them.
		if (!empty($nameParts))
		{
			$nameParts = array_merge(array ($firstWord), array_map('ucfirst', $nameParts));
		}
		else
		{
			$nameParts = array ($firstWord);
		}

		return implode('', $nameParts);
	}

	/**
	 * Discover extension
	 *
	 * @param   array $extension Extension data
	 *
	 * @return bool
	 */
	public static function discoverExtension(array $extension)
	{
		// Check if this extension has been discovered already
		$groupId = self::isExtensionAlreadyDiscovered($extension['extension_id']);

		if ($groupId !== false)
		{
			$group = NenoContentElementGroup::load($groupId);
		}
		else
		{
			$group = new NenoContentElementGroup(array ('group_name' => $extension['name']));
		}

		$group->addExtension($extension['extension_id']);

		$extensionName = self::getExtensionName($extension);
		$languageFiles = self::getLanguageFiles($extensionName);
		$tables        = self::getComponentTables($group, $extensionName);
		$group->setAssignedTranslationMethods(self::getTranslationMethodsForLanguages());

		// If the group contains tables and/or language strings, let's save it
		if (!empty($tables) || !empty($languageFiles))
		{
			$group
				->setLanguageFiles($languageFiles)
				->setTables($tables)
				->persist();
		}
		else
		{
			$group->persist();
		}

		return true;
	}

	/**
	 * Check if an extensions has been discovered yet
	 *
	 * @param   int $extensionId Extension Id
	 *
	 * @return bool|int False if the extension wasn't discovered before, group ID otherwise
	 */
	public static function isExtensionAlreadyDiscovered($extensionId)
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
			->select('group_id')
			->from('#__neno_content_element_groups_x_extensions')
			->where('extension_id = ' . (int) $extensionId);

		$db->setQuery($query);
		$groupId = $db->loadResult();

		if (!empty($groupId))
		{
			return $groupId;
		}

		return false;
	}

	/**
	 * Get the name of an extension based on its ID
	 *
	 * @param   array $extensionData Extension ID
	 *
	 * @return string
	 */
	public static function getExtensionName(array $extensionData)
	{
		$extensionName = $extensionData['element'];

		switch ($extensionData['type'])
		{
			case 'component':
				if (!self::startsWith($extensionName, 'com_'))
				{
					$extensionName = 'com_' . $extensionName;
				}
				break;
			case 'plugin':
				if (!self::startsWith($extensionName, 'plg_'))
				{
					$extensionName = 'plg_' . $extensionData['folder'] . '_' . $extensionName;
				}
				break;
			case 'module':
				if (!self::startsWith($extensionName, 'mod_'))
				{
					$extensionName = 'mod_' . $extensionName;
				}
				break;
			case 'template':
				if (!self::startsWith($extensionName, 'tpl_'))
				{
					$extensionName = 'tpl_' . $extensionName;
				}
				break;
		}

		return $extensionName;
	}

	/**
	 * Check if a string starts with a particular string
	 *
	 * @param   string $string String to be checked
	 * @param   string $prefix Prefix of the string
	 *
	 * @return bool
	 */
	public static function startsWith($string, $prefix)
	{
		return $prefix === "" || strrpos($string, $prefix, -mb_strlen($string)) !== false;
	}

	/**
	 * Get all the language strings related to a extension (group).
	 *
	 * @param   string $extensionName Extension name
	 *
	 * @return array
	 */
	public static function getLanguageFiles($extensionName)
	{
		jimport('joomla.filesystem.folder');
		$defaultLanguage     = NenoSettings::get('source_language');
		$languageFilePattern = preg_quote($defaultLanguage) . '\.' . $extensionName . '\.(((\w)*\.)^sys)?ini';
		$languageFilesPath   = JFolder::files(JPATH_ROOT . "/language/$defaultLanguage/", $languageFilePattern);

		// Getting the template to check if there are files in the template
		$template = self::getFrontendTemplate();

		// If there is a template, let's try to get those files
		if (!empty($template))
		{
			$overwriteFiles = JFolder::files(JPATH_ROOT . "/templates/$template/language/$defaultLanguage/", $languageFilePattern);

			if ($overwriteFiles !== false)
			{
				$languageFilesPath = array_merge($languageFilesPath, $overwriteFiles);
			}
		}

		$languageFiles = array ();

		foreach ($languageFilesPath as $languageFilePath)
		{
			// Only save the language strings if it's not a Joomla core components
			if (!self::isJoomlaCoreLanguageFile($languageFilePath))
			{
				// Checking if the file is already discovered
				if (self::isLanguageFileAlreadyDiscovered($languageFilePath))
				{
					$languageFile = NenoContentElementLanguageFile::load(
						array (
							'filename' => $languageFilePath
						)
					);
				}
				else
				{
					$languageFile = new NenoContentElementLanguageFile(
						array (
							'filename'  => $languageFilePath,
							'extension' => $extensionName
						)
					);

					$languageFile->loadStringsFromFile();
				}

				if (!empty($languageFile))
				{
					$languageFiles[] = $languageFile;
				}
			}
		}

		return $languageFiles;
	}

	/**
	 * Get front-end template
	 *
	 * @return string|null
	 */
	public static function getFrontendTemplate()
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query
			->select('template')
			->from('#__template_styles')
			->where(
				array (
					'home = 1',
					'client_id = 0'
				)
			)
			->group('template');

		$db->setQuery($query);
		$template = $db->loadResult();

		return $template;
	}

	/**
	 * Checks if a file is a Joomla Core language file
	 *
	 * @param   string $languageFileName Language file name
	 *
	 * @return bool
	 */
	public static function isJoomlaCoreLanguageFile($languageFileName)
	{
		$fileParts = explode('.', $languageFileName);

		$result = self::removeCoreLanguageFilesFromArray(array ($languageFileName), $fileParts[0]);

		return empty($result);
	}

	/**
	 * Takes an array of language files and filters out known language files shipped with Joomla
	 *
	 * @param   array  $files    Files to translate
	 * @param   string $language Language tag
	 *
	 * @return array
	 */
	public static function removeCoreLanguageFilesFromArray($files, $language)
	{
		// Get all the language files from Joomla core extensions based on a particular language
		$coreFiles  = self::getJoomlaCoreLanguageFiles($language);
		$validFiles = array ();

		// Filter
		foreach ($files as $file)
		{
			// If the file wasn't found, let's add it as a valid translatable file
			if (!in_array($file, $coreFiles))
			{
				$validFiles[] = $file;
			}
		}

		return $validFiles;
	}

	/**
	 * Get the language files for all the Joomla Core extensions
	 *
	 * @param   string $language JISO language string
	 *
	 * @return array
	 */
	private static function getJoomlaCoreLanguageFiles($language)
	{
		/* @var $db NenoDatabaseDriverMysqlx */
		$db         = JFactory::getDbo();
		$query      = $db->getQuery(true);
		$extensions = self::whichExtensionsShouldBeTranslated();

		$query
			->select(
				'CONCAT(' . $db->quote($language . '.') .
				',IF(type = \'plugin\' OR type = \'template\',
				IF(type = \'plugin\', CONCAT(\'plg_\',folder,\'_\'), IF(type = \'template\', \'tpl_\',\'\')),\'\'),element,\'.ini\') as extension_name'
			)
			->from('#__extensions')
			->where(
				array (
					'extension_id < 10000',
					'type IN (' . implode(',', $db->quote($extensions)) . ')'
				)
			);

		$db->setQuery($query);
		$joomlaCoreLanguageFiles = array_merge($db->loadArray(), array ($language . '.ini'));

		return $joomlaCoreLanguageFiles;
	}

	/**
	 * Return an array of extensions types allowed to be translate
	 *
	 * @return array
	 */
	public static function whichExtensionsShouldBeTranslated()
	{
		return array (
			'component',
			'module',
			'plugin',
			'template'
		);
	}

	/**
	 * Check if a language file has been discovered already
	 *
	 * @param   string $languageFileName Language file name
	 *
	 * @return bool
	 */
	public static function isLanguageFileAlreadyDiscovered($languageFileName)
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
			->select('1')
			->from(NenoContentElementLanguageFile::getDbTable())
			->where('filename = ' . $db->quote($languageFileName));

		$db->setQuery($query);
		$result = $db->loadResult();

		return $result == 1;
	}

	/**
	 * Get all the tables of the component that matches with the Joomla naming convention.
	 *
	 * @param   NenoContentElementGroup $group             Component name
	 * @param   string                  $tablePattern      Table Pattern
	 * @param   bool                    $includeDiscovered Included tables that have been discovered already
	 *
	 * @return array
	 */
	public static function getComponentTables(NenoContentElementGroup $group, $tablePattern = null, $includeDiscovered = true)
	{
		/* @var $db NenoDatabaseDriverMysqlx */
		$db     = JFactory::getDbo();
		$tables = $group->isOtherGroup() ? NenoHelperBackend::getTablesNotDiscovered() : $db->getComponentTables($tablePattern === null ? $group->getGroupName() : $tablePattern);

		$result = array ();

		for ($i = 0; $i < count($tables); $i++)
		{
			// Get Table name
			$tableName     = self::unifyTableName($tables[$i]);
			$table         = null;
			$tablesIgnored = self::getDoNotTranslateTables();

			if (!in_array($tableName, $tablesIgnored))
			{
				if (!self::isTableAlreadyDiscovered($tableName))
				{
					// Create an array with the table information
					$tableData = array (
						'tableName'  => $tableName,
						'primaryKey' => $db->getPrimaryKey($tableName),
						'translate'  => !$group->isOtherGroup(),
						'group'      => $group
					);

					// Create ContentElement object
					$table = new NenoContentElementTable($tableData);

					// Get all the columns a table contains
					$fields = $db->getTableColumns($table->getTableName());

					foreach ($fields as $fieldName => $fieldType)
					{
						$fieldData = array (
							'fieldName' => $fieldName,
							'fieldType' => $fieldType,
							'translate' => NenoContentElementField::isTranslatableType($fieldType),
							'table'     => $table
						);

						$field = new NenoContentElementField($fieldData);
						$table->addField($field);
					}
				}
				elseif ($includeDiscovered)
				{
					$table = NenoContentElementTable::load(array ('table_name' => $tableName, 'group_id' => $group->getId()));
				}
			}

			if (!empty($table))
			{
				$result[] = $table;
			}
		}

		return $result;
	}

	/**
	 * Converts a table name to the Joomla table naming convention: #__table_name
	 *
	 * @param   string $tableName Table name
	 *
	 * @return mixed
	 */
	public static function unifyTableName($tableName)
	{
		$prefix = JFactory::getDbo()->getPrefix();

		return '#__' . str_replace(array ($prefix, '#__'), '', $tableName);
	}

	/**
	 * Get all the tables that should be ignored
	 *
	 * @return array
	 */
	public static function getDoNotTranslateTables()
	{
		return array (
			'#__contentitem_tag_map',
			'#__content_frontpage',
			'#__content_rating',
			'#__content_types',
			'#__finder_links',
			'#__finder_links_terms0',
			'#__finder_links_terms1',
			'#__finder_links_terms2',
			'#__finder_links_terms3',
			'#__finder_links_terms4',
			'#__finder_links_terms5',
			'#__finder_links_terms6',
			'#__finder_links_terms7',
			'#__finder_links_terms8',
			'#__finder_links_terms9',
			'#__finder_links_termsa',
			'#__finder_links_termsb',
			'#__finder_links_termsc',
			'#__finder_links_termsd',
			'#__finder_links_termse',
			'#__finder_links_termsf',
			'#__finder_taxonomy',
			'#__finder_taxonomy_map',
			'#__finder_types',
			'#__messages',
			'#__messages_cfg',
			'#__modules_menu',
			'#__modules',
			'#__postinstall_messages',
			'#__redirect_links',
			'#__users',
			'#__banner_clients',
			'#__banner_tracks',
			'#__extensions',
			'#__overrider',
			'#__template_styles',
			'#__ucm_history',
			'#__usergroups',
			'#__user_keys',
			'#__user_notes',
			'#__user_profiles',
			'#__user_usergroup_map',
			'#__viewlevels',
			'#__menu',
			'#__menu_types',
			'#__languages',
		);
	}

	/**
	 * Check if a table has been already discovered.
	 *
	 * @param   string $tableName Table name
	 *
	 * @return bool
	 */
	public static function isTableAlreadyDiscovered($tableName)
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
			->select('1')
			->from(NenoContentElementTable::getDbTable())
			->where('table_name LIKE ' . $db->quote(self::unifyTableName($tableName)));

		$db->setQuery($query);
		$result = $db->loadResult();

		return $result == 1;
	}

	/**
	 * Get translation method for languages
	 *
	 * @return array
	 */
	public static function getTranslationMethodsForLanguages()
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
			->select(
				array (
					'DISTINCT lang',
					'translation_method_id',
					'ordering'
				)
			)
			->from('#__neno_content_language_defaults')
			->where('lang <> \'\'');

		$db->setQuery($query);

		return $db->loadObjectList();
	}

	/**
	 * Set setup state
	 *
	 * @param   string $message Message
	 * @param   int    $level   Level
	 * @param   string $type    Message type
	 *
	 * @return void
	 */
	public static function setSetupState($message, $level = 1, $type = 'info')
	{
		$db      = JFactory::getDbo();
		$query   = $db->getQuery(true);
		$percent = NenoSettings::get('current_percent');

		if ($level == 1 && $type == 'info')
		{
			$percent = $percent + NenoSettings::get('percent_per_extension');
			NenoSettings::set('current_percent', $percent);
		}

		$query
			->select('1')
			->from('#__neno_installation_messages')
			->where('message = ' . $db->quote($message));

		$db->setQuery($query);
		$result = $db->loadResult();

		if (empty($result))
		{
			$query
				->clear()
				->insert('#__neno_installation_messages')
				->columns(
					array (
						'message',
						'type',
						'percent',
						'level'
					)
				)
				->values($db->quote($message) . ',' . $db->quote($type) . ',' . (int) $percent . ',' . (int) $level);

			$db->setQuery($query);
			$db->execute();
		}
	}

	/**
	 * Concatenate a string to an array of strings
	 *
	 * @param   string $string  String to concatenate
	 * @param   array  &$array  Array of strings
	 * @param   bool   $prepend True if the string will be at beginning, false if it will be at the end.
	 *
	 * @return void
	 */
	public static function concatenateStringToStringArray($string, &$array, $prepend = true)
	{
		for ($i = 0; $i < count($array); $i++)
		{
			if ($prepend)
			{
				$array[$i] = $string . $array[$i];
			}
			else
			{
				$array[$i] = $array[$i] . $string;
			}
		}
	}

	/**
	 * Get the name of the file using its path
	 *
	 * @param   string $filePath File path including the file name
	 *
	 * @return string
	 */
	public static function getFileName($filePath)
	{
		jimport('joomla.filesystem.file');
		$pathParts = explode('/', $filePath);

		return JFile::stripExt($pathParts[count($pathParts) - 1]);
	}

	/**
	 * Output HTML code for translation progress bar
	 *
	 * @param   stdClass $wordCount   Strings translated, queued to be translated, out of sync, not translated & total
	 * @param   bool     $enabled     Render as enabled
	 * @param   bool     $showPercent Show percent flag
	 *
	 * @return string
	 */
	public static function renderWordCountProgressBar($wordCount, $enabled = true, $showPercent = false)
	{
		$displayData                     = new stdClass;
		$displayData->enabled            = $enabled;
		$displayData->wordCount          = $wordCount;
		$displayData->widthTranslated    = ($wordCount->total) ? (100 * $wordCount->translated / $wordCount->total) : (0);
		$displayData->widthQueued        = ($wordCount->total) ? (100 * $wordCount->queued / $wordCount->total) : (0);
		$displayData->widthChanged       = ($wordCount->total) ? (100 * $wordCount->changed / $wordCount->total) : (0);
		$displayData->widthNotTranslated = ($wordCount->total) ? (100 * $wordCount->untranslated / $wordCount->total) : (0);
		$displayData->showPercent        = $showPercent;

		// If total is 0 (there is no content to translate) then mark everything as translated
		if ($wordCount->total == 0)
		{
			$displayData->widthTranslated = 100;
		}

		return JLayoutHelper::render('wordcountprogressbar', $displayData, JPATH_NENO_LAYOUTS);
	}

	/**
	 * Return all groups.
	 *
	 * @param   bool $loadExtraData       Load Extra data flag
	 * @param   bool $avoidDoNotTranslate Don't return fields/keys marked as Don't translate
	 *
	 * @return  array
	 */
	public static function getGroups($loadExtraData = true, $avoidDoNotTranslate = false)
	{
		$cacheId   = NenoCache::getCacheId(__FUNCTION__, array (1));
		$cacheData = NenoCache::getCacheData($cacheId);

		if ($cacheData === null)
		{
			// Create a new query object.
			$db    = JFactory::getDbo();
			$query = $db->getQuery(true);

			$subquery1   = $db->getQuery(true);
			$arrayWhere1 = array ('t.group_id = g.id');

			if ($avoidDoNotTranslate)
			{
				$arrayWhere1[] = 't.translate = 1';
			}

			$subquery1
				->select('1')
				->from(' #__neno_content_element_tables AS t')
				->where($arrayWhere1);

			$subquery2 = $db->getQuery(true);
			$subquery2
				->select('1')
				->from('#__neno_content_element_language_files AS lf')
				->where('lf.group_id = g.id');

			$query
				->select('g.id')
				->from('`#__neno_content_element_groups` AS g')
				->where(
					array (
						'EXISTS (' . (string) $subquery1 . ')',
						'EXISTS (' . (string) $subquery2 . ')',
						'(NOT EXISTS (' . (string) $subquery1 . ') AND NOT EXISTS (' . (string) $subquery2 . ') AND NOT EXISTS(SELECT 1 FROM #__neno_content_element_groups_x_extensions AS ge WHERE g.id = ge.group_id))'
					), 'OR')
				->order(
					array (
						'IFNULL((SELECT DISTINCT 1 FROM #__neno_content_element_groups_x_translation_methods AS gtm WHERE gtm.group_id = g.id) ,0)',
						'group_name'
					)
				);

			$db->setQuery($query);
			$groups = $db->loadObjectList();

			$countGroups = count($groups);

			for ($i = 0; $i < $countGroups; $i++)
			{
				$group              = NenoContentElementGroup::getGroup($groups[$i]->id, $loadExtraData);
				$translationMethods = $group->getAssignedTranslationMethods();

				if ($avoidDoNotTranslate && empty($translationMethods))
				{
					unset ($groups[$i]);
					continue;
				}

				$groups[$i] = $group;
			}

			NenoCache::setCacheData($cacheId, $groups);
			$cacheData = $groups;
		}

		return $cacheData;
	}

	/**
	 * Return all translation methods used on any string.
	 *
	 * @param   string $type What the data is for
	 *
	 * @return  array
	 */
	public static function getTranslationMethods($type = 'list')
	{
		// Create a new query object.
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
			->select(
				array (
					'id',
					'name_constant'
				)
			)
			->from('`#__neno_translation_methods`');

		$db->setQuery($query);
		$methods            = $db->loadObjectList('id');
		$translationMethods = array ();

		if ($type != 'list')
		{
			$translationMethods = $methods;
		}
		else
		{
			foreach ($methods as $id => $methodData)
			{
				$translationMethods[$id] = JText::_($methodData->name_constant);
			}
		}

		return $translationMethods;
	}

	/**
	 * Convert HTML code into text with HTML entities
	 *
	 * @param   string $string   HTML code
	 * @param   int    $truncate Maximum length of the output text
	 *
	 * @return string
	 */
	public static function html2text($string, $truncate = null)
	{
		$string = htmlspecialchars($string);
		$ending = '';

		if ($truncate !== null)
		{
			$parts       = preg_split('/([\s\n\r]+)/', $string, null, PREG_SPLIT_DELIM_CAPTURE);
			$parts_count = count($parts);
			$length      = 0;
			$last_part   = 0;

			for (; $last_part < $parts_count; ++$last_part)
			{
				$length += mb_strlen($parts[$last_part]);

				if ($length - 3 > $truncate)
				{
					$ending = '...';
					break;
				}
			}

			if (count($parts) == 1)
			{
				$string = substr($string, 0, $truncate) . $ending;
			}
			else
			{
				$string = implode(array_slice($parts, 0, $last_part)) . $ending;
			}
		}

		return $string;
	}

	/**
	 * Load Original from a translation
	 *
	 * @param   int $translationId   Translation Id
	 * @param   int $translationType Translation Type (DB Content or Language String)
	 *
	 * @return string|null
	 */
	public static function getTranslationOriginalText($translationId, $translationType)
	{
		$cacheId    = NenoCache::getCacheId(__FUNCTION__, func_get_args());
		$cachedData = NenoCache::getCacheData($cacheId);

		if ($cachedData === null)
		{
			/* @var $db NenoDatabaseDriverMysqlX */
			$db    = JFactory::getDbo();
			$query = $db->getQuery(true);

			$translationElementId = self::getContentIdByTranslationId($translationId);

			// If the translation comes from database content, let's load it
			if ($translationType == NenoContentElementTranslation::DB_STRING)
			{
				list($fieldName, $tableName) = self::getOriginalText($translationElementId);
				$whereValues = self::getWhereValuesForTranslation($translationId);
				$string      = self::getFieldContentFromTranslationData($fieldName, $tableName, $whereValues);
			}
			else
			{
				$query
					->clear()
					->select('string')
					->from(NenoContentElementLanguageString::getDbTable())
					->where('id = ' . $translationElementId);

				$db->setQuery($query);
				$string = $db->loadResult();
			}

			NenoCache::setCacheData($cacheId, $string);
			$cachedData = $string;
		}

		return $cachedData;
	}

	public static function getContentIdByTranslationId($translationId)
	{
		$cacheId = NenoCache::getCacheId(__FUNCTION__, func_get_args());

		if (NenoCache::getCacheData($cacheId) === null)
		{
			/* @var $db NenoDatabaseDriverMysqlX */
			$db    = JFactory::getDbo();
			$query = $db->getQuery(true);

			$query
				->select('content_id')
				->from('#__neno_content_element_translations')
				->where('id = ' . $translationId);

			$db->setQuery($query);
			$translationElementId = (int) $db->loadResult();

			NenoCache::setCacheData($cacheId, $translationElementId);
		}

		return NenoCache::getCacheData($cacheId);
	}

	public static function getOriginalText($translationElementId)
	{
		$cacheId = NenoCache::getCacheId(__FUNCTION__, func_get_args());

		if (NenoCache::getCacheData($cacheId) === null)
		{
			$db    = JFactory::getDbo();
			$query = $db->getQuery(true);

			$query
				->clear()
				->select(
					array (
						'f.field_name',
						't.table_name'
					)
				)
				->from('`#__neno_content_element_fields` AS f')
				->innerJoin('`#__neno_content_element_tables` AS t ON f.table_id = t.id')
				->where('f.id = ' . $translationElementId);

			$db->setQuery($query);
			$row = $db->loadRow();
			NenoCache::setCacheData($cacheId, $row);
		}

		return NenoCache::getCacheData($cacheId);
	}

	public static function getWhereValuesForTranslation($translationId)
	{
		$cacheId = NenoCache::getCacheId(__FUNCTION__, func_get_args());

		if (NenoCache::getCacheData($cacheId) === null)
		{
			$db    = JFactory::getDbo();
			$query = $db->getQuery(true);
			$query
				->clear()
				->select(
					array (
						'f.field_name',
						'ft.value',
					)
				)
				->from('`#__neno_content_element_fields_x_translations` AS ft')
				->innerJoin('`#__neno_content_element_fields` AS f ON f.id = ft.field_id')
				->where('ft.translation_id = ' . $translationId);

			$db->setQuery($query);
			$whereValues = $db->loadAssocList('field_name');

			NenoCache::setCacheData($cacheId, $whereValues);
		}

		return NenoCache::getCacheData($cacheId);
	}

	public static function getFieldContentFromTranslationData($fieldName, $tableName, $whereValues)
	{
		$cacheId = NenoCache::getCacheId(__FUNCTION__, func_get_args());

		if (NenoCache::getCacheData($cacheId) === null)
		{
			$db    = JFactory::getDbo();
			$query = $db->getQuery(true);
			$query
				->clear()
				->select($db->quoteName($fieldName))
				->from($tableName);

			foreach ($whereValues as $whereField => $where)
			{
				$query->where($db->quoteName($whereField) . ' = ' . $db->quote($where['value']));
			}

			$db->setQuery($query);
			$string = $db->loadResult();

			NenoCache::setCacheData($cacheId, $string);
		}

		return NenoCache::getCacheData($cacheId);
	}

	/**
	 * Convert translation method name to id
	 *
	 * @param   string $translationMethodName Translation method name
	 *
	 * @return int
	 */
	public static function convertTranslationMethodNameToId($translationMethodName)
	{
		$id = 0;

		switch ($translationMethodName)
		{
			case 'manual':
				$id = 1;
				break;
			case 'machine':
				$id = 2;
				break;
			case 'pro':
				$id = 3;
				break;
		}

		return $id;
	}

	/**
	 * Convert translation method id to name
	 *
	 * @param   string $translationId Translation method ID
	 *
	 * @return int
	 */
	public static function convertTranslationMethodIdToName($translationId)
	{
		$name = 0;

		switch ($translationId)
		{
			case 1:
				$name = 'manual';
				break;
			case 2:
				$name = 'machine';
				break;
			case 3:
				$name = 'professional';
				break;
		}

		return $name;
	}

	/**
	 * Load translation methods
	 *
	 * @return array
	 */
	public static function loadTranslationMethods()
	{
		$cacheId = NenoCache::getCacheId(__FUNCTION__, array ());

		if (NenoCache::getCacheData($cacheId) === null)
		{
			$db    = JFactory::getDbo();
			$query = $db->getQuery(true);
			$query
				->select('*')
				->from('#__neno_translation_methods');

			$db->setQuery($query);
			$rows = $db->loadObjectList('id');

			NenoCache::setCacheData($cacheId, $rows);
		}

		return NenoCache::getCacheData($cacheId);
	}

	/**
	 * Create the menu structure for a particular language
	 *
	 * @param   string $languageTag Language tag
	 *
	 * @return void
	 */
	public static function createMenuStructureForLanguage($languageTag)
	{
		$db             = JFactory::getDbo();
		$query          = $db->getQuery(true);
		$sourceLanguage = NenoSettings::get('source_language');

		// Get menutypes for source language
		$query
			->select('DISTINCT mt.*')
			->from('#__menu AS m')
			->innerJoin('#__menu_types AS mt ON mt.menutype = m.menutype')
			->where('m.language = ' . $db->quote($sourceLanguage));

		$db->setQuery($query);
		$menuTypes = $db->loadObjectList();

		foreach ($menuTypes as $menuType)
		{
			// Create each menu type
			$newMenuType = self::createMenu($languageTag, $menuType, $sourceLanguage);

			// For each menu items, create its copy in the new language
			$query
				->clear()
				->select('m.*')
				->from('#__menu AS m')
				->where('m.menutype = ' . $db->quote($menuType->menutype));

			$db->setQuery($query);
			$menuItems = $db->loadObjectList();

			foreach ($menuItems as $menuItem)
			{
				$newMenuItem = clone $menuItem;
				unset($newMenuItem->id);
				$newMenuItem->menutype = $newMenuType->menutype;
				$newMenuItem->alias    = JFilterOutput::stringURLSafe($newMenuItem->alias . '-' . $languageTag);
				$newMenuItem->language = $languageTag;

				// If the menu item has been inserted properly, let's execute some actions
				if ($db->insertObject('#__menu', $newMenuItem, 'id'))
				{
					// Assign all the modules to this item
					$query = 'INSERT INTO #__modules_menu (moduleid,menuid) SELECT moduleid,' . $db->quote($newMenuItem->id) . ' FROM  #__modules_menu WHERE menuid = ' . $db->quote($menuItem->id);
					$db->setQuery($query);
					$db->execute();

					// Add this menu to the association
					$query = 'INSERT INTO #__associations (`id`,`context`,`key`) SELECT ' . $db->quote($newMenuItem->id) . ', ' . $db->quote('com_menus.item') . ', key FROM #__associations WHERE id =' . $db->quote($menuItem->id);
					$db->setQuery($query);
					$db->execute();
				}
			}
		}
	}

	/**
	 * Create a new menu
	 *
	 * @param   string   $language        Language
	 * @param   stdClass $defaultMenuType Default language menu type
	 * @param   string   $defaultLanguage Default language
	 *
	 * @return stdClass
	 */
	protected static function createMenu($language, stdClass $defaultMenuType, $defaultLanguage)
	{
		$db       = JFactory::getDbo();
		$query    = $db->getQuery(true);
		$menuType = $defaultMenuType->menutype . '-' . strtolower($language);

		$query
			->select('*')
			->from('#__menu_types')
			->where('menutype = ' . $db->quote($menuType));

		$db->setQuery($query);
		$item = $db->loadObject();

		if (empty($item))
		{
			$query
				->clear()
				->insert('#__menu_types')
				->columns(
					array (
						'menutype',
						'title'
					)
				)
				->values($db->quote($menuType) . ', ' . $db->quote($defaultMenuType->title . '(' . $language . ')'));

			$db->setQuery($query);
			$db->execute();

			$query
				->select('*')
				->from('#__menu_types')
				->where('menutype = ' . $db->quote($menuType));
			$db->setQuery($query);
			$item = $db->loadObject();

			// Create menu modules

			$query
				->clear()
				->select('*')
				->from('#__modules')
				->where(
					array (
						'module = ' . $db->quote('mod_menu'),
						'client_id = 0',
						'params LIKE ' . $db->quote('%' . $defaultMenuType->menutype . '%'),
						'language = ' . $db->quote($defaultLanguage)
					)
				);

			$db->setQuery($query);
			$modules = $db->loadObjectList();

			if (!empty($modules))
			{
				foreach ($modules as $module)
				{
					$previousId     = $module->id;
					$module->params = json_decode($module->params, true);

					$module->id                 = 0;
					$module->params['menutype'] = $item->menutype;
					$module->params             = json_encode($module->params);
					$module->language           = $language;
					$module->title              = $module->title . ' (' . $language . ')';

					$db->insertObject('#__modules', $module, 'id');

					// Assigning items
					$query = 'INSERT INTO #__modules_menu (menuid,moduleid) SELECT menuid,' . $db->quote($module->id) . ' FROM  #__modules_menu WHERE moduleid = ' . $db->quote($previousId);
					$db->setQuery($query);
					$db->execute();
				}
			}
		}

		return $item;
	}

	/**
	 * Create menu structure
	 *
	 * @return void
	 */
	public static function createMenuStructure()
	{
		/* @var $db NenoDatabaseDriverMysqlx */
		$db              = JFactory::getDbo();
		$query           = $db->getQuery(true);
		$languages       = self::getTargetLanguages();
		$defaultLanguage = NenoSettings::get('source_language');

		// Delete all the menus trashed
		$query
			->delete('#__menu')
			->where('published = -2');

		$db->setQuery($query);
		$db->execute();

		// Delete all the associations left
		$query
			->clear()
			->delete('a USING #__associations AS a')
			->where(
				array (
					'context = ' . $db->quote('com_menus.item'),
					'NOT EXISTS (SELECT 1 FROM #__menu AS m WHERE a.id = m.id)'
				)
			);

		$db->setQuery($query);
		$db->execute();

		// Delete associations with just one item
		$query
			->clear()
			->select('DISTINCT id')
			->from('#__associations')
			->group('`key`')
			->having('COUNT(*) = 1');

		$db->setQuery($query);
		$ids = $db->loadArray();

		$query
			->clear()
			->delete('#__associations')
			->where(
				array (
					'context = ' . $db->quote('com_menus.item'),
					'id IN (' . implode(', ', $db->quote($ids)) . ')'
				)
			);

		$db->setQuery($query);
		$db->execute();

		$query
			->clear()
			->update('#__modules')
			->set('language = ' . $db->quote($defaultLanguage))
			->where(
				array (
					'published = 1',
					'module = ' . $db->quote('mod_menu'),
					'client_id = 0',
					'language  = ' . $db->quote('*')
				)
			);
		$db->setQuery($query);
		$db->execute();

		// Set all the menus items from '*' to default language
		$query
			->clear()
			->update('#__menu AS m')
			->set('language = ' . $db->quote($defaultLanguage))
			->where(
				array (
					'client_id = 0',
					'level <> 0',
					'language = ' . $db->quote('*')
				)
			);

		$db->setQuery($query);
		$db->execute();

		$query
			->clear()
			->select(
				array (
					'm.*'
				)
			)
			->from('#__menu_types AS mt')
			->leftJoin('#__menu AS m ON mt.menutype = m.menutype')
			->where(
				array (
					'NOT EXISTS(SELECT 1 FROM #__associations AS a WHERE a.id = m.id AND a.`key` = ' . $db->quote('com_menus.item') . ')',
					'client_id = 0',
					'level <> 0',
					'published <> -2'
				)
			);

		$db->setQuery($query);
		$nonAssociatedMenuItems = $db->loadObjectList();
		$menuAssociations       = array ();

		$query
			->clear()
			->select('DISTINCT m1.menutype AS m1')
			->from('#__associations a1')
			->innerJoin('#__menu AS m1 ON a1.id = m1.id')
			->innerJoin('#__associations AS a2 ON a1.key = a2.key')
			->innerJoin('#__menu AS m2 ON a2.id = m2.id')
			->where(
				array (
					'a1.context = ' . $db->quote('com_menus.item'),
					'a2.context = ' . $db->quote('com_menus.item'),
					'a1.id <> a2.id',
					'm1.client_id = 0',
					'm1.level <> 0',
					'm1.published <> -2',
					'm2.client_id = 0',
					'm2.level <> 0',
					'm2.published <> -2',
				)
			);

		$db->setQuery($query);
		$menuTypes = $db->loadArray();

		foreach ($menuTypes as $menutype)
		{
			$query
				->clear()
				->select(
					array (
						'DISTINCT m2.menutype',
						'm2.language'
					)
				)
				->from('#__associations a1')
				->innerJoin('#__menu AS m1 ON a1.id = m1.id')
				->innerJoin('#__associations AS a2 ON a1.key = a2.key')
				->innerJoin('#__menu AS m2 ON a2.id = m2.id')
				->where(
					array (
						'a1.context = ' . $db->quote('com_menus.item'),
						'a2.context = ' . $db->quote('com_menus.item'),
						'a1.id <> a2.id',
						'm1.client_id = 0',
						'm1.level <> 0',
						'm1.published <> -2',
						'm2.client_id = 0',
						'm2.level <> 0',
						'm2.published <> -2',
						'm1.menutype = ' . $db->quote($menutype)
					)
				);

			$db->setQuery($query);
			$menuAssociations[$menutype] = $db->loadAssocList('language');
		}

		$menuItemsCreated  = array ();
		$modulesDuplicated = array ();

		foreach ($nonAssociatedMenuItems as $key => $menuItem)
		{
			if (!isset($menuAssociations[$menuItem->menutype]))
			{
				$menuAssociations[$menuItem->menutype] = array ();
			}

			$associations = array ();
			$insert       = false;
			$insertQuery  = $db->getQuery(true);
			$insertQuery
				->insert('#__associations')
				->columns(
					array (
						'id',
						$db->quoteName('context'),
						$db->quoteName('key')
					)
				);

			foreach ($languages as $language)
			{
				if ($language->lang_code !== $menuItem->language)
				{
					$menuItemsCreated[$language->lang_code] = array ();

					// If there's no menu associated
					if (empty($menuAssociations[$menuItem->menutype][$language->lang_code]))
					{
						if (!isset($menuAssociations[$menuItem->menutype][$language->lang_code]))
						{
							$menuAssociations[$menuItem->menutype][$language->lang_code] = array ();
						}

						$newMenuType           = new stdClass;
						$newMenuType->menutype = $menuItem->menutype;
						$newMenuType->title    = $menuItem->menutype;
						$newMenuType           = self::createMenu($language->lang_code, $newMenuType, $defaultLanguage);

						// If the menu has been inserted properly, let's save into the data structure
						if (!empty($newMenuType))
						{
							$menuAssociations[$menuItem->menutype][$language->lang_code]['menutype'] = $newMenuType->menutype;
							$menuAssociations[$menuItem->menutype][$language->lang_code]['language'] = $language->lang_code;
						}
					}

					$newMenuItem = clone $menuItem;
					unset($newMenuItem->id);
					$newMenuItem->menutype = $menuAssociations[$menuItem->menutype][$language->lang_code]['menutype'];
					$newMenuItem->alias    = JFilterOutput::stringURLSafe($newMenuItem->alias . '-' . $language->lang_code);
					$newMenuItem->language = $language->lang_code;

					// If the menu item has been inserted properly, let's execute some actions
					if ($db->insertObject('#__menu', $newMenuItem, 'id'))
					{
						$menuItemsCreated[$language->lang_code][] = $newMenuItem->id;

						// Assign all the modules to this item
						$query = 'INSERT INTO #__modules_menu (moduleid,menuid) SELECT moduleid,' . $db->quote($newMenuItem->id) . ' FROM  #__modules_menu WHERE menuid = ' . $db->quote($menuItem->id);
						$db->setQuery($query);
						$db->execute();
						$query          = $db->getQuery(true);
						$associations[] = $newMenuItem->id;
					}
				}
			}

			// Get all the modules assigned to this menu item using a different language from *
			$query
				->clear()
				->select('m.*')
				->from('#__modules AS m')
				->innerJoin('#__modules_menu AS mm ON m.id = mm.moduleid')
				->where(
					array (
						'mm.menuid = ' . (int) $menuItem->id,
						'm.language <> ' . $db->quote('*')
					)
				);

			$db->setQuery($query);
			$modules = $db->loadObjectList();

			if (!empty($modules))
			{
				$query
					->clear()
					->insert('#__modules_menu')
					->columns(
						array (
							'moduleid',
							'menuid'
						)
					);

				foreach ($menuItemsCreated as $language => $newMenuItems)
				{
					foreach ($modules as $module)
					{
						$previousId = $module->id;

						if (!isset($modulesDuplicated[$previousId . $language]))
						{
							unset($module->id);
							$module->language = $language;
							$module->title    = $module->title . '(' . $language . ')';

							if ($db->insertObject('#__modules', $module, 'id'))
							{
								$modulesDuplicated[$previousId . $language] = $module->id;
							}
						}

						foreach ($newMenuItems as $newMenuItem)
						{
							$query->values($modulesDuplicated[$previousId . $language] . ',' . $newMenuItem->id);
						}
					}
				}

				$db->setQuery($query);
				$db->execute();
			}

			$query
				->clear()
				->select($db->quoteName('key', 'associationKey'))
				->from('#__associations')
				->where(
					array (
						'id IN (' . implode(',', array_merge($associations, array ($menuItem->id))) . ')',
						'context = ' . $db->quote('com_menus.item')
					)
				);

			$db->setQuery($query);
			$associationKey = $db->loadResult();

			if (empty($associationKey))
			{
				if (!in_array($menuItem->id, $associations))
				{
					$associations[] = $menuItem->id;
				}

				$associations   = array_unique($associations);
				$associationKey = md5(json_encode($associations));
			}
			else
			{
				$query
					->clear()
					->select('id')
					->from('#__associations')
					->where($db->quoteName('key') . ' = ' . $db->quote($associationKey));

				$db->setQuery($query);
				$alreadyInserted = $db->loadArray();
				$associations    = array_diff($associations, $alreadyInserted);
			}

			foreach ($associations as $association)
			{
				$insertQuery->values($association . ',' . $db->quote('com_menus.item') . ',' . $db->quote($associationKey));
				$insert = true;
			}

			if ($insert)
			{
				$db->setQuery($insertQuery);
				$db->execute();
			}
		}

		// Get all the modules with the language as default
		$query
			->clear()
			->select('m.*')
			->from('#__modules AS m')
			->where('m.language = ' . $db->quote($defaultLanguage));

		$db->setQuery($query);
		$modules = $db->loadObjectList();

		foreach ($modules as $module)
		{
			$previousId    = $module->id;
			$previousTitle = $module->title;

			foreach ($languages as $language)
			{
				if ($language->lang_code != $defaultLanguage)
				{
					$module->id       = 0;
					$module->title    = $previousTitle . ' (' . $language->lang_code . ')';
					$module->language = $language->lang_code;

					// If the module has been inserted correctly, let's assign it
					if ($db->insertObject('#__modules', $module, 'id'))
					{
						$insert      = false;
						$insertQuery = $db->getQuery(true);
						$insertQuery
							->clear()
							->insert('#__modules_menu')
							->columns(
								array (
									'moduleid',
									'menuid'
								)
							);

						// Check if the previous module is assigned to all
						$query
							->clear()
							->select('1')
							->from('#__modules_menu')
							->where(
								array (
									'moduleid = ' . $previousId,
									'menuid'
								)
							);

						$db->setQuery($query);
						$result = $db->loadResult();

						if ($result == 1)
						{
							$insertQuery->values($module->id . ', 0');
							$insert = true;
						}
						else
						{
							// Check if the module has assigned selected
							$query
								->clear()
								->select('DISTINCT m2.id')
								->from('#__modules_menu AS mm')
								->innerJoin('#__menu AS m1 ON mm.menuid = m1.id')
								->innerJoin('#__associations AS a1 ON a1.id = m1.id')
								->innerJoin('#__associations AS a2 ON a1.key = a2.key')
								->innerJoin('#__menu AS m2 ON a2.id = m2.id')
								->where(
									array (
										'a1.context = ' . $db->quote('com_menus.item'),
										'a2.context = ' . $db->quote('com_menus.item'),
										'a1.id <> a2.id',
										'm1.client_id = 0',
										'm1.level <> 0',
										'm1.published <> -2',
										'm2.client_id = 0',
										'm2.level <> 0',
										'm2.published <> -2',
										'mm.moduleid = ' . $previousId,
										'm1.language = ' . $db->quote($defaultLanguage),
										'm2.language = ' . $db->quote($language->lang_code)
									)
								);

							$db->setQuery($query);
							$menuIds = $db->loadArray();

							if (!empty($menuIds))
							{
								$insert = true;

								foreach ($menuIds as $menuId)
								{
									$insertQuery->values($module->id . ',' . $menuId);
								}
							}
						}

						if ($insert)
						{
							$db->setQuery($insertQuery);
							$db->execute();
						}
					}
				}
			}
		}

		// Once we finish restructuring menus, let's rebuild them
		$menuTable = new JTableMenu($db);
		$menuTable->rebuild();
	}

	/**
	 * Get an array indexed by language code of the target languages
	 *
	 * @param   boolean $published Weather or not only the published language should be loaded
	 *
	 * @return array objectList
	 */
	public static function getTargetLanguages($published = true)
	{
		// Load all published languages
		$languages       = self::getLanguages($published);
		$defaultLanguage = NenoSettings::get('source_language');

		// Create a simple array
		$arr = array ();

		if (!empty($languages))
		{
			foreach ($languages as $lang)
			{
				// Do not include the default language
				if ($lang->lang_code !== $defaultLanguage)
				{
					$arr[$lang->lang_code] = $lang;
				}
			}
		}

		return $arr;
	}

	/**
	 * Load all published languages on the site
	 *
	 * @param   boolean $published     Weather or not only the published language should be loaded
	 * @param   boolean $includeSource Include source language
	 *
	 * @return array objectList
	 */
	public static function getLanguages($published = true, $includeSource = true)
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
			->select('*')
			->from('#__languages')
			->where('lang_code IN(' . implode(',', $db->quote(array_keys(JFactory::getLanguage()->getKnownLanguages()))) . ')')
			->order('ordering');

		if ($published)
		{
			$query->where('published = 1');
		}

		$db->setQuery($query);
		$languages      = $db->loadObjectList('lang_code');
		$sourceLanguage = NenoSettings::get('source_language');

		if (!empty($languages))
		{
			foreach ($languages as $key => $language)
			{
				if (!$includeSource && $key == $sourceLanguage)
				{
					unset($languages[$key]);
				}
				else
				{
					$languages[$key]->isInstalled = self::isCompletelyInstall($language->lang_code);
				}

			}
		}

		return $languages;
	}

	/**
	 * Check if the language is completely installed
	 *
	 * @param   string $language Language tag
	 *
	 * @return bool
	 */
	public static function isCompletelyInstall($language)
	{
		$cacheId = NenoCache::getCacheId(__FUNCTION__, func_get_args());

		if (NenoCache::getCacheData($cacheId) === null)
		{
			$db    = JFactory::getDbo();
			$query = $db->getQuery(true);
			$query
				->select('1')
				->from('#__neno_tasks')
				->where(
					array (
						'task_data LIKE ' . $db->quote('%' . $language . '%'),
						'task = ' . $db->quote('language')
					)
				);
			$db->setQuery($query);

			NenoCache::setCacheData($cacheId, $db->loadResult() != 1);
		}

		return NenoCache::getCacheData($cacheId);

	}

	/**
	 * Check if a particular language has errors
	 *
	 * @param   array $language Language data
	 *
	 * @return array
	 */
	public static function getLanguageErrors(array $language)
	{
		$errors = array ();

		if (self::isLanguageFileOutOfDate($language['lang_code']))
		{
			$errors[] = JLayoutHelper::render(
				'fixitbutton',
				array (
					'message'  => JText::sprintf('COM_NENO_ERRORS_LANGUAGE_OUT_OF_DATE', $language['title']),
					'language' => $language['lang_code'],
					'issue'    => 'language_file_out_of_date'
				),
				JPATH_NENO_LAYOUTS
			);
		}

		if (!self::hasContentCreated($language['lang_code']))
		{
			$errors[] = JLayoutHelper::render(
				'fixitbutton',
				array (
					'message'  => JText::sprintf('COM_NENO_ERRORS_LANGUAGE_DOES_NOT_CONTENT_ROW', $language['title']),
					'language' => $language['lang_code'],
					'issue'    => 'content_missing'
				),
				JPATH_NENO_LAYOUTS
			);
		}

		$contentCounter = self::contentCountInOtherLanguages($language['lang_code']);

		if ($contentCounter !== 0)
		{
			$errors[] = JLayoutHelper::render(
				'fixitbutton',
				array (
					'message'  => JText::sprintf('COM_NENO_ERRORS_CONTENT_FOUND_IN_JOOMLA_TABLES', $language['title']),
					'language' => $language['lang_code'],
					'issue'    => 'content_out_of_neno'
				),
				JPATH_NENO_LAYOUTS
			);
		}

		return $errors;
	}

	/**
	 * Check if a language file is out of date
	 *
	 * @param   string $languageTag Language Tag
	 *
	 * @return bool
	 */
	public static function isLanguageFileOutOfDate($languageTag)
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
			->select(
				array (
					'u.version',
					'e.manifest_cache'
				)
			)
			->from('#__extensions AS e')
			->innerJoin('#__updates AS u ON u.element = e.element')
			->where('e.element = ' . $db->quote('pkg_' . $languageTag));

		$db->setQuery($query);
		$extensionData = $db->loadAssoc();

		if (!empty($extensionData))
		{
			$manifestCacheData = json_decode($extensionData['manifest_cache'], true);

			return version_compare($extensionData['version'], $manifestCacheData['version']) == 1;
		}

		return false;
	}

	/**
	 * Check if the language has a row created into the languages table.
	 *
	 * @param   string $languageTag Language tag
	 *
	 * @return bool
	 */
	public static function hasContentCreated($languageTag)
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
			->select('1')
			->from('#__languages')
			->where('lang_code = ' . $db->quote($languageTag));

		$db->setQuery($query);

		return $db->loadResult() == 1;
	}

	/**
	 * Checks if there are content in other languages
	 *
	 * @param   string $language Language to filter the content
	 *
	 * @return int
	 */
	public static function contentCountInOtherLanguages($language = null)
	{
		$db              = JFactory::getDbo();
		$query           = $db->getQuery(true);
		$defaultLanguage = NenoSettings::get('source_language');
		$content         = 0;

		if ($language !== $defaultLanguage)
		{
			$joomlaTablesUsingLanguageField = array (
				'#__banners',
				'#__categories',
				'#__contact_details',
				'#__content',
				'#__finder_links',
				'#__finder_terms',
				'#__finder_terms_common',
				'#__finder_tokens',
				'#__finder_tokens_aggregate',
				'#__newsfeeds',
				'#__tags'
			);

			$unionQueries = array ();
			$query->select('COUNT(*) AS counter');

			if ($language === null)
			{
				$query->where('language <> ' . $db->quote($defaultLanguage));
			}
			else
			{
				$query->where('language = ' . $db->quote($language));
			}

			foreach ($joomlaTablesUsingLanguageField as $joomlaTableUsingLanguageField)
			{
				$query
					->clear('from')
					->from($joomlaTableUsingLanguageField);
				$unionQueries[] = (string) $query;
			}

			$query
				->clear()
				->select('SUM(a.counter)')
				->from('((' . implode(') UNION (', $unionQueries) . ')) AS a');

			$db->setQuery($query);

			$content = (int) $db->loadResult();
		}

		return $content;
	}

	/**
	 * Deleting language
	 *
	 * @param   string $languageTag Language tag
	 *
	 * @return bool True on success
	 */
	public static function deleteLanguage($languageTag)
	{
		/* @var $db NenoDatabaseDriverMysqlx */
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		// Delete all the translations
		$query
			->delete('#__neno_content_element_translations')
			->where('language = ' . $db->quote($languageTag));
		$db->setQuery($query);
		$db->execute();

		// Delete module
		$query
			->clear()
			->delete('#__modules')
			->where(
				array (
					'language = ' . $db->quote($languageTag),
					'module = ' . $db->quote('mod_menu')
				)
			);
		$db->setQuery($query);
		$db->execute();

		// Delete menu items
		$query
			->clear()
			->delete('#__menu')
			->where(
				array (
					'language = ' . $db->quote($languageTag),
					'client_id = 0'
				)
			);
		$db->setQuery($query);
		$db->execute();

		// Delete menu type
		$query
			->clear()
			->delete('#__menu_types')
			->where('menutype NOT IN (SELECT menutype FROM #__menu)');
		$db->setQuery($query);
		$db->execute();

		// Delete associations
		$query
			->clear()
			->delete('#__associations')
			->where(
				array (
					'id NOT IN (SELECT id FROM #__menu )',
					'context = ' . $db->quote('com_menus.item')
				)
			);
		$db->setQuery($query);
		$db->execute();

		// Delete content
		$query
			->clear()
			->delete('#__languages')
			->where('lang_code = ' . $db->quote($languageTag));
		$db->setQuery($query);
		$db->execute();

		// Drop all the shadow tables
		$shadowTables = preg_grep('/' . preg_quote($db->getPrefix() . '_' . $db->cleanLanguageTag($languageTag)) . '/', $db->getTableList());

		foreach ($shadowTables as $shadowTable)
		{
			$db->dropTable($shadowTable);
		}

		// Delete extension(s)
		$installer = JInstaller::getInstance();

		$query
			->clear()
			->select(
				array (
					'extension_id',
					'type'
				)
			)
			->from('#__extensions')
			->where('element LIKE ' . $db->quote('%' . $languageTag));

		$db->setQuery($query);
		$extensions = $db->loadAssocList();

		foreach ($extensions as $extension)
		{
			$installer->uninstall($extension['type'], $extension['extension_id']);
		}

		return true;
	}

	/**
	 * Fix language issue
	 *
	 * @param   string $language Language
	 * @param   string $issue    Issue
	 *
	 * @return bool
	 */
	public static function fixLanguageIssues($language, $issue)
	{
		switch ($issue)
		{
			case 'content_missing':
				if (!self::hasContentCreated($language))
				{
					return self::createContentRow($language);
				}

				return true;

				break;
			case 'language_file_out_of_date':

				// Delete update for this language
				$db    = JFactory::getDbo();
				$query = $db->getQuery(true);
				$query
					->delete('#__updates')
					->where('element LIKE ' . $db->quote('%' . $language));
				$db->setQuery($query);
				$db->execute();

				// Find update for this language
				$languages = self::findLanguages();

				foreach ($languages as $updateLanguage)
				{
					if ($updateLanguage['iso'] == $language)
					{
						return self::installLanguage($updateLanguage['update_id']);
						break;
					}
				}
				break;
			case 'content_out_of_neno':
				return self::moveContentIntoShadowTables($language);
				break;
		}

		return false;
	}

	/**
	 * Create content row
	 *
	 * @param   string $jiso           Joomla ISO
	 * @param   mixed  $languageName   Language name
	 * @param   bool   $publishContent Publish content
	 *
	 * @return bool
	 */
	public static function createContentRow($jiso, $languageName = null, $publishContent = true)
	{
		JLoader::register('LanguagesModelLanguage', JPATH_ADMINISTRATOR . '/components/com_languages/models/language.php');
		/* @var $languageModel LanguagesModelLanguage */
		$languageModel = JModelLegacy::getInstance('Language', 'LanguagesModel');
		$icon          = self::getLanguageSupportedIcon($jiso);

		if (!is_string($languageName))
		{
			$db    = JFactory::getDbo();
			$query = $db->getQuery(true);

			$languageDescFile = JPATH_BASE . '/language/' . $jiso . '/' . $jiso . '.xml';

			if (file_exists($languageDescFile))
			{
				$xml          = simplexml_load_file($languageDescFile);
				$languageName = (string) $xml->name;
			}
			else
			{
				$query
					->select('name')
					->from('#__extensions')
					->where('element = ' . $db->quote($jiso));
				$db->setQuery($query);
				$languageName = $db->loadResult();
			}


			if (empty($languageName))
			{
				$query
					->clear('where')
					->where('element = ' . $db->quote('pkg_' . $jiso));

				$db->setQuery($query);
				$languageName = $db->loadResult();
			}
		}

		// Create content
		$data = array (
			'lang_code'    => $jiso,
			'title'        => $languageName,
			'title_native' => $languageName,
			'sef'          => self::getSef($jiso),
			'image'        => ($icon !== false) ? $icon : '',
			'published'    => $publishContent
		);

		return $languageModel->save($data);
	}

	/**
	 * Get language JISO
	 *
	 * @param   string $jiso Joomla ISO
	 *
	 * @return string|bool
	 */
	protected static function getLanguageSupportedIcon($jiso)
	{
		$iconName = strtolower(str_replace('-', '_', $jiso));
		$iconPath = JPATH_ROOT . '/media/mod_languages/images/' . $iconName . '.gif';

		if (!file_exists($iconPath))
		{
			$iconName = explode('_', strtolower(str_replace('-', '_', $jiso)));
			$iconPath = JPATH_ROOT . '/media/mod_languages/images/' . $iconName[0] . '.gif';

			if (!file_exists($iconPath))
			{
				return false;
			}
			else
			{
				$iconName = $iconName[0];
			}
		}

		return $iconName;
	}

	/**
	 * Get SEF prefix for a particular language
	 *
	 * @param   string $jiso Joomla ISO
	 *
	 * @return string
	 */
	public static function getSef($jiso)
	{
		$jisoParts = explode('-', $jiso);
		$sef       = $jisoParts[0];
		$db        = JFactory::getDbo();
		$query     = $db->getQuery(true);
		$query
			->select('1')
			->from('#__languages')
			->where('sef = ' . $db->quote($sef));

		$db->setQuery($query);
		$exists = $db->loadResult() == 1;

		if ($exists)
		{
			$sef = strtolower(str_replace('-', '_', $jiso));
		}

		return $sef;
	}

	/**
	 * Get a list of languages
	 *
	 * @param   bool $allSupported All the languages supported by Joomla
	 *
	 * @return array
	 */
	public static function findLanguages($allSupported = false)
	{
		$enGbExtensionId = self::getEnGbExtensionId();
		NenoLog::log('en-GB Extension ID ' . $enGbExtensionId);
		$languagesFound = array ();
		$db             = JFactory::getDbo();
		$query          = $db->getQuery(true);

		if (!empty($enGbExtensionId))
		{
			// Let's enable it if it's disabled
			$query
				->select('a.update_site_id')
				->from('#__update_sites AS a')
				->innerJoin('#__update_sites_extensions AS b ON a.update_site_id = b.update_site_id')
				->where('b.extension_id = ' . (int) $enGbExtensionId);
			$db->setQuery($query);
			$updateId = $db->loadResult();

			if (!empty($updateId))
			{
				$query
					->clear()
					->update('#__update_sites')
					->set('enabled = 1')
					->where('update_site_id = ' . (int) $updateId);
				$db->setQuery($query);
				$db->execute();
			}

			// Find updates for languages
			$updater = JUpdater::getInstance();
			$updater->findUpdates($enGbExtensionId);
			$updateSiteId = self::getLanguagesUpdateSite($enGbExtensionId);
			NenoLog::log('UpdateSiteID: ' . $updateSiteId);
			$updates = self::getUpdates($updateSiteId);
			NenoLog::log('Updates: ' . json_encode($updateSiteId));
			$languagesFound = $updates;
		}

		if ($allSupported)
		{
			$query
				->clear()
				->select(
					array (
						'DISTINCT element AS iso',
						'name'
					)
				)
				->from('#__extensions')
				->where('type = ' . $db->quote('language'))
				->group('element');
			$db->setQuery($query);
			$languagesFound = array_merge($db->loadAssocList(), $languagesFound);
		}

		return $languagesFound;
	}

	/**
	 * Get the extension_id of the en-GB package
	 *
	 * @return int
	 */
	protected static function getEnGbExtensionId()
	{
		$db       = JFactory::getDbo();
		$extQuery = $db->getQuery(true);
		$extType  = 'language';
		$extElem  = 'en-GB';

		$extQuery
			->select($db->quoteName('extension_id'))
			->from($db->quoteName('#__extensions'))
			->where($db->quoteName('type') . ' = ' . $db->quote($extType))
			->where($db->quoteName('element') . ' = ' . $db->quote($extElem))
			->where($db->quoteName('client_id') . ' = 0');

		$db->setQuery($extQuery);

		return (int) $db->loadResult();
	}

	/**
	 * Get update site for languages
	 *
	 * @param   int $enGbExtensionId Extension Id of en-GB package
	 *
	 * @return int
	 */
	protected static function getLanguagesUpdateSite($enGbExtensionId)
	{
		$db        = JFactory::getDbo();
		$siteQuery = $db->getQuery(true);

		$siteQuery
			->select($db->quoteName('update_site_id'))
			->from($db->quoteName('#__update_sites_extensions'))
			->where($db->quoteName('extension_id') . ' = ' . $enGbExtensionId);

		$db->setQuery($siteQuery);

		return (int) $db->loadResult();
	}

	/**
	 * Get updates from a particular update site
	 *
	 * @param   int $updateSiteId Update Site Id
	 *
	 * @return array
	 */
	protected static function getUpdates($updateSiteId)
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
			->select(
				array (
					'DISTINCT REPLACE(element, \'pkg_\', \'\') AS iso',
					'u.*'
				)
			)
			->from('#__updates AS u')
			->where(
				array ('u.update_site_id = ' . (int) $updateSiteId),
				'REPLACE(element, \'pkg_\', \'\') NOT IN(' . implode(',', $db->quote(array_keys(JFactory::getLanguage()->getKnownLanguages()))) . ')'
			)
			->order('name')
			->group('u.element');

		$db->setQuery($query);

		return $db->loadAssocList();
	}

	/**
	 * Installs a language and create necessary data.
	 *
	 * @param   integer $languageId     Language id
	 * @param   bool    $publishContent Publish language content
	 *
	 * @return bool
	 */
	public static function installLanguage($languageId, $publishContent = true)
	{
		// Loading language
		$language = JFactory::getLanguage();
		$language->load('com_installer');

		$languageData = self::getLanguageData($languageId);
		$jiso         = str_replace('pkg_', '', $languageData['element']);

		// Registering some classes
		JLoader::register('InstallerModelLanguages', JPATH_ADMINISTRATOR . '/components/com_installer/models/languages.php');
		JLoader::register('LanguagesModelLanguage', JPATH_ADMINISTRATOR . '/components/com_languages/models/language.php');

		/* @var $languagesInstallerModel InstallerModelLanguages */
		$languagesInstallerModel = JModelLegacy::getInstance('Languages', 'InstallerModel');

		// Install language
		$languagesInstallerModel->install(array ($languageId));

		if (self::isLanguageInstalled($jiso) && !self::hasContentCreated($languageData['element']))
		{
			// Assign translation methods to that language
			$db    = JFactory::getDbo();
			$query = $db->getQuery(true);
			$i     = 1;

			$query
				->insert('#__neno_content_language_defaults')
				->columns(
					array (
						'lang',
						'translation_method_id',
						'ordering'
					)
				);

			while (($translationMethod = NenoSettings::get('translation_method_' . $i)) !== null)
			{
				$query->values($db->quote($jiso) . ', ' . $db->quote($translationMethod) . ',' . $db->quote($i));
				$i++;
			}

			$db->setQuery($query);
			$db->execute();

			return self::createContentRow($jiso, $languageData, $publishContent);
		}

		return self::isLanguageInstalled($jiso);
	}

	/**
	 * Get Language data
	 *
	 * @param   int $updateId Update id
	 *
	 * @return array
	 */
	public static function getLanguageData($updateId)
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
			->select(
				array (
					'*',
					'REPLACE(element, \'pkg_\', \'\') AS iso'
				)
			)
			->from('#__updates')
			->where('update_id = ' . (int) $updateId);

		$db->setQuery($query);

		return $db->loadAssoc();
	}

	/**
	 * Check if a language package has been installed.
	 *
	 * @param   string $jiso Joomla language ISO
	 *
	 * @return bool
	 */
	protected static function isLanguageInstalled($jiso)
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
			->select('1')
			->from('#__extensions')
			->where(
				array (
					'type = ' . $db->quote('language'),
					'element = ' . $db->quote($jiso)
				)
			);

		$db->setQuery($query);

		return $db->loadResult() == 1;
	}

	/**
	 * Move content to shadow tables
	 *
	 * @param   string $languageTag Language tag
	 *
	 * @return bool
	 */
	public static function moveContentIntoShadowTables($languageTag)
	{
		/* @var $db NenoDatabaseDriverMysqlx */
		$db = JFactory::getDbo();

		$joomlaTablesUsingLanguageField = array (
			'#__banners',
			'#__categories',
			'#__contact_details',
			'#__content',
			'#__finder_links',
			'#__finder_terms',
			'#__finder_terms_common',
			'#__finder_tokens',
			'#__finder_tokens_aggregate',
			'#__newsfeeds',
			'#__tags'
		);

		foreach ($joomlaTablesUsingLanguageField as $joomlaTableUsingLanguageField)
		{
			$query = 'REPLACE INTO ' . $db->generateShadowTableName($joomlaTableUsingLanguageField, $languageTag) . ' SELECT * FROM ' . $joomlaTableUsingLanguageField . ' WHERE language = ' . $db->quote($languageTag);
			$db->setQuery($query);
			$db->execute();

			$query = 'DELETE FROM ' . $joomlaTableUsingLanguageField . ' WHERE language = ' . $db->quote($languageTag);
			$db->setQuery($query);
			$db->execute();
		}

		return true;
	}

	/**
	 * Get default translation methods
	 *
	 * @return array
	 */
	public static function getDefaultTranslationMethods()
	{
		/* @var $db NenoDatabaseDriverMysqlx */
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
			->select('tm.*')
			->from('#__neno_settings AS s')
			->innerJoin('#__neno_translation_methods AS tm ON tm.id = s.setting_value')
			->where('setting_key LIKE ' . $db->quote('translation_method_%'))
			->order('setting_key ASC');

		$db->setQuery($query);
		$translation_methods_selected = $db->loadObjectList();

		return $translation_methods_selected;
	}

	/**
	 * Get language flag
	 *
	 * @param   string $languageTag Language tag
	 *
	 * @return string
	 */
	public static function getLanguageImage($languageTag)
	{
		$cleanLanguageTag = str_replace('-', '_', strtolower($languageTag));
		$image            = $cleanLanguageTag;

		if (!file_exists(JPATH_ROOT . '/media/mod_languages/images/' . $cleanLanguageTag . '.gif'))
		{
			$cleanLanguageTagParts = explode('_', $cleanLanguageTag);
			$image                 = $cleanLanguageTagParts[0];
		}

		return $image;
	}

	/**
	 * Get language flag
	 *
	 * @param   string $languageTag Language tag
	 *
	 * @return bool
	 */
	public static function isLanguagePublished($languageTag)
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
			->select('published')
			->from('#__languages')
			->where('lang_code = ' . $db->quote($languageTag));

		$db->setQuery($query);
		$published = $db->loadResult();

		return !empty($published);
	}

	/**
	 * Highlight html tags on a text
	 *
	 * @param   string $text String with HTML code already encoded with HTML entities
	 *
	 * @return string
	 */
	public static function highlightHTMLTags($text)
	{
		$text = preg_replace("/(&lt;[\s\S]+?&gt;)/i", '<span class="highlighted-tag">${1}</span>', $text);

		return $text;
	}

	/**
	 * Get language default translation methods
	 *
	 * @param   string $languageTag Language tag
	 * @param   int    $ordering    Ordering
	 *
	 * @return array
	 */
	public static function getLanguageDefault($languageTag, $ordering = 0)
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
			->select(
				array (
					'DISTINCT tm.*',
					'(ordering - 1) AS ordering'
				)
			)
			->from('#__neno_content_language_defaults AS ld')
			->innerJoin('#__neno_translation_methods AS tm ON tm.id = ld.translation_method_id')
			->where(
				array (
					'lang = ' . $db->quote($languageTag),
					'ld.ordering > ' . $ordering
				)
			);

		$db->setQuery($query);
		$translationMethods = $db->loadObjectList('ordering');

		return $translationMethods;
	}

	/**
	 * Get the working language for the current user
	 * The value is stored in #__user_profiles
	 *
	 * @return string 'eb-GB' or 'de-DE'
	 */
	public static function getWorkingLanguage()
	{
		$app = JFactory::getApplication();

		if ($app->getUserState('com_neno.working_language') === null)
		{
			$userId = JFactory::getUser()->id;

			$db = JFactory::getDbo();

			$query = $db->getQuery(true);

			$query
				->select('profile_value')
				->from('#__user_profiles')
				->where(
					array (
						'user_id = ' . intval($userId),
						'profile_key = ' . $db->quote('neno_working_language')
					)
				);

			$db->setQuery($query);
			$lang = $db->loadResult();

			$app->setUserState('com_neno.working_language', $lang);
		}

		return $app->getUserState('com_neno.working_language');
	}

	/**
	 * Save INI file
	 *
	 * @param   string $filename filename
	 * @param   array  $strings  Strings to save
	 *
	 * @return bool
	 */
	public static function saveIniFile($filename, array $strings)
	{
		$res = array ();

		// Unify strings
		$strings = self::unifiedLanguageStrings($strings, false);

		foreach ($strings as $key => $val)
		{
			if (is_array($val))
			{
				$res[] = "[$key]";

				foreach ($val as $stringKey => $stringValue)
				{
					$res[] = "$stringKey = " . (is_numeric($stringValue) ? $stringValue : '"' . $stringValue . '"');
				}
			}
			else
			{
				$res[] = "$key = " . (is_numeric($val) ? $val : '"' . $val . '"');
			}
		}

		if ($fp = fopen($filename, 'w'))
		{
			$startTime = microtime(true);
			$canWrite  = flock($fp, LOCK_EX);

			while ((!$canWrite) and ((microtime(true) - $startTime) < 5))
			{
				// If lock not obtained sleep for 0 - 100 milliseconds, to avoid collision and CPU load
				if (!$canWrite)
				{
					usleep(round(rand(0, 100) * 1000));
				}

				$canWrite = flock($fp, LOCK_EX);
			}

			// File was locked so now we can store information
			if ($canWrite)
			{
				fwrite($fp, implode("\r\n", $res));
				flock($fp, LOCK_UN);
			}

			fclose($fp);

			return true;
		}

		return false;
	}

	/**
	 * Unified language strings
	 *
	 * @param array $strings language strings
	 * @param bool  $read    which are the source of those strings
	 *
	 * @return array
	 */
	public static function unifiedLanguageStrings($strings, $read = true)
	{
		if ($read)
		{
			$strings = self::unifyLanguageStringsRead($strings);
		}
		else
		{
			$strings = self::unifyLanguageStringsWrite($strings);
		}

		return $strings;
	}

	/**
	 * Unify strings from a ini file
	 *
	 * @param array $strings Strings
	 *
	 * @return array
	 */
	protected static function unifyLanguageStringsRead($strings)
	{
		foreach ($strings as $key => $string)
		{
			$strings[$key] = str_replace('_QQ_', '"', $string);
		}

		return $strings;
	}

	/**
	 * Unify strings to a ini file
	 *
	 * @param array $strings Strings
	 *
	 * @return array
	 */
	protected static function unifyLanguageStringsWrite($strings)
	{
		foreach ($strings as $key => $string)
		{
			$strings[$key] = str_replace('"', '_QQ_', $string);
		}

		return $strings;
	}

	/**
	 * Get translations dropdown
	 *
	 * @return string
	 */
	public static function getTranslatorsSelect()
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query
			->clear()
			->select(
				array (
					'translator_name AS value',
					'translator_name AS text',
				)
			)
			->from('#__neno_machine_translation_apis');
		$db->setQuery($query);
		$values = $db->loadObjectList();

		return JHtml::_('select.genericlist', $values, 'translator', null, 'value', 'text', null, false, true);
	}

	/**
	 * Generate filters drop down
	 *
	 * @param   int    $fieldId  Field id
	 * @param   string $selected Filter selected
	 *
	 * @return string
	 */
	public static function generateFilterDropDown($fieldId, $selected)
	{
		$filters = array (
			'INT',
			'UNIT',
			'FLOAT',
			'BOOL',
			'WORD',
			'ALNUM',
			'CMD',
			'STRING',
			'HTML',
			'ARRAY',
			'TRIM',
			'PATH',
			'USERNAME',
			'RAW'
		);

		return JLayoutHelper::render('dropdownbutton', array ('filters' => $filters, 'selected' => $selected, 'fieldId' => $fieldId), JPATH_NENO_LAYOUTS);
	}

	/**
	 * Render tooltip for filters
	 *
	 * @return string
	 */
	public static function renderFilterHelperText()
	{
		echo htmlentities(JLayoutHelper::render('filtertooltip', null, JPATH_NENO_LAYOUTS));
	}

	/**
	 * Get translation method
	 *
	 * @param int $id Translation method Id
	 *
	 * @return stdClass|null
	 */
	public static function getTranslationMethodById($id)
	{
		$cacheId = NenoCache::getCacheId(__FUNCTION__, func_get_args());

		if (NenoCache::getCacheData($cacheId) === null)
		{
			$db    = JFactory::getDbo();
			$query = $db->getQuery(true);

			$query
				->select('*')
				->from('#__neno_translation_methods')
				->where('id = ' . (int) $id);

			$db->setQuery($query);

			NenoCache::setCacheData($cacheId, $db->loadObject());
		}

		return NenoCache::getCacheData($cacheId);
	}

	public static function getTranslationMethodsByTableId($tableId)
	{
		$cacheId = NenoCache::getCacheId(__FUNCTION__, func_get_args());

		if (NenoCache::getCacheData($cacheId) === null)
		{
			/* @var $db NenoDatabaseDriverMysqlx */
			$db    = JFactory::getDbo();
			$query = $db->getQuery(true);
			$query
				->select(
					array (
						'gtm.lang',
						'gtm.translation_method_id',
					)
				)
				->from('#__neno_content_element_tables AS t')
				->innerJoin('#__neno_content_element_groups AS g ON t.group_id = g.id')
				->innerJoin('#__neno_content_element_groups_x_translation_methods AS gtm ON gtm.group_id = g.id')
				->where('t.id = ' . $tableId);
			$db->setQuery($query);
			$translationmethods = $db->loadObjectListMultiIndex('lang');

			NenoCache::setCacheData($cacheId, $translationmethods);
		}

		return NenoCache::getCacheData($cacheId);
	}

	/**
	 * Split HTML into p tags with content in it
	 *
	 * @param $string
	 *
	 * @return string
	 */
	public static function splitHtmlText($string)
	{
		$result = self::getStructure($string);

		if (is_array($result))
		{
			return $result[1];
		}

		return $result;
	}

	/**
	 * Get the structure of a HTML text
	 *
	 * @param   string $string        HTML string
	 * @param   bool   $returnStrings If the content strings should be returned too
	 *
	 * @return string|array
	 */
	protected static function getStructure($string, $returnStrings = true)
	{
		$dom = self::createDomDocument($string);

		if ($dom === false)
		{
			return $string;
		}

		$strings   = self::recursiveDomExplorer($dom->documentElement);
		$structure = self::fromDomDocumentToString($dom);

		if ($returnStrings)
		{
			$convertedString = '';

			foreach ($strings as $key => $string)
			{
				$convertedString .= '<p id="' . $key . '">' . $string . '</p>';
			}

			$structure = array ($structure, $convertedString);
		}

		return $structure;
	}

	/**
	 * Creates DOMDocument class if it's a string, if it's not, return false
	 *
	 * @param $string
	 *
	 * @return bool|DOMDocument
	 */
	protected static function createDomDocument($string)
	{
		if (self::isHtml($string))
		{
			$dom = new DOMDocument;
			$dom->loadHTML($string);

			return $dom;
		}

		return false;
	}

	/**
	 * Check if the string is a html string
	 *
	 * @param   string $string String to check
	 *
	 * @return bool
	 */
	protected static function isHtml($string)
	{
		return preg_match('/<[^<]+>/', $string) != 0;
	}

	/**
	 * Recursive function that goes through DOM of a HTML string replacing content with special tags
	 *
	 * @param   DOMElement $domElement DOM element
	 * @param   int        $index      Index
	 *
	 * @return array
	 */
	protected static function recursiveDomExplorer(DOMElement $domElement, &$index = 1)
	{
		$humanReadableAttributes = array ('alt', 'title', 'summary');
		$strings                 = array ();

		/* @var $node DomElement */
		foreach ($domElement->childNodes as $node)
		{
			if ($node->nodeName == '#text')
			{
				$text            = trim($node->nodeValue);
				$strings[$index] = $text;
				$node->nodeValue = str_replace($text, '[{|' . $index . '|}]}', $node->nodeValue);
				$index++;
			}
			else
			{
				foreach ($humanReadableAttributes as $humanAttribute)
				{
					if ($node->hasAttribute($humanAttribute))
					{
						$attribute       = $node->getAttribute($humanAttribute);
						$text            = trim($attribute);
						$strings[$index] = $text;
						$node->setAttribute($humanAttribute, str_replace($text, '[{|' . $index . '|}]}', $attribute));
						$index++;
					}
				}
			}

			// If the node has children, let's go through them
			if ($node->hasChildNodes())
			{
				$strings = $strings + self::recursiveDomExplorer($node, $index);
			}
		}

		return $strings;
	}

	/**
	 * Convert from DOMDocument to string, It strips html and body tags because database content won't have those tags.
	 *
	 * @param   DOMDocument $document Document
	 *
	 * @return bool|string
	 */
	protected static function fromDomDocumentToString(DOMDocument $document)
	{
		$matches = null;

		// Get content between body tags (DOMDocument class add it)
		if (preg_match('@<body>(.+)<\/body>@', $document->saveHTML(), $matches) != 0)
		{
			return $matches[1];
		}

		return false;
	}

	/**
	 * Replaces translations in a HTML text
	 *
	 * @param string $string      Original HTML text
	 * @param string $translation Text received by the translator
	 *
	 * @return string
	 */
	public static function replaceTranslationsInHtmlTag($string, $translation)
	{
		$structure = self::getStructure($string, false);

		$document = new DOMDocument;
		$document->loadHTML($translation);

		$pTags = $document->getElementsByTagName('p');

		/* @var $pTag DomElement */
		foreach ($pTags as $pTag)
		{
			$structure = str_replace('[{|' . $pTag->getAttribute('id') . '|}]', $pTag->nodeValue, $structure);
		}

		return $structure;
	}

	/**
	 * Get language translator comment
	 *
	 * @param    string $languageTag Language tag
	 *
	 * @return string|null
	 */
	public static function getLanguageTranslatorComment($languageTag)
	{
		$db = JFactory::getDbo();

		$query = $db->getQuery(true);

		$query
			->select('comment')
			->from('#__neno_language_external_translators_comments')
			->where('language = ' . $db->quote($languageTag));

		$db->setQuery($query);

		return $db->loadResult();
	}

	/**
	 * Search for a string into a language file based on its constant
	 *
	 * @param $languageFile
	 * @param $constant
	 *
	 * @return bool
	 */
	public static function existsStringInsideOfLanguageFile($languageFile, $constant)
	{
		$strings = self::readLanguageFile($languageFile);

		return isset($strings[$constant]) ? $strings[$constant] : false;
	}

	/**
	 * Read language file
	 *
	 * @param string $filename
	 *
	 * @return array
	 */
	public static function readLanguageFile($filename)
	{
		return NenoHelper::unifiedLanguageStrings(parse_ini_file($filename));
	}
}
