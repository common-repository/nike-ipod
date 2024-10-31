<?php
/*
 * Sparkline PHP Graphing Library
 * Copyright 2004 James Byers <jbyers@users.sf.net>
 * http://sparkline.org
 *
 * Sparkline is distributed under a BSD License.  See LICENSE for details.
 *
 * $Id: Sparkline_Line.php,v 1.7 2005/01/06 02:40:46 jbyers Exp $
 *
 */

//require_once('Sparkline.php');

define('TEXT_TOP',    1);
define('TEXT_RIGHT',  2);
define('TEXT_BOTTOM', 3);
define('TEXT_LEFT',   4);

define('FONT_1', 1);
define('FONT_2', 2);
define('FONT_3', 3);
define('FONT_4', 4);
define('FONT_5', 5);
define('DEBUG_NONE',     0); // nothing
define('DEBUG_ERROR',    1); // major errors
define('DEBUG_WARNING',  2); // warnings
define('DEBUG_STATS',    4); // dataset, rendering statistics
define('DEBUG_CALLS',    8); // major function calls
define('DEBUG_SET',     16); // all Set methods
define('DEBUG_DRAW',    32); // all Draw methods
define('DEBUG_ALL',   2047); // everything

if(!function_exists('error_handler')){
function error_handler($errno, $errstr, $errfile, $errline) {
  switch ($errno) {
  case E_ERROR:
    $message = "ERROR:    ";
    break;
  case E_WARNING:
    $message = "WARNING:  ";
    break;
  case E_PARSE:
    $message = "PARSE:    ";
    break;
  case E_NOTICE:
    $message = "NOTICE:   ";		
    break;
  case E_USER_ERROR:
    $message = "UERROR:   ";
    break;
  case E_USER_WARNING:
    $message = "UWARNING: ";
    break;
  case E_USER_NOTICE:
    $message = "UNOTICE:  ";		
    break;
  default:
    $message = "UNKNOWN:  ";
    break;
  } // switch
  
  $message .= "$errstr in $errfile at line $errline\n";
  
  if (($errno != E_NOTICE) &&     // suppress notices
      (error_reporting() != 0)) { // respect supressed errors (@)
    log_write($message, 'PHP');
  }
} // function error_handler
}

if(!function_exists('log_write')){
function log_write($string, $type = '', $date = false) {
  global $LOGFILE;

  if (isset($LOGFILE)) {
    if ($date == false) {
      $date = time();
    }
    
    $message = date('d/m/Y:H:i:s', $date) . " $type: $string \n";
    error_log($message, 3, $LOGFILE);
  }
} // function log_write
}

class Object {

  var $isError;
  var $logFile;
  var $errorList;
  var $debugList;
  var $debugLevel;
  var $startTime;

  ////////////////////////////////////////////////////////////////////////////
  // constructor
  //
  function Object($catch_errors = true) {
    $this->isError         = false;
    $this->logFile         = null;
    $this->logDate         = '';
    $this->errorList       = array();
    $this->debugList       = array();
    $this->debugLevel      = DEBUG_NONE;
    $this->startTime       = $this->microTimer();

    //    if ($catch_errors) {
      set_error_handler('error_handler');
      //}
  } // function Object

  ////////////////////////////////////////////////////////////////////////////
  // utility
  //
  function microTimer() {
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec); 
  } // function microTimer

  ////////////////////////////////////////////////////////////////////////////
  // error handling
  //
  function SetDebugLevel($level, $file = null) {
    global $LOGFILE;

    if ($level >= DEBUG_NONE &&
        $level <= DEBUG_ALL) {
      $this->debugLevel = $level;
    }

    if ($file != null) {
      if ((!file_exists($file) && !touch($file)) ||
          !is_writable($file)) {
        die("error log file '$file' is not writable to the web server user");
      } else {
        $this->logFile = $file;
        $LOGFILE       = $file;
      }
    }
  } // function SetDebugLevel
  
  function Debug($string, $level = DEBUG_WARNING) {
    $this->debugList[] = $string;
    if ($this->debugLevel & $level &&
        $this->logFile != null) {
      log_write($string, 'DEBUG');
    }
  } // function Debug

  function Error($string) {
    $this->isError = true;
    $this->errorList[] = $string;
    if ($this->debugLevel & DEBUG_ERROR &&
        $this->logFile != null) {
      log_write($string, 'ERROR');
    }
  } // function Error

  function GetDebug() {
    return $this->debugList;
  } // function GetDebug

  function GetError() {
    return $this->errorList;
  } // function GetError

  function IsError() {
    return $this->isError;
  } // function IsError

} // class Object




