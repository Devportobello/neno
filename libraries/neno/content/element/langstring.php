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
 * Class NenoContentElementLangfile
 *
 * @since  1.0
 */
class NenoContentElementLangstring extends NenoContentElement
{
	/**
	 * This state is for a string that has been translated
	 */
	const TRANSLATED_STATE = 1;

	/**
	 * This state is for a string that has been sent to be translated but the translation has not arrived yet.
	 */
	const QUEUED_FOR_BEING_TRANSLATED_STATE = 2;

	/**
	 * This state is for a string that its source string has changed.
	 */
	const SOURCE_CHANGED_STATE = 3;

	/**
	 * This state is for a string that has not been translated yet or the user does not want to translated it
	 */
	const NOT_TRANSLATED_STATE = 4;

	/**
	 * @var String
	 */
	protected $string;

	/**
	 * @var string
	 */
	protected $language;

	/**
	 * @var DateTime
	 */
	protected $timeDeleted;

	/**
	 * @var integer
	 */
	protected $state;

	/**
	 * @var integer
	 */
	protected $version;

	/**
	 * @var string
	 */
	protected $constant;

	/**
	 * @var string
	 */
	protected $extension;

	/**
	 * @var DateTime
	 */
	protected $timeAdded;

	/**
	 * @var DateTime
	 */
	protected $timeChanged;

	/**
	 * @var NenoContentElementGroup
	 */
	protected $group;

	/**
	 * @var array
	 */
	protected $translations;

	public function __construct($data)
	{
		parent::__construct($data);

		if (!$this->isNew())
		{
			$this->translations = NenoContentElementTranslation::getTranslations($this);
		}
	}

	/**
	 * @param string $language
	 *
	 * @return array
	 */
	public static function loadSourceLanguageStrings($language)
	{
		// Load from DB
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
			->select(
				array(
					'a.*',
					'CONCAT(a.extension,".ini:", UPPER(a.constant)) AS arraykey'
				)
			)
			->from($db->quoteName(self::getDbTable()) . ' AS a')
			->where(
				array(
					'a.language = ' . $db->quote($language),
					'a.state = 1'
				)
			)
			->order(
			// Order by lang and then extension
				array(
					'a.language',
					'a.extension'
				)
			);


		$db->setQuery($query);
		$sourceLanguageStrings = $db->loadObjectList('arraykey');

		$arrayKeys = array_keys($sourceLanguageStrings);

		foreach ($arrayKeys as $arrayKey)
		{
			$sourceLanguageStrings[$arrayKey] = new NenoContentElementLangstring($sourceLanguageStrings[$arrayKeys]);
		}

		// Log it if the debug mode is on
		if (JDEBUG)
		{
			NenoLog::log('Loaded ' . count($sourceLanguageStrings) . ' source language strings in the database', 3);
		}

		return $sourceLanguageStrings;
	}

	/**
	 * @param string $type
	 * @param array  $options ('fieldName' => 'fieldValue')
	 *
	 * @return NenoContentElementLangstring
	 */
	public static function getLanguageString(array $options)
	{
		$db = JFactory::getDbo();
		$db->setQuery(static::getLanguageStringQuery($options));
		$data           = $db->loadAssoc();
		$languageString = new NenoContentElementLangstring($data);

		return $languageString;
	}

	/**
	 * @param string $type
	 * @param array  $options
	 *
	 * @return JDatabaseQuery
	 */
	protected static function getLanguageStringQuery(array $options)
	{
		$tableName = self::getDbTable();
		$db        = JFactory::getDbo();
		$query     = $db->getQuery(true);

		$query
			->select('*')
			->from($tableName);

		foreach ($options as $fieldName => $fieldValue)
		{
			if (!is_null($fieldValue) && !is_null($fieldName))
			{
				$query->where($db->quoteName($fieldName) . ' = ' . $db->quote($fieldValue));
			}
		}

		return $query;
	}

