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

/**
 * Manifest Strings controller class
 *
 * @since  1.0
 */
class NenoControllerInstallation extends JControllerAdmin
{
	/**
	 * Load installation step
	 *
	 * @return void
	 */
	public function loadInstallationStep()
	{
		$step = NenoSettings::get('installation_status', 0);

		if (empty($step))
		{
			$layout = JLayoutHelper::render('installationgetstarted', null, JPATH_NENO_LAYOUTS);
		}
		else
		{
			$layout = JLayoutHelper::render('installationstep' . $step, $this->getDataForStep($step), JPATH_NENO_LAYOUTS);
		}

		$sidebar = '';

		if ($step == 5)
		{
			NenoHelperBackend::addSubmenu();
			$sidebar = JHtmlSidebar::render();
		}

		echo json_encode(array ('installation_step' => $layout, 'jsidebar' => $sidebar));

		JFactory::getApplication()->close();
	}

	/**
	 * Get data for the installation step
	 *
	 * @param   int $step Step number
	 *
	 * @return stdClass
	 */
	protected function getDataForStep($step)
	{
		$data = new stdClass;

		switch ($step)
		{
			case 1:
				$languages           = NenoHelper::findLanguages(true);
				$data->select_widget = JHtml::_('select.genericlist', $languages, 'source_language', null, 'iso', 'name', NenoSettings::get('source_language'));
				break;
			case 3:
				$language                   = JFactory::getLanguage();
				$default                    = NenoSettings::get('source_language');
				$knownLanguages             = $language->getKnownLanguages();
				$languagesData              = array ();
				$defaultTranslationsMethods = NenoHelper::getDefaultTranslationMethods();
				$db                         = JFactory::getDbo();
				$query                      = $db->getQuery(true);
				$query
					->insert('#__neno_content_language_defaults')
					->columns(
						array (
							'lang',
							'translation_method_id',
							'ordering'
						)
					);

				$insert = false;

				foreach ($knownLanguages as $key => $knownLanguage)
				{
					if ($knownLanguage['tag'] != $default)
					{
						$insert                                    = true;
						$languagesData[$key]                       = $knownLanguage;
						$languagesData[$key]['lang_code']          = $knownLanguage['tag'];
						$languagesData[$key]['title']              = $knownLanguage['name'];
						$languagesData[$key]['translationMethods'] = $defaultTranslationsMethods;
						$languagesData[$key]['errors']             = NenoHelper::getLanguageErrors($languagesData[$key]);
						$languagesData[$key]['placement']          = 'installation';
						$languagesData[$key]['image']              = NenoHelper::getLanguageImage($knownLanguage['tag']);
						$languagesData[$key]['published']          = NenoHelper::isLanguagePublished($knownLanguage['tag']);

						foreach ($defaultTranslationsMethods as $ordering => $defaultTranslationsMethod)
						{
							$query->values($db->quote($knownLanguage['tag']) . ',' . $defaultTranslationsMethod->id . ',' . ($ordering + 1));
						}
					}
				}

				if ($insert)
				{
					$db->setQuery($query);
					$db->execute();
				}

				$data->languages           = $languagesData;
				$data->canInstallLanguages = true;
				$memoryDetails             = NenoHelperBackend::getMemoryDetails();

				if (!empty($memoryDetails))
				{
					$data->canInstallLanguages = $memoryDetails['free_space'] == 0 || $memoryDetails['free_space'] > $memoryDetails['current_data_space'];
				}

				break;
			case 4:
				/* @var $db NenoDatabaseDriverMysqlx */
				$db            = JFactory::getDbo();
				$query         = $db->getQuery(true);
				$tablesIgnored = NenoHelper::getDoNotTranslateTables();

				/* @var $config \Joomla\Registry\Registry */
				$config = JFactory::getConfig();

				$query
					->select('DISTINCT TABLE_NAME')
					->from('INFORMATION_SCHEMA.COLUMNS')
					->where(
						array (
							'COLUMN_NAME = ' . $db->quote('language'),
							'TABLE_SCHEMA = ' . $db->quote($config->get('db')),
							'TABLE_NAME NOT LIKE ' . $db->quote('%neno%'),
							'TABLE_NAME NOT LIKE ' . $db->quote('%\_\_%'),
							'TABLE_NAME NOT LIKE ' . $db->quote('%menu'),
						)
					);

				$db->setQuery($query);
				$tables = $db->loadArray();

				$tablesFound = array ();

				foreach ($tables as $table)
				{
					if (!in_array(str_replace($db->getPrefix(), '#__', $table), $tablesIgnored))
					{
						$sourceLanguage      = NenoSettings::get('source_language');
						$sourceLanguageParts = explode('-', $sourceLanguage);
						$query
							->clear()
							->select(
								array (
									'COUNT(*) AS counter',
									'language',
									$db->quote($table) . ' AS `table`'
								)
							)
							->from($db->quoteName($table))
							->where(
								array (
									'language <> ' . $db->quote('*'),
									'language <> ' . $db->quote(''),
									'language <> ' . $db->quote($sourceLanguage),
									'language <> ' . $db->quote($sourceLanguageParts[0]),
								)
							)
							->group('language');

						$db->setQuery($query);
						$recordsFound = $db->loadObjectList();

						if (!empty($recordsFound))
						{
							$tablesFound = array_merge($tablesFound, $recordsFound);
						}
					}
				}

				$data->tablesFound = $tablesFound;
				break;
		}

		return $data;
	}

