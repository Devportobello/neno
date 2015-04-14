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
class NenoModelGroupElement extends JModelAdmin
{
	/**
	 * Get the JTable class related to this view
	 *
	 * @param   string $type   JTable type
	 * @param   string $prefix JTable prefix
	 * @param   array  $config JTable configuration
	 *
	 * @return mixed
	 */
	public function getTable($type = 'GroupElement', $prefix = 'NenoTable', $config = array ())
	{
		return JTable::getInstance($type, $prefix, $config);
	}

	/**
	 * Method to get the record form.
	 *
	 * @param   array   $data     An optional array of data for the form to interrogate.
	 * @param   boolean $loadData True if the form is to load its own data (default case), false if not.
	 *
	 * @return JForm
	 */
	public function getForm($data = array (), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_neno.groupelement', 'groupelement', array ('control' => 'jform', 'load_data' => $loadData));

		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param   array $data Data to save
	 *
	 * @return bool
	 *
	 * @throws Exception
	 */
	public function save($data)
	{
		// If groups data has been saved, let's assign translation method
		if (parent::save($data))
		{
			$db              = JFactory::getDbo();
			$query           = $db->getQuery(true);
			$workingLanguage = NenoHelper::getWorkingLanguage();
			$groupId         = (int) $this->getState($this->getName() . '.id');

			$query
				->delete('#__neno_content_element_presets')
				->where(
					array (
						'group_id = ' . $groupId,
						'lang = ' . $db->quote($workingLanguage)
					)
				);

			$db->setQuery($query);
			$db->execute();

			$query
				->clear()
				->insert('#__neno_content_element_presets')
				->columns(
					array (
						'group_id',
						'lang',
						'translation_method',
						'ordering'
					)
				);

			$insert = false;

			if (!empty($data['translation_methods']))
			{
				$ordering = 1;
				
				foreach ($data['translation_methods'] as $translationMethodId)
				{
					$insert = true;
					$query->values($groupId . ',' . $db->quote($workingLanguage) . ',' . $db->quote($translationMethodId) . ', ' . $ordering);
					$ordering++;
				}
			}

			if ($insert)
			{
				$db->setQuery($query);
				$db->execute();
			}

			return true;
		}

		return false;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return mixed
	 */
	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = JFactory::getApplication()->getUserState('com_neno.edit.groupelement.data', array ());

		if (empty($data))
		{
			$data = $this->getItem();

		}

		return $data;
	}

	/**
	 * Load item
	 *
	 * @param null|mixed $pk Pk data
	 *
	 * @return JObject
	 */
	public function getItem($pk = null)
	{
		$item = parent::getItem();

		if (!empty($item->id))
		{
			/* @var $group NenoContentElementGroup */
			$group           = NenoContentElementGroup::load($item->id);
			$workingLanguage = NenoHelper::getWorkingLanguage();

			$item = $group->prepareDataForView();

			// Get presets
			$db    = JFactory::getDbo();
			$query = $db->getQuery(true);

			$query
				->select(
					array (
						'translation_method',
						'ordering'
					)
				)
				->from('#__neno_content_element_presets')
				->where(
					array (
						'group_id = ' . $item->get('id'),
						'lang = ' . $db->quote($workingLanguage)
					)
				);

			$db->setQuery($query);
			$presetsData = $db->loadAssocList();

			foreach ($presetsData as $presetData)
			{
				$item->{'translation_method_' . $presetData['ordering']} = $presetData['translation_method'];
			}
		}

		return $item;
	}
}
