<?php
defined('WP_UNINSTALL_PLUGIN') || exit;

/*
 * Absichtlich keine Datenlöschung:
 * Benutzer-Metadaten, Unternehmensnews und Einstellungen bleiben erhalten,
 * damit eine Deinstallation nicht versehentlich Firmendaten vernichtet.
 */
