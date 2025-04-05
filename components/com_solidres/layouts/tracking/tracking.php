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
 * /templates/TEMPLATENAME/html/layouts/com_solidres/tracking/tracking.php
 *
 * However, occasionally we will need to update template/layout related files and it is the template developers'
 * responsibility to update the overridden files (if any) to maintain full compatibility with Solidres.
 *
 * We do not provide support if any of the overridden files are out of date and are not compatible with Solidres.
 *
 * @version 2.8.0
 */

defined('_JEXEC') or die;

$trackingCode  = $displayData['trackingCode'] ?? '';
$trackingEmail = $displayData['trackingEmail'] ?? '';
$menuId        = $displayData['menuId'] ?? '';
$scope         = empty($displayData['scope']) ? '' : 'exp';
$link          = 'index.php?option=com_solidres&view=' . ($scope) . 'tracking';


if ($trackingCode)
{
	$link .= '&trackingCode=' . $trackingCode;
}

if ($trackingEmail)
{
	$link .= '&trackingEmail=' . $trackingEmail;
}

if ($menuId)
{
	$link .= '&Itemid=' . $menuId;
}

JHtml::_('behavior.formvalidator');
JFactory::getDocument()->addScriptDeclaration('Solidres.jQuery(document).ready(function ($) {
    $("form.sr-' . ($scope == 'exp' ? $scope . '-' : '') . 'tracking-form").on("submit", function(e) {    
        return document.formvalidator.isValid(this);
    });
});');
?>

<form action="<?php echo JRoute::_($link, false); ?>" method="post"
      class="sr-<?php echo($scope == 'exp' ? $scope . '-' : '') ?>tracking-form form-validate"
      novalidate>
    <div class="mb-3">
        <label class="form-label"
               for="sr-<?php echo($scope == 'exp' ? $scope . '-' : '') ?>tracking-code">
			<?php echo JText::_('SR_' . ($scope == 'exp' ? strtoupper($scope) . '_' : '') . 'ENTER_YOUR_RESERVATION_CODE') . '*'; ?>
        </label>
            <input type="text" name="trackingCode"
                   id="sr-<?php echo($scope == 'exp' ? $scope . '-' : '') ?>tracking-code"
                   class="form-control required"
                   value="<?php echo $trackingCode; ?>"/>
    </div>
    <div class="mb-3">
        <label class="form-label"
               for="sr-<?php echo($scope == 'exp' ? $scope . '-' : '') ?>tracking-email">
			<?php echo JText::_('SR_' . ($scope == 'exp' ? strtoupper($scope) . '_' : '') . 'ENTER_YOUR_EMAIL') . '*'; ?>
        </label>
	    <input type="email" name="trackingEmail"
	           id="sr-<?php echo($scope == 'exp' ? $scope . '-' : '') ?>tracking-email"
	           class="form-control required validate-email"
	           value="<?php echo $trackingEmail; ?>"/>
    </div>
    <div class="d-grid">
        <button type="submit" class="btn btn-primary">
            <i class="fa fa-search"></i>
			<?php echo JText::_('SR_' . ($scope ? strtoupper($scope) . '_' : '') . 'FIND_RESERVATION'); ?>
        </button>
    </div>
</form>
