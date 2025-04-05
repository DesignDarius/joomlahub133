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

/*
 * This layout file can be overridden by copying to:
 *
 * /templates/TEMPLATENAME/html/layouts/com_solidres/reservation/note.php
 *
 * However, occasionally we will need to update template/layout related files and it is the template developers'
 * responsibility to update the overridden files (if any) to maintain full compatibility with Solidres.
 *
 * We do not provide support if any of the overridden files are out of date and are not compatible with Solidres.
 *
 * @version 2.13.1
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

extract($displayData);

$attachmentPath = SRURI_MEDIA . '/notes/' . $note->id;

?>

<div class="reservation-note-item">
    <p class="info">
		<?php echo $note->created_date ?> by <?php echo $note->username ?>
    </p>
    <p>
		<?php echo Text::_("SR_RESERVATION_NOTE_NOTIFY_CUSTOMER") ?>
        : <?php echo $note->notify_customer == 1 ? Text::_('JYES') : Text::_('JNO') ?>
        |
		<?php echo Text::_("SR_RESERVATION_NOTE_DISPLAY_IN_FRONTEND") ?>
        : <?php echo $note->visible_in_frontend == 1 ? Text::_('JYES') : Text::_('JNO') ?></p>
    <p>
		<?php echo $note->text ?>
    </p>
    <?php
    if (!empty($note->attachments)) :
        echo '<ul>';
        foreach ($note->attachments as $attachment) :
            echo '<li><a href="' . $attachmentPath . '/' . $attachment . '">' . $attachment . '</a></li>';
        endforeach;
	    echo '</ul>';
    endif;
    ?>
</div>