<?php
/**
 * @package     Neno
 * @subpackage  Views
 *
 * @author      Jensen Technologies S.L. <info@notwebdesign.com>
 * @copyright   Copyright (C) 2014 Jensen Technologies S.L. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.view');

/**
 * View to edit
 *
 * @since  1.0
 */
class NenoViewLangfilesImport extends JViewLegacy
{
	/**
	 * @var JLanguage
	 */
	protected $sourceLanguage;

	/**
	 * @var array
	 */
	protected $sourceCounts;

	/**
	 * @var array
	 */
	protected $newTargetStrings;

	/**
	 * @var array
	 */
	protected $changedTargetStrings;

	/**
	 * @var boolean
	 */
	protected $changesPending;

	/**
	 * Constructor
	 *
	 * @param   array $config Configuration parameters
	 */
	public function __construct($config = array())
	{
		parent::__construct($config);

		$this->sourceLanguage       = null;
		$this->sourceCounts         = array();
		$this->newTargetStrings     = array();
		$this->changedTargetStrings = array();
		$this->changesPending       = false;
	}

	/**
	 * Display the view
	 *
	 * @param   string $tpl Template
	 *
	 * @return void
	 *
	 * @throws Exception This will happen if there are errors during the process to load the data
	 *
	 * @since 1.0
	 */
	public function display($tpl = null)
	{
		$language             = JFactory::getLanguage();
		$this->sourceLanguage = $language->getDefault();

		/* @var $model NenoModelLangfiles */
		$model = NenoHelper::getModel('Langfiles');

		$this->sourceCounts['new_source_lines']     = $model->getNewStringsInLanguageFiles(NenoContentElementLangstring::SOURCE_LANGUAGE_TYPE);
		$this->sourceCounts['deleted_source_lines'] = $model->getDeletedSourceStringsInLangfiles();
		$this->sourceCounts['updated_source_lines'] = $model->getChangedStringsInLangFiles(NenoContentElementLangstring::SOURCE_LANGUAGE_TYPE);
		$this->newTargetStrings                     = $model->getNewStringsInLanguageFiles(NenoContentElementLangstring::TARGET_LANGUAGE_TYPE);
		$this->changedTargetStrings                 = $model->getChangedStringsInLangFiles(NenoContentElementLangstring::TARGET_LANGUAGE_TYPE);

		// Check for changes
		if (count($this->sourceCounts['new_source_lines'][$this->sourceLanguage])
			|| count($this->sourceCounts['deleted_source_lines'][$this->sourceLanguage])
			|| count($this->sourceCounts['updated_source_lines'][$this->sourceLanguage])
		)
		{
			$this->changesPending = true;
		}

		for ($i = 0; $i < count($this->newTargetStrings) && !$this->changesPending; $i++)
		{
			if (count($this->newTargetStrings[$i]))
			{
				$this->changesPending = true;
			}
		}

		$this->addToolbar();

		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return void
	 */
	protected function addToolbar()
	{
		JFactory::getApplication()->input->set('hidemainmenu', true);
		$canDo = NenoHelper::getActions();

		JToolBarHelper::title(JText::_('COM_NENO_LANGFILES_IMPORT_TITLE'), 'download.png');

		JToolBarHelper::custom('langfiles.import', 'download', 'download', 'COM_NENO_VIEW_LANGFILESIMPORT_BTN_IMPORT', false);
		JToolBarHelper::custom('langfiles.refresh', 'redo-2', 'redo-2', 'COM_NENO_VIEW_LANGFILESIMPORT_BTN_REFRESH', false);
		JToolBarHelper::cancel('langfiles.cancel');

		if ($canDo->get('core.admin'))
		{
			JToolBarHelper::preferences('com_neno');
		}
	}
}
