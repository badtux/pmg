<?php
/**
 * Created by PhpStorm.
 * User: virajabayarathna
 * Date: 5/31/18
 * Time: 12:47 PM
 */

namespace Core;

class HTMLToPDF
{
    private $convertorBinary = 'wkhtmltopdf';
    private $cmd = null;
    private $execReturnOut = false;
    private $execOut = [];
    private $contentHandle = null;
    private $contentPath = null;

    public function __construct($htmlContet)
    {
        // writes the content to a temp file and gets the handle
        $this->writeToFile(false, $htmlContet);

        // prepares the command
        if(defined('app_bin_path') && file_exists(app_bin_path.$this->convertorBinary)) {
            $this->cmd = app_bin_path . $this->convertorBinary;
        }

        // disable smart shrinking
        $this->cmd = $this->cmd .' --disable-smart-shrinking --dpi 96';
    }

    public function getPDF($fileName){
        $filename = app_upload_path.ltrim($fileName, '/');
        $this->prepareTargetFilePath($filename);

        $out = exec($this->cmd.' '.$this->contentPath.' '.$filename.' 2>&1', $this->execOut, $this->execReturnOut);

        if($this->execReturnOut != 0){
            throw new \Exception($this->execOut[0], $this->execReturnOut);
        }

        return $filename;
    }

    private function writeToFile($fileNamePathOrFalseForTemp, $content, $fileType=false){
        if(!$fileNamePathOrFalseForTemp && is_bool($fileNamePathOrFalseForTemp)){
            $tempFilehandle = fopen(sys_get_temp_dir().'/'.time().'-'.rand(100,999).'.html', 'w');

            if(is_resource($tempFilehandle)) {
                fwrite($tempFilehandle, $content);
                //fclose($tempFilehandle); // this removes the file

                $this->contentPath = stream_get_meta_data($tempFilehandle)['uri'];
                $this->contentHandle = $tempFilehandle;

                return true;
            }
        }

        return false;
    }

    private function prepareTargetFilePath($filename){
        $dirname = dirname($filename);
        if (!is_dir($dirname)){
            mkdir($dirname, 0755, true);
        }

        return true;
    }
}

?>