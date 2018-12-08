<?php

namespace GameAPIs\Controllers\APIs\Minecraft\Images\Skin;

use Redis;
use Phalcon\Filter;

class IndexController extends ControllerBase {

    public function skinAction() {
        $filter = new Filter();
        $params = $this->dispatcher->getParams();

        $cConfig = array();
        $cConfig['name']  = $filter->sanitize($params['name'], 'string');
        $cConfig['size']  = $params['size'] ?? 85;
        $cConfig['helm']  = $params['helm'] ?? 'true';

        if ($cConfig['helm'] !== 'false') {
            $cConfig['helm'] = 'true';
        } else {
            $cConfig['helm'] = 'false';
        }

        if (!is_numeric($cConfig['size'])) {
            $cConfig['size'] = 85;
        } elseif($cConfig['size'] > 250) {
            $cConfig['size'] = 250;
        }

        $cConfig['redis']['host'] = $this->config->application->redis->host;
        $cConfig['redis']['key'] = $this->config->application->redis->keyStructure->mcpc->skin->render.$cConfig['name'].':'.$cConfig['size'].':'.$cConfig['helm'];

        $redis = new Redis();
        $redis->pconnect($cConfig['redis']['host']);
        if($redis->exists($cConfig['redis']['key'])) {
            $skin = base64_decode($redis->get($cConfig['redis']['key']));
            echo $skin;
        } else {
            define('MC_SKINS_BASE_URL', 'http://skins.minecraft.net/MinecraftSkins/');
            $skin = null;
            $seconds_to_cache = 120;
            if ($seconds_to_cache > 0) {
                $ts = gmdate("D, d M Y H:i:s", time() + $seconds_to_cache + 3600) . ' GMT';
                header('Expires: ' . $ts);
                header('Pragma: cache');
                header('Cache-Control: max-age=' . $seconds_to_cache);
            }

            $skin_path = MC_SKINS_BASE_URL . $cConfig['name'] . '.png';
            function flipSkin($preview, $skin, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h) {

                $tmp = imagecreatetruecolor(4, 12);
                imagesavealpha($tmp, true);
                $transparent = imagecolorallocatealpha($tmp, 255, 255, 255, 127);
                imagefill($tmp, 0, 0, $transparent);

                imagecopy($tmp, $skin, 0, 0, $src_x, $src_y, $src_w, $src_h);
                flipHorizontal($tmp);
                imagecopy($preview, $tmp, $dst_x, $dst_y, 0, 0, $src_w, $src_h);
                imagedestroy($tmp);
            }

            function flipHorizontal(&$img) {
                $size_x = imagesx($img);
                $size_y = imagesy($img);
                $tmp = imagecreatetruecolor($size_x, $size_y);

                imagesavealpha($tmp, true);
                $transparent = imagecolorallocatealpha($tmp, 255, 255, 255, 127);
                imagefill($tmp, 0, 0, $transparent);
                $x = imagecopyresampled($tmp, $img, 0, 0, ($size_x - 1) , 0, $size_x, $size_y, 0 - $size_x, $size_y);
                if ($x) {
                    $img = $tmp;
                }
                else {
                    die("Unable to flip image");
                }
            }

            function checkForPlainSquare($img, $x, $y) {

                $firstPixColor = imagecolorat($img, 0, 0);

                for ($i = $x; $i < $x + 8; $i++) {
                    for ($j = $y; $j < $y + 8; $j++) {

                        if (imagecolorat($img, $i, $j) != $firstPixColor) {
                            return false;
                        }
                    }
                }

                return true;
            }
            function file_get_contents_curl($url) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
                curl_setopt($ch, CURLOPT_HEADER, 0);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT,1);
                curl_setopt($ch, CURLOPT_TIMEOUT,1);
                $data = curl_exec($ch);
                curl_close($ch);
                return $data;
            }