class Sparkline extends Object {

  var $imageX;
  var $imageY;
  var $imageHandle;
  var $graphAreaPx;
  var $graphAreaPt;
  var $colorList;
  var $colorBackground;
  var $lineSize;

  ////////////////////////////////////////////////////////////////////////////
  // constructor
  //
  function Sparkline($catch_errors = true) {
    parent::Object($catch_errors);

    $this->colorList       = array();
    $this->colorBackground = 'white';
    $this->lineSize        = 1;
    $this->graphAreaPx = array(array(0, 0), array(0, 0)); // px(L, B), px(R, T)
  } // function Sparkline

  ////////////////////////////////////////////////////////////////////////////
  // init
  //
  function Init($x, $y) {
    $this->Debug("Sparkline :: Init($x, $y)", DEBUG_CALLS);

    $this->imageX    = $x;
    $this->imageY    = $y;

    // Set functions may have already set graphAreaPx offsets; add image dimensions
    //
    $this->graphAreaPx = array(array($this->graphAreaPx[0][0],
                                   $this->graphAreaPx[0][1]),
                             array($this->graphAreaPx[1][0] + $x - 1,
                                   $this->graphAreaPx[1][1] + $y - 1));
    
    $this->imageHandle = $this->CreateImageHandle($x, $y);

    // load default colors; set all color handles
    //
    $this->SetColorDefaults();
    while (list($k, $v) = each($this->colorList)) {
      $this->SetColorHandle($k, $this->DrawColorAllocate($k, $this->imageHandle));
    }
    reset($this->colorList);

    if ($this->IsError()) {
      return false;
    } else {
      return true;
    }
  } // function Init

  ////////////////////////////////////////////////////////////////////////////
  // color, drawing setup functions
  //
  function SetColor($name, $r, $g, $b) {
    $this->Debug("Sparkline :: SetColor('$name', $r, $g, $b)", DEBUG_SET);
    $name = strtolower($name);
    $this->colorList[$name] = array('rgb' => array($r, $g, $b));
  } // function SetDecColor

  function SetColorHandle($name, $handle) {
    $this->Debug("Sparkline :: SetColorHandle('$name', $handle)", DEBUG_SET);
    $name = strtolower($name);
    if (array_key_exists($name, $this->colorList)) {
      $this->colorList[$name]['handle'] = $handle;
      return true;
    } else {
      return false;
    }
  } // function SetColorHandle

  function SetColorHex($name, $r, $g, $b) {
    $this->Debug("Sparkline :: SetColorHex('$name', $r, $g, $b)", DEBUG_SET);
    $this->SetColor($name, hexdec($r), hexdec($g), hexdec($b));
  } // function SetHexColor

  function SetColorHtml($name, $rgb) {
    $this->Debug("Sparkline :: SetColorHtml('$name', '$rgb')", DEBUG_SET);
    $rgb = trim($rgb, '#');
    $this->SetColor($name, hexdec(substr($rgb, 0, 2)), hexdec(substr($rgb, 2, 2)), hexdec(substr($rgb, 4, 2)));
  } // function SetHexColor

  function SetColorBackground($name) {
    $this->Debug("Sparkline :: SetColorBackground('$name')", DEBUG_SET);
    $this->colorBackground = $name;
  } // function SetColorBackground

  function GetColor($name) {
    if (array_key_exists($name, $this->colorList)) {
      return $this->colorList[$name]['rgb'];
    } else {
      return false;
    }
  } // function GetColor

