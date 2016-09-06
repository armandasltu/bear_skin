<?php

/**
 * Implements template_preprocess_html().
 * 1. Adds path variables.
 * 2. Include bear skin theme options in Drupal's JS object
 * 3. Include a CSS class on the body tag if the site uses sticky footer
 */
function bear_skin_preprocess_html(&$variables, $hook) {
  // Add variables and paths needed for HTML5 and responsive support.
  $variables['base_path'] = base_path();
  $variables['path_to_bear_skin'] = drupal_get_path('theme', 'bear_skin');
  $variables['skip_link_anchor'] = 'main-content';


  // include the selected language
  global $language;
  $variables['language'] = $language->language;

  // Attributes for html element.
  $variables['html_attributes_array'] = array(
    $variables['language'] => $language->language,
  );

  // Send X-UA-Compatible HTTP header to force IE to use the most recent
  // rendering engine or use Chrome's frame rendering engine if available.
  // This also prevents the IE compatibility mode button to appear when using
  // conditional classes on the html tag.
  if (is_null(drupal_get_http_header('X-UA-Compatible'))) {
    drupal_add_http_header('X-UA-Compatible', 'IE=edge,chrome=1');
  }

  // Return early, so the maintenance page does not call any of the code below.
  if ($hook !== 'html') {
    return;
  }

  // Serialize RDF Namespaces into an RDFa 1.1 prefix attribute.
  if (!empty($variables['rdf_namespaces'])) {
    $prefixes = array();
    foreach (explode("\n  ", ltrim($variables['rdf_namespaces'])) as $namespace) {
      // Remove xlmns: and ending quote and fix prefix formatting.
      $prefixes[] = str_replace('="', ': ', substr($namespace, 6, -1));
    }
    $variables['rdf_namespaces'] = ' prefix="' . implode(' ', $prefixes) . '"';
  }

  // Classes for body element. Allows advanced theming based on context
  // (home page, node of certain type, etc.)
  if (!$variables['is_front']) {
    // Add unique class for each page.
    $path = drupal_get_path_alias($_GET['q']);
    // Add unique class for each website section.
    list($section) = explode('/', $path, 2);
    $variables['classes_array'][] = drupal_html_class('section-' . $section);
  }

  $variables['menu_item'] = menu_get_item();
  if (!empty($variables['menu_item']) && !empty($variables['menu_item']['page_callback'])) {
    // Add class to body when panels renders the page
    if (FALSE !== strpos($variables['menu_item']['page_callback'], 'page_manager')) {
      $variables['classes_array'][] = 'page-panels';
    }
  }
}

/**
 * Implements template_html_head_alter().
 */
function bear_skin_html_head_alter(&$head_elements) {
  $head_elements['system_meta_content_type']['#attributes'] = array(
    'charset' => 'utf-8'
  );
}

/**
 * Override or insert variables into the html templates.
 *
 * @param $variables
 *   An array of variables to pass to the theme template.
 * @param $hook
 *   The name of the template being rendered ("html" in this case.)
 */
function bear_skin_process_html(&$variables, $hook) {
  // Flatten out html_attributes.
  $variables['html_attributes'] = drupal_attributes($variables['html_attributes_array']);
}

/**
 * Implements template_preprocess_page().
 * 1. Check if sidebars have content; Add boolean to $variables
 * 2. Setup the user menu (by default displayed in header)
 */