            $skin = @imagecreatefromstring(file_get_contents_curl($skin_path));
            if (!$skin) {

                $output = 'iVBORw0KGgoAAAANSUhEUgAAAEAAAABACAYAAACqaXHeAAAFDUlEQVR42u2a20sUURzH97G0LKMotPuWbVpslj1olJ';
                $output.= 'XdjCgyisowsSjzgrB0gSKyC5UF1ZNQWEEQSBQ9dHsIe+zJ/+nXfM/sb/rN4ZwZ96LOrnPgyxzP/M7Z+X7OZc96JpE';
                $output.= 'ISfWrFhK0YcU8knlozeJKunE4HahEqSc2nF6zSEkCgGCyb+82enyqybtCZQWAzdfVVFgBJJNJn1BWFgC49/VpwGVl';
                $output.= 'D0CaxQiA5HSYEwBM5sMAdKTqygcAG9+8coHKY/XXAZhUNgDYuBSPjJL/GkzVVhAEU5tqK5XZ7cnFtHWtq/TahdSw2';
                $output.= 'l0HUisr1UKIWJQBAMehDuqiDdzndsP2EZECAG1ZXaWMwOCODdXqysLf++uXUGv9MhUHIByDOijjdiSAoH3ErANQD7';
                $output.= '3C7TXXuGOsFj1d4YH4OTJAEy8y9Hd0mCaeZ5z8dfp88zw1bVyiYhCLOg1ZeAqC0ybaDttHRGME1DhDeVWV26u17lR';
                $output.= 'APr2+mj7dvULfHw2q65fhQRrLXKDfIxkau3ZMCTGIRR3URR5toU38HbaPiMwUcKfBAkoun09PzrbQ2KWD1JJaqswj';
                $output.= 'deweoR93rirzyCMBCmIQizqoizZkm2H7iOgAcHrMHbbV9KijkUYv7qOn55sdc4fo250e+vUg4329/Xk6QB/6DtOws';
                $output.= '+dHDGJRB3XRBve+XARt+4hIrAF4UAzbnrY0ve07QW8uHfB+0LzqanMM7qVb+3f69LJrD90/1axiEIs6qIs21BTITo';
                $output.= 'ewfcSsA+Bfb2x67OoR1aPPzu2i60fSNHRwCw221Suz0O3jO+jh6V1KyCMGse9721XdN5ePutdsewxS30cwuMjtC86';
                $output.= '0T5JUKpXyKbSByUn7psi5l+juDlZYGh9324GcPKbkycaN3jUSAGxb46IAYPNZzW0AzgiQ5tVnzLUpUDCAbakMQXXr';
                $output.= 'OtX1UMtHn+Q9/X5L4wgl7t37r85OSrx+TYl379SCia9KXjxRpiTjIZTBFOvrV1f8ty2eY/T7XJ81FQAwmA8ASH1ob';
                $output.= '68r5PnBsxA88/xAMh6SpqW4HRnLBrkOA9Xv5wPAZjAUgOkB+SHxgBgR0qSMh0zmZRsmwDJm1gFg2PMDIC8/nAHIMl';
                $output.= 's8x8GgzOsG5WiaqREgYzDvpTwjLDy8NM15LpexDEA3LepjU8Z64my+8PtDCmUyRr+fFwA2J0eAFYA0AxgSgMmYBMZ';
                $output.= 'TwFQnO9RNAEaHOj2DXF5UADmvAToA2ftyxZYA5BqgmZZApDkdAK4mAKo8GzPlr8G8AehzMAyA/i1girUA0HtYB2Ca';
                $output.= 'IkUBEHQ/cBHSvwF0AKZFS5M0ZwMQtEaEAmhtbSUoDADH9ff3++QZ4o0I957e+zYAMt6wHkhzpjkuAcgpwNcpA7AZD';
                $output.= 'LsvpwiuOkBvxygA6Bsvb0HlaeKIF2EbADZpGiGzBsA0gnwQHGOhW2snRpbpPexbAB2Z1oicAMQpTnGKU5ziFKc4xS';
                $output.= 'lOcYpTnOIUpzgVmgo+XC324WfJAdDO/+ceADkCpuMFiFKbApEHkOv7BfzfXt+5gpT8V7rpfYJcDz+jAsB233r6yyB';
                $output.= 'sJ0mlBCDofuBJkel4vOwBFPv8fyYAFPJ+wbSf/88UANNRVy4Awo6+Ig2gkCmgA5DHWjoA+X7AlM//owLANkX0w035';
                $output.= '9od++pvX8fdMAcj3/QJ9iJsAFPQCxHSnQt8vMJ3v2wCYpkhkAOR7vG7q4aCXoMoSgG8hFAuc/grMdAD4B/kHl9da7';
                $output.= 'Ne9AAAAAElFTkSuQmCC';
                $output = base64_decode($output);

                $skin = imagecreatefromstring($output);
            }