  function GetColorHandle($name) {
    $name = strtolower($name);
    if (array_key_exists($name, $this->colorList)) {
      return $this->colorList[$name]['handle'];
    } else {
      $this->Debug("Sparkline :: GetColorHandle color '$name' not set", DEBUG_WARNING);
      return false;
    }
  } // function GetColorHandle

  function SetColorDefaults() {
    $this->Debug("Sparkline :: SetColorDefaults()", DEBUG_SET);
    $colorDefaults = array(array('aqua',   '#00FFFF'),
                           array('black',  '#010101'), // TODO failure if 000000?
                           array('blue',   '#0000FF'),
                           array('fuscia', '#FF00FF'),
                           array('gray',   '#808080'),
                           array('grey',   '#808080'),
                           array('green',  '#008000'),
                           array('lime',   '#00FF00'),
                           array('maroon', '#800000'),
                           array('navy',   '#000080'),
                           array('olive',  '#808000'),
                           array('purple', '#800080'),
                           array('red',    '#FF0000'),
                           array('silver', '#C0C0C0'),
                           array('teal',   '#008080'),
                           array('white',  '#FFFFFF'),
                           array('yellow', '#FFFF00'));
    while (list(, $v) = each($colorDefaults)) {
      if (!array_key_exists($v[0], $this->colorList)) {
        $this->SetColorHtml($v[0], $v[1]);
      }
    }
  } // function SetColorDefaults

  function SetLineSize($size) {
    $this->Debug("Sparkline :: SetLineSize($size)", DEBUG_CALLS);

    $this->lineSize = $size;
  } // function SetLineSize

  function GetLineSize() {
    return($this->lineSize);
  } // function GetLineSize

  function SetPadding($T, $R = null, $B = null, $L = null) {
    $this->Debug("Sparkline :: SetPadding($T, $R, $B, $L)", DEBUG_CALLS);

    if (null == $R &&
        null == $B &&
        null == $L) {
      $this->graphAreaPx = array(array($this->graphAreaPx[0][0] + $T,
                                       $this->graphAreaPx[0][1] + $T),
                                 array($this->graphAreaPx[1][0] - $T,
                                       $this->graphAreaPx[1][1] - $T));
    } else {
      $this->graphAreaPx = array(array($this->graphAreaPx[0][0] + $L,
                                       $this->graphAreaPx[0][1] + $B),
                                 array($this->graphAreaPx[1][0] - $R,
                                       $this->graphAreaPx[1][1] - $T));
    }
  } // function SetPadding

  ////////////////////////////////////////////////////////////////////////////
  // canvas setup
  //
  function CreateImageHandle($x, $y) {
    $this->Debug("Sparkline :: CreateImageHandle($x, $y)", DEBUG_CALLS);

    $handle = @imagecreatetruecolor($x, $y);
    if (!is_resource($handle)) {
      $handle = imagecreate($x, $y);
      $this->Debug('imagecreatetruecolor unavailable', DEBUG_WARNING);
    }

    if (!is_resource($handle)) {
      $this->Debug('imagecreate unavailable', DEBUG_WARNING);
      $this->Error('could not create image; GD imagecreate functions unavailable');
    }

    return $handle;
  } // function CreateImageHandle

  ////////////////////////////////////////////////////////////////////////////
  // drawing primitives
  //
  // NB: all drawing primitives use the coordinate system where (0,0) 
  //     corresponds to the bottom left of the image, unlike y-inverted 
  //     PHP gd functions
  //
  function DrawBackground($handle = false) {
    $this->Debug("Sparkline :: DrawBackground()", DEBUG_DRAW);

    if (!$this->IsError()) {
      if ($handle === false) $handle = $this->imageHandle;
      return $this->DrawRectangleFilled(0, 
                                        0, 
                                        imagesx($handle) - 1,
                                        imagesy($handle) - 1,
                                        $this->colorBackground,
                                        $handle);
    }
  } // function DrawBackground

  function DrawColorAllocate($color, $handle = false) {
    $this->Debug("Sparkline :: DrawColorAllocate('$color')", DEBUG_DRAW);

    if (!$this->IsError() &&
        $colorRGB = $this->GetColor($color)) {
      if ($handle === false) $handle = $this->imageHandle;
      return imagecolorallocate($handle,
                                $colorRGB[0], 
                                $colorRGB[1], 
                                $colorRGB[2]);
    }
  } // function DrawColorAllocate

