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
class NenoControllerGroupsElements extends JControllerAdmin
{
	/**
	 * Method to import tables that need to be translated
	 *
	 * @return void
	 */
	public function discoverExtensions()
	{
		// Check all the extensions that haven't been discover yet
		NenoHelper::discoverExtensions();

		$this
			->setRedirect('index.php?option=com_neno&view=groupselements')
			->redirect();
	}

	/**
	 * Read content files
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	public function readContentElementFile()
	{
		jimport('joomla.filesystem.file');

		$input       = JFactory::getApplication()->input;
		$fileData    = $input->files->get('content_element');
		$destFile    = JFactory::getConfig()->get('tmp_path') . '/' . $fileData['name'];
		$extractPath = JFactory::getConfig()->get('tmp_path') . '/' . JFile::stripExt($fileData['name']);

		// If the file has been moved successfully, let's work with it.
		if (JFile::move($fileData['tmp_name'], $destFile) === true)
		{
			// If the file is a zip file, let's extract it
			if ($fileData['type'] == 'application/zip')
			{
				$adapter = JArchive::getAdapter('zip');
				$adapter->extract($destFile, $extractPath);
				$contentElementFiles = JFolder::files($extractPath);
			}
			else
			{
				$contentElementFiles = array($destFile);
			}

			// Add to each content file the path of the extraction location.
			NenoHelper::concatenateStringToStringArray($extractPath . '/', $contentElementFiles);

			// Parse element file(s)
			NenoHelper::parseContentElementFile(JFile::stripExt($fileData['name']), $contentElementFiles);

			// Clean temporal folder
			NenoHelper::cleanFolder(JFactory::getConfig()->get('tmp_path'));
		}

		$this
			->setRedirect('index.php?option=com_neno&view=groupselements')
			->redirect();
	}

	/**
	 * Enable/Disable a database table to be translate
	 *
	 * @return void
	 */
	public function enableDisableContentElementTable()
	{
		$input = JFactory::getApplication()->input;

		$tableId         = $input->getInt('tableId');
		$translateStatus = $input->getBool('translateStatus');

		$table  = NenoContentElementTable::getTableById($tableId);
		$result = 0;

		// If the table exists, let's work with it.
		if ($table !== false)
		{
			$table->markAsTranslatable($translateStatus);
			$table->persist();

			$result = 1;
		}

		echo $result;
		JFactory::getApplication()->close();
	}

	/**
	 *
	 */
	public function enableDisableContentElementField()
	{
		$input = JFactory::getApplication()->input;

		$fieldId         = $input->getInt('fieldId');
		$translateStatus = $input->getBool('translateStatus');

		/* @var $field NenoContentElementField */
		$field  = NenoContentElementField::getFieldById($fieldId);
		$result = 0;

		// If the table exists, let's work with it.
		if ($field !== false)
		{
			$field->setTranslate($translateStatus);
			$field->persist();

			$stringStatus                  = array();
			$stringStatus['translated']    = $field->getWordsTranslated();
			$stringStatus['queued']        = $field->getWordsQueuedToBeTranslated();
			$stringStatus['changed']       = $field->getWordsSourceHasChanged();
			$stringStatus['notTranslated'] = $field->getWordsNotTranslated();

			$result = NenoHelper::htmlTranslationBar($stringStatus, $translateStatus);
		}

		echo $result;
		JFactory::getApplication()->close();
	}
}
