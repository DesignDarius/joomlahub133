<?php
/**
 * Akeeba Engine
 *
 * @package   akeebaengine
 * @copyright Copyright (c)2006-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License version 3, or later
 *
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public
 * License as published by the Free Software Foundation, version 3.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this program. If not, see
 * <https://www.gnu.org/licenses/>.
 */

namespace Akeeba\Engine\Util\Log;

defined('AKEEBAENGINE') || die();

/**
 * The interface for Akeeba Engine logger objects
 */
interface LogInterface
{
	/**
	 * Open a new log instance with the specified tag. If another log is already open it is closed before switching to
	 * the new log tag. If the tag is null use the default log defined in the logging system.
	 *
	 * @param   string|null  $tag        The log to open
	 * @param   string       $extension  The log file extension (default: .php, use empty string for .log files)
	 *
	 * @return void
	 */
	public function open($tag = null, $extension = '.php');

	/**
	 * Close the currently active log and set the current tag to null.
	 *
	 * @return  void
	 */
	public function close();

	/**
	 * Reset (remove entries) of the log with the specified tag.
	 *
	 * @param   string|null  $tag  The log to reset
	 *
	 * @return  void
	 */
	public function reset($tag = null);

	/**
	 * Add a message to the log
	 *
	 * @param   string  $level    One of the Akeeba\Engine\Psr\Log\LogLevel constants
	 * @param   string  $message  The message to log
	 * @param   array   $context  Currently not used. Left here for PSR-3 compatibility.
	 *
	 * @return  void
	 */
	public function log($level, $message, array $context = []);

	/**
	 * Temporarily pause log output. The log() method MUST respect this.
	 *
	 * @return  void
	 */
	public function pause();

	/**
	 * Resume the previously paused log output. The log() method MUST respect this.
	 *
	 * @return  void
	 */
	public function unpause();

	/**
	 * Returns the timestamp (in UNIX time long integer format) of the last log message written to the log with the
	 * specific tag. The timestamp MUST be read from the log itself, not from the logger object. It is used by the
	 * engine to find out the age of stalled backups which may have crashed.
	 *
	 * @param   string|null  $tag  The log tag for which the last timestamp is returned
	 *
	 * @return  int|null  The timestamp of the last log message, in UNIX time. NULL if we can't get the timestamp.
	 */
	public function getLastTimestamp($tag = null);
}
