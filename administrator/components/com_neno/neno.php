<?php
/**
 * @package    Neno
 *
 * @author     Jensen Technologies S.L. <info@notwebdesign.com>
 * @copyright  Copyright (C) 2014 Jensen Technologies S.L. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

// Access check.
if (!JFactory::getUser()->authorise('core.manage', 'com_neno'))
{
	throw new Exception(JText::_('JERROR_ALERTNOAUTHOR'));
}

if (!NenoHelper::isTheDatabaseDriverEnable())
{
	$app = JFactory::getApplication();
	$app->enqueueMessage('Please enable the plugin to use Neno', 'error');
	$app->setUserState('com_plugins.plugins.filter.search', 'neno');
	$app->redirect('index.php?option=com_plugins');
}

// Include dependencies
jimport('joomla.application.component.controller');

$controller = JControllerLegacy::getInstance('Neno');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();
