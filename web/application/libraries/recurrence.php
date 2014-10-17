<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 

/*
 * Copyright 2011-2012 Jorge López Pérez <jorge@adobo.org>
 *
 *  This file is part of AgenDAV.
 *
 *  AgenDAV is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  any later version.
 *
 *  AgenDAV is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with AgenDAV.  If not, see <http://www.gnu.org/licenses/>.
 */

use \AgenDAV\DateHelper;

class Recurrence {

    private $date_format_pref;
    private $time_format_pref;
    private $date_format;
    private $tz;
    private $CI;

    function __construct() {
        $this->CI =& get_instance();
        $this->date_format_pref = $this->CI->config->item('default_date_format');
        $this->time_format_pref = $this->CI->config->item('default_time_format');
        $this->date_format = DateHelper::getDateFormatFor('date', $this->date_format_pref);
        $prefs = Preferences::singleton($this->CI->session->userdata('prefs'));
        if ($prefs->__get('timezone')) {
            $this->tz = $this->CI->timezonemanager->getTz($prefs->__get('timezone'));
        } else {
            $this->tz = $this->CI->timezonemanager->getTz(
                    $this->CI->config->item('default_timezone'));
        }
    }

    /**
     * Returns a human readable expression about what does this rule do
     *
     * If no explanation is possible, FALSE is returned
     */

    function rrule_explain($rrule = null) {
        $explanation = '';

        if (is_null($rrule)) {
            return FALSE;
        }
        
        foreach ($rrule as $k => $v) {
            switch($k) {
                case 'FREQ':
                    switch ($v) {
                        case 'DAILY':
                            $explanation = $this->CI->i18n->_('labels',
                                    'repeatdaily');
                            break;
                        case 'WEEKLY':
                            $explanation = $this->CI->i18n->_('labels',
                                    'repeatweekly');
                            break;
                        case 'MONTHLY':
                            $explanation = $this->CI->i18n->_('labels',
                                    'repeatmonthly');
                            break;
                        case 'YEARLY':
                            $explanation = $this->CI->i18n->_('labels',
                                    'repeatyearly');
                            break;
                        default:
                            // Oops!
                            return FALSE;
                            break;
                    }
                    break;
                case 'COUNT':
                    $explanation .= ', ' . $this->CI->i18n->_('labels',
                            'explntimes',
                            array('%n' => $v));
                    break;
                case 'UNTIL':
                    $date = DateHelper::iCalcreatorToDateTime(
                        $v,
                        $this->CI->timezonemanager->getTz('UTC')
                    );
                    $date->setTimeZone($this->tz);
                    $explanation .= ', ' . $this->CI->i18n->_('labels',
                            'expluntil',
                            array('%d' => $date->format($this->date_format)));
                    break;
                case 'INTERVAL':
                    if ($v != "1") {
                        return FALSE;
                    }
                    break;
                default:
                    // We don't know (at this moment) how to parse this rule
                    return FALSE;
                    break;
            }
        }

        return $explanation;
    }

    /**
     * Builds a recurrence rule based on recurrence type, count and until
     */

    function build($opts, &$rrule_err) {
        if (!isset($opts['recurrence_type'])) {
            $rrule_err = $this->CI->i18n->_('messages',
                    'error_bogusrepeatrule');
            return FALSE;
        }

        $res = array();

        switch ($opts['recurrence_type']) {
            case 'DAILY':
            case 'WEEKLY':
            case 'MONTHLY':
            case 'YEARLY':
                $res['FREQ'] = $opts['recurrence_type'];
                break;
            default:
                // Oops
                $rrule_err = $this->CI->i18n->_('messages',
                        'error_bogusrepeatrule');
                return FALSE;
                break;
        }

        if (isset($opts['recurrence_count']) &&
                !empty($opts['recurrence_count'])) {
            $res['COUNT'] = $opts['recurrence_count'];
        } else if (isset($opts['recurrence_until']) &&
                !empty($opts['recurrence_until'])) {
            $date =
                DateHelper::frontEndToDateTime(
                    $opts['recurrence_until'],
                    $this->date_format_pref,
                    $this->time_format_pref,
                    $this->CI->timezonemanager->getTz('UTC')
                );
            if ($date === FALSE) {
                $rrule_err = $this->CI->i18n->_('messages',
                        'error_bogusrepeatrule');
                return FALSE;
            } else {
                // TODO date-time?
                $res['UNTIL'] = DateHelper::dateTimeToiCalendar($date, 'DATE-TIME');
            }
        }

        return $res;
    }
}

