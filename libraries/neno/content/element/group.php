<?php

/**
 * @package     Neno
 * @subpackage  ContentElement
 *
 * @copyright   Copyright (c) 2014 Jensen Technologies S.L. All rights reserved
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;

/**
 * Class NenoContentElementGroup
 *
 * @since  1.0
 */
class NenoContentElementGroup extends NenoContentElement implements NenoContentElementInterface
{
	/**
	 * @var array|null
	 */
	public $assignedTranslationMethods;

	/**
	 * @var int
	 */
	public $elementCount;

	/**
	 * @var stdClass
	 */
	public $wordCount;

	/**
	 * @var array|null
	 */
	public $languageFiles;

	/**
	 * @var
	 */
	public $extensions;

	/**
	 * @var string
	 */
	protected $groupName;

	/**
	 * @var array|null
	 */
	protected $tables;

	/**
	 * @var bool
	 */
	protected $otherGroup;

	/**
	 * {@inheritdoc}
	 *
	 * @param   mixed $data          Group data
	 * @param   bool  $loadExtraData Load extra data flag
	 */
	public function __construct($data, $loadExtraData = true)
	{
		parent::__construct($data);

		$this->tables                     = null;
		$this->languageFiles              = null;
		$this->assignedTranslationMethods = array ();
		$this->extensions                 = array ();
		$this->elementCount               = null;
		$this->wordCount                  = null;

		// Only search for the statistics for existing groups
		if (!$this->isNew())
		{
			$this->getExtensionIdList();
			$this->getElementCount();
			$this->calculateExtraData();

			if ($loadExtraData)
			{
				$this->getWordCount();
			}
		}
	}

	/**
	 * Get extension list
	 *
	 * @return void
	 */
	protected function getExtensionIdList()
	{
		/* @var $db NenoDatabaseDriverMysqlx */
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
			->select('extension_id')
			->from('#__neno_content_element_groups_x_extensions')
			->where('group_id = ' . $this->id);

		$db->setQuery($query);
		$this->extensions = $db->loadArray();
	}

	/**
	 * Get how many tables this group has
	 *
	 * @return int
	 */
	public function getElementCount()
	{
		if ($this->elementCount === null)
		{
			$tableCounter = NenoContentElementTable::load(
				array (
					'_select'  => array ('COUNT(*) as counter'),
					'group_id' => $this->getId()
				)
			);

			$languageFileCounter = NenoContentElementLanguageFile::load(
				array (
					'_select'  => array ('COUNT(*) as counter'),
					'group_id' => $this->getId()
				)
			);

			$this->elementCount = (int) $tableCounter['counter'] + (int) $languageFileCounter['counter'];
		}

		return $this->elementCount;
	}

	/**
	 * Calculate language string statistics
	 *
	 * @return void
	 */
	public function calculateExtraData()
	{
		$this->assignedTranslationMethods = array ();

		/* @var $db NenoDatabaseDriverMysqlx */
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
			->select('DISTINCT tm.*')
			->from('#__neno_content_element_groups_x_translation_methods AS gt')
			->innerJoin('#__neno_translation_methods AS tm ON gt.translation_method_id = tm.id')
			->where(
				array (
					'group_id = ' . $this->id,
					'lang = ' . $db->quote(NenoHelper::getWorkingLanguage())
				)
			)
			->group('ordering')
			->order('ordering ASC');

		$db->setQuery($query);
		$this->assignedTranslationMethods = $db->loadObjectList();
	}

