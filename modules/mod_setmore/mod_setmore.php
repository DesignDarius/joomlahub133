<?php
/**
 * @package 	Setmore
 * @version 	v20140123
 * @author 		Anand Thiyagarasu
 * @license 	https://www.gnu.org/licenses/gpl-3.0.html; see LICENSE.txt
 * @link		https://www.setmore.com
 */

//No direct access
defined("_JEXEC") or die('Access Deny');
$setmoreUsername=$params->get('setmore_username');

$doc = JFactory::getDocument();
$doc->addScript('https://assets.setmore.com/integration/marketplace/js/setmoreBooking.js');
?>
<!-- Loads the booking page button link -->
<a id="Setmore_button_iframe" style="float:none" href="https://booking.setmore.com/scheduleappointment/<?php echo $setmoreUsername; ?>">
<img border="none" src="https://assets.setmore.com/setmore/images/2.0/Settings/book-now-black.svg" alt="Book an appointment with Salon using Setmore" /></a>
