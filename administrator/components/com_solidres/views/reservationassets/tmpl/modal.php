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

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;

$app = Factory::getApplication();

if ($app->isClient('site'))
{
	Session::checkToken('get') or die(Text::_('JINVALID_TOKEN'));
}

HTMLHelper::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_solidres/helpers/html');
HTMLHelper::_('bootstrap.tooltip');

$function  = $app->input->getCmd('function', 'jSelectReservationAsset');
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
?>
<form action="<?php echo Route::_('index.php?option=com_solidres&view=reservationassets&layout=modal&tmpl=component&function=' . $function . '&' . Session::getFormToken() . '=1'); ?>"
      method="post" name="adminForm" id="adminForm" class="form-inline">
    <fieldset class="filter clearfix">
        <div class="btn-toolbar">
            <div class="btn-group pull-left">
                <label for="filter_search">
					<?php echo Text::_('JSEARCH_FILTER_LABEL'); ?>
                </label>
            </div>
            <div class="btn-group pull-left">
                <input type="text" name="filter_search" id="filter_search"
                       value="<?php echo $this->escape($this->state->get('filter.search')); ?>" size="30"
                       title="<?php echo Text::_('SR_FILTER_SEARCH_DESC'); ?>"/>
            </div>
            <div class="btn-group pull-left">
                <button type="submit" class="btn hasTooltip"
                        title="<?php echo HTMLHelper::tooltipText('JSEARCH_FILTER_SUBMIT'); ?>" data-placement="bottom">
                    <span class="icon-search"></span><?php echo '&#160;' . Text::_('JSEARCH_FILTER_SUBMIT'); ?>
                </button>
                <button type="button" class="btn hasTooltip"
                        title="<?php echo HTMLHelper::tooltipText('JSEARCH_FILTER_CLEAR'); ?>" data-placement="bottom"
                        onclick="document.id('filter_search').value='';this.form.submit();">
                    <span class="icon-remove"></span><?php echo '&#160;' . Text::_('JSEARCH_FILTER_CLEAR'); ?></button>
            </div>
            <div class="clearfix"></div>
        </div>
        <hr class="hr-condensed"/>
        <div class="filters pull-left">
            <select name="filter_published" class="input-medium" onchange="this.form.submit()">
                <option value=""><?php echo Text::_('JOPTION_SELECT_PUBLISHED'); ?></option>
				<?php echo HTMLHelper::_('select.options', HTMLHelper::_('jgrid.publishedOptions'), 'value', 'text', $this->state->get('filter.state'), true); ?>
            </select>
            <select name="filter_category_id" class="input-medium" onchange="this.form.submit()">
                <option value=""><?php echo Text::_('JOPTION_SELECT_CATEGORY'); ?></option>
				<?php echo HTMLHelper::_('select.options', HTMLHelper::_('category.options', 'com_solidres'), 'value', 'text', $this->state->get('filter.category_id')); ?>
            </select>
        </div>
    </fieldset>

    <table class="table table-striped table-condensed">
        <thead>
        <tr>
            <th class="title">
				<?php echo HTMLHelper::_('grid.sort', 'JGLOBAL_TITLE', 'a.title', $listDirn, $listOrder); ?>
            </th>
            <th width="15%" class="center nowrap">
				<?php echo HTMLHelper::_('grid.sort', 'JGRID_HEADING_ACCESS', 'access_level', $listDirn, $listOrder); ?>
            </th>
            <th width="15%" class="center nowrap">
				<?php echo HTMLHelper::_('grid.sort', 'JCATEGORY', 'a.catid', $listDirn, $listOrder); ?>
            </th>
            <th width="5%" class="center nowrap">
				<?php echo HTMLHelper::_('grid.sort', 'JDATE', 'a.created', $listDirn, $listOrder); ?>
            </th>
            <th width="1%" class="center nowrap">
				<?php echo HTMLHelper::_('grid.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
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
		foreach ($this->items as $i => $item) :
			$lang2 = '';
			?>
			<?php if ($item->language && Multilanguage::isEnabled())
		{
			$tag = strlen($item->language);
			if ($tag == 5)
			{
				$lang2 = substr($item->language, 0, 2);
			}
            elseif ($tag == 6)
			{
				$lang2 = substr($item->language, 0, 3);
			}
			else
			{
				$lang2 = "";
			}
		}
        elseif (!Multilanguage::isEnabled())
		{
			$lang2 = "";
		}
			?>
            <tr class="row<?php echo $i % 2; ?>">
                <td>
                    <a href="javascript:void(0)"
                       onclick="if (window.parent) window.parent.<?php echo $this->escape($function); ?>('<?php echo $item->id; ?>', '<?php echo $this->escape(addslashes($item->name)); ?>', '<?php echo $this->escape($item->category_id); ?>', null, '<?php echo $this->escape(''); ?>', '<?php echo $this->escape($lang2); ?>', null);">
						<?php echo $this->escape($item->name); ?></a>
                </td>
                <td class="center">
					<?php echo $this->escape($item->access_level); ?>
                </td>
                <td class="center">
					<?php echo $this->escape($item->category_name); ?>
                </td>

                <td class="center nowrap">
					<?php echo HTMLHelper::_('date', $item->created_date, Text::_('DATE_FORMAT_LC4')); ?>
                </td>
                <td class="center">
					<?php echo (int) $item->id; ?>
                </td>
            </tr>
		<?php endforeach; ?>
        </tbody>
    </table>

    <div>
        <input type="hidden" name="task" value=""/>
        <input type="hidden" name="boxchecked" value="0"/>
        <input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>"/>
        <input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>"/>
		<?php echo HTMLHelper::_('form.token'); ?>
    </div>
</form>
