/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
import LinkBrowser from "@typo3/backend/link-browser.js";
import RegularEvent from "@typo3/core/event/regular-event.js";

/**
 * Module: @tei/photoswipe/photoswipe-link-handler.js
 * photoswipe link
 */
class PhotoSwipeLinkHandler {
    constructor() {
        this.linkPageByTextfield = () => {
            let e = document.getElementById("luid").value;
            if (!e) return;
            const t = parseInt(e, 10);
            e = "t3://photoswipe?uid=" + t;
            LinkBrowser.finalizeFunction(e);
        }

        new RegularEvent("click", ((e, t) => {
            e.preventDefault();
            LinkBrowser.finalizeFunction(
                t.getAttribute("href").replace("page", "photoswipe")
            );
        })).delegateTo(document, "a.t3js-pageLink");
    }
}

export default new PhotoSwipeLinkHandler;