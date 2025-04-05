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
 * /templates/TEMPLATENAME/html/com_solidres/customer/default.php
 *
 * However, occasionally we will need to update template/layout related files and it is the template developers'
 * responsibility to update the overridden files (if any) to maintain full compatibility with Solidres.
 *
 * We do not provide support if any of the overridden files are out of date and are not compatible with Solidres.
 *
 * @version 3.1.0
 */

use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Date\Date;

defined('_JEXEC') or die;

$uri                        = Uri::getInstance();
$config                     = Factory::getConfig();
$tzoffset                   = $config->get('offset');
$timezone                   = new DateTimeZone($tzoffset);
$displayData['customer_id'] = $this->modelReservations->getState('filter.customer_id');
$layout                     = SRLayoutHelper::getInstance();
?>

<?php echo SRLayoutHelper::render('customer.navbar', $displayData); ?>

<form action="<?php echo Uri::getInstance()->toString() ?>" method="post" name="adminForm" id="adminForm">
	<div class="<?php echo SR_UI_GRID_CONTAINER ?> mb-3">
		<div class="<?php echo SR_UI_GRID_COL_4 ?>">
			<select class="form-select" name="filter_state" onchange="document.getElementById('adminForm').submit()">
				<option value=""><?php echo Text::_('SR_CUSTOMER_DASHBOARD_FILTER_ALL_STATUSES') ?></option>
				<option <?php echo $this->modelReservations->getState('filter.state') == 4 ? 'selected' : ''; ?>
						value="4"><?php echo Text::_('SR_CUSTOMER_DASHBOARD_FILTER_CANCELLED_STATUSES') ?></option>
			</select>
		</div>
		<div class="<?php echo SR_UI_GRID_COL_4 ?>">
			<select class="form-select" name="filter_location" onchange="document.getElementById('adminForm').submit()">
				<option value=""><?php echo Text::_('SR_CUSTOMER_DASHBOARD_FILTER_ALL_CITIES') ?></option>
				<?php
				foreach ($this->filterLocations as $location) :
					$selected = '';
					if (strtolower($this->modelReservations->getState('filter.location')) == strtolower($location['city'])) :
						$selected = 'selected';
					endif;
					?>
					<option <?php echo $selected ?>
							value="<?php echo $location['city'] ?>"><?php echo $location['city'] ?></option>
				<?php
				endforeach;
				?>
			</select>
		</div>
		<div class="<?php echo SR_UI_GRID_COL_4 ?>">
			<select class="form-select" name="filter_reservation_asset_id"
			        onchange="document.getElementById('adminForm').submit()">
				<option value=""><?php echo Text::_('SR_CUSTOMER_DASHBOARD_FILTER_ALL_ASSETS') ?></option>
				<?php
				foreach ($this->filterAssets as $asset) :
					$selected = '';
					if (strtolower($this->modelReservations->getState('filter.reservation_asset_id')) == strtolower($asset['id'])) :
						$selected = 'selected';
					endif;
					?>
					<option <?php echo $selected ?>
							value="<?php echo $asset['id'] ?>"><?php echo $asset['name'] ?></option>
				<?php
				endforeach;
				?>
			</select>
		</div>
	</div>
	<input type="hidden" name="filter_clear" value="0"/>
</form>

<?php if ($this->unapprovedReservations > 0) : ?>
	<div class="alert">
		<?php echo Text::plural('SR_UNDER_REVIEW_RESERVATION_WARNING', $this->unapprovedReservations) ?>
	</div>
<?php endif ?>

