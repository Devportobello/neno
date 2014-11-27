<?php
/**
 * @package     Lingo
 * @subpackage  Helpers
 *
 * @author      Jensen Technologies S.L. <info@notwebdesign.com>
 * @copyright   Copyright (C) 2014 Jensen Technologies S.L. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;

jimport('joomla.log.log');

/**
 * Lingo debug helper
 *
 * @since  1.0
 */
class LingoLog extends JLog
{
	/**
	 * A static method that allows logging of errors and messages
	 *
	 * @param   string  $string          The log line that should be saved
	 * @param   integer $level           1=error, 2=info, 3=debug
	 * @param   boolean $display_message Weather or not the logged message should be displayed to the user
	 *
	 * @return bool true on success
	 */
	public static function log($string, $level = 2, $display_message = false)
	{
		// Add an extra tab to debug messages
		if ($level > 2)
		{
			$string = "\t" . $string;
		}

		// Get jLog priority
		$priority = self::getJLogPriorityFromDebugLevel($level);

		// Setup the logging method
		self::setLogMethod();

		// Add the log entry
		self::add($string, $priority, 'com_lingo');

		if ($display_message === true)
		{
			JFactory::getApplication()->enqueueMessage($string);
		}

		return true;

	}

	/**
	 * Convert our simple priority 1,2,3 to appropriate JLog error integer
	 *
	 * @param   integer $priority 1,2 or 3
	 *
	 * @return int JLog priority integer
	 */
	private static function getJLogPriorityFromDebugLevel($priority)
	{
		if ($priority == 1)
		{
			return self::ERROR;
		}
		else
		{
			if ($priority == 2)
			{
				return self::INFO;
			}
			else
			{
				return self::DEBUG;
			}
		}
	}

	/**
	 *Set Log method
	 *
	 * @return void
	 */
	public static function setLogMethod()
	{
		$options['text_entry_format'] = "{DATETIME}\t{PRIORITY}\t\t{MESSAGE}";
		$options['text_file']         = 'lingo_log.php';

		self::addLogger(
			$options,
			self::ALL,
			array( 'com_lingo' )
		);
	}
}
