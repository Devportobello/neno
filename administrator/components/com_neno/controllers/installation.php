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

		echo $layout;

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
				$language            = JFactory::getLanguage();
				$languages           = NenoHelper::findLanguages(true);
				$data->select_widget = JHtml::_('select.genericlist', $languages, 'source_language', null, 'iso', 'name', $language->getDefault());
				break;
			case 4:
				$language                   = JFactory::getLanguage();
				$knownLanguages             = $language->getKnownLanguages();
				$languagesData              = array ();
				$defaultTranslationsMethods = NenoHelper::getDefaultTranslationMethods();

				foreach ($knownLanguages as $key => $knownLanguage)
				{
					$languagesData[$key]                        = $knownLanguage;
					$languagesData[$key]['lang_code']           = $knownLanguage['tag'];
					$languagesData[$key]['title']               = $knownLanguage['name'];
					$languagesData[$key]['translation_methods'] = $defaultTranslationsMethods;
					$languagesData[$key]['errors']              = NenoHelper::getLanguageErrors($languagesData[$key]);
					$languagesData[$key]['placement']           = 'installation';
					$languagesData[$key]['image']               = NenoHelper::getLanguageImage($knownLanguage['tag']);
					$languagesData[$key]['published']           = NenoHelper::isLanguagePublished($knownLanguage['tag']);
					$languagesData[$key]['translationMethods']  = NenoHelper::getLanguageDefault($languagesData[$key]['lang_code']);
				}

				$data->languages = $languagesData;

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
	 * Task to finishing setup
	 *
	 * @return void
	 */
	public function finishingSetup()
	{
		ini_set('max_execution_time', 600);
		$this->setSetupState(0, 'Generating menus');
		NenoHelper::createMenuStructure();
		$this->setSetupState(10, 'Discover extensions');

		/* @var $db NenoDatabaseDriverMysqlx */
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$extensions = $db->quote(NenoHelper::whichExtensionsShouldBeTranslated());

		$query
			->select('e.*')
			->from('`#__extensions` AS e')
			->where(
				array (
					'e.type IN (' . implode(',', $extensions) . ')',
					'e.name NOT LIKE \'com_neno\'',
				)
			)
			->order('name');
		$db->setQuery($query);
		$extensions = $db->loadAssocList();

		$percentPerExtension = (int) 80 / count($extensions);
		$currentPercent      = 10 + $percentPerExtension;

		foreach ($extensions as $extension)
		{
			$this->setSetupState($currentPercent, 'Parsing ' . $extension['name']);
			NenoHelper::discoverExtension($extension);
			$currentPercent = $currentPercent + $percentPerExtension;
		}

		$this->setSetupState(95, 'Parsing Other tables');
		NenoHelper::groupingTablesNotDiscovered();
		$this->setSetupState(100, 'Installation completed');

		$group = new NenoContentElementGroup(array ('group_name' => JText::_('COM_NENO_DO_NOT_TRANSLATE_GROUP_NAME')));
		$group->persist();

		echo 'ok';

		JFactory::getApplication()->close();
	}

	/**
	 * Set setup state
	 *
	 * @param   int    $percent Completed percent
	 * @param   string $message
	 *
	 * @return void
	 */
	protected function setSetupState($percent, $message)
	{
		NenoSettings::set('setup_message', $message);
		NenoSettings::set('setup_percent', $percent);
	}

	/**
	 * Fetch setup status
	 *
	 * @return void
	 */
	public function getSetupStatus()
	{
		echo json_encode(array ('message' => NenoSettings::get('setup_message'), 'percent' => NenoSettings::get('setup_percent')));
		exit;
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
	 * Validate installation step 2
	 *
	 * @return bool
	 */
	protected function validateStep2()
	{
		$input       = $this->input;
		$tasksOption = $input->getWord('schedule_task_option');
		$app         = JFactory::getApplication();

		if (!empty($tasksOption))
		{
			// If the option selected is AJAX, let's enable the module
			if ($tasksOption === 'ajax')
			{
				// Do something
			}

			NenoSettings::set('schedule_task_option', $tasksOption);

			return true;
		}

		$app->enqueueMessage('COM_NENO_INSTALLATION_ERROR');

		return false;
	}

	/**
	 * Validate installation step 3
	 *
	 * @return bool
	 */
	protected function validateStep3()
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