function bear_skin_preprocess_page(&$variables) {

  $page = $variables['page'];

  if (isset($variables['node']->type)) {
    $nodetype = $variables['node']->type;
    $variables['theme_hook_suggestions'][] = 'page__' . $nodetype;
  }

  // set the page title for the homepage
  // for accessibility purposes
  $title = drupal_get_title();
  if (drupal_is_front_page() && empty($title)) {
    $variables['bear_page_title'] = variable_get('site_name', '') . ' Homepage';
  }
  else {
    $variables['bear_page_title'] = $title;
  }

  // check if there is content in the sidebars
  $variables['has_sidebar_first'] = FALSE;
  $variables['has_sidebar_second'] = FALSE;
  if (!empty($page['sidebar_first'])) {
    $variables['has_sidebar_first'] = TRUE;
  }
  if (!empty($page['sidebar_second'])) {
    $variables['has_sidebar_second'] = TRUE;
  }

  // setup the user menu to display in the header
  $variables['user_menu'] = theme('links__user_menu', array(
    'links' => menu_navigation_links('user-menu'),
    'attributes' => array(
      'class ' => array('nav-user__list'),
      'aria-labelledby' => 'userMenuLabel',
    ),
  ));

  // include the basic search form if one exists
  if (module_exists('search')) {
    $bear_search_form = module_invoke('search', 'block_view', 'search');
    $variables['page']['bear_search_form'] = render($bear_search_form);
  }
}

/**
 * Implements template_preprocess_region()
 * 1. Add SMACCS / BEM style CSS class to regions
 */
function bear_skin_preprocess_region(&$variables) {
  $region = $variables['region'];
  $variables['classes_array'][] = 'region--' . str_replace('_', '-', $region);
  $variables['attributes_array']['role'] = 'region';
}

/**
 * Implements theme_preprocess_node()
 * 1. Add SMACCS / BEM style CSS classes for nodes and node titles
 * 2. Let node teasers have a tpl file called node--teaser.tpl.php
 */
function bear_skin_preprocess_node(&$variables) {
  $view_mode = $variables['view_mode'];
  $type = $variables['type'];

  // add theme suggestion for node teasers
  // add teaser CSS classes
  if ($view_mode === 'teaser') {
    $variables['classes_array'][] = 'node-' . $type . '-teaser';
    $variables['title_attributes_array']['class'][] = 'node-teaser__title';
    $variables['title_attributes_array']['class'][] = 'node-' . $type . '-teaser__title';
    array_unshift($variables['theme_hook_suggestions'], 'node__teaser');
  }

  if ($view_mode === 'full' || $view_mode === 'default') {
    $variables['classes_array'][] = 'node-full';
    $variables['classes_array'][] = 'node-' . $type . '-full';
  }
}

/**
 * Implements template_preprocess_block()
 * 1. Add a class to the block to indicate its type and region placement
 */
function bear_skin_preprocess_block(&$variables) {
  $module = str_replace('_', '-', $variables['block']->module) . '-' . $variables['block']->delta;
  $region = str_replace('_', '-', $variables['block']->region);
  $variables['classes_array'][] = 'block__' . $module;
  $variables['classes_array'][] = 'block__' . $module . '--' . $region;
}

/**
 * Implements template_form_alter()
 */
function bear_skin_form_alter(&$form, &$form_state, $form_id) {
  switch ($form_id) {
    case 'search_block_form':
      $form['search_block_form']['#attributes']['placeholder'] = t('Search');
      $form['search_block_form']['#attributes']['required'] = 'required';
      break;
  }
}

/**
 * Implements theme_select()
 */
function bear_skin_select($variables) {
  $element = $variables['element'];
  element_set_attributes($element, array('id', 'name', 'size'));
  _form_set_class($element, array('form-select'));

  return '<div class="select-wrapper"><select' . drupal_attributes($element['#attributes']) . '>' . form_select_options($element) . '</select></div>';
}

/**
 * Implements template_preprocess_views_view()
 */
function bear_skin_preprocess_views_view(&$variables) {
  $name = $variables['css_name'];
  $display = drupal_clean_css_identifier($variables['view']->current_display);
  $variables['classes_array'][] = $name . '-' . $display . '-view';
}

/**
 * Implements template_preprocess_views_view_unformatted()
 */
function bear_skin_preprocess_views_view_unformatted(&$variables) {
  $id = drupal_clean_css_identifier($variables['view']->name) . '-' . drupal_clean_css_identifier($variables['view']->current_display);
  foreach ($variables['classes_array'] as $key => $class) {
    $variables['classes_array'][$key] .= ' ' . $id . '-view__row';
  }
}

