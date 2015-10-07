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

JHtml::_('bootstrap.tooltip');
JHtml::_('behavior.multiselect');

// Include the CSS file
$version = NenoHelperBackend::getNenoVersion();
JHtml::stylesheet('media/neno/css/admin.css?v=' . $version);

// Joomla Component Creator code to allow adding non select list filters
if (!empty($this->extraSidebar))
{
	$this->sidebar .= $this->extraSidebar;
}

$workingLanguage = NenoHelper::getWorkingLanguage();

?>

<style>

	.toggler {
		cursor  : pointer;
		width   : 18px;
		border  : 0;
		padding : 10px 0 0 0 !important;

	}

	.toggler .icon-arrow-right-3,
	.toggler .icon-arrow-down-3 {
		color     : #08c;
		font-size : 21px;
	}

	.loading-row {
		background-color    : #fff !important;
		background-image    : url('../media/neno/images/ajax-loader.gif');
		background-position : 40px 8px;
		background-repeat   : no-repeat;
	}

	.group-container {
		padding-bottom : 15px;
		margin-bottom  : 10px;
		border-bottom  : 2px solid #ccc;
	}

	.table-container {
		padding-top : 5px;
		border-top  : 2px solid #dddddd;
		margin-left : 25px;
		display     : none;
	}

	.fields-container {
		display : none;
	}

	.table-groups-elements .cell-expand,
	.table-groups-elements .cell-collapse {
		width : 15px;
	}

	.table-groups-elements .cell-check {
		width : 18px !important;
	}

	.table-groups-elements .cell-check input {
		margin-top : 0;
	}

	.table-groups-elements .cell-expand,
	.table-groups-elements .cell-collapse {
		padding-top    : 10px;
		padding-bottom : 6px;
		cursor         : pointer;
	}

	.table-groups-elements th,
	.table-groups-elements .row-group > td,
	.table-groups-elements .row-table > td {
		background-color : #ffffff !important;
	}

	.table-groups-elements .row-file > td {
		background-color : #ffffff !important;
	}

	.table-groups-elements th {
		border-top : none;
	}

	.type-icon {
		color : #7a7a7a !important;
	}

	.table-groups-elements .row-field {
		background-color : white;
	}

</style>

