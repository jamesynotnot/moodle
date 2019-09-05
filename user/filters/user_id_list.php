<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * User id field filter
 *
 * @package   core_user
 * @category  user
 * @copyright 1999 Martin Dougiamas  http://dougiamas.com
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot . '/user/filters/lib.php');

/**
 * Generic filter for text fields.
 * @copyright 1999 Martin Dougiamas  http://dougiamas.com
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_id_list extends user_filter_type
{
    /** @var string */
    public $_field;

    /**
     * Constructor
     * @param string $name the name of the filter instance
     * @param string $label the label of the filter instance
     * @param boolean $advanced advanced form element flag
     * @param string $field user table filed name
     */
    public function __construct($name, $label, $advanced, $field)
    {
        parent::__construct($name, $label, $advanced);
        $this->_field = $field;
    }

    /**
     * Old syntax of class constructor. Deprecated in PHP7.
     *
     * @deprecated since Moodle 3.1
     */
    public function user_id_list($name, $label, $advanced, $field)
    {
        debugging('Use of class name as constructor is deprecated', DEBUG_DEVELOPER);
        self::__construct($name, $label, $advanced, $field);
    }

    /**
     * Adds controls specific to this filter in the form.
     * @param object $mform a MoodleForm object to setup
     */
    public function setupForm(&$mform)
    {
        $objs = array();
        $objs['text'] = $mform->createElement('textarea', $this->_name, null);
        $objs['text']->setLabel(get_string('valuefor', 'filters', $this->_label));
        $grp =& $mform->addElement('group', $this->_name . '_grp', $this->_label, $objs, '', false);
        $mform->setType($this->_name, PARAM_RAW);
        if ($this->_advanced) {
            $mform->setAdvanced($this->_name . '_grp');
        }
    }

    /**
     * Retrieves data from the form data
     * @param object $formdata data submited with the form
     * @return mixed array filter data or false when filter not set
     */
    public function check_data($formdata)
    {
        $field = $this->_name;

        // If field value is set then use it, else it's null.
        $fieldvalue = null;
        if (isset($formdata->$field)) {
            $fieldvalue = $formdata->$field;
            return array('value' => $fieldvalue);
        }

        return false;
    }

    /**
     * Returns the condition to be used with SQL where
     * @param array $data filter settings
     * @return array sql string and $params
     */
    public function get_sql_filter($data)
    {
        $value = $data['value'];

        $params = array();

        if (strlen($value) > 0) {
            $res = explode(",", $value);

            $useridlist = '';
            foreach ($res as $userid) {
                if (is_numeric($userid)) {
                    $useridlist = $useridlist . $userid . ',';
                }
            }
            if (strlen($useridlist) > 0) {
                $useridlist = substr($useridlist, 0, strlen($useridlist) - 1);
            }

            $sql = "id IN ($useridlist)";

            return array($sql, $params);
        }

        return '';
    }

    /**
     * Returns a human friendly description of the filter used as label.
     * @param array $data filter settings
     * @return string active filter label
     */
    public function get_label($data)
    {
        $value = $data['value'];

        $a = new stdClass();
        $a->label = $this->_label;
        $a->value = s($value);

        return get_string('globalrolelabel', 'filters', $a);
    }
}