	/**
	 * Get an object with the amount of words per state
	 *
	 * @return stdClass
	 */
	public function getWordCount()
	{
		if ($this->wordCount === null)
		{
			$this->wordCount               = new stdClass;
			$this->wordCount->total        = 0;
			$this->wordCount->untranslated = 0;
			$this->wordCount->translated   = 0;
			$this->wordCount->queued       = 0;
			$this->wordCount->changed      = 0;

			if (!empty($this->assignedTranslationMethods))
			{
				$db              = JFactory::getDbo();
				$query           = $db->getQuery(true);
				$workingLanguage = NenoHelper::getWorkingLanguage();
				$query
					->select(
						array (
							'SUM(word_counter) AS counter',
							't.state'
						)
					)
					->from($db->quoteName(NenoContentElementLanguageString::getDbTable()) . ' AS ls')
					->innerJoin($db->quoteName(NenoContentElementLanguageFile::getDbTable()) . ' AS lf ON ls.languagefile_id = lf.id')
					->innerJoin(
						$db->quoteName(NenoContentElementTranslation::getDbTable()) .
						' AS t ON t.content_id = ls.id AND t.content_type = ' .
						$db->quote('lang_string') .
						' AND t.language LIKE ' . $db->quote($workingLanguage)
					)
					->where('lf.group_id = ' . $this->getId())
					->group('t.state');

				$db->setQuery($query);
				$statistics = $db->loadAssocList('state');

				// Assign the statistics
				foreach ($statistics as $state => $data)
				{
					switch ($state)
					{
						case NenoContentElementTranslation::NOT_TRANSLATED_STATE:
							$this->wordCount->untranslated = (int) $data['counter'];
							break;
						case NenoContentElementTranslation::QUEUED_FOR_BEING_TRANSLATED_STATE:
							$this->wordCount->queued = (int) $data['counter'];
							break;
						case NenoContentElementTranslation::SOURCE_CHANGED_STATE:
							$this->wordCount->changed = (int) $data['counter'];
							break;
						case NenoContentElementTranslation::TRANSLATED_STATE:
							$this->wordCount->translated = (int) $data['counter'];
							break;
					}
				}

				$query
					->clear()
					->select(
						array (
							'SUM(word_counter) AS counter',
							'tr.state'
						)
					)
					->from('#__neno_content_element_tables AS t')
					->innerJoin('#__neno_content_element_fields AS f ON f.table_id = t.id')
					->innerJoin('#__neno_content_element_translations AS tr  ON tr.content_id = f.id AND tr.content_type = ' . $db->quote('db_string') . ' AND tr.language LIKE ' . $db->quote($workingLanguage))
					->where(
						array (
							't.group_id = ' . $this->getId(),
							't.translate = 1',
							'f.translate = 1'
						)
					)
					->group('tr.state');

				$db->setQuery($query);
				$statistics = $db->loadAssocList('state');

				// Assign the statistics
				foreach ($statistics as $state => $data)
				{
					switch ($state)
					{
						case NenoContentElementTranslation::NOT_TRANSLATED_STATE:
							$this->wordCount->untranslated = (int) $data['counter'] + $this->wordCount->untranslated;
							break;
						case NenoContentElementTranslation::QUEUED_FOR_BEING_TRANSLATED_STATE:
							$this->wordCount->queued = (int) $data['counter'] + $this->wordCount->queued;
							break;
						case NenoContentElementTranslation::SOURCE_CHANGED_STATE:
							$this->wordCount->changed = (int) $data['counter'] + $this->wordCount->changed;
							break;
						case NenoContentElementTranslation::TRANSLATED_STATE:
							$this->wordCount->translated = (int) $data['counter'] + $this->wordCount->translated;
							break;
					}
				}

				$this->wordCount->total = $this->wordCount->untranslated + $this->wordCount->queued + $this->wordCount->changed + $this->wordCount->translated;
			}
		}

		return $this->wordCount;
	}

	/**
	 * Get a group object
	 *
	 * @param   integer $groupId       Group Id
	 * @param   bool    $loadExtraData Load extra data flag
	 *
	 * @return NenoContentElementGroup
	 */
	public static function getGroup($groupId, $loadExtraData = true)
	{
		$group = self::load($groupId, $loadExtraData);

		return $group;
	}

