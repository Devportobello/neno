/**
 * @author      Jensen Technologies S.L. <info@notwebdesign.com>
 * @copyright   Copyright (C) 2014 Jensen Technologies S.L. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

//Create global javascript object for storing variables and methods
nenoeditor = {};

jQuery(document).ready(function () {

	bindEvents();

	showFilterTags();

	// Load hierarchy if some groups has been marked
	jQuery('.expanded').each(function () {
		loadGroupsElementsChildren(jQuery(this));
	});

	loadStrings(true);

});

/**
 * Load children for an element in in Groups and elements
 * @param element jQuery object
 */
function loadGroupsElementsChildren(element) {

	var data_id = element.data('id');
	var id = data_id.split('-').pop();
	if (element.data('level') === 1) {
		if (!element.data('loaded')) {
			element.addClass('loading');
			jQuery.ajax({
				url    : 'index.php?option=com_neno&task=editor.getElements&group_id=' + id,
				success: function (html) {
					element.after(html);
					element.data('loaded', true);
					bindEvents();
					element.removeClass('loading');
					checkUncheckFamilyCheckboxes(element.find('input[type=checkbox]').first());
					showFilterTags();
					loadStrings(true);
				}
			});
		}
		else {
			jQuery('[data-parent="' + data_id + '"]').removeClass('hide');
		}
	}
	else {
		jQuery('[data-parent="' + data_id + '"]').removeClass('hide')
	}
}


function bindEvents() {
	jQuery('.multiselect *').unbind('click');

	jQuery('.btn-toggle').click(function (e) {
		jQuery('#' + jQuery(this).attr('data-toggle')).slideToggle('fast');
		jQuery(this).toggleClass('open');
		jQuery(this).blur();
	});

	jQuery('#table-multiselect .cell-expand').click(toggleElementVisibility);

	jQuery('#table-multiselect input[type=checkbox]').unbind('click').click(function () {
		if (confirmNotSavingChanges() === false) {
			jQuery(this).prop('checked', !jQuery(this).prop('checked'));
			return;
		}
		jQuery("input[name='limitstart']").val(0);
		jQuery('#elements-wrapper').html('');
		checkUncheckFamilyCheckboxes(jQuery(this));
		loadStrings(true);
	});
	jQuery('#status-multiselect input[type=checkbox], #method-multiselect input[type=checkbox]').unbind('click').click(function () {
		if (confirmNotSavingChanges() === false) {
			jQuery(this).prop('checked', !jQuery(this).prop('checked'));
			return;
		}
		jQuery("input[name='limitstart']").val(0);
		jQuery('#elements-wrapper').html('');
		loadStrings(true);
	});
}

/**
 * Toggle Elements (Tables and language files)
 */
function toggleElementVisibility() {

	var row = jQuery(this).parent('.element-row');
	var data_id = row.data('id');
	var id = data_id.split('-').pop();

	//Get the state of the current toggler to see if we need to expand or collapse
	if (row.hasClass('collapsed')) {
		// Expand
		row.removeClass('collapsed').addClass('expanded');
		jQuery(this).html('<span class="toggle-arrow icon-arrow-down-3"></span>');
		loadGroupsElementsChildren(row);
	} else {
		//Collapse
		row.removeClass('expanded').addClass('collapsed');
		jQuery(this).html('<span class="toggle-arrow icon-arrow-right-3"></span>');
		jQuery('[data-parent="' + data_id + '"]').removeClass('expanded').addClass('collapsed').addClass('hide').each(function () {
			// Collapse also grandchildren
			jQuery(this).find('.cell-expand').html('<span class="toggle-arrow icon-arrow-right-3"></span>');
			var descendant_data_id = jQuery(this).data('id');
			jQuery('[data-parent="' + descendant_data_id + '"]').addClass('hide');
		});
	}
}

/**
 * Read some values from the dom into variables
 */
function updateFilterState() {

	// If the filters var is not defined then set it as an object
	if (typeof nenoeditor.filters === 'undefined') {
		nenoeditor.filters = {}
	}
	nenoeditor.filters.groupselements = getMultiSelectValue(jQuery('#table-multiselect'));
	nenoeditor.filters.statuses = getMultiSelectValue(jQuery('#status-multiselect'));
	nenoeditor.filters.methods = getMultiSelectValue(jQuery('#method-multiselect'));
	nenoeditor.filters.search = jQuery('#filter_search').val();
	nenoeditor.filters.limitStart = jQuery("input[name='limitstart']").val();
	nenoeditor.filters.limit = document.adminForm.list_limit.value;

}

/**
 * Takes the current filter state and changes the URL to reflect it
 */
