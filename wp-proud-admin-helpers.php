<?php
/** 
 * Wraps label text and adds link to docs
 * @todo: move this somewhere else
 */
function __pcHelp( $text, $link = false, $key = 'proud', $args = array() ) {
  // @todo: This should be a DEFINED variable
  $link = !empty($link) && strpos($link, '//') === false  ? '//pattern.getproudcity.com/#sg-' . $link : $link;
  $link_text = !empty($args['link_text']) ? $args['link_text'] : '<i class="fa fa-fw fa-external-link-squared"></i>';
  $link_text = empty($link) ? '' : ' <a class="btn btn-xs btn-default" href="'. $link .'">'. $link_text .'</a>';
  return __( $text, $key ) . $link_text;
}