  function DrawFill($x, $y, $color, $handle = false) {
    $this->Debug("Sparkline :: DrawFill($x, $y, '$color')", DEBUG_DRAW);

    if (!$this->IsError() &&
        $colorHandle = $this->GetColorHandle($color)) {
      if ($handle === false) $handle = $this->imageHandle;
      return imagefill($handle,
                       $x, 
                       $this->TxGDYToSLY($y, $handle), 
                       $colorHandle);
    }
  } // function DrawFill

  function DrawLine($x1, $y1, $x2, $y2, $color, $thickness = 1, $handle = false) {
    $this->Debug("Sparkline :: DrawLine($x1, $y1, $x2, $y2, '$color', $thickness)", DEBUG_DRAW);

    if (!$this->IsError() &&
        $colorHandle = $this->GetColorHandle($color)) {
      if ($handle === false) $handle = $this->imageHandle;

      imagesetthickness($handle, $thickness);
      $result = imageline($handle, 
                          $x1,
                          $this->TxGDYToSLY($y1, $handle),
                          $x2,
                          $this->TxGDYToSLY($y2, $handle),
                          $colorHandle);
      imagesetthickness($handle, 1);
      return $result;
    }
  } // function DrawLine

  function DrawPoint($x, $y, $color, $handle = false) {
    $this->Debug("Sparkline :: DrawPoint($x, $y, '$color')", DEBUG_DRAW);

    if (!$this->IsError() &&
        $colorHandle = $this->GetColorHandle($color)) {
      if ($handle === false) $handle = $this->imageHandle;
      return imagesetpixel($handle, 
                           $x, 
                           $this->TxGDYToSLY($y, $handle), 
                           $colorHandle);
    }
  } // function DrawPoint

  function DrawRectangle($x1, $y1, $x2, $y2, $color, $handle = false) {
    $this->Debug("Sparkline :: DrawRectangle($x1, $y1, $x2, $y2 '$color')", DEBUG_DRAW);

    if (!$this->IsError() &&
        $colorHandle = $this->GetColorHandle($color)) {
      if ($handle === false) $handle = $this->imageHandle;
      return imagerectangle($handle, 
                            $x1, 
                            $this->TxGDYToSLY($y1, $handle), 
                            $x2, 
                            $this->TxGDYToSLY($y2, $handle), 
                            $colorHandle);
    }
  } // function DrawRectangle

  function DrawRectangleFilled($x1, $y1, $x2, $y2, $color, $handle = false) {
    $this->Debug("Sparkline :: DrawRectangleFilled($x1, $y1, $x2, $y2 '$color')", DEBUG_DRAW);

    if (!$this->IsError() &&
        $colorHandle = $this->GetColorHandle($color)) {
      // NB: switch y1, y2 post conversion
      //
      if ($y1 < $y2) {
        $yt = $y1;
        $y1 = $y2;
        $y2 = $yt;
      }

      if ($handle === false) $handle = $this->imageHandle;
      return imagefilledrectangle($handle, 
                                  $x1,
                                  $this->TxGDYToSLY($y1, $handle),
                                  $x2,
                                  $this->TxGDYToSLY($y2, $handle),
                                  $colorHandle);
    }
  } // function DrawRectangleFilled

  function DrawCircleFilled($x, $y, $diameter, $color, $handle = false) {
    $this->Debug("Sparkline :: DrawCircleFilled($x, $y, $diameter, '$color')", DEBUG_DRAW);

    if (!$this->IsError() &&
        $colorHandle = $this->GetColorHandle($color)) {
      if ($handle === false) $handle = $this->imageHandle;
      return imagefilledellipse($handle, 
                                $x,
                                $this->TxGDYToSLY($y, $handle),
                                $diameter,
                                $diameter,
                                $colorHandle);
    }
  } // function DrawCircleFilled

