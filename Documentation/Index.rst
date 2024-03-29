==========
PhotoSwipe
==========


What does it do?
================
Adds the photoswipe.com JavaScript plugin (version 4.1.3) as default for image enlargement.
|

Installation
============
You can install this extension via em or composer:
|

Installation via ExtensionManager
=================================
1. Go to the Extension Manager and install the extension
2. Include the static template "PhotoSwipe (photoswipe)"
3. Configure extension if required (see section below)

Installation via Composer
=========================
1. install the extension via composer: composer require tei/photoswipe
2. Include the static template "PhotoSwipe (photoswipe)"
3. Configure extension if required (see section below)

Configuration
=============
You can overwrite all files in the Constant Editor. Besides that you will find a checkbox to
enable a share-button for social media and the setting for the css-wrapper to enable photoswipe in it.
Please note that the extension itself uses TS-setup 'page.99' to add its html-template at the bottom of the page.
It also adds a parameter 'data-size' in TYPO3's media link in TypoScript/setup.txt

Usage
=====
Just use the regular "Enlarge on Click" checkbox in the backend. If you have more than one image, the extension will
add arrows in the zoom-view.

ChangeLog
=========
|
1.0.0
=====
- Initial release of Extension
1.0.1 -1.0.4
============
- Struggling with composer.json
1.0.5
=====
- Bugfix: Always opened first image in gallery
1.0.6
=====
- Bugfix: share button
- Feature: make css init-wrapper-class configurable
1.0.7
=====
- Bugfix: composer.json for packagist
1.1.0
=====
- Feature: added base64 support for lazy loading. Just use the checkbox "lazy loading" next to "enlarge image".
1.1.1
=====
- Feature: Ready for TYPO3 11
1.1.2
=====
- Bugfix: Composer
1.1.3
=====
- Bugfix: TCA in overrides
1.2.0
=====
- Bugfix: image link with special chars
- Feature: new psw-version 4.4.1
1.2.1
=====
- Documentation update
1.2.2
=====
- emonf for TER
1.2.3
=====
- emonf for TER, bugfix in constants, settings-check for file-extensions


