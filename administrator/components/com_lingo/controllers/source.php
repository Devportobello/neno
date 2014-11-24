<?php
/**
 * @package     Lingo
 * @subpackage  Controllers
 *
 * @author      Jensen Technologies S.L. <info@notwebdesign.com>
 * @copyright   Copyright (C) 2014 Jensen Technologies S.L. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.controllerform');

/**
 * Source controller class.
 *
 * @since  1.0
 */
class LingoControllerSource extends JControllerForm
{
	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->view_list = 'sources';
		parent::__construct();
	}
}