	/**
	 * Process installation step
	 *
	 * @return void
	 */
	public function processInstallationStep()
	{
		$step        = NenoSettings::get('installation_status', 0);
		$moveForward = true;
		$app         = JFactory::getApplication();
		$response    = array ('status' => 'ok');

		if ($step != 0)
		{
			$methodName = 'validateStep' . (int) $step;

			// Validate data.
			if (method_exists($this, $methodName))
			{
				$moveForward = $this->{$methodName}();
			}
		}

		if ($moveForward)
		{
			NenoSettings::set('installation_status', $step + 1);
		}
		else
		{
			$response['status'] = 'err';
			$messagesQueued     = $app->getMessageQueue();
			$messages           = array ();

			foreach ($messagesQueued as $messageQueued)
			{
				if ($messageQueued['type'] === 'error')
				{
					$messages[] = $messageQueued['message'];
				}
			}

			$response['error_messages'] = $messages;
		}

		echo json_encode($response);

		JFactory::getApplication()->close();
	}

	/**
	 * Get previous messages
	 *
	 * @return void
	 */
	public function getPreviousMessages()
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query
			->select('*')
			->from('#__neno_installation_messages as m1')
			->where(
				array (
					'm1.fetched = 1'
				)
			)
			->group('level')
			->order(
				array (
					'level ASC',
					'id DESC'
				)
			);

		$db->setQuery($query);
		echo json_encode($db->loadAssocList());

