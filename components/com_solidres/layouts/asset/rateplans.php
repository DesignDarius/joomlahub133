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
 * /templates/TEMPLATENAME/html/com_solidres/asset/rateplans.php
 *
 * However, occasionally we will need to update template/layout related files and it is the template developers'
 * responsibility to update the overridden files (if any) to maintain full compatibility with Solidres.
 *
 * We do not provide support if any of the overridden files are out of date and are not compatible with Solidres.
 *
 * @version 3.1.0
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

extract($displayData);

if (!SRPlugin::isEnabled('flexsearch') || (SRPlugin::isEnabled('flexsearch') && empty($roomType->otherAvailableDates))) : ?>
<div id="tariff-holder-<?php echo $roomType->id ?>"
     style="<?php echo (!$disableOnlineBooking || $showTariffs) && $defaultTariffVisibility == 1 ? '' : 'display: none' ?>">
	<?php

	if (!$isFresh) :
		if (!empty($roomType->availableTariffs)) :

		$ratePlanBookDisplayData = [
			'Itemid'               => $Itemid,
			'bookingType'          => $item->booking_type,
			'disableOnlineBooking' => $disableOnlineBooking,
			'isExclusive'          => $isExclusive,
			'item'                 => $item,
			'roomType'             => $roomType,
			'selectedRoomTypes'    => $selectedRoomTypes,
			'showRemainingRooms'   => $showRemainingRooms,
			'skipRoomForm'         => $skipRoomForm,
			'stayLength'           => $stayLength,
		];

        $countRatePerRoomType = 0;
        $countRatePerRoom     = 0;
        if ($roomType->number_of_room == $roomType->totalAvailableRoom) :
            foreach ($roomType->availableTariffs as $tariffKey => $tariffInfo) :

                if ($tariffInfo['tariffType'] != PER_ROOM_TYPE_PER_STAY) :
                    $countRatePerRoom++;
                    continue;
                endif;

                $minPrice = SRUtilities::appendPriceSuffix(
                    $tariffInfo['val'],
                    $tariffInfo['tariffType'],
                    $item->booking_type,
                    ($item->booking_type == 0 ? $stayLength : $stayLength + 1),
                    $tariffInfo['val_original'],
                    $roomType->is_private,
                    $tariffInfo['adults'],
                    $tariffInfo['children']
                );

	            $ratePlanBookDisplayData = array_merge($ratePlanBookDisplayData, [
		            'identification' => $roomType->id . '-' . $tariffKey,
		            'minPrice'       => $minPrice,
		            'tariffInfo'     => $tariffInfo,
		            'tariffKey'      => $tariffKey,
	            ]);

                $layout = SRLayoutHelper::getInstance();
                echo $layout->render('asset.tariff_book' . (!empty(SR_LAYOUT_STYLE) ? '_' . SR_LAYOUT_STYLE : ''), $ratePlanBookDisplayData);
                $countRatePerRoomType++;
            endforeach;
        endif;

        if ($countRatePerRoomType > 0 && $countRatePerRoom > 0) :
            echo '<div class="tariff-sep"></div>';
        endif;

        foreach ($roomType->availableTariffs as $tariffKey => $tariffInfo) :

            if ($tariffInfo['tariffType'] == PER_ROOM_TYPE_PER_STAY) continue;

            $minPrice = SRUtilities::appendPriceSuffix(
                $tariffInfo['val'],
                $tariffInfo['tariffType'],
                $item->booking_type,
                ($item->booking_type == 0 ? $stayLength : $stayLength + 1),
                $tariffInfo['val_original'],
                $roomType->is_private,
                $tariffInfo['adults'],
                $tariffInfo['children']
            );

	        $ratePlanBookDisplayData = array_merge($ratePlanBookDisplayData, [
		        'identification' => $roomType->id . '-' . $tariffKey,
		        'minPrice'       => $minPrice,
		        'tariffInfo'     => $tariffInfo,
		        'tariffKey'      => $tariffKey,
	        ]);

            $layout = SRLayoutHelper::getInstance();
            echo $layout->render('asset.tariff_book' . (!empty(SR_LAYOUT_STYLE) ? '_' . SR_LAYOUT_STYLE : ''), $ratePlanBookDisplayData);
        endforeach;
        else :
            if (SRPlugin::isEnabled('flexsearch') && !empty($roomType->otherAvailableDates)) :

            else :
                $link = Route::_('index.php?option=com_solidres&view=reservationasset&id=' . $item->id . '&Itemid=' . $Itemid . ($enableAutoScroll ? '#book-form' : ''));
                echo '<div class="alert alert-warning">' . Text::sprintf('SR_NO_TARIFF_MATCH_CHECKIN_CHECKOUT', $checkinFormatted, $checkoutFormatted, $link) . '</div>';
            endif;
		endif;
    endif;

	if ($isFresh && $showTariffs == 1 && isset($roomType->tariffs) && is_array($roomType->tariffs)) :

		$ratePlanListDisplayData = [
			'Itemid'               => $Itemid,
			'bookingType'          => $item->booking_type,
			'disableOnlineBooking' => $disableOnlineBooking,
			'item'                 => $item,
			'roomType'             => $roomType,
		];

		$countRatePerRoomType = 0;
		foreach ($roomType->tariffs as $tariff) :

			if ($tariff->type != PER_ROOM_TYPE_PER_STAY) continue;

			$minPrice = SRUtilities::getMinPrice($item, $roomType, $tariff, $showTaxIncl);

			$ratePlanListDisplayData = array_merge($ratePlanListDisplayData, [
				'identification'       => $roomType->id . '-' . $tariff->id,
				'minPrice'             => $minPrice,
				'tariff'               => $tariff,
            ]);

			$layout   = SRLayoutHelper::getInstance();
			echo $layout->render('asset.tariff_list' . (!empty(SR_LAYOUT_STYLE) ? '_' . SR_LAYOUT_STYLE : ''), $ratePlanListDisplayData);
			$countRatePerRoomType++;

		endforeach; // end foreach of complex tariffs

		if ($countRatePerRoomType > 0) :
			echo '<div class="tariff-sep"></div>';
		endif;

		foreach ($roomType->tariffs as $tariff) :

			if ($tariff->type == PER_ROOM_TYPE_PER_STAY) continue;

			$minPrice = SRUtilities::getMinPrice($item, $roomType, $tariff, $showTaxIncl);

			$ratePlanListDisplayData = array_merge($ratePlanListDisplayData, [
				'identification'       => $roomType->id . '-' . $tariff->id,
				'minPrice'             => $minPrice,
				'tariff'               => $tariff,
			]);

			$layout   = SRLayoutHelper::getInstance();
			echo $layout->render('asset.tariff_list' . (!empty(SR_LAYOUT_STYLE) ? '_' . SR_LAYOUT_STYLE : ''), $ratePlanListDisplayData);

		endforeach;
	endif ?>
</div>
<?php endif;