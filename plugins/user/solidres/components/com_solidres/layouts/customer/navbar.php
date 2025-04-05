<?php
/* ------------------------------------------------------------------------
  Solidres - Hotel booking extension for Joomla
  ------------------------------------------------------------------------
  @Author    Solidres Team
  @Website   http://www.solidres.com
  @Copyright Copyright (C) 2013 - 2019 Solidres. All Rights Reserved.
  @License   GNU General Public License version 3, or later
  ------------------------------------------------------------------------ */

use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory as CMSFactory;
use Joomla\CMS\HTML\HTMLHelper;

defined('_JEXEC') or die;

HTMLHelper::_('bootstrap.framework');

$uri            = Uri::getInstance();
$app            = CMSFactory::getApplication();
$layout         = $app->input->getCmd('layout', 'default');
$view           = $app->input->getCmd('view', 'myreservation');
$scope          = $app->input->get('scope', 'reservation_asset');
$Itemid         = $app->input->getUint('Itemid', 0);
$customer_id    = $displayData['customer_id'];
$language       = $app->getLanguage()->getTag();
$redirect       = base64_encode(Uri::getInstance()->toString());
$solidresConfig = ComponentHelper::getParams('com_solidres');
$mainActivity   = $solidresConfig->get('main_activity', '');
$showAll        = $mainActivity === '';
$propertyOnly   = $mainActivity === '0';
$experienceOnly = $mainActivity === '1';
$expInvoice     = SRPlugin::isEnabled('experienceinvoice');

