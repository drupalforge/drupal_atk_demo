<?php

namespace Drupal\pl_drupal_forge_theme;

use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Implements trusted prerender callbacks for pl_drupal_forge_theme.
 *
 * @internal
 */
class PLDrupalForgeThemePreRender implements TrustedCallbackInterface {

  /**
   * Prerender callback for status_messages placeholder.
   *
   * @param array $element
   *   A renderable array.
   *
   * @return array
   *   The updated renderable array containing the placeholder.
   */
  public static function messagePlaceholder(array $element) {
    if (isset($element['fallback']['#markup'])) {
      $element['fallback']['#markup'] = '<div data-drupal-messages-fallback class="hidden messages-list"></div>';
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
 public static function trustedCallbacks() {
    return [
      'messagePlaceholder',
    ];
  }

}
