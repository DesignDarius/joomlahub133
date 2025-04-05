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

use Joomla\CMS\Language\Text;

$partnerName       = $this->form->getValue('partner_name');
$address           = ['address_1', 'address_2', 'city', 'postcode'];
$geocodingAddress  = [];
foreach ($address as $add) :
	if ($this->form->getValue($add, '') != '') :
		$geocodingAddress[] = $this->form->getValue($add);
	endif;
endforeach;

echo $this->form->renderField('name');
echo $this->form->renderField('alternative_name');
echo $this->form->renderField('alias'); 
echo $this->form->renderField('category_id'); 
echo $this->form->renderField('category_name'); 
echo $this->form->renderField('tags'); 
echo $this->form->renderField('partner_id');
echo $this->form->renderField('address_1');
echo $this->form->renderField('address_2');
echo $this->form->renderField('city');
echo $this->form->renderField('postcode');
echo $this->form->renderField('email');
echo $this->form->renderField('website');
echo $this->form->renderField('phone');
echo $this->form->renderField('fax');
echo $this->form->renderField('country_id');
echo $this->form->renderField('geo_state_id');
echo $this->form->renderField('currency_id');
echo $this->form->renderField('price_includes_tax');
echo $this->form->renderField('tax_id');
echo $this->form->renderField('booking_type');

$this->form->setFieldAttribute('map', 'geocodingAddress', implode(', ', $geocodingAddress));
$this->form->setFieldAttribute('map', 'lat', $this->form->getValue('lat'));
$this->form->setFieldAttribute('map', 'lng', $this->form->getValue('lng'));
echo $this->form->renderField('map');

?>

<div class="coordinates">
    <?php echo $this->form->renderField('lat'); ?>
    <?php echo $this->form->renderField('lng'); ?>
</div>

<?php echo $this->form->renderField('description'); ?>