/**
 * Implements template_preprocess_menu_block_wrapper()
 */
function bear_skin_preprocess_menu_block_wrapper(&$variables) {
  $delta = $variables['delta'];
  $variables['theme_hook_suggestions'][] = 'menu_block_wrapper__menu_' . str_replace('-', '_', $variables['config']['menu_name']);
  $variables['classes_array'] = array($variables['config']['menu_name']);

  if ($variables['config']['menu_name'] === 'main-menu') {
    array_unshift($variables['classes_array'], 'site-navigation');
  }
}

/**
 * Implements theme_links()
 * for all others with the exception of user_menu (see bear_skin_links__user_menu)
 * 1. Add SMACCS / BEM style CSS classes
 * 2. Add ARIA roles for accessibility
 */
function bear_skin_links(&$variables) {
  // create a more unique CSS class for the menu
  if (!empty($variables['attributes']['class']) && is_array($variables['attributes']['class'])) {
    $menu_class = implode('-', $variables['attributes']['class']);
    $variables['attributes']['class'][] = $menu_class . '__list';
  }
  else {
    // since the classes array on the menu is empty
    // we'll just give this a class of theme-links since
    // that is the generating function of this menu
    $menu_class = 'theme-links';
    $variables['attributes']['class'] = array($menu_class);
  }

  // add the ARIA role for accessibility
  $variables['attributes']['role'] = 'menubar';

  if (!empty($variables['links']) && is_array($variables['links'])) {
    foreach ($variables['links'] as $key => &$link) {
      $link['attributes']['role'] = 'menuitem';
      $link['attributes']['class'] = (!empty($link['attributes']['class'])) ? (array) $link['attributes']['class'] : array();
      $variables['classes_array'] = $menu_class . '__link';
    }
  }
  return '<nav role="navigation" class="' . $menu_class . '">' . theme_links($variables) . '</nav>' . "\n";
}

/**
 * Implements theme_links()
 * specifically for the user_menu only!
 * 1. Add a SMACCS / BEM style CSS classes
 * 2. Add ARIA roles for accessibility
 */
function bear_skin_links__user_menu(&$variables) {
  // add the ARIA role for accessibility
  $variables['attributes']['role'] = 'menubar';

  foreach ($variables['links'] as $key => &$link) {
    if (!is_array($link)) {
      continue;
    }
    $link['attributes'] = (!empty($link['attributes'])) ? $link['attributes'] : array();
    $link['attributes']['class'][] = 'nav-user__link';
    $link['attributes']['role'] = 'menuitem';
  }
  return theme_links($variables);
}

/**
 * Implements template_preprocess_menu_link()
 * 1. Make a more specific CSS class for menu list items <li>
 * 2. Make a CSS class on menu list items <li> referencing their level depth
 * 3. Make a more specific CSS class for menu links <a>
 * 4. Set ARIA roles and properties for accessibility
 * 5. Save the menu name and depth as attributes
 */
function bear_skin_preprocess_menu_link(&$variables, $hook) {
  $menu_name = (!empty($variables['element']['#original_link'])) ? $variables['element']['#original_link']['menu_name'] : '';
  $depth_word = (!empty($variables['element']['#original_link'])) ? $variables['element']['#original_link']['depth'] : '';

  $variables['element']['#attributes']['class'] = (empty($variables['element']['#attributes']['class'])) ? array() : $variables['element']['#attributes']['class'];
  $is_active = in_array('active', $variables['element']['#attributes']['class']);
  $has_children = (!empty($variables['element']['#original_link'])) ? $variables['element']['#original_link']['expanded'] && $variables['element']['#original_link']['has_children'] : FALSE;

  // <li> elements
  $variables['element']['#attributes']['class'] = array();
  $variables['element']['#attributes']['class'][] = $menu_name . '__item';
  $variables['element']['#attributes']['class'][] = 'level-' . $depth_word;
  if ($has_children) {
    $variables['element']['#attributes']['class'][] = "parent";
  }
  if ($is_active) {
    $variables['element']['#attributes']['class'][] = 'active';
  }
  $variables['element']['#attributes']['role'] = 'presentation';

  // <a> elements
  $variables['element']['#localized_options']['attributes']['class'] = array();
  $variables['element']['#localized_options']['attributes']['class'][] = $menu_name . '__link';
  if ($is_active) {
    $variables['element']['#localized_options']['attributes']['class'][] = 'active';
  }
  $variables['element']['#localized_options']['attributes']['role'] = 'menuitem';
  $variables['element']['#localized_options']['attributes']['aria-haspopup'] = ($has_children) ? 'true' : 'false';

  // save the menu name and depth as data attributes
  // this is a hack so that the <ul class="menu"> element can ultimately have
  // CSS classes that reflect the specific menu name and its depth in the tree
  $variables['element']['#attributes']['data-menu-name'] = $menu_name;
  $variables['element']['#attributes']['data-menu-depth'] = $depth_word;
}

