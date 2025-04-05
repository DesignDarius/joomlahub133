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

/**
 * CodeIgniter Calendar Class
 *
 * This class enables the creation of calendars
 *
 * @package        CodeIgniter
 * @subpackage     Libraries
 * @category       Libraries
 * @author         ExpressionEngine Dev Team
 * @link           http://codeigniter.com/user_guide/libraries/calendar.html
 */
class SRCalendar
{
	var $local_time;
	var $template = '';
	var $start_day = 'sunday';
	var $month_type = 'long';
	var $day_type = 'abr';
	var $show_next_prev = false;
	var $next_prev_url = '';
	var $style = 'modern';
	var $room_type_id = 0;

	public function __construct($config = array())
	{
		$this->local_time = time();

		if (count($config) > 0)
		{
			$this->initialize($config);
		}

		$solidresRoomType = SRFactory::get('solidres.roomtype.roomtype');
		$dbo              = JFactory::getDbo();
		$query            = $dbo->getQuery(true);
		$query->select('COUNT(*)')
			->from('#__sr_rooms')
			->where('room_type_id = ' . (int) $this->room_type_id);
		$this->totalAvailableRooms = $dbo->setQuery($query)->loadResult();

		$query->clear();
		$query->select('id')
			->from('#__sr_room_types')
			->where('is_master = 1 AND state = 1 AND reservation_asset_id = (SELECT reservation_asset_id FROM #__sr_room_types WHERE id = ' . (int) $this->room_type_id . ')');
		$this->masterId = $dbo->setQuery($query)->loadResult();

		if ($this->masterId > 0)
		{
			$query->clear();
			$query->select('id')
				->from('#__sr_room_types')
				->where('is_master = 0 AND state = 1 AND reservation_asset_id = (SELECT reservation_asset_id FROM #__sr_room_types WHERE id = ' . (int) $this->room_type_id . ')');
			$this->slaveIds = $dbo->setQuery($query)->loadColumn();
		}

		$this->bookingType = $solidresRoomType->getBookingType($this->room_type_id);
	}

	function initialize($config = array())
	{
		foreach ($config as $key => $val)
		{
			if (isset($this->$key))
			{
				$this->$key = $val;
			}
		}
	}

