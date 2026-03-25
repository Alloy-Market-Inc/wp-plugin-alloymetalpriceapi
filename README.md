# AlloyMetalPriceAPI

WordPress plugin that pulls live precious metal prices from the Aurify API and exposes them through shortcodes for inline prices, price tables, calculators, and offer cards.

## What This Plugin Does

This plugin provides shortcode-driven UI and text outputs for:

- Live metal spot prices
- Purity-adjusted gold price tables
- Interactive melt-value calculators
- Offer comparison cards
- Server-rendered calculated values you can drop inline inside post content

The plugin currently fetches prices from:

- `https://aurify.app/api/v1/metal-price?metalType=<metal>`

## Included Shortcodes

- `[metalpriceapi]`
- `[metal_price_table]`
- `[metal_calculator]`
- `[metal_offer_card]`
- `[metal_price_calc]`

## Installation

1. Place the plugin folder in:
   `wp-content/plugins/AlloyMetalPriceAPI`
2. Activate the plugin in WordPress.
3. Make sure the built frontend assets exist:
   - `assets/dist/css/plugin.css`
   - `assets/dist/js/alloy-calculator.js`

## Asset Loading Behavior

The plugin registers its stylesheet and script on `wp_enqueue_scripts` and conditionally enqueues them only on singular content that contains supported shortcodes.

Shortcodes that enqueue CSS:

- `metalpriceapi`
- `metal_price_table`
- `metal_calculator`
- `metal_offer_card`

Shortcodes that enqueue JS:

- `metal_calculator`
- `metal_offer_card`

`[metal_price_calc]` returns a plain `<span>` and does not rely on the plugin stylesheet or script.
The underscore version is the canonical shortcode tag. The older dashed form is accepted only as a backward-compatible alias.

## Development

This plugin uses Tailwind CSS v4 for frontend styles.

Install dependencies:

```bash
npm install
```

Build CSS once:

```bash
npm run build:css
```

Watch CSS during development:

```bash
npm run watch:css
```

Full build shortcut:

```bash
npm run build
```

Source CSS lives in:

- [`assets/src/css/plugin.css`](/Users/jamescook/REPOS/LOCAL-SITES/alloy-marcom/app/public/wp-content/plugins/AlloyMetalPriceAPI/assets/src/css/plugin.css)

Built assets live in:

- [`assets/dist/css/plugin.css`](/Users/jamescook/REPOS/LOCAL-SITES/alloy-marcom/app/public/wp-content/plugins/AlloyMetalPriceAPI/assets/dist/css/plugin.css)
- [`assets/dist/js/alloy-calculator.js`](/Users/jamescook/REPOS/LOCAL-SITES/alloy-marcom/app/public/wp-content/plugins/AlloyMetalPriceAPI/assets/dist/js/alloy-calculator.js)

## Shortcode Reference

### `[metalpriceapi]`

Outputs a single live metal price wrapped in a `<span>`.

Default usage:

```text
[metalpriceapi]
```

Default behavior:

- `symbol="XAU"`
- `unit="ounce"`

Supported attributes:

- `symbol`
  - Supported values: `XAU`, `XAG`, `XPT`, `XPD`
  - Maps to:
    - `XAU` = gold
    - `XAG` = silver
    - `XPT` = platinum
    - `XPD` = palladium
- `unit`
  - Supported values: `gram`, `ounce`, `kilogram`
  - Invalid values fall back to `gram`

Examples:

```text
[metalpriceapi symbol="XAU" unit="gram"]
[metalpriceapi symbol="XAG" unit="ounce"]
[metalpriceapi symbol="XPT" unit="kilogram"]
[metalpriceapi symbol="XPD" unit="gram"]
```

Output notes:

- Returns a plain numeric value, not a currency-formatted string with forced trailing zeros
- Wrapped in a `<span>` with plugin font classes
- Returns an empty string if the API request fails

### `[metal_price_table]`

Outputs a gold price table based on live `24K` spot price and a selected purity.

Default usage:

```text
[metal_price_table]
```

Default behavior:

- `title="Gold Price Table"`
- `purity="24K"`
- `show_live_box="false"`

Supported attributes:

- `title`
  - Sets the table heading text
- `purity`
  - Supported values: `1K` through `24K`
  - `K` suffix is optional
  - Invalid values fall back to `24`
- `show_live_box`
  - Boolean-like values accepted: `true`, `1`, `yes`, `on`
  - Any other value is treated as `false`
  - When enabled, shows a separate live summary card beside the table

What the table shows:

- Price per gram
- Price per ounce
- Price per troy ounce
- Price per kilo
- Footer showing current `24K` spot price per gram
- Updated time label using the site time format

Examples:

```text
[metal_price_table]
[metal_price_table purity="14K"]
[metal_price_table purity="18" title="18K Gold Price Table"]
[metal_price_table purity="14K" show_live_box="true"]
[metal_price_table purity="10K" title="10K Gold Value" show_live_box="yes"]
```