/**
 * Implements template_preprocess_menu_tree()
 * 1. Pick the data attributes for menu name and depth,
 *    save them as elements in the $variables array
 *    then the template_menu_tree hook can add them as CSS classes
 */
function bear_skin_preprocess_menu_tree(&$variables) {
  $tree = new DOMDocument();
  @$tree->loadHTML($variables['tree']);
  $links = $tree->getElementsByTagname('li');
  $menu_name = '';
  $menu_depth = '';

  foreach ($links as $link) {
    // get the attributes and save them
    $menu_name = $link->getAttribute('data-menu-name');
    $menu_depth = $link->getAttribute('data-menu-depth');
    break;
  }

  $variables['menu_name'] = $menu_name;
  $variables['menu_depth'] = $menu_depth;
}

/**
 * Implements template_menu_tree()
 * 1. Make CSS classes out of the data attributes stored in
 *    the template_preprocess_menu_tree hook
 */
function bear_skin_menu_tree(&$variables) {
  $role = ($variables['menu_depth'] === 'top' || $variables['menu_depth'] === 'one') ? 'menubar' : 'menu';
  return '<ul class="menu ' . $variables['menu_name'] . '--level-' . $variables['menu_depth'] . '" role="' . $role . '">' . $variables['tree'] . '</ul>';
}

/**
 * Implements theme_status_messages()
 * 1. Add some additional CSS classes
 * 2. Make the alerts accessible using WAI standards
 */
function bear_skin_status_messages($variables) {
  $display = $variables['display'];
  $output = '';

  $status_heading = array(
    'status' => t('Status message'),
    'error' => t('Error message'),
    'warning' => t('Warning message'),
    'success' => t('Status message'),
  );
  foreach (drupal_get_messages($display) as $type => $messages) {
    $type = ($type === 'status') ? 'success' : $type;
    $role = ($type === 'error') ? 'assertive' : 'polite';
    $output .= "<div class=\"messages--$type messages $type\">\n";
    if (!empty($status_heading[$type])) {
      $output .= '<h2 class="visually-hidden">' . $status_heading[$type] . "</h2>\n";
    }
    $output .= " <ul class=\"messages__list\" role=\"list\">\n";
    foreach ($messages as $message) {
      $output .= "  <li class=\"messages__item\" role=\"listitem\"><span role=\"status\" aria-live=\"" . $role . "\">" . $message . "</span></li>\n";
    }
    $output .= " </ul>\n";
    $output .= "</div>\n";
  }
  return $output;
}

/**
 * Implements theme_item_list()
 * 1. Add some additional CSS classes
 * 2. Make the alerts accessible using WAI standards
 */
