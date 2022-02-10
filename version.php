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
 * Version info
 *
 * This File contains information about the current version of report/componentgrades
 *
 * @package    report_csvcomponentgrades
 * @copyright  2021 Dianne Dhanassar
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$plugin->component = 'report_csvcomponentgrades';  // Full name of the plugin (used for diagnostics).
$plugin->version   = 2021100100;  // The current plugin version (Date: YYYYMMDDXX).
$plugin->requires  = 2015051109;  
$plugin->supported = [37, 39]; // Moodle 3.7 and 3.9
$plugin->maturity  = MATURITY_STABLE;
$plugin->release   = '1.0';

/*
$plugin->dependencies = [
    'mod_forum' => ANY_VERSION,
    'mod_data' => TODO
];
*/