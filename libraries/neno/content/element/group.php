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
	 * @var integer
	 */
	public $languageWordsNotTranslated;

	/**
	 * @var integer
	 */
	public $languageWordsQueuedToBeTranslated;

	/**
	 * @var integer
	 */
	public $languageWordsTranslated;

	/**
	 * @var integer
	 */
	public $languageWordsSourceHasChanged;

	/**
	 * @var array
	 */
	public $translationMethodUsed;

	/**
	 * @var int
	 */
	public $elementCount;

	/**
	 * @var stdClass
	 */
	public $wordCount;

	/**
	 * @var array
	 */
	public $languageFiles;

	/**
	 * @var string
	 */
	protected $groupName;

	/**
	 * @var array
	 */
	protected $tables;

	/**
	 * @var array
	 */
	protected $languageStrings;

	/**
	 * {@inheritdoc}
	 *
	 * @param   mixed $data Group data
	 */
	public function __construct($data)
	{
		parent::__construct($data);

		$this->tables                            = null;
		$this->languageStrings                   = null;
		$this->languageWordsNotTranslated        = null;
		$this->languageWordsQueuedToBeTranslated = null;
		$this->languageWordsSourceHasChanged     = null;
		$this->languageWordsTranslated           = null;
		$this->translationMethodUsed             = array ();
		$this->extensionId                       = array ();
		$this->elementCount                      = null;
		$this->wordCount                         = null;

		// Only search for the statistics for existing groups
		if (!$this->isNew())
		{
			$this->getWordCount();
			$this->getElementCount();
		}
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

			$db              = JFactory::getDbo();
			$query           = $db->getQuery(true);
			$workingLanguage = NenoHelper::getWorkingLanguage();

			if ($this->languageWordsNotTranslated === null
				|| $this->languageWordsQueuedToBeTranslated === null
				|| $this->languageWordsQueuedToBeTranslated === null
				|| $this->languageWordsSourceHasChanged === null
				|| $this->languageWordsTranslated === null
			)
			{
				$query
					->select(
						array (
							'SUM((LENGTH(l.string) - LENGTH(replace(l.string,\' \',\'\'))+1)) AS counter',
							't.state'
						)
					)
					->from($db->quoteName(NenoContentElementLangstring::getDbTable()) . ' AS l')
					->leftJoin(
						$db->quoteName(NenoContentElementTranslation::getDbTable()) .
						' AS t ON t.content_id = l.id AND t.content_type = ' .
						$db->quote('lang_string') .
						' AND t.language LIKE ' . $db->quote($workingLanguage)
					)
					->where('l.group_id = ' . $this->getId())
					->group('t.state');

				$db->setQuery($query);
				$statistics = $db->loadAssocList('state');

				// Assign the statistics
				foreach ($statistics as $state => $data)
				{
					switch ($state)
					{
						case NenoContentElementTranslation::NOT_TRANSLATED_STATE:
							$this->languageWordsNotTranslated = (int) $data['counter'];
							break;
						case NenoContentElementTranslation::QUEUED_FOR_BEING_TRANSLATED_STATE:
							$this->languageWordsQueuedToBeTranslated = (int) $data['counter'];
							break;
						case NenoContentElementTranslation::SOURCE_CHANGED_STATE:
							$this->languageWordsSourceHasChanged = (int) $data['counter'];
							break;
						case NenoContentElementTranslation::TRANSLATED_STATE:
							$this->languageWordsTranslated = (int) $data['counter'];
							break;
					}
				}
			}

			$query
				->clear()
				->select(
					array (
						'SUM((LENGTH(tr.string) - LENGTH(replace(tr.string,\' \',\'\'))+1)) AS counter',
						'tr.state'
					)
				)
				->from($db->quoteName(NenoContentElementTable::getDbTable(), 't'))
				->leftJoin(
					$db->quoteName(NenoContentElementField::getDbTable(), 'f') .
					' ON f.table_id = t.id'
				)
				->leftJoin(
					$db->quoteName(NenoContentElementTranslation::getDbTable(), 'tr') .
					' ON tr.content_id = f.id AND tr.content_type = ' .
					$db->quote('db_string') .
					' AND tr.language LIKE ' . $db->quote($workingLanguage)
				)
				->where('t.group_id = ' . $this->getId())
				->group('tr.state');

			$db->setQuery($query);
			$statistics = $db->loadAssocList('state');

			// Assign the statistics
			foreach ($statistics as $state => $data)
			{
				switch ($state)
				{
					case NenoContentElementTranslation::NOT_TRANSLATED_STATE:
						$this->wordCount->untranslated = (int) $data['counter'] + $this->languageWordsNotTranslated;
						break;
					case NenoContentElementTranslation::QUEUED_FOR_BEING_TRANSLATED_STATE:
						$this->wordCount->queued = (int) $data['counter'] + $this->languageWordsQueuedToBeTranslated;
						break;
					case NenoContentElementTranslation::SOURCE_CHANGED_STATE:
						$this->wordCount->changed = (int) $data['counter'] + $this->languageWordsSourceHasChanged;
						break;
					case NenoContentElementTranslation::TRANSLATED_STATE:
						$this->wordCount->translated = (int) $data['counter'] + $this->languageWordsTranslated;
						break;
				}
			}

			$this->wordCount->total = $this->wordCount->untranslated + $this->wordCount->queued + $this->wordCount->changed + $this->wordCount->translated;
		}

		return $this->wordCount;
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
			$countData = NenoContentElementTable::load(
				array (
					'_select'  => array ('COUNT(*) as counter'),
					'group_id' => $this->getId()
				)
			);

			$this->elementCount = (int) $countData['counter'];
		}

		return $this->elementCount;
	}

	/**
	 * Get a group object
	 *
	 * @param   integer $groupId Group Id
	 *
	 * @return NenoContentElementGroup
	 */
	public static function getGroup($groupId)
	{
		$group = self::load($groupId);

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
						$fieldData = array (
							'fieldName' => $fieldData->getAttribute('name'),
							'translate' => intval($fieldData->getAttribute('translate')),
							'table'     => $table
						);
						$field     = new NenoContentElementField($fieldData);

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
	 * @return array
	 */
	public function getTables()
	{
		if ($this->tables === null)
		{
			$this->tables = array ();
			$tablesInfo   = self::getElementsByParentId(NenoContentElementTable::getDbTable(), 'group_id', $this->id, true);

			foreach ($tablesInfo as $tableInfo)
			{
				$table = new NenoContentElementTable($tableInfo);
				$table->setGroup($this);
				$this->tables[] = $table;
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
		$result = parent::persist();

		// Check if the saving process has been completed successfully
		if ($result)
		{
			NenoLog::log('Group data added or modified successfully', 2);

			if (!empty($this->languageStrings))
			{
				/* @var $languageString NenoContentElementLangstring */
				foreach ($this->languageStrings as $languageString)
				{
					$languageString->setGroup($this);
					$languageString->persist();
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

		$this->setContentElementIntoCache();

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
	 * @return void
	 */
	public function refresh()
	{
		$tables          = NenoHelper::getComponentTables($this);
		$languageStrings = NenoHelper::getLanguageStrings($this->getGroupName());

		// If there are tables, let's assign to the group
		if (!empty($tables))
		{
			$this->setTables($tables);
		}

		// If there are language strings, let's assign to the group
		if (!empty($languageStrings))
		{
			$this->setLanguageStrings($languageStrings);
		}

		// If there are tables or language strings assigned, save the group
		if (!empty($tables) || !empty($languageStrings))
		{
			$this->persist();
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
	 * Get how many language strings haven't been translated
	 *
	 * @return int
	 */
	public function getLanguageWordsNotTranslated()
	{
		if ($this->languageWordsNotTranslated === null)
		{
			$this->calculateExtraData();
		}

		return $this->languageWordsNotTranslated;
	}

	/**
	 * Calculate language string statistics
	 *
	 * @return void
	 */
	public function calculateExtraData()
	{
		$this->languageWordsNotTranslated        = 0;
		$this->languageWordsQueuedToBeTranslated = 0;
		$this->languageWordsSourceHasChanged     = 0;
		$this->languageWordsTranslated           = 0;
		$this->translationMethodUsed             = array ();

		/* @var $db NenoDatabaseDriverMysqlx */
		$db              = JFactory::getDbo();
		$query           = $db->getQuery(true);
		$workingLanguage = NenoHelper::getWorkingLanguage();

		$query
			->select(
				array (
					'SUM((LENGTH(l.string) - LENGTH(replace(l.string,\' \',\'\'))+1)) AS counter',
					't.state'
				)
			)
			->from($db->quoteName(NenoContentElementLangstring::getDbTable()) . ' AS l')
			->leftJoin(
				$db->quoteName(NenoContentElementTranslation::getDbTable()) .
				' AS t ON t.content_id = l.id AND t.content_type = ' .
				$db->quote('lang_string') .
				' AND t.language LIKE ' . $db->quote($workingLanguage)
			)
			->where('l.group_id = ' . $this->getId())
			->group('t.state');

		$db->setQuery($query);
		$statistics = $db->loadAssocList('state');

		// Assign the statistics
		foreach ($statistics as $state => $data)
		{
			switch ($state)
			{
				case NenoContentElementTranslation::NOT_TRANSLATED_STATE:
					$this->languageWordsNotTranslated = (int) $data['counter'];
					break;
				case NenoContentElementTranslation::QUEUED_FOR_BEING_TRANSLATED_STATE:
					$this->languageWordsQueuedToBeTranslated = (int) $data['counter'];
					break;
				case NenoContentElementTranslation::SOURCE_CHANGED_STATE:
					$this->languageWordsSourceHasChanged = (int) $data['counter'];
					break;
				case NenoContentElementTranslation::TRANSLATED_STATE:
					$this->languageWordsTranslated = (int) $data['counter'];
					break;
			}
		}

		$query
			->clear()
			->select('DISTINCT translation_method')
			->from($db->quoteName(NenoContentElementTranslation::getDbTable(), 't'))
			->leftJoin($db->quoteName('#__neno_content_element_langstrings', 'l') . ' ON t.content_id = l.id')
			->where('content_type = ' . $db->quote(NenoContentElementTranslation::LANG_STRING));

		$db->setQuery($query);
		$this->translationMethodUsed = $db->loadArray();
	}

	/**
	 * Get how many language strings have been queued to be translated
	 *
	 * @return int
	 */
	public function getLanguageWordsQueuedToBeTranslated()
	{
		if ($this->languageWordsQueuedToBeTranslated === null)
		{
			$this->calculateExtraData();
		}

		return $this->languageWordsQueuedToBeTranslated;
	}

	/**
	 * Get how many language strings have been translated.
	 *
	 * @return int
	 */
	public function getLanguageWordsTranslated()
	{
		if ($this->languageWordsTranslated === null)
		{
			$this->calculateExtraData();
		}

		return $this->languageWordsTranslated;
	}

	/**
	 * Get how many language strings the source language string has changed.
	 *
	 * @return int
	 */
	public function getLanguageWordsSourceHasChanged()
	{
		if ($this->languageWordsSourceHasChanged === null)
		{
			$this->calculateExtraData();
		}

		return $this->languageWordsSourceHasChanged;
	}

	/**
	 * Get Translation methods used.
	 *
	 * @return array
	 */
	public function getTranslationMethodUsed()
	{
		if ($this->translationMethodUsed === null)
		{
			$this->calculateExtraData();
		}

		return $this->translationMethodUsed;
	}

	/**
	 * Set translation methods used
	 *
	 * @param   array $translationMethodUsed Translation methods used
	 *
	 * @return $this
	 */
	public function setTranslationMethodUsed(array $translationMethodUsed)
	{
		$this->translationMethodUsed = $translationMethodUsed;

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
		$languageStrings = $this->getLanguageStrings();

		/* @var $languageString NenoContentElementLangstring */
		foreach ($languageStrings as $languageString)
		{
			$languageString->remove();
		}

		NenoLog::log('Group deleted successfully', 2);

		return parent::remove();
	}

	/**
	 * Get language strings
	 *
	 * @return array
	 */
	public function getLanguageStrings()
	{
		if ($this->languageStrings === null)
		{
			$this->languageStrings = array ();
			$languageStringsInfo   = self::getElementsByParentId(NenoContentElementLangstring::getDbTable(), 'group_id', $this->id, true);

			foreach ($languageStringsInfo as $languageStringInfo)
			{
				$languageString = new NenoContentElementLangstring($languageStringInfo);
				$languageString->setGroup($this);
				$this->languageStrings[] = $languageString;
			}
		}

		return $this->languageStrings;
	}

	/**
	 * Set language strings
	 *
	 * @param   array $languageStrings Language strings
	 *
	 * @return $this
	 */
	public function setLanguageStrings(array $languageStrings)
	{
		$this->languageStrings = $languageStrings;
		$this->contentHasChanged();

		return $this;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return $this
	 */
	public function prepareCacheContent()
	{
		/* @var $data $this */
		$data = parent::prepareCacheContent();

		$tables          = array ();
		$languageStrings = array ();

		/* @var $table NenoContentElementTable */
		foreach ($data->getTables() as $table)
		{
			$tables[] = $table->prepareCacheContent();
		}

		/* @var $languageString NenoContentElementLangstring */
		foreach ($data->getLanguageStrings() as $languageString)
		{
			$languageStrings [] = $languageString->prepareCacheContent();
		}

		$data->tables          = $tables;
		$data->languageStrings = $languageStrings;

		return $data;
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
			$this->languageFiles = array ();
			$defaultLanguage     = JFactory::getLanguage()->getDefault();
			$extensionNames      = NenoContentElementLangstring::load(
				array (
					'_select'  => array ('DISTINCT extension'),
					'group_id' => $this->getId()
				)
			);

			$extensionNames = array_unique($extensionNames);

			foreach ($extensionNames as $extensionName)
			{
				$this->languageFiles[] = $defaultLanguage . '.' . $extensionName . '.ini';
			}
		}

		return $this->languageFiles;
	}
}
