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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\String\Inflector;
use Joomla\CMS\Uri\Uri;

$updateInfo    = $displayData['updateInfo'];
$menuStructure = $displayData['menuStructure'];
$iconMap       = $displayData['iconMap'];
$inflector     = Inflector::getInstance();

if ($inflector->isPlural($displayData['viewName']))
{
	$viewNames = [$displayData['viewName'], $inflector->toSingular($displayData['viewName'])];
}
else
{
	$viewNames = [$displayData['viewName'], $inflector->toPlural($displayData['viewName'])];
}

$app = Factory::getApplication();
$context    = $app->input->get('context');
$view       = $app->input->get('view');
$scope      = $app->getUserState('com_solidres.' . $view . '.filter.scope', $app->input->get('scope', 0, 'uint'));
$isSingular = $inflector->isSingular($displayData['viewName']) && 'expdashboard' !== $displayData['viewName'];

?>
<div id="sr_panel_left" class="<?php echo SR_UI_GRID_COL_2 ?>">
    <ul id="sr_side_navigation"
        class="<?php echo $isSingular ? 'disabled' : ''; ?>">
        <li class="sr_tools">
            <a href="#" id="sr-toggle">
                <i class="fa fa-chevron-circle-left"></i>
            </a>
            <a id="sr_dashboard" href="<?php echo Route::_('index.php?option=com_solidres', false); ?>"
               title="<?php echo Text::_('SR_SUBMENU_DASHBOARD', true); ?>">
                <img src="<?php echo Uri::root(true); ?>/media/com_solidres/assets/images/logo.png" alt="Solidres"
                     title="Solidres"/>
            </a>
            <span id="sr_current_ver">
				<?php echo SRVersion::getShortVersion(); ?>
				<?php if (isset($updateInfo['com_solidres']) && version_compare(SRVersion::getBaseVersion(), $updateInfo['com_solidres'], 'lt')): ?>
                    <a href="https://www.solidres.com/download/show-all-downloads/solidres"
                       id="sr-update-note"
                       target="_blank"
                       title="New update (v<?php echo $updateInfo['com_solidres']; ?>) is available">
						<i class="fa fa-exclamation-triangle"></i>
					</a>
				<?php else: ?>
                    <i title="You are using the latest version" class="fa fa-check"></i>
				<?php endif; ?>
			</span>
        </li>
		<?php
        foreach ($menuStructure as $menuName => $menuDetails):
			$name = strtolower(substr($menuName, 11));
            $subMenuHtml = '';
            $activeMenuItemsCount = 0;
			foreach ($menuDetails as $menu):
                $isActive = false;
                $parts = parse_url($menu[1]);
                parse_str($parts['query'], $query);

                if (isset($query['view']) && in_array($query['view'], $viewNames))
                {
                    if ((isset($query['context']) && $query['context'] == $context)
                        || (isset($query['scope']) && $query['scope'] == $scope)
                        || (!isset($query['context']) && !isset($query['scope']))
                    )
                    {
                        $isActive = true;
	                    $activeMenuItemsCount ++;
                    }
                }

                $subMenuHtml .= "
                <li " . ($isActive ? 'class="active"' : '') . ">
                    <a href=\"" . Route::_($menu[1]) . "\"
                       id=\"" . strtolower($menu[0]) . "\">
                        " . Text::_($menu[0]) . "
                    </a>
                </li>";
		    endforeach;
            ?>
            <li class="sr_toggle<?php echo $activeMenuItemsCount > 0 ? ' active' : '' ?>" id="sr_sn_<?php echo $name; ?>">
                <a class="sr_indicator" style="cursor: pointer"><i class="fa fa-caret-down"></i></a>
                <a class="sr_title">
                    <i class="<?php echo $iconMap[$name]; ?>"></i>
                    <span><?php echo Text::_($menuName); ?></span>
                </a>
                <ul>
                    <?php echo $subMenuHtml ?>
                </ul>
            </li>
		<?php endforeach; ?>
    </ul>
</div>
