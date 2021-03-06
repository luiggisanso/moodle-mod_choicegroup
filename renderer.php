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
 * Moodle renderer used to display special elements of the lesson module
 *
 * @package   Choice
 * @copyright 2010 Rossiani Wijaya
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/
define ('DISPLAY_HORIZONTAL_LAYOUT', 0);
define ('DISPLAY_VERTICAL_LAYOUT', 1);

class mod_choicegroup_renderer extends plugin_renderer_base {

    /**
     * Returns HTML to display choicegroups of option
     * @param object $options
     * @param int  $coursemoduleid
     * @param bool $vertical
     * @return string
     */
    public function display_options($options, $coursemoduleid, $vertical = true) {
        global $DB;
        $layoutclass = 'vertical';
        $target = new moodle_url('/mod/choicegroup/view.php');
        $attributes = array('method'=>'POST', 'target'=>$target, 'class'=> $layoutclass);

        $html = html_writer::start_tag('form', $attributes);
        $html .= html_writer::start_tag('table', array('class'=>'choicegroups' ));
        
        $html .= html_writer::start_tag('tr');
        $html .= html_writer::tag('th', get_string('choice', 'choicegroup'));
        $html .= html_writer::tag('th', get_string('group'));
        $html .= html_writer::tag('th', get_string('members/max', 'choicegroup'));
        $membersdisplay_html = '<div style="width:12px; height:12px; line-height:12px; cursor:pointer; text-align:center; display:block; border:1px #999 solid; margin:0px auto;" onclick="var choicegroups = YAHOO.util.Dom.getElementsByClassName(\'choicegroups_membersnames\'); if (this.innerHTML == \'+\') { this.innerHTML = \'-\'; for (var i=0; i < choicegroups.length; i++) { choicegroups[i].style.display=\'block\'; } } else { this.innerHTML = \'+\'; for (var i=0; i < choicegroups.length; i++) { choicegroups[i].style.display=\'none\'; } } return false;">+</div>';
        $html .= html_writer::tag('th', get_string('groupmembers', 'choicegroup') . $membersdisplay_html);
        $html .= html_writer::end_tag('tr');

        $availableoption = count($options['options']);
        foreach ($options['options'] as $option) {
            $html .= html_writer::start_tag('tr', array('class'=>'option'));
            $html .= html_writer::start_tag('td', array());
            $option->attributes->name = 'answer';
            $option->attributes->type = 'radio';

            $group = $DB->get_record('groups', array('id' => $option->text));
            $labeltext = $group->name;
            $group_members = $DB->get_records('groups_members', array('groupid' => $group->id));
            $group_members_names = array();
            foreach ($group_members as $group_member) {
                $group_user = $DB->get_record('user', array('id' => $group_member->userid));
                $group_members_names[] = $group_user->lastname . ', ' . $group_user->firstname;
            }
            sort($group_members_names);
            if (!empty($option->attributes->disabled)) {
                $labeltext .= ' ' . get_string('full', 'choicegroup');
                $availableoption--;
            }

            $html .= html_writer::empty_tag('input', (array)$option->attributes);
            $html .= html_writer::tag('td', $labeltext, array('for'=>$option->attributes->name));
            $html .= html_writer::tag('td', sizeof($group_members_names).' / '.$option->maxanswers, array('class' => 'center'));
            $group_members_html = '<!--<div style="width:12px; height:12px; line-height:12px; cursor:pointer; text-align:center; display:block; border:1px #999 solid; margin:0px auto;" onclick="if (this.innerHTML == \'+\') { this.innerHTML = \'-\'; document.getElementById(\'choicegroup_'.$option->attributes->value.'\').style.display=\'block\'; } else { this.innerHTML = \'+\'; document.getElementById(\'choicegroup_'.$option->attributes->value.'\').style.display=\'none\'; } return false;">+</div>--><div class="choicegroups_membersnames" id="choicegroup_'.$option->attributes->value.'" style="display:none;">'.implode('<br />', $group_members_names).'</div>';
            $html .= html_writer::tag('td', $group_members_html, array('class' => 'center'));
            $html .= html_writer::end_tag('td');
            $html .= html_writer::end_tag('tr');
        }
        $html .= html_writer::end_tag('table');
        $html .= html_writer::tag('div', '', array('class'=>'clearfloat'));
        $html .= html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'sesskey', 'value'=>sesskey()));
        $html .= html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'id', 'value'=>$coursemoduleid));

        if (!empty($options['hascapability']) && ($options['hascapability'])) {
            if ($availableoption < 1) {
               $html .= html_writer::tag('td', get_string('choicegroupfull', 'choicegroup'));
            } else {
                $html .= html_writer::empty_tag('input', array('type'=>'submit', 'value'=>get_string('savemychoicegroup','choicegroup'), 'class'=>'button'));
            }

            if (!empty($options['allowupdate']) && ($options['allowupdate'])) {
                $url = new moodle_url('view.php', array('id'=>$coursemoduleid, 'action'=>'delchoicegroup', 'sesskey'=>sesskey()));
                $html .= html_writer::link($url, get_string('removemychoicegroup','choicegroup'));
            }
        } else {
            $html .= html_writer::tag('td', get_string('havetologin', 'choicegroup'));
        }

        $html .= html_writer::end_tag('table');
        $html .= html_writer::end_tag('form');

        return $html;
    }

    /**
     * Returns HTML to display choicegroups result
     * @param object $choicegroups
     * @param bool $forcepublish
     * @return string
     */
    public function display_result($choicegroups, $forcepublish = false) {
        if (empty($forcepublish)) { //allow the publish setting to be overridden
            $forcepublish = $choicegroups->publish;
        }

        $displaylayout = $choicegroups->display;

        if ($forcepublish) {  //CHOICEGROUP_PUBLISH_NAMES
            return $this->display_publish_name_vertical($choicegroups);
        } else { //CHOICEGROUP_PUBLISH_ANONYMOUS';
            if ($displaylayout == DISPLAY_HORIZONTAL_LAYOUT) {
                return $this->display_publish_anonymous_horizontal($choicegroups);
            }
            return $this->display_publish_anonymous_vertical($choicegroups);
        }
    }

    /**
     * Returns HTML to display choicegroups result
     * @param object $choicegroups
     * @param bool $forcepublish
     * @return string
     */
    public function display_publish_name_vertical($choicegroups) {
        global $PAGE;
        global $DB;
        $html ='';
        $html .= html_writer::tag('h2',format_string(get_string("responses", "choicegroup")), array('class'=>'main'));

        $attributes = array('method'=>'POST');
        $attributes['action'] = new moodle_url($PAGE->url);
        $attributes['id'] = 'attemptsform';

        if ($choicegroups->viewresponsecapability) {
            $html .= html_writer::start_tag('form', $attributes);
            $html .= html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'id', 'value'=> $choicegroups->coursemoduleid));
            $html .= html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'sesskey', 'value'=> sesskey()));
            $html .= html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'mode', 'value'=>'overview'));
        }

        $table = new html_table();
        $table->cellpadding = 0;
        $table->cellspacing = 0;
        $table->attributes['class'] = 'results names ';
        $table->tablealign = 'center';
        $table->data = array();

        $count = 0;
        ksort($choicegroups->options);

        $columns = array();
        foreach ($choicegroups->options as $optionid => $options) {
            $coldata = '';
            if ($choicegroups->showunanswered && $optionid == 0) {
                $coldata .= html_writer::tag('div', format_string(get_string('notanswered', 'choicegroup')), array('class'=>'option'));
            } else if ($optionid > 0) {
                $group = $DB->get_record('groups', array('id' => $choicegroups->options[$optionid]->text));
                $coldata .= html_writer::tag('div', format_string($group->name), array('class'=>'option'));
            }
            $numberofuser = 0;
            if (!empty($options->user) && count($options->user) > 0) {
                $numberofuser = count($options->user);
            }

            $coldata .= html_writer::tag('div', ' ('.$numberofuser. ')', array('class'=>'numberofuser', 'title' => get_string('numberofuser', 'choicegroup')));
            $columns[] = $coldata;
        }

        $table->head = $columns;

        $coldata = '';
        $columns = array();
        foreach ($choicegroups->options as $optionid => $options) {
            $coldata = '';
            if ($choicegroups->showunanswered || $optionid > 0) {
                if (!empty($options->user)) {
                    foreach ($options->user as $user) {
                        $data = '';
                        if (empty($user->imagealt)){
                            $user->imagealt = '';
                        }

                        if ($choicegroups->viewresponsecapability && $choicegroups->deleterepsonsecapability  && $optionid > 0) {
                            $attemptaction = html_writer::checkbox('attemptid[]', $user->id,'');
                            $data .= html_writer::tag('div', $attemptaction, array('class'=>'attemptaction'));
                        }
                        $userimage = $this->output->user_picture($user, array('courseid'=>$choicegroups->courseid));
                        $data .= html_writer::tag('div', $userimage, array('class'=>'image'));

                        $userlink = new moodle_url('/user/view.php', array('id'=>$user->id,'course'=>$choicegroups->courseid));
                        $name = html_writer::tag('a', fullname($user, $choicegroups->fullnamecapability), array('href'=>$userlink, 'class'=>'username'));
                        $data .= html_writer::tag('div', $name, array('class'=>'fullname'));
                        $data .= html_writer::tag('div','', array('class'=>'clearfloat'));
                        $coldata .= html_writer::tag('div', $data, array('class'=>'user'));
                    }
                }
            }

            $columns[] = $coldata;
            $count++;
        }

        $table->data[] = $columns;
        foreach ($columns as $d) {
            $table->colclasses[] = 'data';
        }
        $html .= html_writer::tag('div', html_writer::table($table), array('class'=>'response'));

        $actiondata = '';
        if ($choicegroups->viewresponsecapability && $choicegroups->deleterepsonsecapability) {
            $selecturl = new moodle_url('#');

            $selectallactions = new component_action('click',"checkall");
            $selectall = new action_link($selecturl, get_string('selectall'), $selectallactions);
            $actiondata .= $this->output->render($selectall) . ' / ';

            $deselectallactions = new component_action('click',"checknone");
            $deselectall = new action_link($selecturl, get_string('deselectall'), $deselectallactions);
            $actiondata .= $this->output->render($deselectall);

            $actiondata .= html_writer::tag('label', ' ' . get_string('withselected', 'choice') . ' ', array('for'=>'menuaction'));

            $actionurl = new moodle_url($PAGE->url, array('sesskey'=>sesskey(), 'action'=>'delete_confirmation()'));
            $select = new single_select($actionurl, 'action', array('delete'=>get_string('delete')), null, array(''=>get_string('chooseaction', 'choicegroup')), 'attemptsform');

            $actiondata .= $this->output->render($select);
        }
        $html .= html_writer::tag('div', $actiondata, array('class'=>'responseaction'));

        if ($choicegroups->viewresponsecapability) {
            $html .= html_writer::end_tag('form');
        }

        return $html;
    }


    /**
     * Returns HTML to display choicegroups result
     * @param object $choicegroups
     * @return string
     */
    public function display_publish_anonymous_vertical($choicegroups) {
        global $CHOICEGROUP_COLUMN_HEIGHT;

        $html = '';
        $table = new html_table();
        $table->cellpadding = 5;
        $table->cellspacing = 0;
        $table->attributes['class'] = 'results anonymous ';
        $table->data = array();
        $count = 0;
        ksort($choicegroups->options);
        $columns = array();
        $rows = array();

        foreach ($choicegroups->options as $optionid => $options) {
            $numberofuser = 0;
            if (!empty($options->user)) {
               $numberofuser = count($options->user);
            }
            $height = 0;
            $percentageamount = 0;
            if($choicegroups->numberofuser > 0) {
               $height = ($CHOICEGROUP_COLUMN_HEIGHT * ((float)$numberofuser / (float)$choicegroups->numberofuser));
               $percentageamount = ((float)$numberofuser/(float)$choicegroups->numberofuser)*100.0;
            }

            $displaydiagram = html_writer::tag('img','', array('style'=>'height:'.$height.'px;width:49px;', 'alt'=>'', 'src'=>$this->output->pix_url('column', 'choicegroup')));

            $cell = new html_table_cell();
            $cell->text = $displaydiagram;
            $cell->attributes = array('class'=>'graph vertical data');
            $columns[] = $cell;
        }
        $rowgraph = new html_table_row();
        $rowgraph->cells = $columns;
        $rows[] = $rowgraph;

        $columns = array();
        $printskiplink = true;
        foreach ($choicegroups->options as $optionid => $options) {
            $columndata = '';
            $numberofuser = 0;
            if (!empty($options->user)) {
               $numberofuser = count($options->user);
            }

            if ($printskiplink) {
                $columndata .= html_writer::tag('div', '', array('class'=>'skip-block-to', 'id'=>'skipresultgraph'));
                $printskiplink = false;
            }

            if ($choicegroups->showunanswered && $optionid == 0) {
                $columndata .= html_writer::tag('div', format_string(get_string('notanswered', 'choicegroup')), array('class'=>'option'));
            } else if ($optionid > 0) {
                $columndata .= html_writer::tag('div', format_string($choicegroups->options[$optionid]->text), array('class'=>'option'));
            }
            $columndata .= html_writer::tag('div', ' ('.$numberofuser.')', array('class'=>'numberofuser', 'title'=> get_string('numberofuser', 'choicegroup')));

            if($choicegroups->numberofuser > 0) {
               $percentageamount = ((float)$numberofuser/(float)$choicegroups->numberofuser)*100.0;
            }
            $columndata .= html_writer::tag('div', format_float($percentageamount,1). '%', array('class'=>'percentage'));

            $cell = new html_table_cell();
            $cell->text = $columndata;
            $cell->attributes = array('class'=>'data header');
            $columns[] = $cell;
        }
        $rowdata = new html_table_row();
        $rowdata->cells = $columns;
        $rows[] = $rowdata;

        $table->data = $rows;

        $header = html_writer::tag('h2',format_string(get_string("responses", "choicegroup")));
        $html .= html_writer::tag('div', $header, array('class'=>'responseheader'));
        $html .= html_writer::tag('a', get_string('skipresultgraph', 'choicegroup'), array('href'=>'#skipresultgraph', 'class'=>'skip-block'));
        $html .= html_writer::tag('div', html_writer::table($table), array('class'=>'response'));

        return $html;
    }

}

