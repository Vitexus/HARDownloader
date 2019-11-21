<?php
require_once './vendor/autoload.php';

ini_set('max_execution_time', 3500); //3000 seconds = 50 minutes
set_time_limit(3500);

$opage = new Ease\WebPage();


$himalayanartUrl = Ease\WebPage::getRequestValue('himalayanartUrl');
if ($himalayanartUrl) {
    $mainpage = file_get_contents($himalayanartUrl);

    if (preg_match_all('/https?\:\/\/[^\" ]+metadata\.json/i', $mainpage, $urls)) {
        $metaUrl  = $urls[0][0];
        $baseUrl  = str_replace(basename($metaUrl), '', $metaUrl);
        $metaData = json_decode(file_get_contents($metaUrl), true);
        $height   = $metaData['height'];
        $width    = $metaData['width'];
        $Xes      = $width / 256;
        $Yes      = $height / 256;
        $z        = $metaData['maxZoom'] - 1;
        $bigImage = imagecreatetruecolor($width, $height);

        $tileCount = $Xes * $Yes;


        for ($x = 0; $x <= $Xes; $x++) {
            $xpos = $x * 256;
            for ($y = 0; $y <= $Yes; $y++) {
                $ypos    = $y * 256;
                $tileUrl = $baseUrl.$z.'/'.$y.'_'.$x.'.jpg';
                $tileTmp = 'img/'.$x.'_'.$y.'.jpg';
                file_put_contents($tileTmp, file_get_contents($tileUrl));
                $tile    = imagecreatefromjpeg($tileUrl);
                imagecopy($bigImage, $tile, $ypos, $xpos, 0, 0, 256, 256);
                imagepng($bigImage, 'img/big.png');
                unlink($tileTmp);
            }
        }
        $opage->addItem(new Ease\Html\ImgTag('img/big.png'));
    } else {
        $opage->addItem('sorry :(');
    }
} else {
    $selForm = new Ease\Html\Form('Mandala');
    $selForm->addItem(new Ease\Html\InputUrlTag('himalayanartUrl', '',
            ['size' => '100']));
    $selForm->addItem(new \Ease\Html\SubmitButton('CUC!'));
    $opage->addItem($selForm);
}

echo $opage;