	/**
	 * Generate the calendar
	 *
	 * @access    public
	 *
	 * @param string $year
	 * @param string $month
	 * @param integer  the month
	 * @param array  $data
	 *
	 * @return    string
	 * @internal  param \the $array data to be shown in the calendar cells
	 */
	function generate($year = '', $month = '', $roomTypeID = 0, $data = array())
	{
		if (!$roomTypeID || $roomTypeID <= 0)
		{
			$roomTypeID = $this->room_type_id;
		}

		$solidresRoomType    = SRFactory::get('solidres.roomtype.roomtype');
		$solidresReservation = SRFactory::get('solidres.reservation.reservation');
		$solidresParams      = JComponentHelper::getParams('com_solidres');
		$confirmationState   = $solidresParams->get('confirm_state', 5);
		$dbo                 = JFactory::getDbo();

		// Set and validate the supplied month/year
		if ($year == '')
			$year = date("Y", $this->local_time);

		if ($month == '')
			$month = date("m", $this->local_time);

		if (strlen($year) == 1)
			$year = '200' . $year;

		if (strlen($year) == 2)
			$year = '20' . $year;

		if (strlen($month) == 1)
			$month = '0' . $month;

		$adjusted_date = $this->adjust_date($month, $year);

		$month = $adjusted_date['month'];
		$year  = $adjusted_date['year'];

		// Determine the total days in the month
		$total_days = $this->get_total_days($month, $year);

		// Set the starting day of the week
		$start_days = array('sunday' => 0, 'monday' => 1, 'tuesday' => 2, 'wednesday' => 3, 'thursday' => 4, 'friday' => 5, 'saturday' => 6);
		$start_day  = (!isset($start_days[$this->start_day])) ? 0 : $start_days[$this->start_day];

		// Set the starting day number
		$local_date = mktime(12, 0, 0, $month, 1, $year);
		$date       = getdate($local_date);
		$day        = $start_day + 1 - $date["wday"];

		while ($day > 1)
		{
			$day -= 7;
		}

		// Set the current month/year/day
		// We use this to determine the "today" date
		$cur_year  = date("Y", $this->local_time);
		$cur_month = date("m", $this->local_time);
		$cur_day   = date("j", $this->local_time);

		$is_current_month = ($cur_year == $year AND $cur_month == $month) ? true : false;

		// Generate the template data array
		$this->parse_template();

		// Begin building the calendar output
		$out = str_replace('{booking_type}', $this->bookingType, $this->temp['table_open']);
		$out .= "\n";

		$out .= "\n";
		$out .= $this->temp['heading_row_start'];
		$out .= "\n";

		// "previous" month link
		if ($this->show_next_prev == true)
		{
			// Add a trailing slash to the  URL if needed
			$this->next_prev_url = preg_replace("/(.+?)\/*$/", "\\1/", $this->next_prev_url);

			$adjusted_date = $this->adjust_date($month - 1, $year);
			$out           .= str_replace('{previous_url}', $this->next_prev_url . $adjusted_date['year'] . '/' . $adjusted_date['month'], $this->temp['heading_previous_cell']);
			$out           .= "\n";
		}

		// Heading containing the month/year
		$colspan = ($this->show_next_prev == true) ? 5 : 7;

		$this->temp['heading_title_cell'] = str_replace('{colspan}', $colspan, $this->temp['heading_title_cell']);
		$this->temp['heading_title_cell'] = str_replace('{heading}', $this->get_month_name($month) . "&nbsp;" . $year, $this->temp['heading_title_cell']);

		$out .= $this->temp['heading_title_cell'];
		$out .= "\n";

		// "next" month link
		if ($this->show_next_prev == true)
		{
			$adjusted_date = $this->adjust_date($month + 1, $year);
			$out           .= str_replace('{next_url}', $this->next_prev_url . $adjusted_date['year'] . '/' . $adjusted_date['month'], $this->temp['heading_next_cell']);
		}

		$out .= "\n";
		$out .= $this->temp['heading_row_end'];
		$out .= "\n";

		// Write the cells containing the days of the week
		$out .= "\n";
		$out .= $this->temp['week_row_start'];
		$out .= "\n";

		$day_names = $this->get_day_names();

		for ($i = 0; $i < 7; $i++)
		{
			if (isset($day_names[($start_day + $i) % 7]))
			{
				$out .= str_replace('{week_day}', $day_names[($start_day + $i) % 7], $this->temp['week_day_cell']);
			}
		}

		$out .= "\n";
		$out .= $this->temp['week_row_end'];
		$out .= "\n";

		while ($day <= $total_days)
		{
			$out .= "\n";
			$out .= $this->temp['cal_row_start'];
			$out .= "\n";

			for ($i = 0; $i < 7; $i++)
			{
				if ($day > 0 AND $day <= $total_days)
				{
					$checkin    = date('Y-m-d', strtotime($year . '-' . $month . '-' . $day));
					$cell_class = [];
					if ($this->bookingType == 0)
					{
						$checkout = date('Y-m-d', strtotime('+1 day', strtotime($checkin)));
					}
					else
					{
						$checkout = $checkin;
					}

					// If this property has MASTER/SLAVE setup, then we must check master first
					if ($this->masterId > 0)
					{
						if ($roomTypeID != $this->masterId)
						{
							$availableRoomsMaster      = $solidresRoomType->getListAvailableRoom($this->masterId, $checkin, $checkout, $this->bookingType, 0, $confirmationState);
							$countAvailableRoomsMaster = is_array($availableRoomsMaster) ? count($availableRoomsMaster) : 0;

							if (!$availableRoomsMaster || $countAvailableRoomsMaster == 0)
							{
								$availableRooms = $availableRoomsMaster;
							}
							else
							{
								$availableRooms = $solidresRoomType->getListAvailableRoom($roomTypeID, $checkin, $checkout, $this->bookingType, 0, $confirmationState);
							}
						}
						else if ($roomTypeID == $this->masterId)
						{
							$isSlaveNotAvail = false;
							foreach ($this->slaveIds as $slaveId)
							{
								$availableRoomsSlave      = $solidresRoomType->getListAvailableRoom($slaveId, $checkin, $checkout, $this->bookingType, 0, $confirmationState);
								$countAvailableRoomsSlave = is_array($availableRoomsSlave) ? count($availableRoomsSlave) : 0;

								if (!$availableRoomsSlave || $countAvailableRoomsSlave == 0)
								{
									$isSlaveNotAvail = true;
									break;
								}
							}

							if ($isSlaveNotAvail)
							{
								$availableRooms = $availableRoomsSlave;
							}
							else
							{
								$availableRooms = $solidresRoomType->getListAvailableRoom($roomTypeID, $checkin, $checkout, $this->bookingType, 0, $confirmationState);
							}
						}
					}
					else
					{
						$availableRooms = $solidresRoomType->getListAvailableRoom($roomTypeID, $checkin, $checkout, $this->bookingType, 0, $confirmationState);
					}

					$temp = ($is_current_month == true AND $day == $cur_day) ? $this->temp['cal_cell_start_today_busy'] : $this->temp['cal_cell_start_busy'];

					$data[$day]['cell_class'] = '';

					if ($month <= $cur_month && $day < $cur_day && $year <= $cur_year)
					{
						$data[$day]['cell_class'] .= 'past';
					}

					$countAvailableRooms = is_array($availableRooms) ? count($availableRooms) : 0;

					if (!$availableRooms || $countAvailableRooms == 0)
					{
						$cell_class[] = 'busy';
						if ($this->style == 'legacy')
						{
							$previous_state = 'busy';
							$hasCheckIn     = $solidresReservation->hasCheckIn($roomTypeID, $checkin);
							if ($hasCheckIn)
							{
								$cell_class[] = 'ci';
							}
						}

						$data[$day]['cell_link'] = 'javascript:void(0)';
					}
					elseif ($countAvailableRooms > 0 && $countAvailableRooms < $this->totalAvailableRooms)
					{
						$cell_class[] = 'restricted';
						if ($this->style == 'legacy')
						{
							$previous_state = 'restricted';
							$hasCheckIn     = $solidresReservation->hasCheckIn($roomTypeID, $checkin);
							if ($hasCheckIn)
							{
								$cell_class[] = 'ci';
							}
						}
						$data[$day]['cell_link'] = 'javascript:void(0)';
					}

					if ($this->style == 'legacy')
					{
						$query = $dbo->getQuery(true);
						$query->select('count(*)')->from('#__sr_reservations AS a')
							->innerJoin('#__sr_rooms AS b ON b.room_type_id = ' . (int) $roomTypeID)
							->innerJoin('#__sr_reservation_room_xref AS c ON c.room_id = b.id AND c.reservation_id = a.id')
							->where('a.checkout = ' . $dbo->quote($checkin) . ' AND a.state = ' . $confirmationState);
						$hasCheckOut = $dbo->setQuery($query)->loadResult() > 0;
						if ($hasCheckOut)
						{
							$data[$day]['cell_link'] = 'javascript:void(0)';
						}

						if ($hasCheckOut && isset($previous_state) && ($previous_state == 'busy' || $previous_state == 'restricted'))
						{
							$cell_class[] = $previous_state;
							$cell_class[] = 'co';
						}

						if (in_array('co', $cell_class))
						{
							$previous_state = '';
						}
					}

					$data[$day]['cell_class'] .= ' ' . implode(' ', $cell_class);

					$out .= str_replace('{cell_class}', $data[$day]['cell_class'], $temp);

					if (isset($data[$day]) && isset($data[$day]['cell_link']))
					{
						// Cells with content
						$temp = ($is_current_month == true AND $day == $cur_day) ? $this->temp['cal_cell_content_today'] : $this->temp['cal_cell_content'];
						$out  .= str_replace('{cell_class}', $data[$day]['cell_class'], str_replace('{day}', $day, str_replace('{cell_link}', $data[$day]['cell_link'], $temp)));
					}
					else
					{
						// Cells with no content
						$temp = ($is_current_month == true AND $day == $cur_day) ? $this->temp['cal_cell_no_content_today'] : $this->temp['cal_cell_no_content'];
						$out  .= str_replace('{day}', $day, $temp);
					}
				}
				else
				{
					// Blank cells
					$out .= ($is_current_month == true AND $day == $cur_day) ? $this->temp['cal_cell_start_today'] : $this->temp['cal_cell_start'];
					$out .= $this->temp['cal_cell_blank'];
				}

				$out .= ($is_current_month == true AND $day == $cur_day) ? $this->temp['cal_cell_end_today'] : $this->temp['cal_cell_end'];
				$day++;
			}

			$out .= "\n";
			$out .= $this->temp['cal_row_end'];
			$out .= "\n";
		}

		$out .= "\n";
		$out .= $this->temp['table_close'];

		return $out;
	}

