# PhotoSwipe for TYPO3

Native image enlargement and content layers powered by [PhotoSwipe](https://photoswipe.com/) —
plain JavaScript, no jQuery or other frameworks required.

## Features

- **Image lightbox** — images with "click to enlarge" open in the PhotoSwipe gallery.
  The image dimensions are added to the markup as `data-pswp-width` / `data-pswp-height`
  by the `ATagParams` user function.
- **Content layer** — link type "Layer" in the RTE and link wizard. A link pointing at a
  content element (`#c123`) renders its HTML inside the layer, a link pointing at a page
  opens it in an iframe.
- **Lazy loading with base64 preview** — the `imagelazy64` checkbox on a content element
  renders a tiny base64 placeholder first and loads the real image afterwards.

## Compatibility

| Extension | TYPO3 |
|---|---|
| 3.0.x | 14.3 LTS |
| 2.0.x | 12.4 / 13.4 |

## Installation

```bash
composer require tei/photoswipe
```

Afterwards include the static TypoScript "PhotoSwipe" in the backend.

## Tests

```bash
composer install
Build/runFunctionalTests.sh
```

The suite needs a reachable database and a user allowed to create databases; it
creates its own `<name>_ft1` schema per run. Credentials come from the
`typo3Database*` environment variables, see `Build/runFunctionalTests.sh`.

The tests are deliberately regression focused — they cover the three mistakes that
broke this extension on TYPO3 14:

- services that must be `public` so `makeInstance()` resolves them through the container,
- the link builder overriding `buildLink()` instead of the removed `build()`,
- the `lazy64` argument being cast before comparison, and the TCA `items` format.

## What changed in 3.0.0 (TYPO3 14)

The extension no longer worked under v14 — in parts it returned HTTP 500 on every page.
The following was adjusted:

**`PhotoSwipeLinkBuilder`**
- `build()` → `buildLink()`. Since TYPO3 14 the `LinkFactory` only calls `buildLink()` on
  builders implementing `TypolinkBuilderInterface`. The old method was never invoked again,
  which silently dropped all `data-pswp-*` attributes.
- `$GLOBALS['TSFE']->cObj` → `$request->getAttribute('currentContentObject')`.
  `$GLOBALS['TSFE']` no longer exists in v14.
- Falls back to a plain link instead of throwing in the middle of the page rendering when
  no ContentObjectRenderer or no content is available.

**`Configuration/Services.yaml`**
- `PhotoSwipeLinkBuilder` and `ImageService64` are `public: true` now.
  TYPO3 instantiates registered `typolinkBuilder` classes through
  `GeneralUtility::makeInstance()` without arguments; only a public service is resolved
  through the DI container. Without this:
  `ArgumentCountError: Too few arguments to PageLinkBuilder::__construct(), 0 passed, 14 expected`
  on **every** page.

**`ImageService64`**
- `FrontendConfigurationManager::getTypoScriptSetup()` requires the request since TYPO3 13.
- Dependencies are injected through the constructor instead of `makeInstance()` calls
  inside the methods.
- Falls back to defaults when no frontend TypoScript is available, e.g. in the backend.
- The temporary file used for stripping EXIF data now lives in `var/transient/` instead of
  `fileadmin/_processed_/` and is unique per process — previously concurrent requests could
  overwrite each other's temporary file.

**`MediaViewHelper` / `ImageViewHelper`**
- `class` and `title` are no longer registered arguments in Fluid 5. Both values are now
  read from the tag builder instead of `$this->arguments`.
- **Bugfix:** `MediaViewHelper` compared `$this->arguments['lazy64'] === 1` strictly against
  an integer while Fluid passes the string `"1"`. Base64 lazy loading never worked there.

**`LinkEventListener`**
- `Icon::SIZE_SMALL` → `IconSize::SMALL` (enum since TYPO3 14).

**TCA `imagelazy64`**
- `items` converted to associative keys (`label` / `value`).

**Misc**
- Removed `ext_emconf.php` — deprecated in TYPO3 14.3, the metadata lives in `composer.json`.
- Minimum requirement is PHP 8.2.
