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
		NenoLog::log('Method getStrings of NenoControllerStrings called', 3);
		$input          = JFactory::getApplication()->input;
		$filterJson     = $input->getString('jsonGroupsElements');
		$filterArray    = json_decode($filterJson);
		$filterGroups   = array ();
		$filterElements = array ();
		$filterField    = array ();
		$filterFile     = array ();
		$filterMethods  = array ();
		$filterStatus   = array ();
		$outputLayout   = strtolower($input->getString('outputLayout'));
		$filterSearch   = strtolower($input->get('filter_search', '', 'RAW'));
		$app            = JFactory::getApplication();

		NenoLog::log('Processing filtered json data for NenoControllerStrings::getStrings', 3);
        
        if (!empty($filterArray))
        {
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
                elseif (NenoHelper::startsWith($filterItem, 'file-') !== false)
                {
                    $filterFile[] = str_replace('file-', '', $filterItem);
                }
            }
        }

		// Set filters into the request.
		$input->set('group', $filterGroups);
		$input->set('table', $filterElements);
		$input->set('field', $filterField);
		$input->set('file', $filterFile);
		$input->set('list', array ('limit' => $input->get('limit'), 'start' => $input->get('limitStart')));
		$input->set('limitstart', $input->get('limitStart'));

		$filterJson  = $input->getString('jsonMethod');
		$filterArray = json_decode($filterJson);
        
        if (!empty($filterArray))
        {
            foreach ($filterArray as $filterItem)
            {
                $filterMethods[] = str_replace('method-', '', $filterItem);
            }
        }

		$input->set('type', $filterMethods);

		$filterJson  = $input->getString('jsonStatus');
		$filterArray = json_decode($filterJson);
        
        if (!empty($filterArray))
        {
            foreach ($filterArray as $filterItem)
            {
                $filterStatus[] = (int) str_replace('status-', '', $filterItem);
            }
        }

		$input->set('status', $filterStatus);

		$input->set('filter_search', $filterSearch);

		$app->setUserState('limit', $input->getInt('limit', 20));
		$app->setUserState('limitStart', $input->getInt('limitStart', 0));

		/* @var $stringsModel NenoModelStrings */
		$stringsModel = $this->getModel();
		$translations = $stringsModel->getItems();

		$displayData               = new stdClass;
		$displayData->translations = $translations;
		$displayData->state        = $stringsModel->getState();
		$displayData->pagination   = $stringsModel->getPagination();

		echo JLayoutHelper::render($outputLayout, $displayData, JPATH_NENO_LAYOUTS);

		JFactory::getApplication()->close();
	}

	/**
	 * Get model
	 *
	 * @param   string $name   Model Name
	 * @param   string $prefix Model Prefix
	 * @param   array  $config Model configuration
	 *
	 * @return NenoModelStrings
	 */
	public function getModel($name = 'Strings', $prefix = 'NenoModel', $config = array ())
	{
		return parent::getModel($name, $prefix, $config);
	}

	/**
	 * Redirects to the editor using the same filters applied into the string view
	 *
	 * @return void
	 */
	public function translateTheseStringsTask()
	{
		/* @var $stringsModel NenoModelStrings */
		$stringsModel = $this->getModel();

		$state     = (array) $stringsModel->getState();
		$queryVars = array (
			'option' => 'com_neno',
			'view'   => 'editor'
		);

		foreach ($state as $filter => $options)
		{
			switch ($filter)
			{
				case 'filter.translator_type':
					$queryVars['type'] = $options;
					break;
				case 'filter.translation_status':
					$queryVars['status'] = $options;
					break;
				case 'filter.group_id':
					$queryVars['group'] = $options;
					break;
				case 'filter.element':
					$queryVars['table'] = $options;
					break;
				case 'filter.field':
					$queryVars['field'] = $options;
					break;
			}
		}

		if (!empty($queryVars['field']))
		{
			unset($queryVars['group']);
			unset($queryVars['table']);
		}

		if (!empty($queryVars['table']))
		{
			unset($queryVars['group']);
		}

		$query = '';

		foreach ($queryVars as $queryVarName => $queryVarValue)
		{
			if (!empty($queryVarValue))
			{
				if (is_array($queryVarValue))
				{
					foreach ($queryVarValue as $queryVarRealValue)
					{
						if (!empty($queryVarRealValue))
						{
							$query .= $queryVarName . '[]=' . urlencode($queryVarRealValue) . '&';
						}
					}
				}
				else
				{
					$query .= $queryVarName . '=' . urlencode($queryVarValue) . '&';
				}
			}
		}

		JFactory::getApplication()->redirect('index.php?' . substr($query, 0, strlen($query) - 1));
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
			$tables = $group->getTables(false, false, true);
			$files  = $group->getLanguageFiles();

			$displayData = array ();

			/* @var $model NenoModelStrings */
			$model                 = $this->getModel();
			$displayData['tables'] = NenoHelper::convertNenoObjectListToJobjectList($tables);

			//Remove fields marked as Don't translate
			foreach ($displayData['tables'] as $table)
			{
				foreach ($table->fields as $key => $field)
				{
					if (!$field->translate)
					{
						unset($table->fields[$key]);
					}
				}
			}
			$displayData['files']  = NenoHelper::convertNenoObjectListToJobjectList($files);
			$displayData['state']  = $model->getState();
			$tablesHTML            = JLayoutHelper::render('multiselecttables', $displayData, JPATH_NENO_LAYOUTS);
			echo $tablesHTML;
		}

		JFactory::getApplication()->close();
	}
}