?>
<nav class="navbar navbar-expand-lg navbar-light bg-light hub-navbar navbar-default mb-3">
	<div class="container-fluid">
		<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sr-customer-navbar"
		        aria-controls="sr-customer-navbar" aria-expanded="false" aria-label="Toggle navigation">
			<span class="navbar-toggler-icon"></span>
		</button>

		<div class="collapse navbar-collapse" id="sr-customer-navbar">
			<ul class="navbar-nav">

				<?php if ($showAll || $propertyOnly): ?>

					<li class="nav-item">
						<a class="nav-link<?php if (($view == 'customer' && $layout == 'default') || $view == 'myreservation') echo ' active'; ?>"
						   href="<?php echo Route::_("index.php?option=com_solidres&view=customer&Itemid=$Itemid", false); ?>">
							<?php echo Text::_('SR_CUSTOMER_DASHBOARD_MY_RESERVATIONS') ?>
						</a>
					</li>

					<?php if (SRPlugin::isEnabled('feedback')): ?>
						<li class="nav-item">
							<a class="nav-link<?php if ($layout == 'feedbacks') echo ' active'; ?>"
							   href="<?php echo Route::_("index.php?option=com_solidres&view=customer&layout=feedbacks&Itemid=$Itemid", false); ?>">
								<?php echo Text::_('SR_CUSTOMER_DASHBOARD_MY_FEEDBACK'); ?>
							</a>
						</li>
					<?php endif; ?>

				<?php endif; ?>

				<li class="nav-item">
					<a class="nav-link<?php if ($view == 'myprofile') echo ' active'; ?>"
					   href="<?php echo Route::_("index.php?option=com_solidres&task=myprofile.edit&id=$customer_id&Itemid=$Itemid", false) . '&return=' . base64_encode($uri); ?>">
						<?php echo Text::_('SR_CUSTOMER_DASHBOARD_MY_PROFILE') ?>
					</a>
				</li>
				<?php if (SRPlugin::isEnabled('hub')): ?>
					<li class="nav-item dropdown">
						<a class="nav-link dropdown-toggle<?php echo $layout == 'wishlist' ? ' active' : '' ?>" href="#"
						   data-bs-toggle="dropdown">
							<?php echo Text::_('SR_CUSTOMER_DASHBOARD_MY_WISHLIST'); ?>
							<b class="caret"></b>
						</a>
						<ul class="dropdown-menu">

							<?php if ($showAll || $propertyOnly): ?>
								<li>
									<a class="dropdown-item<?php if ($layout == 'wishlist' && $scope == 'reservation_asset') echo ' active'; ?>"
									   href="<?php echo Route::_("index.php?option=com_solidres&view=customer&layout=wishlist&scope=reservation_asset&Itemid=$Itemid", false); ?>">
										<?php echo Text::_('SR_CUSTOMER_ASSET_WISHLIST'); ?>
									</a>
								</li>
							<?php endif; ?>

							<?php if ($showAll || $experienceOnly): ?>
								<li>
									<a class="dropdown-item<?php if ($layout == 'wishlist' && $scope == 'experience') echo ' active'; ?>"
									   href="<?php echo Route::_("index.php?option=com_solidres&view=customer&layout=wishlist&scope=experience&Itemid=$Itemid", false); ?>">
										<?php echo Text::_('SR_CUSTOMER_EXP_WISHLIST'); ?>
									</a>
								</li>
							<?php endif; ?>

						</ul>
					</li>
				<?php endif; ?>

				<?php if (($showAll || $experienceOnly) && SRPlugin::isEnabled('experience')): ?>

					<?php if ($expInvoice): ?>

						<li class="nav-item dropdown">
							<a class="nav-link dropdown-toggle <?php echo in_array($view, ['expreservations', 'myexpinvoices']) ? ' active' : '' ?>"
							   href="#" data-bs-toggle="dropdown">
								<?php echo Text::_('SR_CUSTOMER_DASHBOARD_MY_EXPERIENCES'); ?>
								<b class="caret"></b>
							</a>
							<ul class="dropdown-menu">
								<li>
									<a class="dropdown-item<?php if ($view == 'expreservations') echo ' active'; ?>"
									   href="<?php echo Route::_("index.php?option=com_solidres&view=myexperiences&Itemid=$Itemid", false); ?>">
										<?php echo Text::_('SR_RESERVATIONS'); ?>
									</a>
								</li>
								<li>
									<a class="dropdown-item<?php if ($view == 'myexpinvoices') echo ' active'; ?>"
									   href="<?php echo Route::_("index.php?option=com_solidres&view=myexpinvoices&Itemid=$Itemid"); ?>">
										<?php echo Text::_('SR_EXPERIENCE_INVOICES'); ?>
									</a>
								</li>
							</ul>
						</li>
					<?php else: ?>
						<li class="nav-item">
							<a class="nav-link<?php if ($view == 'myexperiences') echo ' active'; ?>"
							   href="<?php echo Route::_("index.php?option=com_solidres&view=myexperiences&Itemid=$Itemid", false); ?>">
								<?php echo Text::_('SR_CUSTOMER_DASHBOARD_MY_EXPERIENCES'); ?>
							</a>
						</li>
					<?php endif; ?>
				<?php endif; ?>

				<?php if (($showAll || $propertyOnly) && SRPlugin::isEnabled('invoice')): ?>
					<li class="nav-item">
						<a class="nav-link<?php if ($view == 'customerinvoices') echo ' active'; ?>"
						   href="<?php echo Route::_("index.php?option=com_solidres&view=customerinvoices&Itemid=$Itemid", false); ?>">
							<?php echo Text::_('SR_CUSTOMER_DASHBOARD_MY_INVOICES'); ?>
						</a>
					</li>
				<?php endif; ?>

				<?php if (ComponentHelper::isInstalled('com_rms')):
					JLoader::register('RmsHelperRoute', JPATH_SITE . '/components/com_rms/helpers/route.php');
					?>
					<li class="nav-item dropdown">
						<a href="#"
						   class="nav-link dropdown-toggle"
						   data-bs-toggle="dropdown"
						>
							<?php echo Text::_('SR_CUSTOMER_DASHBOARD_RMS') ?>
							<b class="caret"></b>
						</a>
						<ul class="dropdown-menu">
							<li>
								<a class="dropdown-item<?php if ($view == 'myreservations') echo ' active'; ?>"
								   href="<?php echo Route::_(RmsHelperRoute::getViewRoute('myreservations', 0, 'sr_user'), false); ?>">
									<?php echo Text::_('SR_CUSTOMER_DASHBOARD_RMS_RESERVATIONS'); ?>
								</a>
							</li>
							<li>
								<a class="dropdown-item<?php if ($view == 'myorders') echo ' active'; ?>"
								   href="<?php echo Route::_(RmsHelperRoute::getViewRoute('myorders', 0, 'sr_user'), false); ?>">
									<?php echo Text::_('SR_CUSTOMER_DASHBOARD_RMS_ORDERS'); ?>
								</a>
							</li>
						</ul>
					</li>
				<?php endif; ?>
			</ul>
		</div>
		<form class="navbar-search dashboard-logout form-inline"
		      action="<?php echo Route::_('index.php?option=com_users&task=user.logout', false); ?>"
		      method="post">
			<button type="submit" name="Submit" class="btn btn-default btn-secondary">
				<i class="fa fa-sign-out-alt"></i>
				<?php echo Text::_('JLOGOUT'); ?>
			</button>
			<input type="hidden" name="return" value="<?php echo $redirect; ?>"/>
			<?php echo HTMLHelper::_('form.token'); ?>
		</form>
	</div>
</nav>
