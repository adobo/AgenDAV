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

class Preferences extends CI_Model {

    private static $current = null;
    function __construct() {
        parent::__construct();
    }

    /**
     * Gets all user preferences for a given user into a Preferences object
     */
    private function load($username) {
        $options = array();
        $use_db = true;

        if (function_exists('hook_load_prefs')) {
            $res = hook_load_prefs($username);
            $options = $res['options'];
            if (array_key_exists('use_db',$res)) {
                $use_db = $res['use_db'];
            }
        }

        if ($use_db) {
            $query = $this->db->get_where('prefs',
                    array('username' => $username));

            if ($query->num_rows() == 1) {
                $result = $query->result();
                $options = json_decode($result[0]->options, TRUE);
                $options = array_merge($options,
                    json_decode($result[0]->options, TRUE));
                }
            }
        }

        $prefs = new AgenDAV\Data\Preferences($options);

        return $prefs;
    }

    /**
     * Saves user preferences
     */
    public function save($username, AgenDAV\Data\Preferences $prefs) {
        $use_db = true;

        if (function_exists('hook_save_prefs')) {
            $res = hook_save_prefs($username,$prefs->getAll());
            if (array_key_exists('use_db',$res)) {
                $use_db = $res['use_db'];
            }
        }

        if ($use_db) {
            $data = array(
                    'options' => $prefs->to_json(),
                    );

            log_message('DEBUG', 'Storing user prefs ['.
                    $prefs->to_json() .']'
                    .' for user ' . $username);

            $query = $this->db->get_where('prefs',
                    array('username' => $username));
            if ($query->num_rows() == 1) {
                $this->db->update('prefs',
                        $data, array('username' => $username));
            } else {
                $data['username'] = $username;
                $this->db->insert('prefs', $data);
            }
        }
    }

    /**
     * Gets current user prefs
     *
     * @param string $user User name
     * @param boolean $force Force load from DB
     */

    public function get($user, $force = false) {
        if ($force === true || self::$current === null) {
            self::$current = $this->load($user);
        }

        return self::$current;
    }
}