	/**
	 * @param string $type
	 * @param array  $options
	 *
	 * @return array
	 */
	public static function getLanguageStrings($type, array $options)
	{
		$db = JFactory::getDbo();
		$db->setQuery(static::getLanguageStringQuery($type, $options));
		$dataList = $db->loadAssocList();

		$languageStringList = array();

		foreach ($dataList as $data)
		{
			// Sanitize the array
			$data                                               = NenoHelper::convertDatabaseArrayToClassArray($data);
			$languageString                                     = new NenoContentElementLangstring($data);
			$languageStringList[$languageString->generateKey()] = $languageString;
		}

		return $languageStringList;

	}

	/**
	 * Generate the language key based on its datas
	 *
	 * @return string
	 */
	public function generateKey()
	{
		return $this->getExtension() . '.ini:' . $this->getConstant();
	}

	/**
	 * Get the name of the extension that owns this string
	 *
	 * @return string
	 */
	public function getExtension()
	{
		return $this->extension;
	}

	/**
	 * Set the name of the extension that owns this string
	 *
	 * @param string $extension
	 *
	 * @return NenoContentElementLangstring
	 */
	public function setExtension($extension)
	{
		$this->extension = $extension;

		return $this;
	}

	/**
	 * Get the constant that identifies the string
	 *
	 * @return string
	 */
	public function getConstant()
	{
		return $this->constant;
	}

	/**
	 * Set the constant that identifies the string
	 *
	 * @param   string $constant Constant
	 *
	 * @return NenoContentElementLangstring
	 */
	public function setConstant($constant)
	{
		$this->constant = $constant;

		return $this;
	}

	/**
	 * @return array
	 */
	public function getTranslations()
	{
		return $this->translations;
	}

	/**
	 * @param array $translations
	 */
	public function setTranslations($translations)
	{
		$this->translations = $translations;
	}

	/**
	 * @return NenoContentElementGroup
	 */
	public function getGroup()
	{
		return $this->group;
	}

	/**
	 * @param NenoContentElementGroup $group
	 */
	public function setGroup(NenoContentElementGroup $group)
	{
		$this->group = $group;
	}

	/**
	 * Get the time when the string was discovered
	 *
	 * @return DateTime
	 */
	public function getTimeAdded()
	{
		return $this->timeAdded;
	}

	/**
	 * Set the time when the string was discovered
	 *
	 * @param   DateTime $timeAdded Discover time
	 *
	 * @return NenoContentElementLangstring
	 */
	public function setTimeAdded($timeAdded)
	{
		$this->timeAdded = $timeAdded;

		return $this;
	}

	/**
	 * Get the time when the string was changed (if it has been changed)
	 *
	 * @return DateTime
	 */
	public function getTimeChanged()
	{
		return $this->timeChanged;
	}

	/**
	 * Set the time when the string changed
	 *
	 * @param   DateTime $timeChanged
	 *
	 * @return NenoContentElementLangstring
	 */
	public function setTimeChanged($timeChanged)
	{
		$this->timeChanged = $timeChanged;

		return $this;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return JObject
	 */
	public function toObject()
	{
		$data = parent::toObject();
		$data->set('group_id', $this->group->getId());

		return $data;
	}

	/**
	 * @return String
	 */
	public function getString()
	{
		return $this->string;
	}

	/**
	 * @param String $string
	 */
	public function setString($string)
	{
		$this->string = $string;
	}

	public function increaseVersion()
	{
		$this->version = $this->version + 1;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getLanguage()
	{
		return $this->language;
	}

	/**
	 * @param string $language
	 */
	public function setLanguage($language)
	{
		$this->language = $language;
	}

	/**
	 * @return DateTime
	 */
	public function getTimeDeleted()
	{
		return $this->timeDeleted;
	}

	/**
	 * @param DateTime $timeDeleted
	 */
	public function setTimeDeleted($timeDeleted)
	{
		$this->timeDeleted = $timeDeleted;
	}

	/**
	 * @return int
	 */
	public function getState()
	{
		return $this->state;
	}

	/**
	 * @param int $state
	 */
	public function setState($state)
	{
		$this->state = $state;
	}

	/**
	 * @return int
	 */
	public function getVersion()
	{
		return $this->version;
	}

	/**
	 * @param int $version
	 */
	public function setVersion($version)
	{
		$this->version = $version;
	}
}
