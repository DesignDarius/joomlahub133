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
 * /templates/TEMPLATENAME/html/layouts/com_solidres/asset/checkinoutform_date_blocks_2.php
 *
 * However, occasionally we will need to update template/layout related files and it is the template developers'
 * responsibility to update the overridden files (if any) to maintain full compatibility with Solidres.
 *
 * We do not provide support if any of the overridden files are out of date and are not compatible with Solidres.
 *
 * @version 2.8.0
 */

defined('_JEXEC') or die;

extract($displayData);
$datePairs   = array();
$datePairs[] = array($defaultMinCheckInDate->format('Y-m-d', true), $defaultMinCheckOutDate->format('Y-m-d', true));

for ($i = 0; $i < 10; $i++) :
	$datePairs[] = array($defaultMinCheckOutDate->format('Y-m-d', true), $defaultMinCheckOutDate->add(new DateInterval('P' . ($bookingType == 0 ? $tariff->d_min : $tariff->d_min - 1) . 'D'))->format('Y-m-d', true));
endfor;
$datePairsCount = count($datePairs);
$itemPerRow     = 4;
$spanNum        = 12 / (int) $itemPerRow;
$totalShow      = 7;

?>
<?php if ($datePairsCount > 0) : ?>
    <div class="row-fluid">
        <div class="span12">
            <div class="inner">
				<?php
				if (!empty($datePairs)) :
					$url = JRoute::_('index.php?option=com_solidres&view=reservationasset&checkin=&checkout=&id=' . (int) $assetId);
					for ($i = 0; $i <= $datePairsCount; $i++) :
						if ($i < $totalShow) :
							$dates = $datePairs[$i];

							if ($i % $itemPerRow == 0 && $i == 0) :
								echo '<div class="' . SR_UI_GRID_CONTAINER . ' solidres-roomtype-tariff-block">';
                            elseif ($i % $itemPerRow == 0 && $i != $datePairsCount) :
								echo '</div><div class="' . SR_UI_GRID_CONTAINER . ' solidres-roomtype-tariff-block">';
                            elseif ($i == $datePairsCount) :
								echo '</div>';
							endif;

							$newUrl = JUri::getInstance($url);
							$newUrl->setVar('checkin', $dates[0]);
							$newUrl->setVar('checkout', $dates[1]);
							if ($enableAutoScroll) :
								$newUrl->setFragment('srt_' . $roomTypeId);
							endif;
							$checkinDisplay         = JDate::getInstance($dates[0])->format('d M', true);
							$checkoutDisplay        = JDate::getInstance($dates[1])->format('d M', true);
							$checkinWeekDayDisplay  = JDate::getInstance($dates[0])->format('D', true);
							$checkoutWeekDayDisplay = JDate::getInstance($dates[1])->format('D', true);
							$lengthOfStay           = (int) SRUtilities::calculateDateDiff($dates[0], $dates[1]);

							echo '<div class="' . constant('SR_UI_GRID_COL_' . $spanNum) . ' solidres-roomtype-tariff-block-item">';
							echo '<a href="' . $newUrl->toString() . '">
                                <span>' . JText::_('SR_CHECKIN') . '</span>
                                <span>' . $checkinDisplay . '</span>
                                <span>' . JText::_('SR_CHECKOUT') . '</span>
                                <span>' . $checkoutDisplay . '</span>
                             </a>';
							echo '</div>';
						endif;
					endfor;
					?>

				<?php
				endif;
				?>
            </div>
        </div>
    </div>
<?php endif ?>
<style>
    .solidres-roomtype-tariff-block-item {
        background: white;
        border: 1px solid #CCC;
        margin-bottom: 10px;
        color: #2A626C;
        text-align: center;
        font-weight: bold;
    }

    .solidres-roomtype-tariff-block-item a:link,
    .solidres-roomtype-tariff-block-item a:hover {
        display: block;
        margin: 15px;
        color: #2A626C;
    }

    .solidres-roomtype-tariff-block-item span {
        display: block;
    }
</style>