<div class="container-fluid">
	<?php
	if (!empty($this->reservations)) :
		$properties = [];
		$media = [];
		foreach ($this->reservations as $reservation) :

			if (!$reservation->is_approved) continue;

			// Caching is needed
			if (!isset($properties[$reservation->reservation_asset_id])) :
				$properties[$reservation->reservation_asset_id] = $this->modelAsset->getItem($reservation->reservation_asset_id);
				$properties[$reservation->reservation_asset_id]->deepLink = SolidresHelperRoute::getReservationAssetRoute($properties[$reservation->reservation_asset_id]->slug);
			endif;
			?>

			<div class="<?php echo SR_UI_GRID_CONTAINER ?> reservation-row mb-3">
				<div class="<?php echo SR_UI_GRID_COL_3 ?> p-3">
					<div class="sr-align-center">
						<?php
						$property = $properties[$reservation->reservation_asset_id];
						if (!empty($property->media)) :
							echo $layout->render('solidres.carousel', [
								'id'         => "carousel-{$property->id}",
								'items'      => $property->media,
								'objectId'   => $property->id,
								'objectName' => $property->name,
								'linkItem'   => true,
								'linkUrl'    => $property->deepLink,
								'linkClass'  => '',
								'class'      => '',
								'size'       => 'asset_medium',
							]);
						endif;
						?>
					</div>
				</div>
				<div class="<?php echo SR_UI_GRID_COL_5 ?> p-3">
					<div>
						<h3>
							<a href="<?php echo SolidresHelperRoute::getReservationAssetRoute($properties[$reservation->reservation_asset_id]->slug) ?>">
								<?php echo $reservation->reservation_asset_name ?>
							</a>

							<?php
							if ($properties[$reservation->reservation_asset_id]->rating > 0) :
								echo '<span class="rating-wrapper">' . str_repeat('<i class="rating fa fa-star"></i> ', $properties[$reservation->reservation_asset_id]->rating) . '</span>';
							endif;
							?>
						</h3>

						<?php
						echo $properties[$reservation->reservation_asset_id]->address_1 . ', ' .
							(!empty($properties[$reservation->reservation_asset_id]->postcode) ? $properties[$reservation->reservation_asset_id]->postcode . ', ' : '') .
							(!empty($properties[$reservation->reservation_asset_id]->city) ? $properties[$reservation->reservation_asset_id]->city : '')//. $asset->country_name
						?>
						<a class="show_map" data-venobox="iframe"
						   href="<?php echo Route::_('index.php?option=com_solidres&view=map&tmpl=component&id=' . $reservation->reservation_asset_id) ?>">
							<?php echo Text::_('SR_SHOW_MAP') ?>
						</a>

						<?php if ($reservation->state == $this->cancellationState) : ?>
							<p class="reservation-cancelled">
								<?php echo Text::_('SR_USER_DASHBOARD_CANCELLED_BOOKING') ?>
							</p>
						<?php endif ?>

						<p>
							<a class="btn btn-secondary btn-sm"
							   href="<?php echo Route::_('index.php?option=com_solidres&task=myreservation.edit&Itemid=' . $this->itemid . '&id=' . $reservation->id . '&return=' . base64_encode($uri)) ?>">
								<?php echo Text::sprintf('SR_MANAGE_BOOKING', $reservation->code) ?>
							</a>
						</p>
					</div>
				</div>
				<div class="<?php echo SR_UI_GRID_COL_4 ?> p-3 checkinout">
					<div class="<?php echo SR_UI_GRID_CONTAINER ?>">
						<div class="<?php echo SR_UI_GRID_COL_6 ?>">
					<span class="dayt">
					<?php echo Date::getInstance($reservation->checkin, $timezone)->format('l', true) ?>
				</span>
							<span class="dayn">
					<?php echo Date::getInstance($reservation->checkin, $timezone)->format('j', true) ?>
				</span>
							<span class="montht">
					<?php echo Date::getInstance($reservation->checkin, $timezone)->format('F', true) ?>
				</span>
							<span class="yearn">
					<?php echo Date::getInstance($reservation->checkin, $timezone)->format('Y', true) ?>
				</span>
						</div>
						<div class="<?php echo SR_UI_GRID_COL_6 ?>">
					<span class="dayt">
					<?php echo Date::getInstance($reservation->checkout, $timezone)->format('l', true) ?>
				</span>
							<span class="dayn">
					<?php echo Date::getInstance($reservation->checkout, $timezone)->format('j', true) ?>
				</span>
							<span class="montht">
					<?php echo Date::getInstance($reservation->checkout, $timezone)->format('F', true) ?>
				</span>
							<span class="yearn">
					<?php echo Date::getInstance($reservation->checkout, $timezone)->format('Y', true) ?>
				</span>
						</div>
					</div>
				</div>
			</div>
		<?php
		endforeach;
	endif;
	?>
</div>


<div class="<?php echo SR_UI_GRID_CONTAINER ?>">
	<?php echo $this->pagination->getListFooter(); ?>
</div>