function bear_skin_item_list(&$variables) {
  $items = $variables['items'];
  $title = $variables['title'];
  $type = $variables['type'];
  $attributes = $variables['attributes'];

  if (empty($variables['attributes']['class'])) {
    $variables['attributes']['class'] = array();
  }
  else if (!is_array($variables['attributes']['class'])) {
    $variables['attributes']['class'] = array($variables['attributes']['class']);
  }

  // determine if this is the pagination element
  $pager = FALSE;
  if (in_array('pager', $variables['attributes']['class'])) {
    $pager = TRUE;
    $variables['attributes']['class'] = array();
  }

  // add generic list class to front of classes
  $list_class = ($pager) ? 'pager' : 'item-list';
  array_unshift($variables['attributes']['class'], $list_class . '__list');

  // add ARIA role to <ul> element
  $variables['attributes']['role'] = ($pager) ? 'menubar' : 'list';
  // add ARIA roles and SMACCS classes to list items
  if (!empty($items)) {
    foreach ($variables['items'] as &$item) {
      if (!is_array($item)) {
        continue;
      }
      $item['role'] = ($pager) ? 'presentation' : 'listitem';

      if (!$pager) {
        $item['class'] = (!empty($item['class'])) ? $item['class'] : array();
        $item['class'][] = 'item-list__item';
      }

      if ($pager) {
        $has_label = preg_match('/title="(.*?)"/', $item['data'], $label_text);
        if ($has_label) {
          // this is a bit ugly
          // TODO: find a way to do this that doesn't use str_replace
          $item['data'] = str_replace('<a ', '<a aria-label="' . $label_text[1] . '" class="' . $list_class . '__link" ', $item['data']);
        }
      }
    }
  }

  if ($pager) {
    return '<nav class="' . $list_class . '" role="navigation" aria-label="Pagination">' . theme_item_list($variables) . '</nav>' . "\n";
  }
  else {
    return theme_item_list($variables);
  }
}

function bear_skin_pager_link(&$variables) {
  $text = $variables['text'];
  $page_new = $variables['page_new'];
  $element = $variables['element'];
  $parameters = $variables['parameters'];
  $attributes = $variables['attributes'];

  $attributes['class'] = (empty($attributes['class']) || !is_array($attributes['class'])) ? array() : $attributes['class'];
  $attributes['class'][] = 'pager__link';

  $page = isset($_GET['page']) ? $_GET['page'] : '';
  if ($new_page = implode(',', pager_load_array($page_new[$element], $element, explode(',', $page)))) {
    $parameters['page'] = $new_page;
  }

  $query = array();
  if (count($parameters)) {
    $query = drupal_get_query_parameters($parameters, array());
  }
  if ($query_pager = pager_get_query_parameters()) {
    $query = array_merge($query, $query_pager);
  }

  // Set each pager link title
  if (!isset($attributes['title'])) {
    static $titles = NULL;
    if (!isset($titles)) {
      $titles = array(
        t('« first') => t('Go to first page'),
        t('‹ previous') => t('Go to previous page'),
        t('next ›') => t('Go to next page'),
        t('last »') => t('Go to last page'),
      );
    }
    if (isset($titles[$text])) {
      $attributes['title'] = $titles[$text];
    }
    elseif (is_numeric($text)) {
      $attributes['title'] = t('Go to page @number', array('@number' => $text));
    }
  }

  // @todo l() cannot be used here, since it adds an 'active' class based on the
  //   path only (which is always the current path for pager links). Apparently,
  //   none of the pager links is active at any time - but it should still be
  //   possible to use l() here.
  // @see http://drupal.org/node/1410574
  $attributes['href'] = url($_GET['q'], array('query' => $query));
  return '<a' . drupal_attributes($attributes) . '>' . check_plain($text) . '</a>';
}

/**
 * Overrides theme_pager().
 */
