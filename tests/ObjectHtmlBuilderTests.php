<?php
namespace Tests;

use niiknow\ObjectHtmlBuilder;

class ObjectHtmlBuilderTests extends \PHPUnit\Framework\TestCase
{
    public function test_init_ObjectHtmlBuilder_with_no_options()
    {
        $this->assertTrue(is_object(new ObjectHtmlBuilder()));
    }

    public function test_ObjectHtmlBuilder_with_bad_json_string()
    {
        $builder  = new ObjectHtmlBuilder();
        $object   = 'hi';
        $expected = '<div></div>';
        $str      = $builder->toHtml($object);
        $this->assertEquals($expected, $str);
    }

    public function test_ObjectHtmlBuilder_with_self_closing_tag()
    {
        $builder  = new ObjectHtmlBuilder();
        $object   = '{ "input": "" }';
        $expected = '<div></div>';
        $str      = $builder->toHtml($object);
        $this->assertEquals('<div><input/></div>', $str);
    }

    public function test_ObjectHtmlBuilder_with_simple_array_json_string()
    {
        $builder  = new ObjectHtmlBuilder(['indent' => '  ']);
        $object   = '[{"john": "doe"}, {"cow": [{"boy": "john"}]}]';
        $expected = '<div>
  <john>doe</john>
  <cow>
    <boy>john</boy>
  </cow>
</div>';
        $str      = $builder->toHtml($object);
        $this->assertEquals($expected, $str);
    }

    public function test_ObjectHtmlBuilder_with_html_escape()
    {
        $builder  = new ObjectHtmlBuilder(['indent' => '  ']);
        $object   = '[{"john": "<doe"}, {"cow": [{"boy": "\'john"}]}]';
        $expected = '<div>
  <john>&lt;doe</john>
  <cow>
    <boy>&#039;john</boy>
  </cow>
</div>';
        $str      = $builder->toHtml($object);
        $this->assertEquals($expected, $str);
    }

    public function test_ObjectHtmlBuilder_complex_html()
    {
        $builder  = new ObjectHtmlBuilder(['indent' => '  ']);
        $object   = '{
    "div": "some simple text",
    "a": {
        "_attrs": {"title": "google", "href": "https://google.com"},
        "_content": [
            {"i": { "_attrs": { "class": "fa fa-pencil"} } },
            {"span": "link to google"}
        ],
        "test": "test"
    },
    "section": {
        "_attrs": {"title": "Raw Data", "class": "go go power ranger go ranger"},
        "_html": "<div>hohoho</div>"
    }
}';
        $expected = '<div>
  <div>some simple text</div>
  <a href="https://google.com" title="google">
    <i class="fa fa-pencil"></i>
    <span>link to google</span>
    <test>test</test>
  </a>
  <section class="go power ranger" title="Raw Data">
    <div>hohoho</div>
  </section>
</div>';
        $str      = $builder->toHtml($object);
        $this->assertEquals($expected, $str);
    }
}
