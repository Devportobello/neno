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

//Include the CSS file
JHtml::stylesheet('media/neno/css/admin.css');

// Joomla Component Creator code to allow adding non select list filters
if (!empty($this->extra_sidebar))
{
	$this->sidebar .= $this->extra_sidebar;
}

$workingLanguage = NenoHelper::getWorkingLanguage();

?>

<style>

	.language-configuration {
		display: none;
	}

</style>

<script type="text/javascript">

	jQuery(document).ready(function () {

		jQuery('.configuration-button').on('click', function () {
			jQuery(this).siblings('.language-configuration').slideToggle();
		});

	});
</script>


<form action="<?php echo JRoute::_('index.php?option=com_neno&view=groupselements'); ?>" method="post" name="adminForm"
      id="adminForm">

	<div id="j-sidebar-container" class="span2">
		<?php echo $this->sidebar; ?>
	</div>
	<div id="j-main-container" class="span10">
		<div class="languages-holder">
			<?php foreach ($this->items as $language => $item): ?>
				<div class="language-wrapper">
					<?php $translated = 0; ?>
					<?php $queued = 0; ?>
					<?php $changed = 0; ?>
					<?php $untranslated = 0; ?>
					<?php foreach ($item as $internalItem): ?>
						<?php if ($internalItem->state == NenoContentElementTranslation::TRANSLATED_STATE): ?>
							<?php $translated = (int) $internalItem->word_count; ?>
						<?php elseif ($internalItem->state == NenoContentElementTranslation::QUEUED_FOR_BEING_TRANSLATED_STATE): ?>
							<?php $translated = (int) $internalItem->word_count; ?>
						<?php elseif ($internalItem->state == NenoContentElementTranslation::SOURCE_CHANGED_STATE): ?>
							<?php $translated = (int) $internalItem->word_count; ?>
						<?php elseif ($internalItem->state == NenoContentElementTranslation::NOT_TRANSLATED_STATE): ?>
							<?php $translated = (int) $internalItem->word_count; ?>
						<?php endif; ?>
					<?php endforeach; ?>
					<?php $wordCount = new stdClass; ?>
					<?php $wordCount->translated = $translated; ?>
					<?php $wordCount->queued = $queued; ?>
					<?php $wordCount->changed = $changed; ?>
					<?php $wordCount->untranslated = $untranslated; ?>
					<?php $wordCount->total = $translated + $queued + $changed + $untranslated; ?>
					<h3>
						<img
							src="<?php echo JUri::root() . 'media/mod_languages/images/' . $item[0]->image . '.gif'; ?>"/>
						<?php echo $item[0]->title; ?>
					</h3>
					<?php echo NenoHelper::renderWordCountProgressBar($wordCount) ?>
					<a class="btn btn-primary"
					   href="<?php echo JRoute::_('index.php?option=com_neno&task=editor.translate&lang=' . $language) ?>">
						<?php echo JText::_('COM_NENO_DASHBOARD_TRANSLATE_BUTTON'); ?>
					</a>
					<button class="btn configuration-button" type="button">
						<?php echo JText::_('COM_NENO_DASHBOARD_CONFIGURATION_BUTTON'); ?>
					</button>
					<div class="language-configuration">
						<?php echo JText::sprintf('COM_NENO_DASHBOARD_GROUPS_ELEMENTS_LINK', JRoute::_('index.php?option=com_neno&task=groupselements.changeConfiguration&lang=' . $language)); ?>
						<fieldset id="jform_published" class="radio btn-group btn-group-yesno">
							<input type="radio" id="jform_published0" name="jform[published]" value="1"
								<?php echo ($item[0]->published) ? 'checked="checked"' : ''; ?>
								>
							<label for="jform_published0" class="btn">
								<?php echo JText::_('JPUBLISHED'); ?>
							</label>
							<input type="radio" id="jform_published1" name="jform[published]" value="0"
								<?php echo ($item[0]->published) ? '' : 'checked="checked"'; ?>>
							<label for="jform_published1" class="btn">
								<?php echo JText::_('JUNPUBLISHED'); ?>
							</label>
						</fieldset>
						<button class="btn btn-small" type="button">
							<span class="icon-trash"></span> <?php echo JText::_('COM_NENO_DASHBOARD_REMOVE_BUTTON'); ?>
						</button>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</form>


