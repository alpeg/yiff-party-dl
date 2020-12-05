<?php

namespace App;

/**
 * Description of ExportUrlsCsv
 *
 * @author https://github.com/alpeg
 * @license MIT
 */
class ExportUrlsCsv {

    public static function exportUrls(Storager $storager) {
        $d = fopen("php://output", "w");
        $exclusions = $storager->read('exclusions')['exclusions'];
        $exclusionsCount = count($exclusions);
        $exclusionsI = 0;
        foreach ($exclusions as $id) {
            $exclusionsI++;
            $i = 1;
            $key = "{$id}/{$i}";
            if (!$storager->exists($key)) {
                fwrite(STDERR, "ERROR: Creator {$id}: No data\n");
                exit(1); // continue;
            }
            $page1 = $storager->read($key);
            $pages = $page1['meta']['pages'];
            self::exportUrlsSingleSharedFiles($d, $id, $i, $page1);
            $ss = round($page1['sharedfiles_storage_required'] / (1024 * 1024));
            $sp = 0;
            unset($page1);
            for ($i = 1; $i <= $pages; $i++) {
                $key = "{$id}/{$i}";
                if (!$storager->exists($key)) {
                    fwrite(STDERR, "ERROR: Creator {$id}: No more data\n");
                    exit(1);
                    continue;
                }
                $pageN = $storager->read($key);
                self::exportUrlsSinglePosts($d, $id, $i, $pageN);
                $sp += $pageN['posts_storage_required'];
                // $ss = round($POST['sharedfiles_storage_required'] / (1024 * 1024));
                // echo "Creator {$id} ({$exclusionsI}/{$exclusionsCount}) (page {$i} of {$pages}) ({$sp} posts, {$ss} shareds)\n";
            }
            $sp = round($sp) / (1024 * 1024);
            // echo "Creator {$id} ({$exclusionsI}/{$exclusionsCount}): {$pages} pages | {$ss} MiB Shared files | {$sp} MiB Shared files\n";
        }
        fclose($d);
    }

    private static function exportUrlsSingleFlush($d, $x) {
        fputcsv($d, [
            $x['priority'],
            $x['type'],
            $x['creator'],
            $x['page'],
            $x['post'],
            is_int($x['size']) ? $x['size'] : -7,
            $x['url'],
            $x['i'],
        ]);
    }

