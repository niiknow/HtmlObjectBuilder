<?php
namespace niiknow;

/**
 * Object to HTML Builder
 */
class ObjectHtmlBuilder
{
    /**
     * HTML tags that support auto-close
     * @var string
     */
    protected $autocloseTags = ',img,br,hr,input,area,link' .
        ',meta,param,base,col,command,keygen,source,';

    protected $tagHandlers = [];

    /**
     * Initialize an instance of \niiknow\ObjectHtmlBuilder
     * @param array $options rendering options
     */
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

        $this->tagHandlers['*']     = [$this, 'makeTagInternal'];
        $this->tagHandlers['_html'] = function (MakeTagEvent $evt) {
            $evt->rst = '' . $evt->content;
            return $evt;
        };
    }

    public function registerTagHandlers($tagHandlers)
    {
        if (isset($tagHandlers)) {
            // merge default
            foreach ($tagHandlers as $k => $v) {
                if ($v instanceof Closure) {
                    $this->tagHandlers[$k] = $v;
                }
            }
        }
    }

    /**
     * Method use to escape html
     * @param  string $str the string to escape
     * @return string      html escaped string
     */
    protected function esc($str)
    {
        return htmlentities($str, ENT_QUOTES);
    }

    /**
     * method use to unescape html
     * @param  string $str the string to unescape
     * @return string      the unescaped string
     */
    public function unesc($str)
    {
        return html_entity_decode($str, ENT_QUOTES);
    }

    /**
     * Convert an object to html
     * @param  mixed $obj      object, array, or json string to convert
     * @param  string $tagName the tag name
     * @param  array  $attrs   any attributes
     * @return string          the conversion result
     */
    public function toHtml($obj, $tagName = 'div', $attrs = [])
    {
        if (is_string($obj)) {
            $obj = json_decode($obj);
        } elseif (!isset($obj)) {
            $obj = '';
        }

        return trim($this->makeHtml($tagName, $obj, $attrs, 0));
    }

    // helper functions
    /**
     * Get an object or array property
     * @param  mixed  $obj     object or array
     * @param  string $prop    property to get
     * @param  mixed  $default default value if not found
     * @return mixed           the property value
     */
    public function getProp($obj, $prop, $default = null)
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

    /**
     * Internal method to convert object to html
     * @param  string  $tagName   the html tag name
     * @param  mixed   $node_data object or array data of node
     * @param  array   $attrs     node attributes
     * @param  integer $level     current level
     * @return string             html result
     */
    protected function makeHtml($tagName, $node_data, $attrs = [], $level = 0)
    {
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
                        $this->getProp($obj, '_tag'),
                        $obj,
                        $this->getProp($obj, '_attrs', []),
                        $level
                    );
                }
            }

            if (isset($tagName)) {
                return $this->makeTag(
                    $node_data,
                    $tagName,
                    implode('', $ret),
                    $attrs,
                    $level,
                    count($ret) > 0
                );
            }

            return $indent . implode('', $ret);
        } elseif (is_object($node_data)) {
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
                } elseif ($k === '_html') {
                    $ret[] = $this->makeTag(
                        $node_data,
                        $k,
                        $v,
                        $attrs,
                        $level + 1
                    );
                } elseif ($k === '_content') {
                    // handle inner content
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

            return $this->makeTag(
                $node_data,
                $tagName,
                implode('', $ret),
                $attrs,
                $level
            );
        }

        return $indent . $this->makeTag(
            $node_data,
            $tagName,
            $this->escHelper($node_data),
            $attrs,
            $level
        );
    }

    /**
     * Internal method for making tag
     * @param  MakeTagEvent $evt the tag making event
     * @return MakeTagEvent            the tag making event
     */
    protected function makeTagInternal(MakeTagEvent $evt)
    {
        $indent      = $evt->indent;
        $hasSubNodes = $evt->hasSubNodes;
        $tag         = $evt->tag;
        $attrs       = $evt->attrs;
        $node        = [];
        $attr        = '';
        $node        = [$indent, '<', $tag];
        $content     = $evt->content;

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
            $node[] = $tag;
            $node[] = '>';
        } elseif (strpos($this->autocloseTags, ',' . $tag . ',') === false) {
            $node[] = '></';
            $node[] = $tag;
            $node[] = '>';
        } else {
            $node[] = '/>';
        }

        $evt->rst = implode('', $node);

        return $evt;
    }

    /**
     * Helper method to create a XML or HTML node/tag
     * @param  mixed    $object      the node/tag object, array, or string
     * @param  string   $tag         the node/tag name
     * @param  string   $content     the node/tag content
     * @param  array    $attrs       the node/tag attributes
     * @param  integer  $level       current node/tag level
     * @return string                the XML or HTML representable of node/tag
     */
    protected function makeTag($object, $tag, $content, $attrs, $level)
    {
        $evt = new MakeTagEvent($this, $object, $tag, $content, $attrs, $level);

        if (isset($this->tagHandlers[$tag])) {
            $this->tagHandlers[$tag]($evt);
        } else {
            // use defualt handler
            $this->tagHandlers['*']($evt);
        }

        return $evt->rst;
    }

    /**
     * Helper method to escape string only if defined
     *
     * @param  string $str the string to escape
     * @return string      the escaped string if escape option is true
     */
    protected function escHelper($str)
    {
        return $this->options['escape'] !== false ? $this->esc($str) : $str;
    }
}