  function DrawText($string, $x, $y, $color, $font = FONT_1, $handle = false) {
    $this->Debug("Sparkline :: DrawText('$string', $x, $y, '$color', $font)", DEBUG_DRAW);
      
    if (!$this->IsError() &&
        $colorHandle = $this->GetColorHandle($color)) {
      // adjust for font height so x,y corresponds to bottom left of font
      //
      if ($handle === false) $handle = $this->imageHandle;
      return imagestring($handle, 
                         $font, 
                         $x,
                         $this->TxGDYToSLY($y + imagefontheight($font), $handle),
                         $string,
                         $colorHandle);
    }
  } // function DrawText

  function DrawTextRelative($string, $x, $y, $color, $position, $padding = 2, $font = FONT_1, $handle = false) {
    $this->Debug("Sparkline :: DrawTextRelative('$string', $x, $y, '$color', $position, $font, $padding)", DEBUG_DRAW);
      
    if (!$this->IsError() &&
        $colorHandle = $this->GetColorHandle($color)) {
      if ($handle === false) $handle = $this->imageHandle;

      // rendered text width, height
      //
      $textHeight = imagefontheight($font);
      $textWidth  = imagefontwidth($font) * strlen($string);

      // set (pxX, pxY) based on position and point
      //
      switch($position) {
      case TEXT_TOP:
        $x = $x - round($textWidth / 2);
        $y = $y + $padding;
        break;
        
      case TEXT_RIGHT:
        $x = $x + $padding;
        $y = $y - round($textHeight / 2);
        break;
        
      case TEXT_BOTTOM:
        $x = $x - round($textWidth / 2);
        $y = $y - $padding - $textHeight;
        break;
        
      case TEXT_LEFT:
      default:
        $x = $x - $padding - $textWidth;
        $y = $y - round($textHeight / 2);
        break;
      }

      // truncate bounds based on string size in pixels, image bounds
      // order: TRBL
      //
      $y = min($y, $this->GetImageHeight() - $textHeight);
      $x = min($x, $this->GetImageWidth() - $textWidth);
      $y = max($y, 0);
      $x = max($x, 0);

      return $this->DrawText($string,
                             $x,
                             $y,
                             $color,
                             $font,
                             $handle);
    }
  } // function DrawTextRelative

  function DrawImageCopyResampled($dhandle, $shandle, $dx, $dy, $sx, $sy, $dw, $dh, $sw, $sh) {
    $this->Debug("Sparkline :: DrawImageCopyResampled($dhhandle, $shandle, $dx, $dy, $sx, $sy, $dw, $dh, $sw, $sh)", DEBUG_DRAW);
    if (!$this->IsError()) {
      return imagecopyresampled($dhandle,  // dest handle
                                $shandle,  // src  handle
                                $dx, $dy,  // dest x, y
                                $sx, $sy,  // src  x, y
                                $dw, $dh,  // dest w, h
                                $sw, $sh); // src  w, h
    }
  } // function DrawImageCopyResampled
  
  ////////////////////////////////////////////////////////////////////////////
  // coordinate system functions
  //   world coordinates are referenced as points or pt
  //   graph coordinates are referenced as pixels or px
  //   sparkline inverts GD Y pixel coordinates; the bottom left of the 
  //     image rendering area is px(0,0)
  //   all coordinate transformation functions are prefixed with Tx
  //   all coordinate transformation functions depend on a valid image handle
  //     and will only return valid results after all Set* calls are performed
  //
  function TxGDYToSLY($gdY, $handle) {
    return imagesy($handle) - 1 - $gdY;
  } // function TxGDYToSLY

  function TxPxToPt($pxX, $pxY, $handle) {
    // TODO;  must occur after data series conversion
  } // function TxPxToPt

  function TxPtToPx($ptX, $ptY, $handle) {
    // TODO;  must occur after data series conversion
  } // function TxPtToPx

  function GetGraphWidth() {
    return $this->graphAreaPx[1][0] - $this->graphAreaPx[0][0];
  } // function GetGraphWidth

  function GetGraphHeight() {
    return $this->graphAreaPx[1][1] - $this->graphAreaPx[0][1];
  } // function GetGraphHeight

  function GetImageWidth() {
    return $this->imageX;
  } // function GetImageWidth

  function GetImageHeight() {
    return $this->imageY;
  } // function GetImageHeight

