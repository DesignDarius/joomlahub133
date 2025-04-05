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

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Layout\LayoutHelper;

defined('_JEXEC') or die;

HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');

$input        = Factory::getApplication()->input;
$field        = $input->getCmd('field');
$listOrder    = $this->escape($this->state->get('list.ordering'));
$listDirn     = $this->escape($this->state->get('list.direction'));
$function     = 'jSelectPartner_' . $field;
$userRequired = (int) $input->get('required', 0, 'int');
$onClick      = "window.parent.jSelectUser(this);window.parent.Joomla.Modal.getCurrent().close()";
?>
<form action="<?php echo Route::_('index.php?option=com_solidres&view=customers&layout=modal&tmpl=component&groups=' . $input->get('groups', '', 'BASE64') . '&excluded=' . $input->get('excluded', '', 'BASE64')); ?>"
      method="post" name="adminForm" id="adminForm">

	<?php if (!$userRequired) : ?>
		<button type="button" class="btn btn-primary button-select"
		        data-user-value="0"
		        data-user-name="<?php echo $this->escape(Text::_('JLIB_FORM_SELECT_USER')); ?>"
		        data-user-field="<?php echo $this->escape($field); ?>">
			<?php echo Text::_('JOPTION_NO_USER'); ?>
		</button>
	<?php endif; ?>

	<?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>

	<?php if (empty($this->items)) : ?>
		<div class="alert alert-info">
			<span class="icon-info-circle" aria-hidden="true"></span><span
					class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
			<?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
		</div>
	<?php else : ?>

		<table class="table table-striped table-condensed">
			<thead>
			<tr>
				<th class="left">
					<?php echo HTMLHelper::_('grid.sort', 'SR_HEADING_CUSTOMER_FULLNAME', 'a.name', $listDirn, $listOrder); ?>
				</th>
				<th class="nowrap w-25">
					<?php echo HTMLHelper::_('grid.sort', 'JGLOBAL_USERNAME', 'a.username', $listDirn, $listOrder); ?>
				</th>
				<th class="nowrap w-25">
					<?php echo Text::_('SR_HEADING_CUSTOMER_GROUP_NAME'); ?>
				</th>
			</tr>
			</thead>
			<tfoot>
			<tr>
				<td colspan="15">
					<?php echo $this->pagination->getListFooter(); ?>
				</td>
			</tr>
			</tfoot>
			<tbody>
			<?php
			$i             = 0;
			foreach ($this->items as $item) :
				$fullName = $this->escape($item->firstname . ' ' . $item->middlename . ' ' . $item->lastname);
				$groupName = is_null($item->group_name) ? Text::_('SR_GENERAL_CUSTOMER_GROUP') : $item->group_name;
				?>
				<tr class="row<?php echo $i % 2; ?>">
					<td>
						<a class="pointer button-select" href="#"
						   data-user-value="<?php echo $item->id; ?>"
						   data-user-name="<?php echo $this->escape($item->jusername); ?>"
						   data-user-field="<?php echo $this->escape($field); ?>">
							<?php echo $fullName; ?>
						</a>
					</td>
					<td>
						<a class="pointer button-select" href="#"
						   data-user-value="<?php echo $item->id; ?>"
						   data-user-name="<?php echo $this->escape($item->jusername); ?>"
						   data-user-field="<?php echo $this->escape($field); ?>">
							<?php echo $this->escape($item->jusername); ?></a>
					</td>
					<td>
						<?php echo $groupName; ?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
	<input type="hidden" name="task" value=""/>
	<input type="hidden" name="field" value="<?php echo $this->escape($field); ?>"/>
	<input type="hidden" name="boxchecked" value="0"/>
	<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>"/>
	<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>"/>
	<?php echo HTMLHelper::_('form.token'); ?>
</form>