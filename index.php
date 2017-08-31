<?php
/**
 * Created by PhpStorm.
 * User: t.tymchenko
 * Date: 17.07.2017
 * Time: 16:41
 */

$first = true;
$oldRoot = '/history/as-1/patriot-images/media';
$newRoot = '/history/as-1/patriot-images/media1';

$directory = new RecursiveDirectoryIterator($oldRoot);
$iterator = new RecursiveIteratorIterator($directory);

foreach ($iterator as $info) {
    $time = microtime(1);
    $oldPath = $info -> getPathname();

    if (in_array($info -> getFilename(), array('.', '..'))){
        continue;
    }

    if(!$newPath = str_replace($oldRoot, $newRoot, $oldPath)){
        continue;
    }

    if(is_file($newPath)){
        continue;
    }

    if(!file_exists(dirname($newPath))){
        mkdir(dirname($newPath), 0777, true);
    }

    $test = 'mogrify -path '. $newPath .' -filter Triangle -define filter:support=2 -unsharp 0.25x0.08+8.3+0.045 -dither None -posterize 136 -quality 82 -define jpeg:fancy-upsampling=off -define png:compression-filter=5 -define png:compression-level=9 -define png:compression-strategy=1 -define png:exclude-chunk=all -interlace none -colorspace sRGB '.$oldPath;

    $isLossy  = in_array(pathinfo($oldPath, PATHINFO_EXTENSION), array('jpg', 'jpeg'));
    $isLossless = in_array(pathinfo($oldPath, PATHINFO_EXTENSION), array(/*'gif',*/ 'png'));
    $applicable = $isLossless || $isLossy;

    if($isLossy){
        exec('convert '. $oldPath .' -quality 85 -interlace JPEG -sampling-factor 2X2 -strip '. $newPath);
    } elseif($isLossless){
        exec('convert '. $oldPath .' -strip '. $newPath);
    } else {
        $copied = copy($oldPath, $newPath);
    }

    if(1){
        if($applicable){
            $oldSize = round(filesize($oldPath) / 1024, 2);
            $newSize = round(filesize($newPath) / 1024, 2);
            $percentage = round(($oldSize - $newSize) / $oldSize * 100, 2);
        } else {
            $oldSize = !empty($copied) ? 'copied successfully' : 'not transferred';
            $newSize = $percentage = '';
        }

        if($first){
            $first = false;
            file_put_contents($newRoot.'/log.csv', implode(',', array(  'path',     'old size(kb)', 'new size(kb)', 'decrease(%)',  'time spent(sec)')).PHP_EOL, FILE_APPEND);
        }
        file_put_contents($newRoot.'/log.csv', implode(',', array(  $oldPath,   $oldSize,       $newSize,       $percentage,    round((microtime(1) - $time), 4))).PHP_EOL, FILE_APPEND);
    }
}