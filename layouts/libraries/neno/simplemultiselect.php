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

$document = JFactory::getDocument();
$version  = NenoHelperBackend::getNenoVersion();
$document->addScript(JUri::root() . '/media/neno/js/multiselect.js?v=' . $version);
$document->addStyleSheet(JUri::root() . '/media/neno/css/multiselect.css?v=' . $version);

$isOverlay = isset($displayData['isOverlay']);
?>

<div class="multiselect simple-multiselect">
	<div>
		<a class="btn btn-toggle" data-toggle="toggle-<?php echo $displayData['type']; ?>-multiselect" href="#">
			<?php echo JText::_('COM_NENO_SELECT_' . strtoupper($displayData['type'])); ?>
			<span class="caret pull-right"></span>
		</a>

		<div id="toggle-<?php echo $displayData['type']; ?>-multiselect"
			class="dropdown-select menu-multiselect <?php echo ($isOverlay) ? ('overlay') : (''); ?>">
			<table class="table-condensend <?php echo $displayData['type']; ?>-multiselect"
				id="<?php echo $displayData['type']; ?>-multiselect">
				<?php foreach ($displayData['data'] as $datum => $label): ?>
					<tr class="" data-id="<?php echo $displayData['type'] . '-' . $datum; ?>"
						data-label="<?php echo $label; ?>"
						data-parent="header">
						<td class="cell-check">
							<input value="<?php echo $datum; ?>"
								id="input-<?php echo $displayData['type'] . '-' . $datum; ?>"
								type="checkbox" <?php echo !empty($displayData['selected']) && in_array($datum, $displayData['selected']) ? 'checked=checked' : ''; ?>/>
						</td>
						<td title="<?php echo $label; ?>">
							<label for="input-<?php echo $displayData['type'] . '-' . $datum; ?>"><?php echo $label; ?></label>
						</td>
					</tr>
				<?php endforeach; ?>
				<?php if (count($displayData['data']) === 0): ?>
					<tr>
						<td><?php echo JText::_('COM_NENO_SELECT_NONE'); ?></td>
					</tr>
				<?php endif; ?>
			</table>
		</div>
	</div>
</div>