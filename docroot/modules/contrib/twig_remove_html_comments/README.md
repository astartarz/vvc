# Twig - Remove HTML comments

Provides a Twig filter that removes HTML comments.

Created out of [https://www.drupal.org/project/drupal/issues/2672656](https://www.drupal.org/project/drupal/issues/2672656).

## Requirements
* Drupal 8 or 9
* PHP 7.2+

## Usage:
```twig
{# Print result as markup render array. #}
{{ content.field_my_field|render|remove_html_comments }}

{# Print result as string. #}
{{ content.field_my_field|render|remove_html_comments_as_string }}
```
