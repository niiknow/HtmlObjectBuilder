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

    protected $makeTagHandlers       = [];
    protected $beforeMakeTagHandlers = [];

    public $options;

    /**
     * Initialize an instance of \niiknow\ObjectHtmlBuilder
     * @param array $options rendering options
     */
    public function __construct($options = null)
    {
        $this->options = [
            'indent'   => '',
            'noescape' => false
        ];

        if (is_array($options)) {
            // merge default
            foreach ($options as $k => $v) {
                if (isset($v)) {
                    $this->options[$k] = $v;
                }
            }
        }

        $this->makeTagHandlers['*']     = [$this, 'makeTagInternal'];
        $this->makeTagHandlers['_html'] = function (MakeTagEvent $evt) {
            $indent = $evt->indent;
            $tc     = trim('' . $evt->content);

            if ($tc[0] !== '<') {
                $indent = '';
            }

            $evt->rst = $indent . $evt->content;
            return $evt;
        };
    }

    public function registerOnBeforeMakeTag($tagHandlers)
    {
        if (isset($tagHandlers)) {
            // merge default
            foreach ($tagHandlers as $k => $v) {
                if ($v instanceof Closure) {
                    $this->beforeMakeTagHandlers[$k] = $v;
                }
            }
        }
    }


    public function registerOnMakeTag($tagHandlers)
    {
        if (isset($tagHandlers)) {
            // merge default
            foreach ($tagHandlers as $k => $v) {
                if ($v instanceof Closure) {
                    $this->makeTagHandlers[$k] = $v;
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

        $html = trim($this->makeHtml($tagName, $obj, $attrs, 0));
        // $html = preg_replace('/(>aaa\s+aaa)/', '>aaa', $html );
        //$html = preg_replace('/zzz/', "\n", $html);
        return $html;
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
        $nodes  = [];
        $indent = '';

        if (!empty($this->options['indent'])) {
            $indent = "\n" . str_repeat($this->options['indent'], $level);
        }
        // echo "$tagName:$indent:$level";

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

            $content = trim(implode('', $ret));

            if (isset($tagName)) {
                $content = trim(
                    $this->makeTag(
                        $node_data,
                        $tagName,
                        implode('', $ret),
                        $attrs,
                        $level
                    )
                );
            }

            if (count($ret) > 1) {
                $indent .= str_repeat($this->options['indent'], 1);
            }

            return $indent . $content;
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
                        $level
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

        $content = trim(
            $this->makeTag(
                $node_data,
                $tagName,
                $this->escHelper($node_data),
                $attrs,
                $level
            )
        );

        return $indent . $content;
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
        $attrs       = (array)$evt->attrs;
        $node        = [];
        $attr        = '';
        $node        = [$indent, '<', $tag];
        $content     = $evt->content;
        ksort($attrs);
        // echo "\n$tag:$indent:$evt->level";

        foreach ($attrs as $k => $v) {
            // unique handling for class attribute
            if ($k === 'class') {
                $vv = isset($v) ? $v : [];

                if (is_string($v)) {
                    $vv = explode(' ', $v);
                }

                // make sure classes are unique
                $vv = array_unique($vv);
                $attr .= ' ' . $k . '="' . implode(' ', $vv) . '"';
            } else {
                $attr .= ' ' . $k . '="' . $this->esc($v) . '"';
            }
        }

        if (!empty($attr)) {
            $node[] = $attr;
        }

        $tc = trim('' . $content);
        if (!empty($tc)) {
            if ((substr($tc, -1) !== '>')) {
                $indent = '';
            }

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

        // allow user to intercept and add special attributes
        // such as class if required
        if (isset($this->beforeMakeTagHandlers[$tag])) {
            $this->beforeMakeTagHandlers[$tag]($evt);
        }

        if ($evt->cancel === false) {
            // then we make the tag
            if (isset($this->makeTagHandlers[$tag])) {
                $this->makeTagHandlers[$tag]($evt);
            } else {
                // use defualt handler
                $this->makeTagHandlers['*']($evt);
            }
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
        return $this->options['noescape'] === false ? $this->esc($str) : $str;
    }
}
