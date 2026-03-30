# Alloy Metal Price Shortcode Guide

This guide is for using the plugin inside WordPress pages, posts, and reusable content blocks.

If you only need the short version:

- Use `[metalpriceapi]` for a single live metal price
- Use `[metal_price_table]` for a simple gold price table
- Use `[metal_calculator]` for the calculator by itself
- Use `[metal_calculator_layout]` for the calculator plus right-side content boxes
- Use `[metal_offer_card]` for a compact offer comparison box
- Use `[metal_payout_comparison]` for a multi-row payout comparison card
- Use `[metal_spot_ticker]` for a small live spot-price box
- Use `[metal_goldbar_live_melt_table]` for a responsive gold bar melt-value table
- Use `[metal_fractional_goldbar_module]` for a fractional gold bar module with a live spot-price header
- Use `[metal_standard_goldbar_module]` for a standard gold bar module with a live spot-price header
- Use `[metal_price_compare]` for a two-metal live price comparison card
- Use `[metal_price_calc]` to print one calculated value inline in text

## Quick Reference

```text
[metalpriceapi symbol="" unit=""]
[metal_price_table title="" metal="" purity="" data="" show_live_box=""]
[metal_calculator title="" purity="" metal=""]
[metal_calculator_layout title="" purity="" metal="" right=""]
[metal_offer_card title="" purity=""]
[metal_price_compare metal_a="" metal_b="" title=""]
[metal_payout_comparison title="" link_url=""]
[metal_spot_ticker metal="" purity="" pill_text=""]
[metal_goldbar_live_melt_table]
[metal_fractional_goldbar_module]
[metal_standard_goldbar_module]
[metal_price_calc metal="" purity="" weight="" weight_unit="" output=""]
```

## General Tips

- Always use underscores in shortcode tags, not dashes
- Add shortcode settings inside the brackets like `purity="14K"`
- Use straight quotes like `"` in shortcode attributes
- If you leave attributes out, the shortcode uses its default settings

## `[metalpriceapi]`

Use this when you want to show one live metal price inside a sentence, heading, or small content area.

Basic example:

```text
[metalpriceapi]
```

Useful examples:

```text
[metalpriceapi symbol="XAU" unit="gram"]
[metalpriceapi symbol="XAU" unit="ounce"]
[metalpriceapi symbol="XAG" unit="ounce"]
[metalpriceapi symbol="XPT" unit="kilogram"]
```

How to use it:

- `symbol` chooses the metal
- `unit` chooses how the price is displayed

Common settings:

- `symbol="XAU"` = Gold
- `symbol="XAG"` = Silver
- `symbol="XPT"` = Platinum
- `symbol="XPD"` = Palladium
- `unit="gram"`
- `unit="ounce"`
- `unit="kilogram"`

Example in a sentence:

```text
Today’s gold price per ounce is [metalpriceapi symbol="XAU" unit="ounce"].
```

## `[metal_price_table]`

Use this when you want a clean gold price table.

Basic example:

```text
[metal_price_table]
```

Popular examples:

```text
[metal_price_table purity="14K"]
[metal_price_table metal="silver" purity="0.925"]
[metal_price_table metal="platinum" purity="0.950" show_live_box="true"]
[metal_price_table purity="18K" title="18K Gold Price Table"]
[metal_price_table purity="14K" show_live_box="true"]
[metal_price_table data="gold bars"]
```

How to use it:

- `title` changes the heading
- `metal` chooses the metal price used for the table
- `purity` changes the purity used for the standard table
- `show_live_box="true"` adds the summary card beside the table
- `data` changes which row set is shown

`metal` options:

- `gold`
- `silver`
- `platinum`
- `palladium`

Purity note:

- For `gold`, use karats like `14K` or `24K`
- For `silver`, `platinum`, and `palladium`, use a decimal purity like `0.925`, `0.950`, or `0.9995`

`data` options:

- `data="default"` shows the normal gold price rows
- `data="gold bars"` shows bar rows for the selected metal
- Variations like `gold-bar`, `gold_bars`, `bars`, and `bar` also work

Good use cases:

- A page about 14K gold prices:

```text
[metal_price_table purity="14K" title="14K Gold Price Today" show_live_box="true"]
```

- A page about gold bars:

```text
[metal_price_table data="gold bars"]
```

- A page about platinum pricing:

```text
[metal_price_table metal="platinum" purity="0.950" show_live_box="true"]
```

## `[metal_calculator]`

Use this when you want the standalone calculator.

Basic example:

```text
[metal_calculator]
```

Popular examples:

```text
[metal_calculator purity="14K"]
[metal_calculator purity="24K"]
[metal_calculator metal="silver" purity="0.925"]
[metal_calculator metal="platinum" purity="0.950"]
[metal_calculator metal="palladium" purity="0.9995"]
[metal_calculator title="14K Gold Calculator" purity="14K"]
```

