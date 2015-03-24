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
 * Manifest Strings controller class
 *
 * @since  1.0
 */
class NenoControllerStrings extends JControllerAdmin
{
	/**
	 * Get a list of strings
	 *
	 * @return  string
	 */
	public function getStrings()
	{
		NenoLog::log('Method getStrings of NenoControllerEditor called', 3);
		$input          = JFactory::getApplication()->input;
		$filterJson     = $input->getString('jsonData');
		$filterArray    = json_decode($filterJson);
		$filterGroups   = array ();
		$filterElements = array ();
		$filterField    = array ();

		NenoLog::log('Processing filtered json data for getStrings', 3);

		foreach ($filterArray as $filterItem)
		{
			if (NenoHelper::startsWith($filterItem, 'group-') !== false)
			{
				$filterGroups[] = str_replace('group-', '', $filterItem);
			}
			elseif (NenoHelper::startsWith($filterItem, 'table-') !== false)
			{
				$filterElements[] = str_replace('table-', '', $filterItem);
			}
			elseif (NenoHelper::startsWith($filterItem, 'field-') !== false)
			{
				$filterField[] = str_replace('field-', '', $filterItem);
			}
		}

		// Set filters into the request.
		$app = JFactory::getApplication();

		$app->setUserState('com_neno.strings.group', $filterGroups);
		$app->setUserState('com_neno.strings.element', $filterElements);
		$app->setUserState('com_neno.strings.field', $filterField);

		/* @var $stringsModel NenoModelStrings */
		$stringsModel = $this->getModel('Strings', 'NenoModel');
		$translations = $stringsModel->getItems();

		echo JLayoutHelper::render('strings', $translations, JPATH_NENO_LAYOUTS);

		JFactory::getApplication()->close();
	}

	/**
	 * Load elements using AJAX
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	public function getElements()
	{
		$input   = JFactory::getApplication()->input;
		$groupId = $input->getInt('group_id');

		if (!empty($groupId))
		{
			/* @var $group NenoContentElementGroup */
			$group  = NenoContentElementGroup::load($groupId);
			$tables = $group->getTables();
			$files  = $group->getLanguageFiles();

			$displayData           = array ();
			$displayData['tables'] = NenoHelper::convertNenoObjectListToJObjectList($tables);
			$displayData['files']  = $files;
			$tablesHTML            = JLayoutHelper::render('multiselecttables', $displayData, JPATH_NENO_LAYOUTS);
			echo $tablesHTML;
		}

		JFactory::getApplication()->close();
	}
}
