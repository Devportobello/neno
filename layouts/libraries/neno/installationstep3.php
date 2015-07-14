<?php
/**
 * @package    Neno
 *
 * @author     Jensen Technologies S.L. <info@notwebdesign.com>
 * @copyright  Copyright (C) 2014 Jensen Technologies S.L. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

JHtml::_('bootstrap.tooltip');
$items = $displayData->languages;

?>

<style>
</style>

<div class="installation-step">
	<div class="installation-body span12">
		<div class="error-messages"></div>
		<h2><?php echo JText::_('COM_NENO_INSTALLATION_TARGET_LANGUAGES_TITLE'); ?></h2>

		<p><?php echo JText::_('COM_NENO_INSTALLATION_TARGET_LANGUAGES_MESSAGE'); ?></p>

		<?php foreach ($items as $item): ?>
			<?php echo JLayoutHelper::render('languageconfiguration', $item, JPATH_NENO_LAYOUTS); ?>
		<?php endforeach; ?>

		<button type="button" class="btn btn-primary"
		        id="add-languages-button">
			<?php echo JText::_('COM_NENO_INSTALLATION_TARGET_LANGUAGES_ADD_LANGUAGE_BUTTON'); ?>
		</button>

		<button type="button" class="btn btn-success next-step-button">
			<?php echo JText::_('COM_NENO_INSTALLATION_NEXT'); ?>
		</button>
		<img src="<?php echo JUri::root(); ?>/media/neno/images/loading_mini.gif" class="hide loading-spin"/>
	</div>

	<?php echo JLayoutHelper::render('installationbottom', 3, JPATH_NENO_LAYOUTS); ?>
</div>

<script>
	jQuery('#add-languages-button').click(function () {
		jQuery.ajax({
			beforeSend: onBeforeAjax,
			url: 'index.php?option=com_neno&task=showInstallLanguagesModal&placement=installation',
			success: function (html) {
				jQuery('#languages-modal .modal-body').empty().append(html);
				jQuery('#languages-modal .modal-header h3').html("<?php echo JText::_('COM_NENO_INSTALLATION_TARGET_LANGUAGES_LANGUAGE_MODAL_TITLE'); ?>");
				jQuery('#languages-modal').modal('show');
			}
		});
	});
	loadMissingTranslationMethodSelectors();

	jQuery('.save-translator-comment').on('click', function () {
		var language = jQuery(this).data('language');

		jQuery.post(
			'index.php?option=com_neno&task=saveExternalTranslatorsComment',
			{
				placement: 'language',
				language: language,
				comment: jQuery(".comment-to-translator[data-language='" + language + "']").val()
			},
			function (response) {

				if (response == 'ok') {
					var text = '<?php echo JText::_('COM_NENO_COMMENTS_TO_TRANSLATOR_LANGUAGE_EDIT'); ?>';
					text = text.replace('%s', language);
					jQuery(".add-comment-to-translator-button[data-language='" + language + "']").html('<span class="icon-pencil"></span> ' + text);
				}

				jQuery('#addCommentFor' + language).modal('toggle');
			}
		);
	});
</script>