function bear_skin_pager(&$variables) {
  $tags = $variables['tags'];
  $element = $variables['element'];
  $parameters = $variables['parameters'];
  $quantity = $variables['quantity'];
  global $pager_page_array, $pager_total;

  // Calculate various markers within this pager piece:
  // Middle is used to "center" pages around the current page.
  $pager_middle = ceil($quantity / 2);
  // current is the page we are currently paged to
  $pager_current = $pager_page_array[$element] + 1;
  // first is the first page listed by this pager piece (re quantity)
  $pager_first = $pager_current - $pager_middle + 1;
  // last is the last page listed by this pager piece (re quantity)
  $pager_last = $pager_current + $quantity - $pager_middle;
  // max is the maximum page number
  $pager_max = $pager_total[$element];
  // End of marker calculations.

  // Prepare for generation loop.
  $i = $pager_first;
  if ($pager_last > $pager_max) {
    // Adjust "center" if at end of query.
    $i = $i + ($pager_max - $pager_last);
    $pager_last = $pager_max;
  }
  if ($i <= 0) {
    // Adjust "center" if at start of query.
    $pager_last = $pager_last + (1 - $i);
    $i = 1;
  }
  // End of generation loop preparation.

  $li_first = theme('pager_first', array(
    'text' => (isset($tags[0]) ? $tags[0] : t('« first')),
    'element' => $element,
    'parameters' => $parameters
  ));
  $li_previous = theme('pager_previous', array(
    'text' => (isset($tags[1]) ? $tags[1] : t('‹ previous')),
    'element' => $element,
    'interval' => 1,
    'parameters' => $parameters
  ));
  $li_next = theme('pager_next', array(
    'text' => (isset($tags[3]) ? $tags[3] : t('next ›')),
    'element' => $element,
    'interval' => 1,
    'parameters' => $parameters
  ));
  $li_last = theme('pager_last', array(
    'text' => (isset($tags[4]) ? $tags[4] : t('last »')),
    'element' => $element,
    'parameters' => $parameters
  ));

  if ($pager_total[$element] > 1) {
    if ($li_first) {
      $items[] = array(
        'class' => array('pager__item', 'pager--first'),
        'data' => $li_first,
      );
    }
    if ($li_previous) {
      $items[] = array(
        'class' => array('pager__item', 'pager--previous'),
        'data' => $li_previous,
      );
    }

    // When there is more than one page, create the pager list.
    if ($i != $pager_max) {
      if ($i > 1) {
        $items[] = array(
          'class' => array('pager__item', 'pager--ellipsis'),
          'data' => '…',
        );
      }
      // Now generate the actual pager piece.
      for (; $i <= $pager_last && $i <= $pager_max; $i++) {
        if ($i < $pager_current) {
          $items[] = array(
            'class' => array('pager__item'),
            'data' => theme('pager_previous', array(
              'text' => $i,
              'element' => $element,
              'interval' => ($pager_current - $i),
              'parameters' => $parameters
            )),
          );
        }
        if ($i == $pager_current) {
          $items[] = array(
            'class' => array('pager__item', 'pager--current'),
            'data' => $i,
          );
        }
        if ($i > $pager_current) {
          $items[] = array(
            'class' => array('pager__item'),
            'data' => theme('pager_next', array(
              'text' => $i,
              'element' => $element,
              'interval' => ($i - $pager_current),
              'parameters' => $parameters
            )),
          );
        }
      }
      if ($i < $pager_max) {
        $items[] = array(
          'class' => array('pager__item', 'pager--ellipsis'),
          'data' => '…',
        );
      }
    }
    // End generation.
    if ($li_next) {
      $items[] = array(
        'class' => array('pager__item', 'pager--next'),
        'data' => $li_next,
      );
    }
    if ($li_last) {
      $items[] = array(
        'class' => array('pager__item', 'pager--last'),
        'data' => $li_last,
      );
    }
    return theme('item_list', array(
      'items' => $items,
      'attributes' => array('class' => array('pager')),
    ));
  }
}

/**
 * Implements theme_breadcrumb()
 * 1. Make the breadcrumbs more accessible using WAI standards
 * 2. Add SMACCS / BEM style CSS classes to HTML elements in breadcrumbs
 */
