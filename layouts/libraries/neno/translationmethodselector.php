<?php

/**
 * @package     Neno
 * @subpackage  Helpers
 *
 * @author      Jensen Technologies S.L. <info@notwebdesign.com>
 * @copyright   Copyright (C) 2014 Jensen Technologies S.L. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('JPATH_NENO') or die;

if ($displayData !== null): ?>
	<?php $n = $displayData['n']; ?>
	<div class="translation-method-selector-container" data-selector-container-id="<?php echo $n; ?>">
		<div class="control-label"><?php echo JText::sprintf('COM_NENO_METHOD_N', $n + 1); ?></div>
		<div class="controls">
			<select name="jform[translation_methods][<?php echo $n; ?>]" class="translation-method-selector"
			        data-selector-id="<?php echo $n; ?>">
				<?php // Set a default for assigned method to avoid errors ?>
				<?php if (empty($displayData['assigned_translation_methods'][$n]->id)): ?>
					<?php if (empty($displayData['assigned_translation_methods'][$n])): ?>
						<?php $displayData['assigned_translation_methods'][$n] = new stdClass; ?>
					<?php endif; ?>
					<?php $displayData['assigned_translation_methods'][$n]->id = 0; ?>
				<?php endif; ?>
				<option value="0"><?php echo JText::_('COM_NENO_TRANSLATION_METHOD_NONE'); ?></option>
				<?php foreach ($displayData['translation_methods'] as $translation_method): ?>
					<option
						value="<?php echo $translation_method->id; ?>" <?php echo ($translation_method->id == $displayData['assigned_translation_methods'][$n]->id) ? 'selected="selected"' : ''; ?>>
						<?php echo JText::_($translation_method->name_constant); ?>
					</option>
				<?php endforeach; ?>

				<?php // Add an "Do not translate option to the first selector ?>
				<?php if ($n == 0): ?>
					<option
						value="0" <?php echo (0 == $displayData['assigned_translation_methods'][$n]->id) ? 'selected="selected"' : ''; ?>>
						<?php echo JText::_('COM_NENO_TRANSLATION_METHOD_DONT'); ?>
					</option>
				<?php endif; ?>
			</select>
		</div>
	</div>
<?php endif; ?>

