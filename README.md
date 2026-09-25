# magiceverse/contracts

The data contracts shared by Magiceverse systems: JSON Schemas, PHP DTOs and
fixtures for products, print positions, decoration techniques, the product
delta feed and product webhooks. The schemas are the source of truth; the DTOs
and fixtures follow them and tests keep all three in step.

| Entity | Schema | `$id` | PHP |
|---|---|---|---|
| Technique | `schemas/technique/v1.json` | `https://contracts.magiceverse.dev/technique/v1` | `Technique\TechniqueData` |
| PrintPosition | `schemas/print-position/v1.json` | `https://contracts.magiceverse.dev/print-position/v1` | `PrintPosition\PrintPositionData` |
| Product | `schemas/product/v1.json` | `https://contracts.magiceverse.dev/product/v1` | `Product\ProductData` |
| Delta page | `schemas/delta-page/v1.json` | `https://contracts.magiceverse.dev/delta-page/v1` | `Delta\DeltaPageData` |
| Webhook event | `schemas/cloudevent/v1.json` | `https://contracts.magiceverse.dev/cloudevent/v1` | `Envelope\CloudEventData` |
| Shared `$defs` | `schemas/common/v1.json` | `https://contracts.magiceverse.dev/common/v1` | - |

---

## For API consumers

### A product

Each tenant PIM publishes its products in this shape (fields trimmed; the full
example is `fixtures/product/v1/simple-with-media-and-positions.json`):

```json
{
    "uid": "01J8Z3K4M5N6P7Q8R9S0T1V2A1",
    "sku": "MUG-300-WHITE",
    "type": "simple",
    "status": "enabled",
    "family": "drinkware",
    "categories": ["mugs"],
    "channels": ["webshop"],
    "values": {
        "common": { "ean": "8712345678901", "weight": 0.35, "materials": ["ceramic"] },
        "locale_specific": { "nl_NL": { "name": "Mok 300 ml wit" } }
    },
    "media": [
        { "attribute": "image", "url": "https://cdn.example.com/p/mug.jpg", "filename": "mug.jpg", "mime": "image/jpeg", "position": 0 }
    ],
    "print_positions": [
        { "code": "front", "name": { "nl_NL": "Voorkant" }, "max_width_mm": 60, "max_height_mm": 40, "techniques": ["screen_print"] }
    ],
    "techniques": ["screen_print"],
    "updated_at": "2026-09-25T07:00:00Z",
    "deleted_at": null
}
```

- `uid` is a ULID and never changes; `sku` is the human key and can.
- `values` follows UnoPim's layout: `common`, `locale_specific`,
  `channel_specific`, `channel_locale_specific`. Images and files are in `media`,
  never in `values`.
- Timestamps are RFC 3339 in UTC (`Z`). Locale keys look like `nl_NL`; codes
  are lowercase snake.
- The schemas reject unknown properties, and a minor version can add new
  optional ones. If you validate strictly, validate against the latest
  published schema of the major (the `schema` block of each delta page says
  which version the producer speaks). Otherwise validate leniently and ignore
  fields you do not know.
- Price attributes are objects of decimal strings per currency:
  `"price": {"EUR": "12.00"}`.

### Reading changes: the delta cursor

The tenant's product delta endpoint takes `?cursor=<next>&limit=500` and returns a page:

```json
{ "schema": { "product": "1.0.0" }, "data": [ ... ], "cursor": { "next": "MjAy...", "has_more": true }, "generated_at": "2026-09-25T07:00:05Z" }
```

1. Start without `cursor` to read everything. `limit` is 1..1000, default 500;
   a value outside that range is clamped, not rejected.
2. `data` is sorted by `(updated_at, uid)`. Store `cursor.next` after you
   processed the page, then ask for the next page with it.
3. `has_more: true` always comes with at least one product; the server never
   sends an empty page that says there is more.
4. `has_more: false` means you are caught up. Keep the last `next` and poll
   with it later to get only what changed since. `next` is `null` only when
   there was nothing to read at all.
5. The cursor is opaque. Do not build or parse it. An invalid or expired cursor
   gets `400` with an `application/problem+json` body; start again without a
   cursor (idempotency makes the re-read harmless).

You do not need to rewind the cursor to catch late writes. The server holds
back the most recent 5 seconds: a row whose change time is newer than
now minus 5 s is not returned yet, so a transaction that commits slightly out
of order still lands after your cursor, not behind it.

### Tombstones

A deleted product stays in the feed as a tombstone: `deleted_at` is set and
only `uid`, `sku`, `type`, `status`, `updated_at` and `deleted_at` are present.
Remove the product on your side. Webhooks send the same tombstone with type
`dev.magiceverse.product.deleted`.

### Idempotency

