<?php

namespace HAR;

/**
 * Description of Har
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
class Har extends \Ease\Brick
{
    /**
     * Curl Handle.
     *
     * @var resource
     */
    public $curl = null;

    /**
     * Informace o posledním HTTP requestu.
     *
     * @var *
     */
    public $curlInfo;

    /**
     * Informace o poslední HTTP chybě.
     *
     * @var string
     */
    public $lastCurlError = null;

    /**
     * Raw Content of last curl response
     *
     * @var string
     */
    public $lastCurlResponse;

    /**
     * HTTP Response code of last request
     *
     * @var int
     */
    public $lastResponseCode = null;

    /**
     * response format
     *
     * @var string image
     */
    public $responseMimeType = null;

    /**
     *
     * @var string
     */
    public $tmpDir = '';

    /**
     *
     * @var string
     */
    public $doneDir = '';

    /**
     *
     * @var string
     */
    private $tileDone;

    /**
     *
     * @var int
     */
    private $Xes;

    /**
     *
     * @var int
     */
    private $Yes;

    /**
     *
     * @var type
     */
    private $z;
    private $bigImage;

    /**
     *
     * @var int
     */
    private $tileCount;

    /**
     *
     */
    public function __construct($doneDir)
    {
        $this->tmpDir = \Ease\Shared::cfg('HAR_TMP_DIR', sys_get_temp_dir() . '/har');
        $this->doneDir = $doneDir;
        $this->logger = new \Ease\Logger\Regent();
        $this->curlInit();
        if (file_exists($this->tmpDir) === false) {
            mkdir($this->tmpDir, 0777, true);
        }
        if (file_exists($this->doneDir) === false) {
            mkdir($this->doneDir, 0777, true);
        }
    }

    /**
     * Inicializace CURL
     */
    public function curlInit()
    {
        $this->curl = \curl_init(); // create curl resource
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true); // return content as a string from curl_exec
        curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, true); // follow redirects (compatibility for future changes in IPEX)
        curl_setopt($this->curl, CURLOPT_HTTPAUTH, true);       // HTTP authentication
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($this->curl, CURLOPT_VERBOSE, ($this->debug === true)); // For debugging
    }

    public function addStatusMessage($message, $type = 'info', $addIcons = true)
    {
        echo date('Y-m-d H:i:s') . ' ' . $message . "\n";
    }

    public function loadImagePage($itemId)
    {
        return $this->doCurlRequest("https://www.himalayanart.org/items/$itemId/images/primary") == 200;
    }

    public function obtain($itemId)
    {
        if ($this->loadImagePage($itemId)) {
            if ($this->debug) {
                $this->addStatusMessage('downloading ' . "https://www.himalayanart.org/items/$itemId/images/primary");
            }
            if (
                    preg_match_all(
                        '/https?\:\/\/[^\" ]+metadata\.json/i',
                        $this->lastCurlResponse,
                        $urls
                    )
            ) {
                file_put_contents(str_replace(
                    '.png',
                    '.html',
                    $this->targetImage($itemId)
                ), $this->lastCurlResponse);
                if (file_exists($this->targetImage($itemId))) {
                    $this->addStatusMessage(
                        $this->targetImage($itemId) . ' already exists',
                        'info'
                    );
                } else {
                    $this->tileDone = 0;
                    $metaUrl = $urls[0][0];
                    $baseUrl = str_replace(
                        basename($metaUrl),
                        '',
                        $metaUrl
                    );
                    $metaData = json_decode(
                        file_get_contents($metaUrl),
                        true
                    );
                    $height = $metaData['height'];
                    $width = $metaData['width'];
                    $this->Xes = round($width / 256);
                    $this->Yes = round($height / 256);
                    $this->z = $metaData['maxZoom'] - 1;
                    $this->bigImage = imagecreatetruecolor($width, $height);

                    $this->tileCount = $this->Xes * $this->Yes;
                    $tileDone = 0;
                    $rowDone = 0;

                    for ($x = 0; $x <= $this->Xes; $x++) {
                        for ($y = 0; $y <= $this->Yes; $y++) {
                            $this->loadTile($itemId, $x, $y, $baseUrl);
                        }
                    }
                    imagepng($this->bigImage, $this->doneDir . '/' . $itemId . '.png');
                    $this->addStatusMessage(
                        'ItemID: ' . $itemId . ' saved as  ' . 'img/' . $itemId . '.png',
                        'success'
                    );
                    return true;
                }
            }
        } else {
            $this->addStatusMessage('Json not found for ' . $itemId, 'warning');
            return false;
        }
    }

    public function loadTile($itemId, $x, $y, $baseUrl)
    {
        $xpos = $x * 256;
        $ypos = $y * 256;
        $tileUrl = $baseUrl . $this->z . '/' . $x . '_' . $y . '.jpg';
        $tileTmp = $this->tmpDir . '/' . $x . '_' . $y . '.jpg';

        $retry = 0;
        while ($this->doCurlRequest($tileUrl) != 200) {
            if ($this->lastResponseCode == 404) {
                $this->addStatusMessage($tileUrl . ' not found', 'warning');
                break;
            }
            if ($this->lastResponseCode == 403) {
                if ($this->debug) {
                    $this->addStatusMessage($tileUrl . ' forbidden', 'debug');
                }
                break;
            }
            if ($this->debug) {
                $this->addStatusMessage('Retry ' . $retry++ . ' reason ' . $this->lastResponseCode);
            }
        };

        if (strlen($this->lastResponseCode == 200)) {
            file_put_contents($tileTmp, $this->lastCurlResponse);
            $tile = imagecreatefromjpeg($tileTmp);
            imagecopy($this->bigImage, $tile, $xpos, $ypos, 0, 0, 256, 256);
            unlink($tileTmp);
            $this->tileDone++;
            if ($this->debug) {
                $this->addStatusMessage('ItemID: ' . $itemId . ' Row ' . $y . ' of ' . $this->Yes . ' tile ' . $x . ' of ' . $this->Xes . ' (' . $this->tileDone . ' of ' . $this->tileCount . ') ' . basename($tileUrl));
                imagepng($this->bigImage, $this->targetImge($itemId));
            }
            return true;
        }
    }

    /**
     * Vykonej HTTP požadavek
     *
     * @param string $url    URL požadavku
     *
     * @return int HTTP Response CODE
     */
    public function doCurlRequest($url)
    {
        curl_setopt($this->curl, CURLOPT_URL, $url);
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'GET');

        $this->lastCurlResponse = curl_exec($this->curl);
        $this->curlInfo = curl_getinfo($this->curl);
        $this->curlInfo['when'] = microtime();
        $this->responseMimeType = $this->curlInfo['content_type'];
        $this->lastResponseCode = $this->curlInfo['http_code'];
        $this->lastCurlError = curl_error($this->curl);
        if (strlen($this->lastCurlError)) {
            $this->addStatusMessage(sprintf(
                'Curl Error (HTTP %d): %s',
                $this->lastResponseCode,
                $this->lastCurlError
            ), 'error');
        }
        return $this->lastResponseCode;
    }

    public function targetImage($itemId)
    {
        return $this->doneDir . '/' . $itemId . '.png';
    }
}
