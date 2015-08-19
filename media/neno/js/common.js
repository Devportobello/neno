/**
 * @author      Jensen Technologies S.L. <info@notwebdesign.com>
 * @copyright   Copyright (C) 2014 Jensen Technologies S.L. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

function loadTranslation(string) {
    if (!ignoreTranslationModification()) {
        return;
    }
    var idString;
    if (jQuery.type(string) == 'object') {
        jQuery('.string-activated').removeClass('string-activated');
        idString = string.data('id');
    } else {
        idString = string;
    }
    jQuery('div[data-id=' + idString + ']').addClass('string-activated');

    // Get information
    jQuery.ajax({
            beforeSend: onBeforeAjax,
            url: 'index.php?option=com_neno&task=editor.getTranslation&id=' + idString,
            success: function (data) {
                jQuery('#editor-wrapper').html(data);
                jQuery('.original-text').addClass('highlighted-text');
                setTimeout(function () {
                    jQuery('.original-text').removeClass('highlighted-text');
                }, 500);
            }
        }
    );
}

function loadNextTranslation() {
    var nextString = jQuery('.string-activated').next('div').next('div');
    if (nextString.length) {
        loadTranslation(nextString);
    }
}

function updateEditorString(row, data) {
    if (jQuery('#input-status-' + data.state).prop('checked') || jQuery('#status-multiselect input:checked').length == 0) {
        var string = data.string;
        var statuses = ['', 'translated', 'queued', 'changed', 'not-translated'];
        try {
            var stringObject = jQuery(string);
            if (stringObject.length) {
                string = stringObject.text();
            }
        }
        catch (err) {
        }
        if (string.length > 50) {
            string = string.substr(0, 45) + '...';
        }
        row.find('.string-text').html(string);
        row.find('.status').removeClass().addClass('status');
        row.find('.status').addClass(statuses[data.state]);
    } else {
        loadNextTranslation();
        row.remove();
    }
}

function saveTranslationAndNext() {
    var text = jQuery('.translated-content').val();
    var translationId = jQuery('#save-next-button').data('id');
    jQuery('.translated-content').attr('data-modified', 'false');
    jQuery.ajax({
            beforeSend: onBeforeAjax,
            type: 'POST',
            url: 'index.php?option=com_neno&task=editor.saveAsCompleted',
            dataType: "json",
            data: {
                id: translationId,
                text: text
            },
            success: function (data) {
                jQuery('.translatedContent').attr('data-modified', 'false');
                if (typeof data.message != 'undefined') {
                    jQuery('#consolidate-modal .modal-body p').html(data.message);
                    jQuery('#consolidate-button').off('click').data('translation', translationId).on('click', function () {
                        var translationId = jQuery(this).data('translation');
                        jQuery.ajax({
                                beforeSend: onBeforeAjax,
                                type: 'POST',
                                data: {
                                    id: translationId
                                },
                                url: 'index.php?option=com_neno&task=editor.consolidateTranslation',
                                success: function () {
                                    jQuery('#consolidate-modal').modal('hide');
                                    jQuery('#consolidate-confirm-modal .modal-body p span').html(data.counter);
                                    jQuery('#consolidate-confirm-modal').modal('show');
                                }
                            }
                        );
                    });
                    jQuery('#consolidate-modal').modal('show');
                    jQuery('#consolidate-button').focus();
                }
                var row = jQuery('#elements-wrapper .string[data-id=' + data.translation.id + ']');
                if (row.length) {
                    updateEditorString(row, data.translation);
                }
                if (row.length && row[0] == jQuery('#elements-wrapper .string[data-id=' + data.translation.id + ']')[0]) {
                    loadNextTranslation();
                }
            }
        }
    );
}

function saveDraft() {
    var text = jQuery('.translated-content').val();
    var translationId = jQuery('#draft-button').data('id');
    jQuery.ajax({
            beforeSend: onBeforeAjax,
            type: 'POST',
            url: 'index.php?option=com_neno&task=editor.saveAsDraft',
            dataType: "json",
            data: {
                id: translationId,
                text: text
            },
            success: function (data) {
                var row = jQuery('#elements-wrapper .string[data-id=' + data.id + ']');
                if (row) {
                    updateEditorString(row, data);
                }
                bindTranslationModificationCheck();
                jQuery('.translated-content').attr('data-modified', 'false');
            }
        }
    );
}

function translate() {
    if (!ignoreTranslationModification()) {
        return;
    }
    var text = jQuery('.original-text').html().trim();
    jQuery.ajax({
            beforeSend: onBeforeAjax,
            type: 'POST',
            url: 'index.php?option=com_neno&task=editor.translate',
            dataType: "json",
            data: {
                text: text
            },
            success: function (data) {
                jQuery('.translated-content').val(data.text);
                if (data.status == "err") {
                    jQuery('.translated-error .error-message').html(data.error);
                    jQuery('.translated-error').show();
                }
                jQuery('.translated-by').show();
                jQuery('.translated-content').focus();
                jQuery('.translated-content').change();
            }
        }
    );
}

function askForTranslatorAPIKey() {
    jQuery('#translate-btn').off('click').on('click', function () {
        jQuery('#translatorKeyModal').modal('show');
    });

    jQuery('#saveTranslatorKey').off('click').on('click', function () {
        var translator = jQuery('#translator').val();
        var translatorKey = jQuery('#translator_api_key').val();
        jQuery.ajax({
                beforeSend: onBeforeAjax,
                type: 'POST',
                data: {
                    translator: translator,
                    translatorKey: translatorKey
                },
                url: 'index.php?option=com_neno&task=editor.saveTranslatorConfig',
                success: function () {
                    jQuery('#saveTranslatorKey').modal('hide');
                    window.location.reload();
                }
            }
        );
    });

    var options = {
        html: true,
        placement: "right"
    }
    jQuery('.settings-tooltip').tooltip(options);
}

// Check if the user has lost the session
function onBeforeAjax() {
    jQuery.get('index.php?option=com_neno&task=checkSession', function (response) {
        if (response != 'ok') {
            document.location.reload();
        }
    });
}

function fixIssue() {
    var button = jQuery(this);
    button.closest('.alert').remove();
    jQuery.ajax({
        beforeSend: onBeforeAjax,
        url: 'index.php?option=com_neno&task=fixLanguageIssue',
        data: {
            language: button.data('language'),
            issue: button.data('issue')
        },
        type: 'POST'
    });
}

function loadMissingTranslationMethodSelectors(listSelector, placement) {
    var apply = false;
    if (typeof listSelector != 'string') {
        var parent = jQuery('.translation-method-selector-container').parent();

        if (typeof parent.prop('id') == 'undefined' || parent.prop('id') == '') {
            listSelector = '.method-selectors';
        }
        else {
            listSelector = '#' + parent.prop('id');
        }
    }

    if (typeof placement != 'string') {
        placement = 'language';
    }

    if (typeof jQuery(this).prop("tagName") == 'undefined') {
        i = 1;
        jQuery(listSelector).each(function () {
            //Count how many we currently are showing
            var n = jQuery(this).find('.translation-method-selector-container').length;

            //If we are loading because of changing a selector, remove all children
            var selector_id = jQuery(this).find('.translation-method-selector').attr('data-selector-id');
            if (typeof selector_id !== 'undefined') {
                //Loop through each selector and remove the ones that are after this one
                for (var i = 0; i < n; i++) {
                    if (i > selector_id) {
                        jQuery(this).find("[data-selector-container-id='" + i + "']").remove();
                    }
                }
            }
            //Create a string to pass the current selections
            var selected_methods_string = '';
            jQuery(this).find('.translation-method-selector').each(function () {
                selected_methods_string += '&selected_methods[]=' + jQuery(this).find(':selected').val();
            });
            var lang = jQuery(this).closest(listSelector).data('language');
            var otherParams = '';

            if (typeof lang != 'undefined') {
                otherParams = '&language=' + lang;
            }

            executeAjaxForTranslationMethodSelectors(listSelector, placement, n, selected_methods_string, jQuery(this).find('.translation-method-selector'), otherParams, false);
        });
    }
    else {

        //If we are loading because of changing a selector, remove all children
        var selector_id = jQuery(this).data('selector-id');
        var n = jQuery(this).closest(listSelector).find('.translation-method-selector-container').length;
        if (typeof selector_id !== 'undefined') {
            //Loop through each selector and remove the ones that are after this one
            for (var i = 0; i < n; i++) {
                if (i > selector_id) {
                    jQuery(this).closest(listSelector).find("[data-selector-container-id='" + i + "']").remove();
                    n--;
                }
            }
        }
        var selected_methods_string = '&selected_methods[]=' + jQuery(this).find(':selected').val();
        var lang = jQuery(this).closest(listSelector).data('language');
        var otherParams = '';
        var element = jQuery(this);

        if (typeof lang != 'undefined') {
            otherParams = '&language=' + lang;
        }

        var modal = jQuery('#translationMethodModal');

        // There isn't a modal, so we are on the installation process setting up the translation method for the source language
        if (modal.length == 0) {
            executeAjaxForTranslationMethodSelectors(listSelector, 'general', n, selected_methods_string, element, otherParams);
        }
        else {
            var run = modal.length == 0;


            modal.modal('show');
            modal.find('.yes-btn').off('click').on('click', function () {
                saveTranslationMethod(element.find(':selected').val(), lang, selector_id + 1, true);
                run = true;
                modal.modal('hide');
                apply = true;
            });

            modal.off('hide').on('hide', function () {
                if (!run) {
                    saveTranslationMethod(element.find(':selected').val(), lang, selector_id + 1, false);
                }

                executeAjaxForTranslationMethodSelectors(listSelector, placement, n, selected_methods_string, element, otherParams);
            });

        }

    }
}


function executeAjaxForTranslationMethodSelectors(listSelector, placement, n, selected_methods_string, element, otherParams) {
    if (typeof otherParams == 'undefined') {
        otherParams = '';
    }
    jQuery.ajax({
        beforeSend: onBeforeAjax,
        url: 'index.php?option=com_neno&task=getTranslationMethodSelector&placement=' + placement + '&n=' + n + selected_methods_string + otherParams,
        success: function (html) {
            if (html !== '') {
                jQuery(element).closest(listSelector).append(html);

                if (placement == 'language') {
                    jQuery(element).closest(listSelector).find('.translation-method-selector').each(function () {
                        saveTranslationMethod(jQuery(this).find(':selected').val(), jQuery(this).closest(listSelector).data('language'), jQuery(this).data('selector-id') + 1, apply);
                    });
                }
            }

            jQuery('select').chosen();
            jQuery('.translation-method-selector').off('change').on('change', loadMissingTranslationMethodSelectors);
            var container = element.parents('.language-configuration');
            var select1 = element.parents(listSelector).find("[data-selector-container-id='1']");
            if (select1.length) {
                if (!container.hasClass('expanded')) {
                    container.css('min-height',
                        parseInt(container.css('min-height')) + 60
                    );
                    container.addClass('expanded');
                }
            } else if (container.hasClass('expanded')) {
                container.css('min-height',
                    parseInt(container.css('min-height')) - 60
                );
                container.removeClass('expanded');
            }
        }
    });
}

function saveTranslationMethod(translationMethod, language, ordering, applyToElements) {
    if (typeof applyToElements == 'undefined') {
        applyToElements = false;
    }

    applyToElements = applyToElements ? 1 : 0;

    jQuery.ajax({
        beforeSend: onBeforeAjax,
        url: 'index.php?option=com_neno&task=saveTranslationMethod',
        type: 'POST',
        data: {
            translationMethod: translationMethod,
            language: language,
            ordering: ordering,
            applyToElements: applyToElements
        }
    });
}

function copyOriginal() {
    if (!ignoreTranslationModification()) {
        return;
    }
    var original = jQuery('.original-text').html().trim();
    original = original.replace(/<span class="highlighted-tag">|<\/span>/g, '');
    original = original.replace(/&lt;/g, '<');
    original = original.replace(/&gt;/g, '>');
    jQuery('.translated-content').val(original);
    jQuery('.translated-by').hide();
    jQuery('.translated-error').hide();
    jQuery('.translated-content').focus();
    jQuery('.translated-content').change();
}

function setResultsWrapperHeight() {
    var available = jQuery(window).outerHeight() - jQuery('header').outerHeight() - jQuery('.subhead-collapse').outerHeight() - jQuery('#status').outerHeight();
    var sidebar = jQuery('#j-sidebar-container');
    sidebar.height(available);

    var results = jQuery('#results-wrapper');
    var resultsBottom = results.position().top + results.outerHeight();
    var gap = sidebar.outerHeight() - resultsBottom;
    var elements = jQuery('#elements-wrapper');
    elements.height(elements.outerHeight() + gap - 70);
}

function bindTranslationModificationCheck() {
    jQuery(window).off('beforeunload');
    var translatedContent = jQuery('.translated-content');
    translatedContent.on('change', function () {
        jQuery(window).off('beforeunload').on('beforeunload', function () {
            return translatedContent.attr('data-modified-message');
        });
        translatedContent.off('change');
        translatedContent.off('keyup');
        translatedContent.attr('data-modified', 'true');
    });
    translatedContent.on('keyup', function () {
        jQuery(window).off('beforeunload').on('beforeunload', function () {
            return translatedContent.attr('data-modified-message');
        });
        translatedContent.off('change');
        translatedContent.off('keyup');
        translatedContent.attr('data-modified', 'true');
    });
}

function ignoreTranslationModification() {
    var translatedContent = jQuery('.translated-content');
    var ignore = true;
    if (translatedContent.attr('data-modified') == "true") {
        ignore = confirm(translatedContent.attr('data-modified-message'));
        if (ignore) {
            bindTranslationModificationCheck();
        }
    }
    return ignore;
}