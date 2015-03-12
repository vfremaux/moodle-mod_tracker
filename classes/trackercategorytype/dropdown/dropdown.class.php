<?php

/**
* @package tracker
* @author Clifford Tham
* @review Valery Fremaux / 1.8
* @date 02/12/2007
*
* A class implementing a dropdown element
*/

require_once $CFG->dirroot.'/mod/tracker/classes/trackercategorytype/trackerelement.class.php';

class dropdownelement extends trackerelement {

    var $options;
    var $multiple;

    // Alex                                   vvvv          vvvvv
    function dropdownelement(&$tracker, $id = NULL, $used = FALSE ) {
        parent::__construct($tracker, $id, $used);
        $this->setoptionsfromdb();
    }

    function view($issueid = 0) {
        $this->getvalue($issueid); // loads $this->value with current value for this issue
        if (isset($this->options)) {
            $optionstrs = array();
            foreach ($this->options as $option) {
                if ($this->value != null) {
                    if ($this->value == $option->id) {
                        $optionstrs[] = format_string($option->description);
                    }
                }
            }
            echo implode(', ', $optionstrs);
        }
    }

    // function edit($issueid = 0) {
    //     $this->getvalue($issueid); // loads $this->value with current value for this issue
    //     if (isset($this->options)) {
    //         $optionstrs = array();
    //         foreach ($this->options as $option) {
    //             if ($this->value != null) {
    //                 if ($this->value == $option->id) {
    //                     $optionstrs[] = format_string($option->description);
    //                 }
    //             }
    //         }
    //         echo implode(', ', $optionstrs);
    //     }
    // }

    function edit($issueid = 0) {
        $this->getvalue($issueid);
        $elmname = 'element'.$this->name;
        // Alex   vvvvvvv
        $values = explode(',', $this->value); // whatever the form ... revert to an array.
        if (isset($this->options)) {
            // Alex . comments x2  vvv     vvvv
            // $optionsstrs = array();
            // echo html_writer::empty_tag('imput', array('type' => 'checkbox', 'id' => 'element'.$elmname.$option->id, 'value' => 1, 'checked' => 'checked'));
            $options = "";
            foreach ($this->options as $option) {
                if (is_array($values) AND in_array($option->id, $values)) {
                    $options .= html_writer::tag('option', $option->description, array('type' => 'checkbox', 'id' => $elmname.$option->id, 'value' => $option->id, 'selected' => 'selected'));
                } else {
                    $options .= html_writer::tag('option', $option->description, array('type' => 'checkbox', 'id' => $elmname.$option->id, 'value' => $option->id));
                }
                // echo format_string($option->description);
                // echo html_writer::empty_tag('br');
            }
            echo html_writer::tag( 'select', $options, array( 'id' => $elmname, 'name' => $elmname ) );
        }
    }

    // function edit($issueid = 0) {
    //     $this->getvalue($issueid);
    //     $elmname = 'element'.$this->name;
    //     // Alex   vvvvvvv
    //     $values = explode(',', $this->value); // whatever the form ... revert to an array.
    //     if (isset($this->options)) {
    //         // Alex . comments x2  vvv     vvvv
    //         // $optionsstrs = array();
    //         // echo html_writer::empty_tag('imput', array('type' => 'checkbox', 'id' => 'element'.$elmname.$option->id, 'value' => 1, 'checked' => 'checked'));
    //         foreach ($this->options as $option) {
    //             if (is_array($values) AND in_array($option->id, $values)) {
    //                 echo html_writer::empty_tag('input', array('type' => 'checkbox', 'id' => 'element'.$elmname.$option->id, 'value' => 1, 'checked' => 'checked'));
    //             } else {
    //                 echo html_writer::empty_tag('input', array('type' => 'checkbox', 'id' => 'element'.$elmname.$option->id, 'value' => 1));
    //             }
    //             echo format_string($option->description);
    //             echo html_writer::empty_tag('br');
    //         }
    //     }
    // }

    function add_form_element(&$form) {

        if (isset($this->options)) {
            foreach ($this->options as $option) {
                $optionsmenu[$option->id] = format_string($option->description);
            }
            $form->addElement('select', "element$this->name", format_string($this->description), $optionsmenu);
        }
    }

    function set_data(&$defaults, $issueid = 0) {
        if ($issueid) {

            $elementname = "element{$this->name}";

            if (!empty($this->options)) {
                $values = $this->getvalue($issueid);
                if ($multiple && is_array($values)) {
                    foreach ($values as $v) {
                        if (array_key_exists($v, $this->options)) { // check option still exists
                            $choice[] = $v;
                        }
                        if (!empty($choice)) {
                            $defaults->$elementname = $choice;
                        }
                    }
                } else {
                    $v = $values; // single value
                    if (array_key_exists($v, $this->options)) { // check option still exists
                        $defaults->$elementname = $v;
                    }
                }
            }
        }
    }

    function formprocess(&$data) {
        global $DB;

        $sqlparams = array('elementid' => $this->id, 'trackerid' => $data->trackerid, 'issueid' => $data->issueid);
        if (!$attribute = $DB->get_record('tracker_issueattribute', $sqlparams)) {
            $attribute = new StdClass();
            $attribute->trackerid = $data->trackerid;
            $attribute->issueid = $data->issueid;
            $attribute->elementid = $this->id;
        }

        $elmname = 'element'.$this->name;
        $elementitemid = optional_param( $elmname, 0, PARAM_INT);
        $attribute->elementitemid = $elementitemid;

        // if (!$this->multiple) {
        //     $attribute->elementitemid = $elementitemid;
        // } else {
        //     if (is_array($data->$elmname)) {
        //         $attribute->elementitemid = implode(',', $data->$elmname);
        //     } else {
        //         $attribute->elementitemid = $data->$elmname;
        //     }
        // }

        $attribute->timemodified = time();

        if (!isset($attribute->id)) {
            $attribute->id = $DB->insert_record('tracker_issueattribute', $attribute);
            if (empty($attribute->id)) {
                print_error('erroraddissueattribute', 'tracker', '', 2);
            }
        } else {
            $DB->update_record('tracker_issueattribute', $attribute);
        }
    }
}

