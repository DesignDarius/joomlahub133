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

echo $this->form->renderField('name');
echo $this->form->renderField('alias');
echo $this->form->renderField('reservation_asset_id');
echo $this->form->renderField('is_private');
echo $this->form->renderField('is_master');
echo $this->form->renderField('occupancy_max');
echo $this->form->renderField('occupancy_adult');
echo $this->form->renderField('occupancy_child');
echo $this->form->renderField('occupancy_child_age_range');
echo $this->form->renderField('coupon_id');
echo $this->form->renderField('extra_id');
echo $this->form->renderField('state');
echo $this->form->renderField('description');