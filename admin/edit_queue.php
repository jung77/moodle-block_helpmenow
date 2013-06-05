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
 * This script handles the queue edit form.
 *
 * @package     block_helpmenow
 * @copyright   2012 VLACS
 * @author      David Zaharee <dzaharee@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once(dirname(dirname(__FILE__)) . '/lib.php');

# require login
require_login(0, false);

# get our parameters
$queueid = optional_param('queueid', 0, PARAM_INT);

# urls
$admin_url = "$CFG->wwwroot/blocks/helpmenow/admin/manage_queues.php";

# contexts and cap check
$sitecontext = get_context_instance(CONTEXT_SYSTEM, SITEID);
if (!has_capability(HELPMENOW_CAP_MANAGE, $sitecontext)) {
    redirect();
}

# form stuff
$form = new helpmenow_queue_form();
if ($form->is_cancelled()) {                # cancelled
    redirect($admin_url);
} else if ($formdata = $form->get_data()) {     # submitted
    if (!$formdata->queueid) {
        $DB->insert_record('block_helpmenow_queue', $formdata);
    } else {
        $formdata->id = $formdata->queueid;
        $DB->update_record('block_helpmenow_queue', $formdata);
    }
    redirect($admin_url);
} 

# title, navbar, and a nice box
$title = get_string('queue_edit', 'block_helpmenow');
$nav = array(
    array('name' => get_string('admin', 'block_helpmenow'), 'link' => $admin_url),
    array('name' => $title),
);
print_header($title, $title, build_navigation($nav));
print_box_start('generalbox centerpara');

$toform = array(
    'queueid' => $queueid,
);
if ($queueid) {
    $queue = new helpmenow_queue($queueid);
    $toform['name'] = $queue->name;
    $toform['description'] = $queue->description;
    $toform['weight'] = $queue->weight;
}

$form->set_data($toform);
$form->display();

print_box_end();

# footer
print_footer();

?>
