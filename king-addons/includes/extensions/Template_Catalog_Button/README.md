# King Addons Template Catalog Button Extension

## Overview
This module adds a button to the Elementor editor that opens the King Addons template catalog in a new tab.

## Architecture

### File Structure
```
includes/extensions/Template_Catalog_Button/
├── Template_Catalog_Button.php          # Main controller class
└── assets/
    ├── template-catalog-button.js       # JavaScript for editor
    └── template-catalog-button.css      # Button styles
```

### Main Components

#### 1. Template_Catalog_Button.php
- **Purpose**: Main controller class
- **Functions**:
  - Module initialization management
  - Asset loading only for Elementor editor
  - Data localization for JavaScript
  - Button text determination based on version (Free/Pro)

#### 2. template-catalog-button.js
- **Purpose**: Button addition and management logic
- **Features**:
  - Uses proper Elementor hooks
  - Multiple button placement strategies
  - Panel change observer
  - Fail-safe initialization mechanisms

#### 3. template-catalog-button.css
- **Purpose**: Button styling
- **Features**:
  - Responsive design
  - Dark theme support
  - Animations and transitions
  - Accessibility features

## Integration in Core.php

The module is loaded in the "Templates Catalog" section:

```php
// Templates Catalog
if (KING_ADDONS_EXT_TEMPLATES_CATALOG) {
    require_once(KING_ADDONS_PATH . 'includes/TemplatesMap.php');
    require_once(KING_ADDONS_PATH . 'includes/extensions/Templates/CollectionsMap.php');
    require_once(KING_ADDONS_PATH . 'includes/extensions/Templates/Templates.php');
    
    // Template Catalog Button for Elementor Editor
    require_once(KING_ADDONS_PATH . 'includes/extensions/Template_Catalog_Button/Template_Catalog_Button.php');
    Template_Catalog_Button::instance();
}
```

## Elementor Hooks

### Used hooks:
- `elementor/editor/before_enqueue_scripts` - for JS loading
- `elementor/editor/after_enqueue_styles` - for CSS loading

### JavaScript hooks:
- `elementor:init` - main initialization
- `panel/open_editor/widget` - when widget editor opens
- `navigator/init` - when navigator initializes

## Button Placement Strategies

1. **Content Area** (priority 1): In the content area where "Drag widget here" is shown
2. **Empty Containers** (priority 2): In empty containers and sections
3. **Overlay** (fallback): Central overlay as last resort

## Implementation Features

### Advantages of the new approach:
1. **Intuitive placement**: Button is in the content area where users expect to see options
2. **Proper hooks**: Uses editor-specific hooks
3. **Smart placement**: Automatically finds suitable empty areas
4. **Fail-safe**: Multiple initialization and placement methods
5. **Performance**: Loads only in editor
6. **Version support**: Automatically detects Free/Pro

### Content area placement:
- Button appears in "Drag widget here" area with attractive design
- Includes "Start with a Template" title and description
- Uses modern design with King Addons brand colors
- Responsive design for different screen sizes
- Clean, professional appearance that fits with Elementor's UI

## Localization

The `window.kingAddonsTemplateCatalog` variable contains:
- `templateCatalogUrl` - Template catalog URL
- `templatesEnabled` - Template module status
- `buttonText` - Button text (Free Templates/Templates Pro)
- `nonce` - Security nonce

## Testing

### How to test:
1. Open any page in Elementor editor
2. Look for the "Free Templates" or "Templates Pro" button in the content area
3. Click should open template catalog in new tab

### Placement areas to check:
- Content area where "Drag widget here" appears (primary placement)
- Empty sections and containers
- Overlay fallback if content area is unavailable

## Compatibility

- **Elementor**: 3.19.0+
- **PHP**: 7.4+
- **WordPress**: 6.0+
- **Browsers**: All modern browsers