    private static function exportUrlsSinglePosts($d, $creator, $page, $p) {
        $i = 0;
        if ($page == 1) {
            if ($p['meta']['url_splash']) {
                $x = [];
                $x['priority'] = 1000;
                $x['type'] = '1s';
                $x['creator'] = $creator;
                $x['page'] = -1;
                $x['post'] = -1;
                $x['size'] = -1;
                $x['url'] = $p['meta']['url_splash'];
                $x['i'] = $i++;
                self::exportUrlsSingleFlush($d, $x);
            }
            if ($p['meta']['url_avatar']) {
                $x = [];
                $x['priority'] = 1000;
                $x['type'] = '1a';
                $x['creator'] = $creator;
                $x['page'] = -1;
                $x['post'] = -1;
                $x['size'] = -1;
                $x['url'] = $p['meta']['url_avatar'];
                $x['i'] = $i++;
                self::exportUrlsSingleFlush($d, $x);
            }
        }
        foreach ($p['posts'] as $row) {
            if ($row2 = $row['thumb_url']) {
                $x = [];
                $x['priority'] = 900;
                $x['type'] = '2t';
                $x['creator'] = $creator;
                $x['page'] = $page;
                $x['post'] = $row['id'];
                $x['size'] = -1;
                $x['url'] = $row2;
                $x['i'] = $i++;
                self::exportUrlsSingleFlush($d, $x);
            }
            foreach (($row['action_files'] ?: []) as $row2) {
                $x = [];
                $x['priority'] = 900;
                $x['type'] = 'af';
                $x['creator'] = $creator;
                $x['page'] = $page;
                $x['post'] = $row['id'];
                $x['size'] = -1;
                $x['url'] = $row2['url'];
                $x['i'] = $i++;
                self::exportUrlsSingleFlush($d, $x);
            }
            foreach (($row['reveal_body_inline_files'] ?: []) as $row2) {
                $x = [];
                $x['priority'] = 900;
                $x['type'] = 'ri';
                $x['creator'] = $creator;
                $x['page'] = $page;
                $x['post'] = $row['id'];
                $x['size'] = -1;
                $x['url'] = $row2['url'];
                $x['i'] = $i++;
                self::exportUrlsSingleFlush($d, $x);
                $x = [];
                $x['priority'] = 400;
                $x['type'] = 'rit';
                $x['creator'] = $creator;
                $x['page'] = $page;
                $x['post'] = $row['id'];
                $x['size'] = -1;
                $x['url'] = $row2['url_thumb'];
                $x['i'] = $i++;
                self::exportUrlsSingleFlush($d, $x);
            }
            foreach (($row['reveal_comments_avatars'] ?: []) as $row2) {
                $x = [];
                $x['priority'] = 100;
                $x['type'] = 'rc';
                $x['creator'] = $creator;
                $x['page'] = $page;
                $x['post'] = $row['id'];
                $x['size'] = -1;
                $x['url'] = $row2;
                $x['i'] = $i++;
                self::exportUrlsSingleFlush($d, $x);
            }
            foreach (($row['content_body_inline_files'] ?: []) as $row2) {
                $x = [];
                $x['priority'] = 900;
                $x['type'] = 'ci';
                $x['creator'] = $creator;
                $x['page'] = $page;
                $x['post'] = $row['id'];
                $x['size'] = -1;
                $x['url'] = $row2['url'];
                $x['i'] = $i++;
                self::exportUrlsSingleFlush($d, $x);
            }
            foreach (($row['content_comments_avatars'] ?: []) as $row2) {
                $x = [];
                $x['priority'] = 100;
                $x['type'] = 'cc';
                $x['creator'] = $creator;
                $x['page'] = $page;
                $x['post'] = $row['id'];
                $x['size'] = -1;
                $x['url'] = $row2;
                $x['i'] = $i++;
                self::exportUrlsSingleFlush($d, $x);
            }
            foreach (($row['reveal_files'] ?: []) as $row22) {
                foreach (($row22['files'] ?: []) as $row2) {
                    $x = [];
                    $x['priority'] = 1000;
                    $x['type'] = 'rf';
                    $x['creator'] = $creator;
                    $x['page'] = $page;
                    $x['post'] = $row['id'];
                    $x['size'] = $row2['size'];
                    $x['url'] = $row2['url'];
                    $x['i'] = $i++;
                    self::exportUrlsSingleFlush($d, $x);
                }
            }
            foreach (($row['content_files'] ?: []) as $row22) {
                foreach (($row22['files'] ?: []) as $row2) {
                    $x = [];
                    $x['priority'] = 1000;
                    $x['type'] = 'cf';
                    $x['creator'] = $creator;
                    $x['page'] = $page;
                    $x['post'] = $row['id'];
                    $x['size'] = $row2['size'] ?? -2;
                    $x['url'] = $row2['url'];
                    $x['i'] = $i++;
                    self::exportUrlsSingleFlush($d, $x);
                }
            }
        }
    }

    private static function exportUrlsSingleSharedFiles($d, $creator, $page, $p) {
        $i = 0;
        foreach ($p['sharedfiles'] as $row) {
            $x = [];
            $x['priority'] = 1000;
            $x['type'] = '0s';
            $x['creator'] = $creator;
            $x['page'] = 0;
            $x['post'] = 0;
            $x['size'] = $row['size'];
            $x['url'] = $row['url'];
            $x['i'] = $i++;
            self::exportUrlsSingleFlush($d, $x);
        }
    }

}
