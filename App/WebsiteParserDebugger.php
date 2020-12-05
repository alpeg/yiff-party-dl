<?php

namespace App;

use DiDom\Document;
use DiDom\Element;
use Exception;
use Throwable;

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
        try {
            $this->postMustBeEmpty('>*', true);
            if (!$this->post->html() == '') {
                throw new Exception("Post must be absolutely empty after parsing");
            }
        } catch (Throwable $t) {
            echo "\n==========================\n";
            echo htmlspecialchars($this->post->html());
            echo "\n==========================\n";
            throw $t;
        }
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

    public function postUnwrap($expression, $expression2) {
        // $expression2 = '>* ' . str_replace(',', ',>* ', $expression2);
        foreach (explode(',', $expression2) as $tag) {
            $ex = "{$expression} {$tag}";
            $i = 0;
            do {
                if ($i++ > 5000) // 224084 page24 p6668049 - 200, 7738442 page5 p29348959 - ??
                    throw new Exception("postUnwrap very deep loop here!");
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
            throw new Exception("Debugger error: {$text}");
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
        return $this;
    }

    public function postMustBeOnly($expression) {
        $c = count($this->post->find($expression));
        if ($c > 1) {
            throw new Exception("Debugger error: Expected \"{$expression}\" to appear only once, {$c} times found!");
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
                throw new Exception("Debugger error: Expected \"{$expression}\" posts to have no children!");
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
                throw new Exception("Debugger error: Expected \"{$expression}\" posts to have no text!");
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
