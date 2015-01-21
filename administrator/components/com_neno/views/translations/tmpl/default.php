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

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');
JHtml::_('bootstrap.tooltip');
JHtml::_('behavior.multiselect');
JHtml::_('formbehavior.chosen', 'select');

// Import CSS
$document = JFactory::getDocument();
$document->addStyleSheet('components/com_neno/assets/css/toolbar.css');

$user          = JFactory::getUser();
$userId        = $user->get('id');
$listOrder     = $this->state->get('list.ordering');
$listDirection = $this->state->get('list.direction');

$sortFields = $this->getSortFields();
?>
<script type="text/javascript">
	Joomla.orderTable = function () {
		var order = jQuery('#sortTable option:selected').val();
		var direction = jQuery('#directionTable option:selected').val();
		if (order != '<?php echo $listOrder; ?>') {
			direction = 'asc';
		}
		Joomla.tableOrdering(order, direction, '');
	}
</script>

<?php
// Joomla Component Creator code to allow adding non select list filters
if (!empty($this->extra_sidebar))
{
	$this->sidebar .= $this->extra_sidebar;
}
?>

<form action="<?php echo JRoute::_('index.php?option=com_neno&view=translations'); ?>" method="post" name="adminForm"
      id="adminForm">
	<?php if (!empty($this->sidebar)): ?>
	<div id="j-sidebar-container" class="span2">
		<?php echo $this->sidebar; ?>
	</div>
	<div id="j-main-container" class="span10">
		<?php else : ?>
		<div id="j-main-container">
			<?php endif; ?>

			<div id="filter-bar" class="btn-toolbar">
				<div class="filter-search btn-group pull-left">
					<label for="filter_search"
					       class="element-invisible"><?php echo JText::_('JSEARCH_FILTER'); ?></label>
					<input type="text" name="filter_search" id="filter_search"
					       placeholder="<?php echo JText::_('JSEARCH_FILTER'); ?>"
					       value="<?php echo $this->escape($this->state->get('filter.search')); ?>"
					       title="<?php echo JText::_('JSEARCH_FILTER'); ?>"/>
				</div>
				<div class="btn-group pull-left">
					<button class="btn hasTooltip" type="submit"
					        title="<?php echo JText::_('JSEARCH_FILTER_SUBMIT'); ?>"><i class="icon-search"></i>
					</button>
					<button class="btn hasTooltip" type="button" title="<?php echo JText::_('JSEARCH_FILTER_CLEAR'); ?>"
					        onclick="document.id('filter_search').value='';this.form.submit();"><i
							class="icon-remove"></i></button>
				</div>
				<div class="btn-group pull-right hidden-phone">
					<label for="limit"
					       class="element-invisible"><?php echo JText::_('JFIELD_PLG_SEARCH_SEARCHLIMIT_DESC'); ?></label>
					<?php echo $this->pagination->getLimitBox(); ?>
				</div>
				<div class="btn-group pull-right hidden-phone">
					<label for="directionTable"
					       class="element-invisible"><?php echo JText::_('JFIELD_ORDERING_DESC'); ?></label>
					<select name="directionTable" id="directionTable" class="input-medium"
					        onchange="Joomla.orderTable()">
						<option value=""><?php echo JText::_('JFIELD_ORDERING_DESC'); ?></option>
						<option
							value="asc" <?php echo $listDirection == 'asc' ? 'selected="selected"' : ''; ?>>
							<?php echo JText::_('JGLOBAL_ORDER_ASCENDING'); ?>
						</option>
						<option
							value="desc" <?php echo $listDirection == 'desc' ? 'selected="selected"' : ''; ?>>
							<?php echo JText::_('JGLOBAL_ORDER_DESCENDING'); ?>
						</option>
					</select>
				</div>
				<div class="btn-group pull-right">
					<label for="sortTable" class="element-invisible"><?php echo JText::_('JGLOBAL_SORT_BY'); ?></label>
					<select name="sortTable" id="sortTable" class="input-medium" onchange="Joomla.orderTable()">
						<option value=""><?php echo JText::_('JGLOBAL_SORT_BY'); ?></option>
						<?php echo JHtml::_('select.options', $sortFields, 'value', 'text', $listOrder); ?>
					</select>
				</div>
			</div>
			<div class="clearfix"></div>
			<table class="table table-striped" id="translationList">
				<thead>
				<tr>
					<th width="1%" class="hidden-phone">
						<input type="checkbox" name="checkall-toggle" value=""
						       title="<?php echo JText::_('JGLOBAL_CHECK_ALL'); ?>" onclick="Joomla.checkAll(this)"/>
					</th>
					<th width="1%" class="nowrap center">
						<?php echo JHtml::_('grid.sort', 'JSTATUS', 'a.state', $listDirection, $listOrder); ?>
					</th>
					<th class='left'>
						<?php echo JHtml::_('grid.sort', 'COM_NENO_TRANSLATIONS_CONSTANT', 'a.constant', $listDirection, $listOrder); ?>
					</th>
					<th class='left'>
						<?php echo JHtml::_('grid.sort', 'COM_NENO_TRANSLATIONS_SOURCE_STRING', 'a.string', $listDirection, $listOrder); ?>
					</th>
					<th class='left'>
						<?php echo JHtml::_('grid.sort', 'COM_NENO_TRANSLATIONS_TARGET_STRING', 't.string', $listDirection, $listOrder); ?>
					</th>
					<th class='left'>
						<?php echo JHtml::_('grid.sort', 'COM_NENO_TRANSLATIONS_TIME_TRANSLATED', 'a.time_translated', $listDirection, $listOrder); ?>
					</th>
					<th class='left'>
						<?php echo JHtml::_('grid.sort', 'COM_NENO_TRANSLATIONS_LANG', 'a.target_lang', $listDirection, $listOrder); ?>
					</th>
					<th width="1%" class="nowrap center hidden-phone">
						<?php echo JHtml::_('grid.sort', 'JGRID_HEADING_ID', 'a.id', $listDirection, $listOrder); ?>
					</th>
				</tr>
				<?php /* @var $item NenoContentElementLangfileTranslation */ ?>
				<?php foreach ($this->items as $i => $item) : ?>
					<?php $canCreate = $user->authorise('core.create', 'com_neno'); ?>
					<?php $canEdit = $user->authorise('core.edit', 'com_neno'); ?>
					<?php $canCheckin = $user->authorise('core.manage', 'com_neno'); ?>
					<?php $canChange = $user->authorise('core.edit.state', 'com_neno'); ?>
					<tr class="row<?php echo $i % 2; ?>">
						<td class="hidden-phone">
							<?php echo JHtml::_('grid.id', $i, $item->getId()); ?>
						</td>
						<td class="center">
							<?php echo JHtml::_('jgrid.published', $item->getState(), $i, 'translations.', $canChange, 'cb'); ?>
						</td>
						<td>
							<?php echo $item->getSource()->getConstant(); ?>
						</td>
						<td>
							<?php echo $item->getSource()->getString(); ?>
						</td>
						<td>
							<a href="index.php?option=com_neno&view=translation&id=<?php echo $item->getId(); ?>">
								<?php echo $item->getString(); ?>
							</a>
						</td>
						<td>
							<?php echo $item->getTimeTranslated(); ?>
						</td>
						<td>
							<?php echo $item->getLanguage(); ?>
						</td>
						<td class="center hidden-phone">
							<?php echo (int) $item->getId(); ?>
						</td>
					</tr>
				<?php endforeach; ?>
				<tr>
					<td colspan="10">
						<?php echo $this->pagination->getListFooter(); ?>
					</td>
				</tr>
			</table>

			<input type="hidden" name="task" value=""/>
			<input type="hidden" name="boxchecked" value="0"/>
			<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>"/>
			<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirection; ?>"/>
			<?php echo JHtml::_('form.token'); ?>
		</div>
</form>        

		
