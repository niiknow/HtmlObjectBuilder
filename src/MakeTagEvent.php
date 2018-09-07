<?php
namespace niiknow;

/**
 * Event object trigger on tag rendering
 */
class MakeTagEvent
{
    public $tag;
    public $object;
    public $content;
    public $attrs;
    public $level;
    public $isHtml;
    public $hasSubNodes;
    public $rst;
    public $indent = '';

    /**
     * Initialize an instance of \niiknow\MakeTagEvent
     * @param array $options rendering options
     */
    public function __construct($builder, $object, $tag, $content, $attrs = [], $level = 0)
    {
        if (!isset($attrs)) {
            $attrs = [];
        }

        $this->builder = $builder;
        $this->tag     = $tag;
        $this->object  = $object;
        $this->content = $content;
        $this->attrs   = $attrs;
        $this->level   = $level;
        $this->isHtml  = $tag === '_html';

        $hasSubNodes = false;

        if (($builder->getProp($object, '_content') !== null
            && $builder->getProp($object, '_html') !== null)
            || (is_string($content) && (strpos(trim($content), '<') === 0))
        ) {
            $hasSubNodes = true;
        }

        $this->hasSubNodes = $hasSubNodes;

        if ($builder->options['prettyPrint'] && $hasSubNodes) {
            $this->indent = "\n" . str_repeat($builder->options['indent'], $level);
        }
    }
}
