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
- `[metal_calculator_layout]`
- `[metal_offer_card]`
- `[metal_price_compare]`
- `[metal_price_calc]`
- `[metal_payout_comparison]`
- `[metal_spot_ticker]`
- `[metal_budget_buy_widget]`
- `[metal_goldbar_live_melt_table]`
- `[metal_fractional_goldbar_module]`
- `[metal_standard_goldbar_module]`
- `[conversion_rate_calculator]`

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
- `metal_calculator_layout`
- `metal_price_compare`
- `metal_payout_comparison`
- `metal_spot_ticker`
- `metal_budget_buy_widget`
- `metal_goldbar_live_melt_table`
- `metal_fractional_goldbar_module`
- `metal_standard_goldbar_module`
- `conversion_rate_calculator`
- `metal_offer_card`

Shortcodes that enqueue JS:

- `metal_calculator`
- `metal_calculator_layout`
- `metal_offer_card`
- `metal_budget_buy_widget`

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

## WP Engine Deploy

This plugin has a plugin-only WP Engine deploy flow using SSH Gateway and `rsync`, documented in:

- [`WPE-DEPLOY.md`](/Users/jamescook/REPOS/LOCAL-SITES/alloy-marcom/app/public/wp-content/plugins/AlloyMetalPriceAPI/WPE-DEPLOY.md)

Use the deploy script:

```bash
./scripts/deploy-wpe-plugin.sh --dry-run
./scripts/deploy-wpe-plugin.sh
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

Outputs a live metal price table based on the selected `metal` and `purity`.

Default usage:

```text
[metal_price_table]
```

Default behavior:

- `title="Gold Price Table"`
- `metal="gold"`
- `purity="24K"`
- `data="default"`
- `show_live_box="false"`

Supported attributes:

- `title`
  - Sets the table heading text
- `metal`
  - Supported values: `gold`, `silver`, `platinum`, `palladium`
  - Invalid values fall back to `gold`
- `purity`
  - For `gold`, supported values are `1K` through `24K`
  - `K` suffix is optional for gold
  - For `silver`, `platinum`, and `palladium`, use a decimal purity like `0.925`, `0.950`, or `0.9995`
  - Invalid gold values fall back to `24`
  - Invalid non-gold values fall back to `0.9999`
  - If `purity` is omitted, the default purity is still used for calculations but the purity label is left out of the visible row text
  - If `purity` is omitted, the live summary card subtext is also hidden
- `data`
  - Default value: `default`
  - `default` shows the standard purity-based rows
  - `gold bars` switches the table data to five `24K` gold bar spot-price rows
  - Common variations like `gold-bar`, `gold_bars`, `bars`, and `bar` are also accepted
- `show_live_box`
  - Boolean-like values accepted: `true`, `1`, `yes`, `on`
  - Any other value is treated as `false`
  - When enabled, shows a separate live summary card beside the table

What the table shows:

- Price per gram
- Price per ounce
- Price per troy ounce
- Price per kilo
- Footer showing the current spot price per gram for the selected metal
- Updated time label using the site time format

Gold bar data mode:

- Keeps the same table layout and styling
- Replaces the default rows with:
  - `1 oz <metal> Bar`
  - `5 oz <metal> Bar`
  - `10 oz <metal> Bar`
  - `100 g <metal> Bar`
  - `1 kg <metal> Bar`
- Uses live spot pricing for the selected metal
- If the default title is still in use, it changes to `<metal> Bar Spot Prices`

Examples:

```text
[metal_price_table]
[metal_price_table purity="14K"]
[metal_price_table metal="silver" purity="0.925"]
[metal_price_table metal="platinum" purity="0.950" show_live_box="true"]
[metal_price_table purity="18" title="18K Gold Price Table"]
[metal_price_table data="gold bars"]
[metal_price_table metal="silver" purity="0.999" data="gold bars"]
[metal_price_table data="gold-bar" show_live_box="true"]
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
- `metal="gold"`
- `classring="false"`

Supported attributes:

- `title`
  - Sets the calculator heading
- `purity`
  - For `gold`, accepts `1K` through `24K`
  - For `silver` and `palladium`, use a decimal purity like `0.925` or `0.9995`
  - For `platinum`, the calculator uses preset fineness options: `999.5`, `999`, `950`, `900`, and `850`
  - Invalid gold values fall back to `14`
  - Invalid non-gold values fall back to `0.9999`
