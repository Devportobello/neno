<?php
/**
 * @package     Neno
 * @subpackage  Controllers
 *
 * @author      Jensen Technologies S.L. <info@notwebdesign.com>
 * @copyright   Copyright (C) 2014 Jensen Technologies S.L. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.controller');

/**
 * Source controller class.
 *
 * @since  1.0
 */
class NenoControllerLangfiles extends JControllerLegacy
{

	/**
	 * Export translations to language file
	 *
	 * @return void
	 */
	public function export()
	{
		/* @var $model NenoModelLangfiles */
		$model = $this->getModel('Langfiles');
		$model->export();
	}

	/**
	 * Looks in all language files and imports any strings that have not been imported as well as marks deleted or
	 * changed
	 *
	 * @return void
	 */
	public function import()
	{
		JSession::checkToken() or die('Invalid Token');

		/* @var $model NenoModelLangfiles */
		$model = $this->getModel('Langfiles');
		$model->import();

		// Check to see if there are any changes to target language files as we will have to redirect to a page where
		// the user can chose what to do with them
		$changed_strings = $model->getChangedStringsInLangfiles('target');

		if (count($changed_strings))
		{
			$this->setRedirect(JRoute::_('index.php?option=com_neno&view=langfilesimporttargetchanges', false));
		}
		else
		{
			$this->setRedirect(JRoute::_('index.php?option=com_neno&view=langfilesimport', false));
		}
	}

	/**
	 * Move strings from files to database
	 *
	 * @return void
	 */
	public function pullTargetStrings()
	{
		$cid = JFactory::getApplication()->input->post->get('cid', array(), 'array');

		/* @var $model NenoModelLangfilesimporttargetchanges */
		$model = NenoHelper::getModel('Langfilesimporttargetchanges');
		$model->updateTargetStrings($cid, 'pull');

		$this->setRedirect(JRoute::_('index.php?option=com_neno&view=langfilesimport', false));
	}

	/**
	 * Move strings from database to files
	 *
	 * @return void
	 */
	public function pushTargetStrings()
	{
		$cid = JFactory::getApplication()->input->post->get('cid', array(), 'array');

		/* @var $model NenoModelLangfilesimporttargetchanges */
		$model = NenoHelper::getModel('Langfilesimporttargetchanges');
		$model->updateTargetStrings($cid, 'push');

		$this->setRedirect(JRoute::_('index.php?option=com_neno&view=langfilesimport', false));
	}

	/**
	 * Cancel action
	 *
	 * @return void
	 */
	public function cancel()
	{
		$this->setRedirect(JRoute::_('index.php?option=com_neno&view=dashboard', false));
	}

	/**
	 * Refresh action
	 *
	 * @return void
	 */
	public function refresh()
	{
		$this->setRedirect(JRoute::_('index.php?option=com_neno&view=langfilesimport', false));
	}
}