	// --------------------------------------------------------------------

	/**
	 * Get Month Name
	 *
	 * Generates a textual month name based on the numeric
	 * month provided.
	 *
	 * @access    public
	 *
	 * @param integer    the month
	 *
	 * @return    string
	 */
	function get_month_name($month)
	{
		if ($this->month_type == 'short')
		{
			$month_names = array('01' => 'JANUARY_SHORT', '02' => 'FEBRUARY_SHORT', '03' => 'MARCH_SHORT', '04' => 'APRIL_SHORT', '05' => 'MAY_SHORT', '06' => 'JUNE_SHORT', '07' => 'JULY_SHORT', '08' => 'AUGUST_SHORT', '09' => 'SEPTEMBER_SHORT', '10' => 'OCTOBER_SHORT', '11' => 'NOVEMBER_SHORT', '12' => 'DECEMBER_SHORT');
		}
		else
		{
			$month_names = array('01' => 'JANUARY', '02' => 'FEBRUARY', '03' => 'MARCH', '04' => 'APRIL', '05' => 'MAY', '06' => 'JUNE', '07' => 'JULY', '08' => 'AUGUST', '09' => 'SEPTEMBER', '10' => 'OCTOBER', '11' => 'NOVEMBER', '12' => 'DECEMBER');
		}

		$month = $month_names[$month];

		return JText::_($month);
	}

