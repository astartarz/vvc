<?php

/**
 * @file
 * Contains \Drupal\views_ical\Plugin\views\row\Fields.
 */

namespace Drupal\views_ical\Plugin\views\row;

use Drupal\views\Plugin\views\row\Fields;
use Drupal\views\ResultRow;
use Drupal\Core\Entity\ContentEntityInterface;
use Eluceo\iCal\Component\Event;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Eluceo\iCal\Component\Calendar;
use Eluceo\iCal\Component\Timezone;


/**
 * The 'Ical Fields' row plugin
 *
 * This displays fields one after another, giving options for inline
 * or not.
 *
 * @ingroup views_row_plugins
 *
 * @ViewsRow(
 *   id = "ical_fields_wizard",
 *   title = @Translation("iCal fields row wizard"),
 *   help = @Translation("Generate ical events with the iCal library."),
 *   theme = "views_view_ical_fields",
 *   display_types = {"feed"}
 * )
 */
class IcalFieldsWizard extends Fields {
  // What is the point of this?

  /**
   * Render a row object. This usually passes through to a theme template
   * of some form, but not always.
   *
   * @param object $row
   *   A single row of the query result, so an element of $view->result.
   *
   * @return string
   *   The rendered output of a single row, used by the style plugin.
   */
  public function render($row) {

   $style = $this->view->getStyle();
    $style_options = $style->options;
     /** @var \Drupal\Core\Field\FieldDefinitionInterface[] $field_storage_definitions */
//    $field_storage_definitions = $style->entityFieldManager->getFieldStorageDefinitions($this->view->field[$options['date_field']]->definition['entity_type']);
    $entity_field_manager = $style->getEntityFieldManager();

    if(!isset($style_options['date_field'])) {
      // If this is not set for some reason (dev is just starting out to create
      // a view?), don't try to render. We can't have an event without a date.
      return;
    }

    $field_storage_definitions = $entity_field_manager->getFieldStorageDefinitions($this->view->field[$style_options['date_field']]->definition['entity_type']);


    //$date_field = $this->view->field[$options['date_field']];
    $date_field_definition = $field_storage_definitions[$this->view->field[$style_options['date_field']]->definition['field_name']];
    /** @var string $date_field_type */
    $date_field_type = $date_field_definition->getType();

    $events = [];
    $user_timezone = \drupal_get_user_timezone();

    // Make sure the events are made as per the configuration in view.
    /** @var string $timezone_override */
    $timezone_override = $this->view->field[$style_options['date_field']]->options['settings']['timezone_override'];
    if ($timezone_override) {
      $timezone = new \DateTimeZone($timezone_override);
    }
    else {
      $timezone = new \DateTimeZone($user_timezone);
    }

    // Use date_recur's API to generate the events.
    // Recurring events will be automatically handled here.
    if ($date_field_type === 'date_recur') {
      $this->addDateRecurEvent($events, $row, $timezone, $style_options);
    }
    // Datetime events are single dates without a time component.
    // Many content models might
    else if($date_field_type === 'datetime') {
      $this->addDateTimeEvent($events, $row, $timezone, $style_options);

    }
    else if ($date_field_type === 'daterange') {
      // TODO: are date ranges separate date field types?
      $this->addDateRangeEvent($events, $row, $timezone, $style_options);

    }
    // This field type is actually deprecated by the date_all_day module.
    else if ($date_field_type === 'daterange_all_day') {
      throw new \Exception('daterange_all_day fields not supported.');
      //$this->helper->addEvent($events, $row , $timezone, $this->options);
    }

    $calendar = $this->view->getStyle()->getCalendar();

    foreach ($events as $event) {
      $calendar->addComponent($event);
    }


    return [
      '#theme' => $this->themeFunctions(),
      '#view' => $this->view,
      '#options' => $this->options,
      '#row' => $row,
      '#field_alias' => isset($this->field_alias) ? $this->field_alias : '',
      '#event' => $events,
    ];
  }





