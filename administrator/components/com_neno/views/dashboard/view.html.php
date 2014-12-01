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
class NenoViewDashboard extends JViewLegacy
{
	/**
	 * Display the view
	 *
	 * @param   string  $tpl  Template to render
	 *
	 * @return void
	 *
	 * @since 1.0
	 */
	public function display($tpl = null)
	{
  		JToolBarHelper::title(NenoHelper::getAdminTitle(), 'nope');

		parent::display($tpl);
	}

}
