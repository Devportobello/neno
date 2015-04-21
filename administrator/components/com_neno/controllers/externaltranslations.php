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
 * Manifest Groups & Elements controller class
 *
 * @since  1.0
 */
class NenoControllerExternalTranslations extends JControllerAdmin
{
	/**
	 * Task to set value for automatic translations
	 *
	 * @return void
	 */
	public function setAutomaticTranslationSetting()
	{
		$app   = JFactory::getApplication();
		$input = $app->input;

		$setting = $input->post->getString('setting');
		$value   = $input->post->getInt('value');

		if (!empty($setting))
		{
			echo NenoSettings::set($setting, $value) ? 'ok' : 'err';
		}
		else
		{
			echo 'err';
		}

		$app->close();
	}
}