  /**
   * Creates an event with default data.
   *
   * Event summary, location and description are set as defaults.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to be used for default data.
   * @param array $field_mapping
   *   Views field option and entity field name mapping.
   *   Example:
   *   [
   *     'date_field' => 'field_event_date',
   *     'summary_field' => 'field_event_summary',
   *     'description_field' => 'field_event_description',
   *   ]
   *   End of example.
   *
   * @return \Eluceo\iCal\Component\Event
   *   A new event.
   *
   * @see \Drupal\views_ical\Plugin\views\style\Ical::defineOptions
   */
  protected function createDefaultEvent(ContentEntityInterface $entity, array $field_mapping): Event {
    if(isset($field_mapping['uid_field'])
      && ($field_mapping['uid_field'] == 'nid'
        || $field_mapping['uid_field'] == 'nothing')) {
      // If the Uid field is the nid, access with the id method.
      $uid = $entity->id();
      if(isset($this->view->field[$field_mapping['uid_field']]->options['alter']['alter_text'])
        && $this->view->field[$field_mapping['uid_field']]->options['alter']['alter_text']) {
        // I need rewrite of the UID field to happen here.
        // This is really hacky, It would be really nice to find a way to render as the row.
        $alter_text = $this->view->field[$field_mapping['uid_field']]->options['alter']['text'];
        $fields = array_keys($this->view->field);
        foreach ($fields as $field) {
          if ($entity->hasField($field)) {
            if ($entity->get($field)->getDataDefinition()->getType() == 'created') {
              $settings = $this->view->field['created']->options['settings'];
              ['custom_date_format'];
              if($settings['date_format'] == 'custom') {
                $field_value =  \Drupal::service('date.formatter')->format($entity->get($field)->getString(), 'custom', $settings['custom_date_format']);
              }
              else {
                $field_value =  \Drupal::service('date.formatter')->format($entity->get($field)->getString(), $settings['date_format']);
              }
            }
            else {
              $field_value = $entity->get($field)->getString();
            }
            $alter_text= str_replace("{{ $field }}", $field_value, $alter_text);
          }
        }
        $uid = $alter_text;
      }
    }
    else if(isset($field_mapping['uid_field'])
      && $field_mapping['uid_field'] != 'none'
      && $entity->hasField($field_mapping['uid_field'])
      && !$entity->get($field_mapping['uid_field'])->isEmpty()) {
      $uid = $entity->get($field_mapping['uid_field'])->getString();
    }
    else {
      $uid = null;
    }

    $event = new Event($uid);

    // Summary field.
    if (isset($field_mapping['summary_field']) && $entity->hasField($field_mapping['summary_field'])) {
      if ($field_mapping['summary_field'] == 'body'  && !$entity->get('body')->isEmpty()) {
        $summary = $entity->get('body')->getValue()[0]['value'];
      }
      else {
        $summary = $entity->get($field_mapping['summary_field'])->getString();
      }
      if ($summary) {
        $event->setSummary($summary);
      }
    }

    // Location field
    if (isset($field_mapping['location_field']) && $entity->hasField($field_mapping['location_field'])) {
      if ($field_mapping['location_field'] == 'body' && !$entity->get('body')->isEmpty()) {
        $location = $entity->get('body')->getValue()[0]['value'];
        $event->setLocation($location);
      }
      else {
        $location = $entity->{$field_mapping['location_field']}->first();
        $event->setLocation($location->getValue()['value']);
      }

    }

    // Description field
    if (isset($field_mapping['description_field']) && $entity->hasField($field_mapping['description_field'])) {
      if ($field_mapping['location_field'] == 'body') {
        /** @var \Drupal\Core\Field\FieldItemInterface $description */
        $description = $entity->{$field_mapping['description_field']}->getValue()[0]['value'];
        $event->setDescription($description);
      }
      else {
        /** @var \Drupal\Core\Field\FieldItemInterface $description */
        $description = $entity->{$field_mapping['description_field']}->first();
        $event->setDescription(\strip_tags($description->getValue()['value']));
      }
    }

    // Transparency - This isn't a real field, but a default setting applied to all events.
    if (isset($field_mapping['default_transparency']) && $field_mapping['default_transparency']) {
      if($field_mapping['default_transparency'] == event::TIME_TRANSPARENCY_OPAQUE)
        $event->setTimeTransparency(event::TIME_TRANSPARENCY_OPAQUE);
      else
        $event->setTimeTransparency(event::TIME_TRANSPARENCY_TRANSPARENT);
    }

    $event->setUseTimezone(TRUE);

    return $event;
  }

  /**
   * Create an event based on a daterange field.
   *
   * @param array $events
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   * @param \DateTimeZone $timezone
   * @param array $field_mapping
   */
  public function addDateRangeEvent(array &$events, \Drupal\views\ResultRow $row, \DateTimeZone $timezone, array $field_mapping): void {

    $entity = $this->getEntity($row);

    $utc_timezone = new \DateTimeZone('UTC');
    $datefield_values = $entity->get($field_mapping['date_field'])->getValue();

    // TODO: make these separate functions
    // Loop over the values to support multiple cardinality dates, which can
    // represent multiple events.
    foreach ($entity->get($field_mapping['date_field'])->getValue() as $date_entry) {

      // generate the event.
      $event = $this->createDefaultEvent($entity, $field_mapping);

      // Set the start time
      $start_datetime = new \DateTime($date_entry['value'], $utc_timezone);
      $start_datetime->setTimezone($timezone);
      $event->setDtStart($start_datetime);

      // Loop over field values so we can support daterange fields with multiple cardinality.
      if (!empty($date_entry['end_value'])) {
        $end_datetime = new \DateTime($date_entry['end_value'], $utc_timezone);
        $end_datetime->setTimezone($timezone);

        $event->setDtEnd($end_datetime);

        // If this is a date_all_day field, pull the all day option from that.
        if($date_all_day = false) {
          // TODO: implement
        }
        else {
          if (isset($field_mapping['no_time_field']) && $field_mapping['no_time_field'] != 'none') {
            $all_day = $entity->get($field_mapping['no_time_field'])->getValue();
            if ($all_day && isset($all_day[0]['value']) && $all_day[0]['value']) {
              $event->setNoTime(true);
            }
          }
        }
      }
      //else {
      // is DTEND is not a required field, but if it is not included, nor
      // is duration (which we are not using here), then the event's duration
      // is taken to be one day. But do we need to explicitly define that here?
      // Do calendar apps handle that? https://tools.ietf.org/html/rfc5545#section-3.6.1
      //}

      $events[] = $event;
    }
  }