	// --------------------------------------------------------------------

	/**
	 * Get Day Names
	 *
	 * Returns an array of day names (Sunday, Monday, etc.) based
	 * on the type.  Options: long, short, abrev
	 *
	 * @access    public
	 *
	 * @param string
	 *
	 * @return    array
	 */
	function get_day_names($day_type = '')
	{
		if ($day_type != '')
			$this->day_type = $day_type;

		if ($this->day_type == 'long')
		{
			$day_names = array('sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday');
		}
		elseif ($this->day_type == 'short')
		{
			$day_names = array('sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat');
		}
		else
		{
			$day_names = array('su', 'mo', 'tu', 'we', 'th', 'fr', 'sa');
		}

		$days = array();
		foreach ($day_names as $val)
		{
			$days[] = ucfirst(JText::_($val));
		}

		return $days;
	}

	// --------------------------------------------------------------------

	/**
	 * Adjust Date
	 *
	 * This function makes sure that we have a valid month/year.
	 * For example, if you submit 13 as the month, the year will
	 * increment and the month will become January.
	 *
	 * @access    public
	 *
	 * @param integer    the month
	 * @param integer    the year
	 *
	 * @return    array
	 */
	function adjust_date($month, $year)
	{
		$date = array();

		$date['month'] = $month;
		$date['year']  = $year;

		while ($date['month'] > 12)
		{
			$date['month'] -= 12;
			$date['year']++;
		}

		while ($date['month'] <= 0)
		{
			$date['month'] += 12;
			$date['year']--;
		}

		if (strlen($date['month']) == 1)
		{
			$date['month'] = '0' . $date['month'];
		}

		return $date;
	}

