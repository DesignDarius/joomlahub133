<?php
/**
 ------------------------------------------------------------------------
 SOLIDRES - Accommodation booking extension for Joomla
 ------------------------------------------------------------------------
 * @author    Solidres Team <contact@solidres.com>
 * @website   https://www.solidres.com
 * @copyright Copyright (C) 2013 Solidres. All Rights Reserved.
 * @license   GNU General Public License version 3, or later
 ------------------------------------------------------------------------
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Utility\Utility;

$allowedFileTypes = $this->solidresConfig->get('reservation_note_allowed_file_types', 'jpg,jpeg,png,pdf,doc,docx');
$allowedFileTypesArray = explode(',', $allowedFileTypes);
?>

<div class="<?php echo SR_UI_GRID_CONTAINER ?>">
    <div class="<?php echo SR_UI_GRID_COL_6 ?>">
        <form id="reservationnote-form"
              action="index.php?option=com_solidres&task=reservationnote.save&format=json">
            <div class="mb-3">
                <textarea rows="5" name="text" class="form-control"
                  placeholder="<?php echo Text::_('SR_RESERVATION_NOTE_PLACEHOLDER') ?>"></textarea>
            </div>
            <div class="mb-3">
                <input type="file" class="form-control" name="attachments[]" accept="<?php echo '.' . implode(',.', $allowedFileTypesArray) ?>" multiple>
	            <?php echo Text::sprintf('JGLOBAL_MAXIMUM_UPLOAD_SIZE_LIMIT', HTMLHelper::_('number.bytes', Utility::getMaxUploadSize())); ?>
            </div>
            <div class="form-check mb-3">
                <input type="checkbox" class="form-check-input" name="notify_customer" value="1">
                <label class="form-check-label"><?php echo Text::_("SR_RESERVATION_NOTE_NOTIFY_CUSTOMER") ?></label>
            </div>
            <div class="form-check mb-3">
                <input type="checkbox" class="form-check-input" name="visible_in_frontend" value="1">
                <label class="form-check-label"><?php echo Text::_("SR_RESERVATION_NOTE_DISPLAY_IN_FRONTEND") ?></label>
            </div>

            <div class="processing nodisplay"></div>
            <button type="submit" class="btn btn-primary"><?php echo Text::_("SR_SUBMIT") ?></button>
            <input name="reservation_id" type="hidden" value="<?php echo $this->reservationId ?>"/>
			<?php echo HTMLHelper::_('form.token'); ?>

        </form>
    </div>
    <div id="reservation-note-holder" class="<?php echo SR_UI_GRID_COL_6 ?>">
		<?php
		$notes = $this->form->getValue('notes', []);
		if (!empty($notes)) :
			foreach ($notes as $note) :
				echo SRLayoutHelper::render('reservation.note', ['note' => $note]);
			endforeach;
		else:
			echo '<div class="alert alert-info">' . Text::_('SR_NO_NOTES') . '</div>';
		endif;
		?>
    </div>
</div>