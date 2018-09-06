<?php
namespace niiknow;

class ObjectHtmlBuilder
{
    protected $autocloseTags = ',img,br,hr,input,area,link,meta,param,base,col,command,keygen,source,';
    protected $descriptorNodes = ',tagName,';

    public function __construct($options = null)
    {
        $this->options = [
            'indent'                      => "  ",
            'prettyPrint'                 => true,
            'escape'                      => true
        ];

        if (isset($options)) {
            // merge default
            foreach ($options as $k => $v) {
                $this->options[$k] = $v;
            }
        }
    }

    public function esc($str)
    {
        return htmlentities($str, ENT_QUOTES);
    }

    public function unesc($str)
    {
        return html_entity_decode($str, ENT_QUOTES);
    }

    public function toHtml($obj, $tagName = 'div', $attrs = [])
    {
        if (!isset($obj)) {
            return '<!-- empty object -->';
        }

        // now convert json back to array
        if (is_string($obj)) {
            $obj = json_decode($obj);
        }

        return trim($this->makeHtml($tagName, $obj, $attrs, 0));
    }

    // helper functions
    protected function makeHtml($tagName, $node_data, $attrs = [], $level = 0)
    {
        $hasSubNodes = false;
        $nodes       = [];
        $indent      = '';

        if ($this->options['prettyPrint'] !== false) {
            $indent =  "\n" . str_repeat($this->options['indent'], $level);
        }

        if (is_array($node_data)) {
            // this must be content
            $ret = [];

            foreach ($node_data as $obj) {
                if (is_object($obj)) {
                    $v = (array)$obj;

                    $ret[] = $this->makeHtml(
                        null,
                        $obj,
                        isset($v['attributes']) ? $v['attributes'] : [],
                        $level
                    );
                }
            }

            if (isset($tagName)) {

                return $this->makeNode(
                    $tagName,
                    implode('', $ret),
                    $attrs,
                    $level,
                    count($ret) > 0
                );
            }

            return $indent . implode('', $ret);
        } elseif (is_object($node_data)) {
            $hasSubNodes = true;
            $ret = [];
            // must be an array of object property
            foreach ($node_data as $k => $v) {
                $ret[] = $this->makeHtml(
                    $k,
                    $v,
                    isset($v['attributes']) ? $v['attributes'] : [],
                    $level + 1
                );
            }

            if (!isset($tagName)) {
                // since there is no tag name, just return the content
                return implode('', $ret);
            }

            return $this->makeNode(
                $tagName,
                implode('', $ret),
                $attrs,
                $level,
                $hasSubNodes
            );
        }

        return $indent . $this->makeNode(
            $tagName,
            $node_data,
            $attrs,
            $level,
            $hasSubNodes
        );
    }

    protected function makeNode($name, $content, $attrs, $level, $hasSubNodes = false)
    {
        $indent = '';

        if ($this->options['prettyPrint'] && $hasSubNodes) {
            $indent = "\n" . str_repeat($this->options['indent'], $level);
        }

        // recursive html call for array
        if (is_array($content)) {
            return $this->makeHtml($name, $content, $attrs, $level + 1);
        }

        if (!isset($attrs)) {
            $attrs = [];
        }

        $attr   = '';

        $node = [$indent, '<', $name];

        foreach ($attrs as $k => $v) {
            $attr .= ' ' . $k . '="' . $this->esc($v) . '"';
        }

        if (!empty($attr)) {
            $node[] = $attr;
        }

        if (!empty(trim('' . $content))) {
            $node[] = '>';
            $node[] = $content;
            $node[] = $indent;
            $node[] = '</';
            $node[] = $name;
            $node[] = '>';
        } elseif (strpos($this->autocloseTags, ',' . $name . ',') === false) {
            $node[] = '></';
            $node[] = $name;
            $node[] = '>';
        } else {
            $node[] = '/>';
        }

        return implode('', $node);
    }

    protected function escHelper($str)
    {
        return $this->options['escape'] !== false ? $this->esc($str) : $str;
    }
}