	/**
	 * Parse a content element file.
	 *
	 * @param   string $groupName           Group name
	 * @param   array  $contentElementFiles Content element file path
	 *
	 * @return bool True on success
	 *
	 * @throws Exception
	 */
	public static function parseContentElementFiles($groupName, $contentElementFiles)
	{
		// Create an array of group data
		$groupData = array (
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
				$tableName = $tableData->getAttribute('name');

				// If the table hasn't been added yet, let's add it
				if (!NenoHelper::isTableAlreadyDiscovered($tableName))
				{
					$table = new NenoContentElementTable(
						array (
							'tableName' => $tableName,
							'translate' => 0
						)
					);

					$fields = $tableData->getElementsByTagName('field');

					/* @var $fieldData DOMElement */
					foreach ($fields as $fieldData)
					{
						$field = new NenoContentElementField(
							array (
								'fieldName' => $fieldData->getAttribute('name'),
								'translate' => intval($fieldData->getAttribute('translate')),
								'table'     => $table
							)
						);

						$table->addField($field);

						// If the field has this attribute, it means this is the primary key field of the table
						if ($fieldData->hasAttribute('referenceid'))
						{
							$table->setPrimaryKey($field->getFieldName());
						}
					}

					$group->addTable($table);
				}
			}
		}

		$tables = $group->getTables();

		// Checking if the group has tables
		if (!empty($tables))
		{
			$group->persist();
		}