How to use it:

- `title` changes the calculator heading
- `purity` sets the default purity shown when the calculator loads
- `metal` chooses the metal price used in the calculator

`metal` options:

- `gold`
- `silver`
- `platinum`
- `palladium`

Purity note:

- For `gold`, use karats like `14K` or `24K`
- For `silver` and `palladium`, use a decimal purity like `0.925` or `0.9995`
- For `platinum`, use the built-in selector options: `999.5`, `999`, `950`, `900`, or `850`

Best for:

- Simple calculator sections
- Landing pages where the calculator should be the main focus

## `[metal_calculator_layout]`

Use this when you want the calculator plus the extra content boxes on the right.

Basic example:

```text
[metal_calculator_layout]
```

Popular exampless:

```text
[metal_calculator_layout purity="14K"]
[metal_calculator_layout metal="silver" purity="0.925"]
[metal_calculator_layout metal="platinum" purity="0.950"]
[metal_calculator_layout metal="palladium" purity="0.9995"]
[metal_calculator_layout purity="14K" right="14K"]
[metal_calculator_layout title="Cash for Gold Calculator" purity="18K"]
```

How to use it:

- `title` changes the calculator heading
- `purity` sets the default purity in the calculator
- `metal` chooses the metal price used in the calculator and right-side price box
- `right` controls the content boxes shown in the right column

`right` options:

- `right="default"` shows the standard gold price and marking boxes
- `right="14K"` shows the 14K-specific right-column content

`metal` options:

- `gold`
- `silver`
- `platinum`
- `palladium`

Purity note:

- For `gold`, use karats like `14K` or `24K`
- For `silver` and `palladium`, use a decimal purity like `0.925` or `0.9995`
- For `platinum`, use the built-in selector options: `999.5`, `999`, `950`, `900`, or `850`

Notes:

- Gold keeps the standard gold content boxes
- `right="14K"` is for gold only
- Platinum uses a platinum fineness box in the bottom-right area
- Silver and Palladium currently use a placeholder bottom-right box until their custom content is added

Best for:

- Full comparison pages
- Service pages where you want helpful supporting content next to the calculator

## `[metal_offer_card]`

Use this when you want a smaller offer comparison box for one purity.

Basic example:

```text
[metal_offer_card]
```

Popular examples:

```text
[metal_offer_card purity="14K"]
[metal_offer_card purity="22K"]
[metal_offer_card purity="10K"]
```

How to use it:

- `purity` sets the karat used for the card values
- `title` can be passed, but the current card design does not display it

Best for:

- Sidebars
- Landing page sections
- Supporting content near forms or CTAs

## `[metal_price_compare]`

Use this when you want a simple card comparing two live metal prices per troy ounce.

Basic example:

```text
[metal_price_compare]
```

Popular examples:

```text
[metal_price_compare]
[metal_price_compare metal_a="silver" metal_b="gold"]
[metal_price_compare metal_a="palladium" metal_b="platinum"]
[metal_price_compare metal_a="platinum" metal_b="gold" title="Platinum vs Gold Price Today (Per Troy Ounce)"]
```

How to use it:

- `metal_a` sets the first metal row
- `metal_b` sets the second metal row
- `title` lets you replace the default comparison heading

`metal_a` and `metal_b` options:

- `gold`
- `silver`
- `platinum`
- `palladium`

Best for:

- Quick comparison sections
- Commodity overview pages
- Side-by-side price callouts

## `[metal_payout_comparison]`

Use this when you want a linked comparison card showing pawn vs Alloy-style payout values across multiple karats.

Basic example:

```text
[metal_payout_comparison]
```

Popular examples:

```text
[metal_payout_comparison title="Gold Payout Comparison by Karat"]
[metal_payout_comparison link_url="https://thealloymarket.com/request-a-kit"]
[metal_payout_comparison title="Compare Gold Offers" link_url="https://thealloymarket.com/request-a-kit"]
```

How to use it:

- `title` changes the heading
- `link_url` changes where the card sends the user when clicked

Best for:

- Pages comparing pawn shop offers to your offer model
- CTA sections that lead to a request form

## `[metal_spot_ticker]`

Use this when you want a compact live price box.

Basic example:

```text
[metal_spot_ticker]
```

Popular examples:

```text
[metal_spot_ticker metal="gold" purity="24K"]
[metal_spot_ticker metal="gold" purity="14K"]
[metal_spot_ticker metal="silver" purity="24K"]
[metal_spot_ticker metal="platinum" purity="18K" pill_text="Live Spot Platinum"]
```

How to use it:

- `metal` chooses the metal
- `purity` adjusts the displayed values by purity
- `pill_text` lets you replace the small label at the top

Best for:

- Sidebar widgets
- Small callout areas
- Price highlights near related content

