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

use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

?>
<div class="system-info-section">
	<h3>Database check list</h3>

	<p>
		<a class="btn btn-light"
		   href="<?php echo Route::_('index.php?option=com_solidres&task=system.databaseFix&' . Session::getFormToken() . '=1', false); ?>"><span
					class="icon-refresh"></span> Fix schema</a>
	</p>

	<table class="table table-condensed system-table">
		<thead>
		<tr>
			<th>
				Setting name
			</th>
			<th>
				Status
			</th>
		</tr>
		</thead>
		<tbody>
		<tr>
			<td>
				Current Solidres database schema version
			</td>
			<td>
				<?php
				$dbo   = Factory::getContainer()->get(DatabaseInterface::class);
				$query = $dbo->getQuery(true);
				$query->select('version_id')
					->from($dbo->quoteName('#__schemas'))
					->where($dbo->quoteName('extension_id') . ' = (SELECT extension_id FROM ' . $dbo->quoteName('#__extensions') . ' WHERE element = ' . $dbo->quote('com_solidres') . ')');

				$dbo->setQuery($query);

				$schemaVersion = $dbo->loadResult();
				if (!empty($schemaVersion)) :
					echo '<span class="badge bg-success">' . $schemaVersion . '</span> Your database is in good state.';
				else :
					echo '<span class="badge bg-warning">No version found</span> If you are using Solidres pre-installed in some template\'s quickstart package, your quickstart package database could have missing entries which leads to this issue. You should contact them so that they can fix it for you. More info can be found in our <a href="https://www.solidres.com/support/frequently-asked-questions">FAQ - #30</a>';
				endif;
				?>
			</td>
		</tr>
		</tbody>
	</table>
</div>
