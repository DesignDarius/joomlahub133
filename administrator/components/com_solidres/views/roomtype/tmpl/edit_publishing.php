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

echo $this->form->renderField('featured');
echo $this->form->renderField('created_by');
echo $this->form->renderField('created_by_alias');
echo $this->form->renderField('created_date');
echo $this->form->renderField('modified_date');
echo $this->form->renderField('version');
echo $this->form->renderField('ordering');
echo $this->form->renderField('id');