function pushFiltersToUrl() {

	// Make sure the variables we use are updated from the DOM
	updateFilterState();

	// Create an array with URL elements
	var urlElements = [];

	if (nenoeditor.filters.groupselements.length !== 0) {
		for (var i = 0; i < nenoeditor.filters.groupselements.length; i++) {
			var data = nenoeditor.filters.groupselements[i].split('-');
			urlElements.push(data[0] + '[]=' + data[1]);
		}
	}
	else {
		nenoeditor.filters.groupselements.push('groups-none');
		urlElements.push('group[]=none');
	}

	if (nenoeditor.filters.statuses.length !== 0) {
		for (var i = 0; i < nenoeditor.filters.statuses.length; i++) {
			var data = nenoeditor.filters.statuses[i].split('-');
			urlElements.push('status[]=' + data[1]);
		}
	} else {
		nenoeditor.filters.statuses.push('status-none');
		urlElements.push('status[]=none');
	}

	if (nenoeditor.filters.methods.length !== 0) {
		for (var i = 0; i < nenoeditor.filters.methods.length; i++) {
			var data = nenoeditor.filters.methods[i].split('-');
			urlElements.push('type[]=' + data[1]);
		}
	} else {
		nenoeditor.filters.methods.push('method-none');
		urlElements.push('type[]=none');
	}

	if (nenoeditor.filters.search !== '') {
		urlElements.push('search=' + encodeURIComponent(nenoeditor.filters.search));
	}

	var url = document.location.origin + document.location.pathname + '?option=com_neno&view=editor';

	if (urlElements.length !== 0) {
		history.pushState(null, null, url + '&' + urlElements.join('&'));
	}
	else {
		history.pushState(null, null, url);
	}

}


/**
 * Load the matching string into the string list below the filter
 * @param reset boolean Whether this is a fresh load or not
 * @returns {undefined}
 */
function loadStrings(reset) {

	if (reset === true) {
		jQuery("input[name='limitstart']").val(0);
	} else {
		var limitStart = jQuery("input[name='limitstart']");
		limitStart.val(
			parseInt(limitStart.val()) + parseInt(jQuery("input[name='list_limit']").val())
		);
	}

	pushFiltersToUrl();

	jQuery.ajax({
			type: "POST",
			url : "index.php?option=com_neno&task=editor.getStrings",
			data: {
				jsonGroupsElements: JSON.stringify(nenoeditor.filters.groupselements),
				filter_search     : nenoeditor.filters.search,
				limitStart        : nenoeditor.filters.limitStart,
				limit             : nenoeditor.filters.limit,
				jsonStatus        : JSON.stringify(nenoeditor.filters.statuses),
				jsonMethod        : JSON.stringify(nenoeditor.filters.methods),
				outputLayout      : document.adminForm.outputLayout.value
			}
		})
		.done(function (data) {
			if (data) {
				var targetContainer = jQuery('#elements-wrapper');
				if (document.adminForm.outputLayout.value === 'editorStrings') {
					showFilterTags();
				}
				if (reset === true) {
					targetContainer.html(data);
					if (targetContainer.find('.string').length) {
						loadTranslation(targetContainer.find('.string').first().data('id'));
					}
				} else {
					targetContainer.append(data);
				}
				// Print messages if no results at all
				if (targetContainer.find('div.string').length === 0) {
					targetContainer.find(".no-results").show();
				}
				// Bind click event in order to load translations
				bindStringsTranslationsLoading();
				// Set results wrapper height
				setResultsWrapperHeight();
			}
		});
}

/**
 * Look at the given jQuery table DOM object and return a simple array
 * of what checkboxes exist and which are checked
 * @param table JQuery object
 * @returns array
 */
function getMultiSelectValue(table) {

	var result = [];
	var checked = [];
	var checks = jQuery('#' + table.attr('id') + ' input[type=checkbox]');

	for (var i = 0; i < checks.length; i++) {
		if (jQuery(checks[i]).prop('checked')) {
			var row = jQuery(checks[i]).closest('tr');
			if (jQuery.inArray(row.attr('data-parent'), checked) === -1) {
				result.push(row.attr('data-id'));
			}
			checked.push(row.attr('data-id'));
		}
	}

	return result;
}

/**
 * Check and uncheck checkboxes
 *  - Parent click: check/uncheck all children
 *  - Child click: uncheck parent if checked
 */
