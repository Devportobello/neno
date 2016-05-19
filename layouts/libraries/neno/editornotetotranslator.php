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
$translation = $displayData;

?>
<div class="pull-left">
    
    <?php if (!empty($translation)): ?>
        <?php if (empty($translation->comment)): ?>
            <div class="add-comment-to-translator">
                <a
                    href="#addTranslatorComment<?php echo $translation->id; ?>"
                    data-toggle="modal">
                    <span class="icon-pencil"></span>
                    <?php echo JText::_('COM_NENO_COMMENTS_TO_TRANSLATOR_GENERAL_CREATE'); ?>
                </a>
            </div>
        <?php else: ?>
            <div class="add-comment-to-translator" data-toggle="modal" data-target="#addTranslatorComment<?php echo $translation->id; ?>">
                <a
                    href="#addTranslatorComment<?php echo $translation->id; ?>"
                    data-toggle="tooltip" 
                    data-placement="bottom" 
                    title="<?php echo str_replace('"','&quot;', $translation->comment); ?>"
                    >
                    <span class="icon-pencil"></span>
                    <?php echo JText::_('COM_NENO_COMMENTS_TO_TRANSLATOR_EDITOR_DISPLAY_COMMENT_TITLE'); ?>
                </a>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>


<div id="addTranslatorComment<?php echo $translation->id; ?>"
     class="modal hide fade comment-modal"
     tabindex="-1" role="dialog" aria-labelledby="modalLabel" aria-hidden="true">
    <div class="modal-body">
        <h3 class="modalLabel"><?php echo JText::_('COM_NENO_COMMENTS_TO_TRANSLATOR_GENERAL_MODAL_ADD_TITLE'); ?></h3>

        <p><?php echo JText::_('COM_NENO_COMMENTS_TO_TRANSLATOR_MODAL_ADD_BODY_PRE'); ?></p>

        <p><?php echo JText::sprintf('COM_NENO_COMMENTS_TO_TRANSLATOR_EDITOR_MODAL_ADD_BODY', JRoute::_('index.php?option=com_neno&view=externaltranslations&open=comment'), $translation->language, JRoute::_('index.php?option=com_neno&view=dashboard')); ?></p>

        <div class="one-lang">
            <p><?php echo JText::sprintf('COM_NENO_COMMENTS_TO_TRANSLATOR_MODAL_ADD_BODY_POST', NenoSettings::get('source_language'), $translation->language); ?></p>
        </div>

        <div class="all-lang">
            <p class="text-danger"><?php echo JText::sprintf('COM_NENO_COMMENTS_TO_TRANSLATOR_GENERAL_MODAL_ADD_BODY_POST', NenoSettings::get('source_language')); ?></p>
        </div>

        <p><textarea class="comment-to-translator"><?php echo empty($translation->comment) ? '' : $translation->comment; ?></textarea></p>
    </div>
    <div class="modal-footer">
        <p>
            <input type="checkbox" id="comment-all-langs-<?php echo $translation->id; ?>" class="comment-check-all"
                   data-content-id="<?php echo $translation->content_id; ?>"/>
            <label for="comment-all-langs-<?php echo $translation->id; ?>">
                <?php echo JText::_('COM_NENO_COMMENTS_TO_TRANSLATOR_EDITOR_ALL_LANGS_CHECK'); ?>
            </label>
            <input type="checkbox" id="comment-check-<?php echo $translation->id; ?>" class="comment-check"
                   data-content-id="<?php echo $translation->content_id; ?>"/>
            <label for="comment-check-<?php echo $translation->id; ?>">
                <?php echo JText::_('COM_NENO_COMMENTS_TO_TRANSLATOR_EDITOR_MODAL_CHECK_LABEL'); ?>
            </label>
            <label for="comment-check-<?php echo $translation->id; ?>" class="comment-breadcrumbs">
                <?php echo implode(' &gt; ', $translation->breadcrumbs); ?>
            </label>
            <input type="checkbox" id="comment-filters-<?php echo $translation->id; ?>" class="comment-check"
                   data-content-id="<?php echo $translation->content_id; ?>"/>
            <label for="comment-filters-<?php echo $translation->id; ?>">
                <?php echo JText::_('COM_NENO_COMMENTS_TO_TRANSLATOR_EDITOR_MODAL_FILTERS_LABEL'); ?>
            </label>
            <label for="comment-filters-<?php echo $translation->id; ?>" class="comment-breadcrumbs">
                <?php echo implode(' &gt; ', $translation->breadcrumbs); ?>
            </label>
        </p>
        <a href="#" class="btn" data-dismiss="modal" aria-hidden="true">
            <?php echo JText::_('COM_NENO_COMMENTS_TO_TRANSLATOR_MODAL_BTN_CLOSE'); ?>
        </a>
        <a href="#" class="btn btn-primary save-translation-comment" data-translation="<?php echo $translation->id; ?>">
            <?php echo JText::_('COM_NENO_COMMENTS_TO_TRANSLATOR_MODAL_BTN_SAVE'); ?>
        </a>
    </div>
</div>
<script>
    jQuery(document).ready(function(){
        jQuery('.all-lang').hide();

        jQuery('.comment-check-all').change(function(){
            if (jQuery(this).prop('checked') == true) {
                jQuery('.one-lang').hide();
                jQuery('.all-lang').show();
            }
            else {
                jQuery('.all-lang').hide();
                jQuery('.one-lang').show();
            }
        });
    });
</script>
