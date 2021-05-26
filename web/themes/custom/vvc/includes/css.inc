<?php

/**
 * Implements hook_css_alter().
 */
function vvc_css_alter(&$css) {
  // Use <link> as opposed to <style> @import during development if
  // css preprocessing is turned off. This will allow tools like
  // BrowserSync and LiveReload to work properly.

}
