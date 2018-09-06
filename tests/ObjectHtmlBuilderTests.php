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
        $builder = new ObjectHtmlBuilder([ "prettyPrint" => false ]);
        $object  = 'hi';
        $expected = '<div></div>';
        $str = $builder->toHtml($object);
        $this->assertEquals('<div></div>', $str);
    }

    public function test_ObjectHtmlBuilder_with_self_closing_tag()
    {
        $builder = new ObjectHtmlBuilder([ "prettyPrint" => false ]);
        $object  = '{ "input": "" }';
        $expected = '<div></div>';
        $str = $builder->toHtml($object);
        $this->assertEquals('<div><input/></div>', $str);
    }

    public function test_ObjectHtmlBuilder_with_simple_array_json_string()
    {
        $builder = new ObjectHtmlBuilder();
        $object  = '[{"john": "doe"}, {"cow": [{"boy": "john"}]}]';
        $expected = '<div>
  <john>doe</john>
  <cow>
    <boy>john</boy>
  </cow>
</div>';
        $str = $builder->toHtml($object);
        $this->assertEquals($expected, $str);
    }

     public function test_ObjectHtmlBuilder_with_html_escape()
    {
        $builder = new ObjectHtmlBuilder();
        $object  = '[{"john": "<doe"}, {"cow": [{"boy": "\'john"}]}]';
        $expected = '<div>
  <john>&lt;doe</john>
  <cow>
    <boy>&#039;john</boy>
  </cow>
</div>';
        $str = $builder->toHtml($object);
        $this->assertEquals($expected, $str);
    }
}