- `metal`
  - Supported values: `gold`, `silver`, `platinum`, `palladium`
  - Invalid values fall back to `gold`
- `classring`
  - Supported values: `true`, `false`
  - Default is `false`
  - When `true`, the calculator shows a stone-material selector for class rings

Calculator UI includes:

- Live base metal price per gram
- Purity selector
- Weight unit selector
  - `grams`
  - `ounces`
  - `pennyweight`
- Weight input
- Optional class-ring stone selector with tooltip guidance
- Computed outputs:
  - Current Market Value
  - Average Pawn Shop Offer
  - Alloy's Estimated Offer
- Results stay hidden until Calculate Value is clicked
- Changing fields does not calculate or submit until Calculate Value is clicked again

Metal behavior:

- `gold` keeps the gold-specific karat labeling
- `silver` and `palladium` use the selected `metal` price and a decimal purity input field
- `platinum` uses the selected `metal` price and a preset purity selector

Offer logic:

- Market value = `spot price per gram × purity factor × weight in grams`
- Pawn shop offer = `market value × 0.4`
- Alloy estimate:
  - `0.85` for `22K` and `24K`
  - `0.7` for all other karats
- When `classring="true"` and any stone option other than `No stone (metal-only)` is selected:
  - deduct `20%` of total weight
  - with a minimum deduction of `0.5 g`
  - and a maximum deduction of `3 g`
  - then calculate all values from the remaining metal-only grams

Examples:

```text
[metal_calculator]
[metal_calculator purity="24K"]
[metal_calculator purity="10K" classring="true"]
[metal_calculator metal="silver" purity="0.925"]
[metal_calculator metal="platinum" purity="0.950"]
[metal_calculator metal="palladium" purity="0.9995"]
[metal_calculator title="14K Gold Melt Value Calculator" purity="14K"]
[metal_calculator title="18K Gold Calculator" purity="18"]
```

API failure behavior:

- If the live gold price request fails, the calculator still renders
- Base price becomes `0`
- Calculated values remain hidden until Calculate Value is clicked and start from zero until refreshed page content gets valid data

Implementation note:

- `[metal_calculator]` and `[metal_calculator_layout]` both use the same shared calculator renderer so calculator markup can be updated in one place

### `[metal_calculator_layout]`

Outputs a two-column layout that includes the shared calculator, a current gold prices widget, and a gold karat marking guide.

Default usage:

```text
[metal_calculator_layout]
```

Default behavior:

- `title="Cash for Gold Calculator"`
- `purity="24K"`
- `metal="gold"`
- `right="default"`
- `classring="false"`

Supported attributes:

- `title`
  - Sets the calculator heading
- `purity`
  - For `gold`, accepts `1K` through `24K`
  - For `silver` and `palladium`, use a decimal purity like `0.925` or `0.9995`
  - For `platinum`, the calculator uses preset fineness options: `999.5`, `999`, `950`, `900`, and `850`
  - Invalid gold values fall back to `24`
  - Invalid non-gold values fall back to `0.9999`
- `metal`
  - Supported values: `gold`, `silver`, `platinum`, `palladium`
  - Invalid values fall back to `gold`
- `right`
  - Controls the content boxes in the right column
  - Supported values:
    - `default`
    - `14K`
  - Invalid values fall back to `default`
- `classring`
  - Supported values: `true`, `false`
  - Default is `false`
  - When `true`, the shared calculator shows the class-ring stone selector and deduction metadata

What the layout shows:

- Shared calculator UI
- One of two right-column content sets:
  - `gold` + `default`
    - current gold prices widget
    - general gold karat marking guide
  - `gold` + `14K`
    - 14K-specific current price widget
    - 14K-specific jewelry marking guide
  - `platinum`
    - current platinum prices widget
    - platinum fineness markings
  - `silver`
    - current silver prices widget
    - placeholder bottom-right content block
  - `palladium`
    - current palladium prices widget
    - placeholder bottom-right content block

Layout behavior notes:

- The top-right price box always updates to the selected `metal`
- The `right="14K"` variant is only used for gold
- For non-gold metals, the bottom-right box switches to metal-specific or placeholder content automatically
- `classring="true"` applies the same stone deduction logic used by `[metal_calculator]`

Examples:

```text
[metal_calculator_layout]
[metal_calculator_layout purity="14K"]
[metal_calculator_layout purity="10K" classring="true"]
[metal_calculator_layout metal="silver" purity="0.925"]
[metal_calculator_layout metal="platinum" purity="0.950"]
[metal_calculator_layout metal="palladium" purity="0.9995"]
[metal_calculator_layout purity="14K" right="14K"]
[metal_calculator_layout title="Cash for Gold Calculator" purity="18K"]
```

API failure behavior:

- The layout still renders
- Shared calculator starts from `0`
- Price widget values render as `$0.00`

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

### `[metal_price_compare]`

Outputs a compact live comparison card showing two metal prices per troy ounce.

Default usage:

```text
[metal_price_compare]
```

Default behavior:

- `metal_a="platinum"`
- `metal_b="gold"`
- `title=""`
- If no title is provided, the shortcode generates one like `Platinum vs Gold Price Today (Per Troy Ounce)`

Supported attributes:

- `metal_a`
  - Supported values: `gold`, `silver`, `platinum`, `palladium`
  - Invalid values fall back to `gold`
- `metal_b`
  - Supported values: `gold`, `silver`, `platinum`, `palladium`
  - Invalid values fall back to `gold`
- `title`
  - Optional custom heading for the comparison card

What the card shows:

- Title line
- First metal row with live `USD / troy oz` price
- Second metal row with live `USD / troy oz` price
- Updated status line

Examples:

```text
[metal_price_compare]
[metal_price_compare metal_a="silver" metal_b="gold"]
[metal_price_compare metal_a="palladium" metal_b="platinum"]
[metal_price_compare metal_a="platinum" metal_b="gold" title="Platinum vs Gold Price Today (Per Troy Ounce)"]
```

API failure behavior:

- The card still renders
- Unavailable rows display as `$—`
- The status line shows `Feed error`

### `[metal_payout_comparison]`

Outputs a linked payout comparison card showing spot, pawn-shop, and Alloy-estimated offers by karat.

Default usage:

```text
[metal_payout_comparison]
```

Default behavior:

- `title="Pawn Shop Payout per Gram Karat Comparison"`
- `link_url="https://thealloymarket.com/request-a-kit"`

Supported attributes:

- `title`
  - Sets the card heading text
- `link_url`
  - Sets the destination URL for the whole card

What the card shows:

- `24K`, `22K`, `18K`, `14K`, and `10K` rows
- Spot price per gram
- Pawn shop offer per gram
- Alloy estimated offer per gram
- Top-right refresh button
- Footnote text below the table
- Hidden current gold ounce-price output for reference

Offer logic:

- Spot = `24K spot × purity factor`
- Pawn = `spot × 0.4`
- Alloy =
  - `spot × 0.85` for `22K` and `24K`
  - `spot × 0.7` for all other karats

Examples:

```text
[metal_payout_comparison]
[metal_payout_comparison title="Gold Payout Comparison by Karat"]
[metal_payout_comparison link_url="https://thealloymarket.com/request-a-kit"]
[metal_payout_comparison title="Compare Gold Offers" link_url="https://thealloymarket.com/request-a-kit"]
```

API failure behavior:

- The card still renders
- All payout values show `Unavailable`
- The footnote changes to a temporary unavailable message

Refresh behavior:

- The top-right refresh button reloads the current page
- The card body remains linked to the configured `link_url`

### `[metal_spot_ticker]`

Outputs a compact live spot-metal ticker card showing ounce and gram pricing.

Default usage:

```text
[metal_spot_ticker]
```

Default behavior:

- `metal="gold"`
- `purity="24K"`
- `pill_text=""`

Supported attributes:

- `metal`
  - Supported values: `gold`, `silver`, `platinum`, `palladium`
  - Invalid values fall back to `gold`
- `purity`
  - Accepts `1K` through `24K`
  - `K` suffix optional
  - Invalid values fall back to `24`
- `pill_text`
  - Optional custom pill label
  - If omitted, the pill label is generated from `metal` and `purity`

What the card shows:

- Live spot price in `USD/oz`
- Live spot price in `USD/g`
- Updated time label

Examples:

```text
[metal_spot_ticker]
[metal_spot_ticker metal="gold" purity="24K"]
[metal_spot_ticker metal="gold" purity="14K"]
[metal_spot_ticker metal="silver" purity="24K"]
[metal_spot_ticker metal="platinum" purity="18K" pill_text="Live Spot Platinum"]
```