<script type="text/javascript">

	jQuery(document).ready(function () {
		statusChanged = false;
		//Bind
		bindEvents();

	});


	function bindEvents() {

		// Bind load elements
		jQuery('.toggle-elements').off('click').on('click', toggleElementVisibility);

		// Bind toggle fields
		jQuery('.toggler.toggle-fields').off('click').on('click', toggleFieldVisibility);

		//Bind checking and unchecking checkboxes
		jQuery('#table-groups-elements').find('input[type=checkbox]').off('click').on('click', checkUncheckFamilyCheckboxes);

		//Attach the field translate state toggler
		jQuery('.check-toggle-translate-radio').off('change').on('change', changeFieldTranslateState);

		//Attach the translate state toggler
		jQuery('.check-toggle-translate-table-radio').off('change').on('change', changeTableTranslateState);

		//Bind modal clicks
		jQuery('.modalgroupform').off('click').on('click', showModalGroupForm);

		jQuery("[data-toggle='tooltip']").tooltip();

		jQuery('.filter').off('click').on('click', saveFilter);

		jQuery('#filters-close-button').off('click').on('click', setOldTableStatus);
		jQuery('#nenomodal-table-filters').off('hide').on('hide', setOldTableStatus);
		jQuery('.add-row-button').off('click').on('click', duplicateFilterRow);
		jQuery('.remove-row-button').off('click').on('click', removeFilterRow);
		jQuery('.active.btn-warning').off('click').on('click', function () {
			var forAttribute = jQuery(this).attr('for');
			var regex = new RegExp('check-toggle-translate-table-([0-9]+)\-[0-2]', 'g');
			var result = regex.exec(forAttribute);
			showTableFiltersModal(result[1], 2);
		});

		jQuery('[data-toogle="tooltip"]').tooltip('destroy').tooltip();
	}

	function setOldTableStatus(event) {
		if (!statusChanged) {
			var modal = jQuery('#nenomodal-table-filters');
			var oldStatus = parseInt(modal.data('current-status'));
			var tableId = modal.data('table-id');

			markLabelAsActiveByStatus(tableId, oldStatus, false);
			if (event.type != 'hide') {
				modal.modal('hide');
			}
		}
	}

	function saveFilter(e) {
		e.preventDefault();
		var filter = jQuery(this).data('filter');
		var parent = jQuery(this).closest('.btn-group');
		var fieldId = parent.data('field');

		parent.find('.filter.hide').removeClass('hide');
		parent.find(".filter[data-filter='" + filter + "']").addClass('hide');
		parent.find('.dropdown-toggle').text(filter);

		jQuery.ajax({
			url : 'index.php?option=com_neno&task=groupselements.changeFieldFilter',
			type: 'POST',
			data: {
				fieldId: fieldId,
				filter : filter
			}
		});
	}


	/**
	 * Toggle Elements (Tables and language files)
	 */
	function toggleElementVisibility() {

		var row = jQuery(this).parents('.row-group');
		var id = getGroupIdFromChildElement(jQuery(this));

		//Get the state of the current toggler to see if we need to expand or collapse
		if (jQuery(this).hasClass('toggler-collapsed')) {

			// Expand
			jQuery(this).removeClass('toggler-collapsed').addClass('toggler-expanded').html('<span class="icon-arrow-down-3"></span>');

			// Show a loader row while loading
			row.after('<tr id="loader-' + id + '"><td colspan="9" class="loading-row">&nbsp;</td></tr>');

			jQuery.ajax({
					beforeSend: onBeforeAjax,
					url       : 'index.php?option=com_neno&task=groupselements.getElements&group_id=' + id,
					success   : function (html) {
						jQuery('#loader-' + id).replaceWith(html);

						//Bind events to new fields
						bindEvents();
					}
				}
			);
		} else {

			//Collapse
			jQuery(this).removeClass('toggler-expanded').addClass('toggler-collapsed').html('<span class="icon-arrow-right-3"></span>');

			//Remove children
			jQuery('[data-parent="' + id + '"]').remove();
			jQuery('[data-grandparent="' + id + '"]').remove();

		}

	}

	function toggleFieldVisibility() {

		var row = jQuery(this).parent('.row-table');
		var id_parts = row.attr('data-id').split('-');
		var id = id_parts[1];

		//Get the state of the current toggler to see if we need to expand or collapse
		if (jQuery(this).hasClass('toggler-collapsed')) {

			// Expand
			jQuery(this).removeClass('toggler-collapsed').addClass('toggler-expanded').html('<span class="icon-arrow-down-3"></span>');

			jQuery('[data-parent="' + id + '"]').show();

		} else {

			//Collapse
			jQuery(this).removeClass('toggler-expanded').addClass('toggler-collapsed').html('<span class="icon-arrow-right-3"></span>');

			//hide children
			jQuery('[data-parent="' + id + '"]').hide();

		}

	}


	function changeFieldTranslateState() {

		var id = jQuery(this).parent('fieldset').attr('data-field');
		var status = jQuery(this).val();

		if (status == 1) {
			jQuery(this).parents('.row-field').find('.bar').removeClass('bar-disabled');
			jQuery('[for="check-toggle-translate-' + id + '-1"]').addClass('active btn-success');
			jQuery('[for="check-toggle-translate-' + id + '-0"]').removeClass('active btn-danger');
		} else {
			jQuery(this).parents('.row-field').find('.bar').addClass('bar-disabled');
			jQuery('[for="check-toggle-translate-' + id + '-0"]').addClass('active btn-danger');
			jQuery('[for="check-toggle-translate-' + id + '-1"]').removeClass('active btn-success');
		}

		//Show an alert that count no longer is accurate
		jQuery('#reload-notice').remove();
		jQuery('.navbar-fixed-top .navbar-inner').append('<div style="padding:10px 30px;" id="reload-notice"><div class="alert alert-warning"><?php echo JText::_('COM_NENO_VIEW_GROUPSELEMENTS_RELOAD_WARNING'); ?><a href="index.php?option=com_neno&view=groupselements" class="btn btn-info pull-right" style="height: 16px; font-size: 12px;margin-top:-4px"><?php echo JText::_('COM_NENO_VIEW_GROUPSELEMENTS_RELOAD_BTN'); ?></a></div></div>').height('92');
		jQuery('body').css('padding-top', '93px');

		jQuery.ajax({
				beforeSend: onBeforeAjax,
				url       : 'index.php?option=com_neno&task=groupselements.toggleContentElementField&fieldId=' + id + '&translateStatus=' + status
			}
		);
	}


	function changeTableTranslateState() {

		var id = jQuery(this).parent('fieldset').attr('data-field');
		var status = parseInt(jQuery(this).val());

		if (!jQuery('[for="check-toggle-translate-table-' + id + '-' + status + '"]').hasClass('active')) {
			markLabelAsActiveByStatus(id, status, true);

			if (status != 2) {
				setTranslateStatus(id, status);
			}
		}
	}

	function setTranslateStatus(tableId, status) {
		//Show an alert that count no longer is accurate
		jQuery('#reload-notice').remove();
		jQuery('.navbar-fixed-top .navbar-inner').append('<div style="padding:10px 30px;" id="reload-notice"><div class="alert alert-warning"><?php echo JText::_('COM_NENO_VIEW_GROUPSELEMENTS_RELOAD_WARNING'); ?><a href="index.php?option=com_neno&view=groupselements" class="btn btn-info pull-right" style="height: 16px; font-size: 12px;margin-top:-4px"><?php echo JText::_('COM_NENO_VIEW_GROUPSELEMENTS_RELOAD_BTN'); ?></a></div></div>').height('92');
		jQuery('body').css('padding-top', '93px');

		jQuery.ajax({
				beforeSend: onBeforeAjax,
				url       : 'index.php?option=com_neno&task=groupselements.toggleContentElementTable&tableId=' + tableId + '&translateStatus=' + status
			}
		);
	}

	function markLabelAsActiveByStatus(id, status, showFiltersModal) {
		var row = jQuery('.row-table[data-id="table-' + id + '"]');
		var toggler = row.find('.toggle-fields');
		switch (status) {
			case 1:
				row.find('.bar').removeClass('bar-disabled');
				jQuery('[for="check-toggle-translate-table-' + id + '-1"]').addClass('active btn-success');
				jQuery('[for="check-toggle-translate-table-' + id + '-0"]').removeClass('active btn-danger');
				jQuery('[for="check-toggle-translate-table-' + id + '-2"]').removeClass('active btn-warning');

				//Add field toggler
				toggler.off('click').on('click', toggleFieldVisibility);
				toggler.addClass('toggler toggler-collapsed');
				toggler.find('span').addClass('icon-arrow-right-3');
				break;
			case 2:
				row.find('.bar').removeClass('bar-disabled');
				var currentStatus = jQuery(".active[for|='check-toggle-translate-table-" + id + "']").attr('for').replace('check-toggle-translate-table-' + id + '-', '');
				jQuery('[for="check-toggle-translate-table-' + id + '-1"]').removeClass('active btn-success');
				jQuery('[for="check-toggle-translate-table-' + id + '-0"]').removeClass('active btn-danger');
				jQuery('[for="check-toggle-translate-table-' + id + '-2"]').addClass('active btn-warning');

				//Add field toggler
				toggler.off('click').on('click', toggleFieldVisibility);
				toggler.addClass('toggler toggler-collapsed');
				toggler.find('span').addClass('icon-arrow-right-3');

				if (showFiltersModal) {
					showTableFiltersModal(id, currentStatus);
				}

				break;
			case 0:
				row.find('.bar').addClass('bar-disabled');
				jQuery('[for="check-toggle-translate-table-' + id + '-0"]').addClass('active btn-danger');
				jQuery('[for="check-toggle-translate-table-' + id + '-1"]').removeClass('active btn-success');
				jQuery('[for="check-toggle-translate-table-' + id + '-2"]').removeClass('active btn-warning');

				//Remove fields
				if (toggler.hasClass('toggler-expanded')) {
					toggler.click();
				}
				toggler.off('click');
				toggler.removeClass('toggler toggler-collapsed');
				toggler.find('span').removeClass();
				break;
		}

		jQuery('#check-toggle-translate-table-' + id + '-' + status).click();
	}

	function showTableFiltersModal(id, currentStatus) {
		//Load group form html
		jQuery.ajax({
				beforeSend: onBeforeAjax,
				url       : 'index.php?option=com_neno&task=groupselements.getTableFilterModalLayout&tableId=' + id,
				success   : function (html) {

					statusChanged = false;

					//Inject HTML into the modal
					var modal = jQuery('#nenomodal-table-filters');
					modal.data('current-status', currentStatus);
					modal.data('table-id', id);
					modal.find('.modal-body').html(html);
					modal.modal('show');

					// Bind events
					bindEvents();

					//Handle saving and submitting the form
					jQuery('#save-filters-btn').off('click').on('click', saveTableFilters);
				}
			}
		);
	}

	function duplicateFilterRow() {
		jQuery(this).closest('tr').clone().appendTo('#filters-table');
		bindEvents();
	}

	function removeFilterRow() {
		if (jQuery('tr.filter-row').length > 1) {
			jQuery(this).closest('tr').remove();
		}
	}

	function saveTableFilters() {
		var filters = [];

		jQuery('tr.filter-row').each(function () {
			// Only include if the filter contains any value
			if (jQuery(this).find('.filter-value').val()) {
				var filter = {
					field   : jQuery(this).find('.filter-field option:selected').val(),
					operator: jQuery(this).find('.filter-operator option:selected').val(),
					value   : jQuery(this).find('.filter-value').val()
				};

				filters.push(filter);
			}
		});

		if (filters.length != 0) {
			jQuery.post(
				'index.php?option=com_neno&task=groupselements.saveTableFilters',
				{
					filters: filters,
					tableId: jQuery('#nenomodal-table-filters').data('table-id')
				},
				function (data) {
					if (data = 'ok') {
						setTranslateStatus(jQuery('#nenomodal-table-filters').data('table-id'), 2);

						statusChanged = true;
						var modal = jQuery('#nenomodal-table-filters');
						modal.modal('hide');
					}
				}
			);


		}
	}


	/**
	 * Check and uncheck checkboxes
	 *  - Parent click: check/uncheck all children
	 *  - Child click: uncheck parent if unchecked
	 */
	function checkUncheckFamilyCheckboxes() {

		//Set some vars
		var state = jQuery(this).prop('checked');
		var this_data_id = jQuery(this).closest('tr').attr('data-id');
		var this_parts = this_data_id.split('-');
		var this_id = this_parts[1];

		//Check uncheck all children
		jQuery('[data-parent="' + this_id + '"]').find('input[type=checkbox]').prop('checked', state);

		//Uncheck parents
		if (state === false) {
			var parent_id = jQuery('[data-id="' + this_data_id + '"').attr('data-parent');
			if (parent_id) {
				jQuery('[data-id="group-' + parent_id + '"]').find('input[type=checkbox]').prop('checked', false);
			}
		}

		// Make available to Joomla if a checkbox is checked to prevent submitting without a checked item
		Joomla.isChecked(state);
	}


	function showModalGroupForm(isNew) {
		var id = 0;
		if (isNew !== true) {
			id = getGroupIdFromChildElement(jQuery(this));
		}

		//Load group form html
		jQuery.ajax({
				beforeSend: onBeforeAjax,
				url       : 'index.php?option=com_neno&view=groupelement&id=' + id + '&format=raw',
				success   : function (html) {

					//Inject HTML into the modal
					var modal = jQuery('#nenomodal');
					modal.find('.modal-body').html(html);
					modal.modal('show');

					//Handle saving and submitting the form
					jQuery('#save-modal-btn').off('click').on('click', function () {
						jQuery('#groupelement-form').submit();
					});
				}
			}
		);
	}


	/**
	 * Helpers
	 */
	function getGroupIdFromChildElement(e) {

		var row = e.parents('.row-group');
		var id_parts = row.data('id');
		var id = 0;

		if (typeof id_parts != 'undefined') {
			id_parts = id_parts.split('-');
			id = id_parts[1];
		}

		return id;

	}

	//Catch the joomla submit
	var originalJoomla = Joomla.submitbutton;
	Joomla.submitbutton = function (task) {
		if (task === 'addGroup') {
			showModalGroupForm(true);
		} else {
			//Submit as normal
			originalJoomla.apply(this, arguments);
		}

	}

