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

/**
 * NenoViewGroupsElements class
 *
 * @since  1.0
 */
class NenoViewDashboard extends JViewLegacy
{
	/**
	 * @var array
	 */
	protected $items;

	/**
	 * @var Joomla\Registry\Registry
	 */
	protected $state;

	/**
	 * @var string
	 */
	protected $sidebar;


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
		$this->state = $this->get('State');
		$this->items = $this->get('Items');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors));
		}

		NenoHelper::addSubmenu('dashboard');

		$this->addToolbar();

		$this->sidebar = JHtmlSidebar::render();

		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return void
	 *
	 * @since    1.6
	 */
	protected function addToolbar()
	{
		$canDo = NenoHelper::getActions();

		if ($canDo->get('core.admin'))
		{
			JToolBarHelper::preferences('com_neno');
		}

		// Set sidebar action - New in 3.0
		JHtmlSidebar::setAction('index.php?option=com_neno&view=groupselements');

		$this->extra_sidebar = '';
	}
}