            $preview = imagecreatetruecolor(16, 32);

            $transparent = imagecolorallocatealpha($preview, 255, 255, 255, 127);
            imagefill($preview, 0, 0, $transparent);
            imagecopy($preview, $skin, 4, 0, 8, 8, 8, 8);
            imagecopy($preview, $skin, 4, 8, 20, 20, 8, 12);
            imagecopy($preview, $skin, 0, 8, 44, 20, 4, 12);
            flipSkin($preview, $skin, 12, 8, 44, 20, 4, 12);
            imagecopy($preview, $skin, 4, 20, 4, 20, 4, 12);
            flipSkin($preview, $skin, 8, 20, 4, 20, 4, 12);
            if ($cConfig['helm'] != 'false') {
                if (!checkForPlainSquare($skin, 40, 8)) {
                    imagecopy($preview, $skin, 4, 0, 40, 8, 8, 8);
                }
            }

            imagedestroy($skin);
            $size1 = $cConfig['size'] * 2;
            $fullsize = imagecreatetruecolor($cConfig['size'], $size1);
            imagesavealpha($fullsize, true);
            $transparent = imagecolorallocatealpha($fullsize, 255, 255, 255, 127);
            imagefill($fullsize, 0, 0, $transparent);
            imagecopyresized($fullsize, $preview, 0, 0, 0, 0, imagesx($fullsize) , imagesy($fullsize) , imagesx($preview) , imagesy($preview));

            ob_start();
            imagepng($fullsize);
            $imagedata = ob_get_contents();
            ob_end_clean();
            $redis->set($cConfig['redis']['key'], base64_encode($imagedata), 120);

            header("Cache-Control: s-maxage=120, max-age=120");
            echo $imagedata;
        }
    }

    public function rawskinAction() {
        $filter = new Filter();
        $params = $this->dispatcher->getParams();

        $cConfig = array();
        $cConfig['name']  = $filter->sanitize($params['name'], 'string');

        $cConfig['redis']['host'] = $this->config->application->redis->host;
        $cConfig['redis']['key'] = $this->config->application->redis->keyStructure->mcpc->skin->rawfile.$cConfig['name'];

        $redis = new Redis();
        $redis->pconnect($cConfig['redis']['host']);
        if($redis->exists($cConfig['redis']['key'])) {
            $skin = base64_decode($redis->get($cConfig['redis']['key']));
            echo $skin;
        } else {
            function file_get_contents_curl($url) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
                curl_setopt($ch, CURLOPT_HEADER, 0);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT,1);
                curl_setopt($ch, CURLOPT_TIMEOUT,1);
                $data = curl_exec($ch);
                curl_close($ch);
                return $data;
            }
            $skin = @file_get_contents_curl('http://skins.minecraft.net/MinecraftSkins/'.$cConfig['name'].'.png');
            if(!$skin) {
                $skin = file_get_contents_curl('http://assets.mojang.com/SkinTemplates/steve.png');
            }
            $redis->set($cConfig['redis']['key'], base64_encode($skin), 120);
            header("Cache-Control: s-maxage=120, max-age=120");
            echo $skin;
        }
    }
}
