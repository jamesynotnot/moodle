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
 * User sign-up form.
 *
 * @package    core
 * @subpackage auth
 * @copyright  1999 onwards Martin Dougiamas  http://dougiamas.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/user/profile/lib.php');
require_once($CFG->dirroot . '/user/editlib.php');

class login_signup_form extends moodleform implements renderable, templatable {
    function definition() {
        global $USER, $CFG;

        $mform = $this->_form;

        // DestinyEDU: username field is removed and is not used for sign-up/in; as a required field, however, it is set to email
        //   address in signup.php on submit (temp email address is set below for field validation on submit).
        // Setting "Allow extended characters in usernames" is required since Moodle username charset is random and does not
        //   conform to RFC 822 (even simple uppercase is not allowed).
        // Username has client-side processing rules that make it required, i.e. if not included in the form, form is not accepted
        // Solution: 1) use email label, email validation and LTR with username,
        //           2) create a hidden email field with a setDefault to a valid temp address
        //           3) substitute username as the real email address, a.k.a. username, in signup.php.  valid@valid.valid

        $mform->addElement('text', 'username', get_string('email'), 'maxlength="100" size="40" autocapitalize="none"');
        $mform->setType('username', core_user::get_property_type('email'));
        $mform->addRule('username', 'Invalid email address', 'regex', '/^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/', 'client');
        $mform->addRule('username', get_string('missingemail'), 'required', null, 'client');
        $mform->setForceLtr('username');

        $mform->addElement('hidden', 'email', get_string('email'), 'maxlength="100" size="25"');
        $mform->setDefault('email', 'valid@valid.valid');

        if (!empty($CFG->passwordpolicy)){
            $mform->addElement('static', 'passwordpolicyinfo', '', print_password_policy());
        }
        $mform->addElement('password', 'password', get_string('password'), 'maxlength="32" size="12"');
        $mform->setType('password', core_user::get_property_type('password'));
        $mform->addRule('password', get_string('missingpassword'), 'required', null, 'client');

        $strpasswordagain = get_string('password') . ' (' . get_string('again') . ')';
        $mform->addElement('password', 'password2', $strpasswordagain, 'maxlength="32" size="12"');
        $mform->setType('password2', PARAM_RAW);
        $mform->addRule('password2', get_string('missingpassword'), 'required', null, 'client');

        $namefields = useredit_get_required_name_fields();
        foreach ($namefields as $field) {
            $mform->addElement('text', $field, get_string($field), 'maxlength="100" size="30"');
            $mform->setType($field, core_user::get_property_type('firstname'));
            $stringid = 'missing' . $field;
            if (!get_string_manager()->string_exists($stringid, 'moodle')) {
                $stringid = 'required';
            }
            $mform->addRule($field, get_string($stringid), 'required', null, 'client');
        }

        $phone2 = '';
        if (isset($_GET['sms'])) {
            $phone2 = $_GET['sms'];
        }
        if (isset($_GET['whatsapp'])) {
            $phone2 = $_GET['whatsapp'];
        }
        $mform->addElement('text', 'phone2', get_string('phone2'), 'maxlength="25" size="25"');
        $mform->setType('phone2', core_user::get_property_type('phone2'));
        $mform->setDefault('phone2', $phone2);
        $mform->addRule('phone2', 'Please type a valid phone number', 'regex', '/^((\+)?[1-9]{1,2})?([-\s\.])?((\(\d{1,4}\))|\d{1,4})(([-\s\.])?[0-9]{1,12}){1,2}$/', 'client');
        $mform->addRule('phone2', 'Missing mobile phone', 'required', null, 'client');
        $mform->setForceLtr('phone2');

        $mform->addElement('text', 'city', get_string('city'), 'maxlength="120" size="20"');
        $mform->setType('city', core_user::get_property_type('city'));
        $mform->addRule('city', 'Missing city or town', 'required', null, 'client');
        if (!empty($CFG->defaultcity)) {
            $mform->setDefault('city', $CFG->defaultcity);
        }

        $country = get_string_manager()->get_list_of_countries();

//        $host_country['NG'] = 'Nigeria';
//        $country = array_merge($host_country, $country);
//        $host_country['NE'] = 'Niger';
//        $country = array_merge($host_country, $country);
//        $host_country['TD'] = 'Chad';
//        $country = array_merge($host_country, $country);
//        $host_country['CM'] = 'Cameroon';
//        $country = array_merge($host_country, $country);
//        $host_country['BJ'] = 'Benin';
//        $country = array_merge($host_country, $country);

        $default_country[''] = get_string('selectacountry');
        $country = array_merge($default_country, $country);
        $mform->addElement('select', 'country', get_string('country'), $country);
        $mform->addRule('country', 'Missing country', 'required', null, 'client');

        if(isset($phone2) === true && $phone2 !== '') {

            $iso_country_code = $this->get_iso_country_code($phone2);
            if ($iso_country_code !== '') {
                $mform->setDefault('country', $iso_country_code);
            } else {
                $mform->setDefault('country', '');
            }
        } else {
            $mform->setDefault('country', '');
        }

        profile_signup_fields($mform);

        if (signup_captcha_enabled()) {
            $mform->addElement('recaptcha', 'recaptcha_element', '');
            $mform->addHelpButton('recaptcha_element', 'recaptcha', 'auth');
            $mform->closeHeaderBefore('recaptcha_element');
        }

        $mform->addElement('static', 'info', '', 'Begin your journey of empowerment and education!');

        // Add "Agree to sitepolicy" controls. By default it is a link to the policy text and a checkbox but
        // it can be implemented differently in custom sitepolicy handlers.
        $manager = new \core_privacy\local\sitepolicy\manager();
        $manager->signup_form($mform);

        // buttons
        $this->add_action_buttons(true, get_string('createaccount'));

    }

