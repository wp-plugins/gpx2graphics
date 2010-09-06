<?php

class Gpx2GraphicsLoader_Geo_File {
	private $_filename;
	private $_trackInfo = array();
	private $_pointList = array();
	private $_totalDistance = 0;
	private $_startTime;
	private $_minLon;
	private $_maxLon;
	private $_minLat;
	private $_maxLat;
	
	public function __construct($filename) {
		$this->_filename = $filename;
	}
	
	public function setTrackInfo() {
		$xml = simplexml_load_file($this->_filename);
		$metaData = $xml->metadata;
		$this->_trackInfo['time'] = date("Y-m-d H:i:s",strtotime(trim($metaData->time)));
		$track = $xml->trk;
		$this->_trackInfo['name'] = trim($track->name);
		$this->setTrackPoints($track->trkseg);
		$this->setTotalDistance();
	}
	
	public function getCenter() {
		$centerLat = $this->_minLat + ($this->_maxLat-$this->_minLat) / 2;
		$centerLon = $this->_minLon + ($this->_maxLon-$this->_minLon) / 2;
		return $centerLat . ','.$centerLon;
	}
	
	public function getZoomLevel() {
		$pointA = new Gpx2GraphicsLoader_Geo_Point(null);
		$pointA->setLat($this->_minLat);
		$pointA->setLon($this->_minLon);
		$pointB = new Gpx2GraphicsLoader_Geo_Point(null);
		$pointB->setLat($this->_maxLat);
		$pointB->setLon($this->_maxLon);
		$distance = $this->getDistance($pointA,$pointB);
		if ($distance<0.2) return 16;
		if ($distance<0.5) return 16;
		if ($distance<1) return 15;
		if ($distance<2) return 14;
		if ($distance<3) return 13;
		if ($distance<7) return 13;
		if ($distance<15) return 11;
		if ($distance<30) return 11;
		if ($distance<60) return 9;
		if ($distance<120) return 8;
		if ($distance<320) return 7;
		return 5;
	}
	
	public function getTrackInfo($name) {
		if (!isset($this->_trackInfo[$name])) return 'value does not exist'; 
		return $this->_trackInfo[$name];		
	}
	
	public function getElevationChart($width = 600, $height = 300,$filename) {
		return Gpx2GraphicsLoader_Geo_Graph::elevationGraph($width,$height,$this,$filename);
	}

	public function getSpeedChart($width = 600, $height = 300,$filename) {
		return Gpx2GraphicsLoader_Geo_Graph::speedGraph($width,$height,$this,$filename);
	}
	
	public function getMaxSpeed() {
		$maxSpeed = 0;
		foreach ($this->_pointList as $point) {
			if ( ($point->getSpeed()>$maxSpeed) && ($point->getSpeed()<300) ){
				$maxSpeed = $point->getSpeed();
			}
		}
		return $maxSpeed;
	}
	
	public function getPointList() {
		return $this->_pointList;
	}
	
	public function getMinHeight() {
		return $this->_trackInfo['min_elevation'];
	}
	
	public function getMaxHeight() {
		return $this->_trackInfo['max_elevation'];
	}
	
	public function getFileName() {
		echo $this->_filename;
		return $this->_filename;
	}
	
	public function getTotalDistance() {
		return $this->_totalDistance;
	}
	
	private function setTotalDistance() {
		$distance = 0;
		$firstPoint = $this->_pointList[0];
		for ($i=1;$i<count($this->_pointList);$i++) {
			$secondPoint = $this->_pointList[$i];
			$distanceBetweenPoints = $this->getDistance($firstPoint,$secondPoint);
			$distance += $distanceBetweenPoints;
			$secondPoint->setDistanceUntilPoint($distance);
			$secondPoint->setDistanceFromPreviousPoint($distanceBetweenPoints);
			$secondPoint->setSpeed();
			$firstPoint = $secondPoint;
		}
		$this->_totalDistance = $distance;
	}
	
	private function setTrackPoints($pointList) {
		$iCnt = 0;
		foreach ($pointList->trkpt as $point) {
			$geoPoint = new Gpx2GraphicsLoader_Geo_Point($point);
			if ($iCnt==0) {
				$this->_startTime = $geoPoint->getTimeStamp();
				$geoPoint->setTimeUntilPoint(0);
				$this->_trackInfo['max_elevation'] = $geoPoint->getElevation();
				$this->_trackInfo['min_elevation'] = $geoPoint->getElevation();
				$this->_minLat = $geoPoint->getLat();
				$this->_maxLat = $geoPoint->getLat();
				$this->_minLon = $geoPoint->getLon();
				$this->_maxLon = $geoPoint->getLon();
			} else {
				$geoPoint->setTimeUntilPoint( $geoPoint->getTimeStamp() - $this->_startTime );
				
				$timeFromPreviousPoint = $geoPoint->getTimeStamp() - $this->_pointList[count($this->_pointList)-1]->getTimeStamp() ;
				$timeFromPreviousPoint = $timeFromPreviousPoint;
				$geoPoint->setTimeFromPreviousPoint($timeFromPreviousPoint);
				
				if ($geoPoint->getElevation()<$this->_trackInfo['min_elevation']) 
					$this->_trackInfo['min_elevation'] = $geoPoint->getElevation();	
				if ($geoPoint->getElevation()>$this->_trackInfo['max_elevation']) 
					$this->_trackInfo['max_elevation'] = $geoPoint->getElevation();

				if ($geoPoint->getLat()>$this->_maxLat) $this->_maxLat = $geoPoint->getLat();
				if ($geoPoint->getLon()>$this->_maxLon) $this->_maxLon = $geoPoint->getLon();
				if ($geoPoint->getLat()<$this->_minLat) $this->_minLat = $geoPoint->getLat();
				if ($geoPoint->getLon()<$this->_minLon) $this->_minLon = $geoPoint->getLon();
			}
			
			$iCnt++;
			$this->_pointList[] = $geoPoint;
		}
	}
	
	private function getDistance($pointA,$pointB) {
		$r = 6367000; // Radius of the Earth in meters
	    //convert degrees to radians
	    $lat1 = ($pointA->getLat() * pi() ) / 180;
	    $lon1 = ($pointA->getLon() * pi() ) / 180;
	    $lat2 = ($pointB->getLat() * pi() ) / 180;
	    $lon2 = ($pointB->getLon() * pi() ) / 180;

	    $dlon = $lon2 - $lon1;
	    $dlat = $lat2 - $lat1;
	    $a = pow(sin($dlat/2), 2) + cos($lat1) * cos($lat2) * pow(sin($dlon/2),2);
	    $intermediate_result = 2 * asin(min(1,sqrt($a)));
	    $distance = $r * $intermediate_result;
		$distance = $distance/1000;
	    return round($distance,4);
	}
}
