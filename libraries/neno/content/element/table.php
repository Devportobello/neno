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
 * Class NenoContentElementTable
 *
 * @since  1.0
 */
class NenoContentElementTable extends NenoContentElement implements NenoContentElementInterface
{
	/**
	 * @var stdClass
	 */
	public $wordCount;

	/**
	 * @var NenoContentElementGroup
	 */
	protected $group;

	/**
	 * @var string
	 */
	protected $tableName;

	/**
	 * @var array
	 */
	protected $primaryKey;

	/**
	 * @var boolean
	 */
	protected $translate;

	/**
	 * @var array|null
	 */
	protected $fields;

	/**
	 * @var bool
	 */
	protected $discovered;

	/**
	 * {@inheritdoc}
	 *
	 * @param   mixed $data          Table data
	 * @param   bool  $loadExtraData Load Extra data flag
	 * @param   bool  $loadParent    Load parent flag
	 */
	public function __construct($data, $loadExtraData = true, $loadParent = false)
	{
		parent::__construct($data);

		$data = (array) $data;

		// Make sure the name of the table is properly formatted.
		$this->tableName = NenoHelper::unifyTableName($this->tableName);

		$this->primaryKey = is_array($this->primaryKey) ? json_encode($this->primaryKey) : json_decode($this->primaryKey);

		if (!empty($data['groupId']) && $loadParent)
		{
			$this->group = NenoContentElementGroup::load($data['groupId'], $loadExtraData);
		}

		// Init the field list
		$this->fields = null;

		if (!$this->isNew())
		{
			$this->getFields($loadExtraData);

			if ($loadExtraData)
			{
				$this->getWordCount();
			}
		}
	}

	/**
	 * Get the fields related to this table
	 *
	 * @param   bool $loadExtraData                Load Extra data flag for fields
	 * @param   bool $onlyTranslatable             Returns only the translatable fields
	 * @param   bool $onlyFieldsWithNoTranslations Returns only fields with no translations
	 *
	 * @return array
	 */
	public function getFields($loadExtraData = false, $onlyTranslatable = false, $onlyFieldsWithNoTranslations = false)
	{
		if ($this->fields === null || $onlyFieldsWithNoTranslations)
		{
			$this->fields = array ();

			if ($onlyFieldsWithNoTranslations)
			{
				/* @var $db NenoDatabaseDriverMysqlx */
				$db    = JFactory::getDbo();
				$query = $db->getQuery(true);
				$query
					->select('f.id')
					->from('#__neno_content_element_fields AS f')
					->where(
						array (
							'f.table_id = ' . $this->getId(),
							'NOT EXISTS (SELECT 1 FROM #__neno_content_element_translations AS tr WHERE tr.content_id = f.id AND tr.content_type = ' . $db->quote('db_string') . ')'
						)
					);

				if ($onlyTranslatable)
				{
					$query->where('f.translate = 1');
				}

				$db->setQuery($query);
				$fields = $db->loadArray();

				foreach ($fields as $field)
				{
					$field = NenoContentElementField::load($field);

					if (!empty($field))
					{
						$this->fields[] = $field;
					}
				}
			}
			else
			{
				$fieldsInfo = self::getElementsByParentId(NenoContentElementField::getDbTable(), 'table_id', $this->getId(), true);

				for ($i = 0; $i < count($fieldsInfo); $i++)
				{
					$fieldInfo        = $fieldsInfo[$i];
					$fieldInfo->table = $this;
					$field            = new NenoContentElementField($fieldInfo, $loadExtraData);

					// Insert the field only if the $onlyTranslatable flag is off or if the flag is on and the field is translatable
					if (($field->isTranslatable() && $onlyTranslatable) || !$onlyTranslatable)
					{
						$this->fields[] = $field;
					}
				}
			}
		}
		elseif ($onlyTranslatable)
		{
			$reArrange = false;
			$fields    = array ();
			/* @var $field NenoContentElementField */
			foreach ($this->fields as $key => $field)
			{
				if ($field->isTranslatable())
				{
					$fields[] = $field;
				}
			}

			return $fields;
		}

		return $this->fields;
	}