  ////////////////////////////////////////////////////////////////////////////
  // image output
  //
  function Output($file = '') {

    $this->Debug("Sparkline :: Output($file)", DEBUG_CALLS);

    if ($this->IsError()) {
      $colorError = imagecolorallocate($this->imageHandle, 0xFF, 0x00, 0x00);
      imagestring($this->imageHandle, 
                  1, 
                  ($this->imageX / 2) - (5 * imagefontwidth(1) / 2), 
                  ($this->imageY / 2) - (imagefontheight(1) / 2), 
                  "ERROR", 
                  $colorError);
    }

    if ($file == '') {
      header('Content-type: image/png');
      imagepng($this->imageHandle);
    } else {
      imagepng($this->imageHandle, $file);
    }

    $this->Debug('Sparkline :: Output - total execution time: ' . round($this->microTimer() - $this->startTime, 4) . ' seconds', DEBUG_STATS);
  } // function Output

  function OutputToFile($file) {
    $this->Output($file);
  } // function OutputToFile

} // class Sparkline


class Sparkline_Line extends Sparkline {

  var $dataSeries;
  var $dataSeriesStats;
  var $dataSeriesConverted;
  var $yMin;
  var $yMax;
  var $featurePoint;

  ////////////////////////////////////////////////////////////////////////////
  // constructor
  //
  function Sparkline_Line($catch_errors = true) {
    parent::Sparkline($catch_errors);

    $this->dataSeries          = array();
    $this->dataSeriesStats     = array();
    $this->dataSeriesConverted = array();

    $this->featurePoint        = array();
  } // function Sparkline

  ////////////////////////////////////////////////////////////////////////////
  // data setting
  //
  function SetData($x, $y, $series = 1) {
    $x = trim($x);
    $y = trim($y);

    $this->Debug("Sparkline_Line :: SetData($x, $y, $series)", DEBUG_SET);

    if (!is_numeric($x) || 
        !is_numeric($y)) {
      $this->Debug("Sparkline_Line :: SetData rejected values($x, $y) in series $series", DEBUG_WARNING);
      return false;
    } // if

    $this->dataSeries[$series][$x] = $y;
   
    if (!isset($this->dataSeriesStats[$series]['yMin']) ||
        $y < $this->dataSeriesStats[$series]['yMin']) {
      $this->dataSeriesStats[$series]['yMin'] = $y;
    }

    if (!isset($this->dataSeriesStats[$series]['xMin']) ||
        $x < $this->dataSeriesStats[$series]['xMin']) {
      $this->dataSeriesStats[$series]['xMin'] = $x;
    }

    if (!isset($this->dataSeriesStats[$series]['yMax']) ||
        $y > $this->dataSeriesStats[$series]['yMax']) {
      $this->dataSeriesStats[$series]['yMax'] = $y;
    }

    if (!isset($this->dataSeriesStats[$series]['xMax']) ||
        $x > $this->dataSeriesStats[$series]['xMax']) {
      $this->dataSeriesStats[$series]['xMax'] = $x;
    }
  } // function SetData

  function SetYMin($value) {
    $this->Debug("Sparkline_Line :: SetYMin($value)", DEBUG_SET);
    $this->yMin = $value;
  } // function SetYMin

  function SetYMax($value) {
    $this->Debug("Sparkline_Line :: SetYMax($value)", DEBUG_SET);
    $this->yMax = $value;
  } // function SetYMin

  function ConvertDataSeries($series, $xBound, $yBound) {
    $this->Debug("Sparkline_Line :: ConvertDataSeries($series, $xBound, $yBound)", DEBUG_CALLS);

    if (!isset($this->yMin)) {
      $this->yMin = $this->dataSeriesStats[$series]['yMin'];
    }

    if (!isset($this->yMin)) {
      $this->xMin = $this->dataSeriesStats[$series]['XMin'];
    }

    if (!isset($this->yMax)) {
      $this->yMax = $this->dataSeriesStats[$series]['yMax'] + ($this->yMin * -1);
    }

    if (!isset($this->xMax)) {
      $this->xMax = $this->dataSeriesStats[$series]['xMax'];
    }

    for ($i = 0; $i < sizeof($this->dataSeries[$series]); $i ++) {
      $y = round(($this->dataSeries[$series][$i] + ($this->yMin * -1)) * ($yBound / $this->yMax));
      $x = round($i * $xBound / sizeof($this->dataSeries[$series]));
      $this->dataSeriesConverted[$series][] = array($x, $y);
      $this->Debug("Sparkline :: ConvertDataSeries series $series value $i ($x, $y)", DEBUG_SET);
    }
  } // function ConvertDataSeries