    function get_iso_country_code($phone){

        $iso_3166_country_code = '';
        if ($phone === '') {
            $iso_3166_country_code = '';
        } else if (substr( $phone, 0, 2 ) === "+1") {
            $iso_3166_country_code = ''; // TODO parse counties that use '1': CA, TT, etc.
        } else if (substr( $phone, 0, 4 ) === "+234") {
            $iso_3166_country_code = 'NG';
        } else if (substr( $phone, 0, 4 ) === "+227") {
            $iso_3166_country_code = 'NE';
        } else if (substr( $phone, 0, 4 ) === "+235") {
            $iso_3166_country_code = 'TD';
        } else if (substr( $phone, 0, 4 ) === "+237") {
            $iso_3166_country_code = 'CM';
        } else if (substr( $phone, 0, 4 ) === "+229") {
            $iso_3166_country_code = 'BE';
        } else if (substr( $phone, 0, 4 ) === "+972") {
            $iso_3166_country_code = 'IL';
        } else if (substr( $phone, 0, 4 ) === "+255") {
            $iso_3166_country_code = 'TZ';
        } else if (substr( $phone, 0, 4 ) === "+254") {
            $iso_3166_country_code = 'KE';
        } else if (substr( $phone, 0, 4 ) === "+252") {
            $iso_3166_country_code = 'SO';
        } else if (substr( $phone, 0, 4 ) === "+251") {
            $iso_3166_country_code = 'ET';
        } else if (substr( $phone, 0, 4 ) === "+256") {
            $iso_3166_country_code = 'UG';
        } else if (substr( $phone, 0, 4 ) === "+250") {
            $iso_3166_country_code = 'RW';
        } else if (substr( $phone, 0, 4 ) === "+257") {
            $iso_3166_country_code = 'BI';
        }

        return $iso_3166_country_code;
    }

    function definition_after_data(){
        $mform = $this->_form;
        $mform->applyFilter('username', 'trim');

        // Trim required name fields.
        foreach (useredit_get_required_name_fields() as $field) {
            $mform->applyFilter($field, 'trim');
        }
    }

    /**
     * Validate user supplied data on the signup form.
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @return array of "element_name"=>"error_description" if there are errors,
     *         or an empty array if everything is OK (true allowed for backwards compatibility too).
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (signup_captcha_enabled()) {
            $recaptchaelement = $this->_form->getElement('recaptcha_element');
            if (!empty($this->_form->_submitValues['g-recaptcha-response'])) {
                $response = $this->_form->_submitValues['g-recaptcha-response'];
                if (!$recaptchaelement->verify($response)) {
                    $errors['recaptcha_element'] = get_string('incorrectpleasetryagain', 'auth');
                }
            } else {
                $errors['recaptcha_element'] = get_string('missingrecaptchachallengefield');
            }
        }

        if ($data['password'] <> $data['password2']) {
            $errors['password2'] = get_string('passwordsdiffer');
        }

        $errors += signup_validate_data($data, $files);

        return $errors;
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output Used to do a final render of any components that need to be rendered for export.
     * @return array
     */
    public function export_for_template(renderer_base $output) {
        ob_start();
        $this->display();
        $formhtml = ob_get_contents();
        ob_end_clean();
        $context = [
            'formhtml' => $formhtml
        ];
        return $context;
    }
}
