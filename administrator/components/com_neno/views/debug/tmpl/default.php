<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_modules
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

JHtml::_('bootstrap.tooltip');
JHtml::_('behavior.multiselect');
JHtml::_('formbehavior.chosen', 'select');

$user      = JFactory::getUser();
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
?>
<form
  action="<?php echo JRoute::_('index.php?option=com_neno&view=debug&r=' . NenoHelperBackend::generateRandomString()); ?>"
  method="post" name="adminForm" id="adminForm">
	<?php if (!empty($this->sidebar)) : ?>
	<div id="j-sidebar-container" class="span2">
		<?php echo $this->sidebar; ?>
	</div>
	<div id="j-main-container" class="span10">
		<?php else : ?>
		<div id="j-main-container">
			<?php endif; ?>
			<?php
			// Search tools bar and filters
			echo JLayoutHelper::render('joomla.searchtools.default', array('view' => $this));
			?>
			<table class="table table-striped" id="moduleList">
				<thead>
				<tr>
					<th class="nowrap center"
					    style="min-width:55px">
						<?php echo JHtml::_('searchtools.sort', 'COM_NENO_DEBUG_HEADER_DATE', 'a.time_added', $listDirn, $listOrder); ?>
					</th>
					<th class="nowrap center"
					    style="min-width:55px">
						<?php echo JHtml::_('searchtools.sort', 'COM_NENO_DEBUG_HEADER_ERROR_LEVEL', 'a.level', $listDirn, $listOrder); ?>
					</th>
					<th class="title">
						<?php echo JHtml::_('searchtools.sort', 'COM_NENO_DEBUG_HEADER_MESSAGE', 'a.message', $listDirn, $listOrder); ?>
					</th>
					<th class="nowrap hidden-phone">
						<?php echo JHtml::_('searchtools.sort', 'COM_NENO_DEBUG_HEADER_TRIGGERED_BY', 'a.`trigger`', $listDirn, $listOrder); ?>
					</th>
				</tr>
				</thead>
				<tfoot>
				<tr>
					<td colspan="4">
						<?php echo $this->pagination->getListFooter(); ?>
					</td>
				</tr>
				</tfoot>
				<tbody>
				<?php foreach ($this->items as $i => $item) : ?>
					<tr class="row<?php echo $i % 2; ?>">
						<td class="center">
							<?php echo $item->time_added; ?>
						</td>
						<td class="center">
								<span
								  class="label label-<?php echo $item->level; ?>">
									<?php echo JText::_('COM_NENO_DEBUG_PRIORITY_ENTRY_' . strtoupper($item->level)); ?>
								</span>
						</td>
						<td class="has-context">
							<?php echo $item->message; ?>
						</td>
						<td class="hidden-phone small">
							<?php if ($item->triggered): ?>
								<?php echo JFactory::getUser($item->triggered)->name; ?>
							<?php else: ?>
								<?php echo JText::_('System'); ?>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			
			<input type="hidden" name="task" value=""/>
			<input type="hidden" name="boxchecked" value="0"/>
			<?php echo JHtml::_('form.token'); ?>
		</div>
</form>
