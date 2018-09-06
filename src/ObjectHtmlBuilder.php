<?php
namespace niiknow;

class ObjectHtmlBuilder
{
    protected $autocloseTags = ',img,br,hr,input,area,link' .
        ',meta,param,base,col,command,keygen,source,';

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
    protected function getProp($obj, $prop, $default = null)
    {
        if (is_object($obj)) {
            if (property_exists($obj, $prop)) {
                return $obj->{$prop};
            }
        } elseif (is_array($obj)) {
            if (isset($obj[$prop])) {
                return $obj[$prop];
            }
        }

        return $default;
    }

    protected function makeHtml($tagName, $node_data, $attrs = [], $level = 0)
    {
        $hasSubNodes = false;
        $nodes       = [];
        $indent      = '';

        if ($this->options['prettyPrint'] !== false) {
            $indent =  "\n" . str_repeat($this->options['indent'], $level);
        }

        if (is_array($node_data)) {
            // this must be content array
            $ret = [];

            foreach ($node_data as $obj) {
                if (is_object($obj)) {
                    $ret[] = $this->makeHtml(
                        null,
                        $obj,
                        $this->getProp($obj, '_attrs', []),
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
                // ignore properties that start with underscore
                $pos = strpos($k, '_');
                if ($pos === false || $pos > 0) {
                    $ret[] = $this->makeHtml(
                        $k,
                        $v,
                        $this->getProp($v, '_attrs', []),
                        $level + 1
                    );
                } elseif ($k === '_content') {
                    // handle inner conntect
                    $ret[] = $this->makeHtml(
                        null,
                        $v,
                        $this->getProp($v, '_attrs', []),
                        $level + 1
                    );
                }
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
            $this->escHelper($node_data),
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
