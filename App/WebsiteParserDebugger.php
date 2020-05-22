<?php

namespace App;

use DiDom\Document;
use DiDom\Element;
use Error;

/**
 * Description of WebsiteParserDebugger
 *
 * @author https://github.com/alpeg
 * @license MIT
 */
class WebsiteParserDebugger {

    public $debug = false;

    public function __construct($debug) {
        $this->debug = $debug;
    }

    public ?Document $post;

    public function debugPost(Element $post) {
        $this->post = $post->toDocument();
        $this->postRmAll('i.material-icons');
    }

    public function debugPostEnd() {
        echo htmlspecialchars($this->post->html());
        echo "\n==========================\n==========================\n==========================\n";
        $this->post = null;
    }

    public function postRmAll($expression, callable $if = null) {
        foreach ($this->post->find($expression) as $v) {
            if ($if !== null && !$if($v))
                continue;
            $v->remove();
        }
        return $this;
    }

    public function postUnwrapFAIL1($expression, $expression2) {
        $expression2 = '>* ' . str_replace(',', ',>* ', $expression2);
        foreach ($this->post->find($expression) as $v) {
            $i = 0;
            do {
                if ($i++ > 20)
                    throw new Error("postUnwrap very deep loop here!");
                $changed = 0;
                foreach ($v->findInDocument($expression2) as $v2) {
                    // echo "BEFORE: " . htmlspecialchars($v->innerHtml()) . "\n\n\n";
                    // echo print_r($v2);
                    // ===
                    // $parent = $v2->parent();
                    // foreach ($v2->children() as $child) { $v2->insertSiblingBefore($child->cloneNode(true)); }$v2->remove();
                    // ===
                    // $parent = $v2->parent();$children = $v2->children();$sibl = $v2->nextSibling();$v2->remove();
                    // if ($sibl) {foreach ($children as $child) {$parent->insertBefore($child, $sibl);}
                    // } else {foreach ($children as $child) {$parent->appendChild($child);}}
                    // ===
                    // foreach ($children as $child) {$parent->insertBefore($child, $v2);}
                    // ===
                    $ch = $v2->children();
                    $v2->nextSibling();
                    if (count($ch) == 0) {
                        $v2->remove();
                    } else {
                        $v2->replace($ch[0]);
                        $ch0 = array_shift($ch);
                        foreach ($ch as $c) {
                            $ch0->insertAfter($c);
                            $ch0 = $c;
                        }
                    }
                    // echo "AFTER: " . htmlspecialchars($v->innerHtml()) . "\n\n\n";
                    $changed++;
                }
            } while ($changed > 0);
        }
        return $this;
    }

    public function postUnwrapFAIL2($expression, $expression2) {
        $expression2 = '>* ' . str_replace(',', ',>* ', $expression2);
        foreach (explode(',', $expression2) as $tag1) {
            
        }
        foreach ($this->post->find($expression) as $v) {
            $i = 0;
            do {
                if ($i++ > 30)
                    throw new Error("postUnwrap very deep loop here!");
                $continueLoop = false;
                $v2 = $v->firstInDocument($expression2);
                if ($v2) {
                    foreach ($v2->children() as $child) {
                        $v2->insertSiblingBefore($child);
                    }
                    $v2->remove();
                    $continueLoop = true;
                }
            } while ($continueLoop);
        }
        return $this;
    }

    public function postUnwrap($expression, $expression2) {
        // $expression2 = '>* ' . str_replace(',', ',>* ', $expression2);
        foreach (explode(',', $expression2) as $tag) {
            $ex = "{$expression} {$tag}";
            $i = 0;
            do {
                if ($i++ > 50)
                    throw new Error("postUnwrap very deep loop here!");
                $continueLoop = false;
                $v2 = $this->post->first($ex);
                if ($v2) {
                    $p = $v2->parent();

                    foreach ($v2->children() as $child) {
                        // echo htmlspecialchars("<tag>" .  . "</tag>");
                        // if (!$child->isTextNode()) {}
                        $p->appendChild($child);
                    }
                    $v2->remove();
                    $continueLoop = true;
                }
            } while ($continueLoop);
        }
        return $this;
    }

    public function postAssert($if, $text) {
        if (!$if) {
            throw new Error("Debugger error: {$text}");
        }
    }

    public function postRmFirst($expression) {
        $first = $this->post->first($expression);
        if ($first)
            $first->remove();
        return $this;
    }

    public function postMust1Rm($expression) {
        $this->postMustBeOnly($expression)->postRmFirst($expression);
    }

    public function postMustBeOnly($expression) {
        $c = count($this->post->find($expression));
        if ($c > 1) {
            throw new Error("Debugger error: Expected \"{$expression}\" to appear only once, {$c} times found!");
        }
        return $this;
    }

    public function postMustBeEmpty($expression, $remove = false) {
        $this->postMustNotHaveChildren($expression);
        $this->postMustNotHaveText($expression);
        if ($remove) {
            $this->postRmAll($expression);
        }
        return $this;
    }

    public function postMustNotHaveChildren($expression, $remove = false) {
        $p = $this->post->find($expression);
        if (count($p) == 0)
            return $this;
        foreach ($p as $pp) {
            $ch = $pp->find('>*>*');
            if (count($ch) > 0) {
                $i = 0;
                foreach ($ch as $v) {
                    $i++;
                    echo "children {$i}: " . htmlspecialchars($v->html()) . "\n";
                }
                throw new Error("Debugger error: Expected \"{$expression}\" posts to have no children!");
            }
        }
        if ($remove) {
            $this->postRmAll($expression);
        }
        return $this;
    }

    public function postMustNotHaveText($expression, $remove = false) {
        $p = $this->post->find($expression);
        if (count($p) == 0)
            return $this;
        foreach ($p as $pp) {
            $text = $pp->first('>*::text');
            if (!preg_match('#\\A\\s*\\z#', $text)) {
                echo "Text found: \"{$text}\"\n";
                throw new Error("Debugger error: Expected \"{$expression}\" posts to have no text!");
            }
        }
        if ($remove) {
            $this->postRmAll($expression);
        }
        return $this;
    }

    public function shitstorm($expression) {
        echo "### SHITSTORM, SELECTOR: {$expression}\n";
        $i = 0;
        foreach ($this->post->find($expression) as $expr) {
            $i++;
            echo " [|||] FOUND #{$i} [|||] " . htmlspecialchars($expr) . "\n";
        }
        echo "### SHITSTORM END, SELECTOR: {$expression}\n";
    }

}
