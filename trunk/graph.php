<?php

abstract class Gpx2GraphicsLoader_Geo_Graph {
	
	static function speedGraph($width,$height,$geoFile,$fileName) {
		
		$image = imagecreatetruecolor($width,$height);
		$white = imagecolorallocate($image,255,255,255);
		imagefill($image,0,0,$white);

		$yMargin = 20;
		$xLeftMargin = 30;
		$xRightMargin = 40;
		
		$gray = imagecolorallocate($image,119,119,119);
		imageline($image,$xLeftMargin,$yMargin,$xLeftMargin,$height - $yMargin,$gray);
		
		// nullijn tekenen
		$zeroLine = $height - $yMargin;
		imageline($image,$xLeftMargin,$zeroLine,$width - $xRightMargin ,$zeroLine,$gray);
		imagestring($image,2,10,$zeroLine-10,"0",$gray);
		imagestring($image,2,10,$yMargin-10,round($geoFile->getMaxSpeed()),$gray);
		imagestring($image,2,10,$zeroLine,"km/u",$gray);
		
		imagestring($image,2,$width-$xRightMargin-30,0,"Speed Graph",$gray);
		imagestring($image,2,$width - $xRightMargin + 5,$zeroLine - 10, round($geoFile->getTotalDistance()) . "km",$gray);
		$yPixelsPerKm = (($height - ($yMargin*2)) / round($geoFile->getMaxSpeed()));
		$xWidth = $width - ($xLeftMargin + $xRightMargin);
		$distancePerPixel = $geoFile->getTotalDistance() / $xWidth;
		$green = imagecolorallocate($image,149,205,133);
		$iPoint = 0;
		$iWidth = 1;
		$pointList = $geoFile->getPointList();
		while ($iWidth<$xWidth) {
			$pDistance = $iWidth*$distancePerPixel;
			if ($iPoint < count($pointList)) {
				$point = $pointList[$iPoint];
				while ($point->getDistanceUntilPoint() < $pDistance) {
					$iPoint++;
					$point = $pointList[$iPoint];
				}
			}
			$speed = $point->getSpeed();
			$lineHeight = round($speed * $yPixelsPerKm);
			
			if ($speed<300) { 
				imageline($image,$iWidth + $xLeftMargin,$zeroLine-1,$iWidth + $xLeftMargin,$zeroLine - $lineHeight,$green);
			}
			$iWidth++;
		}
		imagepng($image, $fileName);
		return $fileName;
	}
	
	static function elevationGraph($width,$height,$geoFile,$fileName) {
		$image = imagecreatetruecolor($width,$height);
		$white = imagecolorallocate($image,255,255,255);
		imagefill($image,0,0,$white);

		$yMargin = 20;
		$xLeftMargin = 30;
		$xRightMargin = 40;
		
		$gray = imagecolorallocate($image,119,119,119);
		imageline($image,$xLeftMargin,$yMargin,$xLeftMargin,$height - $yMargin,$gray);
		
		// nullijn tekenen
		$minHeight = $geoFile->getMinHeight();
		$maxHeight = $geoFile->getMaxHeight();
		if ($minHeight>0) {
			$minHeight = 0;
			$totalHeight = $maxHeight - $minHeight;
		} else {
			$totalHeight = abs($minHeight) + abs($maxHeight);
		}
		$yPixelsPerMeter = (($height - ($yMargin*2)) / $totalHeight);
		$zeroLine = $yMargin + ($maxHeight*$yPixelsPerMeter);
		imageline($image,$xLeftMargin,$zeroLine,$width - $xRightMargin ,$zeroLine,$gray);
		if ($geoFile->getMinHeight()<0) {
			imagestring($image,2,3,$zeroLine-10,"0m",$gray);
		}
		imagestring($image,2,$width-$xRightMargin-54,0,"Elevation Graph",$gray);
		
		imagestring($image,2,3,$yMargin-10,round($geoFile->getMaxHeight()) . "m",$gray);
		imagestring($image,2,2,$height - $yMargin - 10,round($geoFile->getMinHeight()) ."m",$gray);
		
		imagestring($image,2,$width - $xRightMargin + 5,$zeroLine - 10, round($geoFile->getTotalDistance()) . "km",$gray);

		$xWidth = $width - ($xLeftMargin + $xRightMargin);
		$distancePerPixel = $geoFile->getTotalDistance() / $xWidth;
		
		$green = imagecolorallocate($image,149,205,133);
		$iPoint = 0;
		$iWidth = 1;
		$pointList = $geoFile->getPointList();
		while ($iWidth<$xWidth) {
			$pDistance = $iWidth*$distancePerPixel;
			if ($iPoint < count($pointList)) {
				$point = $pointList[$iPoint];
				while ($point->getDistanceUntilPoint() < $pDistance) {
					$iPoint++;
					$point = $pointList[$iPoint];
				}
			}
			$elevation = $point->getElevation();
			$lineHeight = round(abs($elevation) * $yPixelsPerMeter);
			if ($point->getSpeed()<300) {
				if ($elevation>0) {
					imageline($image,$iWidth + $xLeftMargin,$zeroLine-1,$iWidth + $xLeftMargin,$zeroLine - $lineHeight,$green);
				} else {
					imageline($image,$iWidth + $xLeftMargin,$zeroLine+1,$iWidth + $xLeftMargin,$zeroLine + $lineHeight,$green);
				}
			}
			$iWidth++;
		}
		imagepng($image,$fileName);
		return $fileName;
	}
	
}
