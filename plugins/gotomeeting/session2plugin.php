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
 * GoToMeeting helpmenow meeting class
 *
 * @package     block_helpmenow
 * @copyright   2012 VLACS
 * @author      David Zaharee <dzaharee@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/session2plugin.php');
require_once(dirname(__FILE__) . '/plugin.php');

class helpmenow_session2plugin_gotomeeting extends helpmenow_session2plugin {
    /**
     * Plugin name
     * @var string $plugin
     */
    public $plugin = 'gotomeeting';

    /**
     * Extra fields
     * @var array $extra_fields
     */
    protected $extra_fields = array(
        'join_url',
        'max_participants',
        'unique_meetingid',
        'meetingid',
    );

    /**
     * GoToMeeting joinURL
     * @var string $join_url
     */
    public $join_url;

    /**
     * GoToMeeting maxParticipants
     * @var int $max_participants
     */
    public $max_participants;

    /**
     * GoToMeeting uniquemeetingid
     * @var int $unique_meetingid
     */
    public $unique_meetingid;

    /**
     * GoToMeeting meetingid
     * @var int $meetingid
     */
    public $meetingid;

    /**
     * Create the meeting. Caller will insert record.
     */
    public function create() {
        $params = array(
            'subject' => 'Something',   # todo: change this
            'starttime' => gmdate('Y-m-d\TH:i:s\Z', time() + (24*60*60)), # do a day from now to be safe
            'endtime' => gmdate('Y-m-d\TH:i:s\Z', time() + (25*60*60)),    # lenght of 1 hour, but it doesn't really matter
            'passwordrequired' => 'false',
            'conferencecallinfo' => 'Hybrid',
            'timezonekey' => '',
            'meetingtype' => 'Immediate',
        );
        $meetingdata = helpmenow_plugin_gotomeeting::api('meetings', 'POST', $params);
        $meetingdata = reset($meetingdata);
        $this->join_url = $meetingdata->joinURL;
        $this->max_participants = $meetingdata->maxParticipants;
        $this->unique_meetingid = $meetingdata->uniqueMeetingId;
        $this->meetingid = $meetingdata->meetingid;
        return true;
    }
}

?>