</script>

<!-- Empty hidden modal -->
<div class="modal fade" id="nenomodal" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h2 class="modal-title"
					id="nenomodaltitle"><?php echo JText::_('COM_NENO_VIEW_GROUPSELEMENTS_MODAL_GROUPFORM_TITLE'); ?></h2>
			</div>
			<div class="modal-body">
				...
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default"
					data-dismiss="modal"><?php echo JText::_('COM_NENO_VIEW_GROUPSELEMENTS_MODAL_GROUPFORM_BTN_CLOSE'); ?></button>
				<button type="button" class="btn btn-primary"
					id="save-modal-btn"><?php echo JText::_('COM_NENO_VIEW_GROUPSELEMENTS_MODAL_GROUPFORM_BTN_SAVE'); ?></button>
			</div>
		</div>
	</div>
</div>

<!-- Empty hidden modal -->
<div class="modal fade" id="nenomodal-table-filters" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h2 class="modal-title"
					id="nenomodaltitle"><?php echo JText::_('COM_NENO_VIEW_GROUPSELEMENTS_MODAL_GROUPFORM_TITLE'); ?></h2>
			</div>
			<div class="modal-body">
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" id="filters-close-button">
					<?php echo JText::_('COM_NENO_VIEW_GROUPSELEMENTS_MODAL_GROUPFORM_BTN_CLOSE'); ?>
				</button>
				<button type="button" class="btn btn-primary" id="save-filters-btn">
					<?php echo JText::_('COM_NENO_VIEW_GROUPSELEMENTS_MODAL_GROUPFORM_BTN_SAVE'); ?>
				</button>
			</div>
		</div>
	</div>
