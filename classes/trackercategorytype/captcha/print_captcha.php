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
 * @package mod_tracker
 * @category mod
 * @author Valery Fremaux
 *
 * A class implementing a hidden/labelled element that captures the referer url
 */
require_once('../../../../../config.php');
require_once($CFG->dirroot.'/mod/tracker/locallib.php');

$id = optional_param('id', '', PARAM_INT); // Id of the tracker module id.
$t = optional_param('t', '', PARAM_INT); // Id of the tracker instance.

list($cm, $tracker, $course) = tracker_get_context($id, $t);

if (!isset($SESSION->tracker[$tracker->id]->captcha->length)) {
    print_error('not allowed');
}

$height = 40;
$charcount = $SESSION->tracker[$tracker->id]->captcha->length;
$fontfile = $CFG->libdir.'/default.ttf';

$ttfbox = imagettfbbox( 30, 0, $fontfile, 'H' ); // The text to measure.
$charwidth = $ttfbox[2];

$width = $charcount * $charwidth;

$scale = 0.3;
$elipsesize = intval((($width + $height) / 2) / 5);
$factorx = intval($width * $scale);
$factory = intval($height * $scale);

/*
 * I split the colors in three ranges
 * given are the max-min-values
 * $colors = array(80, 155, 255);
 */
$colors = array(array(0, 40), array(50, 200), array(210, 255));
list($coltext1, $colel, $coltext2) = $colors;

/*
 * if the text is in color_1 so the elipses can be in color_2 or color_3
 * if the text is in color_2 so the elipses can be in color_1 or color_3
 * and so on.
 */
$textcolnum = rand(1, 3);

// Create the numbers to print out.
$nums = array();
for ($i = 0; $i < $charcount; $i++) {
    $nums[] = rand(0, 9); // Randomizes some digits.
}

/*
 * To draw enough elipses so I draw 0.2 * width and 0.2 * height
 * we need th colors for that
 */
$properties = array();
for ($x = 0; $x < $factorx; $x++) {
    for ($y = 0; $y < $factory; $y++) {
        $propobj = new StdClass;
        $propobj->x = intval($x / $scale);
        $propobj->y = intval($y / $scale);
        $propobj->red = get_random_color($colel[0], $colel[1]);
        $propobj->green = get_random_color($colel[0], $colel[1]);
        $propobj->blue = get_random_color($colel[0], $colel[1]);
        $properties[] = $propobj;
    }
}
shuffle($properties);

// Create a blank image.
$image = imagecreatetruecolor($width, $height);
$bg = imagecolorallocate($image, 0, 0, 0);
for ($i = 0; $i < ($factorx * $factory); $i++) {
    $propobj = $properties[$i];
    // Choose a color for the ellipse.
    $colellipse = imagecolorallocate($image, $propobj->red, $propobj->green, $propobj->blue);
    // Draw the white ellipse.
    imagefilledellipse($image, $propobj->x, $propobj->y, $elipsesize, $elipsesize, $colellipse);
}

$checkchar = '';
for ($i = 0; $i < $charcount; $i++) {
    $colnum = rand(1, 2);
    $textcol = new StdClass;
    $textcol->red = get_random_color(${'coltext'.$colnum}[0], ${'coltext'.$colnum}[1]);
    $textcol->green = get_random_color(${'coltext'.$colnum}[0], ${'coltext'.$colnum}[1]);
    $textcol->blue = get_random_color(${'coltext'.$colnum}[0], ${'coltext'.$colnum}[1]);
    $colortext = imagecolorallocate($image, $textcol->red, $textcol->green, $textcol->blue);
    $angletext = rand(-20, 20);
    $lefttext = $i * $charwidth;
    $text = $nums[$i];
    $checkchar .= $text;
    imagettftext($image, 30, $angletext, $lefttext, 35, $colortext, $fontfile, $text);
}

$SESSION->tracker[$tracker->id]->captcha->checkchar = $checkchar;

// Output the picture.
header("Content-type: image/png");
imagepng($image);

function get_random_color($val1 = 0, $val2 = 255) {
    $min = $val1 < $val2 ? $val1 : $val2;
    $max = $val1 > $val2 ? $val1 : $val2;

    return rand($min, $max);
}
