<?php
/* ------------------------------------------------------------------------
  Solidres - Hotel booking extension for Joomla
  ------------------------------------------------------------------------
  @Author    Solidres Team
  @Website   http://www.solidres.com
  @Copyright Copyright (C) 2013 - 2017 Solidres. All Rights Reserved.
  @License   GNU General Public License version 3, or later
  ------------------------------------------------------------------------ */

/*
 * This layout file can be overridden by copying to:
 *
 * /templates/TEMPLATENAME/html/com_solidres/customer/feedbacks.php
 *
 * However, occasionally we will need to update template/layout related files and it is the template developers'
 * responsibility to update the overridden files (if any) to maintain full compatibility with Solidres.
 *
 * We do not provide support if any of the overridden files are out of date and are not compatible with Solidres.
 *
 * @version 2.8.0
 */

defined('_JEXEC') or die;
$displayData = array(
	'view'   => $this->feedbackView,
	'items'  => $this->feedbackView->getModel()->awaitingFeedbacks,
	'return' => JUri::getInstance()->toString(),
);
$customerId  = $this->modelReservations->getState('filter.customer_id');
?>
<?php echo SRLayoutHelper::render('customer.navbar', array('customer_id' => $customerId)); ?>
<div id="sr_panel_feedbacks">
	<?php echo JHtml::_('bootstrap.startTabSet', 'myfeedback', array('active' => 'feedback-list')) ?>

	<?php echo JHtml::_('bootstrap.addTab', 'myfeedback', 'feedback-list', JText::_('SR_CUSTOMER_DASHBOARD_MY_FEEDBACK', true)) ?>
	<?php echo SRLayoutHelper::render('feedbacks.list', $displayData); ?>
	<?php echo JHtml::_('bootstrap.endTab') ?>

	<?php echo JHtml::_('bootstrap.addTab', 'myfeedback', 'feedback-awaiting', JText::_('SR_CUSTOMER_DASHBOARD_MY_FEEDBACK_AWAITING', true)) ?>
	<?php echo SRLayoutHelper::render('feedbacks.awaiting', $displayData); ?>
	<?php echo JHtml::_('bootstrap.endTab') ?>

	<?php echo JHtml::_('bootstrap.endTabSet') ?>
</div>