API failure behavior:

- The card still renders
- Both values show `Unavailable`
- The status line changes to `Updating…`

### `[metal_goldbar_live_melt_table]`

Outputs a responsive live gold bar melt-value component with a desktop table and a mobile card layout.

Default usage:

```text
[metal_goldbar_live_melt_table]
```

Default behavior:

- No attributes are required
- The desktop table is shown on tablet-sized screens and up
- The mobile card layout is shown on smaller screens
- All values are based on live `24K` gold spot pricing with `.9999` purity

What the shortcode shows:

- Desktop view:
  - a four-column table with gold bar size, weight, purity, and live melt value
- Mobile view:
  - one card per gold bar size
  - melt value shown at the top of each card
  - separate rows for grams, troy ounces, and purity

Included bar sizes:

- `1 g`
- `2.5 g`
- `5 g`
- `10 g`
- `20 g`
- `1 oz`
- `50 g`
- `100 g`
- `5 oz`
- `10 oz`
- `250 g`
- `500 g`
- `1 kg`
- `400 oz`

Examples:

```text
[metal_goldbar_live_melt_table]
```

API failure behavior:

- The shortcode still renders
- Melt values show `Unavailable`
- The hidden spot-price source also renders an unavailable value

### `[metal_fractional_goldbar_module]`

Outputs a responsive fractional gold bar melt-value module with a live spot-price header, a desktop table, and a mobile card layout.

Default usage:

```text
[metal_fractional_goldbar_module]
```

Default behavior:

- No attributes are required
- The header shows the current spot price in `USD / troy oz`
- The desktop table is shown on tablet-sized screens and up
- The mobile card layout is shown on smaller screens
- All melt values are based on live `24K` gold spot pricing with `.9999` purity

What the shortcode shows:

- Header:
  - module title
  - live spot price in `USD / troy oz`
- Desktop view:
  - a four-column table with bar size, grams, troy ounces, and live melt value
- Mobile view:
  - one card per bar size
  - melt value shown at the top of each card
  - separate rows for grams, troy ounces, and purity

Included bar sizes:

- `1 g`
- `2.5 g`
- `5 g`
- `10 g`
- `20 g`

Examples:

```text
[metal_fractional_goldbar_module]
```

API failure behavior:

- The shortcode still renders
- The spot-price header shows `Unavailable`
- Melt values show `Unavailable`

### `[metal_standard_goldbar_module]`

Outputs a responsive standard gold bar melt-value module with a live spot-price header, a desktop table, and a mobile card layout.

Default usage:

```text
[metal_standard_goldbar_module]
```

Default behavior:

- No attributes are required
- The header shows the current spot price in `USD / troy oz`
- The desktop table is shown on tablet-sized screens and up
- The mobile card layout is shown on smaller screens
- All melt values are based on live `24K` gold spot pricing with `.9999` purity

What the shortcode shows:

- Header:
  - module title
  - live spot price in `USD / troy oz`
- Desktop view:
  - a four-column table with bar size, grams, troy ounces, and live melt value
- Mobile view:
  - one card per bar size
  - melt value shown at the top of each card
  - separate rows for grams, troy ounces, and purity

Included bar sizes:

- `50 g`
- `100 g`
- `5 oz`

Examples:

```text
[metal_standard_goldbar_module]
```

API failure behavior:

- The shortcode still renders
- The spot-price header shows `Unavailable`
- Melt values show `Unavailable`

### `[conversion_rate_calculator]`

Outputs an interactive conversion rate calculator with one number input and two range sliders.

Default usage:

```text
[conversion_rate_calculator]
```

Default behavior:

- `title="Advanced Conversion Rate Calculator"`
- `sale_amount="1000"`
- `hard_cost="25"`
- `profit_margin="20"`

Supported attributes:

- `title`
  - Sets the calculator heading
- `sale_amount`
  - Sets the initial sale amount
  - Minimum effective value is `50`
- `hard_cost`
  - Sets the initial internal hard cost slider value
  - Clamped to `1` through `100`
- `profit_margin`
  - Sets the initial profit margin slider value
  - Clamped to `1` through `50`

What the calculator shows:

- Sale amount number input
- Internal hard cost range slider
- Profit margin range slider
- Calculate button
- Result box showing:
  - Sale Amount
  - Required Conversion Rate
  - Profit per Sale
  - Internal Hard Cost
  - Affiliate Commission per Sale
  - Total Cost per Lead
  - Total Profit