## `[metal_goldbar_live_melt_table]`

Use this when you want a gold bar melt-value chart that changes layout by screen size.

Basic example:

```text
[metal_goldbar_live_melt_table]
```

How it works:

- On tablet and desktop, visitors see a table
- On smaller screens, visitors see stacked cards
- Both views show the same gold bar data
- Melt values are based on live gold pricing

What it includes:

- Gold bar size
- Weight in grams
- Weight in troy ounces
- Purity
- Live melt value

Included sizes:

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

Best for:

- Gold bar price pages
- Gold bar melt-value guides
- Pages comparing common bar sizes

## `[metal_fractional_goldbar_module]`

Use this when you want a smaller gold bar melt-value module focused on fractional bar sizes.

Basic example:

```text
[metal_fractional_goldbar_module]
```

How it works:

- The top header shows the current gold spot price per troy ounce
- On tablet and desktop, visitors see a table
- On smaller screens, visitors see stacked cards
- Both views show the same fractional gold bar data

What it includes:

- Module title
- Live spot price in `USD / troy oz`
- Bar size
- Weight in grams
- Weight in troy ounces
- Purity
- Live melt value

Included sizes:

- `1 g`
- `2.5 g`
- `5 g`
- `10 g`
- `20 g`

Best for:

- Fractional gold bar pages
- Short-form gold bar guides
- Pages focused on smaller investment bar sizes

## `[metal_standard_goldbar_module]`

Use this when you want a smaller gold bar melt-value module focused on common standard bar sizes.

Basic example:

```text
[metal_standard_goldbar_module]
```

How it works:

- The top header shows the current gold spot price per troy ounce
- On tablet and desktop, visitors see a table
- On smaller screens, visitors see stacked cards
- Both views show the same standard gold bar data

What it includes:

- Module title
- Live spot price in `USD / troy oz`
- Bar size
- Weight in grams
- Weight in troy ounces
- Purity
- Live melt value

Included sizes:

- `50 g`
- `100 g`
- `5 oz`

Best for:

- Standard gold bar pages
- Investment bar comparison pages
- Content focused on mid-size physical gold bars

## `[metal_price_calc]`

Use this when you want to show one calculated value inline without displaying the full calculator.

Basic example:

```text
[metal_price_calc]
```

Popular examples:

```text
[metal_price_calc purity="14K" weight="12.5" weight_unit="grams" output="market"]
[metal_price_calc purity="18K" weight="0.75" weight_unit="ounces" output="pawn"]
[metal_price_calc purity="24K" weight="10" weight_unit="pennyweight" output="alloy"]
[metal_price_calc weight="10" weight_unit="grams" output="melt"]
[metal_price_calc metal="silver" purity="0.925" weight="10" weight_unit="grams" output="market"]
[metal_price_calc metal="platinum" purity="0.950" weight="10" weight_unit="grams" output="alloy"]
```

How to use it:

- `metal` chooses the metal price used in the calculation
- `purity` sets the purity
- `weight` is the item weight
- `weight_unit` can be `grams`, `ounces`, or `pennyweight`
- `output` decides which value to print

`output` options:

- `market`
- `pawn`
- `alloy`
- `melt`

Note:

- `melt` always calculates using `.9999` purity
- For `gold`, use karats like `14K` or `24K`
- For `silver`, `platinum`, and `palladium`, use a decimal purity like `0.925`, `0.950`, or `0.9995`

Best for:

- Headlines
- Intro paragraphs
- Comparison blurbs
- Small stat boxes

Example in a sentence:

```text
A 14K gold item weighing 12.5 grams has an estimated market value of [metal_price_calc purity="14K" weight="12.5" weight_unit="grams" output="market"].
```

## Common Ready-to-Use Examples

14K price page:

```text
[metal_price_table purity="14K" title="14K Gold Price Today" show_live_box="true"]
[metal_calculator title="14K Gold Calculator" purity="14K"]
```

14K calculator layout page:

```text
[metal_calculator_layout purity="14K" right="14K"]
```

Gold bar page:

```text
[metal_price_table data="gold bars"]
```

Small live spot-price box:

```text
[metal_spot_ticker metal="gold" purity="24K"]
```

Responsive gold bar melt table:

```text
[metal_goldbar_live_melt_table]
```

Fractional gold bar module:

```text
[metal_fractional_goldbar_module]
```

Standard gold bar module:

```text
[metal_standard_goldbar_module]
```

Inline melt-value example:

```text
[metal_price_calc purity="14K" weight="5" weight_unit="grams" output="alloy"]
```

## Troubleshooting

If a shortcode does not work as expected:

- Make sure the shortcode tag uses underscores, not dashes
- Make sure the quotes are straight quotes, not curly quotes
- Make sure the plugin is active
- If the page is cached, clear the cache and refresh the page
