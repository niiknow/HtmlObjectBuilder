# ObjectHtmlBuilder
> Useful tool for font-end FormBuilder or Drag&Drop HTML Designer

Sadly, there is a high demand for "Dumb-Proof" HTML Designer tool.  This is useful for "Design-time" saving and rendering for our HTML Designers.


## What can I use this for?
The main focus of this tool is to create dead simple way of capturing "Design-time" data from HTML or Form Builder and store this data for later edit.  Suggestion:

1. Use this tool during "Design-time" to store and render FORM or HTML Builder content. 
2. Persist both the "object/json model" and "html output" during Design-time.
3. Display the output to website during "Runtime".  Do not use this to perform actual public HTML rendering.  Only use for "Design-time" rendering.

## Installing

```
composer install niiknow/objecthtmlbuilder
```

## Usage

```php
$builder = new \niiknow\ObjectHtmlBuilder();

// ObjectHtmlBuilder($options) - take an optional $options array parameter
// $options: ['indent' => null, 'noescape' => false]
// 'indent': set indentation string, example: 'indent' => "\t"
// 'noescape': true to disable html escape
// NOTE: due to different object complexity cases, indent is difficult to cover.  If you really need HTML format and since this is PHP, you can always install tidy: http://php.net/manual/en/tidy.examples.basic.php

// now to use, simply call toHtml method with your object
$builder->toHtml($someObj);

```

Example Object:
```json
{
    "div": "some simple text",
    "a": {
        "_attrs": {"title": "google", "href": "https://google.com"},
        "_content": [
            {"i": { "_attrs": { "class": "fa fa-pencil"} } },
            {"span": "link to google"}
        ]
    },
    "section": {
        "_attrs": {"title": "Raw Data", "class": "go go power ranger go ranger"},
        "_html": "<div>hohoho</div>"
    }
}
```

Output:
```html
<div>
  <div>some simple text</div>
  <a href="https://google.com" title="google">
    <i class="fa fa-pencil"></i>
    <span>link to google</span>
  </a>
  <section class="go power ranger" title="Raw Data"><div>hohoho</div></section>
</div>
```

Special/Internal Attributes:

1. _attrs - object: all your attributes.  Attributes are rendered as *key="escaped(value)"*
2. _content - mixed: this can be oject, array, or string.  For inner content/children of complex object.
3. _tag - string: use to specify the tag name in an array type.  Also use to override an object tag name.
4. _html - string: for rendering raw/unescaped HTML content

## API

### toHtml($someObj, $tagName)
The main function to convert object to HTML.

* $someObj - can be an object, array, or a json string
* $tagName - optional, will default to "div"

### setOnBeforeTagHandler($tagName, Closure $handler)
Register Closure as event handler intercepting MakeTagEvent.  See \niiknow\MakeTagEvent.  This is useful for adding additional class or attribute to tag.

* array - an array of tag to Closure

### setOnTagHandler($tagName, Closure $handler)
Register Closure as event handler for MakeTagEvent.  See \niiknow\MakeTagEvent

* array - an array of tag to Closure

## NOTE
* html node property/attributes are sorted alphabetically.

## TODO
[] Bootstrap4 templating example

## MIT

