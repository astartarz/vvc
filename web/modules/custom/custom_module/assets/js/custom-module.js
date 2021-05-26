(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   *
   * @type {{attach: Drupal.behaviors.CustomModuleScript.attach}}
   */
  Drupal.behaviors.CustomModuleScript = {
    attach: function (context, settings) {

      console.log(drupalSettings);

    }
  };

})(jQuery, Drupal, drupalSettings);
