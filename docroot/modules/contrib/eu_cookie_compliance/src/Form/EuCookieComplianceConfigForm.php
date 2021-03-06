<?php

namespace Drupal\eu_cookie_compliance\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Url;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Routing\RequestContext;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\user\RoleStorageInterface;
use Drupal\filter\Entity\FilterFormat;
use Drupal\Core\Cache\Cache;

/**
 * Provides settings for eu_cookie_compliance module.
 */
class EuCookieComplianceConfigForm extends ConfigFormBase {

  /**
   * The path validator.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * The request context.
   *
   * @var \Drupal\Core\Routing\RequestContext
   */
  protected $requestContext;

  /**
   * The role storage.
   *
   * @var \Drupal\user\RoleStorageInterface
   */
  protected $roleStorage;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs an EuCookieComplianceConfigForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   *   The path validator.
   * @param \Drupal\Core\Routing\RequestContext $request_context
   *   The request context.
   * @param \Drupal\user\RoleStorageInterface $role_storage
   *   The role storage.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ConfigFactoryInterface $config_factory, PathValidatorInterface $path_validator, RequestContext $request_context, RoleStorageInterface $role_storage, ModuleHandlerInterface $module_handler) {
    parent::__construct($config_factory);

    $this->pathValidator = $path_validator;
    $this->requestContext = $request_context;
    $this->roleStorage = $role_storage;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('path.validator'),
      $container->get('router.request_context'),
      $container->get('entity_type.manager')->getStorage('user_role'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'eu_cookie_compliance_config_form';
  }

  /**
   * Gets the roles to display in this form.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   An array of role objects.
   */
  protected function getRoles() {
    return $this->roleStorage->loadMultiple();
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'eu_cookie_compliance.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('eu_cookie_compliance.settings');

    $default_filter_format = filter_default_format();
    $full_html_format = FilterFormat::load('full_html');
    if ($default_filter_format == 'restricted_html' && !empty($full_html_format) && $full_html_format->get('status')) {
      $default_filter_format = 'full_html';
    }

    $consent_storages = \Drupal::service('plugin.manager.eu_cookie_compliance.consent_storage');
    $plugin_definitions = $consent_storages->getDefinitions();

    $consent_storage_options = [];
    $consent_storage_options['do_not_store'] = $this->t('Do not store');
    foreach ($plugin_definitions as $plugin_name => $plugin_definition) {
      /* @var \Drupal\Core\StringTranslation\TranslatableMarkup $plugin_definition_name */
      $plugin_definition_name = $plugin_definition['name'];
      $consent_storage_options[$plugin_name] = $plugin_definition_name->render();
    }

    $form['popup_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable banner'),
      '#default_value' => $config->get('popup_enabled'),
    ];

    // List of checkbox values.
    $role_names = [];
    // Permissions per role.
    $role_permissions = [];
    // Which checkboxes should be ticked.
    $role_values = [];

    $perm = 'display eu cookie compliance popup';

    foreach ($this->getRoles() as $role_name => $role) {
      // Exclude Admin roles.
      /* @var \Drupal\user\Entity\Role $role */
      if (!$role->isAdmin()) {
        $role_names[$role_name] = $role->label();
        // Fetch permissions for the roles.
        $role_permissions[$role_name] = $role->getPermissions();
        // Indicate whether the checkbox should be ticked.
        if (in_array($perm, $role_permissions[$role_name])) {
          $role_values[] = $role_name;
        }
      }
    }

    $form['permissions'] = [
      '#type' => 'details',
      '#title' => $this->t('Permissions'),
      '#open' => TRUE,
    ];

    $form['permissions']['see_the_banner'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Display the banner for'),
      '#options' => $role_names,
      '#default_value' => $role_values,
    ];

    $form['consent_option'] = [
      '#type' => 'details',
      '#title' => $this->t('Consent for processing of personal information'),
      '#open' => TRUE,
    ];

    $form['consent_option']['info'] = [
      '#type' => 'markup',
      '#markup' => $this->t("The EU General Data Protection Regulation (GDPR) (see <a href=\"https://www.eugdpr.org/\" target=\"_blank\">https://www.eugdpr.org/</a>) comes into enforcement from 25 May 2018 and introduces new requirements for web sites which handle information that can be used to identify individuals. The regulation underlines that consent must be <strong>unambiguous</strong> and involve a <strong>clear affirmative action</strong>. When evaluating how to best handle the requirements in the GDPR, remember that if you have a basic web site where the visitors don't log in, you always have the option to <strong>not process data that identifies individuals</strong>, in which case you may not need this module. Also note that GDPR applies to any electronic processing or storage of personal data that your organization may do, and simply installing a module may not be enough to become fully GDPR compliant."),
    ];

    $form['consent_option']['method'] = [
      '#type' => 'radios',
      '#title' => $this->t('Consent method'),
      '#options' => [
        'default' => $this->t("Consent by default. Don't provide any option to opt out."),
        'opt_in' => $this->t("Opt-in. Don't track visitors unless they specifically give consent. (GDPR compliant)"),
        'categories' => $this->t('Opt-in with categories. Let visitors choose which cookie categories they want to opt-in for (GDPR compliant).'),
        'opt_out' => $this->t('Opt-out. Track visitors by default, unless they choose to opt out.'),
        'auto' => $this->t('Automatic. Respect the DNT (Do not track) setting in the browser, if present. Uses opt-in when DNT is 1 or not set, and consent by default when DNT is 0.'),
      ],
      '#default_value' => $config->get('method'),
    ];
    $form['consent_per_category'] = [
      '#type' => 'details',
      '#title' => $this->t('Cookie categories'),
      '#open' => TRUE,
      '#states' => [
        'visible' => [
          "input[name='method']" => ['value' => 'categories'],
        ],
      ],
    ];
    $form['consent_per_category']['cookie_categories'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Cookie categories with separate consent'),
      '#description' => $this->t('List of cookie categories that require separate consent. E.g. Functional cookies, Advertisement cookies, ???') .
      '<br />' . $this->t('Enter one value per line, in the following format: "key|label" or "key|label|description". Description is optional.'),
      '#default_value' => $config->get('cookie_categories'),
    ];

    $form['consent_per_category']['enable_save_preferences_button'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Replace the Agree button with "Save preferences" and "Accept all categories" buttons.'),
      '#default_value' => $config->get('enable_save_preferences_button'),
    ];

    $form['consent_per_category']['save_preferences_button_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('"Save preferences" button label'),
      '#default_value' => $config->get('save_preferences_button_label'),
      '#states' => [
        'visible' => [
          "input[name='enable_save_preferences_button']" => ['checked' => TRUE],
        ],
        'required' => [
          "input[name='enable_save_preferences_button']" => ['checked' => TRUE],
        ],
      ],
    ];

    $form['consent_per_category']['accept_all_categories_button_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('"Accept all categories" button label'),
      '#default_value' => $config->get('accept_all_categories_button_label'),
      '#states' => [
        'visible' => [
          "input[name='enable_save_preferences_button']" => ['checked' => TRUE],
        ],
        'required' => [
          "input[name='enable_save_preferences_button']" => ['checked' => TRUE],
        ],
      ],
    ];

    $form['consent_per_category']['fix_first_cookie_category'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Tick the first checkbox and mark it read only.'),
      '#default_value' => $config->get('fix_first_cookie_category'),
    ];

    $form['consent_per_category']['select_all_categories_by_default'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Tick all category checkboxes by default.'),
      '#default_value' => $config->get('select_all_categories_by_default'),
    ];

    $form['popup_info_template'] = [
      '#type' => 'details',
      '#title' => 'Select the popup info template for \'default by consent\' option',
      '#open' => TRUE,
      '#states' => [
        'visible' => [
          "input[name='method']" => ['value' => 'default'],
        ],
      ],
    ];

    $form['popup_info_template']['popup_info_template'] = [
      '#type' => 'radios',
      '#title' => $this->t('Info banner template'),
      '#options' => [
        'legacy' => t('Cookie policy button in popup-buttons section and styled similarly to the Agree button,
        as in earlier versions of this module'),
        'new' => t('Cookie policy button in popup-text section, styled differently than Agree button.'),
      ],
      '#default_value' => $config->get('popup_info_template'),
    ];

    $form['javascripts'] = [
      '#type' => 'details',
      '#title' => $this->t("Disable the following JavaScripts when consent isn't given"),
      '#open' => TRUE,
      '#states' => [
        'visible' => [
          "input[name='method']" => ['!value' => 'default'],
        ],
      ],
    ];

    $form['javascripts']['disabled_javascripts'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Disable JavaScripts'),
      '#default_value' => $config->get('disabled_javascripts'),
      '#description' => $this->t("<span class='eu-cookie-compliance-word-break'>Include the full path of JavaScripts, each on a separate line. When using the opt-in or opt-out consent options, you can block certain JavaScript files from being loaded when consent isn't given. The on-site JavaScripts should be written as root relative paths <strong>without the leading slash</strong>, you can use public://path/to/file.js and private://path/to/file.js, and off-site JavaScripts should be written as complete URLs <strong>with the leading http(s)://</strong>. Note that after the user gives consent, the scripts will be executed in the order you enter here.<br /><br />Libraries and scripts that attach to Drupal.behaviors are supported. To indicate a behavior that needs to be loaded on consent, append the behavior name after the script with a | (vertical bar). If you also want to conditionally load a library, place that as the third parameter, following another | (vertical bar). <strong>Example: modules/custom/custom_module/js/custom.js|customModule|custom_module/custom_module</strong>.<br />If your script file does not attach to Drupal.attributes, you may skip the second parameter. <strong>Example: modules/custom/custom_module/js/custom.js||custom_module/custom_module</strong><br /><strong>Note that Drupal behavior name and library parameters are both optional</strong>, but may be required to achieve your objective.</span>") .
      '<br /><br />' . $this->t('When using the consent method "Opt-in with categories", you can link the script to a specific category by using the format: "category:path/to/the/script.js".'),
    ];

    $form['cookies'] = [
      '#type' => 'details',
      '#title' => $this->t('Cookie handling'),
      '#open' => TRUE,
      '#states' => [
        'visible' => [
          "input[name='method']" => ['!value' => 'default'],
        ],
      ],
    ];

    $form['cookies']['whitelisted_cookies'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Whitelisted cookies'),
      '#default_value' => $config->get('whitelisted_cookies'),
      '#description' => $this->t("Include the name of cookies, each on a separate line. When using the opt-in or opt-out consent options, this module will <strong>delete cookies from your domain that are not on the whitelist</strong> every few seconds when consent isn't given. PHP session cookies and the cookie for this module are always whitelisted.") .
      '<br /><br />' . t('When using the consent method "Opt-in with categories", you can link the cookie to a specific consent category by using the format: "category:cookie_name".  Only when consent is given for the given category, will the cookie be whitelisted.'),
    ];

    $form['consent_storage'] = [
      '#type' => 'details',
      '#title' => $this->t('Store record of consent'),
      '#open' => TRUE,
      '#states' => [
        'visible' => [
          "input[name='method']" => ['!value' => 'default'],
        ],
      ],
    ];

    $form['consent_storage']['info'] = [
      '#type' => 'markup',
      '#markup' => $this->t('Depending on your implementation of GDPR, you may have to store a record when the user consents. This module comes with a basic consent storage plugin that writes a record to the database. Note that if your site has significant traffic, the basic consent storage may become a bottleneck, as every consent action will require a write to the database. You can easily create your own module with a ConsentStorage Plugin that extends ConsentStorageBase, using BasicConsentStorage from this module as a template. If you create a highly performant consent storage plugin, please consider contributing it back to the Drupal community as a contrib module.'),
    ];

    $form['consent_storage']['consent_storage_method'] = [
      '#type' => 'radios',
      '#title' => $this->t('Consent storage method'),
      '#default_value' => $config->get('consent_storage_method'),
      '#options' => $consent_storage_options,
    ];

    $form['popup_message'] = [
      '#type' => 'details',
      '#title' => $this->t('Cookie information banner'),
      '#open' => TRUE,
    ];

    $form['popup_message']['popup_clicking_confirmation'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Consent by clicking'),
      '#default_value' => $config->get('popup_clicking_confirmation'),
      '#description' => $this->t('By default by clicking any link or button on the website the visitor accepts the cookie policy. Uncheck this box if you don???t require this functionality. You may want to edit the banner message below accordingly.'),
      '#states' => [
        'visible' => [
          'input[name="method"]' => ['value' => 'default'],
        ],
      ],
    ];

    $config_format = $config->get('popup_info.format');
    if (!empty($config_format)) {
      $filter_format = FilterFormat::load($config_format);
      if (empty($filter_format) || !$filter_format->get('status')) {
        $config_format = $default_filter_format;
      }
    }

    $form['popup_message']['popup_info'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Cookie information banner message'),
      '#default_value' => $config->get('popup_info.value'),
      '#required' => TRUE,
      '#format' => $config_format,
    ];

    $form['popup_message']['use_mobile_message'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use a different message for mobile phones.'),
      '#default_value' => !empty($config->get('use_mobile_message')) ? $config->get('use_mobile_message') : FALSE,
    ];

    $form['popup_message']['container'] = [
      '#type' => 'container',
      '#states' => ['visible' => ['input[name="use_mobile_message"]' => ['checked' => TRUE]]],
    ];

    $config_format = $config->get('mobile_popup_info.format');
    if (!empty($config_format)) {
      $filter_format = FilterFormat::load($config_format);
      if (empty($filter_format) || !$filter_format->get('status')) {
        $config_format = $default_filter_format;
      }
    }

    $form['popup_message']['container']['mobile_popup_info'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Cookie information banner message - mobile'),
      '#default_value' => $config->get('mobile_popup_info.value'),
      '#required' => FALSE,
      '#format' => $config_format,
    ];

    $form['popup_message']['mobile_breakpoint'] = [
      '#type' => 'number',
      '#title' => $this->t('Mobile breakpoint'),
      '#default_value' => !empty($config->get('mobile_breakpoint')) ? $config->get('mobile_breakpoint') : '768',
      '#field_suffix' => $this->t('px'),
      '#size' => 4,
      '#maxlength' => 4,
      '#required' => FALSE,
      '#description' => $this->t('The mobile message will be used when the window width is below or equal to the given value.'),
      '#states' => [
        'visible' => [
          "input[name='use_mobile_message']" => ['checked' => TRUE],
        ],
      ],
    ];

    $form['popup_message']['popup_agree_button_message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Agree button label'),
      '#default_value' => $config->get('popup_agree_button_message'),
      '#size' => 30,
      '#states' => [
        'visible' => [
          [
            "input[name='enable_save_preferences_button']" => ['checked' => FALSE],
            "input[name='method']" => ['value' => 'categories'],
          ],
          [
            "input[name='method']" => ['!value' => 'categories'],
          ],
        ],
        'required' => [
          [
            "input[name='enable_save_preferences_button']" => ['checked' => FALSE],
            "input[name='method']" => ['value' => 'categories'],
          ],
          [
            "input[name='method']" => ['!value' => 'categories'],
          ],
        ],
      ],
    ];

    $form['popup_message']['disagree_button'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show "Cookie Policy" and "More info" buttons'),
      '#description' => $this->t('If this option is checked, the cookie policy button will be shown on the site. Disabling this option will hide both the "Cookie Policy" button on the information banner and the "More info" button on the "Thank you" banner.'),
      '#default_value' => $config->get('show_disagree_button'),
      '#states' => [
        'visible' => [
          "input[name='method']" => ['value' => 'default'],
        ],
      ],
    ];

    $form['popup_message']['popup_disagree_button_message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cookie policy button label'),
      '#default_value' => $config->get('popup_disagree_button_message'),
      '#size' => 30,
      '#states' => [
        'visible' => [
          ['input[name="disagree_button"]' => ['checked' => TRUE]],
          ['input[name="method"]' => ['!value' => 'default']],
        ],
        'required' => [
          ['input[name="disagree_button"]' => ['checked' => TRUE]],
          ['input[name="method"]' => ['!value' => 'default']],
        ],
      ],
    ];

    $form['popup_message']['disagree_button_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Disagree button label'),
      '#default_value' => $config->get('disagree_button_label'),
      '#size' => 30,
      '#states' => [
        'visible' => [
          'input[name="method"]' => ['!value' => 'default'],
        ],
        'required' => [
          'input[name="method"]' => ['!value' => 'default'],
        ],
      ],
    ];

    $form['withdraw_consent'] = [
      '#type' => 'details',
      '#title' => $this->t('Withdraw consent'),
      '#open' => TRUE,
      '#states' => [
        'visible' => [
          "input[name='method']" => ['!value' => 'default'],
        ],
      ],
    ];

    $form['withdraw_consent']['info'] = [
      '#type' => 'markup',
      '#markup' => t('GDPR requires that withdrawing consent for handling personal information should be as easy as giving consent. This module offers a tab button that when clicked brings up a message and a button that can be used to withdraw consent.'),
    ];

    $form['withdraw_consent']['withdraw_enabled'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable floating privacy settings tab and withdraw consent banner'),
      '#default_value' => $config->get('withdraw_enabled'),
    ];

    $form['withdraw_consent']['withdraw_button_on_info_popup'] = [
      '#type' => 'checkbox',
      '#title' => t('Put the "Withdraw consent" button on the cookie information banner.'),
      '#default_value' => $config->get('withdraw_button_on_info_popup'),
    ];

    $config_format = $config->get('popup_info.format');
    if (!empty($config_format)) {
      $filter_format = FilterFormat::load($config_format);
      if (empty($filter_format) || !$filter_format->get('status')) {
        $config_format = $default_filter_format;
      }
    }

    $form['withdraw_consent']['withdraw_message'] = [
      '#type' => 'text_format',
      '#title' => t('Withdraw consent banner message'),
      '#default_value' => isset($config->get('withdraw_message')['value']) ? $config->get('withdraw_message')['value'] : '',
      '#description' => t('Text that will be displayed in the banner that appears when the privacy settings tab is clicked.'),
      '#format' => $config_format,
      '#states' => [
        'visible' => [
          "input[name='withdraw_button_on_info_popup']" => ['checked' => FALSE],
        ],
      ],
    ];

    $form['withdraw_consent']['withdraw_tab_button_label'] = [
      '#type' => 'textfield',
      '#title' => t('Privacy settings tab label'),
      '#default_value' => $config->get('withdraw_tab_button_label'),
      '#description' => t('Tab button that reveals/hides the withdraw message and action button when clicked.'),
    ];

    $form['withdraw_consent']['withdraw_action_button_label'] = [
      '#type' => 'textfield',
      '#title' => t('Withdraw consent button label'),
      '#default_value' => $config->get('withdraw_action_button_label'),
      '#description' => t('This button will withdraw consent when clicked.'),
    ];

    $form['thank_you'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Thank you banner'),
    ];

    $form['thank_you']['popup_agreed_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable "Thank you" banner'),
      '#default_value' => $config->get('popup_agreed_enabled'),
    ];

    $form['thank_you']['popup_hide_agreed'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Clicking hides "Thank you" banner.'),
      '#default_value' => $config->get('popup_hide_agreed'),
      '#description' => $this->t('Clicking a link or button hides the "Thank you" message automatically.'),
    ];

    $config_format = $config->get('popup_info.format');
    if (!empty($config_format)) {
      $filter_format = FilterFormat::load($config_format);
      if (empty($filter_format) || !$filter_format->get('status')) {
        $config_format = $default_filter_format;
      }
    }

    $form['thank_you']['popup_agreed'] = [
      '#type' => 'text_format',
      '#title' => $this->t('"Thank you" banner message'),
      '#default_value' => !empty($config->get('popup_agreed')['value']) ? $config->get('popup_agreed')['value'] : '',
      '#required' => TRUE,
      '#format' => $config_format,
    ];

    $form['thank_you']['popup_find_more_button_message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('More info button label'),
      '#default_value' => $config->get('popup_find_more_button_message'),
      '#size' => 30,
      '#states' => [
        'visible' => [
          ['input[name="disagree_button"]' => ['checked' => TRUE]],
          ['input[name="method"]' => ['!value' => 'default']],
        ],
        'required' => [
          ['input[name="disagree_button"]' => ['checked' => TRUE]],
          ['input[name="method"]' => ['!value' => 'default']],
        ],
      ],
    ];

    $form['thank_you']['popup_hide_button_message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Hide button label'),
      '#default_value' => $config->get('popup_hide_button_message'),
      '#size' => 30,
      '#required' => TRUE,
    ];

    $form['privacy'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Privacy policy'),
    ];

    $form['privacy']['popup_link'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Privacy policy link'),
      '#default_value' => $config->get('popup_link'),
      '#maxlength' => 1024,
      '#required' => TRUE,
      '#description' => $this->t('Enter link to your privacy policy or other page that will explain cookies to your users, external links should start with http:// or https://.'),
      '#element_validate' => [[$this, 'validatePopupLink']],
    ];

    $form['privacy']['popup_link_new_window'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Open privacy policy link in a new window.'),
      '#default_value' => $config->get('popup_link_new_window'),
    ];

    $form['appearance'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Appearance'),
    ];

    $form_color_picker_type = 'textfield';

    if ($this->moduleHandler->moduleExists('jquery_colorpicker')) {
      $form_color_picker_type = 'jquery_colorpicker';
    }

    $popup_position_options = [
      'bottom' => 'Bottom',
      'top' => 'Top',
    ];

    $popup_position_value = ($config->get('popup_position') === TRUE ? 'top' : ($config->get('popup_position') === FALSE ? 'bottom' : $config->get('popup_position')));

    $form['appearance']['popup_position'] = [
      '#type' => 'radios',
      '#title' => $this->t('Position'),
      '#default_value' => $popup_position_value,
      '#options' => $popup_position_options,
    ];

    $form['appearance']['use_bare_css'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include minimal CSS, I want to style the banner in the theme CSS.'),
      '#default_value' => !empty($config->get('use_bare_css')) ? $config->get('use_bare_css') : 0,
      '#description' => $this->t('This may be useful if you want the banner to share the button style of your theme. Note that you will have to configure values like the banner width, text color and background color in your CSS file.'),
    ];

    $form['appearance']['popup_text_hex'] = [
      '#type' => $form_color_picker_type,
      '#title' => $this->t('Text color'),
      '#default_value' => $config->get('popup_text_hex'),
      '#description' => $this->t('Change the text color of the banner. Provide HEX value without the #.'),
      '#element_validate' => ['eu_cookie_compliance_validate_hex'],
      '#states' => [
        'visible' => [
          "input[name='use_bare_css']" => ['checked' => FALSE],
        ],
      ],
    ];

    $form['appearance']['popup_bg_hex'] = [
      '#type' => $form_color_picker_type,
      '#title' => $this->t('Background color'),
      '#default_value' => $config->get('popup_bg_hex'),
      '#description' => $this->t('Change the background color of the banner. Provide HEX value without the #.'),
      '#element_validate' => ['eu_cookie_compliance_validate_hex'],
      '#states' => [
        'visible' => [
          "input[name='use_bare_css']" => ['checked' => FALSE],
        ],
      ],
    ];

    $form['appearance']['popup_height'] = [
      '#type' => 'number',
      '#title' => $this->t('Banner height'),
      '#default_value' => !empty($config->get('popup_height')) ? $config->get('popup_height') : '',
      '#field_suffix' => $this->t('px'),
      '#size' => 5,
      '#maxlength' => 5,
      '#required' => FALSE,
      '#description' => $this->t('Enter an integer value for a desired height in pixels or leave empty for automatically adjusted height.'),
      '#states' => [
        'visible' => [
          "input[name='use_bare_css']" => ['checked' => FALSE],
        ],
      ],
    ];

    $form['appearance']['popup_width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Banner width in pixels or a percentage value'),
      '#default_value' => $config->get('popup_width'),
      '#field_suffix' => $this->t('px or %'),
      '#size' => 5,
      '#maxlength' => 5,
      '#description' => $this->t('Set the width of the banner. This can be either an integer value or percentage of the screen width. For example: 200 or 50%.'),
      '#states' => [
        'visible' => ["input[name='use_bare_css']" => ['checked' => FALSE]],
        'required' => ["input[name='use_bare_css']" => ['checked' => FALSE]],
      ],
    ];

    $form['eu_only'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => t('EU countries'),
    ];

    if ($this->moduleHandler->moduleExists('smart_ip') || $this->moduleHandler->moduleExists('geoip') || extension_loaded('geoip')) {
      $form['eu_only']['eu_only'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Only display banner in EU countries.'),
        '#default_value' => !empty($config->get('eu_only')) ? $config->get('eu_only') : 0,
        '#description' => $this->t('You can limit the number of countries for which the banner is displayed by checking this option. If you want to provide a list of countries other than current EU states, you may place an array in <code>$config[\'eu_cookie_compliance.settings\'][\'eu_countries\']</code> in your <code>settings.php</code> file. Using the <a href="http://drupal.org/project/smart_ip">smart_ip</a> module or using the <a href="http://drupal.org/project/geoip">geoip</a> module or the <a href="http://www.php.net/manual/en/function.geoip-country-code-by-name.php">geoip_country_code_by_name()</a> PHP function.'),
      ];
      $form['eu_only']['eu_only_js'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('JavaScript-based (for Varnish): Only display banner in EU countries.'),
        '#default_value' => !empty($config->get('eu_only_js')) ? $config->get('eu_only_js') : 0,
        '#description' => $this->t('This option also works for visitors that bypass Varnish. You can limit the number of countries for which the banner is displayed by checking this option. If you want to provide a list of countries other than current EU states, you may place an array in <code>$config[\'eu_cookie_compliance.settings\'][\'eu_countries\']</code> in your <code>settings.php</code> file. Using the <a href="http://drupal.org/project/smart_ip">smart_ip</a> module or the <a href="http://www.php.net/manual/en/function.geoip-country-code-by-name.php">geoip_country_code_by_name()</a> PHP function.'),
      ];
    }
    else {
      $form['eu_only']['info'] = [
        '#markup' => t('You can choose to show the banner only to visitors from EU countries. In order to achieve this, you need to install the <a href="http://drupal.org/project/smart_ip">smart_ip</a> module or install the <a href="http://drupal.org/project/geoip">geoip</a> module or enable the <a href="http://www.php.net/manual/en/function.geoip-country-code-by-name.php">geoip_country_code_by_name()</a> PHP function.'),
      ];
    }

    $form['advanced'] = [
      '#type' => 'details',
      '#open' => FALSE,
      '#title' => $this->t('Advanced'),
    ];

    $form['advanced']['fixed_top_position'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("If the banner is at the top, don't scroll the banner with the page."),
      '#default_value' => $config->get('fixed_top_position'),
      '#description' => $this->t('Use position:fixed for the banner when displayed at the top.'),
    ];

    $form['advanced']['popup_delay'] = [
      '#type' => 'number',
      '#title' => $this->t('Banner sliding animation time'),
      '#default_value' => $config->get('popup_delay'),
      '#field_suffix' => $this->t('ms'),
      '#size' => 5,
      '#maxlength' => 5,
      '#required' => TRUE,
    ];

    $form['advanced']['disagree_do_not_show_popup'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Do not show cookie policy when the user clicks the "Cookie Policy" button.'),
      '#default_value' => !empty($config->get('disagree_do_not_show_popup')) ? $config->get('disagree_do_not_show_popup') : 0,
      '#description' => $this->t('Enabling this will make it possible to record the fact that the user disagrees without the user having to see the privacy policy.'),
    ];

    $form['advanced']['reload_page'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Reload page after user clicks the "Agree" button.'),
      '#default_value' => !empty($config->get('reload_page')) ? $config->get('reload_page') : 0,
    ];

    $form['advanced']['popup_scrolling_confirmation'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Consent by scrolling'),
      '#default_value' => $config->get('popup_scrolling_confirmation'),
      '#description' => $this->t('Scrolling makes the visitors to accept the cookie policy. In some countries, like Italy, it is permitted.'),
      '#states' => [
        'visible' => [
          ['input[name="method"]' => ['value' => 'default']],
        ],
      ],
    ];

    $form['advanced']['cookie_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cookie name'),
      '#default_value' => !empty($config->get('cookie_name')) ? $config->get('cookie_name') : '',
      '#description' => $this->t('Sets the cookie name that is used to check whether the user has agreed or not. This option is useful when policies change and the user needs to agree again.'),
    ];

    // Adding option to add/remove banner on specified domains.
    $exclude_domains_option_active = [
      0 => $this->t('Add'),
      1 => $this->t('Remove'),
    ];

    $form['advanced']['domains_option'] = [
      '#type' => 'radios',
      '#title' => $this->t('Add/remove banner on specified domains'),
      '#default_value' => $config->get('domains_option'),
      '#options' => $exclude_domains_option_active,
      '#description' => $this->t('Specify if you want to add or remove banner on the listed below domains.'),
    ];

    $form['advanced']['domains_list'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Domains list'),
      '#default_value' => $config->get('domains_list'),
      '#description' => $this->t('Specify domains with protocol (e.g., http or https). Enter one domain per line.'),
    ];

    $form['advanced']['exclude_paths'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Exclude paths'),
      '#default_value' => !empty($config->get('exclude_paths')) ? $config->get('exclude_paths') : '',
      '#description' => $this->t("Specify pages by using their paths. Enter one path per line. The '*' character is a wildcard. Example paths are %blog for the blog page and %blog-wildcard for every personal blog. %front is the front page.", [
        '%blog' => '/blog',
        '%blog-wildcard' => '/blog/*',
        '%front' => '<front>',
      ]),
    ];

    $form['advanced']['exclude_admin_theme'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Exclude admin pages.'),
      '#default_value' => $config->get('exclude_admin_theme'),
    ];

    $form['advanced']['exclude_uid_1'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Don't show the banner for UID 1."),
      '#default_value' => !empty($config->get('exclude_uid_1')) ? $config->get('exclude_uid_1') : 0,
    ];

    $form['advanced']['better_support_for_screen_readers'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Let screen readers see the banner before other links on the page.'),
      '#default_value' => !empty($config->get('better_support_for_screen_readers')) ? $config->get('better_support_for_screen_readers') : 0,
      '#description' => $this->t('Enable this if you want to place the banner as the first HTML element on the page. This will make it possible for screen readers to close the banner without tabbing through all links on the page.'),
    ];

    $form['advanced']['cookie_session'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Prompt for consent (from the same user) at every new browser session.'),
      '#description' => $this->t("This sets cookie lifetime to 0, invalidating the cookie at the end of the browser session. To set a cookie lifetime greater than 0, uncheck this option. Note that some users will find this behavior highly annoying, and it's recommended to double-check with the legal advisor whether you really need this option enabled."),
      '#default_value' => $config->get('cookie_session'),
    ];

    $form['advanced']['domain'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Domain'),
      '#default_value' => $config->get('domain'),
      '#description' => $this->t('Sets the domain of the cookie to a specific url. Used when you need consistency across domains. This is language independent. Note: Make sure you actually enter a domain that the browser can make use of. For example if your site is accessible at both www.domain.com and domain.com, you will not be able to hide the banner at domain.com if your value for this field is www.domain.com.'),
    ];

    $form['advanced']['domain_all_sites'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow the cookie to be set for all sites on the same domain.'),
      '#default_value' => !empty($config->get('domain_all_sites')) ? $config->get('domain_all_sites') : 0,
      '#description' => $this->t("Sets the path of the cookie to '/' so that the cookie works across all sites on the domain."),
    ];

    $form['advanced']['cookie_lifetime'] = [
      '#type' => 'number',
      '#title' => $this->t('Cookie lifetime'),
      '#description' => $this->t("How many days the system remember the user's choice."),
      '#default_value' => $config->get('cookie_lifetime'),
      '#field_suffix' => $this->t('days'),
      '#size' => 5,
      '#maxlength' => 5,
      '#required' => TRUE,
      '#states' => [
        'enabled' => [
          "input[name='cookie_session']" => ['checked' => FALSE],
        ],
      ],
    ];

    $form['#attached']['library'][] = 'eu_cookie_compliance/admin';

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    // Set popup_info_template value to 'new' if method is not 'consent by
    // default'.
    if ($form_state->getValue('method') !== 'default') {
      $form_state->setValue('popup_info_template', 'new');
    }
    // Validate cookie name against valid characters.
    if (preg_match('/[&\'\x00-\x20\x22\x28-\x29\x2c\x2f\x3a-\x40\x5b-\x5d\x7b\x7d\x7f]/', $form_state->getValue('cookie_name'))) {
      $form_state->setErrorByName('cookie_name', $this->t('Invalid cookie name, please remove any special characters and try again.'));
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Clear values if we are using minimal css.
    if ($form_state->getValue('use_bare_css')) {
      $form_state->setValue('popup_bg_hex', '');
      $form_state->setValue('popup_text_hex', '');
      $form_state->setValue('popup_height', '');
      $form_state->setValue('popup_width', '');
    }

    // If there's no mobile message entered, disable the feature.
    if (trim($form_state->getValue('mobile_popup_info')['value']) == '') {
      $form_state->setValue('use_mobile_message', FALSE);
    }

    if ($form_state->getValue('popup_link') == '<front>' && $form_state->getValue('disagree_button')) {
      $this->messenger()->addError($this->t('Your privacy policy link is pointing at the front page. This is the default value after installation, and unless your privacy policy is actually posted at the front page, you will need to create a separate page for the privacy policy and link to that page.'));
    }

    // Save permissions.
    $permission_name = 'display eu cookie compliance popup';

    foreach ($this->getRoles() as $role_name => $role) {
      /* @var \Drupal\user\Entity\Role $role */
      if (!$role->isAdmin()) {
        if (array_key_exists($role_name, $form_state->getValue('see_the_banner')) && $form_state->getValue('see_the_banner')[$role_name]) {
          user_role_grant_permissions($role_name, [$permission_name]);
        }
        else {
          user_role_revoke_permissions($role_name, [$permission_name]);
        }
      }
    }

    // Handle legacy settings for popup_position:
    if ($form_state->getValue('popup_position') == 'top') {
      $form_state->setValue('popup_position', TRUE);
    }
    elseif ($form_state->getValue('popup_position') == 'bottom') {
      $form_state->setValue('popup_position', FALSE);
    }

    $method = $form_state->getValue('method');

    if ($method != 'default') {
      $form_state->setValue('disagree_button', TRUE);
      $form_state->setValue('popup_clicking_confirmation', FALSE);
      $form_state->setValue('popup_scrolling_confirmation', FALSE);
      $form_state->setValue('popup_info_template', 'new');
    }
    else {
      $form_state->setValue('whitelisted_cookies', '');
      $form_state->setValue('disabled_javascripts', '');
      $form_state->setValue('withdraw_enabled', FALSE);
    }

    // Clear cached javascript.
    Cache::invalidateTags(['library_info']);

    eu_cookie_compliance_module_set_weight();

    // Save settings.
    $this->config('eu_cookie_compliance.settings')
      ->set('domain', $form_state->getValue('domain'))
      ->set('popup_enabled', $form_state->getValue('popup_enabled'))
      ->set('popup_clicking_confirmation', $form_state->getValue('popup_clicking_confirmation'))
      ->set('popup_scrolling_confirmation', $form_state->getValue('popup_scrolling_confirmation'))
      ->set('popup_position', $form_state->getValue('popup_position'))
      ->set('popup_agree_button_message', $form_state->getValue('popup_agree_button_message'))
      ->set('show_more_info', $form_state->getValue('show_more_info'))
      ->set('popup_more_info_button_message', $form_state->getValue('popup_more_info_button_message'))
      ->set('popup_info', $form_state->getValue('popup_info'))
      ->set('popup_info_template', $form_state->getValue('popup_info_template'))
      ->set('use_mobile_message', $form_state->getValue('use_mobile_message'))
      ->set('mobile_popup_info', $form_state->getValue('use_mobile_message') ? $form_state->getValue('mobile_popup_info') : ['value' => '', 'format' => filter_default_format()])
      ->set('mobile_breakpoint', $form_state->getValue('mobile_breakpoint'))
      ->set('popup_agreed_enabled', $form_state->getValue('popup_agreed_enabled'))
      ->set('popup_hide_agreed', $form_state->getValue('popup_hide_agreed'))
      ->set('popup_find_more_button_message', $form_state->getValue('popup_find_more_button_message'))
      ->set('popup_hide_button_message', $form_state->getValue('popup_hide_button_message'))
      ->set('popup_agreed', $form_state->getValue('popup_agreed'))
      ->set('popup_link', $form_state->getValue('popup_link'))
      ->set('popup_link_new_window', $form_state->getValue('popup_link_new_window'))
      ->set('popup_height', $form_state->getValue('popup_height'))
      ->set('popup_width', $form_state->getValue('popup_width'))
      ->set('popup_delay', $form_state->getValue('popup_delay'))
      ->set('popup_bg_hex', $form_state->getValue('popup_bg_hex'))
      ->set('popup_text_hex', $form_state->getValue('popup_text_hex'))
      ->set('domains_option', $form_state->getValue('domains_option'))
      ->set('domains_list', $form_state->getValue('domains_list'))
      ->set('exclude_paths', $form_state->getValue('exclude_paths'))
      ->set('exclude_admin_theme', $form_state->getValue('exclude_admin_theme'))
      ->set('cookie_lifetime', $form_state->getValue('cookie_lifetime'))
      ->set('cookie_session', $form_state->getValue('cookie_session'))
      ->set('eu_only', $form_state->getValue('eu_only'))
      ->set('eu_only_js', $form_state->getValue('eu_only_js'))
      ->set('use_bare_css', $form_state->getValue('use_bare_css'))
      ->set('disagree_do_not_show_popup', $form_state->getValue('disagree_do_not_show_popup'))
      ->set('reload_page', $form_state->getValue('reload_page'))
      ->set('domain_all_sites', $form_state->getValue('domain_all_sites'))
      ->set('cookie_name', $form_state->getValue('cookie_name'))
      ->set('exclude_uid_1', $form_state->getValue('exclude_uid_1'))
      ->set('better_support_for_screen_readers', $form_state->getValue('better_support_for_screen_readers'))
      ->set('fixed_top_position', $form_state->getValue('fixed_top_position'))
      ->set('method', $form_state->getValue('method'))
      ->set('disagree_button_label', $form_state->getValue('disagree_button_label'))
      ->set('whitelisted_cookies', $form_state->getValue('whitelisted_cookies'))
      ->set('disabled_javascripts', $form_state->getValue('disabled_javascripts'))
      ->set('consent_storage_method', $form_state->getValue('consent_storage_method'))
      ->set('withdraw_message', $form_state->getValue('withdraw_message'))
      ->set('withdraw_action_button_label', $form_state->getValue('withdraw_action_button_label'))
      ->set('withdraw_tab_button_label', $form_state->getValue('withdraw_tab_button_label'))
      ->set('withdraw_enabled', $form_state->getValue('withdraw_enabled'))
      ->set('withdraw_button_on_info_popup', $form_state->getValue('withdraw_button_on_info_popup'))
      ->set('cookie_categories', $form_state->getValue('cookie_categories'))
      ->set('enable_save_preferences_button', $form_state->getValue('enable_save_preferences_button'))
      ->set('save_preferences_button_label', $form_state->getValue('save_preferences_button_label'))
      ->set('accept_all_categories_button_label', $form_state->getValue('accept_all_categories_button_label'))
      ->set('fix_first_cookie_category', $form_state->getValue('fix_first_cookie_category'))
      ->set('select_all_categories_by_default', $form_state->getValue('select_all_categories_by_default'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Validates the banner link field.
   *
   * @param array $element
   *   Element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  public function validatePopupLink(array $element, FormStateInterface &$form_state) {
    if (empty($element['#value'])) {
      return;
    }

    $input = $element['#value'];
    if (UrlHelper::isExternal($input)) {
      $allowed_protocols = ['http', 'https'];
      if (!in_array(parse_url($input, PHP_URL_SCHEME), $allowed_protocols)) {
        $form_state->setError($element, $this->t('Invalid protocol specified for the %name (valid protocols: %protocols).', [
          '%name' => $element['#title'],
          '%protocols' => implode(', ', $allowed_protocols),
        ]));
      }
      else {
        try {
          Url::fromUri($input);
        }
        catch (\Exception $exc) {
          $form_state->setError($element, $this->t('Invalid %name (:message).', ['%name' => $element['#title'], ':message' => $exc->getMessage()]));
        }
      }
    }
    else {
      // Special case for '<front>'.
      if ($input === '<front>') {
        $input = '/';
      }
      try {
        Url::fromUserInput($input);
      }
      catch (\Exception $exc) {
        $form_state->setError($element, $this->t('Invalid URL in %name field (:message).', ['%name' => $element['#title'], ':message' => $exc->getMessage()]));
      }
    }
  }

}
