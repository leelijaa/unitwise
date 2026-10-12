# UnitWise Converters — Plugin

WordPress plugin for the UnitWise unit-converter hub. Owns all data, routing, and logic. Survives theme switches.

## Install & activate

1. Copy `unitwise-converters/` into `wp-content/plugins/`.
2. Copy `unitwise-theme/` into `wp-content/themes/`.
3. Activate **UnitWise Converters** plugin in WP Admin → Plugins.
4. Go to **Tools → UnitWise Seeder** and click **Seed Converters** (creates all 41 weight converter posts).
5. Activate **UnitWise Theme** in WP Admin → Appearance → Themes.
6. Go to **Settings → Permalinks** → click **Save Changes** (flushes rewrite rules).
7. Visit `/weight/` — silo landing should load. Visit `/weight/grams-to-kilograms/` — converter page should load.

### WP-CLI alternative to step 4

```
wp unitwise seed
```

## Verify it works

| Check | How |
|-------|-----|
| Live converter | Visit `/weight/grams-to-kilograms/` → type a number → result updates instantly |
| No-JS fallback | Disable JS in DevTools → static worked example + table still render |
| JSON-LD | View Source → find `<script type="application/ld+json">` → paste into Google Rich Results Test |
| Deactivate theme | All converter posts and URLs remain; no PHP fatals |
| Deactivate plugin | Theme degrades gracefully (no PHP fatals) |
| Re-run seeder | Zero duplicates |

## Add a new unit

1. Open `includes/class-registry.php`.
2. Add one row to `$weight_factors`:
   ```php
   'grains' => 0.06479891,   // 1 grain = 0.0648 grams
   ```
3. Add its label to `$labels` and optionally a description to `$descriptions`.
4. Run the seeder or add converter posts manually — no engine changes needed.

## Add a new converter pair

Open **Tools → UnitWise Seeder**, or add one row to `class-seeder.php::get_pairs()` and re-seed:

```php
[ 'from' => 'grams', 'to' => 'grains', 'category' => 'weight' ],
```

## Add a new category (e.g. Length)

1. Add to `Unitwise_Registry::$categories`:
   ```php
   'length' => [
       'name'      => 'Length',
       'slug'      => 'length',
       'description' => '...',
       'base_unit' => 'meters',
   ],
   ```
2. Add a `$length_factors` array and update `get_factors()` to return it for `'length'`.
3. Add rows to `Unitwise_Seeder::get_pairs()` for the new category.
4. Re-seed. The rewrite rules auto-generate from the category list — no extra code.

## URL structure

| URL | Description |
|-----|-------------|
| `/` | Hub homepage |
| `/weight/` | Weight & Mass silo landing |
| `/weight/grams-to-kilograms/` | Converter page |
| `/blog/` | Blog index |
| `/blog/{slug}/` | Blog post |

## Plugin–theme boundary

| Lives in plugin | Lives in theme |
|-----------------|----------------|
| CPT, taxonomy, meta | Templates, CSS |
| Rewrite rules | `functions.php` (theme setup only) |
| Conversion engine | `header.php`, `footer.php` |
| JSON-LD schema | Template parts |
| Blocks, shortcodes | `assets/` styles + JS |
| Seeder | — |