function bear_skin_breadcrumb(&$variables) {
  $breadcrumb = $variables['breadcrumb'];

  $crumbs = '';
  if (!empty($breadcrumb)) {
    $separator = theme_get_setting('zen_breadcrumb_separator');
    $crumbs = '<nav role="navigation" aria-label="breadcrumbs" class="wrapper--breadcrumbs">' . "\n";
    $crumbs .= '<h2 class="visually-hidden" id="breadcrumbLabel">' . t('You are here:') . '</h2>';
    $crumbs .= '<ul class="breadcrumbs" aria-labelledby="breadcrumbLabel">' . "\n";
    foreach ($breadcrumb as $value) {
      $value = str_replace('<a', '<a class="breadcrumbs__link"', $value);
      // the breadcrumb divider has aria-hidden, which should make it ignored by screen readers
      $crumbs .= '<li class="breadcrumbs__item">' . $value . ' <span class="breadcrumbs__divider" aria-hidden="true">' . $separator . '</span></li>' . "\n";
    }
    $crumbs .= '</ul>' . "\n";
    $crumbs .= '</nav>' . "\n";
  }
  return $crumbs;
}

/**
 * Implements theme_menu_local_tasks()
 * 1. Make the tabs more accessible using WAI standards
 * 2. Add SMACCS / BEM style CSS classes to HTML elements in tab containers
 */
function bear_skin_menu_local_tasks(&$variables) {
  $output = '';

  // Add theme hook suggestions for tab type.
  foreach (array('primary', 'secondary') as $type) {
    if (!empty($variables[$type])) {
      foreach (array_keys($variables[$type]) as $key) {
        if (isset($variables[$type][$key]['#theme']) && ($variables[$type][$key]['#theme'] == 'menu_local_task' || is_array($variables[$type][$key]['#theme']) && in_array('menu_local_task', $variables[$type][$key]['#theme']))) {
          $variables[$type][$key]['#theme'] = array(
            'menu_local_task__' . $type,
            'menu_local_task',
          );
        }
      }
    }
  }

  if (!empty($variables['primary'])) {
    $variables['primary']['#prefix'] = '<h2 class="visually-hidden" id="primaryTabsLabel">' . t('Primary tabs') . '</h2>';
    $variables['primary']['#prefix'] .= '<ul class="tabs-primary tabs primary" role="tablist" aria-labelledby="primaryTabsLabel">';
    $variables['primary']['#suffix'] = '</ul>';
    $output .= drupal_render($variables['primary']);
  }
  if (!empty($variables['secondary'])) {
    $variables['secondary']['#prefix'] = '<h2 class="visually-hidden" id="secondaryTabsLabel">' . t('Secondary tabs') . '</h2>';
    $variables['secondary']['#prefix'] .= '<ul class="tabs-secondary tabs secondary" role="tablist" aria-labelledby="secondarTabsLabel">';
    $variables['secondary']['#suffix'] = '</ul>';
    $output .= drupal_render($variables['secondary']);
  }

  return $output;
}

/**
 * Implements theme_menu_local_task()
 * 1. Make the tab items more accessible using WAI standards
 * 2. Add SMACCS / BEM style CSS classes to HTML elements in individual tabs
 */
function bear_skin_menu_local_task($variables) {
  $type = $class = FALSE;

  $link = $variables['element']['#link'];
  $link_text = $link['title'];

  // Check for tab type set in zen_menu_local_tasks().
  if (is_array($variables['element']['#theme'])) {
    $type = in_array('menu_local_task__secondary', $variables['element']['#theme']) ? 'tabs-secondary' : 'tabs-primary';
  }

  // Add SMACSS-style class names.
  if ($type) {
    $link['localized_options']['attributes']['class'][] = $type . '__tab-link';
    $class = $type . '__tab';
  }

  $link['localized_options']['attributes']['role'] = 'tab';

  if (!empty($variables['element']['#active'])) {
    // Add text to indicate active tab for non-visual users.
    $active = ' <span class="visually-hidden">' . t('(active tab)') . '</span>';

    // If the link does not contain HTML already, check_plain() it now.
    // After we set 'html'=TRUE the link will not be sanitized by l().
    if (empty($link['localized_options']['html'])) {
      $link['title'] = check_plain($link['title']);
    }
    $link['localized_options']['html'] = TRUE;
    $link_text = t('!local-task-title!active', array(
      '!local-task-title' => $link['title'],
      '!active' => $active,
    ));

    if (!$type) {
      $class = 'active';
    }
    else {
      $link['localized_options']['attributes']['class'][] = 'is-active';
      $class .= ' is-active';
    }
  }

  return '<li' . ($class ? ' class="' . $class . '"' : '') . '>' . l($link_text, $link['href'], $link['localized_options']) . "</li>\n";
}

