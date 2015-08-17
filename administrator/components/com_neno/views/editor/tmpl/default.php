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

// Joomla Component Creator code to allow adding non select list filters
if (!empty($this->extraSidebar))
{
	$this->sidebar .= $this->extraSidebar;
}

$document = JFactory::getDocument();
$document->addStyleSheet(JUri::root() . '/media/neno/css/editor.css');
?>

<div id="j-sidebar-container" class="span2">
	<form action="<?php echo JRoute::_('index.php?option=com_neno&view=editor'); ?>" method="post"
	      name="adminForm" id="adminForm">
		<?php $extraDisplayData = new stdClass; ?>
		<?php $extraDisplayData->groups = $this->groups; ?>
		<?php $extraDisplayData->statuses = $this->statuses; ?>
		<?php $extraDisplayData->methods = $this->methods; ?>
		<?php $extraDisplayData->modelState = $this->state; ?>
		<?php echo JLayoutHelper::render('editorfilters', array ('view' => $this, 'extraDisplayData' => $extraDisplayData), JPATH_NENO_LAYOUTS); ?>
		<input type="hidden" name="limitstart" id="limitstart" value="0"/>
		<input type="hidden" name="list_limit" id="list_limit" value="30"/>
	</form>
	<div id="filter-tags-wrapper"></div>
	<div id="results-wrapper">
			<span id="editor-strings-title">
				<?php echo JText::_('COM_NENO_EDITOR_STRINGS'); ?>:
			</span>

		<div id="elements-wrapper">
			<?php echo JLayoutHelper::render('editorstrings', $this->items, JPATH_NENO_LAYOUTS); ?>
		</div>
	</div>
</div>


<div id="j-main-container" class="span10">
	<input type="hidden" name="default_translate_action" id="default_translate_action" value="<?php echo $this->defaultAction; ?>">
	<div id="editor-wrapper">
		<?php echo JLayoutHelper::render('editor', null, JPATH_NENO_LAYOUTS); ?>
	</div>
</div>

<div class="modal hide fade" id="consolidate-modal">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">    
		        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h3><?php echo JText::_('COM_NENO_CONSOLIDATE_TRANSLATION_HEADER'); ?></h3>
            </div>
            <div class="modal-body">
                <p></p>
            </div>
            <div class="modal-footer">
                <a href="#" class="btn" data-dismiss="modal" aria-hidden="true"><?php echo JText::_('COM_NENO_CONSOLIDATE_TRANSLATION_NO'); ?></a>
                <a href="#" class="btn btn-primary" id="consolidate-button"><?php echo JText::_('COM_NENO_CONSOLIDATE_TRANSLATION_YES'); ?></a>
            </div>
        </div>
    </div>
</div>

<div class="modal hide fade" id="consolidate-confirm-modal">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
		        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h3><?php echo JText::_('COM_NENO_CONSOLIDATE_TRANSLATION_HEADER'); ?></h3>
            </div>
            <div class="modal-body">
                <p><?php echo JText::_('COM_NENO_CONSOLIDATE_TRANSLATION_CONFIRM'); ?></p>
            </div>
            <div class="modal-footer">
                <a href="#" class="btn" data-dismiss="modal" aria-hidden="true"><?php echo JText::_('JTOOLBAR_CLOSE'); ?></a>
            </div>
        </div>
    </div>
</div>