Unavailable-state behavior:

- If the API request fails, the table still renders
- Table values show `Unavailable`
- The live box, if enabled, also renders an unavailable state

### `[metal_calculator]`

Outputs the interactive gold calculator UI.

Default usage:

```text
[metal_calculator]
```

Default behavior:

- `title="Gold Calculator"`
- `purity="14K"`

Supported attributes:

- `title`
  - Sets the calculator heading
- `purity`
  - Sets the default selected karat
  - Accepts `1K` through `24K`
  - Invalid values fall back to `14`

Calculator UI includes:

- Live base gold price per gram
- Karat selector
- Weight unit selector
  - `grams`
  - `ounces`
  - `pennyweight`
- Weight input
- Computed outputs:
  - Current Market Value
  - Average Pawn Shop Offer
  - Alloy's Estimated Offer

Offer logic:

- Market value = `spot price per gram × purity factor × weight in grams`
- Pawn shop offer = `market value × 0.4`
- Alloy estimate:
  - `0.85` for `22K` and `24K`
  - `0.7` for all other karats

Examples:

```text
[metal_calculator]
[metal_calculator purity="24K"]
[metal_calculator title="14K Gold Melt Value Calculator" purity="14K"]
[metal_calculator title="18K Gold Calculator" purity="18"]
```

API failure behavior:

- If the live gold price request fails, the calculator still renders
- Base price becomes `0`
- Calculated values start from zero until refreshed page content gets valid data

### `[metal_offer_card]`

Outputs a live offer comparison card for a selected purity.

Default usage:

```text
[metal_offer_card]
```

Default behavior:

- `title="Metal Price Offer Card"`
- `purity="24K"`

Supported attributes:

- `title`
  - Accepted by the shortcode
  - Currently not displayed in the rendered markup
- `purity`
  - Accepts `1K` through `24K`
  - Invalid values fall back to `24`

What the card shows:

- Purity-adjusted spot price per gram
- Pawn shop offer per gram
- Alloy estimated offer per gram
- Informational note
- AJAX refresh button

Offer logic:

- Spot = `24K spot × purity factor`
- Pawn = `spot × 0.4`
- Alloy =
  - `spot × 0.85` for `22K` and `24K`
  - `spot × 0.7` for all other karats

Examples:

```text
[metal_offer_card]
[metal_offer_card purity="14K"]
[metal_offer_card purity="22K"]
[metal_offer_card title="14K Offer Card" purity="14"]
```

Refresh behavior:

- Uses `admin-ajax.php`
- Action: `alloy_metal_price_api_refresh_offer_card`
- Protected by nonce: `alloy_metal_price_api_refresh`

API failure behavior:

- Spot, pawn, and alloy values render as `Unavailable`

### `[metal_price_calc]`

Outputs a server-rendered formatted currency value in a `<span>` using calculator-style math from shortcode attributes.

This shortcode is useful when you want to print a single computed value inline inside headings, paragraphs, cards, or CMS copy.

Default usage:

```text
[metal_price_calc]
```

Default behavior:

- `purity="14K"`
- `weight="0"`
- `weight_unit="grams"`
- `output="market"`

Supported attributes:

- `purity`
  - Accepts `1K` through `24K`
  - `K` suffix optional
  - Invalid values fall back to `14`
- `weight`
  - Any numeric value
  - Negative or invalid values are coerced to `0`
- `weight_unit`
  - Supported values:
    - `gram`
    - `grams`
    - `ounce`
    - `ounces`
    - `pennyweight`
    - `pennyweights`
    - `dwt`
  - Invalid values fall back to `grams`
- `output`
  - Supported values:
    - `market`
    - `market_value`
    - `pawn`
    - `pawn_offer`
    - `pawn_shop_offer`
    - `alloy`
    - `alloy_offer`
    - `alloy_estimated_offer`
  - Invalid values fall back to `market`

Examples:

```text
[metal_price_calc purity="14K" weight="10" weight_unit="grams" output="market"]
[metal_price_calc purity="14K" weight="10" weight_unit="grams" output="pawn"]
[metal_price_calc purity="14K" weight="10" weight_unit="grams" output="alloy"]
[metal_price_calc purity="18K" weight="0.5" weight_unit="ounces" output="market_value"]
[metal_price_calc purity="24K" weight="4" weight_unit="dwt" output="alloy_offer"]
```

Usage inside sentence copy:

```text
A 10 gram 14K gold item has a live melt value of [metal_price_calc purity="14K" weight="10" weight_unit="grams" output="market"].
```

Output behavior:

- Always returns a formatted currency string inside a `<span>`
- Returns `Unavailable` inside a `<span>` if the API request fails

## Purity Rules

Across the gold-related shortcodes:

