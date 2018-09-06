<?php
namespace niiknow;

class ObjectHtmlBuilder
{
    protected $autocloseTags = ',img,br,hr,input,area,link,meta,param,base,col,command,keygen,source,';
    protected $descriptorNodes = ',tagName,';

    public function __construct($options = null)
    {
        $this->options = [
            'indent'                      => "\t",
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

        // the first item is always an array/content
        if (is_object($obj)) {
            $obj = json_encode((array)$obj);
        }

        // now convert json back to array
        if (is_string($obj)) {
            $obj = json_decode($obj, true);
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

        echo "\n$level:" . $tagName . ': ' . json_encode($node_data) . "\n";

        // node_data tag name can override root data
        if (isset($node_data['tagName'])) {
            echo "\n" . 'wooo'. "\n";
            $tagName = $node_data['tagName'];
            echo "\n" . $tagName . ': ' . json_encode($node_data) . "\n";
        }

        // handle content tag, build content
        if ($tagName === 'content') {
            echo "\nboo\n";
            // go deeper, but keep level the same
            if (is_array($node_data)) {
                foreach ($node_data as $k => $v) {
                    // ignore descriptor nodes
                    if (strpos($this->descriptorNodes, $k) === false) {
                        $nodes[] = $this->makeHtml(
                            is_numeric($k) ? $tagName : $k,
                            $v,
                            isset($v['attributes']) ? $v['attributes'] : [],
                            $level + 1
                        );
                    }
                }

                return $indent . implode('', $nodes);
            } else {
                echo "\nwhooo\n";
                // since it's content node, no need to make node, just return
                return $indent . $this->escHelper('' . $node_data);
            }
        } elseif (is_array($node_data) && isset($node_data['content'])) {
            echo "\nskip to: content\n";
            $nodes[] = $this->makeHtml('content', $node_data['content'], [], $level);

            // finally, make the current node
            return $this->makeNode(
                $tagName,
                implode('', $nodes),
                $attrs,
                $level,
                true
            );
        } else {
            echo "\nblah\n";
            $nodes[] = $node_data;
        }

        $indent = '';
        if ($this->options['prettyPrint'] && $hasSubNodes) {
            $indent =  "\n" . str_repeat($this->options['indent'], $level);
            $nodes[] = $indent;
        }

        // finally, make the current node
        return $this->makeNode(
            $tagName,
            implode('', $nodes),
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

        if (strpos(',' . $name . ',', $this->autocloseTags) !== false) {
            $node[] = '>';
            $node[] = '</';
            $node[] = $name;
            $node[] = '>';
        } elseif (!isset($content)) {
            $node[] = '/>';
        } else {
            $node[] = '>';
            $node[] = '' . $content;
            $node[] = $indent;
            $node[] = '</';
            $node[] = $name;
            $node[] = '>';
        }

        return implode('', $node);
    }

    protected function escHelper($str)
    {
        return $this->options['escape'] !== false ? $this->esc($str) : $str;
    }
}

<?php
namespace niiknow;

class ObjectHtmlBuilder
{
    protected $autocloseTags = ',img,br,hr,input,area,link,meta,param,base,col,command,keygen,source,';
    protected $descriptorNodes = ',tagName,';

    public function __construct($options = null)
    {
        $this->options = [
            'indent'                      => "\t",
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

        // the first item is always an array/content
        if (is_object($obj)) {
            $obj = json_encode((array)$obj);
        }

        // now convert json back to array
        if (is_string($obj)) {
            $obj = json_decode($obj, true);
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

        echo "\n$level:" . $tagName . ': ' . json_encode($node_data) . "\n";

        if (is_array($node_data)) {
            // this must be content
            $ret = [];

            foreach ($node_data as $v) {
                $ret[] = $this->makeHtml(
                    null,
                    $v,
                    isset($v['attributes']) ? $v['attributes'] : [],
                    $level + 1
                );
            }

            return implode('', $ret);
        } elseif (is_object($node_data)) {
            $ret = [];
            // must be an array of object
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
                $node_data,
                $attrs,
                $level,
                $hasSubNodes
            );
        }
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

        if (strpos(',' . $name . ',', $this->autocloseTags) !== false) {
            $node[] = '>';
            $node[] = '</';
            $node[] = $name;
            $node[] = '>';
        } elseif (!isset($content)) {
            $node[] = '/>';
        } else {
            $node[] = '>';
            $node[] = '' . $content;
            $node[] = $indent;
            $node[] = '</';
            $node[] = $name;
            $node[] = '>';
        }

        return implode('', $node);
    }

    protected function escHelper($str)
    {
        return $this->options['escape'] !== false ? $this->esc($str) : $str;
    }
}