</div>

<form action="<?php echo JRoute::_('index.php?option=com_neno&view=groupselements'); ?>" method="post" name="adminForm"
	id="adminForm">

	<?php if (!empty($this->sidebar)): ?>
	<div id="j-sidebar-container" class="span2">
		<?php echo $this->sidebar; ?>
	</div>
	<div id="j-main-container" class="span10">
		<?php else : ?>
		<div id="j-main-container">
			<?php endif; ?>
			<table class="table table-striped table-groups-elements" id="table-groups-elements">
				<tr class="row-header" data-level="0" data-id="header">
					<th></th>
					<th class="cell-check"></th>
					<th colspan="3"
						class="group-label"><?php echo JText::_('COM_NENO_VIEW_GROUPSELEMENTS_GROUPS'); ?></th>
					<th class="table-groups-elements-label"><?php echo JText::_('COM_NENO_VIEW_GROUPSELEMENTS_ELEMENTS'); ?></th>
					<th class="table-groups-elements-label"><?php echo JText::_('COM_NENO_VIEW_GROUPSELEMENTS_COUNT'); ?></th>
					<th class="table-groups-elements-label translation-methods"><?php echo JText::_('COM_NENO_VIEW_GROUPSELEMENTS_METHODS'); ?></th>
					<th class="table-groups-elements-blank"></th>
				</tr>
				<?php foreach ($this->items as $group): ?>
					<tr class="row-group" data-id="group-<?php echo $group->id; ?>">
						<td class="toggler toggler-collapsed toggle-elements"><span class="icon-arrow-right-3"></span>
						</td>
						<td class="cell-check"><input type="checkbox" name="groups[]"
								value="<?php echo $group->id; ?>" /></td>
						<td colspan="3"><a href="#" class="modalgroupform"><?php echo $group->group_name; ?></a></td>
						<td<?php echo ($group->element_count) ? ' class="load-elements"' : ''; ?>><?php echo $group->element_count; ?></td>
						<td><?php echo NenoHelper::renderWordCountProgressBar($group->word_count); ?></td>
						<td>
							<a href="#" class="modalgroupform">
								<?php if (empty($group->assigned_translation_methods)): ?>
									<?php echo JText::_('COM_NENO_VIEW_GROUPSELEMENTS_ADD_TRANSLATION_METHOD'); ?>
								<?php else: ?>
									<?php echo NenoHelperBackend::renderTranslationMethodsAsCSV($group->assigned_translation_methods); ?>
								<?php endif; ?>
							</a>
						</td>
						<td></td>
					</tr>
				<?php endforeach; ?>
			</table>

			<input type="hidden" name="task" value="" />
			<input type="hidden" name="boxchecked" value="0" />
			<?php echo JHtml::_('form.token'); ?>

		</div>

</form>

<?php echo NenoHelperBackend::renderVersionInfoBox(); ?>

