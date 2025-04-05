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

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Session\Session;

defined('_JEXEC') or die;

HTMLHelper::_('behavior.multiselect');
$user      = $this->getCurrentUser();
$userId    = $user->get('id');
$listOrder = $this->state->get('list.ordering');
$listDirn  = $this->state->get('list.direction');

?>

<div id="solidres">
    <div class="<?php echo SR_UI_GRID_CONTAINER; ?>">
		<?php echo SolidresHelperSideNavigation::getSideNavigation($this->getName()); ?>
        <div id="sr_panel_right" class="sr_list_view <?php echo SR_UI_GRID_COL_10; ?>">
            <form action="<?php echo Route::_('index.php?option=com_solidres&view=origins', false); ?>" method="post"
                  name="adminForm" id="adminForm">
				<?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>
                <table id="originsList" class="table table-striped">
                    <thead>
                    <tr>
                        <th width="1%" class="center nowrap">
							<?php echo HTMLHelper::_('grid.checkall'); ?>
                        </th>
                        <th width="1%" class="center nowrap hidden-phone d-none d-md-table-cell">
							<?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
                        </th>
                        <th>
							<?php echo HTMLHelper::_('searchtools.sort', 'JFIELD_NAME_LABEL', 'a.name', $listDirn, $listOrder); ?>
                        </th>
                        <th width="1%" class="center nowrap">
							<?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'a.state', $listDirn, $listOrder); ?>
                        </th>
                        <th width="1%" class="center nowrap">
							<?php echo HTMLHelper::_('searchtools.sort', 'SR_FIELD_ORIGIN_DEFAULT', 'a.is_default', $listDirn, $listOrder); ?>
                        </th>
                        <th width="1%" class="center nowrap hidden-phone d-none d-md-table-cell">
							<?php echo HTMLHelper::_('searchtools.sort', 'SR_FIELD_TAX_RATE_LABEL', 'taxRate', $listDirn, $listOrder); ?>
                        </th>
                    </tr>
                    </thead>
                    <tbody>
					<?php foreach ($this->items as $i => $item): ?>
                        <tr>
                            <td class="center nowrap">
								<?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
                            </td>
                            <td class="center nowrap hidden-phone d-none d-md-table-cell">
								<?php echo $item->id; ?>
                            </td>
                            <td>
                                <div style="display: inline-block; width: 15px; height: 15px; border: 1px solid #eee; margin-bottom: -4px; background-color: <?php echo $item->color; ?>"></div>
								<?php if ($user->authorise('core.create', 'com_solidres')
									|| $user->authorise('core.edit', 'com_solidres')) : ?>
                                    <a href="<?php echo Route::_('index.php?option=com_solidres&task=origin.edit&id=' . (int) $item->id, false); ?>">
										<?php echo $this->escape($item->name); ?>
                                    </a>
								<?php else : ?>
									<?php echo $this->escape($item->name); ?>
								<?php endif; ?>
                            </td>
                            <td class="center nowrap">
								<?php echo HTMLHelper::_('jgrid.published', $item->state, $i, 'origins.', $user->authorise('core.edit', 'com_solidres')); ?>
                            </td>
                            <td class="center nowrap">
								<?php if ($user->authorise('core.edit', 'com_solidres') && (int) $item->state === 1): ?>
									<?php if ($item->is_default) : ?>
                                        <button class="btn btn-sm btn-success" type="button" disabled>
                                            <i class="fa fa-check"></i>
                                        </button>
									<?php else: ?>
                                        <a href="<?php echo Route::_('index.php?option=com_solidres&task=origin.setDefault&id=' . $item->id . '&' . Session::getFormToken() . '=1', false); ?>"
                                           class="btn btn-sm btn-danger">
                                            <i class="fa fa-times"></i>
                                        </a>
									<?php endif; ?>
								<?php else: ?>
									<?php if ($item->is_default) : ?>
                                        <button class="btn btn-sm btn-success" type="button" disabled>
                                            <i class="fa fa-check"></i>
                                        </button>
									<?php else: ?>
                                        <button class="btn btn-sm btn-danger" type="button" disabled>
                                            <i class="fa fa-check"></i>
                                        </button>
									<?php endif; ?>
								<?php endif; ?>
                            </td>
                            <td class="center nowrap hidden-phone d-none d-md-table-cell">
								<?php echo $item->taxRate ?: 'N/A'; ?>
                            </td>
                        </tr>
					<?php endforeach; ?>
                    </tbody>
                </table>
				<?php echo $this->pagination->getListFooter(); ?>
                <input type="hidden" name="task" value=""/>
                <input type="hidden" name="boxchecked" value="0"/>
				<?php echo HTMLHelper::_('form.token'); ?>
            </form>
        </div>
    </div>
	<div class="powered">
		<p>Powered by <a href="https://www.solidres.com" target="_blank">Solidres</a></p>
	</div>
</div>