  /**
   * Create an event based on a datetime field
   *
   * @param array $events
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   * @param \DateTimeZone $timezone
   * @param array $field_mapping
   */
  public function addDateTimeEvent(array &$events, \Drupal\views\ResultRow $row, \DateTimeZone $timezone, array $field_mapping): void {

    $entity = $this->getEntity($row);

    $utc_timezone = new \DateTimeZone('UTC');
    $datefield_values = $entity->get($field_mapping['date_field'])->getValue();

    // If an end date field was defined, then the content model is most likely
    // using two, single cardinality fields for a start and an end date.
    if (isset($field_mapping['end_date_field']) && $field_mapping['end_date_field'] != 'none') {

      // generate the event
      $event = $this->createDefaultEvent($entity, $field_mapping);

      // set the start time.
      $date_entry = $datefield_values[0];
      $start_datetime = new \DateTime($date_entry['value'], $utc_timezone);
      $start_datetime->setTimezone($timezone);
      $event->setDtStart($start_datetime);

      // Set the end time
      $end_date_field_values = $entity->get($field_mapping['end_date_field'])->getValue();
      $end_date_entry = $end_date_field_values[0];
      $end_datetime = new \DateTime($end_date_entry['value'], $utc_timezone);
      $end_datetime->setTimezone($timezone);
      $event->setDtEnd($end_datetime);

      // All day events.
      if (isset($field_mapping['no_time_field']) && $field_mapping['no_time_field'] != 'none') {
        $all_day = $entity->get($field_mapping['no_time_field'])->getValue();
        if ($all_day && isset($all_day[0]['value']) && $all_day[0]['value']) {
          $event->setNoTime(TRUE);
        }
      }
      $events[] = $event;
    }


  }

  /**
   * {@inheritdoc}
   */
  public function addEvent(array &$events, \Drupal\views\ResultRow $row, \DateTimeZone $timezone, array $field_mapping): void {
    // All code moved to field-specific methods.
  }




  /**
   * {@inheritdoc}
   */
  public function addDateRecurEvent(array &$events, \Drupal\views\ResultRow $row, \DateTimeZone $timezone, array $field_mapping): void {
    /** @var \Drupal\date_recur\Plugin\Field\FieldType\DateRecurItem[] $field_items */
    $entity = $this->getEntity($row);
    $field_items = $entity->{$field_mapping['date_field']};

    foreach ($field_items as $index => $item) {
      /** @var \Drupal\date_recur\DateRange[] $occurrences */
      $occurrences = $item->getHelper()->getOccurrences();

      foreach ($occurrences as $occurrence) {
        $event = $this->createDefaultEvent($entity, $field_mapping);

        /** @var \DateTime $start_datetime */
        $start_datetime = $occurrence->getStart();
        $start_datetime->setTimezone($timezone);
        $event->setDtStart($start_datetime);

        /** @var \DateTime $end_datetime */
        $end_datetime = $occurrence->getEnd();
        $end_datetime->setTimezone($timezone);
        $event->setDtEnd($end_datetime);

        $events[] = $event;
      }
    }
  }

  /**
   * Gets the entity for a corresponding row.
   *
   * @param \Drupal\views\ResultRow $row
   * @return \Drupal\Core\Entity\EntityInterface|null
   */
  public function getEntity($row) {
    if ($this->view->storage->get('base_table') == 'node_field_data') {
      // TODO, Change how this is being accessed so it's not using private properties
      $entity = $row->_entity;
    }
    else if ($this->view->storage->get('base_table') == 'search_api_index_default_content_index') {
      $entity = $row->_object->getValue();
    }
    else {
      throw new Exception('Base table type not supported. At the moment, Views iCal only supports nodes and Search API indexes');
    }

    return $entity;

  }


}
