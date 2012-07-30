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
 * Help me now wiziq lib
 *
 * @package     block_helpmenow
 * @copyright   2012 VLACS
 * @author      David Zaharee <dzaharee@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/lib.php');

define('HELPMENOW_WIZIQ_API_URL', 'http://class.api.wiziq.com/');
define('HELPMENOW_WIZIQ_DURATION', 60);     # this is minutes as that's what the api takes


function helpmenow_wiziq_add_attendee($class_id) {
    global $USER;

    $attendee_list = new SimpleXMLElement('<attendee_list/>');
    $attendee = $attendee_list->addChild('attendee');
    $attendee->addChild('attendee_id', $USER->id);
    $attendee->addChild('screen_name', fullname($USER));
    $attendee->addChild('language_culture_name', 'en-us');

    $params = array(
        'class_id' => $class_id,
        'attendee_list' => $attendee_list->asXML(),
    );
    return helpmenow_wiziq_api('add_attendees', $params);
}

function helpmenow_wiziq_api($method, $params) {
    global $CFG;

    $signature = array();
    $signature['access_key'] = $CFG->helpmenow_wiziq_access_key;
    $signature['timestamp'] = time();
    $signature['method'] = $method;
    $signature['signature'] = helpmenow_wiziq_api_signature($signature);

    $params = array_merge($signature, $params);

    $ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_POSTFIELDS => http_build_query($params, '', '&'),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => false,
        CURLINFO_HEADER_OUT => true,
        CURLOPT_URL => HELPMENOW_WIZIQ_API_URL . "?method=$method",
    ));
    $response = curl_exec($ch);
    curl_close($ch);

    return new SimpleXMLElement($response);
}

function helpmenow_wiziq_api_signature($sig_params) {
    global $CFG;

    $sig_base = array();
    foreach ($sig_params as $f => $v) {
        $sig_base[] = "$f=$v";
    }
    $sig_base = implode('&', $sig_base);

    return base64_encode(helpmenow_wiziq_hmacsha1(urlencode($CFG->helpmenow_wiziq_secret_key), $sig_base));
}

/**
 * using wiziq's "hmac_sha1" function, as it doesn't match php's hash_hmac
 */
function helpmenow_wiziq_hmacsha1($key, $data) {
    $blocksize = 64;
    $hashfunc = 'sha1';
    if (strlen($key)>$blocksize) {
        $key = pack('H*', $hashfunc($key));
    }
    $key = str_pad($key, $blocksize,chr(0x00));
    $ipad = str_repeat(chr(0x36), $blocksize);
    $opad = str_repeat(chr(0x5c), $blocksize);
    $hmac = pack(
        'H*',$hashfunc(
            ($key^$opad).pack(
                'H*',$hashfunc(
                    ($key^$ipad).$data
                )
            )
        )
    );
    return $hmac;
}

/**
 *     _____ _
 *    / ____| |
 *   | |    | | __ _ ___ ___  ___  ___
 *   | |    | |/ _` / __/ __|/ _ \/ __|
 *   | |____| | (_| \__ \__ \  __/\__ \
 *    \_____|_|\__,_|___/___/\___||___/
 */

/**
 * wiziq helpmenow plugin class
 */
class helpmenow_plugin_wiziq extends helpmenow_plugin {
    /**
     * Plugin name
     * @var string $plugin
     */
    public $plugin = 'wiziq';

    /**
     * Cron
     * @return boolean
     */
    public static function cron() {
        return true;
    }

    public static function display($privileged = false) {
        global $CFG, $USER;

        switch ($USER->id) {
        case 5:
        case 56385:
        case 919:
        case 52650:
        case 37479:
        case 57885:
        case 56528:
        case 8712:
                break;
        default:
            return '';
        }

        if ($privileged) {
            $create_url = new moodle_url("$CFG->wwwroot/blocks/helpmenow/plugins/wiziq/create.php");
            $create_url->param('sessionid', required_param('session', PARAM_INT));
            return link_to_popup_window($create_url->out(), "wiziq", 'Start WizIQ', 400, 500, null, null, true);
        }
        return '';
    }

    public static function on_login() {
        global $CFG, $USER;

        $user2plugin = helpmenow_user2plugin_wiziq::get_user2plugin();
        # if we don't have a user2plugin record, we need one
        if (!$user2plugin) {
            $user2plugin = new helpmenow_user2plugin_wiziq();
            $user2plugin->userid = $USER->id;
            $user2plugin->insert();
        }
        return true;
    }
}

/**
 * wiziq user2plugin class
 */
class helpmenow_user2plugin_wiziq extends helpmenow_user2plugin {
    /**
     * Extra fields
     * @var array $extra_fields
     */
    protected $extra_fields = array(
        'class_id',
        'presenter_url',
        'duration',
        'timecreated',
    );

    /**
     * wiziq class_id
     * @var int $class_id
     */
    public $class_id;

    /**
     * wiziq presenter_url
     * @var string $presenter_url
     */
    public $presenter_url;

    /**
     * duration in seconds of meeting
     * @var integer $duration
     */
    public $duration;

    /**
     * timestamp of creation
     * @var integer $timecreated
     */
    public $timecreated;

    /**
     * plugin
     * @var string $plugin
     */
    public $plugin = 'wiziq';

    /**
     * Create the meeting.
     */
    public function create_meeting() {
        global $CFG, $USER;

        $update_url = new moodle_url("$CFG->wwwroot/blocks/helpmenow/plugins/wiziq/update.php");
        $update_url->param('user_id', $USER->id);

        $params = array(
            'title' => fullname($USER),
            'start_time' => date('m/d/Y G:i:s'),
            'time_zone' => date('e'),
            'presenter_id' => $USER->id,
            'presenter_name' => fullname($USER),
            'duration' => HELPMENOW_WIZIQ_DURATION,
            'status_ping_url' => $update_url->out(),
        );
        $response = helpmenow_wiziq_api('create', $params);

        $this->class_id = (integer) $response->create->class_details->class_id;
        $this->presenter_url = (string) $response->create->class_details->presenter_list->presenter[0]->presenter_url;
        $this->duration = HELPMENOW_WIZIQ_DURATION * 60;    # we're saving in seconds instead of minutes
        $this->timecreated = time();

        $this->update();

        return true;
    }

    /**
     * see if the meeting is still ative
     * @return bool true = active
     */
    public function verify_active_meeting() {
        global $USER;

        $params = array(
            'class_id' => $this->class_id,
            'title' => fullname($USER),
        );
        $response = helpmenow_wiziq_api('modify', $params);

        if (debugging()) {
            print_object($response);
        }

        # if we can modify it at all, it's cause it hasn't started
        if ((string) $response['status'] == 'ok') {
            return true;
        }

        switch ((integer) $response->error['code'] == 1015) {
        case 1015:  # in-progress
            return true;
        case 1016:  # completed
        case 1017:  # expired
        case 1018:  # deleted
        default:
            return false;
        }
    }

    /**
     * "delete" the meeting
     */
    public function delete_meeting() {
        foreach ($this->extra_fields as $attribute) {
            unset($this->$attribute);
        }
        return $this->update();
    }
}

/**
 * wiziq session2plugin class
 */
class helpmenow_session2plugin_wiziq extends helpmenow_session2plugin {
    /**
     * Extra fields
     * @var array $extra_fields
     */
    protected $extra_fields = array(
        'classes',
    );

    /**
     * array of wiziq classes that have been linked in this session
     * @var array $classes
     */
    public $classes = array();

    /**
     * plugin
     * @var string $plugin
     */
    public $plugin = 'wiziq';
}

?>