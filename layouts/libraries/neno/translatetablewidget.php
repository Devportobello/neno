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

$table = $displayData;
?>

<fieldset id="check-toggle-translate-table-<?php echo $table->id; ?>"
          class="radio btn-group" data-field="<?php echo $table->id; ?>"
          data-type="table">
	<!-- Translate -->
	<input class="check-toggle-translate-table-radio" type="radio"
	       id="check-toggle-translate-table-<?php echo $table->id; ?>-1"
	       name="jform[check-toggle-translate-table]"
	       value="1" <?php echo ($table->translate == 1) ? 'checked="checked"' : ''; ?>>
	<label for="check-toggle-translate-table-<?php echo $table->id; ?>-1"
	       class="btn btn-small <?php echo ($table->translate == 1) ? 'active btn-success' : ''; ?>"
	       data-toogle="tooltip"
	       title="<?php echo JText::_('COM_NENO_GROUPS_ELEMENTS_TRANSLATE_BUTTON_TOOLTIP'); ?>">
		<?php echo JText::_('COM_NENO_GROUPS_ELEMENTS_TRANSLATE_BUTTON'); ?>
	</label>

	<!-- Do not translate -->
	<input class="check-toggle-translate-table-radio" type="radio"
	       id="check-toggle-translate-table-<?php echo $table->id; ?>-0"
	       name="jform[check-toggle-translate-table]"
	       value="0" <?php echo ($table->translate == 0) ? 'checked="checked"' : ''; ?>>
	<label for="check-toggle-translate-table-<?php echo $table->id; ?>-0"
	       class="btn btn-small <?php echo (!$table->translate) ? 'active btn-danger' : ''; ?>"
	       data-toogle="tooltip"
	       title="<?php echo JText::_('COM_NENO_GROUPS_ELEMENTS_DO_NOT_TRANSLATE_BUTTON_TOOLTIP'); ?>"
	>
		<?php echo JText::_('COM_NENO_GROUPS_ELEMENTS_DO_NOT_TRANSLATE_BUTTON'); ?>
	</label>
</fieldset>

<button class="btn btn-small pull-right" data-toogle="tooltip" type="button"
        title="<?php echo JText::_('COM_NENO_GROUPS_ELEMENTS_TRANSLATE_SOME_BUTTON_TOOLTIP'); ?>">
	<?php echo JText::_('COM_NENO_GROUPS_ELEMENTS_TRANSLATE_SOME_BUTTON'); ?>
</button>