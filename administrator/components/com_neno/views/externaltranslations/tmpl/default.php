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

$listOrder     = $this->state->get('list.ordering');
$listDirection = $this->state->get('list.direction');

// Joomla Component Creator code to allow adding non select list filters
if (!empty($this->extra_sidebar))
{
	$this->sidebar .= $this->extra_sidebar;
}

?>

<style>
	.translation-type, .information-box {
		border: 1px solid #ccc;
		margin: 30px 0;
	}
	
	.translation-type .translation-type-header, .translation-type .translation-type-footer {
		background-color: #eee;
	}
	
	.translation-type > div {
		padding: 15px;
	}
	
	.translation-type .translation-introtext {
		color: #888;
	}
	
	.translation-type .translation {
		padding: 20px 15px;
	}
	
	.information-box {
		padding: 20px 15px;
	}
</style>

<script>
	jQuery(document).ready(function () {
		jQuery('.translate_automatically_setting').on('click', function () {
			jQuery.post(
				'index.php?option=com_neno&task=externaltranslations.setAutomaticTranslationSetting',
				{
					setting: jQuery(this).data('setting'),
					value: +jQuery(this).is(':checked')
				}
				, function (data) {
					if (data != 'ok') {
						alert("There was an error saving setting");
					}
				}
			);
		});
	});
</script>

<div id="j-sidebar-container" class="span2">
	<?php echo $this->sidebar; ?>
</div>
<div id="j-main-container" class="span10">
	<div class="span9">
		<div id="elements-wrapper">
			<h1><?php echo JText::_('COM_NENO_TITLE_EXTERNALTRANSLATIONS'); ?></h1>
			
			<p>
				<?php echo JText::_('COM_NENO_EXTERNALTRANSLATION_INTROTEXT'); ?>
			</p>
			
			<div class="translation-type">
				<div class="translation-type-header">
					<h3>
					<span
						class="icon-screen"></span> <?php echo JText::_('COM_NENO_EXTERNALTRANSLATION_MACHINE_TRANSLATION_TITLE'); ?>
					</h3>
					
					<p class="translation-introtext">
						<?php echo JText::sprintf('COM_NENO_EXTERNALTRANSLATION_MACHINE_TRANSLATION_INTROTEXT', '#'); ?>
					</p>
				</div>
				<div class="translation-type-content">
					<?php foreach ($this->items as $key => $item): ?>
						<?php if ($item->translation_method == 'machine'): ?>
							<div class="translation">
								<div class="span3">
									<?php echo $item->language; ?>
								</div>
								<div class="span3">
									<?php echo JText::sprintf('COM_NENO_EXTERNALTRANSLATION_WORDS', $item->words); ?>
								</div>
								<div class="span3">
									<?php echo JText::sprintf('COM_NENO_EXTERNALTRANSLATION_PRICE'); ?> <?php echo $item->words; ?>
									TC
								</div>
								<div class="span3">
									<button type="button" class="btn">
										<?php echo JText::_('COM_NENO_EXTERNALTRANSLATION_ORDER_NOW'); ?>
									</button>
								</div>
							</div>
							<?php unset($this->items[$key]); ?>
						<?php endif; ?>
					<?php endforeach; ?>
				</div>
				<div class="translation-type-footer">
					<input type="checkbox" class="translate_automatically_setting"
					       data-setting="translate_automatically_machine"
					       name="machine_translation" <?php echo NenoSettings::get('translate_automatically_machine') ? 'checked="checked"' : ''; ?>
					       value="1"/> <?php echo JText::_('COM_NENO_EXTERNALTRANSLATION_AUTOMATICALLY_MACHINE_TRANSLATE'); ?>
				</div>
			</div>
			
			<div class="translation-type">
				<div class="translation-type-header">
					<h3>
					<span
						class="icon-users"></span> <?php echo JText::_('COM_NENO_EXTERNALTRANSLATION_PROFESSIONAL_TRANSLATION_TITLE'); ?>
					</h3>
					
					<p class="translation-introtext">
						<?php echo JText::sprintf('COM_NENO_EXTERNALTRANSLATION_PROFESSIONAL_TRANSLATION_INTROTEXT', '#'); ?>
					</p>
				</div>
				<div class="translation-type-content">
					<?php foreach ($this->items as $key => $item): ?>
						<?php if ($item->translation_method == 'pro'): ?>
							<div class="translation">
								<div class="span3">
									<?php echo $item->language; ?>
								</div>
								<div class="span3">
									<?php echo JText::sprintf('COM_NENO_EXTERNALTRANSLATION_WORDS', $item->words); ?>
								</div>
								<div class="span3">
									<?php echo JText::sprintf('COM_NENO_EXTERNALTRANSLATION_PRICE'); ?> <?php echo $item->words; ?>
									TC
								</div>
								<div class="span3">
									<button type="button" class="btn">
										<?php echo JText::_('COM_NENO_EXTERNALTRANSLATION_ORDER_NOW'); ?>
									</button>
								</div>
							</div>
						<?php endif; ?>
					<?php endforeach; ?>
				</div>
				<div class="translation-type-footer">
					<input type="checkbox" class="translate_automatically_setting"
					       data-setting="translate_automatically_professional"
					       name="machine_translation" <?php echo NenoSettings::get('translate_automatically_professional') ? 'checked="checked"' : ''; ?>
					       value="1"/> <?php echo JText::_('COM_NENO_EXTERNALTRANSLATION_AUTOMATICALLY_PROFESSIONAL_TRANSLATE'); ?>
				</div>
			</div>
		</div>
	</div>
	<div class="span3">
		<div class="information-box span11 pull-right">
			<div class="center">
				<div>
					<span>ICON</span>
				</div>
				<div>
					<p class="center">
						<?php echo JText::sprintf('COM_NENO_EXTERNALTRANSLATION_BUY_TC_TEXT', '7.345'); ?>
					</p>
				</div>
				<div class="center">
					<h3><?php echo JText::sprintf('COM_NENO_EXTERNALTRANSLATION_PRICE'); ?> €74</h3>
				</div>
				<div class="center">
					<a href="#" class="btn btn-success">
						<?php echo JText::_('COM_NENO_EXTERNALTRANSLATION_BUY_TC_BUTTON'); ?>
					</a>
				</div>
			</div>
		</div>
		<div class="information-box span11 pull-right">
			<div class="center">
				<div>
					<span>ICON</span>
				</div>
				<div>
					<p class="left">
						Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut
						labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco
						laboris nisi ut aliquip ex ea commodo consequat.
					</p>
				</div>
				<div>
					<p>
						Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque
						laudantium.
					</p>
				</div>
			</div>
		</div>
	</div>
</div>
