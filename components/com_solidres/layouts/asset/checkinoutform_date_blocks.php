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
 * /templates/TEMPLATENAME/html/layouts/com_solidres/asset/checkinoutform_date_blocks.php
 *
 * However, occasionally we will need to update template/layout related files and it is the template developers'
 * responsibility to update the overridden files (if any) to maintain full compatibility with Solidres.
 *
 * We do not provide support if any of the overridden files are out of date and are not compatible with Solidres.
 *
 * @version 2.8.0
 */

defined('_JEXEC') or die;

$uri = JUri::root() . 'media/plg_solidres_flexsearch';
JHtml::_('stylesheet', $uri . '/css/slick.css', false, false);
JHtml::_('stylesheet', $uri . '/css/slick-theme.css', false, false);
JHtml::_('stylesheet', $uri . '/css/flexsearch.css', false, false);
JHtml::_('script', $uri . '/js/slick.min.js', false, false);
JHtml::_('script', $uri . '/js/flexsearch.js', false, false);
extract($displayData);
$datePairs   = array();
$datePairs[] = array($defaultMinCheckInDate->format('Y-m-d', true), $defaultMinCheckOutDate->format('Y-m-d', true));

for ($i = 0; $i < 10; $i++) :
	$datePairs[] = array($defaultMinCheckOutDate->format('Y-m-d', true), $defaultMinCheckOutDate->add(new DateInterval('P' . ($bookingType == 0 ? $tariff->d_min : $tariff->d_min - 1) . 'D'))->format('Y-m-d', true));
endfor;
$datePairsCount = count($datePairs);

?>
<?php if ($datePairsCount > 0) : ?>
    <div class="row-fluid">
        <div class="span12">
            <div class="inner">
				<?php
				if (!empty($datePairs)) :

					$url = JRoute::_('index.php?option=com_solidres&view=reservationasset&checkin=&checkout=&id=' . (int) $assetId);

					echo '<div id="fs-date-blocks-' . $roomTypeId . '" class="fs-date-blocks ' . ($datePairsCount > 3 ? 'narrow' : 'full') . '">';
					foreach ($datePairs as $dates) :
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
						echo '<a class="fs-date-block" href="' . $newUrl->toString() . '">
							<span>' .
							$checkinDisplay . ' - ' . $checkoutDisplay .
							'</span>
					        <span>' .
							($bookingType == 0 ? JText::plural('SR_NIGHTS', $lengthOfStay) : JText::plural('SR_DAYS', ($lengthOfStay + 1))) . ', ' . $checkinWeekDayDisplay . ' - ' . $checkoutWeekDayDisplay .
							'</span>
					     </a>';
					endforeach;
					echo '</div>';
					?>
                    <script>
                        Solidres.jQuery(document).ready(function ($) {
                            $('#fs-date-blocks-' + <?php echo $roomTypeId ?>).slick({
                                infinite: true,
                                slidesToShow: <?php echo $datePairsCount >= 3 ? 3 : $datePairsCount ?>,
                                slidesToScroll: 3,
                                dots: false,
                                arrows: <?php echo $datePairsCount > 3 ? 'true' : 'false' ?>,
                                responsive: [
                                    {
                                        breakpoint: 1024,
                                        settings: {
                                            slidesToShow: 3,
                                            slidesToScroll: 3,
                                            infinite: true,
                                        }
                                    },
                                    {
                                        breakpoint: 600,
                                        settings: {
                                            slidesToShow: 2,
                                            slidesToScroll: 2
                                        }
                                    },
                                    {
                                        breakpoint: 480,
                                        settings: {
                                            slidesToShow: 1,
                                            slidesToScroll: 1
                                        }
                                    }
                                ]
                            });
                        });
                    </script>
				<?php
				endif;
				?>
            </div>
        </div>
    </div>
<?php endif ?>