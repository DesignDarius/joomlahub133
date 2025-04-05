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
 * /templates/TEMPLATENAME/html/com_solidres/reservationasset/default_userinfo.php
 *
 * However, occasionally we will need to update template/layout related files and it is the template developers'
 * responsibility to update the overridden files (if any) to maintain full compatibility with Solidres.
 *
 * We do not provide support if any of the overridden files are out of date and are not compatible with Solidres.
 *
 * @version 2.8.0
 */

defined('_JEXEC') or die;
$user       = JFactory::getUser();
$name       = '<a href="' . JRoute::_('index.php?option=com_solidres&view=customer', false) . '"><strong>' . $user->get('name') . '</strong></a>';

$uri = JUri::getInstance();
if ($this->enableAutoScroll) :
	$uri->setFragment('form');
else :
	$uri->setFragment('');
endif;

$actionLink = JRoute::_($uri->toString(), true);
?>
<div class="sr-user-info">
	<form action="<?php echo $actionLink; ?>" method="post">
		<?php echo '<i class="fa fa-sign-out-alt"></i>' . JText::sprintf( 'SR_USER_INFO_USERNAME_PLURAL', $name ); ?>
		<button type="submit" name="Submit" class="btn btn-secondary">
			<?php echo JText::_( 'JLOGOUT' ); ?>
		</button>
		<input type="hidden" name="option" value="com_users"/>
		<input type="hidden" name="task" value="user.logout"/>
		<input type="hidden" name="return" value="<?php echo base64_encode( $actionLink ); ?>"/>
		<?php echo JHtml::_( 'form.token' ); ?>
	</form>
</div>