  ////////////////////////////////////////////////////////////////////////////
  // features
  // 
  function SetFeaturePoint($x, $y, $color, $diameter, $text = '', $position = TEXT_TOP, $font = FONT_1) {
    $this->Debug("Sparkline_Line :: SetFeaturePoint($x, $y, '$color', $diameter, '$text')", DEBUG_CALLS);

    $this->featurePoint[] = array('ptX'      => $x,
                                  'ptY'      => $y,
                                  'color'    => $color,
                                  'diameter' => $diameter,
                                  'text'     => $text,
                                  'textpos'  => $position,
                                  'font'     => $font);
  } // function SetFeaturePoint

  ////////////////////////////////////////////////////////////////////////////
  // low quality rendering
  //
  function Render($x, $y) {
    $this->Debug("Sparkline_Line :: Render($x, $y)", DEBUG_CALLS);

    if (!parent::Init($x, $y)) {
      return false;
    }

    // convert based on graphAreaPx bounds
    //
    $this->ConvertDataSeries(1, $this->GetGraphWidth(), $this->GetGraphHeight());

    // stats debugging
    //
    $this->Debug('Sparkline_Line :: Draw' . 
                 ' series: 1 min: ' . $this->dataSeriesStats[1]['yMin'] . 
                 ' max: ' .           $this->dataSeriesStats[1]['yMax'] . 
                 ' offset: ' .        ($this->dataSeriesStats[1]['yMin'] * -1) . 
                 ' height: ' .        $this->GetGraphHeight() + 1 . 
                 ' yfactor: ' .       ($this->GetGraphHeight() / ($this->dataSeriesStats[1]['yMax'] + ($this->dataSeriesStats[1]['yMin'] * -1))));
    $this->Debug('Sparkline_Line :: Draw' .
                 ' drawing area:' . 
                 ' (' . $this->graphAreaPx[0][0] . ',' . $this->graphAreaPx[0][1] .  '), ' . 
                 ' (' . $this->graphAreaPx[1][0] . ',' . $this->graphAreaPx[1][1] .  ')');

    $this->DrawBackground();

    // draw graph
    //
    for ($i = 0; $i < sizeof($this->dataSeriesConverted[1]) - 1; $i++) {
      $this->DrawLine($this->dataSeriesConverted[1][$i][0] + $this->graphAreaPx[0][0], 
                      $this->dataSeriesConverted[1][$i][1] + $this->graphAreaPx[0][1], 
                      $this->dataSeriesConverted[1][$i+1][0] + $this->graphAreaPx[0][0], 
                      $this->dataSeriesConverted[1][$i+1][1] + $this->graphAreaPx[0][1],  
                      'black');
    }

    // draw features
    //
    while (list(, $v) = each($this->featurePoint)) {
      $pxY = round(($v['ptY'] + ($this->yMin * -1)) * ($this->GetGraphHeight() / $this->yMax));
      $pxX = round($v['ptX'] * $this->GetGraphWidth() / $this->dataSeriesStats[1]['xMax']);

      $this->DrawCircleFilled($pxX + $this->graphAreaPx[0][0], 
                              $pxY + $this->graphAreaPx[0][1], 
                              $v['diameter'], 
                              $v['color'], 
                              $this->imageHandle);
      $this->DrawTextRelative($v['text'],
                              $pxX + $this->graphAreaPx[0][0], 
                              $pxY + $this->graphAreaPx[0][1], 
                              $v['color'], 
                              $v['textpos'], 
                              round($v['diameter'] / 2),
                              $v['font'],
                              $this->imageHandle);
    }
  } // function Render