Examples:

```text
[conversion_rate_calculator]
[conversion_rate_calculator title="Advanced Conversion Rate Calculator"]
[conversion_rate_calculator sale_amount="1500" hard_cost="35" profit_margin="25"]
```

### `[metal_budget_buy_widget]`

Outputs a live budget-buy widget that estimates how many ounces of a selected metal a budget can buy after applying a premium percentage.

Default usage:

```text
[metal_budget_buy_widget]
```

Default behavior:

- `title=""`
- `metal="gold"`
- `budget="10000"`
- `premium="5"`

Supported attributes:

- `title`
  - Optional custom widget heading
  - If omitted, the heading is generated from `budget` and `metal`
- `metal`
  - Supported values: `gold`, `silver`, `platinum`, `palladium`
  - Invalid values fall back to `gold`
- `budget`
  - Any non-negative numeric value
  - Invalid values fall back to `10000`
- `premium`
  - Any numeric value from `0` to `30`
  - Invalid values fall back to `5`

Widget behavior:

- Shows the live spot price per troy ounce for the selected metal
- Lets the user edit the assumed premium percentage
- Recalculates the estimated ounces on input
- Uses the formula:
  - `budget / (spot per ounce × (1 + premium / 100))`

Examples:

```text
[metal_budget_buy_widget]
[metal_budget_buy_widget metal="silver"]
[metal_budget_buy_widget budget="5000" premium="3"]
[metal_budget_buy_widget metal="platinum" budget="25000" premium="7.5"]
[metal_budget_buy_widget title="How much silver will $5,000 buy?" metal="silver" budget="5000"]
```

### `[metal_price_calc]`

Outputs a server-rendered formatted currency value in a `<span>` using calculator-style math from shortcode attributes.

This shortcode is useful when you want to print a single computed value inline inside headings, paragraphs, cards, or CMS copy.

Default usage:

```text
[metal_price_calc]
```

Default behavior:

- `metal="gold"`
- `purity="14K"`
- `weight="0"`
- `weight_unit="grams"`
- `output="market"`

Supported attributes:

- `metal`
  - Supported values: `gold`, `silver`, `platinum`, `palladium`
  - Invalid values fall back to `gold`
- `purity`
  - For `gold`, accepts `1K` through `24K`
  - `K` suffix optional for gold
  - For `silver`, `platinum`, and `palladium`, use either a decimal purity like `0.925`, `0.950`, or `0.9995`, or fineness-style values like `925`, `950`, `999`, or `999.5`
  - Invalid gold values fall back to `14`
  - Invalid non-gold values fall back to `0.9999`
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
  - `melt`
  - `melt_value`
  - Invalid values fall back to `market`
  - `melt` uses the passed `purity` when `purity` is explicitly set
  - If `purity` is omitted, `melt` falls back to `.9999` for backward compatibility

Examples:

```text
[metal_price_calc purity="14K" weight="10" weight_unit="grams" output="market"]
[metal_price_calc purity="14K" weight="10" weight_unit="grams" output="pawn"]
[metal_price_calc purity="14K" weight="10" weight_unit="grams" output="alloy"]
[metal_price_calc purity="14K" weight="10" weight_unit="grams" output="melt"]
[metal_price_calc weight="10" weight_unit="grams" output="melt"]
[metal_price_calc metal="silver" purity="0.925" weight="10" weight_unit="grams" output="market"]
[metal_price_calc metal="platinum" purity="0.950" weight="10" weight_unit="grams" output="alloy"]
[metal_price_calc metal="platinum" purity="950" weight="10" weight_unit="grams" output="market"]
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
- `[metal_calculator_layout]` renders with zero-value calculator and price widget values
- `[metal_offer_card]` renders unavailable values
- `[metal_price_compare]` renders a comparison card with unavailable rows and `Feed error`
- `[metal_payout_comparison]` renders an unavailable comparison table
- `[metal_spot_ticker]` renders an unavailable ticker card
- `[metal_budget_buy_widget]` renders with the live spot price at `$0.00`, so the estimated ounces also start at `0.000`
- `[conversion_rate_calculator]` still renders because it performs calculations locally in the browser
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
- [`includes/shortcodes/class-alloy-metal-price-api-calculator-renderer.php`](/Users/jamescook/REPOS/LOCAL-SITES/alloy-marcom/app/public/wp-content/plugins/AlloyMetalPriceAPI/includes/shortcodes/class-alloy-metal-price-api-calculator-renderer.php)
- [`includes/shortcodes/class-alloy-metal-price-api-metal-calculator-shortcode.php`](/Users/jamescook/REPOS/LOCAL-SITES/alloy-marcom/app/public/wp-content/plugins/AlloyMetalPriceAPI/includes/shortcodes/class-alloy-metal-price-api-metal-calculator-shortcode.php)
- [`includes/shortcodes/class-alloy-metal-price-api-metal-calculator-layout-shortcode.php`](/Users/jamescook/REPOS/LOCAL-SITES/alloy-marcom/app/public/wp-content/plugins/AlloyMetalPriceAPI/includes/shortcodes/class-alloy-metal-price-api-metal-calculator-layout-shortcode.php)
- [`includes/shortcodes/class-alloy-metal-price-api-metal-offer-card-shortcode.php`](/Users/jamescook/REPOS/LOCAL-SITES/alloy-marcom/app/public/wp-content/plugins/AlloyMetalPriceAPI/includes/shortcodes/class-alloy-metal-price-api-metal-offer-card-shortcode.php)
- [`includes/shortcodes/class-alloy-metal-price-api-metal-price-compare-shortcode.php`](/Users/jamescook/REPOS/LOCAL-SITES/alloy-marcom/app/public/wp-content/plugins/AlloyMetalPriceAPI/includes/shortcodes/class-alloy-metal-price-api-metal-price-compare-shortcode.php)
- [`includes/shortcodes/class-alloy-metal-price-api-metal-payout-comparison-shortcode.php`](/Users/jamescook/REPOS/LOCAL-SITES/alloy-marcom/app/public/wp-content/plugins/AlloyMetalPriceAPI/includes/shortcodes/class-alloy-metal-price-api-metal-payout-comparison-shortcode.php)
- [`includes/shortcodes/class-alloy-metal-price-api-metal-spot-ticker-shortcode.php`](/Users/jamescook/REPOS/LOCAL-SITES/alloy-marcom/app/public/wp-content/plugins/AlloyMetalPriceAPI/includes/shortcodes/class-alloy-metal-price-api-metal-spot-ticker-shortcode.php)
- [`includes/shortcodes/class-alloy-metal-price-api-metal-goldbar-live-melt-table-shortcode.php`](/Users/jamescook/REPOS/LOCAL-SITES/alloy-marcom/app/public/wp-content/plugins/AlloyMetalPriceAPI/includes/shortcodes/class-alloy-metal-price-api-metal-goldbar-live-melt-table-shortcode.php)
- [`includes/shortcodes/class-alloy-metal-price-api-metal-fractional-goldbar-module-shortcode.php`](/Users/jamescook/REPOS/LOCAL-SITES/alloy-marcom/app/public/wp-content/plugins/AlloyMetalPriceAPI/includes/shortcodes/class-alloy-metal-price-api-metal-fractional-goldbar-module-shortcode.php)
- [`includes/shortcodes/class-alloy-metal-price-api-metal-standard-goldbar-module-shortcode.php`](/Users/jamescook/REPOS/LOCAL-SITES/alloy-marcom/app/public/wp-content/plugins/AlloyMetalPriceAPI/includes/shortcodes/class-alloy-metal-price-api-metal-standard-goldbar-module-shortcode.php)
- [`includes/shortcodes/class-alloy-metal-price-api-conversion-rate-calculator-shortcode.php`](/Users/jamescook/REPOS/LOCAL-SITES/alloy-marcom/app/public/wp-content/plugins/AlloyMetalPriceAPI/includes/shortcodes/class-alloy-metal-price-api-conversion-rate-calculator-shortcode.php)
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

24K gold bar spot table:

```text
[metal_price_table data="gold bars"]
```

14K interactive calculator:

```text
[metal_calculator title="14K Gold Calculator" purity="14K"]
```

Two-column calculator layout:

```text
[metal_calculator_layout purity="24K"]
```

14K offer card:

```text
[metal_offer_card purity="14K"]
```

Payout comparison card:

```text
[metal_payout_comparison]
```

Spot gold ticker card:

```text
[metal_spot_ticker]
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
