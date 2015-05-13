<?php
/**
 * @package     Neno
 * @subpackage  Models
 *
 * @author      Jensen Technologies S.L. <info@notwebdesign.com>
 * @copyright   Copyright (C) 2014 Jensen Technologies S.L. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

/**
 * NenoModelGroupsElements class
 *
 * @since  1.0
 */
class NenoModelExternalTranslations extends JModelList
{
	/**
	 * Get the amount of link credits available
	 *
	 * @return int
	 */
	public function getTCAvailable()
	{
		return NenoHelperApi::getTCAvailable();
	}

	public function getTCNeeded()
	{
		$db    = JFactory::getDbo();
		$query = $this->getListQuery();

		$query
			->clear('select')
			->select('SUM(tr.word_counter * tm.pricing_per_word) AS tc');

		$db->setQuery($query);

		return (int) $db->loadResult();
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return    JDatabaseQuery
	 *
	 * @since    1.6
	 */
	protected function getListQuery()
	{
		$query = parent::getListQuery();

		$query
			->select(
				array (
					'SUM(word_counter) AS words',
					'trtm.translation_method_id',
					'language'
				)
			)
			->from('#__neno_content_element_translations AS tr')
			->innerJoin('#__neno_content_element_translation_x_translation_methods AS trtm ON trtm.translation_id = tr.id')
			->innerJoin('#__neno_translation_methods AS tm ON trtm.translation_method_id = tm.id')
			->where(
				array (
					'state = ' . NenoContentElementTranslation::NOT_TRANSLATED_STATE,
					'NOT EXISTS (SELECT 1 FROM #__neno_jobs_x_translations AS jt WHERE tr.id = jt.translation_id)',
					'tm.pricing_per_word <> 0',
					'trtm.ordering = 1'
				)
			)
			->group(
				array (
					'trtm.translation_method_id',
					'language'
				)
			);

		return $query;
	}
}
