<?php
/**
 * @package     Neno
 *
 * @author      Jensen Technologies S.L. <info@notwebdesign.com>
 * @copyright   Copyright (C) 2014 Jensen Technologies S.L. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

$data = $displayData;

// Receive overridable options
$data['options'] = !empty($data['options']) ? $data['options'] : array ();

// Set some basic options
$customOptions = array (
	'filtersHidden'       => false,
	'filterButton'        => false,
	'defaultLimit'        => isset($data['options']['defaultLimit']) ? $data['options']['defaultLimit'] : JFactory::getApplication()->get('list_limit', 20),
	'searchFieldSelector' => '#filter_search',
	'orderFieldSelector'  => '#list_fullordering'
);

$data['options'] = array_unique(array_merge($customOptions, $data['options']));

$formSelector = !empty($data['options']['formSelector']) ? $data['options']['formSelector'] : '#adminForm';

// Load search tools
JHtml::_('searchtools.form', $formSelector, $data['options']);

$filters = $data['view']->filterForm->getGroup('filter');

$document = JFactory::getDocument();
$document->addStyleSheet(JUri::root() . '/media/neno/css/editorfilters.css');
$document->addScript(JUri::root() . '/media/neno/js/editorfilters.js');
?>

<div class="js-stools clearfix">
	<div class="clearfix">
		<div class="js-stools-container-bar">
			<label for="filter_search" class="element-invisible">
				<?php echo JText::_('JSEARCH_FILTER'); ?>
			</label>

			<div class="btn-wrapper input-append">
				<?php echo $filters['filter_search']->input; ?>
				<button type="submit" class="btn hasTooltip"
				        title="<?php echo JHtml::tooltipText('JSEARCH_FILTER_SUBMIT'); ?>">
					<i class="icon-search"></i>
				</button>
			</div>
		</div>
		<div class="js-stools-container-list hidden-phone hidden-tablet">
			<?php echo JLayoutHelper::render('joomla.searchtools.default.list', $data); ?>
		</div>
	</div>
	<!-- Filters div -->
	<div class="js-stools-container-filters hidden-phone clearfix">
		<div class="multiselect-wrapper">
			<?php echo JLayoutHelper::render('simplemultiselect', array ('type' => 'method', 'data' => $data['extraDisplayData']->methods, 'selected' => $data['extraDisplayData']->modelState->get('filter.translator_type')), JPATH_NENO_LAYOUTS); ?>
		</div>
		<div class="multiselect-wrapper">
			<?php echo JLayoutHelper::render('simplemultiselect', array ('type' => 'status', 'data' => $data['extraDisplayData']->statuses, 'selected' => $data['extraDisplayData']->modelState->get('filter.translation_status')), JPATH_NENO_LAYOUTS); ?>
		</div>
		<div class="multiselect-wrapper">
			<?php echo JLayoutHelper::render('multiselectgroup', $data['extraDisplayData'], JPATH_NENO_LAYOUTS); ?>
		</div>
	</div>
	<input type="hidden" id="outputLayout" name="outputLayout" value="editorStrings">
</div>
