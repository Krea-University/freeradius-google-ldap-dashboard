<?php
/**
 * Security: Prevent directory listing
 *
 * This file prevents direct access to the auth views directory.
 * All views should be accessed through controllers only.
 */

// Redirect to home page
header('Location: /');
exit;