Apply a product only if its `(uid, updated_at)` is newer than what you have;
applying the same one twice must be a no-op. That makes it safe to re-read a
page after a crash, restart from scratch after a `400` cursor, and receive the
same change by webhook and by delta page. Webhook events are CloudEvents 1.0
with a ULID `id`; deduplicate on `(source, id)`. The event's `subject` is always
the `uid` of the product in `data`.

### Validating on your side

The schemas are plain JSON Schema draft 2020-12 and work with any validator
that lets you map the `$id`s above to the files in `schemas/`. Each schema
carries its version in the annotation keyword `x-version`. With Ajv (strict
mode passes):

```js
import Ajv2020 from 'ajv/dist/2020.js';
import addFormats from 'ajv-formats';
import fs from 'node:fs';

const ajv = new Ajv2020({ strict: true, allowUnionTypes: true, allErrors: true });
addFormats(ajv);                 // "uri" format
ajv.addKeyword('x-version');     // our version annotation; strict mode rejects unknown keywords, x- included
for (const entity of ['common', 'technique', 'print-position', 'product', 'delta-page', 'cloudevent']) {
    ajv.addSchema(JSON.parse(fs.readFileSync(`schemas/${entity}/v1.json`, 'utf8')));
}

const validateProduct = ajv.getSchema('https://contracts.magiceverse.dev/product/v1');
```

---

## For Magiceverse developers

### Using it

```php
use Magiceverse\Contracts\Product\ProductData;
use Magiceverse\Contracts\Schema;
use Magiceverse\Contracts\Version;

Schema::validate('product', 1, $payload);   // throws ContractViolation
$product = ProductData::from($payload);      // typed, enums included; does NOT validate
$product->toArray();                         // back to contract shape
Version::PRODUCT;                            // '1.0.0'
```

`ContractViolation` (an `InvalidArgumentException`) lists every error at once,
keyed by JSON pointer (`$e->errors`, `$e->pointers()`); `/` is the document
itself. `Schema::validator()` gives you a configured opis validator if you need
more control; every `$id` resolves to the local file, never the network.
`Schema::validate()` also checks what JSON Schema cannot: a CloudEvent's
`subject` must equal `data.uid` (reported at `/subject`). The raw opis
validator does not.

`ProductData::from()` and the other `from()` calls only map and cast; they do
not check patterns, enums of nested strings, or the rules above. At every trust
boundary (incoming webhook, delta page from another system, API input) call
`Schema::validate()` first and build the DTO afterwards.

Optional properties are `Optional` in the DTOs, not defaulted, so an absent key
stays absent and `null` stays `null` in `toArray()`. Timestamps stay strings so
they round-trip unchanged.

PHP arrays cannot tell an empty object from an empty list: `[]` encodes as a
JSON array. The DTOs' `toArray()` therefore leaves empty maps out (value
buckets, provenance, an optional description), while empty lists such as
`channels: []` stay. When you build payloads without the DTOs, omit empty maps
yourself, or validate the decoded object (`json_decode($json)`) instead of an
array.

### The rule: additive only within a major

Inside `v1` you may only add **optional** properties (and new `$defs`). Never
rename, remove, retype, make required, narrow an enum or tighten a pattern.
Anything else is a new major: a new `v2.json` next to `v1.json`, served side by
side until consumers moved.

Because every object is closed, an added field is only safe once the reader
knows it. So the order is: consumers upgrade this package first, producers
send the new field after. External consumers validate against the latest
published schema of the major, or validate leniently.

### Adding a field

1. Add the property to `schemas/<entity>/v1.json` (with a `description`), not to
   `required`.
2. Bump the minor in the schema's `"x-version"` keyword and in `src/Version.php`
   (`1.0.0` -> `1.1.0`). A test fails if the two differ.
3. Add the property to the DTO as `type|Optional` (plus `null` if the schema
   allows null).
4. Use it in at least one valid fixture, and add an invalid fixture when the
   field has rules. Each `fixtures/<entity>/v1/invalid/<name>.json` needs a
   `<name>.reason.txt`:

   ```
   pointer: /print_positions/0/shape
   reason: shape must be one of rectangle, circle, free.
   ```

   The test checks the payload fails at that pointer.
5. Add a line to `CHANGELOG.md` under Unreleased.

A fix to a description or example is a patch bump. The package version
(git tag) follows the highest change in any schema.

### Fixtures

`fixtures/` is the shared test data for every repo that produces or consumes
these contracts. Use them in your own tests (they ship with the package under
`vendor/magiceverse/contracts/fixtures`) instead of inventing payloads, so all
systems agree on the same examples.

### Running the checks

```bash
composer install
vendor/bin/pint --test
vendor/bin/pest
```

## Planned

- TypeScript types generated from the schemas, for the portal and storefronts.
- An OpenAPI description of the delta endpoint and webhooks that references
  these schemas.