		return true;
	}

	/**
	 * Add a table to the list
	 *
	 * @param   NenoContentElementTable $table Table
	 *
	 * @return $this
	 */
	public function addTable(NenoContentElementTable $table)
	{
		$this->tables[] = $table;

		return $this;
	}

	/**
	 * Get all the tables related to this group
	 *
	 * @param   bool $loadExtraData           Calculate other data
	 * @param   bool $loadTablesNotDiscovered Only loads tables that have not been discovered yet
	 * @param   bool $avoidDoNotTranslate     Don't load tables marked as Don't translate
	 *
	 * @return array
	 */
	public function getTables($loadExtraData = true, $loadTablesNotDiscovered = false, $avoidDoNotTranslate = false)
	{
		if ($this->tables === null || $loadTablesNotDiscovered)
		{
			if ($loadTablesNotDiscovered)
			{
				$this->tables = NenoHelper::getComponentTables($this, null, false);
			}
			else
			{
				$this->tables = NenoContentElementTable::load(array ('group_id' => $this->getId()), $loadExtraData);

				// If there's only one table
				if ($this->tables instanceof NenoContentElementTable)
				{
					$this->tables = array ($this->tables);
				}

				/* @var $table NenoContentElementTable */
				foreach ($this->tables as $key => $table)
				{
					if ($avoidDoNotTranslate && !$this->tables[$key]->isTranslate())
					{
						unset ($this->tables[$key]);
						continue;
					}

					$this->tables[$key]->setGroup($this);
				}
			}
		}

		return $this->tables;
	}

	/**
	 * Set all the tables related to this group
	 *
	 * @param   array $tables Tables
	 *
	 * @return $this
	 */
	public function setTables(array $tables)
	{
		$this->tables = $tables;
		$this->contentHasChanged();

		return $this;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return boolean
	 */
	public function persist()
	{
		$isNew  = $this->isNew();
		$result = parent::persist();

		// Check if the saving process has been completed successfully
		if ($result)
		{
			NenoLog::log('Group data added or modified successfully', 2);

			if (!empty($this->extensions))
			{
				$db          = JFactory::getDbo();
				$deleteQuery = $db->getQuery(true);

				$deleteQuery
					->delete('#__neno_content_element_groups_x_extensions')
					->where('group_id = ' . $this->getId());

				$db->setQuery($deleteQuery);
				$db->execute();

				$insertQuery = $db->getQuery(true);

				$insertQuery
					->clear()
					->insert('#__neno_content_element_groups_x_extensions')
					->columns(
						array (
							'extension_id',
							'group_id'
						)
					);

				foreach ($this->extensions as $extension)
				{
					$insertQuery->values((int) $extension . ',' . $this->getId());
				}

				$db->setQuery($insertQuery);
				$db->execute();
			}

			// check whether or not this group should have translation methods (For unknown groups we set them as do not translate)
			if ($isNew)
			{
				$fileFound = false;
				/* @var $table NenoContentElementTable */
				foreach ($this->tables as $table)
				{
					if (file_exists($table->getContentElementFilename()))
					{
						$fileFound = true;
						break;
					}
				}

				if (!$fileFound)
				{
					$this->assignedTranslationMethods = null;
				}
			}

			if (!empty($this->assignedTranslationMethods))
			{
				$db          = JFactory::getDbo();
				$deleteQuery = $db->getQuery(true);
				$insertQuery = $db->getQuery(true);
				$insert      = false;

				$insertQuery
					->insert('#__neno_content_element_groups_x_translation_methods')
					->columns(
						array (
							'group_id',
							'lang',
							'translation_method_id',
							'ordering'
						)
					);

				foreach ($this->assignedTranslationMethods as $translationMethod)
				{

					if (!empty($translationMethod->lang))
					{
						$deleteQuery
							->clear()
							->delete('#__neno_content_element_groups_x_translation_methods')
							->where(
								array (
									'group_id = ' . $this->id,
									'lang = ' . $db->quote($translationMethod->lang)
								)
							);

						$db->setQuery($deleteQuery);
						$db->execute();

						if (!empty($translationMethod))
						{
							$insert = true;
							$insertQuery->values(
								$this->id . ',' . $db->quote($translationMethod->lang) . ', ' . $db->quote($translationMethod->translation_method_id) . ', ' . $db->quote($translationMethod->ordering)
							);
						}
					}
				}

				if ($insert)
				{
					$db->setQuery($insertQuery);
					$db->execute();
				}
			}

			if (!empty($this->languageFiles))
			{
				/* @var $languageFile NenoContentElementLanguageFile */
				foreach ($this->languageFiles as $languageFile)
				{
					$languageFile->setGroup($this);
					$languageFile->persist();
				}
			}

			if (!empty($this->tables))
			{
				/* @var $table NenoContentElementTable */
				foreach ($this->tables as $table)
				{
					$table->setGroup($this);
					$table->persist();
				}
			}
		}

		return $result;
	}

	/**
	 * Create a NenoContentElementGroup based on the extension Id
	 *
	 * @param   integer $extensionId Extension Id
	 *
	 * @return NenoContentElementGroup
	 */
	public static function createNenoContentElementGroupByExtensionId($extensionId)
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
			->select(
				array (
					'e.extension_id',
					'e.name'
				)
			)
			->from('`#__extensions` AS e')
			->where('e.extension_id = ' . (int) $extensionId);

		$db->setQuery($query);

		$extension = $db->loadAssoc();
		$group     = new NenoContentElementGroup(
			array (
				'groupName'   => $extension['name'],
				'extensionId' => $extension['extension_id']
			)
		);

		NenoLog::log('Group created successfully', 2);

		return $group;
	}

	/**
	 * Refresh NenoContentElementGroup data
	 *
	 * @param   string|null $language Language to update. Null for none
	 *
	 * @return void
	 */
	public function refresh($language = null)
	{
		$tables = NenoHelper::getComponentTables($this);

		if (!$this->isOtherGroup())
		{
			$languageFiles = NenoHelper::getLanguageFiles($this->getGroupName());
		}

		// If there are tables, let's assign to the group
		if (!empty($tables))
		{
			$this->setTables($tables);
		}

		// If there are language strings, let's assign to the group
		if (!empty($languageFiles))
		{
			$this->setLanguageFiles($languageFiles);
		}

		// If there are tables or language strings assigned, save the group
		if (!empty($tables) || !empty($languageFiles))
		{
			$this->persist();
		}

		// Once the structure is saved, let's go through the translations.
		if (!empty($tables))
		{
			/* @var $table NenoContentElementTable */
			foreach ($tables as $table)
			{
				if ($table->isTranslate())
				{
					$fields = $table->getFields(false, true);

					/* @var $field NenoContentElementField */
					foreach ($fields as $field)
					{
						$field->persistTranslations(null, $language);
					}
				}
			}
		}

		if (!empty($languageFiles))
		{
			/* @var $languageFile NenoContentElementLanguageFile */
			foreach ($languageFiles as $languageFile)
			{
				if ($languageFile->loadStringsFromFile())
				{
					$languageStrings = $languageFile->getLanguageStrings();

					/* @var $languageString NenoContentElementLanguageString */
					foreach ($languageStrings as $languageString)
					{
						$languageString->persistTranslations($language);
					}
				}
			}
		}
	}

	/**
	 * Check if it's other group
	 *
	 * @return boolean
	 */
	public function isOtherGroup()
	{
		return $this->otherGroup;
	}

	/**
	 * @param boolean $otherGroup
	 */
	public function setOtherGroup($otherGroup)
	{
		$this->otherGroup = $otherGroup;
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
	 * Get Translation methods used.
	 *
	 * @return array
	 */
	public function getAssignedTranslationMethods()
	{
		if ($this->assignedTranslationMethods === null)
		{
			$this->calculateExtraData();
		}

		return $this->assignedTranslationMethods;
	}

	/**
	 * Set translation methods used
	 *
	 * @param   array $assignedTranslationMethods Translation methods used
	 *
	 * @return $this
	 */
	public function setAssignedTranslationMethods(array $assignedTranslationMethods)
	{
		$this->assignedTranslationMethods = $assignedTranslationMethods;

		NenoLog::log('Translation method of group changed successfully', 2);

		return $this;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return bool
	 */
	public function remove()
	{
		// Get the tables
		$tables = $this->getTables();

		/* @var $table NenoContentElementTable */
		foreach ($tables as $table)
		{
			$table->remove();
		}

		// Get language strings
		$languageStrings = $this->getLanguageFiles();

		/* @var $languageString NenoContentElementLanguageString */
		foreach ($languageStrings as $languageString)
		{
			$languageString->remove();
		}

		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query
			->delete('#__neno_content_element_groups_x_translation_methods')
			->where('group_id = ' . $this->id);
		$db->setQuery($query);
		$db->execute();

		$query
			->clear()
			->delete('#__neno_content_element_groups_x_extensions')
			->where('group_id = ' . $this->id);
		$db->setQuery($query);
		$db->execute();

		NenoLog::log('Group deleted successfully', 2);

		return parent::remove();
	}

	/**
	 * Get all the language files
	 *
	 * @return array
	 */
	public function getLanguageFiles()
	{
		if ($this->languageFiles === null)
		{
			$this->languageFiles = NenoContentElementLanguageFile::load(array ('group_id' => $this->getId()));

			if (!is_array($this->languageFiles))
			{
				$this->languageFiles = array ($this->languageFiles);
			}
		}

		return $this->languageFiles;
	}

	/**
	 * Set language strings
	 *
	 * @param   array $languageStrings Language strings
	 *
	 * @return $this
	 */
	public function setLanguageFiles(array $languageStrings)
	{
		$this->languageFiles = $languageStrings;
		$this->contentHasChanged();

		return $this;
	}

	/**
	 * Get a list of extensions linked to this group
	 *
	 * @return array
	 */
	public function getExtensions()
	{
		return $this->extensions;
	}

	/**
	 * Set a list of extensions linked to this group
	 *
	 * @param   array $extensions Extension list
	 *
	 * @return $this
	 */
	public function setExtensions(array $extensions)
	{
		$this->extensions = $extensions;

		return $this;
	}

	/**
	 * Add an extension id to the list
	 *
	 * @param   int $extensionId Extension id
	 *
	 * @return $this
	 */
	public function addExtension($extensionId)
	{
		$this->extensions[] = $extensionId;
		$this->extensions   = array_unique($this->extensions);

		return $this;
	}

	/**
	 * Generate the content for a particular language
	 *
	 * @param   string $languageTag Language tag
	 *
	 * @return bool True on success
	 */
	public function generateContentForLanguage($languageTag)
	{
		$tables = $this->getTables();

		if (!empty($tables))
		{
			/* @var $table NenoContentElementTable */
			foreach ($tables as $table)
			{
				$fields = $table->getFields(false, true);

				/* @var $field NenoContentElementField */
				foreach ($fields as $field)
				{
					$field->persistTranslations(null, $languageTag);
				}
			}
		}

		$languageFiles = $this->getLanguageFiles();

		if (!empty($languageFiles))
		{
			/* @var $languageFile NenoContentElementLanguageFile */
			foreach ($languageFiles as $languageFile)
			{
				$languageStrings = $languageFile->getLanguageStrings();

				/* @var $languageString NenoContentElementLanguageString */
				foreach ($languageStrings as $languageString)
				{
					$languageString->persistTranslations($languageTag);
				}
			}
		}

		// Assign default methods
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
			->insert('#__neno_content_element_groups_x_translation_methods')
			->columns(
				array (
					'group_id',
					'lang',
					'translation_method_id',
					'ordering'
				)
			);

		$firstTranslationMethod = NenoSettings::get('translation_method_1');
		$query->values($this->id . ',' . $db->quote($languageTag) . ', ' . $db->quote($firstTranslationMethod) . ', 1');

		$queryTranslations1 = 'INSERT INTO #__neno_content_element_translation_x_translation_methods (translation_id, translation_method_id, ordering)
							SELECT id, ' . $db->quote($firstTranslationMethod) . ',1 FROM #__neno_content_element_translations
							WHERE language = ' . $db->quote($languageTag) . ' AND state = ' . NenoContentElementTranslation::NOT_TRANSLATED_STATE;

		$secondTranslationMethod = NenoSettings::get('translation_method_2');
		$queryTranslations2      = null;

		if (!empty($secondTranslationMethod))
		{
			$query->values($this->id . ',' . $db->quote($languageTag) . ', ' . $db->quote($secondTranslationMethod) . ', 2');
			$queryTranslations2 = 'INSERT INTO #__neno_content_element_translation_x_translation_methods (translation_id, translation_method_id, ordering)
							SELECT id, ' . $db->quote($secondTranslationMethod) . ',2 FROM #__neno_content_element_translations
							WHERE language = ' . $db->quote($languageTag) . ' AND state = ' . NenoContentElementTranslation::NOT_TRANSLATED_STATE;
		}

		$db->setQuery($query);
		$db->execute();

		$db->setQuery($queryTranslations1);
		$db->execute();

		if (!empty($queryTranslations2))
		{
			$db->setQuery($queryTranslations2);
			$db->execute();
		}
	}

	/**
	 * Discover the element
	 *
	 * @return bool True on success
	 */
	public function discoverElement()
	{
		// Save the hierarchy first,
		if ($this->isNew() || NenoSettings::get('discovering_element_0') == $this->id)
		{
			NenoHelper::setSetupState(JText::sprintf('COM_NENO_INSTALLATION_MESSAGE_PARSING_GROUP', $this->groupName));
			$level = '1.1';
		}
		else
		{
			$level = '1.2';
		}

		$this->persist();

		$elementId = $this->id;

		if (empty($this->tables) && empty($this->languageFiles))
		{
			NenoHelper::setSetupState(JText::sprintf('COM_NENO_INSTALLATION_MESSAGE_CONTENT_NOT_DETECTED', $this->getGroupName()), 1, 'warning');
			$level     = 0;
			$elementId = 0;
		}

		NenoSettings::set('installation_level', $level);
		NenoSettings::set('discovering_element_0', $elementId);
	}
}