	// --------------------------------------------------------------------

	/**
	 * Total days in a given month
	 *
	 * @access    public
	 *
	 * @param integer    the month
	 * @param integer    the year
	 *
	 * @return    integer
	 */
	function get_total_days($month, $year)
	{
		$days_in_month = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);

		if ($month < 1 OR $month > 12)
		{
			return 0;
		}

		// Is the year a leap year?
		if ($month == 2)
		{
			if ($year % 400 == 0 OR ($year % 4 == 0 AND $year % 100 != 0))
			{
				return 29;
			}
		}

		return $days_in_month[$month - 1];
	}

	// --------------------------------------------------------------------

	/**
	 * Set Default Template Data
	 *
	 * This is used in the event that the user has not created their own template
	 *
	 * @access    public
	 * @return array
	 */
	function default_template()
	{
		return array(
			'table_open'                => '<table border="0" cellpadding="4" cellspacing="0" class="table btype-{booking_type}">',
			'heading_row_start'         => '<tr>',
			'heading_previous_cell'     => '<th><a href="{previous_url}">&lt;&lt;</a></th>',
			'heading_title_cell'        => '<th colspan="{colspan}">{heading}</th>',
			'heading_next_cell'         => '<th><a href="{next_url}">&gt;&gt;</a></th>',
			'heading_row_end'           => '</tr>',
			'week_row_start'            => '<tr class="row-week-day">',
			'week_day_cell'             => '<td>{week_day}</td>',
			'week_row_end'              => '</tr>',
			'cal_row_start'             => '<tr class="row-month-day">',
			'cal_cell_start'            => '<td>',
			'cal_cell_start_today'      => '<td>',
			'cal_cell_start_busy'       => '<td class="{cell_class}">',
			'cal_cell_start_today_busy' => '<td class="{cell_class}">',
			'cal_cell_content'          => '<a href="{cell_link}">{day}</a>',
			'cal_cell_content_today'    => '<a class="today" href="{cell_link}">{day}</a>',
			'cal_cell_no_content'       => '{day}',
			'cal_cell_no_content_today' => '<span class="today">{day}</span>',
			'cal_cell_blank'            => '&nbsp;',
			'cal_cell_end'              => '</td>',
			'cal_cell_end_today'        => '</td>',
			'cal_row_end'               => '</tr>',
			'table_close'               => '</table>'
		);
	}

	// --------------------------------------------------------------------

	/**
	 * Parse Template
	 *
	 * Harvests the data within the template {pseudo-variables}
	 * used to display the calendar
	 *
	 * @access    public
	 * @return    void
	 */
	function parse_template()
	{
		$this->temp = $this->default_template();

		if ($this->template == '')
		{
			return;
		}

		$today = array('cal_cell_start_today', 'cal_cell_content_today', 'cal_cell_no_content_today', 'cal_cell_end_today');

		foreach (array('table_open', 'table_close', 'heading_row_start', 'heading_previous_cell', 'heading_title_cell', 'heading_next_cell', 'heading_row_end', 'week_row_start', 'week_day_cell', 'week_row_end', 'cal_row_start', 'cal_cell_start', 'cal_cell_content', 'cal_cell_no_content', 'cal_cell_blank', 'cal_cell_end', 'cal_row_end', 'cal_cell_start_today', 'cal_cell_content_today', 'cal_cell_no_content_today', 'cal_cell_end_today') as $val)
		{
			if (preg_match("/\{" . $val . "\}(.*?)\{\/" . $val . "\}/si", $this->template, $match))
			{
				$this->temp[$val] = $match['1'];
			}
			else
			{
				if (in_array($val, $today, true))
				{
					$this->temp[$val] = $this->temp[str_replace('_today', '', $val)];
				}
			}
		}
	}
}
