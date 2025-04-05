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
 * /templates/TEMPLATENAME/html/com_solidres/reservation/final.php
 *
 * However, occasionally we will need to update template/layout related files and it is the template developers'
 * responsibility to update the overridden files (if any) to maintain full compatibility with Solidres.
 *
 * We do not provide support if any of the overridden files are out of date and are not compatible with Solidres.
 *
 * @version 3.1.0
 */

use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;

// Get some data from successful reservation
$reservationCodeUserState   = $this->app->getUserState($this->context . '.code', '');
$isNew   = $this->app->getUserState($this->context . '.is_new', true);
if (!isset($this->reservation) && !empty($reservationCodeUserState)):
	$paymentMethodMessage = $this->app->getUserState($this->context . '.payment_method_message');
	$bookingRequireApproval = $this->app->getUserState($this->context . '.booking_require_approval');
	$finalContent           = '';
	if (!empty($paymentMethodMessage) || $bookingRequireApproval) :
		$finalContent = $paymentMethodMessage;
	else:
        $customerFullName = $this->app->getUserState($this->context . '.customer_firstname') . ' ' . $this->app->getUserState($this->context . '.customer_lastname');
	    $msg = $isNew ? 'SR_RESERVATION_COMPLETE' : 'SR_RESERVATION_AMEND_COMPLETE';
	    $link = $isNew ? JUri::root() : JRoute::_('index.php?option=com_solidres&view=customer');
        $finalContent = Text::sprintf($msg,
            $customerFullName,
            $this->app->getUserState($this->context . '.code'),
            $this->app->getUserState($this->context . '.customeremail'),
            $this->app->getUserState($this->context . '.reservation_asset_name'),
            $link
        );
	endif;
	?>

	<?php if (!empty($finalContent)) : ?>
    <div id="solidres">
        <div class="alert alert-success">
			<?php echo $finalContent ?>
        </div>
    </div>
<?php endif ?>

	<?php
	$this->app->setUserState($this->context . '.payment_method_message', null);
	$this->app->setUserState($this->context . '.payment_method_custom_email_content', null);
elseif (isset($this->reservation)) :
	?>
    <div id="solidres">

        <h3><?php echo Text::_('SR_ASSET_INFO') ?></h3>

        <table class="table table-striped">
            <thead></thead>
            <tbody>
            <tr>
                <td>
					<?php echo Text::_('SR_CONFIRMATION_ASSET_NAME') ?>
                </td>
                <td>
					<?php echo $this->asset->name ?>
                </td>
            </tr>
            <tr>
                <td>
					<?php echo Text::_('SR_CONFIRMATION_ASSET_ADDRESS') ?>
                </td>
                <td>
					<?php echo $this->asset->address_1 . ', ' .
						(!empty($this->asset->city) ? $this->asset->city . ', ' : '') .
						(!empty($this->asset->postcode) ? $this->asset->postcode . ', ' : '') .
						$this->asset->country_name ?>
                </td>
            </tr>
            <tr>
                <td>
					<?php echo Text::_('SR_CONFIRMATION_ASSET_EMAIL') ?>
                </td>
                <td>
					<?php echo $this->asset->email ?>
                </td>
            </tr>
            <tr>
                <td>
					<?php echo Text::_('SR_CONFIRMATION_ASSET_PHONE') ?>
                </td>
                <td>
					<?php echo $this->asset->phone ?>
                </td>
            </tr>
            </tbody>
        </table>

        <h3><?php echo Text::_('SR_BOOKING_INFO') ?></h3>

        <table class="table table-striped">
            <thead></thead>
            <tbody>
            <tr>
                <td>
					<?php echo Text::_('SR_CONFIRMATION_BOOKING_NUMBER') ?>
                </td>
                <td>
					<?php echo $this->reservation->code ?>
                </td>
            </tr>
            <tr>
                <td>
					<?php echo Text::_('SR_CONFIRMATION_EMAIL') ?>
                </td>
                <td>
					<?php echo $this->reservation->customer_email ?>
                </td>
            </tr>
            <tr>
                <td>
					<?php echo Text::_('SR_CONFIRMATION_BOOKING_DETAILS') ?>
                </td>
                <td>
					<?php
					if (!isset($this->reservation->booking_type)) :
						$this->reservation->booking_type = 0;
					endif;

					if ($this->reservation->booking_type == 0) :
						echo Text::plural('SR_NIGHTS', $this->lengthOfStay);
					else :
						echo Text::plural('SR_DAYS', $this->lengthOfStay + 1);
					endif;
					?>,

					<?php echo Text::plural('SR_CONFIRMATION_BOOKING_ROOM_NUM', count($this->reservation->reserved_room_details)) ?>

                </td>
            </tr>
            <tr>
                <td>
					<?php echo Text::_('SR_CONFIRMATION_CHECKIN') ?>
                </td>
                <td>
					<?php echo $this->reservation->checkin ?>
                </td>
            </tr>
            <tr>
                <td>
					<?php echo Text::_('SR_CONFIRMATION_CHECKOUT') ?>
                </td>
                <td>
					<?php echo $this->reservation->checkout ?>
                </td>
            </tr>
            <tr>
                <td>
					<?php echo Text::_('SR_CONFIRMATION_TOTAL_PRICE') ?>
                </td>
                <td>
					<?php
					JLoader::register('SRCurrency', SRPATH_LIBRARY . '/currency/currency.php');
					$baseCurrency = new SRCurrency($this->reservation->total_price_tax_incl - $this->reservation->total_discount, $this->reservation->currency_id);
					echo $baseCurrency->format()
					?>
                </td>
            </tr>
            </tbody>
        </table>

        <h3><?php echo Text::_('SR_BOOKING_CONFIRMATION_ROOM_DETAILS') ?> </h3>

        <table>
            <thead></thead>
            <tbody>
			<?php
			$reservedRoomDetails = $this->reservation->reserved_room_details;
			foreach ($reservedRoomDetails as $room) : ?>
                <dl>
                    <dt>
						<?php echo $room->room_type_name ?>
                        (
						<?php
						echo Text::plural('SR_BOOKING_CONFIRMATION_ADULTS', $room->adults_number) . ' ' . Text::_('SR_AND') . ' ' . Text::plural('SR_BOOKING_CONFIRMATION_CHILDREN', $room->children_number)
						?>
                        )
                    </dt>
                    <dd><?php echo Text::_("SR_BOOKING_CONFIRMATION_GUEST_FULLNAME") ?>
                        : <?php echo $room->guest_fullname ?></dd>
                    <dd>
						<?php
						if (is_array($room->other_info)) :
							foreach ($room->other_info as $info) :
								if (substr($info->key, 0, 7) == 'smoking') :
									echo Text::_('SR_BOOKING_CONFIRMATION_' . $info->key) . ': ' . ($info->value == '' ? Text::_('SR_NO_PREFERENCES') : ($info->value == 1 ? Text::_('JYES') : Text::_('JNO')));
								endif;
							endforeach;
						endif
						?>
                    </dd>
                    <dd>
						<?php
						$roomPriceCurrency = clone $baseCurrency;
						$roomPriceCurrency->setValue($room->room_price_tax_incl ?? $room->room_price);
						echo Text::_('SR_BOOKING_CONFIRMATION_ROOM_COST') . ': ' . $roomPriceCurrency->format();
						?>
                    </dd>
                </dl>
			<?php endforeach ?>
            </tbody>
        </table>
    </div>
<?php

endif;