  ////////////////////////////////////////////////////////////////////////////
  // high quality rendering
  //
  function RenderResampled($x, $y) {
    $this->Debug("Sparkline_Line :: RenderResampled($x, $y)", DEBUG_CALLS);

    if (!parent::Init($x, $y)) {
      return false;
    }

    // draw background on standard image in case of resample blit miss
    //
    $this->DrawBackground($this->imageHandle);

    // convert based on virtual canvas: x based on size of dataset, y scaled proportionately
    // if size of data set is small, default to 4X target canvas size
    //
    $xVC = max(sizeof($this->dataSeries[1]), 4 * $x);
    $yVC = floor($xVC * ($this->GetGraphHeight() / $this->GetGraphWidth()));
    $this->ConvertDataSeries(1, $xVC, $yVC);

    // stats debugging
    //
    $this->Debug('Sparkline_Line :: DrawResampled' . 
                 ' series: 1 min: ' . $this->dataSeriesStats[1]['yMin'] . 
                 ' max: ' . $this->dataSeriesStats[1]['yMax'] . 
                 ' offset: ' . ($this->dataSeriesStats[1]['yMin'] * -1) . 
                 ' height: ' . $this->GetGraphHeight() . 
                 ' yfactor: ' . ($this->GetGraphHeight() / ($this->dataSeriesStats[1]['yMax'] + ($this->dataSeriesStats[1]['yMin'] * -1))), DEBUG_STATS);
    $this->Debug('Sparkline_Line :: DrawResampled' .
                 ' drawing area:' . 
                 ' (' . $this->graphAreaPx[0][0] . ',' . $this->graphAreaPx[0][1] .  '), ' . 
                 ' (' . $this->graphAreaPx[1][0] . ',' . $this->graphAreaPx[1][1] .  ')');

    // create virtual image
    // allocate colors
    // draw background, graph
    // resample and blit onto original graph
    //
    $imageVCHandle = $this->CreateImageHandle($xVC, $yVC);

    while (list($k, $v) = each($this->colorList)) {
      $this->SetColorHandle($k, $this->DrawColorAllocate($k, $imageVCHandle));
    }
    reset($this->colorList);

    $this->DrawBackground($imageVCHandle);

    for ($i = 0; $i < sizeof($this->dataSeriesConverted[1]) - 1; $i++) {
      $this->DrawLine($this->dataSeriesConverted[1][$i][0],
                      $this->dataSeriesConverted[1][$i][1],
                      $this->dataSeriesConverted[1][$i+1][0],
                      $this->dataSeriesConverted[1][$i+1][1],
                      'black', 
                      $this->GetLineSize(), 
                      $imageVCHandle);
    }

    $this->DrawImageCopyResampled($this->imageHandle, 
                                  $imageVCHandle, 
                                  $this->graphAreaPx[0][0], // dest x
                                  $this->GetImageHeight() - $this->graphAreaPx[1][1], // dest y
                                  0, 0,                     // src x, y
                                  $this->GetGraphWidth(),   // dest width
                                  $this->GetGraphHeight(),  // dest height
                                  $xVC,                     // src  width
                                  $yVC);                    // src  height

    // draw features
    //
    while (list(, $v) = each($this->featurePoint)) {
      $pxY = round(($v['ptY'] + ($this->yMin * -1)) * ($this->GetGraphHeight() / $this->yMax));
      $pxX = round($v['ptX'] * $this->GetGraphWidth() / $this->dataSeriesStats[1]['xMax']);

      $this->DrawCircleFilled($pxX + $this->graphAreaPx[0][0], 
                              $pxY + $this->graphAreaPx[0][1], 
                              $v['diameter'], 
                              $v['color'], 
                              $this->imageHandle);
      $this->DrawTextRelative($v['text'],
                              $pxX + $this->graphAreaPx[0][0], 
                              $pxY + $this->graphAreaPx[0][1], 
                              $v['color'], 
                              $v['textpos'], 
                              round($v['diameter'] / 2),
                              $v['font'],
                              $this->imageHandle);
    }
  } // function RenderResampled
} // class Sparkline_Line

?>