- `purity` accepts whole-number karats from `1` to `24`
- Both `14` and `14K` are valid
- Values outside that range fall back to the shortcode-specific default

Default purity fallback by shortcode:

- `[metal_price_table]` → `24`
- `[metal_calculator]` → `14`
- `[metal_offer_card]` → `24`
- `[metal_price_calc]` → `14`

## Weight Conversion Rules

Where applicable, weight is converted as follows:

- `grams` = no conversion
- `ounces` = `31.1035` grams
- `pennyweight` = `1.55517384` grams

## Error Handling

The plugin makes live API requests on page render or refresh. Different shortcodes handle failures differently:

- `[metalpriceapi]` returns an empty string on failure
- `[metal_price_table]` renders an unavailable table
- `[metal_calculator]` renders with base price `0`
- `[metal_offer_card]` renders unavailable values
- `[metal_price_calc]` returns `<span>Unavailable</span>`

## File Map

Plugin bootstrap:

- [`alloy-metal-price-api.php`](/Users/jamescook/REPOS/LOCAL-SITES/alloy-marcom/app/public/wp-content/plugins/AlloyMetalPriceAPI/alloy-metal-price-api.php)

Core services:

- [`includes/class-alloy-metal-price-api-plugin.php`](/Users/jamescook/REPOS/LOCAL-SITES/alloy-marcom/app/public/wp-content/plugins/AlloyMetalPriceAPI/includes/class-alloy-metal-price-api-plugin.php)
- [`includes/class-alloy-metal-price-api-assets.php`](/Users/jamescook/REPOS/LOCAL-SITES/alloy-marcom/app/public/wp-content/plugins/AlloyMetalPriceAPI/includes/class-alloy-metal-price-api-assets.php)
- [`includes/class-alloy-metal-price-api-client.php`](/Users/jamescook/REPOS/LOCAL-SITES/alloy-marcom/app/public/wp-content/plugins/AlloyMetalPriceAPI/includes/class-alloy-metal-price-api-client.php)
- [`includes/class-alloy-metal-price-api-logger.php`](/Users/jamescook/REPOS/LOCAL-SITES/alloy-marcom/app/public/wp-content/plugins/AlloyMetalPriceAPI/includes/class-alloy-metal-price-api-logger.php)

Shortcodes:

- [`includes/shortcodes/class-alloy-metal-price-api-metal-price-shortcode.php`](/Users/jamescook/REPOS/LOCAL-SITES/alloy-marcom/app/public/wp-content/plugins/AlloyMetalPriceAPI/includes/shortcodes/class-alloy-metal-price-api-metal-price-shortcode.php)
- [`includes/shortcodes/class-alloy-metal-price-api-metal-price-table-shortcode.php`](/Users/jamescook/REPOS/LOCAL-SITES/alloy-marcom/app/public/wp-content/plugins/AlloyMetalPriceAPI/includes/shortcodes/class-alloy-metal-price-api-metal-price-table-shortcode.php)
- [`includes/shortcodes/class-alloy-metal-price-api-metal-calculator-shortcode.php`](/Users/jamescook/REPOS/LOCAL-SITES/alloy-marcom/app/public/wp-content/plugins/AlloyMetalPriceAPI/includes/shortcodes/class-alloy-metal-price-api-metal-calculator-shortcode.php)
- [`includes/shortcodes/class-alloy-metal-price-api-metal-offer-card-shortcode.php`](/Users/jamescook/REPOS/LOCAL-SITES/alloy-marcom/app/public/wp-content/plugins/AlloyMetalPriceAPI/includes/shortcodes/class-alloy-metal-price-api-metal-offer-card-shortcode.php)
- [`includes/shortcodes/class-alloy-metal-price-api-metal-price-calc-shortcode.php`](/Users/jamescook/REPOS/LOCAL-SITES/alloy-marcom/app/public/wp-content/plugins/AlloyMetalPriceAPI/includes/shortcodes/class-alloy-metal-price-api-metal-price-calc-shortcode.php)

## Quick Copy/Paste Examples

Inline live gold price:

```text
Current gold price per ounce: [metalpriceapi symbol="XAU" unit="ounce"]
```

14K table with summary card:

```text
[metal_price_table purity="14K" title="14K Gold Price Today" show_live_box="true"]
```

14K interactive calculator:

```text
[metal_calculator title="14K Gold Calculator" purity="14K"]
```

14K offer card:

```text
[metal_offer_card purity="14K"]
```

Inline melt value:

```text
[metal_price_calc purity="14K" weight="10" weight_unit="grams" output="market"]
```

Inline pawn offer:

```text
[metal_price_calc purity="14K" weight="10" weight_unit="grams" output="pawn"]
```

Inline Alloy estimate:

```text
[metal_price_calc purity="14K" weight="10" weight_unit="grams" output="alloy"]
```