		JFactory::getApplication()->close();
	}

	/**
	 * Execute discovering process
	 *
	 * @return void
	 */
	public function processDiscoveringStep()
	{
		/* @var $db NenoDatabaseDriverMysqlx */
		$db       = JFactory::getDbo();
		$query    = $db->getQuery(true);
		$finished = NenoSettings::get('installation_completed') == 1;

		if (!$finished)
		{
			$level   = NenoSettings::get('installation_level', 0);
			$element = $this->getElementByLevel($level);

			if ($element == null && $level == 0)
			{
				// If there aren't any, let's create do not translate group if it doesn't exist
				NenoHelperBackend::createDoNotTranslateGroup();

				// Let's publish language plugins
				$query
					->clear()
					->update('#__extensions')
					->set('enabled = 1')
					->where(
						array (
							'element LIKE ' . $db->quote('languagecode'),
							'element LIKE ' . $db->quote('languagefilter')
						), 'OR'
					);

				$db->setQuery($query);
				$db->execute();

				// Let's create menu structure
				NenoHelper::createMenuStructure();

				NenoSettings::set('installation_completed', 1);
				$finished = true;
			}
			elseif ($element == null && $level != 0)
			{
				list($firstPart, $secondPart) = explode('.', $level);
				$firstPart--;

				if ($firstPart == 0)
				{
					NenoSettings::set('installation_level', $firstPart);
				}
				else
				{
					NenoSettings::set('installation_level', implode('.', array ($firstPart, $secondPart)));
				}
			}
			else
			{
				$element->discoverElement();
			}
		}

		if ($finished)
		{
			echo 'ok';
		}

		JFactory::getApplication()->close();
	}

	/**
	 * Get a particular element using the level
	 *
	 * @param   string $level Hierarchy level
	 *
	 * @return NenoContentElementInterface|null
	 */
	protected function getElementByLevel($level)
	{
		$element   = null;
		$elementId = NenoSettings::get('discovering_element_' . $level);
		$db        = JFactory::getDbo();
		$query     = $db->getQuery(true);

		switch ($level)
		{
			// Groups
			case '0':
				// This means to get a group that haven't been discovered yet
				$extensions = $db->quote(NenoHelper::whichExtensionsShouldBeTranslated());

				$query
					->clear()
					->select('e.*')
					->from('`#__extensions` AS e')
					->where(
						array (
							'e.type IN (' . implode(',', $extensions) . ')',
							'e.name NOT LIKE \'com_neno\'',
							'NOT EXISTS (SELECT 1 FROM #__neno_content_element_groups_x_extensions AS ge WHERE ge.extension_id = e.extension_id)'
						)
					)
					->order('name');
				$db->setQuery($query, 0, 1);
				$extension = $db->loadAssoc();

				if (!empty($extension))
				{
					// Check if this extension has been discovered already
					$groupId = NenoHelper::isExtensionAlreadyDiscovered($extension['extension_id']);

					if ($groupId !== false)
					{
						$group = NenoContentElementGroup::load($groupId);
					}
					else
					{
						$group = new NenoContentElementGroup(array ('group_name' => $extension['name']));
					}

					$group->addExtension($extension['extension_id']);

					$extensionName = NenoHelper::getExtensionName($extension);
					$languageFiles = NenoHelper::getLanguageFiles($extensionName);
					$tables        = NenoHelper::getComponentTables($group, $extensionName);
					$group->setAssignedTranslationMethods(NenoHelper::getTranslationMethodsForLanguages());

					// If the group contains tables and/or language strings, let's save it
					if (!empty($tables) || !empty($languageFiles))
					{
						$group
							->setLanguageFiles($languageFiles)
							->setTables($tables);
					}

					$element = $group;
				}
				else
				{
					$element = NenoHelperBackend::groupingTablesNotDiscovered(false);
				}

				break;

			// Tables
			case '1.1':
				// This means to get a table which has fields that haven't been discovered yet.

				if (empty($elementId))
				{
					// Get one table that hasn't been discovered yet
					$table = NenoContentElementTable::load(
						array (
							'discovered' => 0,
							'_limit'     => 1,
							'translate'  => 1,
							'group_id'   => NenoSettings::get('discovering_element_0')
						), false, true
					);
				}
				else
				{
					$table = NenoContentElementTable::load($elementId, false, true);
				}

				if (!empty($table))
				{
					$element = $table;
				}

				break;

			// Language files
			case '1.2':
				// This means to get a language file which has language strings that haven't been discovered yet.

				if ($elementId == null)
				{
					// Get one table that hasn't been discovered yet
					$languageFile = NenoContentElementLanguageFile::load(
						array (
							'discovered' => 0,
							'_limit'     => 1,
							'group_id'   => NenoSettings::get('discovering_element_0')
						), false, true
					);
				}
				else
				{
					$languageFile = NenoContentElementLanguageFile::load($elementId, false, true);
				}

				if (!empty($languageFile))
				{
					$element = $languageFile;
				}
				break;

			// Fields
			case '2.1':
				// This means to get a field that hasn't been completed yet.

				if ($elementId == null)
				{
					// Get one table that hasn't been discovered yet
					$field = NenoContentElementField::load(
						array (
							'discovered' => 0,
							'_limit'     => 1,
							'translate'  => 1,
							'table_id'   => NenoSettings::get('discovering_element_1.1')
						), false, true
					);
				}
				else
				{
					$field = NenoContentElementField::load($elementId);
				}

				if (!empty($field) && $field)
				{
					$element = $field;
				}
				break;

			// Language strings
			case '2.2':
				// This means to get a language string that hasn't been completed yet.

				if ($elementId == null)
				{
					// Get one table that hasn't been discovered yet
					$languageString = NenoContentElementLanguageString::load(
						array (
							'discovered'      => 0,
							'_limit'          => 1,
							'languagefile_id' => NenoSettings::get('discovering_element_1.2')
						), false, true
					);
				}
				else
				{
					$languageString = NenoContentElementLanguageString::load($elementId);
				}

				if (!empty($languageString))
				{
					$element = $languageString;
				}
				break;
		}

		return $element;
	}

	/**
	 * Fetch setup status
	 *
	 * @return void
	 */
	public function getSetupStatus()
	{
		$setupState = NenoHelperBackend::getSetupState();
		echo json_encode($setupState);
		JFactory::getApplication()->close();
	}

	/**
	 * Validate installation step 1
	 *
	 * @return bool
	 */
	protected function validateStep1()
	{
		$input          = $this->input;
		$sourceLanguage = $input->post->get('source_language');
		$app            = JFactory::getApplication();

		if (!empty($sourceLanguage))
		{
			$language           = JFactory::getLanguage();
			$knownLanguagesTags = array_keys($language->getKnownLanguages());

			if (!in_array($sourceLanguage, $knownLanguagesTags))
			{
				$db    = JFactory::getDbo();
				$query = $db->getQuery(true);

				$query
					->select('update_id')
					->from('#__updates')
					->where('element = ' . $db->quote('pkg_' . $sourceLanguage))
					->order('update_id DESC');

				$db->setQuery($query, 0, 1);
				$updateId = $db->loadResult();

				if (!empty($updateId))
				{
					if (!NenoHelper::installLanguage($updateId))
					{
						$app->enqueueMessage('There was an error install language. Please try again later.', 'error');

						return false;
					}
				}
			}

			// Once the language is installed, let's mark it as default
			JLoader::register('LanguagesModelInstalled', JPATH_ADMINISTRATOR . '/components/com_languages/models/installed.php');

			/* @var $model LanguagesModelInstalled */
			$model = JModelLegacy::getInstance('Installed', 'LanguagesModel');

			// If the language has been marked as default, let's save that on the settings
			if ($model->publish($sourceLanguage))
			{
				NenoSettings::set('source_language', $sourceLanguage, true);
			}

			return true;
		}

		$app->enqueueMessage('Error getting source language', 'error');

		return false;
	}

	/**
	 * Validate installation step 3
	 *
	 * @return bool
	 */
	protected function validateStep2()
	{
		$input = $this->input;
		$app   = JFactory::getApplication();

		$jform = $input->post->get('jform', array (), 'ARRAY');

		if (!empty($jform['translation_methods']))
		{
			foreach ($jform['translation_methods'] as $key => $translationMethod)
			{
				NenoSettings::set('translation_method_' . ($key + 1), $translationMethod);
			}

			return true;
		}

		$app->enqueueMessage('', 'error');

		return false;
	}
}
