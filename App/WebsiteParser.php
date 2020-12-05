<?php

namespace App;

use DiDom\Document;
use DiDom\Element;
use DiDom\Query;
use Exception;
use Throwable;

/**
 * Description of WebsiteParser
 *
 * @author https://github.com/alpeg
 * @license MIT
 */
class WebsiteParser {

    public static function parseBytes($str) {
        if (!preg_match('#\\A([\d.]+)([KMGT]?)i?B\\z#i', trim($str), $m))
            return null;
        $num = $m[1];
        $num = filter_var($num, FILTER_VALIDATE_FLOAT, ['flags' => FILTER_NULL_ON_FAILURE]);
        if ($num === null)
            return null;
        $prefix = strtoupper($m[2]);
        switch ($prefix) {
            case 'K':$num *= 1024;
                break;
            case 'M':$num *= 1048576;
                break;
            case 'G':$num *= 1073741824;
                break;
            case 'T':$num *= 1099511627776;
                break;
            // default: break;
        }
        $num = round($num);
        return $num;
    }

    public static function debugLeftovers() {
        
    }

    public static function parseSplash($html, $dbgr = false) {
        $dbgr = $dbgr ? (new WebsiteParserDebugger($dbgr)) : null;
        /** @var WebsiteParserDebugger $dbgr */
        $doc = new Document($html);
        $selectHban = "main > #index-banner ";
        $o = [];
        $o['meta'] = [];
        $o['meta']['name'] = rtrim($doc->first("{$selectHban}.yp-info-name::text"), ' ');
        $o['meta']['name_small'] = preg_replace('#\\A\\((.*)\\)\\z#s', '$1', $doc->first("{$selectHban}.yp-info-name>small::text"));
        $o['meta']['cuf'] = $doc->first("{$selectHban}.yp-info-cuf>a::text");
        $o['meta']['cuf_count'] = $doc->first("{$selectHban}.yp-cuf-count::text");
        $o['meta']['update_status'] = null;
        $update_status = $doc->first("{$selectHban}.yp-info-last");
        if ($update_status) {
            $o['meta']['update_status'] = $update_status->text();
        }
        $idPosts = $doc->first('#posts');
        $idSf = $doc->first('#shared_files');
        if (!$idPosts)
            throw new Exception("Parse error: no #posts!");
        if (!$idSf)
            throw new Exception("Parse error: no #shared_files!");
        $o['meta']['posts_count'] = null;
        $o['meta']['shared_files_count'] = null;
        $tabs = $idPosts->parent()->first('.tabs');
        if ($tabs) {
            $o['meta']['posts_count'] = filter_var(
                    preg_replace('#\\A.*\\((.*)\\).*\\z#s', '$1', $tabs->first('a[href="#posts"]::text')),
                    FILTER_VALIDATE_INT, ['flags' => FILTER_NULL_ON_FAILURE]
            );
            $o['meta']['shared_files_count'] = filter_var(
                    preg_replace('#\\A.*\\((.*)\\).*\\z#s', '$1', $tabs->first('a[href="#shared_files"]::text')),
                    FILTER_VALIDATE_INT, ['flags' => FILTER_NULL_ON_FAILURE]
            );
        }
        $pages1 = $idPosts->first('.yp-posts-paginate');
        if (!$pages1)
            throw new Exception("Parse error: no pagination!");
        $pageText = $pages1->first('.paginate-count::text');
        if (!preg_match('#\\A\\s*+(\\d+)\\s*+/\\s*+(\\d+)\\s*+\\z#', $pageText, $m)) {
            throw new Exception("Parse error: missing pagination");
        }

        $pages = null;
        foreach ($pages1->find(".yp-posts-paginate-buttons") as $pages2) {
            $p = $pages2->find('a[data-pag]');
            if (count($p) == 0)
                continue;
            $pages = [];
            foreach ($p as $pa) {
                // $pa = $idPosts->first('');
                $text = $pa->first('text()', Query::TYPE_XPATH);
                $pages[strtolower($text)] = filter_var($pa->getAttribute('data-pag'), FILTER_VALIDATE_INT, ['flags' => FILTER_NULL_ON_FAILURE]);
            }
        }
        $o['meta']['page'] = filter_var($m[1], FILTER_VALIDATE_INT, ['flags' => FILTER_NULL_ON_FAILURE]);
        $o['meta']['pages'] = filter_var($m[2], FILTER_VALIDATE_INT, ['flags' => FILTER_NULL_ON_FAILURE]);
        $o['meta']['page_nav'] = $pages;

        $o['meta']['url_splash'] = null;
        if (preg_match('#\\A.*\'([^\']*)\'.*\\z#', $doc->first(".yp-info-col::attr(style)"), $m)) {
            $o['meta']['url_splash'] = $m[1];
        } else {
            throw new Exception("Parse error: no .yp-info-col::attr(style)");
        }
        $o['meta']['url_avatar'] = $doc->first(".yp-info-col .yp-info-avatar .yp-info-img::attr(src)");
        if (!$o['meta']['url_avatar']) {
            throw new Exception("Parse error: no .yp-info-col .yp-info-avatar .yp-info-img::attr(src)");
        }

        // POSTS

        $unwrapTags = 'a, strong, ul, ol, li, b, i, u, em, ins, div, p, font, blockquote, h1, h2, h3, h4, h5, h6, br, hr'; // @@@warn!

        $o['posts'] = [];
        $o['posts_storage_required'] = 0;
        foreach ($idPosts->find('.yp-posts-row .yp-post') as $post) {
            $dbgr && $dbgr->debugPost($post);
            $p = [];
            $p['post_storage_required'] = 0;
            /** @var $post \DiDom\Element */
            // ---
            // post id parameter id="p1234"
            $p['id'] = filter_var(
                    preg_replace('#\\Ap#', '', $post->first('@id', Query::TYPE_XPATH)),
                    FILTER_VALIDATE_INT, ['flags' => FILTER_NULL_ON_FAILURE]);
            // thumbnail url
            $p['thumb_url'] = $post->first('>*> .card-image > img::attr(data-src)');
            $dbgr && $dbgr->postMust1Rm('>*> .card-image > img')->postMustBeEmpty('>*> .card-image', true);
            // ---
            // .card-content #1
            // content_title
            $p['content_title'] = rtrim($post->first('>*> .card-content > .card-title::text'), ' ');
            $dbgr && $dbgr->postMust1Rm('>*> .card-content > .card-title');
            // content_time
            $p['content_time'] = $post->first('>*> .card-content > .post-time::text');
            $dbgr && $dbgr->postMust1Rm('>*> .card-content > .post-time');
            // ====================
            // .card-action
            // action_files
            $p['action_files'] = [];
            foreach ($post->find('>*> .card-action > a') as $a) {
                $p['action_files'][] = [
                    'name' => $a->first('text()', Query::TYPE_XPATH),
                    'url' => $a->attr('href'),
                ];
            }
            $dbgr && $dbgr->postMustBeOnly('>*> .card-action')
                            ->postRmAll('>*> .card-action > a')
                            ->postMustBeEmpty('>*> .card-action', true);
            // ###################
            // .card-reveal
            // reveal_title
            $p['reveal_title'] = rtrim($post->first('>*> .card-reveal > .card-title::text'), ' ');
            // reveal_time
            $p['reveal_time'] = $post->first('>*> .card-reveal > .card-title > .post-time::text');
            $dbgr && $dbgr->postMustBeOnly('>*> .card-reveal > .card-title')
                            ->postMustNotHaveChildren('>*> .card-reveal > .card-title > .post-time', true)
                            ->postMustNotHaveChildren('>*> .card-reveal > .card-title', true);

            // $p['reveal_body'] can have links, see 13756532 page6 id="p23991070"
            // reveal_body + reveal_body_inline_files
            $postBody = $post->first('>*> .card-reveal > .post-body');
            $dbgr && $dbgr->postMustBeOnly('>*> .card-reveal > .post-body');
            $p['reveal_body'] = $postBody ? ( $postBody->innerHtml() ) : null;
            $p['reveal_body_inline_files'] = null;
            if ($postBody) {
                $postBodyLinks = [];
                foreach ($postBody->find('a') as $a) {
                    $href = $a->attr('href');
                    if (!$href)
                        continue;
                    $thumb = $a->first('>*> img[data-src]::attr(data-src)');
                    // echo htmlspecialchars($a->html());die;
                    $href = preg_replace('#\\A/(patreon_)#', 'https://data.yiff.party/$1', $href);
                    $postBodyLinks[] = ['url' => $href, 'url_thumb' => $thumb];
                }
                if (count($postBodyLinks) > 0) {
                    $p['reveal_body_inline_files'] = $postBodyLinks;
                }
            }
            $dbgr && $dbgr->postRmAll('>*> .card-reveal > .post-body a', function(Element $e) {
                        $href = $e->attr('href');
                        return preg_match('#\\A/patreon_inline/#', $href);
                    });
            try {
                // if ($p['reveal_body'] && $p['reveal_body_inline_files']) {}
                $dbgr && $dbgr
                                ->postRmAll('>*> .card-reveal > .post-body br')
                                ->postRmAll('>*> .card-reveal > .post-body hr')
                                ->postRmAll('>*> .card-reveal > .post-body img[data-src]')
                                ->postUnwrap('>*> .card-reveal > .post-body', $unwrapTags) // @@@warn!
                                ->postMustNotHaveChildren('>*> .card-reveal > .post-body > p', true)
                                ->postMustNotHaveChildren('>*> .card-reveal > .post-body', true)
                ;
            } catch (Throwable $t) {
                $dbgr && $dbgr->shitstorm('>*> .card-reveal > .post-body');
                throw $t;
            }


            // reveal_comments
            $post1Comments = $post->first($post1cs = '>*> .card-reveal > .card-comments');
            $p['reveal_comments'] = $post1Comments ? $post1Comments->innerHtml() : null;
            $p['reveal_comments_avatars'] = null;
            if ($post1Comments) {
                $post1CommentsAvatars = $post1Comments->find('.yp-post-comment-avatar::attr(data-src)');
                if (\count($post1CommentsAvatars) > 0) {
                    $p['reveal_comments_avatars'] = $post1CommentsAvatars;
                }
            }
            $dbgr && $dbgr->postRmAll("{$post1cs} .yp-post-comment-avatar[data-src]")
                            ->postRmAll("{$post1cs} .yp-post-comment-head")
                            ->postRmAll("{$post1cs} img.post-img-inline[data-src]") // [data-media-id] // @@@warn!
                            // ->postRmAll("{$post1cs} .yp-post-comment-body img[src*=\"patreonusercontent.com/\"]") // @@@warn!
                            ->postRmAll("{$post1cs} .yp-post-comment-body img") // @@@warn!
                            ->postUnwrap("{$post1cs} .yp-post-comment-body", $unwrapTags) // @@@warn!
                            ->postMustNotHaveChildren("{$post1cs} .yp-post-comment-body", true)
                            ->postMustBeEmpty("{$post1cs} .yp-post-comment > div", true)
                            ->postMustBeEmpty("{$post1cs} .yp-post-comment", true)
                            ->postMustNotHaveChildren("{$post1cs} .card-title", true)
                            ->postMustBeEmpty("{$post1cs}", true)
            ;
            unset($post1cs);

            // 20259648 page1 p36073545
            // .card-reveal > .card-embed (copy-paste of content_embed - ".card-content > .card-embed")
            $embed = $post->first('>*> .card-reveal > .card-embed > div');
            $p['reveal_embed'] = $embed ? ( $embed->innerHtml() ) : null;
            if ($embed) {
                $embedText = $post->first('>*> .card-reveal > .card-embed > .card-title::text');

                $dbgr && $dbgr->postAssert(
                                !$embedText || trim($embedText) === 'Embed data',
                                ">*> .card-reveal > .card-embed > .card-title::text === {$embedText}"
                );
            }
            unset($embed, $embedText);
            $dbgr && $dbgr
                            ->postMustBeOnly('>*> .card-reveal > .card-embed')
                            ->postMustBeOnly('>*> .card-reveal > .card-embed > div')
                            ->postRmAll('>*> .card-reveal > .card-embed > div')
                            ->postRmAll('>*> .card-reveal > .card-embed > hr')
                            ->postMustNotHaveChildren(">*> .card-reveal > .card-embed > .card-title", true)
                            ->postMustBeEmpty('>*> .card-reveal > .card-embed', true)
            ;

            // ###################
            // .card-content #2
            // .card-content > .post-body
            $postBody2 = $post->first('>*> .card-content > .post-body');
            // if ($postBody2) {echo htmlspecialchars($postBody2);die;}
            $p['content_body'] = $postBody2 ? ( $postBody2->innerHtml() ) : null;

            // data-media-id="59020922" data-src
            $p['content_body_inline_files'] = null;
            if ($postBody2) {
                $postBody2Links = $postBody2->find('img.post-img-inline[data-src][data-media-id]'); // ::attr(data-src|data-media-id)
                if (count($postBody2Links) > 0) {
                    $p['content_body_inline_files'] = [];
                    foreach ($postBody2Links as $postBody2Link) {
                        $p['content_body_inline_files'][] = [
                            'url' => $postBody2Link->getAttribute('data-src'),
                            'media_id' => $postBody2Link->getAttribute('data-media-id'),
                        ];
                        // 'url_thumb' => '?',
                    }
                }
            }
            $dbgr && $dbgr->postRmAll('>*> .card-content > .post-body a', function(Element $e) {
                        $href = $e->attr('href');
                        return preg_match('#\\A/patreon_inline/#', $href);
                    });
            $dbgr && $dbgr
                            ->postRmAll('>*> .card-content > .post-body br')
                            ->postRmAll('>*> .card-content > .post-body img.post-img-inline[data-src][data-media-id]')
                            ->postUnwrap('>*> .card-content > .post-body', $unwrapTags) // @@@warn!
                            ->postMustNotHaveChildren('>*> .card-content > .post-body > p', true)
                            ->postMustNotHaveChildren('>*> .card-content > .post-body', true)
            ;

            // .card-content > .card-comments
            $post2cs = '>*> .card-content > .card-comments';
            $post2Comments = $post->first('>*> .card-content > .card-comments');
            $p['content_comments'] = $post2Comments ? $post2Comments->innerHtml() : null;
            $p['content_comments_avatars'] = null;
            if ($post1Comments) {
                $post1CommentsAvatars = $post1Comments->find('.yp-post-comment-avatar::attr(data-src)');
                if (\count($post1CommentsAvatars) > 0) {
                    $p['content_comments_avatars'] = $post1CommentsAvatars;
                }
            }
            $dbgr && $dbgr->postRmAll("{$post2cs} .yp-post-comment-avatar[data-src]")
                            ->postRmAll("{$post2cs} .yp-post-comment-head")
                            // ->postRmAll("{$post2cs} .yp-post-comment-body img[src*=\"patreonusercontent.com/\"]") // @@@warn!
                            ->postRmAll("{$post2cs} .yp-post-comment-body img") // @@@warn!
                            ->postUnwrap("{$post2cs} .yp-post-comment-body", $unwrapTags) // @@@warn!
                            ->postMustNotHaveChildren("{$post2cs} .yp-post-comment-body", true)
                            ->postMustBeEmpty("{$post2cs} .yp-post-comment > div", true)
                            ->postMustBeEmpty("{$post2cs} .yp-post-comment", true)
                            ->postMustNotHaveChildren("{$post2cs} .card-title", true)
                            ->postMustBeEmpty("{$post2cs}", true)
            ;
            unset($post2cs);

            // 13756532 page6
            // .card-content > .card-embed
            $embed = $post->first('>*> .card-content > .card-embed > div');
            $p['content_embed'] = $embed ? ( $embed->innerHtml() ) : null;
            if ($embed) {
                $embedText = $post->first('>*> .card-content > .card-embed > .card-title::text');

                $dbgr && $dbgr->postAssert(
                                !$embedText || trim($embedText) === 'Embed data',
                                ">*> .card-content > .card-embed > .card-title::text === {$embedText}"
                );
            }
            unset($embed, $embedText);

            $dbgr && $dbgr
                            ->postMustBeOnly('>*> .card-content > .card-embed')
                            ->postMustBeOnly('>*> .card-content > .card-embed > div')
                            ->postRmAll('>*> .card-content > .card-embed > div')
                            ->postRmAll('>*> .card-content > .card-embed > hr')
                            ->postMustNotHaveChildren(">*> .card-content > .card-embed > .card-title", true)
                            ->postMustBeEmpty('>*> .card-content > .card-embed', true)
            ;
            // @@@TODO //
            // ###################
            $p['reveal_files'] = [];
            // echo htmlspecialchars($post->first('>*> .card-reveal > .card-attachments')->first('>*> p > a')->html());
            // die;
            // >*> .card-reveal > .card-attachment
            foreach ($post->find('>*> .card-reveal > .card-attachments') as $attach) {
                $p2 = [];
                foreach ($attach->find('>*> p > a') as $a) {
                    $p2[] = [
                        'name' => $a->first('text()', Query::TYPE_XPATH),
                        'url' => $a->attr('href'),
                    ];
                }
                // echo htmlspecialchars(json_encode($p2));
                // die;
                $file_sizes = join(' ', $attach->find('>*> p::text'));
                if (!preg_match_all('#\\s*\\(([^)]*+)\\)#s', $file_sizes, $m, PREG_PATTERN_ORDER)) {
                    throw new Exception("Unable to parse attachment size: ");
                }
                $file_sizes = $m[1];
                unset($m);
                $file_sizes_c = count($file_sizes);
                if (count($p2) != $file_sizes_c) {
                    $a = count($p2);
                    $file_sizes_c = count($file_sizes);
                    throw new Exception("Unable to parse attachment sizes - {$a} files, {$file_sizes_c} sizes");
                }
                for ($i = 0; $i < $file_sizes_c; $i++) {
                    $p2[$i]['size_h'] = $file_sizes[$i];
                    $num = self::parseBytes($file_sizes[$i]);
                    $p2[$i]['size'] = $num;
                    if ($num) {
                        $o['posts_storage_required'] += $num;
                        $p['post_storage_required'] += $num;
                    }
                }
                unset($m, $num, $prefix);
                unset($i, $file_sizes_c);
                // 'file_sizes' => $file_sizes, // $attach->find('>*> p::text'),
                $tmp = [
                    'title' => $attach->first('.card-title::text'),
                    'files' => $p2,
                ];
                $p['reveal_files'][] = $tmp;
            }
            $dbgr && $dbgr
                            ->postRmAll('>*> .card-reveal > .card-attachments > hr')
                            ->postRmAll('>*> .card-reveal > .card-attachments > .card-title')
                            ->postMustNotHaveChildren('>*> .card-reveal > .card-attachments > p > a', true)
                            ->postRmAll('>*> .card-reveal > .card-attachments > p br')
                            ->postMustNotHaveChildren('>*> .card-reveal > .card-attachments > p', true)
                            ->postMustBeEmpty('>*> .card-reveal > .card-attachments', true)
            ;
            // .card-reveal END
            // copy-paste start:
            // >*> .card-content > .card-attachment
            // <editor-fold defaultstate="collapsed" desc="copy-paste">
            $p['content_files'] = [];
            foreach ($post->find('>*> .card-content > .card-attachments') as $attach) {
                $p2 = [];
                foreach ($attach->find('>*> p > a') as $a) {
                    $p2[] = [
                        'name' => $a->first('text()', Query::TYPE_XPATH),
                        'url' => $a->attr('href'),
                    ];
                }
                // echo htmlspecialchars(json_encode($p2));
                // die;
                $file_sizes = join(' ', $attach->find('>*> p::text'));
                if (!preg_match_all('#\\s*\\(([^)]*+)\\)#s', $file_sizes, $m, PREG_PATTERN_ORDER)) {
                    throw new Exception("Unable to parse attachment size: ");
                }
                $file_sizes = $m[1];
                unset($m);
                $file_sizes_c = count($file_sizes);
                if (count($p2) != $file_sizes_c) {
                    $a = count($p2);
                    $file_sizes_c = count($file_sizes);
                    throw new Exception("Unable to parse attachment sizes - {$a} files, {$file_sizes_c} sizes");
                }
                for ($i = 0; $i < $file_sizes_c; $i++) {
                    $p2[$i]['size_h'] = $file_sizes[$i];
                    $num = self::parseBytes($file_sizes[$i]);
                    $p2[$i]['size'] = $num;
                    if ($num) {
                        $o['posts_storage_required'] += $num;
                        $p['post_storage_required'] += $num;
                    }
                }
                unset($m, $num, $prefix);
                unset($i, $file_sizes_c);
                // 'file_sizes' => $file_sizes, // $attach->find('>*> p::text'),
                $tmp = [
                    'title' => $attach->first('.card-title::text'),
                    'files' => $p2,
                ];
                $p['content_files'][] = $tmp;
            }
            $dbgr && $dbgr
                            ->postRmAll('>*> .card-content > .card-attachments > hr')
                            ->postRmAll('>*> .card-content > .card-attachments > .card-title')
                            ->postMustNotHaveChildren('>*> .card-content > .card-attachments > p > a', true)
                            ->postRmAll('>*> .card-content > .card-attachments > p br')
                            ->postMustNotHaveChildren('>*> .card-content > .card-attachments > p', true)
                            ->postMustBeEmpty('>*> .card-content > .card-attachments', true)
            ;
            // </editor-fold>
            // .card-content END
            // copy-paste end

            $dbgr && $dbgr->postMustBeEmpty('>*> .card-reveal', true);
            $dbgr && $dbgr
                            ->postRmAll(">*> .card-content br")
                            ->postMustBeEmpty(">*> .card-content", true);
            // ###################
            // post end
            $o['posts'][] = $p;
            $dbgr && $dbgr->debugPostEnd();
        }

        // yp-posts-paginate-buttons
        // SHARED FILES
        $o['sharedfiles'] = [];
        $o['sharedfiles_storage_required'] = 0;
        foreach ($idSf->find('>*> .row .yp-shared-card') as $sf) {
            $f = [];
            /** @var $sf \DiDom\Element */
            // 
            // .card-content > .card-title > .post-time-unix

            $f['name'] = \rtrim($sf->first(">*> .card-content > .card-title::text"), ' ');
            $f['time'] = $sf->first(">*> .card-content > .card-title > .post-time-unix::text");
            $f['text'] = $sf->first(">*> .card-content > .yp-shared-text::text");
            $f['size_h'] = $sf->first(">*> .card-content > p:last-child > strong::text");
            $size = self::parseBytes($f['size_h']);
            $f['size'] = $size;
            if ($size) {
                $o['sharedfiles_storage_required'] += $size;
            }
            $f['url'] = $sf->first(">*> .card-action > a::attr(href)");
            $o['sharedfiles'][] = $f;
        }

        return $o;
    }

}