/**
 * Implements theme_field()
 * In order to get to the least amount of CSS nesting possible:
 * 1. Very specific classes for fields
 *    examples:
 *      the 'body' field of a 'page' node in full view has a class of node-page-body
 *      the 'date' field of a 'event' node in teaser view is node-event-date-teaser
 * 2. SMACCS / BEM style CSS classes for multiple field groups, single fields, and field labels
 *    examples:
 *      the label of a 'date' field for an 'event' node is node-event-date__label
 *      if a field has multiple items, they are inside a div with class node-event-date__group
 *      each item of a field has a class like node-event-date__content
 * 3. Add aria labels for WAI accessibility
 */
function bear_skin_field(&$variables) {
  // create a CSS class
  // the type of object this field is inside
  if ($variables['element']['#entity_type'] === 'node') {
    $object_class = 'node-' . $variables['element']['#object']->type;
  }
  else {
    $object_class = $variables['element']['#entity_type'];
  }
  $object_class = $object_class . '-' . $variables['field_name_css'];
  $object_class = ($variables['element']['#view_mode'] === 'full') ? $object_class : $object_class . '-' . $variables['element']['#view_mode'];

  $variables['classes'] = $variables['classes'] . ' ' . $object_class;
  $output = '';

  // Render the label, if it's not hidden.
  if (!$variables['label_hidden']) {
    $output .= '<div class="field-label ' . $object_class . '__label"' . $variables['title_attributes'] . ' id="label-for-' . $object_class . '">' . $variables['label'] . ':&nbsp;</div>';
  }

  // Render the items
  // Add a group wrapper if there is more than one item
  if (count($variables['items']) > 1) {
    $output .= '<div class="field-items ' . $object_class . '__group"' . $variables['content_attributes'] . '>';
    foreach ($variables['items'] as $delta => $item) {
      $classes = 'field-item ' . $object_class . '__content';
      $output .= '<div class="' . $classes . '"' . $variables['item_attributes'][$delta] . '>' . drupal_render($item) . '</div>';
    }
    $output .= '</div>';
  }
  else {
    $item = reset($variables['items']);
    $classes = 'field-item ' . $object_class . '__content';
    $output .= '<div class="' . $classes . '"' . $variables['item_attributes'][0] . '>' . drupal_render($item) . '</div>';
  }

  // Render the top-level DIV.
  if (!$variables['label_hidden']) {
    $output = '<div class="' . $variables['classes'] . '"' . $variables['attributes'] . ' aria-labelledby="label-for-' . $object_class . '">' . $output . '</div>';
  }
  else {
    $output = '<div class="' . $variables['classes'] . '"' . $variables['attributes'] . '>' . $output . '</div>';
  }

  return $output;
}

/**
 * Implements hook_css_alter().
 * 1. Remove some of Drupal's default CSS
 * 2. Force insertion of CSS as <link> tags instead of @import
 *    if CSS aggregation is turned off
 */
function bear_skin_css_alter(&$css) {
  // remove drupal's default message css
  unset($css['modules/system/system.messages.css']);
  unset($css['modules/system/system.menus.css']);
  unset($css[drupal_get_path('module', 'views') . '/css/views.css']);

  // if local fetcher environment, include css as link tags
  // this allows livereload / guard to inject css
  $fetcher_environment = variable_get('fetcher_environment', NULL);
  if (!is_null($fetcher_environment) && $fetcher_environment === 'local') {
    foreach ($css as $key => $value) {
      if (file_exists($value['data'])) {
        $css[$key]['preprocess'] = FALSE;
      }
    }
  }
}