function checkUncheckFamilyCheckboxes(checkbox, recursive) {

	//Set some vars
	var state = checkbox.prop('checked');
	var this_data_id = checkbox.closest('tr').data('id');
	var children = jQuery('[data-parent="' + this_data_id + '"]');
	if (recursive === undefined) {
		recursive = true;
	}

	if (recursive) {
		//Check uncheck all children
		if (children.find('input[type=checkbox]').length == children.find('input[type=checkbox]:checked').length || state == true) {
			children.find('input[type=checkbox]').prop('checked', state);
		}

		children.find('input[type=checkbox]').each(function () {
			checkUncheckFamilyCheckboxes(jQuery(this), true);
		});
	}

	//Check uncheck parent
	var parent_data_id = jQuery('[data-id="' + this_data_id + '"').attr('data-parent');
	var parent = jQuery('[data-id="' + parent_data_id + '"]');
	if (parent_data_id) {
		if (state === true) {
			// Search all siblings to see if any of them is unchecked
			var uncheckedSiblings = jQuery('[data-parent="' + parent_data_id + '"]').find('input[type=checkbox]:not(:checked)');
			if (uncheckedSiblings.length == 0) {
				parent.find('input[type=checkbox]').prop('checked', true);
				if (recursive) {
					parent.find('input[type=checkbox]').each(function () {
						checkUncheckFamilyCheckboxes(jQuery(this), false);
					});
				}
			}
		} else {
			parent.find('input[type=checkbox]').prop('checked', false);
			if (recursive) {
				parent.find('input[type=checkbox]').each(function () {
					checkUncheckFamilyCheckboxes(jQuery(this), false);
				});
			}
		}
	}
}


function showFilterTags() {

	// Delete any existing tags
	jQuery('#filter-tags-wrapper').html('');

	// Load variables from the dom
	updateFilterState();

	var statuses = nenoeditor.filters.statuses;
	var methods = getMultiSelectValue(jQuery('#method-multiselect'));
	var groupselements = getMultiSelectValue(jQuery('#table-multiselect'));

	// Write a tag for search words
	if (nenoeditor.filters.search !== '') {
		printFilterTag('search', '"' + nenoeditor.filters.search + '"');
	}

	// Write tags for selected statuses
	for (s in statuses) {
		if (String(statuses[s]).indexOf('status') !== 0) {
			continue;
		}
		printFilterTag(statuses[s], jQuery('[data-id="' + statuses[s] + '"]').attr('data-label'));
	}

	// Write tags for methods
	for (m in methods) {
		if (String(methods[m]).indexOf('method') !== 0) {
			continue;
		}
		printFilterTag(methods[m], jQuery('[data-id="' + methods[m] + '"]').attr('data-label'));
	}

	// Write tags for Groups and Elements
	for (ge in groupselements) {

		if (String(groupselements[ge]).indexOf('group') !== 0 && String(groupselements[ge]).indexOf('table') !== 0 && String(groupselements[ge]).indexOf('field') !== 0 && String(groupselements[ge]).indexOf('file') !== 0) {
			continue;
		}
		var row = jQuery('[data-id="' + groupselements[ge] + '"]');
		var label = '';
		if (row.attr('data-parent') && row.attr('data-parent') !== 'header') {
			var parent = jQuery('[data-id="' + row.data('parent') + '"]');
			if (parent.attr('data-parent') && parent.attr('data-parent') !== 'header') {
				label += jQuery('[data-id="' + parent.data('parent') + '"]').attr('data-label') + ' > ';
			}
			label += parent.attr('data-label') + ' > ';
		}
		label += row.attr('data-label');

		printFilterTag(groupselements[ge], label);
	}

	bindRemoveTags();
}


/**
 * Write HTML for a tag and append it to the tag area
 * @param type The type of tag
 * @param label The label of the tag
 */
function printFilterTag(type, label) {

	label = label.replace('<', '&lt;');
	label = label.replace('>', '&gt;');
	var tag = jQuery('<span class="filter-tag btn btn-small disabled" data-type="' + type + '"><span class="removeTag icon-remove"></span>' + label + '</span>');
	jQuery('#filter-tags-wrapper').append(tag);

}

/**
 * Bind functionality to remove tags
 */
function bindRemoveTags() {

	jQuery('.removeTag').click(function () {

		// Prevent removing a tag if changes has been made
		if (confirmNotSavingChanges() === false) {
			return;
		}

		// Uncheck the underlying checkbox for the tag
		var type = jQuery(this).parent().data('type');
		jQuery('[data-id="' + type + '"]').find('input[type=checkbox]').prop('checked', false);

		// Check if the tag is from a Group/Element/Key
		if (type.indexOf('group') !== -1 || type.indexOf('table') !== -1 || type.indexOf('field') !== -1) {
			checkUncheckFamilyCheckboxes(jQuery('[data-id="' + type + '"]').find('input[type=checkbox]'), true);
		}

		// If search tag, reset search
		if (type === 'search') {
			jQuery('#filter_search').val('');
		}

		// Remove the tag
		jQuery(this).parent().remove();

		// Reload strings
		loadStrings(true);

	});

}
