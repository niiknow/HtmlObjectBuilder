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

// teach it positive phrases

$builder->toHtml($someObj, $tagName);

// $someObj - can be an object, array, or a json string
// $tagName - optional, will default to "div"

```

## MIT

