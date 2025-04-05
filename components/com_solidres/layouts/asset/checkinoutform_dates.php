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
 * /templates/TEMPLATENAME/html/layouts/com_solidres/asset/checkinoutform_dates.php
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
use Joomla\CMS\Layout\LayoutHelper;

extract($displayData);

?>
<p class="text-muted"><?php echo Text::_('SR_OCCUPANCY_STATUS_HEADING') ?></p>
<?php
echo LayoutHelper::render('joomla.form.field.checkboxes', [
		'name'           => 'occupied_dates[]',
		'id'             => 'occupied_dates',
		'required'       => true,
		'label'          => 'Check a date to mark it as occupied, uncheck to mark it as unoccupied',
		'options'        => $options,
		'checkedOptions' => $options,
		'hasValue'       => true,
		'autofocus'      => false,
		'dataAttribute'  => '',
		'disabled'       => false,
		'class'          => '',
	]
);