# REMP Cabrio - Wordpress publishing A/B testing plugin

## Installation

The installation goes as with other wordpress plugins. You can either copy it manually into your `wp-content/plugins`
folder or zip the package and than upload it via Wordpress plugin installation screen.

### Dependencies

You will be able to install the plugin, but not activate it, unless all of the following dependencies are also installed
and activated:

* (Multiple Post Thumbnails)[https://wordpress.org/plugins/multiple-post-thumbnails/]

### Configuration

Once the plugin is activated, you can enable/disable A/B testing per feature:

* Titles
* Featured images
* Content locking

The settings are linked into the Wordpress Settings menu with title *DN REMP Cabrio*

## Usage

All the types of tests are being evaluated on the frontend via JavaScript to prevent issues with backend
caches blocking the A/B test functionallity. Content is being replaced as soon as it's rendered.

### Titles

When enabled, alternative title is injected into the post editing page. If it's filled, users are randomly
shown one of the two available versions. The setting is then stored into the `cabriot` cookie.

### Images

When enabled, alternative to feauted image is displayed in the post editing page. If it's filled, users
are randomly shown on of the two available versions. The setting is then stored into the `cabrioi` cookie.

### Content Locking

Plugin doesn't add the functionallity of content locking, it just utilizes the locking that you have
configured on your website. If it's enabled, it's dependendent on 3 settings:

* *Article selector*: Code that plugin can evaluate via `document.querySelector` which will return DOM
element containing content of the article. All direct descendants of selected element are counted
as paragraphs to be handled by the plugin.

    ```
    // example value for default Wordpress 4.9 theme
    
    .entry-content
    ```

* *Gate selector*: Code that plugin can evaluate via `document.querySelector` which will return position
of your content lock within the article. If nothing is returned, no changes to article are done.

    ```
    // example value that should be generated by your app/template inside your article
    
    .paywall
    ```

* *Number of paragraphs to hide*: Number of full paragraphs that will be hidden for portion of your
visitors. If the final number of visible paragraphs would be less than 1, single paragraph is kept
just so the content of article is not empty.

    If the paywall gate is surrounded by other content, all paragraph content preceding the paywall
    gate will be removed (if at least one full paragraph will be removed).

## Tracking

### REMP Beam

Statistics are not part of the plugin and have to be tracked separately. We recommend to track the
stats to [REMP Beam](https://github.com/remp2020/remp/tree/master/Beam), which could store the data alongside with
other pageview data.

Once you have the Beam snippet in-place, insert following before the `remplib.tracker.init(rempConfig)` call.
 
```js
var articleId = String; // get ID of the article from your CMS

if (articleId && window.cabrio) {
    var variants = {
        title: null,
        image: null,
        lock: null
    };
    // title A/B test
    if (window.cabrio.t && window.cabrio.t.variants && window.cabrio.t.variants[articleId]) {
        variants.title = window.cabrio.t.variants[articleId];
    }
    // image A/B test
    if (window.cabrio.i && window.cabrio.i.variants && window.cabrio.i.variants[articleId]) {
        variants.image = window.cabrio.i.variants[articleId];
    }
    // lock A/B test
    if (window.cabrio.l && window.cabrio.l.variants && window.cabrio.l.variants[articleId]) {
        variants.lock = window.cabrio.l.variants[articleId];
    }
    rempConfig.tracker.article.variants = variants
}
```

Once the tracking is working, you can see the A/B testing statistics by using [Iota](https://github.com/remp2020/remp/tree/master/Beam#iota-on-site-reporting)
(on-site reporting tool), which is part of the Beam.

### Your own solution

If you would like to track and display the statistics in other way, you can read the data yourself
from the `window.cabrio` object. The structure is:

```
{
    "i": VariantUsage, // featured image A/B testing
    "t": VariantUsage, // title A/B testing
    "l": VariantUsage // content lock A/B testing
}

VariantUsage: {
    "default": String, // label of variant, e.g. "A", "B",
    "selected": String,  // label of variant, e.g. "A", "B",
    "variants": {
        String: String, // key-value pairs of articleId-variant
        // ...
    }
}
```

Plugin will always include all articles that were touched during render. That means, that if your
article detail lists recommended articles and you have A/B testing of titles enabled, you'll see
all of the displayed articleIds along with used variants within the `window.cabrio.t.variants` object.