<?php
$plugin->component = 'block_peerblock';  // Full name of the block (used for diagnostics)
$plugin->version = 2021031701;  // The current block version (Date: YYYYMMDDXX)
$plugin->requires = 2020110300; // Requires this Moodle version

$plugin->dependencies = array(
        'mod_peerforum' => 2021031701,
);
