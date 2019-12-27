<?php
/**
 * Allows course enrolment via a simple text code.
 *
 * @package   enrol_easy
 * @copyright 2017 Dearborn Public Schools
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("$CFG->libdir/formslib.php");
require_once(dirname(__FILE__) . '/../../config.php');

function randomstring($length = 10) {
    //$characters = '0123456789abcdefghijklmnopqrstuvwxyz';
    // DestinyEDU: index.php line 46: force lower, form.mustache line 9 transform lower
    $characters = '23456789abcdefghijkmnpqrstuvwxyz';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}
/*
 * https://stackoverflow.com/questions/4117555/simplest-way-to-detect-a-mobile-device
 */
function isMobile() {
        return preg_match("/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i", $_SERVER["HTTP_USER_AGENT"]);
}

class enrolform extends moodleform {
    public function definition() {
        global $CFG;

        $mform = $this->_form;

        $mform->addElement('text', 'enrolform_course_code', get_string('enrolform_course_code', 'enrol_easy'));
        $mform->setType('enrolform_course_code', PARAM_NOTAGS);

        $mform->addElement('submit', 'enrolform_submit', get_string('enrolform_submit', 'enrol_easy'));
    }
    function validation($data, $files) {
        return array();
    }
}

/**
 * Send welcome email to specified user.
 *
 * @param stdClass $instance
 * @param stdClass $user user record
 * @return void
 */
function email_welcome_message($instance, $user) {
    global $CFG, $DB;

    $course = $DB->get_record('course', array('id' => $instance->courseid), '*', MUST_EXIST);
    $context = context_course::instance($course->id);

    $a = new stdClass();
    $a->coursename = format_string($course->fullname, true, array('context' => $context));
    $a->profileurl = "$CFG->wwwroot/user/view.php?id=$user->id&course=$course->id";
    $a->courseurl = "$CFG->wwwroot/course/view.php?id=$course->id";

    if (trim($instance->customtext1) !== '') {
        $message = $instance->customtext1;
        $key = array('{$a->coursename}', '{$a->profileurl}', '{$a->fullname}', '{$a->email}', '{$a->courseurl}');
        $value = array($a->coursename, $a->profileurl, fullname($user), $user->email, $a->courseurl);
        $message = str_replace($key, $value, $message);
        if (strpos($message, '<') === false) {
            // Plain text only.
            $messagetext = $message;
            $messagehtml = text_to_html($messagetext, null, false, true);
        } else {
            // This is most probably the tag/newline soup known as FORMAT_MOODLE.
            $messagehtml = format_text($message, FORMAT_MOODLE,
                array('context' => $context, 'para' => false, 'newlines' => true, 'filter' => false));
            $messagetext = html_to_text($messagehtml);
        }
    } else {
        $messagetext = get_string('welcometocoursetext', 'enrol_self', $a);
        $messagehtml = text_to_html($messagetext, null, false, true);
    }

    $subject = get_string('welcometocourse', 'enrol_easy',
        format_string($course->fullname, true, array('context' => $context)));

    $sendoption = $instance->customint7;
    $contact = get_welcome_email_contact($sendoption, $context);

    // Directly emailing welcome message rather than using messaging.
    email_to_user($user, $contact, $subject, $messagetext, $messagehtml);
}

/**
 * Get the "from" contact which the email will be sent from.
 *
 * @param int $sendoption send email from constant ENROL_SEND_EMAIL_FROM_*
 * @param object $context context where the user will be fetched
 * @return mixed|stdClass the contact user object.
 */
function get_welcome_email_contact($sendoption, $context) {
    global $CFG;

    if (!defined('ENROL_SEND_EMAIL_FROM_COURSE_CONTACT')) {
        define('ENROL_SEND_EMAIL_FROM_COURSE_CONTACT', 1);
    }
    if (!defined('ENROL_SEND_EMAIL_FROM_NOREPLY')) {
        define('ENROL_SEND_EMAIL_FROM_NOREPLY', 3);
    }

    $contact = null;
    // Send as the first user assigned as the course contact.
    if ($sendoption == ENROL_SEND_EMAIL_FROM_COURSE_CONTACT) {
        $rusers = array();
        if (!empty($CFG->coursecontact)) {
            $croles = explode(',', $CFG->coursecontact);
            list($sort, $sortparams) = users_order_by_sql('u');
            // We only use the first user.
            $i = 0;
            do {
                $rusers = get_role_users($croles[$i], $context, true, '',
                    'r.sortorder ASC, ' . $sort, null, '', '', '', '', $sortparams);
                $i++;
            } while (empty($rusers) && !empty($croles[$i]));
        }
        if ($rusers) {
            $contact = array_values($rusers)[0];
        }
    }

    // If send welcome email option is set to no reply or if none of the previous options have
    // returned a contact send welcome message as noreplyuser.
    if ($sendoption == ENROL_SEND_EMAIL_FROM_NOREPLY || empty($contact)) {
        $contact = core_user::get_noreply_user();
    }

    return $contact;
}
