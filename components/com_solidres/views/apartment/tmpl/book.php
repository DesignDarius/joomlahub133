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
 * /templates/TEMPLATENAME/html/com_solidres/apartment/book.php
 *
 * However, occasionally we will need to update template/layout related files and it is the template developers'
 * responsibility to update the overridden files (if any) to maintain full compatibility with Solidres.
 *
 * We do not provide support if any of the overridden files are out of date and are not compatible with Solidres.
 *
 * @version 2.12.0
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

$layout = SRLayoutHelper::getInstance();

?>

<div id="solidres">
    <div id="apartment-book-form">
	    <?php echo $layout->render('asset.guestform', $this->displayData); ?>
    </div>
</div>

<form id="sr-reservation-form-confirmation"
        enctype="multipart/form-data"
        action="<?php echo Route::_("index.php?option=com_solidres&task=reservation.save&Itemid=" . $this->menu->id) ?>"
        method="POST">
    <input type="hidden" name="return" value="<?php echo base64_encode(Uri::getInstance()->toString()) ?>"/>
	<?php echo HTMLHelper::_("form.token") ?>
</form>
<script>
    const tc = document.getElementById('termsandconditions'),
        btnBooks = document.querySelectorAll('.btn-book');

    if (tc) {
        tc.addEventListener('change', function () {
            btnBooks.forEach(function (btnBook) {
                if (tc.checked) {
                    btnBook.removeAttribute('disabled');
                } else {
                    btnBook.setAttribute('disabled', '');
                }
            });
        });
    }
</script>

