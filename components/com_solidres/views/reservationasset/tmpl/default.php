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
 * /templates/TEMPLATENAME/html/com_solidres/reservationasset/default.php
 *
 * However, occasionally we will need to update template/layout related files and it is the template developers'
 * responsibility to update the overridden files (if any) to maintain full compatibility with Solidres.
 *
 * We do not provide support if any of the overridden files are out of date and are not compatible with Solidres.
 *
 * @version 3.2.2
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;

$menuId = '&Itemid=' . Factory::getApplication()->input->get('Itemid', '', 'uint');
SRHtml::_('venobox');
?>
<div id="solidres" class="<?php echo SR_UI ?> reservation_asset_default <?php echo SR_LAYOUT_STYLE ?>">
	<?php if (!empty($this->checkin) && !empty($this->checkout)) :
		echo SRLayoutHelper::render('asset.booking_summary', [
			'checkinFormatted'  => $this->checkinFormatted,
			'checkoutFormatted' => $this->checkoutFormatted,
			'property'          => $this->item,
		]);
	endif ?>
    <div class="reservation_asset_item clearfix">
		<?php if ($this->item->params['only_show_reservation_form'] == 0 && !$this->isAmending) : ?>
            <div class="<?php echo SR_UI_GRID_CONTAINER ?> mb-3">
                <div class="<?php echo SR_UI_GRID_COL_9 ?>">
                    <h1>
						<?php echo $this->escape($this->item->name) . ' '; ?>
						<?php
                        if ($this->item->rating > 0) :
                            echo '<span class="rating-wrapper">' . str_repeat('<i class="rating fa fa-star"></i> ', $this->item->rating) . '</span>';
                        endif;
                        ?>
                    </h1>
                </div>
                <div class="<?php echo SR_UI_GRID_COL_3 ?> align-self-center">
					<?php echo $this->events->afterDisplayAssetName; ?>
                </div>
            </div>
            <div class="mb-3">
					<span class="address_1 reservation_asset_subinfo">
					<?php
					echo $this->escape($this->item->address_1 . ', ' .
						(!empty($this->item->city) ? $this->item->city . ', ' : '') .
						(!empty($this->item->geostate_code_2) ? $this->item->geostate_code_2 . ' ' : '') .
						(!empty($this->item->postcode) ? $this->item->postcode . ', ' : '') .
						$this->item->country_name)
					?>
                        <a class="show_map" data-venobox="iframe" data-ratio="full"
                           href="<?php echo Route::_('index.php?option=com_solidres&view=map&tmpl=component&id=' . $this->item->id . $menuId) ?>">
							<?php echo Text::_('SR_SHOW_MAP') ?>
						</a>
					</span>

	            <?php if (!empty($this->item->address_2)) : ?>
		            <span class="address_2 reservation_asset_subinfo">
						<?php echo $this->escape($this->item->address_2); ?>
					</span>
	            <?php endif ?>

	            <?php if (!empty($this->item->phone)) : ?>
		            <span class="phone reservation_asset_subinfo">
						<?php echo Text::_('SR_PHONE') . ': <a href="tel:' . $this->escape($this->item->phone) . '">' . $this->escape($this->item->phone) . '</a>'; ?>
					</span>
	            <?php endif ?>

	            <?php if (!empty($this->item->fax)) : ?>
		            <span class="fax reservation_asset_subinfo">
						<?php echo Text::_('SR_FAX') . ': ' . $this->escape($this->item->fax); ?>
					</span>
	            <?php endif ?>

	            <span class="social_network reservation_asset_subinfo clearfix">
						<?php
						$socialNetworks = ['facebook', 'twitter', 'instagram', 'linkedin', 'pinterest', 'slideshare', 'vimeo', 'youtube'];
						$socialIconsSuffixes = ['instagram' => ''];
						$iconPrefix = 'fab';

						foreach ($socialNetworks as $socialNetwork) :
							if (!empty($this->item->reservationasset_extra_fields[$socialNetwork . '_link'])
								&& $this->item->reservationasset_extra_fields[$socialNetwork . '_show'] == 1) :
								echo '<a href="' . $this->item->reservationasset_extra_fields[$socialNetwork . '_link'] .'"
                                   target="_blank"><i class="' . $iconPrefix . ' fa-' . $socialNetwork . ($socialIconsSuffixes[$socialNetwork] ?? '-square') . '"></i></a> ';
							endif;
						endforeach;
						?>

					</span>
            </div>

            <div class="mb-3">
	            <?php echo $this->defaultGallery; ?>
            </div>

            <div class="mb-3">
	            <?php
	            echo HTMLHelper::_(SR_UITAB . '.startTabSet', 'asset-info', ['active' => 'asset-desc', 'recall' => true]);

	            if (!empty($this->item->description) || !empty($this->item->facilities)) :
		            echo HTMLHelper::_(SR_UITAB . '.addTab', 'asset-info', 'asset-desc', Text::_('SR_DESCRIPTION', true));

		            echo SRLayoutHelper::render('asset.description', [
			            'property' => $this->item,
		            ]);

		            echo HTMLHelper::_(SR_UITAB . '.endTab');
	            endif;

	            if (isset($this->item->feedbacks->render) && !empty($this->item->feedbacks->render)) :
		            echo HTMLHelper::_(SR_UITAB . '.addTab', 'asset-info', 'asset-feedback', Text::_('SR_REVIEWS', true));
		            echo $this->item->feedbacks->render;
		            echo HTMLHelper::_(SR_UITAB . '.endTab');

		            echo HTMLHelper::_(SR_UITAB . '.addTab', 'asset-info', 'asset-feedback-scores', Text::_('SR_FEEDBACK_SCORES', true));
		            echo $this->item->feedbacks->scores;
		            echo HTMLHelper::_(SR_UITAB . '.endTab');
	            endif;

	            if (!empty($this->item->tab_content)
		            && ($tabContents = json_decode($this->item->tab_content, true))
	            )
	            {
		            foreach ($tabContents as $tabId => $tabContent)
		            {
			            $tabTitle = $this->escape($tabContent['title']);

			            echo HTMLHelper::_(SR_UITAB . '.addTab', 'asset-info', $tabId, $tabTitle);
			            echo HTMLHelper::_('content.prepare', $tabContent['content']);
			            echo HTMLHelper::_(SR_UITAB . '.endTab');
		            }
	            }

	            echo HTMLHelper::_(SR_UITAB . '.endTabSet');
	            ?>
            </div>

		<?php endif ?>

		<?php echo $this->events->beforeDisplayAssetForm; ?>
		<?php if (SRPlugin::isEnabled('user') && $this->showLoginBox && !$this->isAmending) : ?>
            <div class="mb-3">
	            <div class="alert alert-info sr-login-form">
		            <?php
		            if (!$this->getCurrentUser()->get('id')) :
			            echo $this->loadTemplate('login');
		            else:
			            echo $this->loadTemplate('userinfo');
		            endif;
		            ?>
	            </div>
            </div>
		<?php endif; ?>

        <div class="mb-3">
	        <?php echo $this->loadTemplate('roomtype'); ?>
        </div>

        <?php if (!$this->isAmending) : ?>
        <div class="mb-3">
	        <?php echo $this->loadTemplate('information'); ?>
        </div>
        <?php endif ?>

		<?php echo $this->events->afterDisplayAssetForm; ?>
		<?php if ($this->showPoweredByLink) : ?>
			<div class="powered">
				<p>
					Powered by <a target="_blank" title="Solidres - A hotel booking extension for Joomla"
					              href="https://www.solidres.com">Solidres</a>
				</p>
			</div>
		<?php endif ?>
    </div>
</div>
