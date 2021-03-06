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
defined('_JEXEC') or die;

$document = JFactory::getDocument();
$version  = NenoHelperBackend::getNenoVersion();
$document->addScript(JUri::root() . '/media/neno/js/multiselect.js?v=' . $version);
$document->addStyleSheet(JUri::root() . '/media/neno/css/multiselect.css?v=' . $version);

$isOverlay = isset($displayData->isOverlay);
?>

<div class="multiselect">
	<div>
		<a class="btn btn-toggle" data-toggle="multiselect" href="#">
			<?php echo JText::_('COM_NENO_SELECT_GROUPSELEMENTS'); ?>
			<span class="caret pull-right"></span>
		</a>

		<div id="multiselect"
			class="dropdown-select menu-multiselect <?php echo ($isOverlay) ? (' overlay') : (''); ?>">
			<table class="table-condensend table-multiselect" id="table-multiselect">
				<?php foreach ($displayData->groups as $group): ?>
					<?php $elementCount = $group->element_count; ?>
					<?php $class = $elementCount ? 'cell-expand' : ''; ?>
					<tr class="row-group element-row <?php echo in_array($group->id, $displayData->modelState->get('filter.group_id', array())) || in_array($group->id, $displayData->modelState->get('filter.parent_group_id', array())) ? 'expanded' : 'collapsed'; ?>"
						data-level="1" data-id="group-<?php echo $group->id; ?>"
						data-parent="header" data-label="<?php echo $group->group_name; ?>">
						<td class="first-cell <?php echo $class; ?>">
							<?php if ($elementCount): ?>
								<span
									class="toggle-arrow <?php echo in_array($group->id, $displayData->modelState->get('filter.group_id', array())) || in_array($group->id, $displayData->modelState->get('filter.parent_group_id', array())) ? 'icon-arrow-down-3' : 'icon-arrow-right-3'; ?>"></span>
							<?php endif; ?>
						</td>
						<td class="cell-check">
							<input
								type="checkbox"
								id="input-group-<?php echo $group->id; ?>"
								<?php echo in_array($group->id, $displayData->modelState->get('filter.group_id', array())) ? 'checked="checked"' : ''; ?>/>
						</td>
						<td colspan="4"
							title="<?php echo $group->group_name; ?>">
							<label for="input-group-<?php echo $group->id; ?>">
								<?php echo $group->group_name; ?>
							</label>
						</td>
					</tr>
				<?php endforeach; ?>
			</table>
		</div>
	</div>
</div>