	/**
	 * Set the fields related to this table
	 *
	 * @param   array $fields Table fields
	 *
	 * @return $this
	 */
	public function setFields(array $fields)
	{
		$this->fields = $fields;

		return $this;
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

			$query
				->select(
					array (
						'SUM(word_counter) AS counter',
						'tr.state'
					)
				)
				->from($db->quoteName(NenoContentElementField::getDbTable(), 'f'))
				->innerJoin(
					$db->quoteName(NenoContentElementTranslation::getDbTable(), 'tr') .
					' ON tr.content_id = f.id AND tr.content_type = ' .
					$db->quote('db_string') .
					' AND tr.language LIKE ' . $db->quote($workingLanguage)
				)
				->where(
					array (
						'f.table_id = ' . $this->getId(),
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

			$this->wordCount->total = $this->wordCount->untranslated + $this->wordCount->queued + $this->wordCount->changed + $this->wordCount->translated;
		}

		return $this->wordCount;
	}

	/**
	 * Load a table using its ID
	 *
	 * @param   integer $tableId Table Id
	 *
	 * @return bool|NenoContentElementTable
	 */
	public static function getTableById($tableId)
	{
		$table = self::load($tableId);

		return $table;
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
	 * @param   NenoContentElementGroup $group Group
	 *
	 * @return $this
	 */
	public function setGroup(NenoContentElementGroup $group)
	{
		$this->group = $group;

		return $this;
	}

	/**
	 * Get Primary key
	 *
	 * @return array
	 */
	public function getPrimaryKey()
	{
		if (!is_array($this->primaryKey))
		{
			$this->primaryKey = (array) json_decode($this->primaryKey);
		}

		return $this->primaryKey;
	}

	/**
	 * Set Primary key
	 *
	 * @param   array $primaryKey Primary keys
	 *
	 * @return $this
	 */
	public function setPrimaryKey(array $primaryKey)
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
	 * @param   boolean $translate If the table needs to be translated
	 *
	 * @return $this
	 */
	public function markAsTranslatable($translate)
	{
		$this->translate = $translate;

		return $this;
	}

	/**
	 * Add a field to the field list.
	 *
	 * @param   NenoContentElementField $field Field
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
	 * @param   bool $allFields         Allows to show all the fields
	 * @param   bool $recursive         Convert this method in recursive
	 * @param   bool $convertToDatabase Convert property names to database
	 *
	 * @return stdClass
	 */
	public function toObject($allFields = false, $recursive = false, $convertToDatabase = true)
	{
		$object = parent::toObject($allFields, $recursive, $convertToDatabase);

		if (!empty($this->group) && $convertToDatabase)
		{
			$object->group_id = $this->group->getId();
		}

		// If it's an array, let's json it!
		if (is_array($this->primaryKey) && $convertToDatabase)
		{
			$object->primary_key = json_encode($this->primaryKey);
		}

		return $object;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return bool
	 */
	public function remove()
	{
		$fields = $this->getFields();

		// Delete all the translations first
		/* @var $field NenoContentElementField */
		foreach ($fields as $field)
		{
			$field->removeTranslations();
		}

		// The delete the field itself
		/* @var $field NenoContentElementField */
		foreach ($fields as $field)
		{
			$field->remove();
		}

		/* @var $db NenoDatabaseDriverMysqlx */
		$db = JFactory::getDbo();
		$db->deleteShadowTables($this->getTableName());

		return parent::remove();
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
	 * @param   string $tableName Table name
	 *
	 * @return $this
	 */
	public function setTableName($tableName)
	{
		$this->tableName = $tableName;

		return $this;
	}

	/**
	 * Discover the element
	 *
	 * @return bool True on success
	 */
	public function discoverElement()
	{
		NenoHelper::setSetupState(
			JText::sprintf('COM_NENO_INSTALLATION_MESSAGE_PARSING_GROUP_TABLE', $this->group->getGroupName(), $this->getTableName()), 2
		);

		if ($this->translate)
		{
			// Check if there are children not discovered
			$field = NenoContentElementField::load(array ('discovered' => 0, 'table_id' => $this->id, '_limit' => 1, 'translate' => 1));

			if (!empty($field))
			{
				NenoSettings::set('installation_level', '2.1');
				NenoSettings::set('discovering_element_1.1', $this->id);
			}
			else
			{
				NenoSettings::set('discovering_element_1.1', 0);
				$this
					->setDiscovered(true)
					->persist();
			}
		}
		else
		{
			NenoHelper::setSetupState(
				JText::sprintf('COM_NENO_INSTALLATION_MESSAGE_TABLE_TOO_MANY_RECORDS', $this->group->getGroupName(), $this->getTableName()), 2, 'error'
			);
		}
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

		if ($result)
		{
			/* @var $db NenoDatabaseDriverMysqlx */
			$db = JFactory::getDbo();

			// If the table has been marked as translated
			if ($this->translate)
			{
				// Creates shadow tables and copy the content on it
				$db->createShadowTables($this->tableName);

				if (!$isNew)
				{
					// Sync existing tables,
					$db->syncTable($this->tableName);
				}
			}

			/* @var $field NenoContentElementField */
			foreach ($this->fields as $field)
			{
				$field
					->setTable($this)
					->setTranslate($field->isTranslatable() && $this->isTranslate())
					->persist();

				if ($field->getFieldName() === 'language' && $this->isTranslate())
				{
					$languages       = NenoHelper::getTargetLanguages();
					$defaultLanguage = NenoSettings::get('source_language');

					foreach ($languages as $language)
					{
						if ($language->lang_code != $defaultLanguage)
						{
							$db->deleteContentElementsFromSourceTableToShadowTables($this->tableName, $language->lang_code);
						}
					}

					$db->setContentForAllLanguagesToSourceLanguage($this->tableName, $defaultLanguage);
				}
			}
		}

		return $result;
	}

	/**
	 * Check if the table is translatable
	 *
	 * @return boolean
	 */
	public function isTranslate()
	{
		return $this->translate;
	}

	/**
	 * Mark this table as translatable/untranslatable
	 *
	 * @param   boolean $translate Translation status
	 * @param   boolean $force     Force the status
	 *
	 * @return $this
	 */
	public function setTranslate($translate, $force = false)
	{
		$this->translate = $translate;

		// If the table has been marked as translatable, let's check for the content element file
		if ($this->translate && !$force)
		{
			$this->checkTranslatableStatusFromContentElementFile();
		}

		return $this;
	}

	/**
	 * Check if the table should be translatable
	 *
	 * @return void
	 */
	public function checkTranslatableStatusFromContentElementFile()
	{
		$filePath = $this->getContentElementFilename();

		if (file_exists($filePath))
		{
			$xml             = simplexml_load_file($filePath);
			$this->translate = ((int) $xml->translate) == 1;
		}
	}

	/**
	 * Get content element filename
	 *
	 * @return string
	 */
	public function getContentElementFilename()
	{
		return JPATH_NENO . '/contentelements/' . str_replace('#__', '', $this->tableName) . '_contentelements.xml';
	}

	/**
	 * Check if the field has been discovered already
	 *
	 * @return boolean
	 */
	public function isDiscovered()
	{
		return $this->discovered;
	}

	/**
	 * Set discovered flag
	 *
	 * @param   boolean $discovered Discovered flag
	 *
	 * @return $this
	 */
	public function setDiscovered($discovered)
	{
		$this->discovered = $discovered;

		return $this;
	}

	/**
	 * Check if a table has a state field
	 *
	 * @return bool
	 */
	public function hasState()
	{
		$fields = $this->getFields(false);
		$found  = false;

		/* @var $field NenoContentElementField */
		foreach ($fields as $field)
		{
			if ($field->getFieldName() == 'state')
			{
				$found = true;
				break;
			}
		}

		return $found;
	}

	/**
	 * Get primary keys field
	 *
	 * @return array
	 */
	public function getPrimaryKeys()
	{
		$primaryKeys = array ();

		foreach ($this->primaryKey as $primaryKey)
		{
			$primaryKeys[] = NenoContentElementField::load(
				array (
					'field_name' => $primaryKey,
					'table_id'   => $this->id
				)
			);
		}

		return $primaryKeys;
	}
}
